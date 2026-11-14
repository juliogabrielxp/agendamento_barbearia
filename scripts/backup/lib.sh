# Funções comuns aos scripts de backup e de teste de restauração.
# Nenhuma senha fica aqui: ela vem de uma variável de ambiente ou do SSM Parameter Store.

set -euo pipefail
umask 077

log() { printf '%s [%s] %s\n' "$(date -u +%Y-%m-%dT%H:%M:%SZ)" "${NOME_SCRIPT:-backup}" "$*" >&2; }
falhar() { log "ERRO: $*"; exit 1; }

exigir() {
    local variavel
    for variavel in "$@"; do
        [ -n "${!variavel:-}" ] || falhar "variável $variavel não definida"
    done
}

exigir_comando() {
    local comando
    for comando in "$@"; do
        command -v "$comando" >/dev/null || falhar "comando '$comando' não encontrado"
    done
}

# Senha pela variável (ex.: BACKUP_DB_PASSWORD) ou pelo nome do parâmetro no SSM (BACKUP_DB_PASSWORD_SSM)
ler_senha() {
    local variavel=$1
    local variavel_ssm="${1}_SSM"

    if [ -n "${!variavel:-}" ]; then
        printf '%s' "${!variavel}"
    elif [ -n "${!variavel_ssm:-}" ]; then
        aws ssm get-parameter --name "${!variavel_ssm}" --with-decryption \
            --query Parameter.Value --output text \
            || falhar "não consegui ler o parâmetro ${!variavel_ssm} no SSM"
    else
        falhar "defina $variavel ou $variavel_ssm"
    fi
}

# Arquivo de opções do cliente MySQL (modo 600, apagado no fim): a senha nunca vai
# para a linha de comando, onde apareceria no "ps" de qualquer usuário do servidor.
criar_credenciais() {
    local destino=$1 host=$2 porta=$3 usuario=$4 senha=$5
    usuario=${usuario//\\/\\\\}; usuario=${usuario//\"/\\\"}
    senha=${senha//\\/\\\\}; senha=${senha//\"/\\\"}

    cat >"$destino" <<EOF
[client]
host="$host"
port=$porta
user="$usuario"
password="$senha"
EOF
}

# sql <credenciais> <comando> [banco]: resultado em TSV, sem cabeçalho
sql() {
    mysql --defaults-file="$1" --batch --skip-column-names -e "$2" ${3:+"$3"}
}

literal_sql() { local valor=${1//\\/\\\\}; printf "'%s'" "${valor//\'/\'\'}"; }
identificador_sql() { printf '`%s`' "${1//\`/\`\`}"; }

# Tabelas reais (sem views) do banco, uma por linha
listar_tabelas() {
    sql "$1" "SELECT table_name FROM information_schema.tables
              WHERE table_schema = $(literal_sql "$2") AND table_type = 'BASE TABLE'
              ORDER BY table_name"
}

# Contagem exata de linhas de cada tabela, num único snapshot consistente.
# Saída: "tabela<TAB>linhas"
contar_linhas() {
    local credenciais=$1 banco=$2 tabelas=$3 consulta="" tabela

    while IFS= read -r tabela; do
        [ -n "$tabela" ] || continue
        consulta+="${consulta:+ UNION ALL }SELECT $(literal_sql "$tabela"), COUNT(*) FROM $(identificador_sql "$tabela")"
    done <<<"$tabelas"

    [ -n "$consulta" ] || return 0

    sql "$credenciais" "SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ;
                        START TRANSACTION WITH CONSISTENT SNAPSHOT, READ ONLY;
                        $consulta;
                        COMMIT;" "$banco"
}

pingar() {
    [ -n "${1:-}" ] || return 0
    curl -fsS -m 10 --retry 3 "$1" >/dev/null || log "aviso: não consegui avisar o monitor em $1"
}
