<?php
/**
 * Script de Execução em Lote - Todos os Testes
 * Executa todos os testes disponíveis e gera relatório consolidado
 * 
 * @author Sistema CFC
 * @version 1.0
 * @since 2024
 */

// Configurações
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
set_time_limit(600); // 10 minutos

// Incluir arquivos necessários
require_once '../includes/config.php';
require_once '../includes/database.php';

class ExecutorTestesCompletos {
    private $db;
    private $resultados = [];
    private $inicioGeral;
    private $totalSucessos = 0;
    private $totalFalhas = 0;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->inicioGeral = microtime(true);
    }
    
    /**
     * Executar todos os testes
     */
    public function executarTodosTestes() {
        echo "<!DOCTYPE html>";
        echo "<html lang='pt-BR'>";
        echo "<head>";
        echo "<meta charset='UTF-8'>";
        echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
        echo "<title>🧪 EXECUÇÃO COMPLETA DE TESTES - CFC BOM CONSELHO</title>";
        echo "<style>";
        echo "body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 20px; background: #f8f9fa; }";
        echo ".container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }";
        echo ".header { text-align: center; margin-bottom: 30px; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 10px; }";
        echo ".teste-section { margin: 20px 0; padding: 20px; border: 1px solid #dee2e6; border-radius: 8px; background: #f8f9fa; }";
        echo ".sucesso { color: #155724; background: #d4edda; padding: 10px; margin: 5px 0; border-radius: 5px; border-left: 4px solid #28a745; }";
        echo ".falha { color: #721c24; background: #f8d7da; padding: 10px; margin: 5px 0; border-radius: 5px; border-left: 4px solid #dc3545; }";
        echo ".info { color: #0c5460; background: #d1ecf1; padding: 10px; margin: 5px 0; border-radius: 5px; border-left: 4px solid #17a2b8; }";
        echo ".progress-bar { width: 100%; height: 20px; background: #e9ecef; border-radius: 10px; overflow: hidden; margin: 10px 0; }";
        echo ".progress-fill { height: 100%; background: linear-gradient(90deg, #28a745, #20c997); transition: width 0.3s ease; }";
        echo ".resumo-final { background: #e9ecef; padding: 20px; border-radius: 10px; margin: 20px 0; text-align: center; }";
        echo ".metricas { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }";
        echo ".metrica { background: white; padding: 15px; border-radius: 8px; text-align: center; border: 1px solid #dee2e6; }";
        echo ".metrica-valor { font-size: 24px; font-weight: bold; margin: 10px 0; }";
        echo ".excelente { color: #28a745; }";
        echo ".bom { color: #ffc107; }";
        echo ".ruim { color: #dc3545; }";
        echo "</style>";
        echo "</head>";
        echo "<body>";
        
        echo "<div class='container'>";
        echo "<div class='header'>";
        echo "<h1>🧪 EXECUÇÃO COMPLETA DE TESTES</h1>";
        echo "<h2>CFC BOM CONSELHO - SISTEMA DE TESTES AUTOMATIZADOS</h2>";
        echo "<p><strong>Data/Hora de Início:</strong> " . date('d/m/Y H:i:s') . "</p>";
        echo "<p><strong>Versão do Sistema:</strong> 95% COMPLETO</p>";
        echo "</div>";
        
        echo "<div class='info'>";
        echo "<h3>📋 INICIANDO EXECUÇÃO COMPLETA DOS TESTES</h3>";
        echo "<p>Este script executará todos os testes disponíveis em sequência e gerará um relatório consolidado.</p>";
        echo "</div>";
        
        // Executar testes em sequência
        $this->executarTeste('test-usuario-real.php', 'Teste Principal de Usuário Real');
        $this->executarTeste('test-interacao-usuario.php', 'Teste de Interação e Navegação');
        $this->executarTeste('test-performance-stress.php', 'Teste de Performance e Stress');
        $this->executarTeste('test-api-agendamento.php', 'Teste das APIs de Agendamento');
        
        // Exibir relatório consolidado
        $this->exibirRelatorioConsolidado();
        
        echo "</div>";
        echo "</body>";
        echo "</html>";
    }
    
    /**
     * Executar teste específico
     */
    private function executarTeste($arquivo, $nome) {
        echo "<div class='teste-section'>";
        echo "<h3>🔍 Executando: $nome</h3>";
        echo "<p><strong>Arquivo:</strong> $arquivo</p>";
        echo "<p><strong>Status:</strong> <span id='status-$arquivo'>⏳ Executando...</span></p>";
        
        $inicio = microtime(true);
        
        try {
            // Capturar saída do teste
            ob_start();
            
            if (file_exists($arquivo)) {
                include $arquivo;
                $saida = ob_get_contents();
                ob_end_clean();
                
                // Extrair estatísticas da saída
                $estatisticas = $this->extrairEstatisticas($saida);
                
                $tempo = (microtime(true) - $inicio) * 1000;
                
                echo "<div class='sucesso'>";
                echo "✅ <strong>$nome</strong> executado com sucesso em " . round($tempo, 2) . "ms";
                echo "</div>";
                
                echo "<div class='info'>";
                echo "<strong>Estatísticas:</strong><br>";
                echo "• Sucessos: {$estatisticas['sucessos']}<br>";
                echo "• Falhas: {$estatisticas['falhas']}<br>";
                echo "• Taxa de Sucesso: {$estatisticas['taxa']}%";
                echo "</div>";
                
                // Atualizar contadores globais
                $this->totalSucessos += $estatisticas['sucessos'];
                $this->totalFalhas += $estatisticas['falhas'];
                
                // Armazenar resultado
                $this->resultados[$arquivo] = [
                    'nome' => $nome,
                    'status' => 'sucesso',
                    'tempo' => $tempo,
                    'estatisticas' => $estatisticas,
                    'saida' => $saida
                ];
                
                echo "<script>document.getElementById('status-$arquivo').innerHTML = '✅ Concluído';</script>";
                
            } else {
                throw new Exception("Arquivo de teste não encontrado: $arquivo");
            }
            
        } catch (Exception $e) {
            $tempo = (microtime(true) - $inicio) * 1000;
            
            echo "<div class='falha'>";
            echo "❌ <strong>Erro ao executar $nome:</strong> " . $e->getMessage();
            echo "</div>";
            
            // Armazenar resultado com erro
            $this->resultados[$arquivo] = [
                'nome' => $nome,
                'status' => 'erro',
                'tempo' => $tempo,
                'erro' => $e->getMessage(),
                'estatisticas' => ['sucessos' => 0, 'falhas' => 1, 'taxa' => 0]
            ];
            
            $this->totalFalhas++;
            
            echo "<script>document.getElementById('status-$arquivo').innerHTML = '❌ Erro';</script>";
        }
        
        echo "</div>";
    }
    
    /**
     * Extrair estatísticas da saída do teste
     */
    private function extrairEstatisticas($saida) {
        $sucessos = 0;
        $falhas = 0;
        
        // Contar sucessos e falhas baseado na saída
        preg_match_all('/✅/', $saida, $matches);
        $sucessos = count($matches[0]);
        
        preg_match_all('/❌/', $saida, $matches);
        $falhas = count($matches[0]);
        
        $total = $sucessos + $falhas;
        $taxa = $total > 0 ? round(($sucessos / $total) * 100, 2) : 0;
        
        return [
            'sucessos' => $sucessos,
            'falhas' => $falhas,
            'taxa' => $taxa
        ];
    }
    
    /**
     * Exibir relatório consolidado
     */
    private function exibirRelatorioConsolidado() {
        $tempoTotal = (microtime(true) - $this->inicioGeral) * 1000;
        $totalTestes = count($this->resultados);
        $testesSucesso = count(array_filter($this->resultados, function($r) { return $r['status'] === 'sucesso'; }));
        $taxaSucessoGeral = $totalTestes > 0 ? round(($testesSucesso / $totalTestes) * 100, 2) : 0;
        
        echo "<div class='resumo-final'>";
        echo "<h2>📊 RELATÓRIO CONSOLIDADO FINAL</h2>";
        echo "<p><strong>Execução concluída em:</strong> " . date('d/m/Y H:i:s') . "</p>";
        echo "<p><strong>Tempo total de execução:</strong> " . round($tempoTotal, 2) . "ms</p>";
        
        // Barra de progresso
        echo "<div class='progress-bar'>";
        echo "<div class='progress-fill' style='width: {$taxaSucessoGeral}%'></div>";
        echo "</div>";
        echo "<p><strong>Taxa de Sucesso Geral:</strong> {$taxaSucessoGeral}%</p>";
        
        // Classificação geral
        if ($taxaSucessoGeral >= 90) {
            echo "<h3 style='color: #28a745;'>🎉 EXCELENTE - SISTEMA PRONTO PARA PRODUÇÃO!</h3>";
        } elseif ($taxaSucessoGeral >= 80) {
            echo "<h3 style='color: #ffc107;'>⚠️ BOM - PEQUENAS CORREÇÕES NECESSÁRIAS</h3>";
        } else {
            echo "<h3 style='color: #dc3545;'>🚨 PROBLEMAS - CORREÇÕES URGENTES NECESSÁRIAS</h3>";
        }
        
        echo "</div>";
        
        // Métricas consolidadas
        echo "<div class='metricas'>";
        
        echo "<div class='metrica'>";
        echo "<h4>📁 Testes Executados</h4>";
        echo "<div class='metrica-valor'>$totalTestes</div>";
        echo "<p>Total de testes realizados</p>";
        echo "</div>";
        
        echo "<div class='metrica'>";
        echo "<h4>✅ Testes com Sucesso</h4>";
        echo "<div class='metrica-valor excelente'>$testesSucesso</div>";
        echo "<p>Testes que passaram</p>";
        echo "</div>";
        
        echo "<div class='metrica'>";
        echo "<h4>❌ Testes com Falha</h4>";
        echo "<div class='metrica-valor ruim'>" . ($totalTestes - $testesSucesso) . "</div>";
        echo "<p>Testes que falharam</p>";
        echo "</div>";
        
        echo "<div class='metrica'>";
        echo "<h4>🎯 Taxa de Sucesso</h4>";
        echo "<div class='metrica-valor " . ($taxaSucessoGeral >= 90 ? 'excelente' : ($taxaSucessoGeral >= 80 ? 'bom' : 'ruim')) . "'>{$taxaSucessoGeral}%</div>";
        echo "<p>Percentual de sucesso</p>";
        echo "</div>";
        
        echo "<div class='metrica'>";
        echo "<h4>⚡ Operações Totais</h4>";
        echo "<div class='metrica-valor'>" . ($this->totalSucessos + $this->totalFalhas) . "</div>";
        echo "<p>Total de operações testadas</p>";
        echo "</div>";
        
        echo "<div class='metrica'>";
        echo "<h4>🚀 Performance</h4>";
        echo "<div class='metrica-valor'>" . round($tempoTotal / 1000, 2) . "s</div>";
        echo "<p>Tempo total de execução</p>";
        echo "</div>";
        
        echo "</div>";
        
        // Detalhamento por teste
        echo "<h3>🔍 DETALHAMENTO POR TESTE</h3>";
        foreach ($this->resultados as $arquivo => $resultado) {
            $statusIcon = $resultado['status'] === 'sucesso' ? '✅' : '❌';
            $statusClass = $resultado['status'] === 'sucesso' ? 'sucesso' : 'falha';
            
            echo "<div class='teste-section'>";
            echo "<h4>$statusIcon {$resultado['nome']}</h4>";
            echo "<p><strong>Arquivo:</strong> $arquivo</p>";
            echo "<p><strong>Status:</strong> " . ucfirst($resultado['status']) . "</p>";
            echo "<p><strong>Tempo de Execução:</strong> " . round($resultado['tempo'], 2) . "ms</p>";
            
            if ($resultado['status'] === 'sucesso') {
                echo "<p><strong>Sucessos:</strong> {$resultado['estatisticas']['sucessos']}</p>";
                echo "<p><strong>Falhas:</strong> {$resultado['estatisticas']['falhas']}</p>";
                echo "<p><strong>Taxa de Sucesso:</strong> {$resultado['estatisticas']['taxa']}%</p>";
            } else {
                echo "<p><strong>Erro:</strong> {$resultado['erro']}</p>";
            }
            echo "</div>";
        }
        
        // Recomendações finais
        echo "<h3>💡 RECOMENDAÇÕES FINAIS</h3>";
        
        if ($taxaSucessoGeral >= 90) {
            echo "<div class='sucesso'>";
            echo "<h4>🎉 Parabéns! O sistema está funcionando perfeitamente!</h4>";
            echo "<ul>";
            echo "<li>✅ Todas as funcionalidades principais estão operacionais</li>";
            echo "<li>✅ Performance dentro dos parâmetros esperados</li>";
            echo "<li>✅ Sistema pronto para uso em produção</li>";
            echo "<li>✅ Continue monitorando regularmente</li>";
            echo "</ul>";
            echo "</div>";
        } elseif ($taxaSucessoGeral >= 80) {
            echo "<div class='info'>";
            echo "<h4>⚠️ Sistema funcionando bem, mas com algumas melhorias necessárias</h4>";
            echo "<ul>";
            echo "<li>🔧 Corrigir os problemas identificados</li>";
            echo "<li>🔧 Executar testes novamente após correções</li>";
            echo "<li>🔧 Considerar otimizações de performance</li>";
            echo "<li>🔧 Sistema pode ser usado com cautela</li>";
            echo "</ul>";
            echo "</div>";
        } else {
            echo "<div class='falha'>";
            echo "<h4>🚨 Sistema com problemas significativos</h4>";
            echo "<ul>";
            echo "<li>🚨 Corrigir problemas urgentes antes do uso</li>";
            echo "<li>🚨 Revisar arquitetura e implementação</li>";
            echo "<li>🚨 Executar testes novamente após correções</li>";
            echo "<li>🚨 Sistema não recomendado para produção</li>";
            echo "</ul>";
            echo "</div>";
        }
        
        // Instruções para próximos passos
        echo "<h3>📋 PRÓXIMOS PASSOS RECOMENDADOS</h3>";
        echo "<div class='info'>";
        echo "<ol>";
        echo "<li><strong>Análise dos Resultados:</strong> Revisar detalhadamente cada teste que falhou</li>";
        echo "<li><strong>Correção de Problemas:</strong> Implementar correções baseadas nos erros identificados</li>";
        echo "<li><strong>Re-execução:</strong> Executar testes novamente após correções</li>";
        echo "<li><strong>Monitoramento Contínuo:</strong> Estabelecer rotina de testes regulares</li>";
        echo "<li><strong>Documentação:</strong> Atualizar documentação com base nos resultados</li>";
        echo "</ol>";
        echo "</div>";
        
        // Informações técnicas
        echo "<h3>🔧 INFORMAÇÕES TÉCNICAS</h3>";
        echo "<div class='info'>";
        echo "<p><strong>Servidor:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "</p>";
        echo "<p><strong>PHP:</strong> " . PHP_VERSION . "</p>";
        echo "<p><strong>Banco de Dados:</strong> MySQL</p>";
        echo "<p><strong>Diretório de Execução:</strong> " . __DIR__ . "</p>";
        echo "<p><strong>Memória Utilizada:</strong> " . round(memory_get_peak_usage(true) / 1024 / 1024, 2) . " MB</p>";
        echo "</div>";
        
        echo "<hr>";
        echo "<p><em>Relatório gerado automaticamente em: " . date('d/m/Y H:i:s') . "</em></p>";
        echo "<p><em>Sistema de Testes Automatizados - CFC Bom Conselho v1.0</em></p>";
    }
}

// Executar todos os testes
$executor = new ExecutorTestesCompletos();
$executor->executarTodosTestes();
?>
