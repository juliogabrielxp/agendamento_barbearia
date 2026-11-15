<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Contracts\EnviadorDeCodigoDeConfirmacaoInterface;
use App\Domain\Exceptions\EnvioDeEmailFalhouException;
use App\Infrastructure\Notifications\CodigoDeConfirmacaoMail;
use App\Infrastructure\Notifications\ContaJaExisteMail;
use App\Infrastructure\Persistence\Eloquent\UserModel;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use RuntimeException;
use Tests\Feature\Concerns\CriaCenarioDeAgenda;
use Tests\TestCase;

class CadastroComConfirmacaoDeEmailTest extends TestCase
{
    use CriaCenarioDeAgenda;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->travelTo('2026-11-13 10:00:00');
        $this->criarEstabelecimento('dona@studio.com');
    }

    public function test_cadastro_envia_codigo_e_nao_cria_a_conta_antes_da_confirmacao(): void
    {
        $response = $this->pedirCadastro('maria@email.com');

        $this->assertSame(202, $response->getStatusCode(), $response->getContent());
        $this->assertDatabaseMissing('users', ['email' => 'maria@email.com']);
        $this->assertDatabaseMissing('clientes', ['email' => 'maria@email.com']);
        $this->assertGuest();

        Mail::assertSent(CodigoDeConfirmacaoMail::class, fn (CodigoDeConfirmacaoMail $mail) => $mail->hasTo('maria@email.com')
            && $mail->nomeDoEstabelecimento === 'Estabelecimento dona@studio.com'
            && preg_match('/^\d{6}$/', $mail->codigo) === 1);
    }

    public function test_codigo_certo_cria_a_conta_e_abre_a_sessao(): void
    {
        $this->pedirCadastro('maria@email.com');

        $response = $this->postJson('/cadastro-cliente/confirmar', ['codigo' => $this->codigoEnviadoPara('maria@email.com')]);

        $this->assertSame(201, $response->getStatusCode(), $response->getContent());

        $usuario = UserModel::where('email', 'maria@email.com')->firstOrFail();
        $this->assertAuthenticatedAs($usuario);
        $this->assertTrue(Hash::check('senha-segura-123', $usuario->password));
        $this->assertDatabaseHas('clientes', ['user_id' => $usuario->id, 'nome' => 'Maria Silva']);
        $this->assertDatabaseMissing('cadastros_pendentes', ['email' => 'maria@email.com']);
    }

    public function test_codigo_errado_nao_cria_a_conta(): void
    {
        $this->pedirCadastro('maria@email.com');

        $response = $this->postJson('/cadastro-cliente/confirmar', ['codigo' => $this->codigoErrado('maria@email.com')]);

        $response->assertStatus(422)->assertJsonValidationErrors('codigo');
        $this->assertDatabaseMissing('users', ['email' => 'maria@email.com']);
        $this->assertGuest();
    }

    public function test_depois_de_cinco_codigos_errados_nem_o_certo_vale_mais(): void
    {
        $this->pedirCadastro('maria@email.com');
        $codigo = $this->codigoEnviadoPara('maria@email.com');

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/cadastro-cliente/confirmar', ['codigo' => $this->codigoErrado('maria@email.com')])
                ->assertStatus(422);
        }

        $response = $this->postJson('/cadastro-cliente/confirmar', ['codigo' => $codigo]);

        $response->assertStatus(422)->assertJsonValidationErrors('codigo');
        $this->assertStringContainsString('Muitas tentativas', $response->json('message'));
        $this->assertDatabaseMissing('users', ['email' => 'maria@email.com']);
    }

    public function test_codigo_expirado_nao_vale(): void
    {
        $this->pedirCadastro('maria@email.com');
        $codigo = $this->codigoEnviadoPara('maria@email.com');

        $this->travel(15)->minutes();

        $response = $this->postJson('/cadastro-cliente/confirmar', ['codigo' => $codigo]);

        $response->assertStatus(422)->assertJsonValidationErrors('codigo');
        $this->assertDatabaseMissing('users', ['email' => 'maria@email.com']);
    }

    public function test_reenvio_troca_o_codigo_e_o_anterior_para_de_valer(): void
    {
        $this->pedirCadastro('maria@email.com');
        $codigoAntigo = $this->codigoEnviadoPara('maria@email.com');

        $this->travel(61)->seconds();
        $this->postJson('/cadastro-cliente/reenviar-codigo')->assertStatus(202);

        $codigoNovo = $this->codigoEnviadoPara('maria@email.com');
        Mail::assertSentCount(2);

        if ($codigoAntigo !== $codigoNovo) {
            $this->postJson('/cadastro-cliente/confirmar', ['codigo' => $codigoAntigo])->assertStatus(422);
        }

        $this->postJson('/cadastro-cliente/confirmar', ['codigo' => $codigoNovo])->assertStatus(201);
    }

    public function test_reenvio_antes_de_um_minuto_e_recusado(): void
    {
        $this->pedirCadastro('maria@email.com');

        $this->travel(30)->seconds();

        $this->postJson('/cadastro-cliente/reenviar-codigo')->assertStatus(429);
        Mail::assertSentCount(1);
    }

    public function test_pedir_cadastro_de_novo_antes_de_um_minuto_nao_manda_outro_email(): void
    {
        $this->pedirCadastro('maria@email.com');

        $this->pedirCadastro('maria@email.com')->assertStatus(429);

        Mail::assertSentCount(1);
    }

    public function test_quem_reenvia_o_cadastro_com_o_email_de_outra_pessoa_nao_define_a_senha_dela(): void
    {
        // A dona do e-mail começa o cadastro no navegador dela
        $this->pedirCadastro('maria@email.com', 'senha-da-maria-123');
        $sessaoDaMaria = session('cadastro_pendente');

        // Um terceiro, em outro navegador, refaz o cadastro com o e-mail dela e a senha dele
        $this->travel(61)->seconds();
        $this->flushSession();
        $this->pedirCadastro('maria@email.com', 'senha-do-atacante-1')->assertStatus(202);

        // Maria recebe o código novo e digita no navegador dela
        $response = $this->withSession(['cadastro_pendente' => $sessaoDaMaria])
            ->postJson('/cadastro-cliente/confirmar', ['codigo' => $this->codigoEnviadoPara('maria@email.com')]);

        $response->assertStatus(422)->assertJsonValidationErrors('codigo');
        $this->assertDatabaseMissing('users', ['email' => 'maria@email.com']);
    }

    public function test_confirmacao_de_email_diferente_retorna_422_sem_mandar_codigo(): void
    {
        $response = $this->pedirCadastro('maria@email.com', confirmacaoDoEmail: 'maria@emial.com');

        $response->assertStatus(422)->assertJsonValidationErrors(['email' => 'Os e-mails não são iguais.']);
        $this->assertDatabaseMissing('cadastros_pendentes', ['email' => 'maria@email.com']);
        Mail::assertNothingSent();
    }

    public function test_cadastro_sem_confirmacao_de_email_retorna_422(): void
    {
        $response = $this->postJson('/cadastro-cliente', [
            'nome' => 'Maria Silva',
            'email' => 'maria@email.com',
            'password' => 'senha-segura-123',
            'password_confirmation' => 'senha-segura-123',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('email');
        Mail::assertNothingSent();
    }

    /**
     * Quem testa e-mails no cadastro não pode descobrir quais já têm conta: a resposta
     * é a mesma de um e-mail novo, e só a dona do e-mail fica sabendo, pelo aviso.
     */
    public function test_email_ja_cadastrado_responde_igual_a_email_novo_e_so_avisa_a_dona(): void
    {
        ['usuario' => $maria] = $this->criarCliente('maria@email.com');
        $senhaDaMaria = $maria->password;

        $existente = $this->pedirCadastro('maria@email.com');
        $novo = $this->pedirCadastro('ana@email.com');

        $this->assertSame($novo->getStatusCode(), $existente->getStatusCode(), $existente->getContent());
        $this->assertSame(
            str_replace('ana@email.com', 'maria@email.com', $novo->getContent()),
            $existente->getContent()
        );

        Mail::assertSent(ContaJaExisteMail::class, fn (ContaJaExisteMail $mail) => $mail->hasTo('maria@email.com'));
        Mail::assertNotSent(CodigoDeConfirmacaoMail::class, fn (CodigoDeConfirmacaoMail $mail) => $mail->hasTo('maria@email.com'));
        Mail::assertSent(CodigoDeConfirmacaoMail::class, fn (CodigoDeConfirmacaoMail $mail) => $mail->hasTo('ana@email.com'));
        $this->assertSame($senhaDaMaria, $maria->fresh()->password);
        $this->assertSame(1, UserModel::where('email', 'maria@email.com')->count());
    }

    public function test_aviso_nao_leva_o_nome_digitado_por_quem_pediu(): void
    {
        $this->criarCliente('maria@email.com', 'Maria');

        $this->postJson('/cadastro-cliente', [
            'nome' => 'Clique em http://golpe.example',
            'email' => 'maria@email.com',
            'email_confirmation' => 'maria@email.com',
            'password' => 'senha-segura-123',
            'password_confirmation' => 'senha-segura-123',
        ])->assertStatus(202);

        Mail::assertSent(ContaJaExisteMail::class, function (ContaJaExisteMail $mail) {
            $this->assertStringNotContainsString('golpe', $mail->render());

            return true;
        });
    }

    public function test_email_ja_cadastrado_tambem_espera_um_minuto_entre_pedidos(): void
    {
        $this->criarCliente('maria@email.com');

        $this->pedirCadastro('maria@email.com')->assertStatus(202);
        $this->pedirCadastro('maria@email.com')->assertStatus(429);

        $this->travelTo(now()->addSeconds(61));
        $this->postJson('/cadastro-cliente/reenviar-codigo')->assertStatus(202);

        Mail::assertSentCount(2);
        Mail::assertNotSent(CodigoDeConfirmacaoMail::class);
    }

    public function test_nenhum_codigo_confirma_cadastro_de_email_ja_cadastrado(): void
    {
        $this->criarCliente('maria@email.com');
        $this->pedirCadastro('maria@email.com')->assertStatus(202);

        $this->postJson('/cadastro-cliente/confirmar', ['codigo' => '123456'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('codigo');

        $this->assertGuest();
        $this->assertSame(1, UserModel::where('email', 'maria@email.com')->count());
    }

    public function test_confirmar_sem_ter_comecado_o_cadastro_retorna_422(): void
    {
        $response = $this->postJson('/cadastro-cliente/confirmar', ['codigo' => '123456']);

        $response->assertStatus(422)->assertJsonValidationErrors('codigo');
    }

    public function test_conta_criada_pelo_google_enquanto_o_codigo_estava_pendente_nao_e_duplicada(): void
    {
        $this->pedirCadastro('maria@email.com');
        $codigo = $this->codigoEnviadoPara('maria@email.com');

        UserModel::create(['google_id' => 'google-123', 'nome' => 'Maria', 'email' => 'maria@email.com']);

        $response = $this->postJson('/cadastro-cliente/confirmar', ['codigo' => $codigo]);

        $response->assertStatus(422)->assertJsonValidationErrors('email');
        $this->assertSame(1, UserModel::where('email', 'maria@email.com')->count());
        $this->assertNull(UserModel::where('email', 'maria@email.com')->value('password'));
    }

    public function test_conta_criada_pelo_google_no_meio_da_confirmacao_responde_422_e_nao_500(): void
    {
        $this->pedirCadastro('maria@email.com');
        $codigo = $this->codigoEnviadoPara('maria@email.com');

        // O login com Google grava a conta logo depois de a confirmação conferir que o e-mail estava livre
        $googleJaGravou = false;
        DB::listen(function (QueryExecuted $query) use (&$googleJaGravou): void {
            $sql = str_replace(['`', '"'], '', $query->sql);
            if ($googleJaGravou || ! str_contains($sql, 'exists(select * from users where email')) {
                return;
            }
            $googleJaGravou = true;
            UserModel::create(['google_id' => 'google-123', 'nome' => 'Maria', 'email' => 'maria@email.com']);
        });

        $response = $this->postJson('/cadastro-cliente/confirmar', ['codigo' => $codigo]);

        $response->assertStatus(422)->assertJsonValidationErrors('email');
        $this->assertSame(1, UserModel::where('email', 'maria@email.com')->count());
        $this->assertNull(UserModel::where('email', 'maria@email.com')->value('password'));
        $this->assertDatabaseMissing('clientes', ['email' => 'maria@email.com']);
    }

    public function test_falha_no_envio_do_email_retorna_503_e_nao_guarda_o_cadastro(): void
    {
        $enviador = $this->createMock(EnviadorDeCodigoDeConfirmacaoInterface::class);
        $enviador->method('enviar')->willThrowException(EnvioDeEmailFalhouException::porCausaDe(new RuntimeException('smtp fora')));
        $this->app->instance(EnviadorDeCodigoDeConfirmacaoInterface::class, $enviador);

        $response = $this->pedirCadastro('maria@email.com');

        $this->assertSame(503, $response->getStatusCode(), $response->getContent());
        $this->assertDatabaseMissing('cadastros_pendentes', ['email' => 'maria@email.com']);
    }

    public function test_cadastro_em_host_sem_estabelecimento_retorna_404(): void
    {
        $this->noHost('nao-existe.test');

        $this->pedirCadastro('maria@email.com')->assertNotFound();
        Mail::assertNothingSent();
    }

    private function pedirCadastro(string $email, string $senha = 'senha-segura-123', ?string $confirmacaoDoEmail = null): TestResponse
    {
        return $this->postJson('/cadastro-cliente', [
            'nome' => 'Maria Silva',
            'email' => $email,
            'email_confirmation' => $confirmacaoDoEmail ?? $email,
            'password' => $senha,
            'password_confirmation' => $senha,
        ]);
    }

    private function codigoEnviadoPara(string $email): string
    {
        $enviado = Mail::sent(CodigoDeConfirmacaoMail::class, fn (CodigoDeConfirmacaoMail $mail) => $mail->hasTo($email))->last();

        $this->assertNotNull($enviado, "Nenhum código enviado para {$email}");

        return $enviado->codigo;
    }

    private function codigoErrado(string $email): string
    {
        $certo = (int) $this->codigoEnviadoPara($email);

        return str_pad((string) (($certo + 1) % 1_000_000), 6, '0', STR_PAD_LEFT);
    }
}
