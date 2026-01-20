#!/bin/bash
# Diagnóstico Completo EFI Pix - Execute em produção via SSH
# Uso: bash public_html/tools/diagnostico_completo_ssh.sh

echo "=========================================="
echo "🔍 DIAGNÓSTICO COMPLETO EFI PIX - PRODUÇÃO"
echo "=========================================="
echo "Data/Hora: $(date)"
echo ""

# 1. Verificar diretório e estrutura
echo "1️⃣ DIRETÓRIO ATUAL"
echo "-------------------"
pwd
echo ""

# 2. Verificar Git
echo "2️⃣ VERIFICAÇÃO GIT"
echo "-------------------"
echo "Branch atual:"
git branch --show-current 2>/dev/null || echo "❌ Não é um repositório Git"
echo ""
echo "Último commit:"
git log -1 --oneline 2>/dev/null || echo "❌ Não foi possível verificar commit"
echo ""
echo "Status:"
git status --short 2>/dev/null | head -5 || echo "❌ Não foi possível verificar status"
echo ""

# 3. Verificar arquivo EfiPaymentService.php
echo "3️⃣ ARQUIVO EfiPaymentService.php"
echo "-----------------------------------"
if [ -f "app/Services/EfiPaymentService.php" ]; then
    echo "✅ Arquivo existe"
    echo "Data modificação:"
    stat -c "%y" app/Services/EfiPaymentService.php 2>/dev/null || stat -f "%Sm" app/Services/EfiPaymentService.php 2>/dev/null || echo "N/A"
    echo ""
    echo "Verificando URLs Pix:"
    if grep -q "pix.api.efipay.com.br" app/Services/EfiPaymentService.php; then
        echo "✅ Contém pix.api.efipay.com.br"
        grep -n "pix.api.efipay.com.br" app/Services/EfiPaymentService.php | head -3
    else
        echo "❌ NÃO contém pix.api.efipay.com.br"
    fi
    echo ""
    echo "Verificando baseUrlPix:"
    if grep -q "baseUrlPix" app/Services/EfiPaymentService.php; then
        echo "✅ Contém baseUrlPix"
        grep -n "baseUrlPix" app/Services/EfiPaymentService.php | head -3
    else
        echo "❌ NÃO contém baseUrlPix"
    fi
    echo ""
    echo "Verificando detecção PIX:"
    grep -A 2 "isPix = " app/Services/EfiPaymentService.php | head -5
    echo ""
else
    echo "❌ Arquivo NÃO existe"
fi
echo ""

# 4. Verificar configuração ENV
echo "4️⃣ CONFIGURAÇÃO .env"
echo "---------------------"
if [ -f ".env" ]; then
    echo "✅ Arquivo .env existe"
    echo ""
    echo "Variáveis EFI configuradas:"
    grep "^EFI_" .env | sed 's/=.*/=***/' || echo "❌ Nenhuma variável EFI encontrada"
    echo ""
    echo "EFI_SANDBOX:"
    grep "^EFI_SANDBOX" .env || echo "❌ EFI_SANDBOX não configurado"
    echo ""
    echo "EFI_PIX_KEY:"
    if grep -q "^EFI_PIX_KEY" .env; then
        PIX_KEY=$(grep "^EFI_PIX_KEY" .env | cut -d'=' -f2)
        if [ -z "$PIX_KEY" ]; then
            echo "❌ EFI_PIX_KEY está vazia"
        else
            echo "✅ EFI_PIX_KEY configurada (${#PIX_KEY} caracteres)"
        fi
    else
        echo "❌ EFI_PIX_KEY não configurada"
    fi
    echo ""
    echo "EFI_CERT_PATH:"
    CERT_PATH=$(grep "^EFI_CERT_PATH" .env | cut -d'=' -f2)
    if [ -z "$CERT_PATH" ]; then
        echo "⚠️ EFI_CERT_PATH não configurado"
    else
        echo "Caminho: $CERT_PATH"
        if [ -f "$CERT_PATH" ]; then
            echo "✅ Certificado existe"
        else
            echo "❌ Certificado NÃO existe"
        fi
    fi
else
    echo "❌ Arquivo .env NÃO existe"
fi
echo ""

# 5. Verificar última matrícula
echo "5️⃣ ÚLTIMA MATRÍCULA"
echo "-------------------"
php -r "
require 'app/Config/Database.php';
require 'app/Config/Env.php';
App\Config\Env::load();
try {
    \$db = App\Config\Database::getInstance()->getConnection();
    \$stmt = \$db->query('SELECT id, payment_method, installments, gateway_charge_id, gateway_last_status, billing_status FROM enrollments ORDER BY id DESC LIMIT 1');
    \$row = \$stmt->fetch();
    if (\$row) {
        echo 'ID: ' . \$row['id'] . PHP_EOL;
        echo 'payment_method: ' . (\$row['payment_method'] ?? 'NULL') . PHP_EOL;
        echo 'installments: ' . (\$row['installments'] ?? 'NULL') . PHP_EOL;
        echo 'gateway_charge_id: ' . (\$row['gateway_charge_id'] ?? 'NULL') . PHP_EOL;
        echo 'gateway_last_status: ' . (\$row['gateway_last_status'] ?? 'NULL') . PHP_EOL;
        echo 'billing_status: ' . (\$row['billing_status'] ?? 'NULL') . PHP_EOL;
        \$paymentMethod = \$row['payment_method'] ?? 'pix';
        \$installments = intval(\$row['installments'] ?? 1);
        \$isPix = (\$paymentMethod === 'pix' && \$installments === 1);
        echo 'Seria detectado como PIX: ' . (\$isPix ? '✅ SIM' : '❌ NÃO') . PHP_EOL;
    } else {
        echo '❌ Nenhuma matrícula encontrada' . PHP_EOL;
    }
} catch (Exception \$e) {
    echo '❌ Erro: ' . \$e->getMessage() . PHP_EOL;
}
" 2>&1
echo ""

# 6. Testar OAuth Pix
echo "6️⃣ TESTE OAUTH PIX"
echo "-------------------"
if [ -f ".env" ]; then
    CLIENT_ID=$(grep "^EFI_CLIENT_ID" .env | cut -d'=' -f2)
    CLIENT_SECRET=$(grep "^EFI_CLIENT_SECRET" .env | cut -d'=' -f2)
    SANDBOX=$(grep "^EFI_SANDBOX" .env | cut -d'=' -f2)
    
    if [ -z "$CLIENT_ID" ] || [ -z "$CLIENT_SECRET" ]; then
        echo "❌ Credenciais não configuradas"
    else
        if [ "$SANDBOX" = "false" ]; then
            OAUTH_URL="https://pix.api.efipay.com.br/oauth/token"
            echo "Ambiente: PRODUÇÃO"
        else
            OAUTH_URL="https://pix-h.api.efipay.com.br/oauth/token"
            echo "Ambiente: SANDBOX"
        fi
        echo "URL: $OAUTH_URL"
        echo ""
        
        AUTH_HEADER=$(echo -n "$CLIENT_ID:$CLIENT_SECRET" | base64)
        echo "Testando OAuth Pix..."
        
        RESPONSE=$(curl -s -w "\nHTTP_CODE:%{http_code}" -X POST "$OAUTH_URL" \
            -H "Content-Type: application/x-www-form-urlencoded" \
            -H "Authorization: Basic $AUTH_HEADER" \
            -d "grant_type=client_credentials" \
            --connect-timeout 10 \
            --max-time 30 2>&1)
        
        HTTP_CODE=$(echo "$RESPONSE" | grep "HTTP_CODE:" | cut -d':' -f2)
        BODY=$(echo "$RESPONSE" | sed '/HTTP_CODE:/d')
        
        if [ "$HTTP_CODE" = "200" ]; then
            echo "✅ OAuth Pix bem-sucedido!"
            TOKEN=$(echo "$BODY" | grep -o '"access_token":"[^"]*' | cut -d'"' -f4)
            if [ -n "$TOKEN" ]; then
                TOKEN_LEN=${#TOKEN}
                TOKEN_PREVIEW="${TOKEN:0:20}...${TOKEN: -10}"
                echo "Token (preview): $TOKEN_PREVIEW"
                echo "Token length: $TOKEN_LEN caracteres"
                
                # Verificar formato do token
                if echo "$TOKEN" | grep -q '[^[:print:]]'; then
                    echo "⚠️ Token contém caracteres não-printáveis"
                else
                    echo "✅ Token contém apenas caracteres válidos"
                fi
                
                # Testar header Authorization
                AUTH_HEADER_FULL="Authorization: Bearer $TOKEN"
                echo ""
                echo "Header Authorization (preview):"
                echo "${AUTH_HEADER_FULL:0:50}..."
                echo "Header length: ${#AUTH_HEADER_FULL} caracteres"
                
                # Verificar problemas no header
                if echo "$AUTH_HEADER_FULL" | grep -q '='; then
                    echo "⚠️ AVISO: Header contém '=' (pode causar erro)"
                fi
            fi
        else
            echo "❌ OAuth Pix falhou"
            echo "HTTP Code: $HTTP_CODE"
            echo "Resposta: $BODY" | head -200
        fi
    fi
else
    echo "❌ Arquivo .env não encontrado"
fi
echo ""

# 7. Resumo e recomendações
echo "7️⃣ RESUMO E RECOMENDAÇÕES"
echo "-------------------------"
echo ""
echo "Verificações realizadas:"
echo "✅ Código Git"
echo "✅ Arquivo EfiPaymentService.php"
echo "✅ Configuração .env"
echo "✅ Última matrícula"
echo "✅ Teste OAuth Pix"
echo ""
echo "Próximos passos se houver problemas:"
echo "1. Se código não atualizado: git pull origin master"
echo "2. Se EFI_PIX_KEY não configurada: adicione no .env"
echo "3. Se certificado não existe: verifique EFI_CERT_PATH"
echo "4. Se OAuth falhou: verifique credenciais e certificado"
echo ""

echo "=========================================="
echo "FIM DO DIAGNÓSTICO"
echo "=========================================="
