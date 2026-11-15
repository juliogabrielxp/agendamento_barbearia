-- Conta quantas linhas atuais quebram cada constraint proposta. Somente leitura (só SELECT).
-- Uso: mysql <banco> < verificar-violacoes.sql      (zero em tudo = a migration pode rodar)
SELECT 'N1 estabelecimentos.user_id NOT NULL' AS regra, COUNT(*) AS violacoes FROM estabelecimentos WHERE user_id IS NULL
UNION ALL SELECT 'N2 clientes.user_id NOT NULL', COUNT(*) FROM clientes WHERE user_id IS NULL
UNION ALL SELECT 'N3 estabelecimentos.dominio NOT NULL', COUNT(*) FROM estabelecimentos WHERE dominio IS NULL
UNION ALL SELECT 'C1 agendamentos.status IN (confirmado,concluido,cancelado)', COUNT(*) FROM agendamentos WHERE status NOT IN ('confirmado', 'concluido', 'cancelado')
UNION ALL SELECT 'C2 servicos.duracao_em_minutos >= 1', COUNT(*) FROM servicos WHERE duracao_em_minutos < 1
UNION ALL SELECT 'C3 servicos.preco >= 0', COUNT(*) FROM servicos WHERE preco < 0
UNION ALL SELECT 'C4 estabelecimentos.hora_fechamento > hora_abertura', COUNT(*) FROM estabelecimentos WHERE hora_fechamento <= hora_abertura
UNION ALL SELECT 'C5 intervalo coerente com o expediente', COUNT(*) FROM estabelecimentos
    WHERE (intervalo_inicio IS NOT NULL AND NOT (intervalo_fim IS NOT NULL AND intervalo_inicio >= hora_abertura AND intervalo_fim > intervalo_inicio AND intervalo_fim <= hora_fechamento))
       OR (intervalo_fim IS NOT NULL AND intervalo_fim > hora_fechamento)
UNION ALL SELECT 'C6 servicos.nome não vazio', COUNT(*) FROM servicos WHERE TRIM(nome) = ''
UNION ALL SELECT 'C6 profissionais.nome não vazio', COUNT(*) FROM profissionais WHERE TRIM(nome) = ''
UNION ALL SELECT 'C6 estabelecimentos.nome não vazio', COUNT(*) FROM estabelecimentos WHERE TRIM(nome) = ''
UNION ALL SELECT 'C6 clientes.nome não vazio', COUNT(*) FROM clientes WHERE TRIM(nome) = ''
UNION ALL SELECT 'C6 users.nome não vazio', COUNT(*) FROM users WHERE TRIM(nome) = ''
UNION ALL SELECT 'C7 users tem senha ou google_id', COUNT(*) FROM users WHERE password IS NULL AND google_id IS NULL
UNION ALL SELECT 'C8 estabelecimentos.dominio minúsculo e no formato do comando', COUNT(*) FROM estabelecimentos
    WHERE dominio IS NOT NULL AND NOT REGEXP_LIKE(dominio, '^[a-z0-9]([a-z0-9.-]*[a-z0-9])?$', 'c')
UNION ALL SELECT 'F1 profissional_servico: profissional e serviço do mesmo estabelecimento', COUNT(*) FROM profissional_servico ps
    JOIN profissionais p ON p.id = ps.profissional_id JOIN servicos s ON s.id = ps.servico_id WHERE p.estabelecimento_id <> s.estabelecimento_id
UNION ALL SELECT 'F2 agendamentos: profissional e serviço do mesmo estabelecimento', COUNT(*) FROM agendamentos a
    JOIN profissionais p ON p.id = a.profissional_id JOIN servicos s ON s.id = a.servico_id WHERE p.estabelecimento_id <> s.estabelecimento_id
UNION ALL SELECT 'F3 agendamentos: profissional realiza o serviço (hoje)', COUNT(*) FROM agendamentos a
    WHERE NOT EXISTS (SELECT 1 FROM profissional_servico ps WHERE ps.profissional_id = a.profissional_id AND ps.servico_id = a.servico_id)
UNION ALL SELECT 'D1 clientes.nome/email diferente de users', COUNT(*) FROM clientes c JOIN users u ON u.id = c.user_id WHERE c.email <> u.email OR c.nome <> u.nome
UNION ALL SELECT 'D2 estabelecimentos.email diferente do login da dona', COUNT(*) FROM estabelecimentos b JOIN users u ON u.id = b.user_id WHERE b.email <> u.email
UNION ALL SELECT 'D3 clientes.telefone vazio (string vazia como ausência)', COUNT(*) FROM clientes WHERE telefone = ''
UNION ALL SELECT 'X  intervalo_fim sem intervalo_inicio (aceito pela validação hoje)', COUNT(*) FROM estabelecimentos WHERE intervalo_fim IS NOT NULL AND intervalo_inicio IS NULL;
