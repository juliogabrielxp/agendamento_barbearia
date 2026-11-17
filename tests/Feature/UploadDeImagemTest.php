<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\EstabelecimentoModel;
use App\Infrastructure\Persistence\Eloquent\ServicoModel;
use App\Infrastructure\Persistence\Eloquent\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class UploadDeImagemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['filesystems.imagens_disk' => 'public']);
        Storage::fake('public');
    }

    public function test_dono_envia_imagem_da_propria_estabelecimento(): void
    {
        $estabelecimento = $this->criarEstabelecimentoComDono('dono@estabelecimento.com');
        $this->actingAs(UserModel::find($estabelecimento->user_id));

        $response = $this->postJson('/api/perfil-estabelecimento/imagem', [
            'imagem' => UploadedFile::fake()->image('foto.jpg', 400, 400),
        ]);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());

        $caminho = $estabelecimento->fresh()->imagem_path;
        $this->assertNotNull($caminho);
        Storage::disk('public')->assertExists($caminho);
    }

    public function test_nao_aceita_arquivo_que_nao_e_imagem(): void
    {
        $estabelecimento = $this->criarEstabelecimentoComDono('dono@estabelecimento.com');
        $this->actingAs(UserModel::find($estabelecimento->user_id));

        $response = $this->postJson('/api/perfil-estabelecimento/imagem', [
            'imagem' => UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf'),
        ]);

        $this->assertSame(422, $response->getStatusCode(), $response->getContent());
        $this->assertNull($estabelecimento->fresh()->imagem_path);
    }

    public function test_aceita_imagem_de_ate_5_mb(): void
    {
        $estabelecimento = $this->criarEstabelecimentoComDono('dono@estabelecimento.com');
        $this->actingAs(UserModel::find($estabelecimento->user_id));

        $response = $this->postJson('/api/perfil-estabelecimento/imagem', [
            'imagem' => UploadedFile::fake()->image('foto.jpg')->size(5120),
        ]);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertNotNull($estabelecimento->fresh()->imagem_path);
    }

    public function test_recusa_imagem_maior_que_5_mb(): void
    {
        $estabelecimento = $this->criarEstabelecimentoComDono('dono@estabelecimento.com');
        $this->actingAs(UserModel::find($estabelecimento->user_id));

        $response = $this->postJson('/api/perfil-estabelecimento/imagem', [
            'imagem' => UploadedFile::fake()->image('foto.jpg')->size(5121),
        ]);

        $this->assertSame(422, $response->getStatusCode(), $response->getContent());
        $this->assertNull($estabelecimento->fresh()->imagem_path);
    }

    public function test_trocar_a_imagem_apaga_a_anterior(): void
    {
        $estabelecimento = $this->criarEstabelecimentoComDono('dono@estabelecimento.com');
        $this->actingAs(UserModel::find($estabelecimento->user_id));

        $this->postJson('/api/perfil-estabelecimento/imagem', ['imagem' => UploadedFile::fake()->image('a.jpg')]);
        $primeira = $estabelecimento->fresh()->imagem_path;

        $this->postJson('/api/perfil-estabelecimento/imagem', ['imagem' => UploadedFile::fake()->image('b.jpg')]);
        $segunda = $estabelecimento->fresh()->imagem_path;

        Storage::disk('public')->assertMissing($primeira);
        Storage::disk('public')->assertExists($segunda);
    }

    public function test_dono_nao_envia_imagem_para_servico_de_outra_estabelecimento(): void
    {
        $minha = $this->criarEstabelecimentoComDono('minha@estabelecimento.com');
        $outra = $this->criarEstabelecimentoComDono('outra@estabelecimento.com');
        $servicoDaOutra = ServicoModel::create([
            'estabelecimento_id' => $outra->id,
            'nome' => 'Corte da Outra',
            'duracao_em_minutos' => 30,
            'preco' => 40.0,
        ]);

        $this->actingAs(UserModel::find($minha->user_id));

        $response = $this->postJson("/api/servicos/{$servicoDaOutra->id}/imagem", [
            'imagem' => UploadedFile::fake()->image('foto.jpg'),
        ]);

        $this->assertSame(404, $response->getStatusCode(), $response->getContent());
        $this->assertNull($servicoDaOutra->fresh()->imagem_path);
    }

    private function criarEstabelecimentoComDono(string $email): EstabelecimentoModel
    {
        $user = UserModel::create(['nome' => 'Dono', 'email' => $email, 'password' => 'senha-segura-123']);

        return EstabelecimentoModel::create([
            'user_id' => $user->id,
            // A primeira responde no host padrão dos testes; as outras ficam em outro endereço
            'dominio' => EstabelecimentoModel::where('dominio', 'localhost')->exists() ? Str::slug($email).'.test' : 'localhost',
            'nome' => 'Estabelecimento '.$email,
            'email' => $email,
            'telefone' => '',
            'endereco' => 'Rua Teste, 1',
        ]);
    }
}
