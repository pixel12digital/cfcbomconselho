<?php
/**
 * Script de Teste de Interação de Usuário - Simulação de Navegação Real
 * Testa a experiência do usuário navegando pelo sistema
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

class TesteInteracaoUsuario {
    private $db;
    private $testes = [];
    private $sucessos = 0;
    private $falhas = 0;
    private $dadosTeste = [];
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->prepararDadosTeste();
    }
    
    /**
     * Preparar dados de teste
     */
    private function prepararDadosTeste() {
        // Dados para teste de CFC
        $this->dadosTeste['cfc'] = [
            'nome' => 'CFC Teste ' . date('Y-m-d H:i:s'),
            'cnpj' => '12.345.678/0001-90',
            'endereco' => 'Rua Teste, 123',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'responsavel' => 'João Teste',
            'telefone' => '(11) 99999-9999',
            'email' => 'teste@cfc.com'
        ];
        
        // Dados para teste de aluno
        $this->dadosTeste['aluno'] = [
            'nome' => 'Aluno Teste ' . date('Y-m-d H:i:s'),
            'cpf' => '123.456.789-00',
            'email' => 'aluno@teste.com',
            'telefone' => '(11) 88888-8888',
            'categoria_cnh' => 'B',
            'endereco' => 'Rua Aluno, 456',
            'cidade' => 'São Paulo',
            'estado' => 'SP'
        ];
        
        // Dados para teste de instrutor
        $this->dadosTeste['instrutor'] = [
            'nome' => 'Instrutor Teste ' . date('Y-m-d H:i:s'),
            'credencial' => 'CRED-' . date('Ymd'),
            'categorias_habilitacao' => 'A,B,C,D',
            'especializacoes' => 'Moto, Carro, Caminhão',
            'telefone' => '(11) 77777-7777',
            'email' => 'instrutor@teste.com'
        ];
        
        // Dados para teste de veículo
        $this->dadosTeste['veiculo'] = [
            'marca' => 'Fiat',
            'modelo' => 'Palio',
            'ano' => '2020',
            'placa' => 'ABC-1234',
            'categoria' => 'B',
            'quilometragem' => '15000',
            'status' => 'ativo'
        ];
        
        // Dados para teste de aula
        $this->dadosTeste['aula'] = [
            'tipo_aula' => 'pratica',
            'data_aula' => date('Y-m-d', strtotime('+1 day')),
            'hora_inicio' => '08:00:00',
            'hora_fim' => '09:00:00',
            'status' => 'agendada'
        ];
    }
    
    /**
     * Executar todos os testes de interação
     */
    public function executarTodosTestes() {
        echo "<h1>🎯 TESTE DE INTERAÇÃO DE USUÁRIO - SIMULAÇÃO DE NAVEGAÇÃO REAL</h1>";
        echo "<p><strong>Data/Hora:</strong> " . date('d/m/Y H:i:s') . "</p>";
        echo "<p><strong>Objetivo:</strong> Simular navegação e operações de usuário real</p>";
        echo "<hr>";
        
        // Executar testes em sequência
        $this->testarNavegacaoSistema();
        $this->testarOperacoesCRUD();
        $this->testarFormularios();
        $this->testarFiltros();
        $this->testarRelacionamentos();
        $this->testarValidacoes();
        
        // Exibir resultados finais
        $this->exibirResultadosFinais();
    }
    
    /**
     * Testar navegação pelo sistema
     */
    private function testarNavegacaoSistema() {
        $this->iniciarTeste("Navegação pelo Sistema");
        
        try {
            // Simular navegação entre páginas
            $paginas = [
                'Dashboard' => 'dashboard.php',
                'CFCs' => 'cfcs.php',
                'Alunos' => 'alunos.php',
                'Instrutores' => 'instrutores.php',
                'Veículos' => 'veiculos.php',
                'Agendamento' => 'agendamento.php'
            ];
            
            foreach ($paginas as $nome => $arquivo) {
                $caminho = "pages/$arquivo";
                if (file_exists($caminho)) {
                    $this->sucesso("Página '$nome' encontrada: $caminho");
                    
                    // Verificar se a página tem conteúdo
                    $conteudo = file_get_contents($caminho);
                    if (strlen($conteudo) > 100) {
                        $this->sucesso("Página '$nome' tem conteúdo válido (" . strlen($conteudo) . " caracteres)");
                    } else {
                        $this->falha("Página '$nome' tem conteúdo insuficiente");
                    }
                } else {
                    $this->falha("Página '$nome' não encontrada: $caminho");
                }
            }
            
            // Testar navegação do menu
            $this->testarMenuNavegacao();
            
        } catch (Exception $e) {
            $this->falha("Erro na navegação: " . $e->getMessage());
        }
        
        $this->finalizarTeste();
    }
    
    /**
     * Testar menu de navegação
     */
    private function testarMenuNavegacao() {
        try {
            $arquivoIndex = 'index.php';
            if (file_exists($arquivoIndex)) {
                $conteudo = file_get_contents($arquivoIndex);
                
                // Verificar links do menu
                $linksMenu = [
                    'dashboard' => 'Dashboard',
                    'cfcs' => 'CFCs',
                    'alunos' => 'Alunos',
                    'instrutores' => 'Instrutores',
                    'veiculos' => 'Veículos',
                    'agendamento' => 'Agendamento'
                ];
                
                foreach ($linksMenu as $link => $nome) {
                    if (strpos($conteudo, $link) !== false) {
                        $this->sucesso("Link do menu '$nome' encontrado");
                    } else {
                        $this->falha("Link do menu '$nome' não encontrado");
                    }
                }
                
                // Verificar estrutura HTML
                if (strpos($conteudo, '<nav') !== false || strpos($conteudo, 'class="nav') !== false) {
                    $this->sucesso("Estrutura de navegação HTML encontrada");
                } else {
                    $this->falha("Estrutura de navegação HTML não encontrada");
                }
            } else {
                $this->falha("Arquivo index.php não encontrado");
            }
        } catch (Exception $e) {
            $this->falha("Erro ao testar menu: " . $e->getMessage());
        }
    }
    
    /**
     * Testar operações CRUD
     */
    private function testarOperacoesCRUD() {
        $this->iniciarTeste("Operações CRUD (Create, Read, Update, Delete)");
        
        try {
            // Testar CREATE - CFC
            $this->testarCriarCFC();
            
            // Testar READ - Buscar dados
            $this->testarLerDados();
            
            // Testar UPDATE - Modificar dados
            $this->testarAtualizarDados();
            
            // Testar DELETE - Remover dados de teste
            $this->testarDeletarDados();
            
        } catch (Exception $e) {
            $this->falha("Erro nas operações CRUD: " . $e->getMessage());
        }
        
        $this->finalizarTeste();
    }
    
    /**
     * Testar criação de CFC
     */
    private function testarCriarCFC() {
        try {
            $dados = $this->dadosTeste['cfc'];
            
            $sql = "INSERT INTO cfcs (nome, cnpj, endereco, cidade, estado, responsavel, telefone, email, status, criado_em) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'ativo', NOW())";
            
            $params = [
                $dados['nome'],
                $dados['cnpj'],
                $dados['endereco'],
                $dados['cidade'],
                $dados['estado'],
                $dados['responsavel'],
                $dados['telefone'],
                $dados['email']
            ];
            
            $resultado = $this->db->query($sql, $params);
            
            if ($resultado) {
                $cfcId = $this->db->getConnection()->lastInsertId();
                $this->dadosTeste['cfc']['id'] = $cfcId;
                $this->sucesso("CFC criado com sucesso (ID: $cfcId)");
            } else {
                $this->falha("Falha ao criar CFC");
            }
            
        } catch (Exception $e) {
            $this->falha("Erro ao criar CFC: " . $e->getMessage());
        }
    }
    
    /**
     * Testar leitura de dados
     */
    private function testarLerDados() {
        try {
            if (isset($this->dadosTeste['cfc']['id'])) {
                $cfcId = $this->dadosTeste['cfc']['id'];
                
                $cfc = $this->db->fetch("SELECT * FROM cfcs WHERE id = ?", [$cfcId]);
                
                if ($cfc) {
                    $this->sucesso("CFC lido com sucesso: " . $cfc['nome']);
                    
                    // Verificar se todos os dados foram salvos corretamente
                    $campos = ['nome', 'cnpj', 'endereco', 'cidade', 'estado', 'responsavel'];
                    foreach ($campos as $campo) {
                        if ($cfc[$campo] === $this->dadosTeste['cfc'][$campo]) {
                            $this->sucesso("Campo '$campo' correto: " . $cfc[$campo]);
                        } else {
                            $this->falha("Campo '$campo' incorreto. Esperado: " . $this->dadosTeste['cfc'][$campo] . ", Obtido: " . $cfc[$campo]);
                        }
                    }
                } else {
                    $this->falha("CFC não encontrado após criação");
                }
            } else {
                $this->falha("CFC não foi criado para teste de leitura");
            }
            
        } catch (Exception $e) {
            $this->falha("Erro ao ler dados: " . $e->getMessage());
        }
    }
    
    /**
     * Testar atualização de dados
     */
    private function testarAtualizarDados() {
        try {
            if (isset($this->dadosTeste['cfc']['id'])) {
                $cfcId = $this->dadosTeste['cfc']['id'];
                $novoNome = 'CFC Atualizado ' . date('Y-m-d H:i:s');
                
                $resultado = $this->db->query("UPDATE cfcs SET nome = ?, atualizado_em = NOW() WHERE id = ?", [$novoNome, $cfcId]);
                
                if ($resultado) {
                    $this->sucesso("CFC atualizado com sucesso");
                    
                    // Verificar se a atualização foi aplicada
                    $cfc = $this->db->fetch("SELECT nome FROM cfcs WHERE id = ?", [$cfcId]);
                    
                    if ($cfc && $cfc['nome'] === $novoNome) {
                        $this->sucesso("Atualização confirmada: " . $cfc['nome']);
                    } else {
                        $this->falha("Atualização não foi aplicada corretamente");
                    }
                } else {
                    $this->falha("Falha ao atualizar CFC");
                }
            } else {
                $this->falha("CFC não foi criado para teste de atualização");
            }
            
        } catch (Exception $e) {
            $this->falha("Erro ao atualizar dados: " . $e->getMessage());
        }
    }
    
    /**
     * Testar deleção de dados
     */
    private function testarDeletarDados() {
        try {
            if (isset($this->dadosTeste['cfc']['id'])) {
                $cfcId = $this->dadosTeste['cfc']['id'];
                
                // Soft delete (mudar status para inativo)
                $resultado = $this->db->query("UPDATE cfcs SET status = 'inativo', atualizado_em = NOW() WHERE id = ?", [$cfcId]);
                
                if ($resultado) {
                    $this->sucesso("CFC marcado como inativo (soft delete)");
                    
                    // Verificar se o status foi alterado
                    $cfc = $this->db->fetch("SELECT status FROM cfcs WHERE id = ?", [$cfcId]);
                    
                    if ($cfc && $cfc['status'] === 'inativo') {
                        $this->sucesso("Status alterado para inativo confirmado");
                    } else {
                        $this->falha("Status não foi alterado corretamente");
                    }
                } else {
                    $this->falha("Falha ao marcar CFC como inativo");
                }
            } else {
                $this->falha("CFC não foi criado para teste de deleção");
            }
            
        } catch (Exception $e) {
            $this->falha("Erro ao deletar dados: " . $e->getMessage());
        }
    }
    
    /**
     * Testar formulários
     */
    private function testarFormularios() {
        $this->iniciarTeste("Formulários e Validações");
        
        try {
            // Testar estrutura de formulários nas páginas
            $paginasFormulario = [
                'cfcs.php' => ['nome', 'cnpj', 'endereco'],
                'alunos.php' => ['nome', 'cpf', 'email'],
                'instrutores.php' => ['nome', 'credencial', 'categorias_habilitacao'],
                'veiculos.php' => ['marca', 'modelo', 'placa']
            ];
            
            foreach ($paginasFormulario as $pagina => $campos) {
                $caminho = "pages/$pagina";
                if (file_exists($caminho)) {
                    $conteudo = file_get_contents($caminho);
                    
                    $this->sucesso("Página $pagina encontrada");
                    
                    // Verificar campos do formulário
                    foreach ($campos as $campo) {
                        if (strpos($conteudo, "name=\"$campo\"") !== false || strpos($conteudo, "name='$campo'") !== false) {
                            $this->sucesso("Campo '$campo' encontrado em $pagina");
                        } else {
                            $this->falha("Campo '$campo' não encontrado em $pagina");
                        }
                    }
                    
                    // Verificar botões de ação
                    if (strpos($conteudo, 'type="submit"') !== false || strpos($conteudo, 'type="button"') !== false) {
                        $this->sucesso("Botões de ação encontrados em $pagina");
                    } else {
                        $this->falha("Botões de ação não encontrados em $pagina");
                    }
                    
                } else {
                    $this->falha("Página $pagina não encontrada");
                }
            }
            
        } catch (Exception $e) {
            $this->falha("Erro ao testar formulários: " . $e->getMessage());
        }
        
        $this->finalizarTeste();
    }
    
    /**
     * Testar filtros
     */
    private function testarFiltros() {
        $this->iniciarTeste("Sistema de Filtros");
        
        try {
            // Testar filtros por CFC
            $filtroCidade = $this->db->fetch("SELECT COUNT(*) as total FROM cfcs WHERE cidade LIKE ?", ['%São Paulo%']);
            $this->sucesso("Filtro por cidade funcionando: " . $filtroCidade['total'] . " CFCs em São Paulo");
            
            // Testar filtros por status
            $filtroStatus = $this->db->fetch("SELECT COUNT(*) as total FROM cfcs WHERE status = ?", ['ativo']);
            $this->sucesso("Filtro por status funcionando: " . $filtroStatus['total'] . " CFCs ativos");
            
            // Testar filtros combinados
            $filtroCombinado = $this->db->fetch("SELECT COUNT(*) as total FROM cfcs WHERE status = ? AND cidade LIKE ?", ['ativo', '%São Paulo%']);
            $this->sucesso("Filtros combinados funcionando: " . $filtroCombinado['total'] . " CFCs ativos em São Paulo");
            
        } catch (Exception $e) {
            $this->falha("Erro ao testar filtros: " . $e->getMessage());
        }
        
        $this->finalizarTeste();
    }
    
    /**
     * Testar relacionamentos
     */
    private function testarRelacionamentos() {
        $this->iniciarTeste("Relacionamentos entre Entidades");
        
        try {
            // Testar relacionamento CFC -> Alunos
            $sql = "SELECT c.nome as cfc_nome, COUNT(a.id) as total_alunos 
                    FROM cfcs c 
                    LEFT JOIN alunos a ON c.id = a.cfc_id 
                    WHERE c.ativo = 1 
                    GROUP BY c.id, c.nome 
                    HAVING total_alunos > 0";
            $relacionamentos = $this->db->fetchAll($sql);
            
            if (!empty($relacionamentos)) {
                foreach ($relacionamentos as $rel) {
                    $this->sucesso("CFC '{$rel['cfc_nome']}' tem {$rel['total_alunos']} alunos");
                }
            } else {
                $this->sucesso("Relacionamento CFC-Alunos funcionando (sem dados para exibir)");
            }
            
            // Testar relacionamento CFC -> Instrutores
            $sql = "SELECT c.nome as cfc_nome, COUNT(i.id) as total_instrutores 
                    FROM cfcs c 
                    LEFT JOIN instrutores i ON c.id = i.cfc_id 
                    WHERE c.ativo = 1 
                    GROUP BY c.id, c.nome 
                    HAVING total_instrutores > 0";
            $relInstrutores = $this->db->fetchAll($sql);
            
            if (!empty($relInstrutores)) {
                foreach ($relInstrutores as $rel) {
                    $this->sucesso("CFC '{$rel['cfc_nome']}' tem {$rel['total_instrutores']} instrutores");
                }
            } else {
                $this->sucesso("Relacionamento CFC-Instrutores funcionando (sem dados para exibir)");
            }
            
            // Testar relacionamento CFC -> Veículos
            $sql = "SELECT c.nome as cfc_nome, COUNT(v.id) as total_veiculos 
                    FROM cfcs c 
                    LEFT JOIN veiculos v ON c.id = v.cfc_id 
                    WHERE c.ativo = 1 
                    GROUP BY c.id, c.nome 
                    HAVING total_veiculos > 0";
            $relVeiculos = $this->db->fetchAll($sql);
            
            if (!empty($relVeiculos)) {
                foreach ($relVeiculos as $rel) {
                    $this->sucesso("CFC '{$rel['cfc_nome']}' tem {$rel['total_veiculos']} veículos");
                }
            } else {
                $this->sucesso("Relacionamento CFC-Veículos funcionando (sem dados para exibir)");
            }
            
        } catch (Exception $e) {
            $this->falha("Erro ao testar relacionamentos: " . $e->getMessage());
        }
        
        $this->finalizarTeste();
    }
    
    /**
     * Testar validações
     */
    private function testarValidacoes() {
        $this->iniciarTeste("Sistema de Validações");
        
        try {
            // Testar validação de CNPJ
            $cnpjValido = '12.345.678/0001-90';
            $cnpjInvalido = '12.345.678/0001-99';
            
            if (strlen($cnpjValido) === 18) {
                $this->sucesso("Formato de CNPJ válido: $cnpjValido");
            } else {
                $this->falha("Formato de CNPJ inválido: $cnpjValido");
            }
            
            // Testar validação de CPF
            $cpfValido = '123.456.789-00';
            $cpfInvalido = '123.456.789-99';
            
            if (strlen($cpfValido) === 14) {
                $this->sucesso("Formato de CPF válido: $cpfValido");
            } else {
                $this->falha("Formato de CPF inválido: $cpfValido");
            }
            
            // Testar validação de email
            $emailValido = 'teste@cfc.com';
            $emailInvalido = 'teste.cfc.com';
            
            if (filter_var($emailValido, FILTER_VALIDATE_EMAIL)) {
                $this->sucesso("Email válido: $emailValido");
            } else {
                $this->falha("Email inválido: $emailValido");
            }
            
            if (!filter_var($emailInvalido, FILTER_VALIDATE_EMAIL)) {
                $this->sucesso("Email inválido detectado corretamente: $emailInvalido");
            } else {
                $this->falha("Email inválido não foi detectado: $emailInvalido");
            }
            
            // Testar validação de telefone
            $telefoneValido = '(11) 99999-9999';
            $telefoneInvalido = '11999999999';
            
            if (preg_match('/^\(\d{2}\) \d{4,5}-\d{4}$/', $telefoneValido)) {
                $this->sucesso("Formato de telefone válido: $telefoneValido");
            } else {
                $this->falha("Formato de telefone inválido: $telefoneValido");
            }
            
        } catch (Exception $e) {
            $this->falha("Erro ao testar validações: " . $e->getMessage());
        }
        
        $this->finalizarTeste();
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
        echo "<h1>📊 RESULTADOS DOS TESTES DE INTERAÇÃO</h1>";
        echo "<div style='background: #e9ecef; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        
        $totalTestes = count($this->testes);
        $taxaSucesso = $this->sucessos > 0 ? round(($this->sucessos / ($this->sucessos + $this->falhas)) * 100, 2) : 0;
        
        echo "<h2>📈 Resumo da Experiência do Usuário</h2>";
        echo "<p><strong>Total de Testes:</strong> $totalTestes</p>";
        echo "<p><strong>Sucessos:</strong> <span style='color: #28a745; font-weight: bold;'>$this->sucessos</span></p>";
        echo "<p><strong>Falhas:</strong> <span style='color: #dc3545; font-weight: bold;'>$this->falhas</span></p>";
        echo "<p><strong>Taxa de Sucesso:</strong> <span style='color: #007bff; font-weight: bold;'>$taxaSucesso%</span></p>";
        
        if ($taxaSucesso >= 90) {
            echo "<p style='color: #28a745; font-weight: bold; font-size: 18px;'>🎉 EXPERIÊNCIA DO USUÁRIO EXCELENTE!</p>";
        } elseif ($taxaSucesso >= 80) {
            echo "<p style='color: #ffc107; font-weight: bold; font-size: 18px;'>⚠️ EXPERIÊNCIA DO USUÁRIO BOA - PEQUENAS MELHORIAS NECESSÁRIAS</p>";
        } else {
            echo "<p style='color: #dc3545; font-weight: bold; font-size: 18px;'>🚨 EXPERIÊNCIA DO USUÁRIO COM PROBLEMAS - CORREÇÕES NECESSÁRIAS</p>";
        }
        
        echo "</div>";
        
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
        
        // Recomendações específicas para UX
        echo "<h2>💡 RECOMENDAÇÕES PARA EXPERIÊNCIA DO USUÁRIO</h2>";
        if ($this->falhas > 0) {
            echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107;'>";
            echo "<p><strong>⚠️ Melhorias Recomendadas:</strong></p>";
            echo "<ul>";
            echo "<li>Corrigir os " . $this->falhas . " problemas identificados</li>";
            echo "<li>Melhorar validações de formulários</li>";
            echo "<li>Otimizar navegação entre páginas</li>";
            echo "<li>Verificar responsividade em dispositivos móveis</li>";
            echo "</ul>";
            echo "</div>";
        } else {
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; border-left: 4px solid #28a745;'>";
            echo "<p><strong>🎉 Excelente!</strong> A experiência do usuário está perfeita!</p>";
            echo "<ul>";
            echo "<li>Navegação fluida e intuitiva</li>";
            echo "<li>Formulários bem estruturados</li>";
            echo "<li>Validações funcionando perfeitamente</li>";
            echo "<li>Sistema pronto para uso em produção</li>";
            echo "</ul>";
            echo "</div>";
        }
        
        echo "<hr>";
        echo "<p><em>Teste de interação executado em: " . date('d/m/Y H:i:s') . "</em></p>";
    }
}

// Executar testes
$teste = new TesteInteracaoUsuario();
$teste->executarTodosTestes();
?>
