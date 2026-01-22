#!/bin/bash
# Teste detalhado OAuth Pix com certificado
# Execute: bash public_html/tools/teste_oauth_pix_detalhado.sh

echo "=== TESTE OAUTH PIX DETALHADO ==="
echo ""

# Carregar variáveis do .env
if [ ! -f ".env" ]; then
    echo "❌ Arquivo .env não encontrado"
    exit 1
fi

CLIENT_ID=$(grep "^EFI_CLIENT_ID" .env | cut -d'=' -f2)
CLIENT_SECRET=$(grep "^EFI_CLIENT_SECRET" .env | cut -d'=' -f2)
CERT_PATH=$(grep "^EFI_CERT_PATH" .env | cut -d'=' -f2)
CERT_PASSWORD=$(grep "^EFI_CERT_PASSWORD" .env | cut -d'=' -f2)
SANDBOX=$(grep "^EFI_SANDBOX" .env | cut -d'=' -f2)

if [ -z "$CLIENT_ID" ] || [ -z "$CLIENT_SECRET" ]; then
    echo "❌ Credenciais não configuradas"
    exit 1
fi

if [ "$SANDBOX" = "false" ]; then
    OAUTH_URL="https://pix.api.efipay.com.br/oauth/token"
    echo "Ambiente: PRODUÇÃO"
else
    OAUTH_URL="https://pix-h.api.efipay.com.br/oauth/token"
    echo "Ambiente: SANDBOX"
fi

echo "URL: $OAUTH_URL"
echo "Client ID: ${CLIENT_ID:0:20}..."
echo ""

# Preparar autenticação
AUTH_HEADER=$(echo -n "$CLIENT_ID:$CLIENT_SECRET" | base64)

echo "=== TESTE 1: OAuth Pix SEM certificado ==="
RESPONSE1=$(curl -s -w "\nHTTP_CODE:%{http_code}\nTIME:%{time_total}" -X POST "$OAUTH_URL" \
    -H "Content-Type: application/x-www-form-urlencoded" \
    -H "Authorization: Basic $AUTH_HEADER" \
    -d "grant_type=client_credentials" \
    --connect-timeout 10 \
    --max-time 30 \
    -v 2>&1)

HTTP_CODE1=$(echo "$RESPONSE1" | grep "HTTP_CODE:" | cut -d':' -f2)
echo "HTTP Code: $HTTP_CODE1"
echo "Resposta:"
echo "$RESPONSE1" | grep -v "HTTP_CODE:" | grep -v "TIME:" | head -20
echo ""

if [ -n "$CERT_PATH" ] && [ -f "$CERT_PATH" ]; then
    echo "=== TESTE 2: OAuth Pix COM certificado ==="
    
    # Criar arquivo temporário para curl com certificado
    TEMP_CURL=$(mktemp)
    cat > "$TEMP_CURL" << EOF
url = "$OAUTH_URL"
output = /dev/stdout
silent = false
show-error = true
write-out = "\nHTTP_CODE:%{http_code}\nTIME:%{time_total}\n"
connect-timeout = 10
max-time = 30
request = POST
header = "Content-Type: application/x-www-form-urlencoded"
header = "Authorization: Basic $AUTH_HEADER"
data = "grant_type=client_credentials"
cert = "$CERT_PATH"
cert-type = P12
key = "$CERT_PATH"
key-type = P12
EOF

    if [ -n "$CERT_PASSWORD" ]; then
        echo "key = $CERT_PASSWORD" >> "$TEMP_CURL"
        echo "pass = $CERT_PASSWORD" >> "$TEMP_CURL"
    fi
    
    RESPONSE2=$(curl -K "$TEMP_CURL" 2>&1)
    rm -f "$TEMP_CURL"
    
    HTTP_CODE2=$(echo "$RESPONSE2" | grep "HTTP_CODE:" | cut -d':' -f2)
    echo "HTTP Code: $HTTP_CODE2"
    echo "Resposta:"
    echo "$RESPONSE2" | grep -v "HTTP_CODE:" | grep -v "TIME:" | head -20
    echo ""
    
    # Teste alternativo com curl direto
    echo "=== TESTE 3: OAuth Pix COM certificado (método alternativo) ==="
    CURL_CMD="curl -X POST \"$OAUTH_URL\" \
        -H \"Content-Type: application/x-www-form-urlencoded\" \
        -H \"Authorization: Basic $AUTH_HEADER\" \
        -d \"grant_type=client_credentials\" \
        --cert \"$CERT_PATH\" \
        --cert-type P12"
    
    if [ -n "$CERT_PASSWORD" ]; then
        CURL_CMD="$CURL_CMD --pass \"$CERT_PASSWORD\""
    fi
    
    CURL_CMD="$CURL_CMD -v --connect-timeout 10 --max-time 30 2>&1"
    
    RESPONSE3=$(eval $CURL_CMD)
    HTTP_CODE3=$(echo "$RESPONSE3" | grep -o "HTTP/[0-9.]* [0-9]*" | tail -1 | awk '{print $2}')
    
    echo "HTTP Code: $HTTP_CODE3"
    echo "Resposta:"
    echo "$RESPONSE3" | tail -30
    echo ""
else
    echo "⚠️ Certificado não configurado ou não existe"
fi

echo "=== RESUMO ==="
echo "Se HTTP Code 200: ✅ OAuth funcionando"
echo "Se HTTP Code 000: ❌ Erro de conexão (verificar certificado/firewall)"
echo "Se HTTP Code 401: ❌ Credenciais inválidas"
echo "Se HTTP Code 403: ❌ Certificado inválido ou não corresponde às credenciais"
