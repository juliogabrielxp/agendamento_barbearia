-- Usuários do MySQL de produção, cada um só com o que usa. Rode na EC2 com: sudo mysql
-- Troque <...> por senhas fortes (ex.: openssl rand -base64 24) e guarde-as no SSM, não aqui.
-- Troque agendamento se o DB_DATABASE de produção tiver outro nome.
--
-- Host 'localhost': o app e os scripts conectam na própria EC2 (DB_HOST=127.0.0.1). Se o MySQL
-- estiver com skip_name_resolve=ON (SHOW VARIABLES LIKE 'skip_name_resolve'), use '127.0.0.1'.
--
-- Nada aqui apaga ou altera o usuário que o app usa hoje: a troca é feita depois, no .env.

-- 1. App (runtime). Provado com a suíte inteira (143 testes) rodando só com estes 4 privilégios.
CREATE USER 'agendamento_app'@'localhost' IDENTIFIED BY '<senha-do-app>';
GRANT SELECT, INSERT, UPDATE, DELETE ON agendamento.* TO 'agendamento_app'@'localhost';

-- 2. Migrations (só no deploy: php artisan migrate --force --database=mysql_migracao).
--    Provado aplicando e desfazendo as 22 migrations (migrate:refresh) só com estes privilégios.
CREATE USER 'agendamento_migracao'@'localhost' IDENTIFIED BY '<senha-de-migracao>';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, DROP, INDEX, REFERENCES
    ON agendamento.* TO 'agendamento_migracao'@'localhost';

-- 3. Backup (scripts/backup/backup-banco.sh): só leitura.
CREATE USER 'backup'@'localhost' IDENTIFIED BY '<senha-do-backup>';
GRANT SELECT, SHOW VIEW, TRIGGER ON agendamento.* TO 'backup'@'localhost';

-- 4. Teste de restauração (scripts/backup/testar-restauracao.sh): só no banco de teste, nunca na produção.
CREATE USER 'restauracao'@'localhost' IDENTIFIED BY '<senha-da-restauracao>';
GRANT ALL PRIVILEGES ON agendamento_restauracao_teste.* TO 'restauracao'@'localhost';

-- Conferência: cada usuário só deve aparecer com o que está acima.
SHOW GRANTS FOR 'agendamento_app'@'localhost';
SHOW GRANTS FOR 'agendamento_migracao'@'localhost';
SHOW GRANTS FOR 'backup'@'localhost';
SHOW GRANTS FOR 'restauracao'@'localhost';
