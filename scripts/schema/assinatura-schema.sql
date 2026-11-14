-- Assinatura do schema: uma linha por objeto, ordenada. Somente leitura.
-- Uso: mysql -N <banco> < assinatura-schema.sql > assinatura.txt
--      diff scripts/schema/assinatura-esperada.txt assinatura.txt   (vazio = schema igual ao das migrations)
-- Regerar a esperada depois de uma migration nova: rode este script num banco recém-migrado (ex.: o mysql-dev).
SELECT CONCAT('TABELA ', table_name, ' engine=', engine, ' collation=', table_collation) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE'
UNION ALL SELECT CONCAT('VIEW ', table_name) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = 'VIEW'
UNION ALL SELECT CONCAT_WS(' ', CONCAT('COLUNA ', table_name, '.', column_name), column_type, IF(is_nullable = 'YES', 'null', 'not-null'), CONCAT('default=', IFNULL(column_default, '<nenhum>')), NULLIF(extra, ''), collation_name) FROM information_schema.columns WHERE table_schema = DATABASE()
UNION ALL SELECT CONCAT('INDICE ', table_name, '.', index_name, IF(non_unique = 0, ' unique', ''), ' (', GROUP_CONCAT(column_name ORDER BY seq_in_index), ')') FROM information_schema.statistics WHERE table_schema = DATABASE() GROUP BY table_name, index_name, non_unique
UNION ALL SELECT CONCAT('FK ', k.table_name, '.', k.constraint_name, ' (', GROUP_CONCAT(k.column_name ORDER BY k.ordinal_position), ') -> ', k.referenced_table_name, ' delete=', r.delete_rule, ' update=', r.update_rule) FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema = k.table_schema AND r.constraint_name = k.constraint_name WHERE k.table_schema = DATABASE() GROUP BY k.table_name, k.constraint_name, k.referenced_table_name, r.delete_rule, r.update_rule
UNION ALL SELECT CONCAT('CHECK ', constraint_name, ': ', check_clause) FROM information_schema.check_constraints WHERE constraint_schema = DATABASE()
UNION ALL SELECT CONCAT('TRIGGER ', trigger_name, ' ', event_manipulation, ' ', event_object_table) FROM information_schema.triggers WHERE trigger_schema = DATABASE()
UNION ALL SELECT CONCAT('ROTINA ', routine_type, ' ', routine_name) FROM information_schema.routines WHERE routine_schema = DATABASE()
UNION ALL SELECT CONCAT('EVENTO ', event_name) FROM information_schema.events WHERE event_schema = DATABASE()
UNION ALL SELECT CONCAT('BANCO collation=', default_collation_name) FROM information_schema.schemata WHERE schema_name = DATABASE()
ORDER BY 1;
