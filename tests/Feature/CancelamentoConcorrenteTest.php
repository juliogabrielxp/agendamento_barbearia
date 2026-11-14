<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\AgendamentoModel;
use App\Infrastructure\Persistence\Eloquent\BarbeariaModel;
use App\Infrastructure\Persistence\Eloquent\ClienteModel;
use App\Infrastructure\Persistence\Eloquent\ProfissionalModel;
use App\Infrastructure\Persistence\Eloquent\ServicoModel;
use App\Infrastructure\Persistence\Eloquent\UserModel;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A dona concluindo um agendamento enquanto o cliente o cancela: o cancelamento lê o
 * agendamento, confere o status e só então grava. Se ninguém trava a linha, a conclusão
 * entra no meio e o cliente cancela um atendimento que já foi concluído.
 *
 * Só no MySQL (composer test:mysql): precisa de SELECT ... FOR UPDATE e de uma segunda
 * conexão de verdade. Os dados são gravados (commit) por essa segunda conexão, para os
 * dois lados enxergarem a mesma linha, e apagados no fim do teste.
 */
class CancelamentoConcorrenteTest extends TestCase
{
    use RefreshDatabase;

    private const PARALELA = 'mysql_paralela';

    public function test_conclusao_paralela_espera_o_cancelamento_terminar(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Corrida só se reproduz no MySQL (composer test:mysql).');
        }

        config(['database.connections.'.self::PARALELA => config('database.connections.'.DB::getDefaultConnection())]);
        ['usuario' => $usuario, 'agendamento' => $agendamento] = $this->criarCenarioGravado();

        // Assim que o cancelamento lê o agendamento, a "dona" tenta concluí-lo por outra conexão
        $concluiuNoMeio = null;
        DB::listen(function (QueryExecuted $query) use ($agendamento, &$concluiuNoMeio): void {
            $sql = str_replace(['`', '"'], '', $query->sql);
            if ($concluiuNoMeio !== null || $query->connectionName === self::PARALELA
                || ! str_starts_with($sql, 'select * from agendamentos where agendamentos.id = ?')) {
                return;
            }

            $paralela = DB::connection(self::PARALELA);
            $paralela->statement('SET SESSION innodb_lock_wait_timeout = 1');
            try {
                $paralela->table('agendamentos')->where('id', $agendamento->id)->update(['status' => 'concluido']);
                $concluiuNoMeio = true;
            } catch (QueryException $e) {
                // 1205: Lock wait timeout. A linha estava travada pelo cancelamento.
                $concluiuNoMeio = false;
                $this->assertSame(1205, $e->errorInfo[1] ?? null, $e->getMessage());
            }
        });

        $this->actingAs($usuario)->deleteJson("/api/meus-agendamentos/{$agendamento->id}")->assertNoContent();

        $this->assertFalse($concluiuNoMeio, 'A conclusão entrou no meio do cancelamento: o cliente cancelou um atendimento concluído.');
    }

    /** @return array{usuario: UserModel, agendamento: AgendamentoModel} */
    private function criarCenarioGravado(): array
    {
        $c = self::PARALELA;
        $dona = UserModel::on($c)->create(['nome' => 'Dona', 'email' => 'dona@concorrencia.test', 'password' => 'senha-segura-123']);
        $barbearia = BarbeariaModel::on($c)->create([
            'user_id' => $dona->id, 'dominio' => 'localhost', 'nome' => 'Estúdio', 'email' => 'dona@concorrencia.test',
            'telefone' => '', 'endereco' => 'Rua Teste, 1',
        ]);
        $servico = ServicoModel::on($c)->create(['barbearia_id' => $barbearia->id, 'nome' => 'Corte', 'duracao_em_minutos' => 30, 'preco' => 40.0]);
        $profissional = ProfissionalModel::on($c)->create(['barbearia_id' => $barbearia->id, 'nome' => 'João']);
        $usuario = UserModel::on($c)->create(['nome' => 'Maria', 'email' => 'maria@concorrencia.test', 'password' => 'senha-segura-123']);
        $cliente = ClienteModel::on($c)->create(['user_id' => $usuario->id, 'nome' => 'Maria', 'email' => 'maria@concorrencia.test', 'telefone' => '']);
        $agendamento = AgendamentoModel::on($c)->create([
            'profissional_id' => $profissional->id, 'servico_id' => $servico->id, 'cliente_id' => $cliente->id, 'inicio' => '2030-01-10 10:00:00',
        ]);

        // Roda depois do rollback do RefreshDatabase, que libera as travas da conexão principal
        $this->beforeApplicationDestroyed(function () use ($c, $agendamento, $cliente, $profissional, $servico, $barbearia, $usuario, $dona): void {
            $paralela = DB::connection($c);
            $paralela->table('agendamentos')->where('id', $agendamento->id)->delete();
            $paralela->table('clientes')->where('id', $cliente->id)->delete();
            $paralela->table('profissionais')->where('id', $profissional->id)->delete();
            $paralela->table('servicos')->where('id', $servico->id)->delete();
            $paralela->table('barbearias')->where('id', $barbearia->id)->delete();
            $paralela->table('users')->whereIn('id', [$usuario->id, $dona->id])->delete();
            DB::disconnect($c);
        });

        return ['usuario' => $usuario, 'agendamento' => $agendamento];
    }
}
