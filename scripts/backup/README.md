# Backup do banco (MySQL na EC2)

| | |
|---|---|
| **O que é salvo** | o banco `agendamento` inteiro (`mysqldump --single-transaction`, gzip) |
| **Quando** | todo dia às 03:00 (Brasília); teste de restauração às 04:30 |
| **Onde** | S3 `s3://NOME-DO-BUCKET/agendamento/{diario,semanal}/`, fora do servidor |
| **Por quanto tempo** | 7 cópias diárias + 4 semanais (domingo), apagadas pelo ciclo de vida do bucket |
| **Até onde volta** | até 28 dias atrás; dentro da última semana, qualquer dia |
| **Perda máxima (RPO)** | até 24 h: tudo o que entrou depois das 03:00 do último backup |
| **Tempo para voltar (RTO)** | ~20 min de procedimento + o tempo de restauração que o teste diário registra no log |

Cada backup leva junto um `.manifest` com o sha256 do arquivo e a contagem de linhas de cada
tabela no momento do backup. Todo dia, o teste de restauração baixa a cópia mais recente,
confere o sha256, restaura num **banco separado**, confere se todas as tabelas existem e se a
contagem das principais bate com a da produção registrada no manifesto. Também reprova se o
último backup tiver mais de 26 h. No fim, apaga o banco de teste.

O servidor só consegue **enviar e ler** backups, nunca apagar: quem invadir a EC2 não leva os
backups junto. Com o versionamento do bucket, nem sobrescrever um backup apaga a versão anterior.

## 1. Diagnóstico: o que existe hoje

No repositório não existe backup nenhum: nem script, nem pacote, nem tarefa no agendador
do Laravel. **Uma EC2 não tem backup automático por padrão** (diferente do RDS). Confira o
que pode existir fora do repositório:

```bash
# Na EC2
sudo crontab -l; crontab -l; ls /etc/cron.d /etc/cron.daily; systemctl list-timers
sudo find / -xdev \( -name '*.sql' -o -name '*.sql.gz' \) -mtime -60 2>/dev/null | head
cat /var/www/agendamento/deploy.sh      # faz dump antes do migrate?
```

No console da AWS:
- **EC2 → Elastic Block Store → Snapshots**: snapshots do volume da instância (data do mais recente)
- **EC2 → Lifecycle Manager**: política que tira snapshots sozinha
- **AWS Backup → Backup plans / Protected resources**

Se nada aparecer, hoje a perda máxima é **tudo**.

## 2. Instalação (uma vez)

Os comandos `aws` podem ser rodados no **CloudShell** (o ícone `>_` no topo do console da AWS),
que já está autenticado. Troque `NOME-DO-BUCKET`, `ID-DA-CONTA` e a região onde aparecerem.

**Bucket** (privado por padrão, com versionamento e retenção):
```bash
aws s3api create-bucket --bucket NOME-DO-BUCKET --region us-east-2 \
    --create-bucket-configuration LocationConstraint=us-east-2
aws s3api put-bucket-versioning --bucket NOME-DO-BUCKET --versioning-configuration Status=Enabled
aws s3api put-bucket-lifecycle-configuration --bucket NOME-DO-BUCKET \
    --lifecycle-configuration file://ciclo-de-vida-s3.json     # o arquivo está em aws/
```

**Permissão da EC2:** em IAM → Roles, crie uma role para EC2 com a política
[`aws/politica-iam-servidor.json`](aws/politica-iam-servidor.json) e associe-a à instância (EC2 →
Actions → Security → Modify IAM role). Se a instância já tiver uma role, só adicione a política
a ela. Não crie access keys: a role dá credenciais temporárias.

**Usuários do MySQL** (na EC2, `sudo mysql`). Gere senhas fortes e guarde-as só no SSM:
```sql
CREATE USER 'backup'@'localhost' IDENTIFIED BY '<senha-backup>';
GRANT SELECT, SHOW VIEW, TRIGGER ON agendamento.* TO 'backup'@'localhost';

CREATE USER 'restauracao'@'localhost' IDENTIFIED BY '<senha-restauracao>';
GRANT ALL PRIVILEGES ON agendamento_restauracao_teste.* TO 'restauracao'@'localhost';
```
O usuário `restauracao` só existe dentro do banco de teste. O script confere isso a cada
execução e aborta se ele conseguir abrir a produção.

**Senhas no SSM** (pelo console: Systems Manager → Parameter Store → Create, tipo
*SecureString*), uma com cada nome:
- `/agendamento/backup/db-password-backup`
- `/agendamento/backup/db-password-restauracao`

**No servidor:**
```bash
# AWS CLI v2 (instalador oficial; o snap não funciona com o usuário descartável do systemd)
curl -sSo /tmp/awscliv2.zip https://awscli.amazonaws.com/awscli-exe-linux-x86_64.zip
cd /tmp && unzip -q awscliv2.zip && sudo ./aws/install && cd -

# Scripts num diretório do root: o systemd não executa nada que o usuário do deploy possa alterar
cd /var/www/agendamento/scripts/backup
sudo install -d /usr/local/lib/agendamento-backup /etc/agendamento
sudo install -m 755 lib.sh backup-banco.sh testar-restauracao.sh /usr/local/lib/agendamento-backup/
sudo install -m 600 backup.env.example /etc/agendamento/backup.env
sudo nano /etc/agendamento/backup.env          # bucket, banco e região
sudo install -m 644 systemd/* /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now backup-banco.timer teste-restauracao.timer

# Primeira execução, na mão, para ver funcionando
sudo systemctl start backup-banco.service && sudo systemctl start teste-restauracao.service
journalctl -u backup-banco -u teste-restauracao -n 60 --no-pager
```
Quando os scripts mudarem no repositório, repita a linha do `install -m 755`.

**Monitoramento:** um backup que para de rodar sem ninguém perceber é o caso mais comum de
"tinha backup, mas não tinha". Crie dois checks num serviço de *dead man's switch*
(ex.: healthchecks.io, plano grátis) e preencha `BACKUP_PING_URL` e `RESTORE_PING_URL`. Você
recebe um e-mail no dia em que o aviso **não** chegar.

## 3. O dia que der ruim

Regra de ouro: **nunca restaure por cima do banco atual.** Restaure num banco novo e aponte
o app para ele; o banco antigo fica intacto para investigação.

```bash
cd /var/www/agendamento
set -a; . <(sudo cat /etc/agendamento/backup.env); set +a   # bucket e nomes (sem senhas)
```

1. **Pare as escritas:** `php artisan down`
2. **Guarde o estado atual**, mesmo estragado: `sudo systemctl start backup-banco.service`
3. **Escolha o backup.** O mais novo agora é o do passo 2, então escolha pela data:
   ```bash
   aws s3 ls "$BACKUP_S3_URI/diario/"; aws s3 ls "$BACKUP_S3_URI/semanal/"
   ARQUIVO=diario/agendamento_AAAA-MM-DD_HHMMSS.sql.gz
   ```
4. **Valide antes de usar.** Isto restaura no banco de teste, confere e mantém o banco para você olhar os dados:
   ```bash
   sudo ARQUIVO="$ARQUIVO" bash -c 'set -a; . /etc/agendamento/backup.env; set +a
       RESTORE_ARQUIVO="$ARQUIVO" RESTORE_MANTER=1 /usr/local/lib/agendamento-backup/testar-restauracao.sh'
   ```
5. **Restaure num banco novo:**
   ```bash
   NOVO=agendamento_$(date +%Y%m%d)
   aws s3 cp "$BACKUP_S3_URI/$ARQUIVO" /var/tmp/restaurar.sql.gz
   sudo mysql -e "CREATE DATABASE $NOVO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
   gunzip -c /var/tmp/restaurar.sql.gz | sudo mysql "$NOVO"
   sudo mysql -e "GRANT ALL PRIVILEGES ON $NOVO.* TO '<usuario-do-app>'@'localhost';
                  GRANT SELECT, SHOW VIEW, TRIGGER ON $NOVO.* TO 'backup'@'localhost'"
   ```
6. **Aponte o app e o backup para o banco novo:**
   ```bash
   sed -i "s/^DB_DATABASE=.*/DB_DATABASE=$NOVO/" .env && php artisan config:cache
   sudo sed -i "s/^DB_DATABASE=.*/DB_DATABASE=$NOVO/" /etc/agendamento/backup.env
   php artisan up
   sudo systemctl start backup-banco.service teste-restauracao.service   # já protege o banco novo
   ```
7. Depois de alguns dias, com tudo estável, apague o banco antigo e o banco de teste mantido no passo 4.

**O que se perde:**
- Tudo o que foi gravado depois do horário do backup escolhido: agendamentos, cadastros,
  cancelamentos, serviços e profissionais editados. No pior caso, quase 24 h.
- Quem fez login depois do backup precisa entrar de novo. Quem ativou as notificações depois
  do backup precisa ativá-las de novo.

## 4. O que este backup NÃO cobre

- **Imagens enviadas** (logos e fotos de serviços em `storage/app/public`): ficam no disco,
  não no banco. Para elas, ligue snapshots diários do volume no **EC2 → Lifecycle Manager**
  ou sincronize a pasta com o S3.
- **O `.env` de produção** (`APP_KEY`, segredo do Google, chaves VAPID): guarde uma cópia
  no SSM ou num gerenciador de senhas. Sem as chaves VAPID, todas as notificações param.
- **Perda menor que 24 h:** exigiria ligar o binlog do MySQL e guardá-lo no S3
  (restauração até o minuto). Só vale a pena se perder um dia de agendamentos for inaceitável.
