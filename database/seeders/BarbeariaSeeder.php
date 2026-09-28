<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Eloquent\BarbeariaModel;
use App\Infrastructure\Persistence\Eloquent\ProfissionalModel;
use App\Infrastructure\Persistence\Eloquent\ServicoModel;
use Illuminate\Database\Seeder;

class BarbeariaSeeder extends Seeder
{
    public function run(): void
    {
        $barbearias = [
            [
                'nome' => 'Barbearia 1995',
                'telefone' => '(11) 91995-1995',
                'email' => 'contato@barbearia1995.com',
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
                'telefone' => '(11) 98765-4321',
                'email' => 'contato@gabrieldocorte.com',
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
                'telefone' => '(21) 99887-6655',
                'email' => 'contato@lacorte.com',
                'endereco' => 'Rua das Laranjeiras, 120 - Rio de Janeiro, RJ',
                'servicos' => [
                    ['nome' => 'Corte Premium', 'duracao_em_minutos' => 45, 'preco' => 70.00],
                    ['nome' => 'Barboterapia', 'duracao_em_minutos' => 35, 'preco' => 55.00],
                    ['nome' => 'Corte + Sobrancelha', 'duracao_em_minutos' => 45, 'preco' => 60.00],
                ],
                'profissionais' => ['Rafael Duarte', 'Bruno Costa'],
            ],
        ];

        foreach ($barbearias as $dados) {
            $barbearia = BarbeariaModel::create([
                'nome' => $dados['nome'],
                'telefone' => $dados['telefone'],
                'email' => $dados['email'],
                'endereco' => $dados['endereco'],
            ]);

            $servicosCriados = [];
            foreach ($dados['servicos'] as $servico) {
                $servicosCriados[] = ServicoModel::create([
                    'barbearia_id' => $barbearia->id,
                    'nome' => $servico['nome'],
                    'duracao_em_minutos' => $servico['duracao_em_minutos'],
                    'preco' => $servico['preco'],
                ]);
            }

            foreach ($dados['profissionais'] as $nomeProfissional) {
                $profissional = ProfissionalModel::create([
                    'barbearia_id' => $barbearia->id,
                    'nome' => $nomeProfissional,
                ]);

                // Vincula o profissional a todos os serviços dessa barbearia.
                // Se quiser distribuir serviços específicos por profissional, ajuste aqui.
                $profissional->servicos()->attach(
                    collect($servicosCriados)->pluck('id')
                );
            }
        }
    }
}
