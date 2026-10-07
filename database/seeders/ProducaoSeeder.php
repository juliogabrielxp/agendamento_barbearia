<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Eloquent\BarbeariaModel;
use App\Infrastructure\Persistence\Eloquent\ProfissionalModel;
use App\Infrastructure\Persistence\Eloquent\ServicoModel;
use App\Infrastructure\Persistence\Eloquent\UserModel;
use Illuminate\Database\Seeder;

class ProducaoSeeder extends Seeder
{
    public function run(): void
    {
        $senha = $this->command->secret('Senha das contas de barbearia (mínimo 8 caracteres)');

        if (!$senha || strlen($senha) < 8) {
            $this->command->error('Senha inválida. Nada foi alterado.');
            return;
        }

        $resumo = [];

        foreach ($this->barbearias() as $dados) {
            $user = UserModel::firstOrCreate(
                ['email' => $dados['login']],
                ['nome' => $dados['nome'], 'password' => $senha]
            );

            $barbearia = BarbeariaModel::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'nome' => $dados['nome'],
                    'telefone' => $dados['telefone'],
                    'email' => $dados['login'],
                    'endereco' => $dados['endereco'],
                ]
            );

            $servicosIds = [];
            foreach ($dados['servicos'] as $servico) {
                $servicosIds[] = ServicoModel::firstOrCreate(
                    ['barbearia_id' => $barbearia->id, 'nome' => $servico['nome']],
                    ['duracao_em_minutos' => $servico['duracao_em_minutos'], 'preco' => $servico['preco']]
                )->id;
            }

            foreach ($dados['profissionais'] as $nomeProfissional) {
                $profissional = ProfissionalModel::firstOrCreate(
                    ['barbearia_id' => $barbearia->id, 'nome' => $nomeProfissional]
                );

                $profissional->servicos()->syncWithoutDetaching($servicosIds);
            }

            $resumo[] = [$barbearia->nome, $dados['login']];
        }

        $this->command->info('Seed de produção concluído. Contas de login:');
        $this->command->table(['Barbearia', 'E-mail de login'], $resumo);
    }

    private function barbearias(): array
    {
        return [
            [
                'nome' => 'Barbearia 1995',
                'login' => 'contato@barbearia1995.com',
                'telefone' => '(11) 91995-1995',
                'endereco' => 'Rua Augusta, 1995 - São Paulo, SP',
                'servicos' => [
                    ['nome' => 'Corte Clássico', 'duracao_em_minutos' => 30, 'preco' => 40.00],
                    ['nome' => 'Barba', 'duracao_em_minutos' => 20, 'preco' => 30.00],
                    ['nome' => 'Corte + Barba', 'duracao_em_minutos' => 50, 'preco' => 65.00],
                ],
                'profissionais' => ['João Vintage', 'Marcos Ribeiro'],
            ],
            [
                'nome' => 'Gabriel do Corte',
                'login' => 'contato@gabrieldocorte.com',
                'telefone' => '(11) 98765-4321',
                'endereco' => 'Av. Paulista, 500 - São Paulo, SP',
                'servicos' => [
                    ['nome' => 'Corte Degradê', 'duracao_em_minutos' => 40, 'preco' => 50.00],
                    ['nome' => 'Sobrancelha', 'duracao_em_minutos' => 15, 'preco' => 20.00],
                    ['nome' => 'Corte Infantil', 'duracao_em_minutos' => 30, 'preco' => 35.00],
                ],
                'profissionais' => ['Gabriel Andrade', 'Lucas Ferreira', 'Diego Souza'],
            ],
            [
                'nome' => 'La Corte',
                'login' => 'contato@lacorte.com',
                'telefone' => '(21) 99887-6655',
                'endereco' => 'Rua das Laranjeiras, 120 - Rio de Janeiro, RJ',
                'servicos' => [
                    ['nome' => 'Corte Premium', 'duracao_em_minutos' => 45, 'preco' => 70.00],
                    ['nome' => 'Barboterapia', 'duracao_em_minutos' => 35, 'preco' => 55.00],
                    ['nome' => 'Corte + Sobrancelha', 'duracao_em_minutos' => 45, 'preco' => 60.00],
                ],
                'profissionais' => ['Rafael Duarte', 'Bruno Costa'],
            ],
        ];
    }
}
