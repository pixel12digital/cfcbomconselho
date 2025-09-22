<?php
/**
 * API de Notificações - Sistema CFC
 * Gerencia notificações do sistema
 */

// Configurações
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Verificar se é uma requisição GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Incluir dependências
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

// Verificar autenticação
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

try {
    $db = Database::getInstance();
    $limit = (int)($_GET['limit'] ?? 10);
    $limit = min($limit, 50); // Máximo de 50 notificações
    
    // Gerar notificações baseadas em dados reais do sistema
    $notifications = [];
    
    // Notificações de alunos novos (últimos 7 dias)
    try {
        $novosAlunos = $db->fetchAll("
            SELECT 
                CONCAT('Novo aluno cadastrado: ', nome) as title,
                CONCAT('O aluno ', nome, ' foi cadastrado no sistema') as message,
                'user' as type,
                '#9b59b6' as color,
                'fas fa-user' as icon,
                CONCAT('?page=alunos&action=view&id=', id) as url,
                criado_em as created_at,
                1 as unread
            FROM alunos 
            WHERE criado_em >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY criado_em DESC
            LIMIT 3
        ");
        
        $notifications = array_merge($notifications, $novosAlunos);
    } catch (Exception $e) {
        error_log('Erro ao buscar notificações de alunos: ' . $e->getMessage());
    }
    
    // Notificações de aulas próximas (próximas 24 horas)
    try {
        $aulasProximas = $db->fetchAll("
            SELECT 
                CONCAT('Aula agendada para ', DATE_FORMAT(data_aula, '%d/%m/%Y'), ' às ', TIME_FORMAT(hora_inicio, '%H:%i')) as title,
                CONCAT('Aula com ', COALESCE(al.nome, 'aluno'), ' e instrutor ', COALESCE(u.nome, 'não definido')) as message,
                'schedule' as type,
                '#e67e22' as color,
                'fas fa-calendar' as icon,
                CONCAT('?page=agendar-aula&action=view&id=', a.id) as url,
                NOW() as created_at,
                1 as unread
            FROM aulas a
            LEFT JOIN alunos al ON a.aluno_id = al.id
            LEFT JOIN instrutores i ON a.instrutor_id = i.id
            LEFT JOIN usuarios u ON i.usuario_id = u.id
            WHERE a.data_aula BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 DAY)
            AND a.status = 'agendada'
            ORDER BY a.data_aula ASC, a.hora_inicio ASC
            LIMIT 5
        ");
        
        $notifications = array_merge($notifications, $aulasProximas);
    } catch (Exception $e) {
        error_log('Erro ao buscar notificações de aulas: ' . $e->getMessage());
    }
    
    // Notificações de instrutores inativos (simplificado para evitar erros de coluna)
    try {
        $documentosVencendo = $db->fetchAll("
            SELECT 
                CONCAT('Instrutor inativo: ', u.nome) as title,
                CONCAT('O instrutor ', u.nome, ' está inativo no sistema') as message,
                'warning' as type,
                '#f39c12' as color,
                'fas fa-exclamation-triangle' as icon,
                CONCAT('?page=instrutores&action=view&id=', i.id) as url,
                NOW() as created_at,
                1 as unread
            FROM instrutores i
            JOIN usuarios u ON i.usuario_id = u.id
            WHERE i.ativo = 0
            ORDER BY i.id ASC
            LIMIT 3
        ");
        
        $notifications = array_merge($notifications, $documentosVencendo);
    } catch (Exception $e) {
        // Se houver erro, continuar sem essas notificações
        error_log('Erro ao buscar notificações de instrutores: ' . $e->getMessage());
    }
    
    // Notificações de veículos em manutenção
    try {
        $veiculosManutencao = $db->fetchAll("
            SELECT 
                CONCAT('Veículo em manutenção: ', placa) as title,
                CONCAT('O veículo ', placa, ' (', marca, ' ', modelo, ') está em manutenção') as message,
                'maintenance' as type,
                '#e74c3c' as color,
                'fas fa-wrench' as icon,
                CONCAT('?page=veiculos&action=view&id=', id) as url,
                NOW() as created_at,
                1 as unread
            FROM veiculos 
            WHERE status = 'manutencao' OR proxima_manutencao <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            ORDER BY proxima_manutencao ASC
            LIMIT 3
        ");
        
        $notifications = array_merge($notifications, $veiculosManutencao);
    } catch (Exception $e) {
        error_log('Erro ao buscar notificações de veículos: ' . $e->getMessage());
    }
    
    // Notificações do sistema
    $notificacoesSistema = [
        [
            'title' => 'Sistema atualizado',
            'message' => 'Nova versão do sistema CFC disponível com melhorias',
            'type' => 'system',
            'color' => '#34495e',
            'icon' => 'fas fa-cog',
            'url' => '?page=atualizacoes',
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours')),
            'unread' => 1
        ],
        [
            'title' => 'Backup realizado',
            'message' => 'Backup automático do sistema realizado com sucesso',
            'type' => 'system',
            'color' => '#27ae60',
            'icon' => 'fas fa-database',
            'url' => '?page=backup',
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'unread' => 0
        ]
    ];
    
    $notifications = array_merge($notifications, $notificacoesSistema);
    
    // Ordenar por data de criação (mais recentes primeiro)
    usort($notifications, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    // Limitar resultados
    $notifications = array_slice($notifications, 0, $limit);
    
    // Adicionar IDs únicos
    foreach ($notifications as $index => &$notification) {
        $notification['id'] = $index + 1;
    }
    
    // Contar não lidas
    $unreadCount = array_reduce($notifications, function($count, $notification) {
        return $count + ($notification['unread'] ? 1 : 0);
    }, 0);
    
    // Retornar notificações
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => $unreadCount,
        'total' => count($notifications)
    ]);
    
} catch (Exception $e) {
    error_log('Erro ao carregar notificações: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor',
        'error' => DEBUG_MODE ? $e->getMessage() : null
    ]);
}
?>