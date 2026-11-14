-- Roda só na primeira subida do mysql-dev (volume vazio). Senhas de desenvolvimento,
-- iguais às do .env.example; produção usa outras senhas (scripts/schema/usuarios-producao.sql).
-- Host '%' porque no Docker a conexão chega pela rede interna, não por 127.0.0.1.

-- O app: só lê e grava dados. Não cria, altera nem apaga tabela.
CREATE USER 'agendamento_app'@'%' IDENTIFIED BY 'app-dev';
GRANT SELECT, INSERT, UPDATE, DELETE ON agendamento_barbearia.* TO 'agendamento_app'@'%';

-- As migrations: DDL + DML (a tabela migrations e os ajustes de dados das migrations).
CREATE USER 'agendamento_migracao'@'%' IDENTIFIED BY 'migracao-dev';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, DROP, INDEX, REFERENCES
    ON agendamento_barbearia.* TO 'agendamento_migracao'@'%';
