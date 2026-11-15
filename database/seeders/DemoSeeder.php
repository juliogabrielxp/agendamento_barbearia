<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Eloquent\ClienteModel;
use App\Infrastructure\Persistence\Eloquent\EstabelecimentoModel;
use App\Infrastructure\Persistence\Eloquent\ProfissionalModel;
use App\Infrastructure\Persistence\Eloquent\ServicoModel;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $estabelecimento = EstabelecimentoModel::create([
            'nome' => 'Barbearia do Zé',
            'telefone' => '83999999999',
            'email' => 'ze@barbearia.com',
            'endereco' => 'Rua A, 123',
        ]);

        $cliente = ClienteModel::create([
            'nome' => 'Maria',
            'telefone' => '83988888888',
            'email' => 'maria@email.com',
        ]);

        $servico = ServicoModel::create([
            'estabelecimento_id' => $estabelecimento->id,
            'nome' => 'Corte Masculino',
            'duracao_em_minutos' => 30,
            'preco' => 50.00,
        ]);

        $profissional = ProfissionalModel::create([
            'estabelecimento_id' => $estabelecimento->id,
            'nome' => 'João',
        ]);

        $profissional->servicos()->attach($servico->id);

        $this->command->info("Estabelecimento ID: {$estabelecimento->id}");
        $this->command->info("Cliente ID: {$cliente->id}");
        $this->command->info("Servico ID: {$servico->id}");
        $this->command->info("Profissional ID: {$profissional->id}");
    }
}
