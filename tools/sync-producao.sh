#!/bin/bash

# Script para sincronizar código de produção com o repositório remoto
# Uso: ./sync-producao.sh

set -e  # Parar em caso de erro

echo "🔄 Sincronizando código de produção..."

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Verificar se estamos no diretório correto
if [ ! -d ".git" ]; then
    echo -e "${RED}❌ Erro: Este script deve ser executado na raiz do projeto${NC}"
    exit 1
fi

# Verificar status atual
echo -e "${YELLOW}📊 Verificando status do repositório...${NC}"
git status

# Fazer fetch do repositório de produção
echo -e "${YELLOW}📥 Fazendo fetch do repositório de produção...${NC}"
git fetch production

# Verificar se há diferenças
LOCAL_COMMIT=$(git rev-parse HEAD)
REMOTE_COMMIT=$(git rev-parse production/master)

if [ "$LOCAL_COMMIT" = "$REMOTE_COMMIT" ]; then
    echo -e "${GREEN}✅ Código local e produção estão sincronizados${NC}"
    echo "Commit: $LOCAL_COMMIT"
else
    echo -e "${YELLOW}⚠️  Há diferenças entre local e produção${NC}"
    echo "Local:   $LOCAL_COMMIT"
    echo "Remoto:  $REMOTE_COMMIT"
    
    # Mostrar diferenças
    echo -e "${YELLOW}📋 Arquivos diferentes:${NC}"
    git diff --name-status HEAD production/master
    
    # Perguntar se deseja fazer pull
    read -p "Deseja fazer pull do repositório de produção? (s/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Ss]$ ]]; then
        echo -e "${YELLOW}📥 Fazendo pull do repositório de produção...${NC}"
        git pull production master
        
        if [ $? -eq 0 ]; then
            echo -e "${GREEN}✅ Pull realizado com sucesso!${NC}"
        else
            echo -e "${RED}❌ Erro ao fazer pull. Verifique os conflitos.${NC}"
            exit 1
        fi
    fi
fi

# Verificar se há mudanças locais não commitadas
if [ -n "$(git status --porcelain)" ]; then
    echo -e "${YELLOW}⚠️  Há mudanças locais não commitadas:${NC}"
    git status --short
    
    read -p "Deseja ver as diferenças? (s/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Ss]$ ]]; then
        git diff
    fi
else
    echo -e "${GREEN}✅ Não há mudanças locais não commitadas${NC}"
fi

echo -e "${GREEN}✅ Sincronização concluída!${NC}"
