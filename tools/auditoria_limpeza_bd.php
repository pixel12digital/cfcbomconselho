<?php
/**
 * Script de Auditoria do Banco de Dados - Antes da Limpeza
 * 
 * Objetivo: Fazer uma auditoria completa do banco atual para planejar
 * uma limpeza "produção-ready" com risco mínimo.
 * 
 * NÃO executa DELETE - apenas auditoria e relatório.
 * 
 * Uso: php tools/auditoria_limpeza_bd.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

require_once APP_PATH . '/autoload.php';

use App\Config\Database;
use App\Config\Env;

// Carregar variáveis de ambiente
Env::load();

echo "========================================\n";
echo "AUDITORIA DO BANCO DE DADOS\n";
echo "========================================\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // 1. Listar todas as tabelas
    echo "1. Listando tabelas...\n";
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);
    echo "   Encontradas " . count($tables) . " tabelas\n\n";
    
    // 2. Contar registros e classificar
    echo "2. Contando registros e classificando tabelas...\n\n";
    
    $audit = [];
    
    // Tabelas que devem PERMANECER (KEEP)
    $keepTables = [
        'cfcs' => 'Configurações do CFC',
        'theory_disciplines' => 'Disciplinas teóricas (exemplo)',
        'theory_courses' => 'Cursos teóricos (exemplo)',
        'theory_course_disciplines' => 'Relação curso-disciplinas (configuração)',
        'services' => 'Serviços (podem permanecer como exemplo)',
        'states' => 'Estrutura: Estados',
        'cities' => 'Estrutura: Cidades',
        'settings' => 'Configurações do sistema',
        'migrations' => 'Histórico de migrations',
        'steps' => 'Catálogo de etapas (estrutura)',
        'permissoes' => 'Permissões do sistema (estrutura)',
        'roles' => 'Papéis do sistema (estrutura)',
        'role_permissoes' => 'Relação roles-permissões (estrutura)',
        'smtp_settings' => 'Configurações SMTP',
    ];
    
    // Tabelas que devem ser ZERADAS (DELETE)
    $deletePatterns = [
        'alunos' => 'Alunos',
        'students' => 'Alunos',
        'enrollments' => 'Matrículas',
        'student_history' => 'Histórico de alunos',
        'student_steps' => 'Etapas de alunos',
        'lessons' => 'Aulas/Agendamentos',
        'agenda' => 'Agenda',
        'instructors' => 'Instrutores',
        'instructor_availability' => 'Disponibilidade de instrutores',
        'vehicles' => 'Veículos',
        'payments' => 'Pagamentos',
        'charges' => 'Cobranças',
        'installments' => 'Parcelas',
        'notifications' => 'Notificações/Comunicados',
        'broadcast_notifications' => 'Comunicados',
        'reschedule_requests' => 'Solicitações de remarcação',
        'theory_enrollments' => 'Matrículas teóricas',
        'theory_attendance' => 'Presenças teóricas',
        'theory_classes' => 'Aulas teóricas',
        'theory_sessions' => 'Sessões teóricas',
        'password_reset_tokens' => 'Tokens de reset',
        'account_activation_tokens' => 'Tokens de ativação',
        'user_recent_financial_queries' => 'Logs de consultas financeiras',
    ];
    
    // Tabelas que precisam REVISÃO (REVIEW)
    $reviewTables = [
        'usuarios' => 'Manter apenas ADMIN, deletar demais',
        'usuario_roles' => 'Manter apenas roles do ADMIN, deletar demais',
        'auditoria' => 'Logs de auditoria - verificar se deve manter ou deletar',
    ];
    
    // 3. Para cada tabela, contar registros
    foreach ($tables as $table) {
        $stmt = $db->query("SELECT COUNT(*) as count FROM `{$table}`");
        $result = $stmt->fetch();
        $count = (int)$result['count'];
        
        // Classificar
        $classification = 'REVIEW';
        $observation = '';
        
        if (isset($keepTables[$table])) {
            $classification = 'KEEP';
            $observation = $keepTables[$table];
        } elseif (isset($reviewTables[$table])) {
            $classification = 'REVIEW';
            $observation = $reviewTables[$table];
        } else {
            // Verificar por padrão
            $matched = false;
            foreach ($deletePatterns as $pattern => $desc) {
                if (stripos($table, $pattern) !== false) {
                    $classification = 'DELETE';
                    $observation = $desc;
                    $matched = true;
                    break;
                }
            }
            
            if (!$matched) {
                // Verificar se referencia aluno/financeiro por FK
                $stmt = $db->query("
                    SELECT 
                        COLUMN_NAME,
                        REFERENCED_TABLE_NAME,
                        REFERENCED_COLUMN_NAME
                    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = '{$table}'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                $fks = $stmt->fetchAll();
                
                foreach ($fks as $fk) {
                    $refTable = $fk['REFERENCED_TABLE_NAME'];
                    if (in_array($refTable, ['students', 'alunos', 'enrollments', 'payments', 'charges', 'instructors', 'vehicles'])) {
                        $classification = 'DELETE';
                        $observation = "Referencia {$refTable}";
                        break;
                    }
                }
                
                if ($classification === 'REVIEW' && $count > 0) {
                    $observation = 'Verificar manualmente';
                }
            }
        }
        
        $audit[] = [
            'table' => $table,
            'count' => $count,
            'classification' => $classification,
            'observation' => $observation
        ];
    }
    
    // 4. Identificar Foreign Keys e dependências
    echo "3. Mapeando dependências (Foreign Keys)...\n\n";
    
    $dependencies = [];
    foreach ($tables as $table) {
        $stmt = $db->query("
            SELECT 
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = '{$table}'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        $fks = $stmt->fetchAll();
        
        if (!empty($fks)) {
            $dependencies[$table] = [];
            foreach ($fks as $fk) {
                $dependencies[$table][] = [
                    'column' => $fk['COLUMN_NAME'],
                    'references' => $fk['REFERENCED_TABLE_NAME'],
                    'ref_column' => $fk['REFERENCED_COLUMN_NAME']
                ];
            }
        }
    }
    
    // 5. Verificar arquivos em storage/uploads
    echo "4. Verificando arquivos em storage/uploads...\n\n";
    
    $uploadFiles = [
        'cfcs' => [],
        'students' => [],
        'instructors' => [],
        'vehicles' => []
    ];
    
    $uploadBase = ROOT_PATH . '/storage/uploads';
    
    foreach (['cfcs', 'students', 'instructors', 'vehicles'] as $folder) {
        $folderPath = $uploadBase . '/' . $folder;
        if (is_dir($folderPath)) {
            $files = glob($folderPath . '/*');
            $uploadFiles[$folder] = array_map('basename', $files);
        }
    }
    
    // 6. Gerar relatório
    echo "========================================\n";
    echo "RELATÓRIO DE AUDITORIA\n";
    echo "========================================\n\n";
    
    // Agrupar por classificação
    $grouped = ['KEEP' => [], 'DELETE' => [], 'REVIEW' => []];
    foreach ($audit as $item) {
        $grouped[$item['classification']][] = $item;
    }
    
    // Ordenar por contagem (maior primeiro)
    foreach ($grouped as $key => $items) {
        usort($grouped[$key], function($a, $b) {
            return $b['count'] - $a['count'];
        });
    }
    
    // Exibir resumo
    echo "RESUMO POR CLASSIFICAÇÃO:\n";
    echo "  KEEP:   " . count($grouped['KEEP']) . " tabelas\n";
    echo "  DELETE: " . count($grouped['DELETE']) . " tabelas\n";
    echo "  REVIEW: " . count($grouped['REVIEW']) . " tabelas\n\n";
    
    // Tabela detalhada
    echo "DETALHAMENTO POR TABELA:\n";
    echo str_repeat('=', 100) . "\n";
    printf("%-30s | %10s | %-8s | %s\n", "TABELA", "REGISTROS", "AÇÃO", "OBSERVAÇÃO");
    echo str_repeat('-', 100) . "\n";
    
    foreach ($audit as $item) {
        printf("%-30s | %10d | %-8s | %s\n", 
            $item['table'], 
            $item['count'], 
            $item['classification'],
            $item['observation']
        );
    }
    
    echo "\n";
    
    // Dependências críticas
    echo "DEPENDÊNCIAS CRÍTICAS (para ordem de exclusão):\n";
    echo str_repeat('=', 100) . "\n";
    
    $deleteTables = array_filter($audit, function($item) {
        return $item['classification'] === 'DELETE' && $item['count'] > 0;
    });
    
    foreach ($deleteTables as $item) {
        $table = $item['table'];
        if (isset($dependencies[$table])) {
            echo "\n{$table} depende de:\n";
            foreach ($dependencies[$table] as $dep) {
                echo "  - {$dep['column']} -> {$dep['references']}.{$dep['ref_column']}\n";
            }
        }
    }
    
    echo "\n";
    
    // Arquivos em storage
    echo "ARQUIVOS EM storage/uploads:\n";
    echo str_repeat('=', 100) . "\n";
    foreach ($uploadFiles as $folder => $files) {
        $count = count($files);
        $action = ($folder === 'cfcs') ? 'MANTER' : 'DELETAR';
        echo "\n{$folder}/: {$count} arquivo(s) - {$action}\n";
        if ($count > 0 && $count <= 10) {
            foreach ($files as $file) {
                echo "  - {$file}\n";
            }
        } elseif ($count > 10) {
            echo "  (muitos arquivos, listar apenas primeiros 5)\n";
            foreach (array_slice($files, 0, 5) as $file) {
                echo "  - {$file}\n";
            }
        }
    }
    
    echo "\n";
    
    // Salvar relatório completo em arquivo
    $reportFile = ROOT_PATH . '/.docs/AUDITORIA_LIMPEZA_BD.md';
    $reportDir = dirname($reportFile);
    if (!is_dir($reportDir)) {
        mkdir($reportDir, 0755, true);
    }
    
    $report = "# Auditoria do Banco de Dados - Antes da Limpeza\n\n";
    $report .= "**Data:** " . date('Y-m-d H:i:s') . "\n\n";
    $report .= "## Resumo\n\n";
    $report .= "- **KEEP:** " . count($grouped['KEEP']) . " tabelas\n";
    $report .= "- **DELETE:** " . count($grouped['DELETE']) . " tabelas\n";
    $report .= "- **REVIEW:** " . count($grouped['REVIEW']) . " tabelas\n\n";
    
    $report .= "## Tabelas por Classificação\n\n";
    
    foreach (['KEEP', 'DELETE', 'REVIEW'] as $class) {
        if (empty($grouped[$class])) continue;
        
        $report .= "### {$class}\n\n";
        $report .= "| Tabela | Registros | Observação |\n";
        $report .= "|--------|-----------|------------|\n";
        
        foreach ($grouped[$class] as $item) {
            $report .= "| `{$item['table']}` | {$item['count']} | {$item['observation']} |\n";
        }
        
        $report .= "\n";
    }
    
    $report .= "## Dependências (Foreign Keys)\n\n";
    foreach ($deleteTables as $item) {
        $table = $item['table'];
        if (isset($dependencies[$table])) {
            $report .= "### {$table}\n\n";
            foreach ($dependencies[$table] as $dep) {
                $report .= "- `{$dep['column']}` → `{$dep['references']}.{$dep['ref_column']}`\n";
            }
            $report .= "\n";
        }
    }
    
    $report .= "## Arquivos em storage/uploads\n\n";
    foreach ($uploadFiles as $folder => $files) {
        $count = count($files);
        $action = ($folder === 'cfcs') ? 'MANTER' : 'DELETAR';
        $report .= "### {$folder}/ ({$count} arquivos - {$action})\n\n";
        if ($count > 0) {
            foreach ($files as $file) {
                $report .= "- `{$file}`\n";
            }
        }
        $report .= "\n";
    }
    
    $report .= "## Ordem Sugerida de Exclusão\n\n";
    $report .= "Baseado nas dependências, a ordem segura seria:\n\n";
    $report .= "1. Tabelas filhas (que referenciam outras)\n";
    $report .= "2. Tabelas pai (referenciadas)\n";
    $report .= "3. Resetar AUTO_INCREMENT das tabelas limpas\n\n";
    
    file_put_contents($reportFile, $report);
    
    echo "✅ Relatório completo salvo em: .docs/AUDITORIA_LIMPEZA_BD.md\n\n";
    
    // Estatísticas finais
    $totalRecords = array_sum(array_column($audit, 'count'));
    $keepRecords = array_sum(array_column($grouped['KEEP'], 'count'));
    $deleteRecords = array_sum(array_column($grouped['DELETE'], 'count'));
    $reviewRecords = array_sum(array_column($grouped['REVIEW'], 'count'));
    
    echo "ESTATÍSTICAS:\n";
    echo str_repeat('=', 100) . "\n";
    echo "Total de registros: " . number_format($totalRecords, 0, ',', '.') . "\n";
    echo "  - KEEP:   " . number_format($keepRecords, 0, ',', '.') . " registros\n";
    echo "  - DELETE: " . number_format($deleteRecords, 0, ',', '.') . " registros\n";
    echo "  - REVIEW: " . number_format($reviewRecords, 0, ',', '.') . " registros\n";
    
    echo "\n✅ Auditoria concluída!\n";
    
} catch (\Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
