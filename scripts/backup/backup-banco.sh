#!/usr/bin/env bash
# Backup diário do MySQL de produção, guardado no S3 (fora do servidor).
#
# Cada execução envia para "$BACKUP_S3_URI/diario/" o dump comprimido e um manifesto com
# o sha256 e a contagem de linhas de cada tabela no momento do backup. No dia
# BACKUP_DIA_SEMANAL a mesma cópia vai também para "semanal/". Quem apaga as cópias antigas
# é a regra de ciclo de vida do bucket (7 diárias, 4 semanais); o servidor não tem
# permissão para apagar backup, então quem invadir o servidor também não tem.
#
# Variáveis (ver backup.env.example):
#   DB_DATABASE, BACKUP_DB_USER, BACKUP_S3_URI            obrigatórias
#   BACKUP_DB_PASSWORD ou BACKUP_DB_PASSWORD_SSM          senha direta ou nome do parâmetro no SSM
#   DB_HOST (127.0.0.1), DB_PORT (3306), BACKUP_DIA_SEMANAL (7 = domingo),
#   BACKUP_TMP_DIR (/var/tmp), BACKUP_PING_URL (opcional, avisado quando dá certo)

NOME_SCRIPT=backup
# shellcheck source=lib.sh
source "$(dirname "$(readlink -f "$0")")/lib.sh"

exigir DB_DATABASE BACKUP_DB_USER BACKUP_S3_URI
exigir_comando mysql mysqldump gzip sha256sum aws

DB_HOST=${DB_HOST:-127.0.0.1}
DB_PORT=${DB_PORT:-3306}
DESTINO=${BACKUP_S3_URI%/}
DIA_SEMANAL=${BACKUP_DIA_SEMANAL:-7}

trabalho=$(mktemp -d "${BACKUP_TMP_DIR:-/var/tmp}/backup-banco.XXXXXX")
trap 'rm -rf "$trabalho"' EXIT

credenciais="$trabalho/cliente.cnf"
criar_credenciais "$credenciais" "$DB_HOST" "$DB_PORT" "$BACKUP_DB_USER" "$(ler_senha BACKUP_DB_PASSWORD)"

inicio=$SECONDS
agora=$(date -u +%s)
carimbo=$(date -u -d "@$agora" +%Y-%m-%d_%H%M%S)
nome="${DB_DATABASE}_${carimbo}"
dump="$trabalho/$nome.sql.gz"
manifesto="$trabalho/$nome.manifest"

log "backup de $DB_DATABASE em $DB_HOST:$DB_PORT"

tabelas=$(listar_tabelas "$credenciais" "$DB_DATABASE")
[ -n "$tabelas" ] || falhar "nenhuma tabela encontrada em $DB_DATABASE"
quantidade=$(wc -l <<<"$tabelas")

# Contagem tirada logo antes do dump: é a foto da produção que o teste de restauração confere
contagens=$(contar_linhas "$credenciais" "$DB_DATABASE" "$tabelas")

# Sem --databases de propósito: o dump não tem CREATE DATABASE nem USE, então só
# consegue ser restaurado no banco escolhido na hora, nunca "volta sozinho" para produção.
opcoes=(--single-transaction --quick --triggers --no-tablespaces --hex-blob --default-character-set=utf8mb4)
ajuda=$(mysqldump --help)
grep -q -- '--set-gtid-purged' <<<"$ajuda" && opcoes+=(--set-gtid-purged=OFF)
grep -q -- '--column-statistics' <<<"$ajuda" && opcoes+=(--column-statistics=0)

mysqldump --defaults-file="$credenciais" "${opcoes[@]}" "$DB_DATABASE" | gzip -6 >"$dump"

# O dump só vale se estiver íntegro, completo e com todas as tabelas
gzip -t "$dump" || falhar "arquivo comprimido corrompido"
gzip -dc "$dump" | tail -n 1 | grep -q '^-- Dump completed' || falhar "dump incompleto (sem a linha final do mysqldump)"
criadas=$(gzip -dc "$dump" | grep -c '^CREATE TABLE' || true)
[ "$criadas" -eq "$quantidade" ] || falhar "o dump tem $criadas tabelas, mas o banco tem $quantidade"

{
    echo "formato=1"
    echo "banco=$DB_DATABASE"
    echo "criado_em=$(date -u -d "@$agora" +%Y-%m-%dT%H:%M:%SZ)"
    echo "servidor=$(sql "$credenciais" 'SELECT VERSION()')"
    echo "arquivo=$nome.sql.gz"
    echo "sha256=$(sha256sum "$dump" | cut -d' ' -f1)"
    echo "bytes=$(stat -c %s "$dump")"
    while IFS=$'\t' read -r tabela linhas; do
        echo "tabela.$tabela=$linhas"
    done <<<"$contagens"
} >"$manifesto"

enviar() { aws s3 cp "$1" "$2" --only-show-errors --sse AES256; }

# O manifesto sobe por último: backup sem manifesto é tratado como incompleto e ignorado
enviar "$dump" "$DESTINO/diario/$nome.sql.gz"
enviar "$manifesto" "$DESTINO/diario/$nome.manifest"

if [ "$(date -u +%u)" = "$DIA_SEMANAL" ]; then
    enviar "$dump" "$DESTINO/semanal/$nome.sql.gz"
    enviar "$manifesto" "$DESTINO/semanal/$nome.manifest"
    log "cópia semanal enviada"
fi

log "OK: $nome.sql.gz ($(stat -c %s "$dump") bytes, $quantidade tabelas) em $((SECONDS - inicio))s"
pingar "${BACKUP_PING_URL:-}"
