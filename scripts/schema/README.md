# Schema, ambientes e usuários do banco

| | Desenvolvimento | Produção |
|---|---|---|
| **Onde** | serviço `mysql-dev` do `docker-compose.yml` (`127.0.0.1:3309`, volume `mysql-dev-data`) | MySQL na própria EC2 |
| **Configuração** | `.env` copiado do `.env.example` | `.env` que só existe na EC2 |
| **Usuário do app** | `agendamento_app` (só DML) | `agendamento_app` (só DML) |
| **Usuário das migrations** | `agendamento_migracao` (`composer migrate`) | `agendamento_migracao` (no `deploy.sh`) |
| **Comandos destrutivos** | liberados | recusados (`DB::prohibitDestructiveCommands`) |

As migrations são do próprio Laravel (`database/migrations`, `php artisan migrate`). O app nunca
cria nem altera tabela: só a conexão `mysql_migracao` tem DDL.

Desde 2026-11-15 o agente de IA pode acessar a EC2 por `ssh girassol`, com autorização do dono.
O hook `.claude/hooks/proteger-producao.sh` continua bloqueando a leitura de `.env` com segredos,
`--env=production` e o acesso direto ao banco de produção a partir da máquina local.

## Começar no dev

```bash
composer setup        # sobe o mysql-dev, migra com o usuário de migração, instala o front
composer migrate      # depois de criar uma migration nova
```

### Banco de dev antigo

Até 2026-11-15 o dev usava o banco `agendamento_barbearia` dentro do container do projeto
`job_tracker` (`laravel_mysql`, porta 3307). Nessa data ele foi renomeado para `agendamento`, com a
tabela `barbearias` virando `estabelecimentos`, sem perder dados. O `.env` local ainda aponta para
esse container. Para passar a usar o `mysql-dev` deste projeto, troque as linhas `DB_*` pelas do
`.env.example` (porta 3309 e os dois usuários), rode `composer setup` e `php artisan config:clear`.
Um volume `mysql-dev-data` criado antes dessa data tem o banco com o nome antigo: recrie-o com
`docker compose down -v`.

## Produção: comparar o schema com as migrations

Rode na EC2 (só leitura) e me mande os dois arquivos; eles não têm senha nem dado de cliente:

```bash
# na sua máquina
scp scripts/schema/assinatura-schema.sql girassol:/tmp/
# na EC2
cd /var/www/agendamento
php artisan migrate:status > /tmp/migrate-status.txt
sudo mysql -N <DB_DATABASE> < /tmp/assinatura-schema.sql > /tmp/assinatura-producao.txt
```

Para comparar você mesmo: `diff scripts/schema/assinatura-esperada.txt assinatura-producao.txt`.

- Nenhuma saída: o schema é igual ao das migrations.
- A linha `BANCO collation=…` pode ser diferente. Isso é inofensivo, porque toda tabela declara a própria collation.
- Linhas que vêm de migrations ainda `Pending` no `migrate-status.txt` de produção também são
  esperadas: a assinatura esperada é a do código atual. Desde 2026-11-14 isso inclui as colunas
  `agendamentos.duracao_em_minutos`/`preco` (BD-3), os `CHECK chk_*` e a FK `RESTRICT` de
  `clientes.user_id` (BD-13, M1 a M4).
- Qualquer outra linha é algo criado ou mudado à mão. Me mande antes de mexer: eu escrevo a
  migration que traz isso para o código, sem apagar nada que só existe em produção.

## Produção: trocar para usuários com permissão mínima

**Feito em 2026-11-15.** O app usa `agendamento_app`, o `deploy.sh` migra com
`agendamento_migracao`, e o usuário antigo (`barbearia_user`, com todos os privilégios) foi removido.
As senhas foram geradas na EC2 e só existem no `.env` de lá. Os passos abaixo ficam como registro
e para montar um servidor novo.

A ordem importa. Em cada passo, o app continua no ar e dá para voltar atrás.

1. **Deploy deste código antes.** A conexão `mysql_migracao` precisa existir. Sem `DB_MIGRACAO_*`
   no `.env`, ela usa o usuário atual, então nada muda ainda.
2. **Crie os usuários:** `sudo mysql < usuarios-producao.sql`, com as senhas trocadas.
3. **Migrations com o usuário de migração.** No `.env` da EC2, acrescente `DB_MIGRACAO_USERNAME` e
   `DB_MIGRACAO_PASSWORD`. No `deploy.sh`, troque `php artisan migrate --force` por
   `php artisan migrate --force --database=mysql_migracao`.
4. **App com o usuário do app.** No `.env` da EC2, troque `DB_USERNAME` e `DB_PASSWORD` para
   `agendamento_app`, rode `php artisan config:cache` e teste a vitrine, o login e a agenda.
   Para voltar atrás: restaure os valores antigos e rode `php artisan config:cache` de novo.
5. **O usuário antigo (root?) não é removido aqui.** Depois de alguns dias estável, me pergunte
   antes de revogar ou apagar.
