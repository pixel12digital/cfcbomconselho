<?php
// =====================================================
// DASHBOARD PARA ALUNOS - SISTEMA CFC
// =====================================================

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

// Verificar se está logado como aluno
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = getCurrentUser();
if (!$user || $user['tipo'] !== 'aluno') {
    header('Location: login.php');
    exit;
}

$aluno_id = $user['id'];
$db = db();

// Buscar dados do aluno na tabela usuarios
$aluno = $db->fetch("SELECT * FROM usuarios WHERE id = ? AND tipo = 'aluno'", [$aluno_id]);

if (!$aluno) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Buscar aulas do aluno
$aulas = $db->fetchAll("
    SELECT a.*, i.nome as instrutor_nome, v.modelo as veiculo_modelo, v.placa as veiculo_placa
    FROM aulas a
    LEFT JOIN instrutores i ON a.instrutor_id = i.id
    LEFT JOIN veiculos v ON a.veiculo_id = v.id
    WHERE a.aluno_id = ?
    ORDER BY a.data_aula DESC, a.hora_inicio DESC
    LIMIT 10
", [$aluno_id]);

// Estatísticas do aluno
$stats = [
    'total_aulas' => $db->count('aulas', 'aluno_id = ?', [$aluno_id]),
    'aulas_realizadas' => $db->count('aulas', 'aluno_id = ? AND status = ?', [$aluno_id, 'realizada']),
    'aulas_pendentes' => $db->count('aulas', 'aluno_id = ? AND status = ?', [$aluno_id, 'agendada']),
    'aulas_canceladas' => $db->count('aulas', 'aluno_id = ? AND status = ?', [$aluno_id, 'cancelada'])
];

// Próximas aulas
$proximas_aulas = $db->fetchAll("
    SELECT a.*, i.nome as instrutor_nome, v.modelo as veiculo_modelo
    FROM aulas a
    LEFT JOIN instrutores i ON a.instrutor_id = i.id
    LEFT JOIN veiculos v ON a.veiculo_id = v.id
    WHERE a.aluno_id = ? AND a.data_aula >= CURDATE() AND a.status = 'agendada'
    ORDER BY a.data_aula ASC, a.hora_inicio ASC
    LIMIT 5
", [$aluno_id]);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | <?php echo htmlspecialchars($aluno['nome']); ?></title>
    <link rel="stylesheet" href="../assets/css/login.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            color: #333;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 24px;
            font-weight: 600;
        }
        
        .header-info {
            text-align: right;
        }
        
        .header-info p {
            margin: 2px 0;
            opacity: 0.9;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.3s;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        
        .welcome-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .welcome-card h2 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .welcome-card p {
            color: #666;
            font-size: 16px;
            line-height: 1.6;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 8px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .card h3 {
            color: #333;
            margin-bottom: 20px;
            font-size: 20px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }
        
        .aula-item {
            padding: 15px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 10px;
            transition: all 0.3s;
        }
        
        .aula-item:hover {
            border-color: #667eea;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.1);
        }
        
        .aula-data {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .aula-info {
            color: #666;
            font-size: 14px;
            margin: 2px 0;
        }
        
        .aula-status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-agendada {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .status-realizada {
            background: #e8f5e8;
            color: #2e7d32;
        }
        
        .status-cancelada {
            background: #ffebee;
            color: #c62828;
        }
        
        .empty-state {
            text-align: center;
            color: #666;
            padding: 40px 20px;
        }
        
        .empty-state i {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 15px;
        }
        
        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .header-content {
                flex-direction: column;
                gap: 15px;
            }
            
            .header-info {
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>🎓 Painel do Aluno</h1>
            <div class="header-info">
                <p><strong><?php echo htmlspecialchars($aluno['nome']); ?></strong></p>
                <p>CPF: <?php echo htmlspecialchars($aluno['cpf']); ?></p>
                <a href="logout.php" class="logout-btn">Sair</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="welcome-card">
            <h2>Bem-vindo, <?php echo htmlspecialchars($aluno['nome']); ?>!</h2>
            <p>
                Aqui você pode acompanhar suas aulas, verificar seu progresso e 
                visualizar informações sobre seu curso de habilitação.
            </p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_aulas']; ?></div>
                <div class="stat-label">Total de Aulas</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['aulas_realizadas']; ?></div>
                <div class="stat-label">Aulas Realizadas</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['aulas_pendentes']; ?></div>
                <div class="stat-label">Aulas Pendentes</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['aulas_canceladas']; ?></div>
                <div class="stat-label">Aulas Canceladas</div>
            </div>
        </div>
        
        <div class="content-grid">
            <div class="card">
                <h3>📅 Próximas Aulas</h3>
                <?php if (!empty($proximas_aulas)): ?>
                    <?php foreach ($proximas_aulas as $aula): ?>
                        <div class="aula-item">
                            <div class="aula-data">
                                <?php echo date('d/m/Y', strtotime($aula['data_aula'])); ?> 
                                às <?php echo date('H:i', strtotime($aula['hora_inicio'])); ?>
                            </div>
                            <div class="aula-info">
                                <strong>Tipo:</strong> <?php echo htmlspecialchars($aula['tipo_aula']); ?>
                            </div>
                            <div class="aula-info">
                                <strong>Instrutor:</strong> <?php echo htmlspecialchars($aula['instrutor_nome'] ?? 'Não definido'); ?>
                            </div>
                            <div class="aula-info">
                                <strong>Veículo:</strong> <?php echo htmlspecialchars($aula['veiculo_modelo'] ?? 'Não definido'); ?>
                            </div>
                            <div class="aula-info">
                                <span class="aula-status status-agendada">Agendada</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i>📅</i>
                        <p>Nenhuma aula agendada</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h3>📋 Histórico Recente</h3>
                <?php if (!empty($aulas)): ?>
                    <?php foreach (array_slice($aulas, 0, 5) as $aula): ?>
                        <div class="aula-item">
                            <div class="aula-data">
                                <?php echo date('d/m/Y', strtotime($aula['data_aula'])); ?> 
                                às <?php echo date('H:i', strtotime($aula['hora_inicio'])); ?>
                            </div>
                            <div class="aula-info">
                                <strong>Tipo:</strong> <?php echo htmlspecialchars($aula['tipo_aula']); ?>
                            </div>
                            <div class="aula-info">
                                <strong>Instrutor:</strong> <?php echo htmlspecialchars($aula['instrutor_nome'] ?? 'Não definido'); ?>
                            </div>
                            <div class="aula-info">
                                <span class="aula-status status-<?php echo $aula['status']; ?>">
                                    <?php echo ucfirst($aula['status']); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i>📋</i>
                        <p>Nenhuma aula registrada</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
