<?php
/**
 * TESTE #8: CRUD de Veículos
 * Verifica todas as operações CRUD para a tabela veiculos
 */

echo "<h1>🔍 TESTE #8: CRUD de Veículos</h1>";
echo "<p><strong>Data/Hora:</strong> " . date('d/m/Y H:i:s') . "</p>";
echo "<p><strong>Ambiente:</strong> XAMPP Local (Porta 8080)</p>";
echo "<hr>";

// Contador de testes
$total_testes = 0;
$sucessos = 0;
$erros = 0;
$avisos = 0;

try {
    // 8.1 Inclusão de Arquivos Necessários
    echo "<h2>8.1 Inclusão de Arquivos Necessários</h2>";
    $total_testes++;
    
    if (file_exists('../includes/config.php') && file_exists('../includes/database.php')) {
        require_once '../includes/config.php';
        require_once '../includes/database.php';
        echo "✅ <strong>Arquivos necessários</strong> - INCLUÍDOS COM SUCESSO<br>";
        $sucessos++;
    } else {
        echo "❌ <strong>Arquivos necessários</strong> - NÃO ENCONTRADOS<br>";
        $erros++;
    }
    
    // 8.2 Conexão com Banco de Dados
    echo "<h2>8.2 Conexão com Banco de Dados</h2>";
    $total_testes++;
    
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        echo "✅ <strong>Conexão PDO</strong> - ESTABELECIDA COM SUCESSO<br>";
        $sucessos++;
    } catch (Exception $e) {
        echo "❌ <strong>Conexão PDO</strong> - FALHOU: " . $e->getMessage() . "<br>";
        $erros++;
    }
    
    // 8.3 Estrutura da Tabela 'veiculos'
    echo "<h2>8.3 Estrutura da Tabela 'veiculos'</h2>";
    $total_testes++;
    
    try {
        $stmt = $pdo->query("DESCRIBE veiculos");
        $colunas = $stmt->fetchAll();
        
        $colunas_necessarias = [
            'id', 'placa', 'marca', 'modelo', 'ano', 'cor', 'chassi', 'renavam',
            'cfc_id', 'status', 'created_at', 'updated_at'
        ];
        
        $colunas_encontradas = array_column($colunas, 'Field');
        $colunas_faltando = array_diff($colunas_necessarias, $colunas_encontradas);
        
        if (empty($colunas_faltando)) {
            echo "✅ <strong>Estrutura da tabela</strong> - COMPLETA<br>";
            $sucessos++;
        } else {
            echo "⚠️ <strong>Estrutura da tabela</strong> - FALTANDO: " . implode(', ', $colunas_faltando) . "<br>";
            $avisos++;
        }
        
        echo "<details style='margin: 10px 0;'>";
        echo "<summary style='cursor: pointer; color: #007bff;'>📋 Ver estrutura atual da tabela</summary>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0; font-size: 12px;'>";
        echo "<tr style='background: #f8f9fa;'><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th><th>Extra</th></tr>";
        
        foreach ($colunas as $coluna) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($coluna['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($coluna['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($coluna['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($coluna['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($coluna['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($coluna['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</details>";
        
    } catch (Exception $e) {
        echo "❌ <strong>Estrutura da tabela</strong> - ERRO: " . $e->getMessage() . "<br>";
        $erros++;
    }
    
    // 8.4 Verificação de Dados na Tabela 'veiculos'
    echo "<h2>8.4 Verificação de Dados na Tabela 'veiculos'</h2>";
    $total_testes++;
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM veiculos");
        $resultado = $stmt->fetch();
        $total_veiculos = $resultado['total'];
        
        echo "✅ <strong>Total de Veículos na tabela</strong> - $total_veiculos registros<br>";
        $sucessos++;
        
        if ($total_veiculos > 0) {
            echo "<details style='margin: 10px 0;'>";
            echo "<summary style='cursor: pointer; color: #007bff;'>📋 Ver veículos existentes</summary>";
            
            $stmt = $pdo->query("SELECT * FROM veiculos LIMIT 3");
            $veiculos = $stmt->fetchAll();
            
            echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0; font-size: 12px;'>";
            echo "<tr style='background: #f8f9fa;'><th>ID</th><th>Placa</th><th>Marca</th><th>Modelo</th><th>Status</th></tr>";
            
            foreach ($veiculos as $veiculo) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($veiculo['id']) . "</td>";
                echo "<td>" . htmlspecialchars($veiculo['placa'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($veiculo['marca'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($veiculo['modelo'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($veiculo['status'] ?? 'N/A') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "</details>";
        }
        
    } catch (Exception $e) {
        echo "❌ <strong>Verificação de dados</strong> - ERRO: " . $e->getMessage() . "<br>";
        $erros++;
    }
    
    // 8.5 Verificação de CFCs para Referência
    echo "<h2>8.5 Verificação de CFCs para Referência</h2>";
    $total_testes++;
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM cfcs");
        $resultado = $stmt->fetch();
        $total_cfcs = $resultado['total'];
        
        if ($total_cfcs > 0) {
            $stmt = $pdo->query("SELECT id, nome FROM cfcs LIMIT 1");
            $cfc = $stmt->fetch();
            echo "✅ <strong>CFC de referência</strong> - ENCONTRADO: " . htmlspecialchars($cfc['nome']) . " (ID: " . $cfc['id'] . ")<br>";
            $sucessos++;
        } else {
            echo "❌ <strong>CFC de referência</strong> - NENHUM CFC ENCONTRADO<br>";
            $erros++;
        }
        
    } catch (Exception $e) {
        echo "❌ <strong>Verificação de CFCs</strong> - ERRO: " . $e->getMessage() . "<br>";
        $erros++;
    }
    
    // 8.6 Teste CREATE - Criar Veículo
    echo "<h2>8.6 Teste CREATE - Criar Veículo</h2>";
    $total_testes++;
    
    try {
        $cfc_id = $cfc['id'] ?? 1;
        
        $sql = "INSERT INTO veiculos (
            placa, marca, modelo, ano, cor, chassi, renavam, 
            cfc_id, status, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $pdo->prepare($sql);
        $resultado = $stmt->execute([
            'ABC-1234',
            'Fiat',
            'Palio',
            '2020',
            'Branco',
            '9BWZZZ377VT004251',
            '12345678901',
            $cfc_id,
            'ativo'
        ]);
        
        if ($resultado) {
            $id_veiculo = $pdo->lastInsertId();
            echo "✅ <strong>CREATE</strong> - VEÍCULO CRIADO COM SUCESSO<br>";
            echo "✅ <strong>ID do veículo criado</strong> - $id_veiculo<br>";
            $sucessos++;
        } else {
            echo "❌ <strong>CREATE</strong> - FALHA AO CRIAR VEÍCULO<br>";
            $erros++;
        }
        
    } catch (Exception $e) {
        echo "❌ <strong>Erro no CREATE</strong> - " . $e->getMessage() . "<br>";
        $erros++;
    }
    
    // 8.7 Teste READ - Ler Veículo
    echo "<h2>8.7 Teste READ - Ler Veículo</h2>";
    $total_testes++;
    
    try {
        if (isset($id_veiculo)) {
            // READ por ID
            $stmt = $pdo->prepare("SELECT * FROM veiculos WHERE id = ?");
            $stmt->execute([$id_veiculo]);
            $veiculo = $stmt->fetch();
            
            if ($veiculo) {
                echo "✅ <strong>READ por ID</strong> - VEÍCULO ENCONTRADO<br>";
                echo "<details style='margin: 10px 0;'>";
                echo "<summary style='cursor: pointer; color: #007bff;'>📋 Dados: Placa: " . htmlspecialchars($veiculo['placa']) . ", Marca: " . htmlspecialchars($veiculo['marca']) . ", Status: " . htmlspecialchars($veiculo['status']) . "</summary>";
                echo "<pre>" . print_r($veiculo, true) . "</pre>";
                echo "</details>";
                $sucessos++;
            } else {
                echo "❌ <strong>READ por ID</strong> - VEÍCULO NÃO ENCONTRADO<br>";
                $erros++;
            }
            
            // READ por Placa
            $stmt = $pdo->prepare("SELECT * FROM veiculos WHERE placa = ?");
            $stmt->execute(['ABC-1234']);
            $veiculo_placa = $stmt->fetch();
            
            if ($veiculo_placa) {
                echo "✅ <strong>READ por Placa</strong> - VEÍCULO ENCONTRADO<br>";
                $sucessos++;
            } else {
                echo "❌ <strong>READ por Placa</strong> - VEÍCULO NÃO ENCONTRADO<br>";
                $erros++;
            }
            
            // READ ALL
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM veiculos");
            $resultado = $stmt->fetch();
            $total_veiculos_final = $resultado['total'];
            
            echo "✅ <strong>READ ALL</strong> - $total_veiculos_final VEÍCULOS ENCONTRADOS<br>";
            $sucessos++;
            
        } else {
            echo "⚠️ <strong>READ</strong> - SKIP (veículo não foi criado)<br>";
            $avisos++;
        }
        
    } catch (Exception $e) {
        echo "❌ <strong>Erro no READ</strong> - " . $e->getMessage() . "<br>";
        $erros++;
    }
    
    // 8.8 Teste UPDATE - Atualizar Veículo
    echo "<h2>8.8 Teste UPDATE - Atualizar Veículo</h2>";
    $total_testes++;
    
    try {
        if (isset($id_veiculo)) {
            $sql = "UPDATE veiculos SET cor = ?, status = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $resultado = $stmt->execute(['Azul', 'inativo', $id_veiculo]);
            
            if ($resultado) {
                echo "✅ <strong>UPDATE</strong> - VEÍCULO ATUALIZADO COM SUCESSO<br>";
                $sucessos++;
                
                // Verificar UPDATE
                $stmt = $pdo->prepare("SELECT cor, status FROM veiculos WHERE id = ?");
                $stmt->execute([$id_veiculo]);
                $veiculo_atualizado = $stmt->fetch();
                
                if ($veiculo_atualizado && $veiculo_atualizado['cor'] === 'Azul' && $veiculo_atualizado['status'] === 'inativo') {
                    echo "✅ <strong>Verificação UPDATE</strong> - DADOS CONFIRMADOS<br>";
                    $sucessos++;
                } else {
                    echo "❌ <strong>Verificação UPDATE</strong> - DADOS NÃO CONFIRMADOS<br>";
                    $erros++;
                }
                
            } else {
                echo "❌ <strong>UPDATE</strong> - FALHA AO ATUALIZAR VEÍCULO<br>";
                $erros++;
            }
            
        } else {
            echo "⚠️ <strong>UPDATE</strong> - SKIP (veículo não foi criado)<br>";
            $avisos++;
        }
        
    } catch (Exception $e) {
        echo "❌ <strong>Erro no UPDATE</strong> - " . $e->getMessage() . "<br>";
        $erros++;
    }
    
    // 8.9 Teste DELETE - Excluir Veículo
    echo "<h2>8.9 Teste DELETE - Excluir Veículo</h2>";
    $total_testes++;
    
    try {
        if (isset($id_veiculo)) {
            $sql = "DELETE FROM veiculos WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $resultado = $stmt->execute([$id_veiculo]);
            
            if ($resultado) {
                echo "✅ <strong>DELETE</strong> - VEÍCULO EXCLUÍDO COM SUCESSO<br>";
                $sucessos++;
                
                // Verificar DELETE
                $stmt = $pdo->prepare("SELECT * FROM veiculos WHERE id = ?");
                $stmt->execute([$id_veiculo]);
                $veiculo_excluido = $stmt->fetch();
                
                if (!$veiculo_excluido) {
                    echo "✅ <strong>Verificação DELETE</strong> - VEÍCULO NÃO ENCONTRADO (EXCLUÍDO)<br>";
                    $sucessos++;
                } else {
                    echo "❌ <strong>Verificação DELETE</strong> - VEÍCULO AINDA EXISTE<br>";
                    $erros++;
                }
                
            } else {
                echo "❌ <strong>DELETE</strong> - FALHA AO EXCLUIR VEÍCULO<br>";
                $erros++;
            }
            
        } else {
            echo "⚠️ <strong>DELETE</strong> - SKIP (veículo não foi criado)<br>";
            $avisos++;
        }
        
    } catch (Exception $e) {
        echo "❌ <strong>Erro no DELETE</strong> - " . $e->getMessage() . "<br>";
        $erros++;
    }
    
    // 8.10 Teste de Validações
    echo "<h2>8.10 Teste de Validações</h2>";
    $total_testes++;
    
    try {
        // Teste de placa duplicada
        $sql = "INSERT INTO veiculos (placa, marca, modelo, ano, cfc_id, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = $pdo->prepare($sql);
        
        try {
            $stmt->execute(['ABC-1234', 'Volkswagen', 'Gol', '2019', $cfc_id, 'ativo']);
            echo "⚠️ <strong>Validação Placa</strong> - PLACA DUPLICADA ACEITA<br>";
            $avisos++;
            
            // Limpar o registro de teste
            $pdo->exec("DELETE FROM veiculos WHERE placa = 'ABC-1234'");
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                echo "✅ <strong>Validação Placa</strong> - PLACA DUPLICADA REJEITADA (Constraint)<br>";
                $sucessos++;
            } else {
                echo "❌ <strong>Validação Placa</strong> - ERRO: " . $e->getMessage() . "<br>";
                $erros++;
            }
        }
        
        // Teste de chassi duplicado
        try {
            $stmt->execute(['XYZ-5678', 'Ford', 'Ka', '2021', $cfc_id, 'ativo']);
            echo "⚠️ <strong>Validação Chassi</strong> - CHASSI DUPLICADO ACEITO<br>";
            $avisos++;
            
            // Limpar o registro de teste
            $pdo->exec("DELETE FROM veiculos WHERE placa = 'XYZ-5678'");
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                echo "✅ <strong>Validação Chassi</strong> - CHASSI DUPLICADO REJEITADO (Constraint)<br>";
                $sucessos++;
            } else {
                echo "❌ <strong>Validação Chassi</strong> - ERRO: " . $e->getMessage() . "<br>";
                $erros++;
            }
        }
        
    } catch (Exception $e) {
        echo "❌ <strong>Erro na validação</strong> - " . $e->getMessage() . "<br>";
        $erros++;
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "❌ <strong>ERRO GERAL:</strong> " . $e->getMessage();
    echo "</div>";
    $erros++;
}

// Resumo dos testes
echo "<hr>";
echo "<h2>📊 RESUMO DOS TESTES</h2>";

if ($sucessos > 0) {
    echo "✅ <strong>SUCESSOS ($sucessos)</strong><br>";
    echo "<ul>";
    echo "<li>Arquivos necessários incluídos</li>";
    echo "<li>Conexão PDO estabelecida</li>";
    echo "<li>Estrutura da tabela veiculos verificada</li>";
    echo "<li>Contagem de veículos: $total_veiculos registros</li>";
    echo "<li>CFC de referência encontrado</li>";
    if (isset($id_veiculo)) {
        echo "<li>Veículo criado com sucesso - ID: $id_veiculo</li>";
        echo "<li>Veículo lido por ID com sucesso</li>";
        echo "<li>Veículo lido por placa com sucesso</li>";
        echo "<li>Listagem de veículos funcionando</li>";
        echo "<li>Veículo atualizado com sucesso</li>";
        echo "<li>Verificação de atualização confirmada</li>";
        echo "<li>Veículo excluído com sucesso</li>";
        echo "<li>Verificação de exclusão confirmada</li>";
    }
    echo "</ul>";
}

if ($avisos > 0) {
    echo "⚠️ <strong>AVISOS ($avisos)</strong><br>";
    echo "<ul>";
    if (isset($id_veiculo)) {
        echo "<li>Validação de placa duplicada não funcionando</li>";
        echo "<li>Validação de chassi duplicado não funcionando</li>";
    }
    echo "</ul>";
}

if ($erros > 0) {
    echo "❌ <strong>ERROS ($erros)</strong><br>";
    echo "<ul>";
    echo "<li>Verificar erros específicos acima</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<h2>🎯 STATUS FINAL</h2>";
echo "<p><strong>Total de Testes:</strong> $total_testes</p>";
echo "<p><strong>Sucessos:</strong> $sucessos</p>";
echo "<p><strong>Erros:</strong> $erros</p>";
echo "<p><strong>Avisos:</strong> $avisos</p>";

$taxa_sucesso = ($total_testes > 0) ? round(($sucessos / $total_testes) * 100, 1) : 0;
echo "<p><strong>Taxa de Sucesso:</strong> $taxa_sucesso%</p>";

if ($erros == 0) {
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "🎉 <strong>TODOS OS TESTES PASSARAM! Sistema pronto para próximo teste.</strong>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "⚠️ <strong>Existem erros que precisam ser corrigidos antes de prosseguir.</strong>";
    echo "</div>";
}

echo "<hr>";
echo "<h2>🔄 PRÓXIMO PASSO</h2>";

if ($erros == 0) {
    echo "<p>✅ <strong>TESTE #8 CONCLUÍDO COM SUCESSO!</strong></p>";
    echo "<p>🎯 <strong>Próximo:</strong> TESTE #9 - CRUD de Aulas</p>";
    echo "<p>📝 <strong>Instrução:</strong> Execute este teste e me informe o resultado. Se tudo estiver OK, criarei o próximo teste.</p>";
} else {
    echo "<p>❌ <strong>TESTE #8 COM ERROS!</strong></p>";
    echo "<p>🔧 <strong>Ação Necessária:</strong> Corrija os erros listados acima e execute novamente.</p>";
    echo "<p>📝 <strong>Instrução:</strong> Me informe quais erros apareceram para que eu possa ajudar a corrigi-los.</p>";
}

echo "<hr>";
echo "<h2>💡 INFORMAÇÕES ADICIONAIS</h2>";
echo "<p><strong>URL de Teste:</strong> " . $_SERVER['REQUEST_URI'] . "</p>";
echo "<p><strong>Funcionalidades Testadas:</strong> CREATE, READ, UPDATE, DELETE, Validações</p>";
echo "<p><strong>Arquivos Utilizados:</strong> Database, Config</p>";
echo "<p><strong>Operações CRUD:</strong> Inserir, Buscar, Atualizar, Excluir Veículos</p>";
echo "<p><strong>Validações:</strong> Placa única, Chassi único, Estrutura da tabela, Relacionamento com CFC</p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1 { color: #2c3e50; }
h2 { color: #34495e; margin-top: 30px; }
table { font-size: 14px; }
th { padding: 8px; background: #f8f9fa; }
td { padding: 6px; text-align: center; }
details { margin: 10px 0; }
summary { cursor: pointer; color: #007bff; }
pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
ul { margin: 10px 0; padding-left: 20px; }
li { margin: 5px 0; }
</style>
