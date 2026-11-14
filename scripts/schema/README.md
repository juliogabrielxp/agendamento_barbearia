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

O agente de IA trabalha só no dev. O hook `.claude/hooks/proteger-producao.sh` bloqueia `.env`,
`--env=production`, `ssh`/`scp` e acesso ao host de produção. O que é de produção sai daqui como
comando para o dono rodar.

## Começar no dev

```bash
composer setup        # sobe o mysql-dev, migra com o usuário de migração, instala o front
composer migrate      # depois de criar uma migration nova
```

### Trazer os dados do banco de dev antigo

O banco de dev antigo está dentro do container do projeto `job_tracker` (`laravel_mysql`, porta
3307). Esta cópia só **lê** de lá. O banco antigo continua intacto, e você decide depois se apaga.
Ela já foi feita em 2026-11-13, com contagens idênticas nas 9 tabelas do app. Falta só trocar o `.env`.

```bash
docker compose up -d --wait mysql-dev && composer migrate
docker exec -i laravel_mysql mysqldump -uroot -p --single-transaction --no-create-info \
    --ignore-table=agendamento_barbearia.migrations agendamento_barbearia \
  | docker compose exec -T mysql-dev mysql -uroot -proot-dev agendamento_barbearia
```

O `-p` pede a senha de root do container antigo. Depois, troque no seu `.env` as linhas `DB_*` pelas
do `.env.example` (porta 3309 e os dois usuários) e rode `php artisan config:clear`.

## Produção: comparar o schema com as migrations

Rode na EC2 (só leitura) e me mande os dois arquivos; eles não têm senha nem dado de cliente:

```bash
# na sua máquina
scp scripts/schema/assinatura-schema.sql <usuario>@barbearia.juliogabrieldev.com:/tmp/
# na EC2
cd /var/www/barbearia.juliogabrieldev.com
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
