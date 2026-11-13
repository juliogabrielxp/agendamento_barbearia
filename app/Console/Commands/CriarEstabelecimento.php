<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Infrastructure\Persistence\Eloquent\BarbeariaModel;
use App\Infrastructure\Persistence\Eloquent\ProfissionalModel;
use App\Infrastructure\Persistence\Eloquent\UserModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Cadastro de um estabelecimento novo: só por aqui, porque não existe tela pública
 * de "criar estabelecimento". A senha é sempre perguntada, nunca passada por opção,
 * para não ficar no histórico do shell.
 */
final class CriarEstabelecimento extends Command
{
    protected $signature = 'estabelecimento:criar
        {--nome= : Nome do estabelecimento}
        {--dominio= : Host de acesso, ex.: trancas.juliogabrieldev.com}
        {--email= : E-mail de login da dona}
        {--profissional= : Nome da profissional que atende}
        {--telefone= : Telefone de contato}
        {--endereco= : Endereço}';

    protected $description = 'Cria um estabelecimento com a conta de acesso da dona e a profissional que atende';

    public function handle(): int
    {
        $dados = [
            'nome' => $this->option('nome') ?? $this->ask('Nome do estabelecimento'),
            'dominio' => strtolower(trim((string) ($this->option('dominio') ?? $this->ask('Domínio (ex.: trancas.juliogabrieldev.com)')))),
            'email' => $this->option('email') ?? $this->ask('E-mail de login da dona'),
            'profissional' => $this->option('profissional') ?? $this->ask('Nome da profissional que atende'),
            'telefone' => $this->option('telefone') ?? $this->ask('Telefone de contato', '') ?? '',
            'endereco' => $this->option('endereco') ?? $this->ask('Endereço', '') ?? '',
        ];

        $senha = $this->secret('Senha de acesso da dona (mínimo 8 caracteres)');
        $confirmacao = $this->secret('Repita a senha');

        $validador = Validator::make([...$dados, 'senha' => $senha, 'senha_confirmation' => $confirmacao], [
            'nome' => 'required|string|max:255',
            'dominio' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]([a-z0-9.-]*[a-z0-9])?$/', 'unique:barbearias,dominio'],
            'email' => 'required|email|max:255|unique:users,email|unique:barbearias,email',
            'profissional' => 'required|string|max:255',
            'telefone' => 'nullable|string|max:255',
            'endereco' => 'nullable|string|max:255',
            'senha' => 'required|string|min:8|confirmed',
        ], [], [
            'dominio' => 'domínio',
            'email' => 'e-mail',
            'endereco' => 'endereço',
        ]);

        if ($validador->fails()) {
            foreach ($validador->errors()->all() as $erro) {
                $this->error($erro);
            }
            $this->error('Nada foi criado.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($dados, $senha): void {
            $dona = UserModel::create([
                'nome' => $dados['profissional'],
                'email' => $dados['email'],
                'password' => $senha,
            ]);

            $estabelecimento = BarbeariaModel::create([
                'user_id' => $dona->id,
                'nome' => $dados['nome'],
                'dominio' => $dados['dominio'],
                'email' => $dados['email'],
                'telefone' => $dados['telefone'],
                'endereco' => $dados['endereco'],
            ]);

            ProfissionalModel::create([
                'barbearia_id' => $estabelecimento->id,
                'nome' => $dados['profissional'],
            ]);
        });

        $this->info("{$dados['nome']} criado.");
        $this->table(['Endereço do app', 'Painel da dona', 'Login da dona'], [[
            "https://{$dados['dominio']}/app",
            "https://{$dados['dominio']}/app/painel/entrar",
            $dados['email'],
        ]]);
        $this->line('Próximo passo: entrar no painel, cadastrar os serviços e ajustar o expediente.');

        return self::SUCCESS;
    }
}
