#!/bin/bash

# Script para configurar remote "production" no servidor
# Uso: ./configurar-remote-producao.sh

set -e

echo "🔧 Configurando remote production no servidor..."

# Cores
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# Verificar se estamos no diretório correto
if [ ! -d ".git" ]; then
    echo -e "${RED}❌ Erro: Este script deve ser executado na raiz do projeto${NC}"
    exit 1
fi

# Verificar remotes atuais
echo -e "${YELLOW}📋 Remotes atuais:${NC}"
git remote -v

# Verificar se production já existe
if git remote | grep -q "^production$"; then
    echo -e "${GREEN}✅ Remote 'production' já existe${NC}"
    git remote -v | grep production
else
    echo -e "${YELLOW}➕ Adicionando remote 'production'...${NC}"
    
    # Tentar HTTPS primeiro
    if git remote add production https://github.com/pixel12digital/cfcbomconselho.git 2>/dev/null; then
        echo -e "${GREEN}✅ Remote 'production' adicionado (HTTPS)${NC}"
    else
        echo -e "${YELLOW}⚠️  Tentando com SSH...${NC}"
        git remote add production git@github.com:pixel12digital/cfcbomconselho.git
        echo -e "${GREEN}✅ Remote 'production' adicionado (SSH)${NC}"
    fi
fi

# Fazer fetch
echo -e "${YELLOW}📥 Fazendo fetch do production...${NC}"
git fetch production

# Verificar branches
echo -e "${YELLOW}📊 Branches remotos:${NC}"
git branch -r | grep production

# Comparar commits
LOCAL=$(git rev-parse HEAD)
REMOTE=$(git rev-parse production/master 2>/dev/null || echo "")

if [ -z "$REMOTE" ]; then
    echo -e "${RED}❌ Não foi possível obter commit remoto${NC}"
    exit 1
fi

echo -e "${YELLOW}📋 Comparando commits:${NC}"
echo "Local:  $LOCAL"
echo "Remoto: $REMOTE"

if [ "$LOCAL" = "$REMOTE" ]; then
    echo -e "${GREEN}✅ Código já está sincronizado!${NC}"
else
    echo -e "${YELLOW}⚠️  Há diferenças. Fazendo pull...${NC}"
    git pull production master
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ Pull realizado com sucesso!${NC}"
    else
        echo -e "${RED}❌ Erro ao fazer pull. Verifique os conflitos.${NC}"
        exit 1
    fi
fi

# Verificar status final
echo -e "${YELLOW}📊 Status final:${NC}"
git status

echo -e "${GREEN}✅ Configuração concluída!${NC}"
