#!/bin/bash
# 🗂️ Script de Backup Automático - CFC Bom Conselho
# Execução: via Cron Jobs da Hostinger (diário às 2h)

set -e

# Configurações
APP_NAME="CFC Bom Conselho"
BACKUP_DIR="/backups"
SOURCE_DIR="/public_html"
DATE=$(date +%Y%m%d-%H%M%S)
LOG_FILE="$BACKUP_DIR/backup.log"

# Criar diretório de backup se não existir
mkdir -p "$BACKUP_DIR"

# Função de log
log() {
    echo "[$(date)] $1" >> "$LOG_FILE"
    echo "[$(date)] $1"
}

log "🚀 Iniciando backup do $APP_NAME"

# Verificar se diretório fonte existe
if [ ! -d "$SOURCE_DIR" ]; then
    log "❌ Erro: Diretório $SOURCE_DIR não encontrado"
    exit 1
fi

# Criar backup
BACKUP_NAME="backup-$DATE"
BACKUP_PATH="$BACKUP_DIR/$BACKUP_NAME"

log "📦 Criando backup em: $BACKUP_PATH"

# Copiar arquivos principais
mkdir -p "$BACKUP_PATH"
cp -r "$SOURCE_DIR"/* "$BACKUP_PATH/" 2>/dev/null || {
    log "❌ Erro ao copiar arquivos para backup"
    exit 1
}

# Backup adicional dos arquivos de configuração
log "⚙️ Fazendo backup das configurações"
cp /etc/passwd "$BACKUP_PATH/system_passwd.bak" 2>/dev/null || true
cp /etc/hosts "$BACKUP_PATH/system_hosts.bak" 2>/dev/null || true

# Backup da base de dados (se possível)
log "🗄️ Tentando backup da base de dados"
mysqldump -u root --all-databases > "$BACKUP_PATH/database_full.sql" 2>/dev/null || {
    log "⚠️ Backup de banco de dados não disponível"
}

# Comprimir backup
log "🗜️ Comprimindo backup"
cd "$BACKUP_DIR"
tar -czf "$BACKUP_NAME.tar.gz" "$BACKUP_NAME"
rm -rf "$BACKUP_NAME"

# Limpar backups antigos (manter últimos 30 dias)
log "🧹 Limpando backups antigos"
find "$BACKUP_DIR" -name "backup-*.tar.gz" -mtime +30 -delete

# Calcular tamanho do backup
BACKUP_SIZE=$(du -h "$BACKUP_DIR/$BACKUP_NAME.tar.gz" | cut -f1)

log "✅ Backup concluído com sucesso!"
log "📊 Backup: $BACKUP_NAME.tar.gz ($BACKUP_SIZE)"
log "📅 Data: $(date)"

# Contar total de backups
TOTAL_BACKUPS=$(ls "$BACKUP_DIR"/backup-*.tar.gz 2>/dev/null | wc -l)
log "📈 Total de backups disponíveis: $TOTAL_BACKUPS"

# Verificar espaço disponível
AVAILABLE_SPACE=$(df -h "$BACKUP_DIR" | awk 'NR==2 {print $4}')
log "💾 Espaço disponível: $AVAILABLE_SPACE"

log "🎉 Backup do $APP_NAME finalizado!"

exit 0
