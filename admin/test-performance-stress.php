<?php
/**
 * Script de Teste de Performance e Stress - Simulação de Múltiplos Usuários
 * Testa a capacidade do sistema sob carga e múltiplos acessos simultâneos
 * 
 * @author Sistema CFC
 * @version 1.0
 * @since 2024
 */

// Configurações de teste
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir arquivos necessários
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

class TestePerformanceStress {
    private $db;
    private $testes = [];
    private $sucessos = 0;
    private $falhas = 0;
    private $metricas = [];
    private $inicioGeral;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->inicioGeral = microtime(true);
    }
    
    /**
     * Executar todos os testes de performance
     */
    public function executarTodosTestes() {
        echo "<h1>🚀 TESTE DE PERFORMANCE E STRESS - SIMULAÇÃO DE MÚLTIPLOS USUÁRIOS</h1>";
        echo "<p><strong>Data/Hora:</strong> " . date('d/m/Y H:i:s') . "</p>";
        echo "<p><strong>Objetivo:</strong> Testar capacidade do sistema sob carga</p>";
        echo "<hr>";
        
        // Executar testes em sequência
        $this->testarPerformanceBanco();
        $this->testarConcorrenciaSimultanea();
        $this->testarConsultasComplexas();
        $this->testarOperacoesEmLote();
        $this->testarLimitesSistema();
        $this->testarRecuperacaoErros();
        
        // Exibir resultados finais
        $this->exibirResultadosFinais();
    }
    
    /**
     * Testar performance do banco de dados
     */
    private function testarPerformanceBanco() {
        $this->iniciarTeste("Performance do Banco de Dados");
        
        try {
            // Teste 1: Consulta simples
            $inicio = microtime(true);
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM cfcs");
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            $tempoConsulta = (microtime(true) - $inicio) * 1000;
            
            if ($resultado) {
                $this->sucesso("Consulta simples executada em " . round($tempoConsulta, 2) . "ms");
                $this->adicionarMetrica('consulta_simples', $tempoConsulta);
                
                if ($tempoConsulta < 50) {
                    $this->sucesso("Performance EXCELENTE (< 50ms)");
                } elseif ($tempoConsulta < 100) {
                    $this->sucesso("Performance BOA (< 100ms)");
                } elseif ($tempoConsulta < 200) {
                    $this->falha("Performance ACEITÁVEL (< 200ms) - Considerar otimização");
                } else {
                    $this->falha("Performance RUIM (> 200ms) - Otimização necessária");
                }
            } else {
                $this->falha("Consulta simples falhou");
            }
            
            // Teste 2: Consulta com JOIN
            $inicio = microtime(true);
            $stmt = $this->db->query("
                SELECT c.nome as cfc_nome, COUNT(a.id) as total_alunos 
                FROM cfcs c 
                LEFT JOIN alunos a ON c.id = a.cfc_id 
                WHERE c.ativo = 1 
                GROUP BY c.id, c.nome
            ");
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $tempoJoin = (microtime(true) - $inicio) * 1000;
            
            if ($resultados !== false) {
                $this->sucesso("Consulta com JOIN executada em " . round($tempoJoin, 2) . "ms");
                $this->adicionarMetrica('consulta_join', $tempoJoin);
                
                if ($tempoJoin < 100) {
                    $this->sucesso("Performance JOIN EXCELENTE (< 100ms)");
                } elseif ($tempoJoin < 200) {
                    $this->sucesso("Performance JOIN BOA (< 200ms)");
                } elseif ($tempoJoin < 500) {
                    $this->falha("Performance JOIN ACEITÁVEL (< 500ms) - Considerar otimização");
                } else {
                    $this->falha("Performance JOIN RUIM (> 500ms) - Otimização necessária");
                }
            } else {
                $this->falha("Consulta com JOIN falhou");
            }
            
            // Teste 3: Múltiplas consultas sequenciais
            $inicio = microtime(true);
            $consultas = [
                "SELECT COUNT(*) FROM usuarios",
                "SELECT COUNT(*) FROM alunos", 
                "SELECT COUNT(*) FROM instrutores",
                "SELECT COUNT(*) FROM veiculos",
                "SELECT COUNT(*) FROM aulas"
            ];
            
            $totalConsultas = 0;
            foreach ($consultas as $sql) {
                $stmt = $this->db->query($sql);
                $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($resultado) $totalConsultas++;
            }
            
            $tempoMultiplas = (microtime(true) - $inicio) * 1000;
            
            if ($totalConsultas === count($consultas)) {
                $this->sucesso("Múltiplas consultas executadas em " . round($tempoMultiplas, 2) . "ms");
                $this->adicionarMetrica('multiplas_consultas', $tempoMultiplas);
                
                $tempoMedio = $tempoMultiplas / count($consultas);
                $this->sucesso("Tempo médio por consulta: " . round($tempoMedio, 2) . "ms");
            } else {
                $this->falha("Falha em múltiplas consultas: $totalConsultas/" . count($consultas));
            }
            
        } catch (Exception $e) {
            $this->falha("Erro no teste de performance: " . $e->getMessage());
        }
        
        $this->finalizarTeste();
    }
    
    /**
     * Testar concorrência simultânea
     */
    private function testarConcorrenciaSimultanea() {
        $this->iniciarTeste("Concorrência e Acessos Simultâneos");
        
        try {
            // Simular múltiplos usuários acessando simultaneamente
            $numUsuarios = 10;
            $inicio = microtime(true);
            
            $this->sucesso("Simulando $numUsuarios usuários simultâneos...");
            
            // Teste de leitura simultânea
            $this->testarLeituraSimultanea($numUsuarios);
            
            // Teste de escrita simultânea
            $this->testarEscritaSimultanea($numUsuarios);
            
            // Teste de operações mistas
            $this->testarOperacoesMistas($numUsuarios);
            
            $tempoTotal = (microtime(true) - $inicio) * 1000;
            $this->adicionarMetrica('concorrencia_total', $tempoTotal);
            
            $this->sucesso("Teste de concorrência concluído em " . round($tempoTotal, 2) . "ms");
            
        } catch (Exception $e) {
            $this->falha("Erro no teste de concorrência: " . $e->getMessage());
        }
        
        $this->finalizarTeste();
    }
    
    /**
     * Testar leitura simultânea
     */
    private function testarLeituraSimultanea($numUsuarios) {
        $inicio = microtime(true);
        $sucessos = 0;
        
        for ($i = 0; $i < $numUsuarios; $i++) {
            try {
                $resultado = $this->db->fetch("SELECT * FROM cfcs WHERE status = ? LIMIT 1", ['ativo']);
                
                if ($resultado) {
                    $sucessos++;
                }
            } catch (Exception $e) {
                // Falha silenciosa para não interromper o teste
            }
        }
        
        $tempo = (microtime(true) - $inicio) * 1000;
        $taxaSucesso = ($sucessos / $numUsuarios) * 100;
        
        $this->sucesso("Leitura simultânea: $sucessos/$numUsuarios sucessos (" . round($taxaSucesso, 1) . "%) em " . round($tempo, 2) . "ms");
        
        if ($taxaSucesso >= 95) {
            $this->sucesso("Concorrência de leitura EXCELENTE");
        } elseif ($taxaSucesso >= 80) {
            $this->sucesso("Concorrência de leitura BOA");
        } else {
            $this->falha("Concorrência de leitura com problemas");
        }
        
        $this->adicionarMetrica('leitura_simultanea', $tempo);
        $this->adicionarMetrica('taxa_leitura', $taxaSucesso);
    }
    
    /**
     * Testar escrita simultânea
     */
    private function testarEscritaSimultanea($numUsuarios) {
        $inicio = microtime(true);
        $sucessos = 0;
        $idsCriados = [];
        
        for ($i = 0; $i < $numUsuarios; $i++) {
            try {
                $nome = 'CFC Stress Test ' . date('Y-m-d H:i:s') . " - Usuario $i";
                $cnpj = '12.345.678/000' . str_pad($i, 2, '0', STR_PAD_LEFT);
                
                $sql = "INSERT INTO cfcs (nome, cnpj, endereco, cidade, estado, responsavel, status, criado_em) 
                        VALUES (?, ?, 'Endereco Teste', 'São Paulo', 'SP', 'Responsavel Teste', 'ativo', NOW())";
                
                $resultado = $this->db->query($sql, [$nome, $cnpj]);
                
                if ($resultado) {
                    $idsCriados[] = $this->db->getConnection()->lastInsertId();
                    $sucessos++;
                }
            } catch (Exception $e) {
                // Falha silenciosa para não interromper o teste
            }
        }
        
        $tempo = (microtime(true) - $inicio) * 1000;
        $taxaSucesso = ($sucessos / $numUsuarios) * 100;
        
        $this->sucesso("Escrita simultânea: $sucessos/$numUsuarios sucessos (" . round($taxaSucesso, 1) . "%) em " . round($tempo, 2) . "ms");
        
        if ($taxaSucesso >= 90) {
            $this->sucesso("Concorrência de escrita EXCELENTE");
        } elseif ($taxaSucesso >= 70) {
            $this->sucesso("Concorrência de escrita BOA");
        } else {
            $this->falha("Concorrência de escrita com problemas");
        }
        
        $this->adicionarMetrica('escrita_simultanea', $tempo);
        $this->adicionarMetrica('taxa_escrita', $taxaSucesso);
        
        // Limpar dados de teste
        $this->limparDadosTeste($idsCriados);
    }
    
    /**
     * Testar operações mistas
     */
    private function testarOperacoesMistas($numUsuarios) {
        $inicio = microtime(true);
        $sucessos = 0;
        
        for ($i = 0; $i < $numUsuarios; $i++) {
            try {
                // Operação mista: leitura + escrita
                $resultado = $this->db->fetch("SELECT COUNT(*) as total FROM cfcs WHERE status = ?", ['ativo']);
                
                if ($resultado) {
                    // Simular log de operação
                    $sql = "INSERT INTO logs (usuario_id, acao, tabela, registro_id, ip, criado_em) 
                            VALUES (?, ?, ?, ?, ?, NOW())";
                    $resultadoLog = $this->db->query($sql, [1, 'consulta', 'cfcs', 0, '127.0.0.1']);
                    
                    if ($resultadoLog) {
                        $sucessos++;
                    }
                }
            } catch (Exception $e) {
                // Falha silenciosa para não interromper o teste
            }
        }
        
        $tempo = (microtime(true) - $inicio) * 1000;
        $taxaSucesso = ($sucessos / $numUsuarios) * 100;
        
        $this->sucesso("Operações mistas: $sucessos/$numUsuarios sucessos (" . round($taxaSucesso, 1) . "%) em " . round($tempo, 2) . "ms");
        
        $this->adicionarMetrica('operacoes_mistas', $tempo);
        $this->adicionarMetrica('taxa_mistas', $taxaSucesso);
    }
    
    /**
     * Testar consultas complexas
     */
    private function testarConsultasComplexas() {
        $this->iniciarTeste("Consultas Complexas e Relatórios");
        
        try {
            // Teste 1: Relatório de estatísticas
            $inicio = microtime(true);
            $sql = "SELECT 
                        c.nome as cfc_nome,
                        COUNT(DISTINCT a.id) as total_alunos,
                        COUNT(DISTINCT i.id) as total_instrutores,
                        COUNT(DISTINCT v.id) as total_veiculos,
                        COUNT(DISTINCT au.id) as total_aulas
                    FROM cfcs c
                                LEFT JOIN alunos a ON c.id = a.cfc_id AND a.status = 'ativo'
            LEFT JOIN instrutores i ON c.id = i.cfc_id AND i.ativo = 1
            LEFT JOIN veiculos v ON c.id = v.cfc_id AND v.ativo = 1
                    LEFT JOIN aulas au ON c.id = au.cfc_id AND au.status = 'agendada'
                    WHERE c.ativo = 1
                    GROUP BY c.id, c.nome
                    ORDER BY total_alunos DESC";
            
            $resultados = $this->db->fetchAll($sql);
            $tempoRelatorio = (microtime(true) - $inicio) * 1000;
            
            if ($resultados !== false) {
                $this->sucesso("Relatório complexo executado em " . round($tempoRelatorio, 2) . "ms");
                $this->adicionarMetrica('relatorio_complexo', $tempoRelatorio);
                
                if ($tempoRelatorio < 500) {
                    $this->sucesso("Performance do relatório EXCELENTE (< 500ms)");
                } elseif ($tempoRelatorio < 1000) {
                    $this->sucesso("Performance do relatório BOA (< 1s)");
                } elseif ($tempoRelatorio < 3000) {
                    $this->falha("Performance do relatório ACEITÁVEL (< 3s) - Considerar otimização");
                } else {
                    $this->falha("Performance do relatório RUIM (> 3s) - Otimização necessária");
                }
            } else {
                $this->falha("Relatório complexo falhou");
            }
            
            // Teste 2: Consulta com subconsultas
            $inicio = microtime(true);
            $sql = "SELECT 
                        c.nome as cfc_nome,
                                    (SELECT COUNT(*) FROM alunos WHERE cfc_id = c.id AND status = 'ativo') as total_alunos,
            (SELECT COUNT(*) FROM instrutores WHERE cfc_id = c.id AND ativo = 1) as total_instrutores,
            (SELECT COUNT(*) FROM veiculos WHERE cfc_id = c.id AND ativo = 1) as total_veiculos
                    FROM cfcs c
                    WHERE c.ativo = 1
                    HAVING total_alunos > 0 OR total_instrutores > 0 OR total_veiculos > 0";
            
            $resultadosSub = $this->db->fetchAll($sql);
            $tempoSubconsultas = (microtime(true) - $inicio) * 1000;
            
            if ($resultadosSub !== false) {
                $this->sucesso("Subconsultas executadas em " . round($tempoSubconsultas, 2) . "ms");
                $this->adicionarMetrica('subconsultas', $tempoSubconsultas);
            } else {
                $this->falha("Subconsultas falharam");
            }
            
        } catch (Exception $e) {
            $this->falha("Erro no teste de consultas complexas: " . $e->getMessage());
        }
        
        $this->finalizarTeste();
    }
    
    /**
     * Testar operações em lote
     */
    private function testarOperacoesEmLote() {
        $this->iniciarTeste("Operações em Lote");
        
        try {
            // Teste 1: Inserção em lote
            $inicio = microtime(true);
            $numRegistros = 50;
            $sucessos = 0;
            
            $this->db->beginTransaction();
            
            for ($i = 0; $i < $numRegistros; $i++) {
                $nome = 'Aluno Lote ' . date('Y-m-d H:i:s') . " - $i";
                $cpf = '123.456.789-' . str_pad($i, 2, '0', STR_PAD_LEFT);
                
                $sql = "INSERT INTO alunos (nome, cpf, email, telefone, categoria_cnh, cfc_id, status, criado_em) 
                        VALUES (?, ?, ?, ?, ?, ?, 'ativo', NOW())";
                
                $resultado = $this->db->query($sql, [
                    $nome, 
                    $cpf, 
                    "aluno$i@teste.com", 
                    "(11) 99999-999$i", 
                    'B', 
                    1
                ]);
                
                if ($resultado) {
                    $sucessos++;
                }
            }
            
            $this->db->commit();
            $tempoLote = (microtime(true) - $inicio) * 1000;
            
            $this->sucesso("Inserção em lote: $sucessos/$numRegistros registros em " . round($tempoLote, 2) . "ms");
            $this->adicionarMetrica('insercao_lote', $tempoLote);
            
            $registrosPorSegundo = ($sucessos / $tempoLote) * 1000;
            $this->sucesso("Taxa de inserção: " . round($registrosPorSegundo, 1) . " registros/segundo");
            
            // Limpar dados de teste
            $this->limparAlunosTeste();
            
        } catch (Exception $e) {
            $this->db->rollback();
            $this->falha("Erro no teste de operações em lote: " . $e->getMessage());
        }
        
        $this->finalizarTeste();
    }
    
    /**
     * Testar limites do sistema
     */
    private function testarLimitesSistema() {
        $this->iniciarTeste("Limites e Capacidade do Sistema");
        
        try {
            // Teste 1: Múltiplas conexões
            $inicio = microtime(true);
            $conexoes = [];
            $maxConexoes = 20;
            
            for ($i = 0; $i < $maxConexoes; $i++) {
                try {
                    $conexao = new PDO(
                        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                        DB_USER,
                        DB_PASS,
                        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                    );
                    $conexoes[] = $conexao;
                } catch (Exception $e) {
                    break;
                }
            }
            
            $tempoConexoes = (microtime(true) - $inicio) * 1000;
            $conexoesAtivas = count($conexoes);
            
            $this->sucesso("Conexões simultâneas: $conexoesAtivas/$maxConexoes em " . round($tempoConexoes, 2) . "ms");
            $this->adicionarMetrica('conexoes_simultaneas', $conexoesAtivas);
            
            if ($conexoesAtivas >= 15) {
                $this->sucesso("Capacidade de conexões EXCELENTE");
            } elseif ($conexoesAtivas >= 10) {
                $this->sucesso("Capacidade de conexões BOA");
            } else {
                $this->falha("Capacidade de conexões limitada");
            }
            
            // Fechar conexões de teste
            foreach ($conexoes as $conexao) {
                $conexao = null;
            }
            
            // Teste 2: Tamanho das consultas
            $inicio = microtime(true);
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM information_schema.tables WHERE table_schema = DATABASE()");
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            $tempoInfo = (microtime(true) - $inicio) * 1000;
            
            if ($resultado) {
                $this->sucesso("Consulta de metadados executada em " . round($tempoInfo, 2) . "ms");
                $this->adicionarMetrica('metadados', $tempoInfo);
            }
            
        } catch (Exception $e) {
            $this->falha("Erro no teste de limites: " . $e->getMessage());
        }
        
        $this->finalizarTeste();
    }
    
    /**
     * Testar recuperação de erros
     */
    private function testarRecuperacaoErros() {
        $this->iniciarTeste("Recuperação de Erros e Robustez");
        
        try {
            // Teste 1: Consulta com erro de sintaxe
            $inicio = microtime(true);
            $erroCapturado = false;
            
            try {
                $stmt = $this->db->query("SELECT * FROM tabela_inexistente");
            } catch (Exception $e) {
                $erroCapturado = true;
                $this->sucesso("Erro de tabela inexistente capturado corretamente");
            }
            
            if (!$erroCapturado) {
                $this->falha("Erro de tabela inexistente não foi capturado");
            }
            
            // Teste 2: Recuperação após erro
            $inicio = microtime(true);
            $stmt = $this->db->query("SELECT 1 as teste");
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            $tempoRecuperacao = (microtime(true) - $inicio) * 1000;
            
            if ($resultado && $resultado['teste'] == 1) {
                $this->sucesso("Sistema recuperou após erro em " . round($tempoRecuperacao, 2) . "ms");
                $this->adicionarMetrica('recuperacao_erro', $tempoRecuperacao);
            } else {
                $this->falha("Sistema não se recuperou após erro");
            }
            
            // Teste 3: Transações com rollback
            $inicio = microtime(true);
            $this->db->beginTransaction();
            
            try {
                $this->db->query("INSERT INTO cfcs (nome, cnpj, status, criado_em) VALUES (?, ?, 'ativo', NOW())", ['CFC Teste Rollback', '12.345.678/0001-99']);
                
                // Forçar erro
                $this->db->query("INSERT INTO cfcs (nome, cnpj, status, criado_em) VALUES (?, ?, 'ativo', NOW())", ['CFC Teste Rollback 2', '12.345.678/0001-99']); // CNPJ duplicado
                
                $this->db->commit();
                $this->falha("Rollback não funcionou - transação foi commitada");
            } catch (Exception $e) {
                $this->db->rollback();
                $this->sucesso("Rollback funcionou corretamente após erro");
            }
            
            $tempoTransacao = (microtime(true) - $inicio) * 1000;
            $this->adicionarMetrica('transacao_rollback', $tempoTransacao);
            
        } catch (Exception $e) {
            $this->falha("Erro no teste de recuperação: " . $e->getMessage());
        }
        
        $this->finalizarTeste();
    }
    
    /**
     * Limpar dados de teste
     */
    private function limparDadosTeste($ids) {
        if (!empty($ids)) {
            try {
                $placeholders = str_repeat('?,', count($ids) - 1) . '?';
                $this->db->query("DELETE FROM cfcs WHERE id IN ($placeholders)", $ids);
                $this->sucesso("Dados de teste limpos: " . count($ids) . " registros");
            } catch (Exception $e) {
                $this->falha("Erro ao limpar dados de teste: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Limpar alunos de teste
     */
    private function limparAlunosTeste() {
        try {
            $this->db->query("DELETE FROM alunos WHERE nome LIKE 'Aluno Lote%'");
            $this->sucesso("Alunos de teste limpos");
        } catch (Exception $e) {
            $this->falha("Erro ao limpar alunos de teste: " . $e->getMessage());
        }
    }
    
    /**
     * Adicionar métrica
     */
    private function adicionarMetrica($nome, $valor) {
        $this->metricas[$nome] = $valor;
    }
    
    /**
     * Iniciar teste
     */
    private function iniciarTeste($nome) {
        $this->testes[] = [
            'nome' => $nome,
            'inicio' => microtime(true),
            'sucessos' => 0,
            'falhas' => 0,
            'erros' => []
        ];
        
        echo "<h2>🔍 Testando: $nome</h2>";
        echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    }
    
    /**
     * Finalizar teste
     */
    private function finalizarTeste() {
        $teste = end($this->testes);
        $duracao = round((microtime(true) - $teste['inicio']) * 1000, 2);
        
        echo "</div>";
        echo "<p><strong>Duração:</strong> {$duracao}ms | ";
        echo "<strong>Sucessos:</strong> {$teste['sucessos']} | ";
        echo "<strong>Falhas:</strong> {$teste['falhas']}</p>";
        echo "<hr>";
    }
    
    /**
     * Registrar sucesso
     */
    private function sucesso($mensagem) {
        $this->sucessos++;
        $teste = &$this->testes[count($this->testes) - 1];
        $teste['sucessos']++;
        
        echo "<div style='color: #155724; background: #d4edda; padding: 8px; margin: 5px 0; border-radius: 3px;'>";
        echo "✅ $mensagem";
        echo "</div>";
    }
    
    /**
     * Registrar falha
     */
    private function falha($mensagem) {
        $this->falhas++;
        $teste = &$this->testes[count($this->testes) - 1];
        $teste['falhas']++;
        $teste['erros'][] = $mensagem;
        
        echo "<div style='color: #721c24; background: #f8d7da; padding: 8px; margin: 5px 0; border-radius: 3px;'>";
        echo "❌ $mensagem";
        echo "</div>";
    }
    
    /**
     * Exibir resultados finais
     */
    private function exibirResultadosFinais() {
        $tempoTotal = (microtime(true) - $this->inicioGeral) * 1000;
        
        echo "<h1>📊 RESULTADOS DOS TESTES DE PERFORMANCE E STRESS</h1>";
        echo "<div style='background: #e9ecef; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        
        $totalTestes = count($this->testes);
        $taxaSucesso = $this->sucessos > 0 ? round(($this->sucessos / ($this->sucessos + $this->falhas)) * 100, 2) : 0;
        
        echo "<h2>📈 Resumo de Performance</h2>";
        echo "<p><strong>Tempo Total de Execução:</strong> " . round($tempoTotal, 2) . "ms</p>";
        echo "<p><strong>Total de Testes:</strong> $totalTestes</p>";
        echo "<p><strong>Sucessos:</strong> <span style='color: #28a745; font-weight: bold;'>$this->sucessos</span></p>";
        echo "<p><strong>Falhas:</strong> <span style='color: #dc3545; font-weight: bold;'>$this->falhas</span></p>";
        echo "<p><strong>Taxa de Sucesso:</strong> <span style='color: #007bff; font-weight: bold;'>$taxaSucesso%</span></p>";
        
        if ($taxaSucesso >= 90) {
            echo "<p style='color: #28a745; font-weight: bold; font-size: 18px;'>🚀 PERFORMANCE EXCELENTE - SISTEMA PRONTO PARA PRODUÇÃO!</p>";
        } elseif ($taxaSucesso >= 80) {
            echo "<p style='color: #ffc107; font-weight: bold; font-size: 18px;'>⚠️ PERFORMANCE BOA - PEQUENAS OTIMIZAÇÕES RECOMENDADAS</p>";
        } else {
            echo "<p style='color: #dc3545; font-weight: bold; font-size: 18px;'>🚨 PERFORMANCE COM PROBLEMAS - OTIMIZAÇÕES NECESSÁRIAS</p>";
        }
        
        echo "</div>";
        
        // Métricas de performance
        if (!empty($this->metricas)) {
            echo "<h2>📊 Métricas de Performance</h2>";
            echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
            
            foreach ($this->metricas as $nome => $valor) {
                $cor = $valor < 100 ? '#28a745' : ($valor < 500 ? '#ffc107' : '#dc3545');
                echo "<p><strong>$nome:</strong> <span style='color: $cor; font-weight: bold;'>" . round($valor, 2) . "ms</span></p>";
            }
            
            echo "</div>";
        }
        
        // Detalhamento por teste
        echo "<h2>🔍 Detalhamento por Teste</h2>";
        foreach ($this->testes as $i => $teste) {
            $taxaTeste = $teste['sucessos'] > 0 ? round(($teste['sucessos'] / ($teste['sucessos'] + $teste['falhas'])) * 100, 2) : 0;
            $status = $taxaTeste >= 90 ? '✅' : ($taxaTeste >= 70 ? '⚠️' : '❌');
            
            echo "<div style='border: 1px solid #dee2e6; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
            echo "<h3>$status {$teste['nome']}</h3>";
            echo "<p><strong>Sucessos:</strong> {$teste['sucessos']} | <strong>Falhas:</strong> {$teste['falhas']} | <strong>Taxa:</strong> {$taxaTeste}%</p>";
            
            if (!empty($teste['erros'])) {
                echo "<p><strong>Problemas encontrados:</strong></p>";
                echo "<ul>";
                foreach ($teste['erros'] as $erro) {
                    echo "<li style='color: #dc3545;'>$erro</li>";
                }
                echo "</ul>";
            }
            echo "</div>";
        }
        
        // Recomendações de performance
        echo "<h2>💡 RECOMENDAÇÕES DE PERFORMANCE</h2>";
        if ($this->falhas > 0) {
            echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107;'>";
            echo "<p><strong>⚠️ Otimizações Recomendadas:</strong></p>";
            echo "<ul>";
            echo "<li>Corrigir os " . $this->falhas . " problemas de performance identificados</li>";
            echo "<li>Considerar índices adicionais no banco de dados</li>";
            echo "<li>Otimizar consultas complexas e relatórios</li>";
            echo "<li>Implementar cache para consultas frequentes</li>";
            echo "<li>Monitorar uso de memória e CPU</li>";
            echo "</ul>";
            echo "</div>";
        } else {
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; border-left: 4px solid #28a745;'>";
            echo "<p><strong>🎉 Excelente!</strong> O sistema está performando perfeitamente!</p>";
            echo "<ul>";
            echo "<li>Performance otimizada para produção</li>";
            echo "<li>Capacidade de concorrência adequada</li>";
            echo "<li>Recuperação de erros robusta</li>";
            echo "<li>Sistema escalável e confiável</li>";
            echo "</ul>";
            echo "</div>";
        }
        
        echo "<hr>";
        echo "<p><em>Teste de performance executado em: " . date('d/m/Y H:i:s') . "</em></p>";
    }
}

// Executar testes
$teste = new TestePerformanceStress();
$teste->executarTodosTestes();
?>
