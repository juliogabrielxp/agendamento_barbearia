<?php

declare(strict_types=1);

namespace Tests\Feature\Concerns;

use App\Infrastructure\Persistence\Eloquent\AgendamentoModel;
use App\Infrastructure\Persistence\Eloquent\BarbeariaModel;
use App\Infrastructure\Persistence\Eloquent\ClienteModel;
use App\Infrastructure\Persistence\Eloquent\ProfissionalModel;
use App\Infrastructure\Persistence\Eloquent\ServicoModel;
use App\Infrastructure\Persistence\Eloquent\UserModel;

/**
 * Monta barbearias, clientes e agendamentos direto no banco,
 * para os testes focarem só na rota que estão exercitando.
 */
trait CriaCenarioDeAgenda
{
    /** @return array{dono: UserModel, barbearia: BarbeariaModel, profissional: ProfissionalModel, servico: ServicoModel} */
    private function criarBarbearia(string $email): array
    {
        $dono = UserModel::create(['nome' => 'Dono ' . $email, 'email' => $email, 'password' => 'senha-segura-123']);

        $barbearia = BarbeariaModel::create([
            'user_id' => $dono->id,
            'nome' => 'Barbearia ' . $email,
            'email' => $email,
            'telefone' => '',
            'endereco' => 'Rua Teste, 1',
        ]);

        $servico = ServicoModel::create([
            'barbearia_id' => $barbearia->id,
            'nome' => 'Corte',
            'duracao_em_minutos' => 30,
            'preco' => 40.0,
        ]);

        $profissional = ProfissionalModel::create(['barbearia_id' => $barbearia->id, 'nome' => 'João']);
        $profissional->servicos()->attach($servico->id);

        return compact('dono', 'barbearia', 'profissional', 'servico');
    }

    /** @return array{usuario: UserModel, cliente: ClienteModel} */
    private function criarCliente(string $email, string $nome = 'Cliente'): array
    {
        $usuario = UserModel::create(['nome' => $nome, 'email' => $email, 'password' => 'senha-segura-123']);

        $cliente = ClienteModel::create([
            'user_id' => $usuario->id,
            'nome' => $nome,
            'email' => $email,
            'telefone' => '',
        ]);

        return compact('usuario', 'cliente');
    }

    private function criarAgendamento(array $barbearia, ClienteModel $cliente, string $inicio): AgendamentoModel
    {
        return AgendamentoModel::create([
            'profissional_id' => $barbearia['profissional']->id,
            'servico_id' => $barbearia['servico']->id,
            'cliente_id' => $cliente->id,
            'inicio' => $inicio,
        ]);
    }
}
