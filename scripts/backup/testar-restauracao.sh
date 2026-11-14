#!/usr/bin/env bash
# Teste de restauração: baixa o backup mais recente do S3, restaura num banco SEPARADO,
# confere se todas as tabelas existem e se a contagem das principais bate com a da
# produção no momento do backup (gravada no manifesto). No fim apaga o banco de teste.
#
# Três travas impedem que isto toque a produção:
#   1. o nome do banco de restauração precisa conter "restauracao" e ser diferente de DB_DATABASE;
#   2. o usuário de restauração não pode conseguir abrir DB_DATABASE (o script testa e aborta);
#   3. o dump não tem CREATE DATABASE nem USE (o script confere antes de restaurar).
#
# Variáveis (ver backup.env.example):
#   BACKUP_S3_URI, DB_DATABASE, RESTORE_DB_NAME, RESTORE_DB_USER          obrigatórias
#   RESTORE_DB_PASSWORD ou RESTORE_DB_PASSWORD_SSM
#   RESTORE_DB_HOST (127.0.0.1), RESTORE_DB_PORT (3306)
#   RESTORE_TABELAS_PRINCIPAIS  tabelas cuja contagem precisa bater exatamente
#   RESTORE_IDADE_MAXIMA_HORAS  (26) backup mais velho que isso reprova o teste
#   RESTORE_ARQUIVO             testa um backup específico (ex.: semanal/x.sql.gz) em vez do mais recente
#   RESTORE_MANTER=1            não apaga o banco restaurado no fim (para inspecionar)
#   BACKUP_DB_USER + senha      se presentes, mostra também a contagem atual da produção
#   BACKUP_TMP_DIR (/var/tmp), RESTORE_PING_URL (opcional, avisado quando dá certo)

NOME_SCRIPT=restauracao
# shellcheck source=lib.sh
source "$(dirname "$(readlink -f "$0")")/lib.sh"

exigir BACKUP_S3_URI DB_DATABASE RESTORE_DB_NAME RESTORE_DB_USER
exigir_comando mysql gzip sha256sum aws

DESTINO=${BACKUP_S3_URI%/}
IDADE_MAXIMA=${RESTORE_IDADE_MAXIMA_HORAS:-26}
PRINCIPAIS=${RESTORE_TABELAS_PRINCIPAIS:-"users barbearias clientes servicos profissionais profissional_servico agendamentos push_subscriptions"}

# Trava 1: o nome
[ "$RESTORE_DB_NAME" != "$DB_DATABASE" ] || falhar "RESTORE_DB_NAME é o banco de produção"
case "$RESTORE_DB_NAME" in
    *restauracao*) ;;
    *) falhar "o banco de restauração precisa ter 'restauracao' no nome (recebi '$RESTORE_DB_NAME')" ;;
esac

trabalho=$(mktemp -d "${BACKUP_TMP_DIR:-/var/tmp}/restauracao.XXXXXX")
credenciais="$trabalho/cliente.cnf"
criar_credenciais "$credenciais" "${RESTORE_DB_HOST:-127.0.0.1}" "${RESTORE_DB_PORT:-3306}" \
    "$RESTORE_DB_USER" "$(ler_senha RESTORE_DB_PASSWORD)"

banco_restaurado=$(identificador_sql "$RESTORE_DB_NAME")
criei_banco=0

limpar() {
    if [ "$criei_banco" = 1 ] && [ "${RESTORE_MANTER:-0}" != 1 ]; then
        sql "$credenciais" "DROP DATABASE IF EXISTS $banco_restaurado" || log "aviso: não consegui apagar $RESTORE_DB_NAME"
    fi
    rm -rf "$trabalho"
}
trap limpar EXIT

# Trava 2: o usuário de restauração não enxerga a produção
if sql "$credenciais" "SELECT 1" "$DB_DATABASE" >/dev/null 2>&1; then
    falhar "o usuário $RESTORE_DB_USER consegue abrir o banco de produção $DB_DATABASE; ele só pode ter permissão em $RESTORE_DB_NAME"
fi

# Escolhe o backup: o pedido ou o diário mais recente que tenha manifesto (backup completo)
if [ -n "${RESTORE_ARQUIVO:-}" ]; then
    chave_manifesto="${RESTORE_ARQUIVO%.sql.gz}.manifest"
else
    ultimo=$(aws s3 ls "$DESTINO/diario/" | awk '{print $4}' | grep '\.manifest$' | sort | tail -n 1 || true)
    [ -n "$ultimo" ] || falhar "nenhum backup encontrado em $DESTINO/diario/"
    chave_manifesto="diario/$ultimo"
fi
chave_dump="${chave_manifesto%.manifest}.sql.gz"

manifesto="$trabalho/backup.manifest"
dump="$trabalho/backup.sql.gz"
aws s3 cp "$DESTINO/$chave_manifesto" "$manifesto" --only-show-errors
aws s3 cp "$DESTINO/$chave_dump" "$dump" --only-show-errors

valor() { grep -m1 "^$1=" "$manifesto" | cut -d= -f2-; }

criado_em=$(valor criado_em)
idade_horas=$(( ($(date -u +%s) - $(date -u -d "$criado_em" +%s)) / 3600 ))
log "backup $chave_dump, criado em $criado_em (há ${idade_horas}h)"

[ "$(sha256sum "$dump" | cut -d' ' -f1)" = "$(valor sha256)" ] || falhar "sha256 não confere: o arquivo no S3 não é o que o backup gerou"
gzip -t "$dump" || falhar "arquivo comprimido corrompido"

# Trava 3: nada no dump troca de banco. grep -c (e não -q) lê até o fim: com -q o gzip
# morreria de SIGPIPE, o pipefail marcaria falha e o "if" deixaria passar justo o dump perigoso.
trocas_de_banco=$(gzip -dc "$dump" | grep -ciE '^(USE |CREATE DATABASE)' || true)
if [ "${trocas_de_banco:-0}" -gt 0 ]; then
    falhar "o dump contém USE/CREATE DATABASE; recuso restaurar porque ele poderia escrever em outro banco"
fi

sql "$credenciais" "DROP DATABASE IF EXISTS $banco_restaurado;
                    CREATE DATABASE $banco_restaurado CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
criei_banco=1

inicio=$SECONDS
gzip -dc "$dump" | mysql --defaults-file="$credenciais" "$RESTORE_DB_NAME"
duracao=$((SECONDS - inicio))
log "restaurado em $RESTORE_DB_NAME em ${duracao}s"

# Conferência
falhas=0
reprovar() { log "FALHA: $*"; falhas=$((falhas + 1)); }

(( idade_horas <= IDADE_MAXIMA )) || [ -n "${RESTORE_ARQUIVO:-}" ] \
    || reprovar "o backup mais recente tem ${idade_horas}h (máximo ${IDADE_MAXIMA}h): o backup diário parou de rodar?"

tabelas_backup=$(grep '^tabela\.' "$manifesto" | sed 's/^tabela\.//; s/=.*//')
tabelas_restauradas=$(listar_tabelas "$credenciais" "$RESTORE_DB_NAME")
contagem_restaurada=$(contar_linhas "$credenciais" "$RESTORE_DB_NAME" "$tabelas_restauradas")

contagem_producao=""
if [ -n "${BACKUP_DB_USER:-}" ] && { [ -n "${BACKUP_DB_PASSWORD:-}" ] || [ -n "${BACKUP_DB_PASSWORD_SSM:-}" ]; }; then
    credenciais_producao="$trabalho/producao.cnf"
    criar_credenciais "$credenciais_producao" "${DB_HOST:-127.0.0.1}" "${DB_PORT:-3306}" "$BACKUP_DB_USER" "$(ler_senha BACKUP_DB_PASSWORD)"
    contagem_producao=$(contar_linhas "$credenciais_producao" "$DB_DATABASE" "$tabelas_backup" || true)
fi

linhas_de() { awk -F'\t' -v t="$1" '$1 == t {print $2}' <<<"$2"; }

printf '\n%-24s %12s %12s %14s  %s\n' "tabela" "no backup" "restaurado" "produção agora" "situação" >&2
for tabela in $tabelas_backup; do
    esperado=$(valor "tabela.$tabela")
    restaurado=$(linhas_de "$tabela" "$contagem_restaurada")
    agora=$(linhas_de "$tabela" "$contagem_producao")
    principal=0
    [[ " $PRINCIPAIS " == *" $tabela "* ]] && principal=1

    if [ -z "$restaurado" ]; then
        situacao="NÃO EXISTE"
        reprovar "a tabela $tabela não foi restaurada"
    elif [ "$restaurado" = "$esperado" ]; then
        situacao="ok"
    elif [ "$principal" = 1 ]; then
        situacao="DIFERENTE"
        reprovar "$tabela: backup tinha $esperado linhas, restaurou $restaurado"
    else
        situacao="diferente (tabela auxiliar, só aviso)"
    fi

    marca=""; [ "$principal" = 1 ] && marca="*"
    printf '%-24s %12s %12s %14s  %s\n' "$tabela$marca" "$esperado" "${restaurado:--}" "${agora:--}" "$situacao" >&2
done
printf '(* = tabela principal: a contagem precisa bater exatamente)\n\n' >&2

for tabela in $PRINCIPAIS; do
    grep -qx "$tabela" <<<"$tabelas_backup" || reprovar "a tabela principal $tabela não está no backup"
done

if [ "$falhas" -gt 0 ]; then
    falhar "teste de restauração REPROVADO com $falhas problema(s)"
fi

log "OK: backup de $criado_em restaurado e conferido ($(wc -l <<<"$tabelas_backup") tabelas, restauração em ${duracao}s)"
pingar "${RESTORE_PING_URL:-}"
