<?php
/**
 * 🔍 Sistema de Monitoramento Profissional - CFC Bom Conselho
 * Monitoramento contínuo de saúde da aplicação
 */

require_once 'includes/config.php';
require_once 'config/deploy-info.php';

class SystemMonitor {
    private $checks = [];
    private $alerts = [];
    private $statusFile = 'logs/system-status.json';
    
    public function runHealthCheck() {
        $this->checks['timestamp'] = date('Y-m-d H:i:s');
        $this->checks['overall_status'] = 'healthy';
        
        // Verificar componentes críticos
        $this->checkDatabase();
        $this->checkFilePermissions();
        $this->checkDiskSpace();
        $this->checkMemoryUsage();
        $this->checkProcessStatus();
        $this->checkLogFileSize();
        $this->checkDeployStatus();
        
        // Calcular status geral
        $criticalIssues = array_filter($this->checks, function($check) {
            return isset($check['status']) && $check['status'] === 'critical';
        });
        
        if (!empty($criticalIssues)) {
            $this->checks['overall_status'] = 'critical';
        } elseif (!empty($this->alerts)) {
            $this->checks['overall_status'] = 'warning';
        }
        
        // Salvar status
        $this->saveStatus();
        
        // Enviar alertas se necessário
        $this->sendAlerts();
        
        return $this->checks;
    }
    
    private function checkDatabase() {
        try {
            $db = db();
            $result = $db->fetch("SELECT 1 as test", []);
            
            $this->checks['database'] = [
                'status' => 'healthy',
                'message' => 'Conexão OK',
                'timestamp' => date('H:i:s')
            ];
        } catch (Exception $e) {
            $this->checks['database'] = [
                'status' => 'critical',
                'message' => 'Erro: ' . $e->getMessage(),
                'timestamp' => date('H:i:s')
            ];
            $this->alerts[] = 'Database connection failed';
        }
    }
    
    private function checkFilePermissions() {
        $criticalFiles = ['login.php', 'includes/config.php', 'index.php'];
        $issues = [];
        
        foreach ($criticalFiles as $file) {
            if (!file_exists($file)) {
                $issues[] = "$file não encontrado";
                continue;
            }
            
            $perms = fileperms($file);
            if ($perms & 0022) { // Escritável por outros
                $issues[] = "$file com permissões inseguras";
            }
        }
        
        if (empty($issues)) {
            $this->checks['file_permissions'] = [
                'status' => 'healthy',
                'message' => 'Permissões OK',
                'timestamp' => date('H:i:s')
            ];
        } else {
            $this->checks['file_permissions'] = [
                'status' => 'warning',
                'message' => implode(', ', $issues),
                'timestamp' => date('H:i:s')
            ];
        }
    }
    
    private function checkDiskSpace() {
        $bytes = disk_free_space('.');
        $total = disk_total_space('.');
        $used = $total - $bytes;
        $percentUsed = round(($used / $total) * 100, 2);
        
        if ($percentUsed > 90) {
            $status = 'critical';
            $this->alerts[] = "Disk space critically low";
        } elseif ($percentUsed > 80) {
            $status = 'warning';
        } else {
            $status = 'healthy';
        }
        
        $this->checks['disk_space'] = [
            'status' => $status,
            'message' => "$percentUsed% usado (" . $this->formatBytes($used) . " / " . $this->formatBytes($total) . ")",
            'percent_used' => $percentUsed,
            'timestamp' => date('H:i:s')
        ];
    }
    
    private function checkMemoryUsage() {
        $memUsage = memory_get_usage(true);
        $memPeak = memory_get_peak_usage(true);
        $memLimit = ini_get('memory_limit');
        
        $this->checks['memory'] = [
            'status' => 'healthy',
            'message' => 'Uso atual: ' . $this->formatBytes($memUsage),
            'current' => $memUsage,
            'peak' => $memPeak,
            'limit' => $memLimit,
            'timestamp' => date('H:i:s')
        ];
    }
    
    private function checkProcessStatus() {
        // Verificar se processos críticos estão rodando
        $webserver = $this->getWebServerInfo();
        
        $this->checks['processes'] = [
            'status' => 'healthy',
            'message' => "Web server: $webserver",
            'timestamp' => date('H:i:s')
        ];
    }
    
    private function checkLogFileSize() {
        $logPath = 'logs/';
        $logFiles = glob($logPath . "*.log");
        $totalSize = 0;
        
        foreach ($logFiles as $file) {
            $totalSize += filesize($file);
        }
        
        $status = $totalSize > 100 * 1024 * 1024 ? 'warning' : 'healthy'; // 100MB
        
        $this->checks['logs'] = [
            'status' => $status,
            'message' => 'Tamanho dos logs: ' . $this->formatBytes($totalSize),
            'total_size' => $totalSize,
            'files_count' => count($logFiles),
            'timestamp' => date('H:i:s')
        ];
        
        if ($status === 'warning') {
            $this->alerts[] = "Log files growing large";
        }
    }
    
    private function checkDeployStatus() {
        $deployInfo = DeployInfo::getAllInfo();
        
        $this->checks['deploy'] = [
            'status' => 'healthy',
            'version' => $deployInfo['version'],
            'last_deploy' => $deployInfo['deploy_date'],
            'environment' => $deployInfo['environment'],
            'timestamp' => date('H:i:s')
        ];
    }
    
    private function saveStatus() {
        if (!file_exists('logs')) {
            mkdir('logs', 0755, true);
        }
        
        file_put_contents(
            $this->statusFile,
            json_encode($this->checks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }
    
    private function sendAlerts() {
        if (empty($this->alerts)) {
            return;
        }
        
        $message = "🚨 Alertas do sistema CFC Bom Conselho:\n" . implode("\n", array_unique($this->alerts));
        
        // Log dos alertas
        error_log("[SYSTEM_ALERT] " . $message);
        
        // Aqui você pode adicionar notificações por email/SMS/webhook
        // $this->sendEmailAlert($message);
        // $this->sendSMSAlert($message);
        // $this->sendWebhookAlert($message);
    }
    
    private function getWebServerInfo() {
        if (function_exists('apache_get_version')) {
            return apache_get_version();
        } elseif (isset($_SERVER['SERVER_SOFTWARE'])) {
            return $_SERVER['SERVER_SOFTWARE'];
        } else {
            return 'Unknown';
        }
    }
    
    private function formatBytes($size, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $size >= 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, $precision) . ' ' . $units[$i];
    }
}

// Execução do monitoramento
$monitor = new SystemMonitor();

if (isset($_GET['api'])) {
    header('Content-Type: application/json');
    echo json_encode($monitor->runHealthCheck());
    exit;
}

$status = $monitor->runHealthCheck();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔍 Status do Sistema - CFC Bom Conselho</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .status-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; text-align: center; }
        .status-badge { display: inline-block; padding: 5px 15px; border-radius: 20px; font-weight: bold; }
        .healthy { background: #d4edda; color: #155724; }
        .warning { background: #fff3cd; color: #856404; }
        .critical { background: #f8d7da; color: #721c24; }
        .check-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; margin: 20px 0; }
        .check-card { background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 5px solid; }
        .check-card.healthy { border-left-color: #28a745; }
        .check-card.warning { border-left-color: #ffc107; }
        .check-card.critical { border-left-color: #dc3545; }
        .check-title { font-weight: bold; margin-bottom: 10px; }
        .check-message { margin: 5px 0; }
        .check-time { font-size: 0.9em; color: #666; }
        .refresh-btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 10px 0; }
        .refresh-btn:hover { background: #0056b3; }
    </style>
    <script>
        function refreshStatus() {
            location.reload();
        }
        
        // Auto-refresh a cada 30 segundos
        setTimeout(refreshStatus, 30000);
    </script>
</head>
<body>
    <div class="container">
        <div class="status-header">
            <h1>🔍 Monitoramento do Sistema</h1>
            <h2>CFC Bom Conselho</h2>
            <div class="status-badge <?= $status['overall_status'] ?>">
                Status Geral: <?= strtoupper($status['overall_status']) ?>
            </div>
            <p>Última verificação: <?= $status['timestamp'] ?></p>
        </div>
        
        <div class="check-grid">
            <?php foreach ($status as $checkName => $checkData): 
                if ($checkName === 'timestamp' || $checkName === 'overall_status') continue; ?>
                <div class="check-card <?= $checkData['status'] ?>">
            
                    <div class="check-title">
                        <?php 
                        $icons = [
                            'database' => '🗄️',
                            'file_permissions' => '📁', 
                            'disk_space' => '💾',
                            'memory' => '🧠',
                            'processes' => '⚙️',
                            'logs' => '📝',
                            'deploy' => '🚀'
                        ];
                        echo $icons[$checkName] ?? '🔍';
                        ?> 
                        <?= ucfirst(str_replace('_', ' ', $checkName)) ?>
                    </div>
                    
                    <div class="check-message">
                        <strong>Status:</strong> 
                        <span class="status-badge <?= $checkData['status'] ?>">
                            <?= strtoupper($checkData['status']) ?>
                        </span>
                    </div>
                    
                    <div class="check-message">
                        <?= $checkData['message'] ?>
                    </div>
                    
                    <?php if (isset($checkData['details'])): ?>
                        <?php foreach ($checkData['details'] as $key => $value): ?>
                            <div class="check-message"><strong><?= $key ?>:</strong> <?= $value ?></div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <div class="check-time">
                        Verificado às: <?= $checkData['timestamp'] ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <button class="refresh-btn" onclick="refreshStatus()">🔄 Atualizar Status</button>
        
        <?php if (!empty($alerts)): ?>
        <div class="alerts" style="background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3>🚨 Alertas Ativos:</h3>
            <ul>
                <?php foreach ($alerts as $alert): ?>
                    <li><?= $alert ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
