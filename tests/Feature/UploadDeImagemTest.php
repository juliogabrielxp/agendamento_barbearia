<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\BarbeariaModel;
use App\Infrastructure\Persistence\Eloquent\ServicoModel;
use App\Infrastructure\Persistence\Eloquent\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_dono_envia_imagem_da_propria_barbearia(): void
    {
        $barbearia = $this->criarBarbeariaComDono('dono@barbearia.com');
        $this->actingAs(UserModel::find($barbearia->user_id));

        $response = $this->postJson('/api/perfil-barbearia/imagem', [
            'imagem' => UploadedFile::fake()->image('foto.jpg', 400, 400),
        ]);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());

        $caminho = $barbearia->fresh()->imagem_path;
        $this->assertNotNull($caminho);
        Storage::disk('public')->assertExists($caminho);
    }

    public function test_nao_aceita_arquivo_que_nao_e_imagem(): void
    {
        $barbearia = $this->criarBarbeariaComDono('dono@barbearia.com');
        $this->actingAs(UserModel::find($barbearia->user_id));

        $response = $this->postJson('/api/perfil-barbearia/imagem', [
            'imagem' => UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf'),
        ]);

        $this->assertSame(422, $response->getStatusCode(), $response->getContent());
        $this->assertNull($barbearia->fresh()->imagem_path);
    }

    public function test_trocar_a_imagem_apaga_a_anterior(): void
    {
        $barbearia = $this->criarBarbeariaComDono('dono@barbearia.com');
        $this->actingAs(UserModel::find($barbearia->user_id));

        $this->postJson('/api/perfil-barbearia/imagem', ['imagem' => UploadedFile::fake()->image('a.jpg')]);
        $primeira = $barbearia->fresh()->imagem_path;

        $this->postJson('/api/perfil-barbearia/imagem', ['imagem' => UploadedFile::fake()->image('b.jpg')]);
        $segunda = $barbearia->fresh()->imagem_path;

        Storage::disk('public')->assertMissing($primeira);
        Storage::disk('public')->assertExists($segunda);
    }

    public function test_dono_nao_envia_imagem_para_servico_de_outra_barbearia(): void
    {
        $minha = $this->criarBarbeariaComDono('minha@barbearia.com');
        $outra = $this->criarBarbeariaComDono('outra@barbearia.com');
        $servicoDaOutra = ServicoModel::create([
            'barbearia_id' => $outra->id,
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

    private function criarBarbeariaComDono(string $email): BarbeariaModel
    {
        $user = UserModel::create(['nome' => 'Dono', 'email' => $email, 'password' => 'senha-segura-123']);

        return BarbeariaModel::create([
            'user_id' => $user->id,
            'nome' => 'Barbearia ' . $email,
            'email' => $email,
            'telefone' => '',
            'endereco' => 'Rua Teste, 1',
        ]);
    }
}
