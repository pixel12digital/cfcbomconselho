<?php
/**
 * 🚀 Sistema de Controle de Deploy - CFC Bom Conselho
 * Informações de versão e deploy automático
 */

class DeployInfo {
    private static $versionFile = '.version';
    
    public static function getVersion() {
        if (!file_exists(self::$versionFile)) {
            return 'Versão não disponível';
        }
        
        $lines = file(self::$versionFile);
        return trim($lines[0] ?? 'Desconhecida');
    }
    
    public static function getDeployDate() {
        if (!file_exists(self::$versionFile)) {
            return 'Data não disponível';
        }
        
        $lines = file(self::$versionFile);
        return trim($lines[1] ?? 'Data desconhecida');
    }
    
    public static function getAllInfo() {
        return [
            'version' => self::getVersion(),
            'deploy_date' => self::getDeployDate(),
            'app_name' => 'CFC Bom Conselho',
            'environment' => ENVIRONMENT ?? 'production',
            'php_version' => PHP_VERSION,
            'server_time' => date('Y-m-d H:i:s'),
            'last_git_commit' => exec('git log -1 --pretty=format:"%h - %an, %ar : %s"') ?: 'N/A'
        ];
    }
    
    public static function displayVersion() {
        $info = self::getAllInfo();
        return "
        🚀 CFC Bom Conselho - Sistema de Deploy
        ======================================
        📦 Versão: {$info['version']}
        📅 Deploy: {$info['deploy_date']}  
        🌍 Ambiente: {$info['environment']}
        📱 PHP: {$info['php_version']}
        ⏰ Servidor: {$info['server_time']}
        🔄 Git: {$info['last_git_commit']}
        ";
    }
}

// Auto-exibir informações em modo debug
if (defined('DEBUG_MODE') && DEBUG_MODE && isset($_GET['debug_version'])) {
    echo '<pre>' . DeployInfo::displayVersion() . '</pre>';
}
?>
