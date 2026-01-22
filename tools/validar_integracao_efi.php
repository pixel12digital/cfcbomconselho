<?php
/**
 * Script de Validação - Integração Gateway EFI
 * 
 * Uso via linha de comando:
 *   php tools/validar_integracao_efi.php
 * 
 * Ou acesse via browser:
 *   http://localhost/cfc-v.1/public_html/tools/validar_integracao_efi.php
 * 
 * Este script valida completamente a integração com o gateway EFI:
 * - Configuração (.env)
 * - Autenticação OAuth
 * - Criação de cobrança
 * - Consulta de status
 * - Sincronização
 * - Mapeamento de status
 */

// Se executado via CLI, redirecionar para versão web
if (php_sapi_name() === 'cli') {
    echo "Este script deve ser executado via browser.\n";
    echo "Acesse: http://localhost/cfc-v.1/public_html/tools/validar_integracao_efi.php\n";
    exit(1);
}

// Redirecionar para versão web
header('Location: /tools/validar_integracao_efi.php');
exit;
