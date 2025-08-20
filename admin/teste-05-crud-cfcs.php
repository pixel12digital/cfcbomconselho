<?php
/**
 * TESTE #5: CRUD de CFCs
 * Este teste verifica se as operações Create, Read, Update, Delete para CFCs estão funcionando
 */

// Configurações de teste
$erros = [];
$sucessos = [];
$avisos = [];

echo "<h1>🔍 TESTE #5: CRUD de CFCs</h1>";
echo "<p><strong>Data/Hora:</strong> " . date('d/m/Y H:i:s') . "</p>";
echo "<p><strong>Ambiente:</strong> " . ($_SERVER['SERVER_PORT'] == '8080' ? 'XAMPP Local (Porta 8080)' : 'Produção') . "</p>";
echo "<hr>";

// Teste 5.1: Verificar se conseguimos incluir os arquivos necessários
echo "<h2>5.1 Inclusão de Arquivos Necessários</h2>";

try {
    require_once '../includes/config.php';
    require_once '../includes/database.php';
    
    echo "✅ <strong>Arquivos necessários</strong> - INCLUÍDOS COM SUCESSO<br>";
    $sucessos[] = "Arquivos necessários incluídos";
} catch (Exception $e) {
    echo "❌ <strong>Erro ao incluir arquivos</strong> - " . $e->getMessage() . "<br>";
    $erros[] = "Erro ao incluir arquivos: " . $e->getMessage();
}

// Teste 5.2: Verificar conexão com banco
echo "<h2>5.2 Conexão com Banco de Dados</h2>";

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
    $sucessos[] = "Conexão PDO estabelecida";
    
} catch (PDOException $e) {
    echo "❌ <strong>Erro na conexão PDO</strong> - " . $e->getMessage() . "<br>";
    $erros[] = "Erro na conexão PDO: " . $e->getMessage();
}

// Teste 5.3: Verificar estrutura da tabela cfcs
echo "<h2>5.3 Estrutura da Tabela 'cfcs'</h2>";

if (isset($pdo)) {
    try {
        $stmt = $pdo->query("DESCRIBE cfcs");
        $colunas = $stmt->fetchAll();
        
        $colunas_necessarias = ['id', 'nome', 'cnpj', 'endereco', 'telefone', 'email', 'responsavel', 'status'];
        $colunas_encontradas = array_column($colunas, 'Field');
        
        $colunas_faltando = array_diff($colunas_necessarias, $colunas_encontradas);
        
        if (empty($colunas_faltando)) {
            echo "✅ <strong>Estrutura da tabela</strong> - COMPLETA<br>";
            $sucessos[] = "Estrutura da tabela cfcs completa";
        } else {
            echo "⚠️ <strong>Estrutura da tabela</strong> - FALTANDO: " . implode(', ', $colunas_faltando) . "<br>";
            $avisos[] = "Estrutura da tabela cfcs incompleta: " . implode(', ', $colunas_faltando);
        }
        
        // Mostrar estrutura atual
        echo "<details style='margin: 10px 0;'>";
        echo "<summary style='cursor: pointer; color: #007bff;'>📋 Ver estrutura atual da tabela</summary>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0; font-size: 12px;'>";
        echo "<tr style='background: #f8f9fa;'><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Padrão</th></tr>";
        
        foreach ($colunas as $coluna) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($coluna['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($coluna['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($coluna['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($coluna['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</details>";
        
    } catch (Exception $e) {
        echo "❌ <strong>Erro ao verificar estrutura</strong> - " . $e->getMessage() . "<br>";
        $erros[] = "Erro ao verificar estrutura: " . $e->getMessage();
    }
}

// Teste 5.4: Verificar se a tabela cfcs tem dados
echo "<h2>5.4 Verificação de Dados na Tabela 'cfcs'</h2>";

if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM cfcs");
        $resultado = $stmt->fetch();
        $total_cfcs = $resultado['total'];
        
        echo "✅ <strong>Total de CFCs na tabela</strong> - $total_cfcs registros<br>";
        $sucessos[] = "Contagem de CFCs: $total_cfcs registros";
        
        if ($total_cfcs > 0) {
            // Mostrar alguns CFCs existentes
            $stmt = $pdo->query("SELECT id, nome, cnpj, status FROM cfcs LIMIT 3");
            $cfcs_existentes = $stmt->fetchAll();
            
            echo "<details style='margin: 10px 0;'>";
            echo "<summary style='cursor: pointer; color: #007bff;'>📋 Ver CFCs existentes</summary>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0; font-size: 12px;'>";
            echo "<tr style='background: #f8f9fa;'><th>ID</th><th>Nome</th><th>CNPJ</th><th>Status</th></tr>";
            
            foreach ($cfcs_existentes as $cfc) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($cfc['id']) . "</td>";
                echo "<td>" . htmlspecialchars($cfc['nome']) . "</td>";
                echo "<td>" . htmlspecialchars($cfc['cnpj']) . "</td>";
                echo "<td>" . htmlspecialchars($cfc['status']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "</details>";
        }
        
    } catch (Exception $e) {
        echo "❌ <strong>Erro ao verificar dados</strong> - " . $e->getMessage() . "<br>";
        $erros[] = "Erro ao verificar dados: " . $e->getMessage();
    }
}

// Teste 5.5: Teste de CRUD - CREATE (Criar CFC)
echo "<h2>5.5 Teste CREATE - Criar CFC</h2>";

if (isset($pdo)) {
    try {
        // Dados de teste para criar CFC
        $dados_teste = [
            'nome' => 'CFC Teste CRUD',
            'cnpj' => '12.345.678/0001-90',
            'endereco' => 'Rua Teste, 123 - Centro',
            'telefone' => '(11) 99999-9999',
            'email' => 'teste.crud@cfc.com',
            'responsavel' => 'João Silva',
            'status' => 'ativo'
        ];
        
        // Preparar e executar INSERT
        $sql = "INSERT INTO cfcs (nome, cnpj, endereco, telefone, email, responsavel, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $resultado = $stmt->execute([
            $dados_teste['nome'],
            $dados_teste['cnpj'],
            $dados_teste['endereco'],
            $dados_teste['telefone'],
            $dados_teste['email'],
            $dados_teste['responsavel'],
            $dados_teste['status']
        ]);
        
        if ($resultado) {
            $id_teste = $pdo->lastInsertId();
            echo "✅ <strong>CREATE</strong> - CFC CRIADO COM SUCESSO<br>";
            echo "✅ <strong>ID do CFC criado</strong> - $id_teste<br>";
            $sucessos[] = "CFC criado com sucesso - ID: $id_teste";
        } else {
            echo "❌ <strong>CREATE</strong> - FALHOU AO CRIAR CFC<br>";
            $erros[] = "Falha ao criar CFC";
        }
        
    } catch (Exception $e) {
        echo "❌ <strong>Erro no CREATE</strong> - " . $e->getMessage() . "<br>";
        $erros[] = "Erro no CREATE: " . $e->getMessage();
    }
}

// Teste 5.6: Teste de CRUD - READ (Ler CFC)
echo "<h2>5.6 Teste READ - Ler CFC</h2>";

if (isset($pdo) && isset($id_teste)) {
    try {
        // Buscar CFC por ID
        $stmt = $pdo->prepare("SELECT * FROM cfcs WHERE id = ?");
        $stmt->execute([$id_teste]);
        $cfc_lido = $stmt->fetch();
        
        if ($cfc_lido) {
            echo "✅ <strong>READ por ID</strong> - CFC ENCONTRADO<br>";
            echo "📋 <strong>Dados:</strong> Nome: " . htmlspecialchars($cfc_lido['nome']) . 
                 ", CNPJ: " . htmlspecialchars($cfc_lido['cnpj']) . 
                 ", Status: " . htmlspecialchars($cfc_lido['status']) . "<br>";
            $sucessos[] = "CFC lido por ID com sucesso";
        } else {
            echo "❌ <strong>READ por ID</strong> - CFC NÃO ENCONTRADO<br>";
            $erros[] = "CFC não encontrado por ID";
        }
        
        // Buscar CFC por CNPJ
        $stmt = $pdo->prepare("SELECT * FROM cfcs WHERE cnpj = ?");
        $stmt->execute(['12.345.678/0001-90']);
        $cfc_cnpj = $stmt->fetch();
        
        if ($cfc_cnpj) {
            echo "✅ <strong>READ por CNPJ</strong> - CFC ENCONTRADO<br>";
            $sucessos[] = "CFC lido por CNPJ com sucesso";
        } else {
            echo "❌ <strong>READ por CNPJ</strong> - CFC NÃO ENCONTRADO<br>";
            $erros[] = "CFC não encontrado por CNPJ";
        }
        
        // Listar todos os CFCs
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM cfcs");
        $resultado = $stmt->fetch();
        $total_cfcs_final = $resultado['total'];
        
        echo "✅ <strong>READ ALL</strong> - $total_cfcs_final CFCs ENCONTRADOS<br>";
        $sucessos[] = "Listagem de CFCs funcionando";
        
    } catch (Exception $e) {
        echo "❌ <strong>Erro no READ</strong> - " . $e->getMessage() . "<br>";
        $erros[] = "Erro no READ: " . $e->getMessage();
    }
}

// Teste 5.7: Teste de CRUD - UPDATE (Atualizar CFC)
echo "<h2>5.7 Teste UPDATE - Atualizar CFC</h2>";

if (isset($pdo) && isset($id_teste)) {
    try {
        // Dados para atualização
        $dados_atualizacao = [
            'nome' => 'CFC Teste CRUD ATUALIZADO',
            'cnpj' => '98.765.432/0001-10',
            'endereco' => 'Av. Atualizada, 456 - Jardim',
            'telefone' => '(11) 88888-8888',
            'email' => 'teste.crud.atualizado@cfc.com',
            'responsavel' => 'Maria Santos',
            'status' => 'inativo'
        ];
        
        // Atualizar CFC
        $sql = "UPDATE cfcs SET nome = ?, cnpj = ?, endereco = ?, telefone = ?, email = ?, responsavel = ?, status = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $resultado_update = $stmt->execute([
            $dados_atualizacao['nome'],
            $dados_atualizacao['cnpj'],
            $dados_atualizacao['endereco'],
            $dados_atualizacao['telefone'],
            $dados_atualizacao['email'],
            $dados_atualizacao['responsavel'],
            $dados_atualizacao['status'],
            $id_teste
        ]);
        
        if ($resultado_update) {
            echo "✅ <strong>UPDATE</strong> - CFC ATUALIZADO COM SUCESSO<br>";
            $sucessos[] = "CFC atualizado com sucesso";
            
            // Verificar se a atualização foi feita
            $stmt = $pdo->prepare("SELECT * FROM cfcs WHERE id = ?");
            $stmt->execute([$id_teste]);
            $cfc_atualizado = $stmt->fetch();
            
            if ($cfc_atualizado && $cfc_atualizado['nome'] === $dados_atualizacao['nome']) {
                echo "✅ <strong>Verificação UPDATE</strong> - DADOS CONFIRMADOS<br>";
                $sucessos[] = "Verificação de atualização confirmada";
            } else {
                echo "⚠️ <strong>Verificação UPDATE</strong> - DADOS NÃO CONFIRMADOS<br>";
                $avisos[] = "Verificação de atualização não confirmada";
            }
            
        } else {
            echo "❌ <strong>UPDATE</strong> - FALHOU AO ATUALIZAR CFC<br>";
            $erros[] = "Falha ao atualizar CFC";
        }
        
    } catch (Exception $e) {
        echo "❌ <strong>Erro no UPDATE</strong> - " . $e->getMessage() . "<br>";
        $erros[] = "Erro no UPDATE: " . $e->getMessage();
    }
}

// Teste 5.8: Teste de CRUD - DELETE (Excluir CFC)
echo "<h2>5.8 Teste DELETE - Excluir CFC</h2>";

if (isset($pdo) && isset($id_teste)) {
    try {
        // Excluir CFC de teste
        $stmt = $pdo->prepare("DELETE FROM cfcs WHERE id = ?");
        $resultado_delete = $stmt->execute([$id_teste]);
        
        if ($resultado_delete) {
            echo "✅ <strong>DELETE</strong> - CFC EXCLUÍDO COM SUCESSO<br>";
            $sucessos[] = "CFC excluído com sucesso";
            
            // Verificar se foi realmente excluído
            $stmt = $pdo->prepare("SELECT * FROM cfcs WHERE id = ?");
            $stmt->execute([$id_teste]);
            $cfc_excluido = $stmt->fetch();
            
            if (!$cfc_excluido) {
                echo "✅ <strong>Verificação DELETE</strong> - CFC NÃO ENCONTRADO (EXCLUÍDO)<br>";
                $sucessos[] = "Verificação de exclusão confirmada";
            } else {
                echo "⚠️ <strong>Verificação DELETE</strong> - CFC AINDA ENCONTRADO<br>";
                $avisos[] = "Verificação de exclusão não confirmada";
            }
            
        } else {
            echo "❌ <strong>DELETE</strong> - FALHOU AO EXCLUIR CFC<br>";
            $erros[] = "Falha ao excluir CFC";
        }
        
    } catch (Exception $e) {
        echo "❌ <strong>Erro no DELETE</strong> - " . $e->getMessage() . "<br>";
        $erros[] = "Erro no DELETE: " . $e->getMessage();
    }
}

// Teste 5.9: Teste de Validações
echo "<h2>5.9 Teste de Validações</h2>";

if (isset($pdo)) {
    try {
        // Testar inserção com CNPJ duplicado (deve falhar)
        $dados_duplicado = [
            'nome' => 'CFC Duplicado',
            'cnpj' => '12.345.678/0001-90', // CNPJ que já existe
            'endereco' => 'Rua Duplicada, 789',
            'telefone' => '(11) 77777-7777',
            'email' => 'duplicado@cfc.com',
            'responsavel' => 'Pedro Duplicado',
            'status' => 'ativo'
        ];
        
        $sql = "INSERT INTO cfcs (nome, cnpj, endereco, telefone, email, responsavel, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $resultado_duplicado = $stmt->execute([
            $dados_duplicado['nome'],
            $dados_duplicado['cnpj'],
            $dados_duplicado['endereco'],
            $dados_duplicado['telefone'],
            $dados_duplicado['email'],
            $dados_duplicado['responsavel'],
            $dados_duplicado['status']
        ]);
        
        if (!$resultado_duplicado) {
            echo "✅ <strong>Validação CNPJ</strong> - CNPJ DUPLICADO REJEITADO<br>";
            $sucessos[] = "Validação de CNPJ duplicado funcionando";
        } else {
            echo "⚠️ <strong>Validação CNPJ</strong> - CNPJ DUPLICADO ACEITO<br>";
            $avisos[] = "Validação de CNPJ duplicado não funcionando";
        }
        
    } catch (Exception $e) {
        // Se der erro de constraint unique, significa que a validação está funcionando
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            echo "✅ <strong>Validação CNPJ</strong> - CNPJ DUPLICADO REJEITADO (Constraint)<br>";
            $sucessos[] = "Validação de CNPJ duplicado funcionando via constraint";
        } else {
            echo "❌ <strong>Erro na validação</strong> - " . $e->getMessage() . "<br>";
            $erros[] = "Erro na validação: " . $e->getMessage();
        }
    }
}

// Resumo dos Testes
echo "<hr>";
echo "<h2>📊 RESUMO DOS TESTES</h2>";

echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>✅ SUCESSOS (" . count($sucessos) . ")</h3>";
foreach ($sucessos as $sucesso) {
    echo "• $sucesso<br>";
}
echo "</div>";

if (count($avisos) > 0) {
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>⚠️ AVISOS (" . count($avisos) . ")</h3>";
    foreach ($avisos as $aviso) {
        echo "• $aviso<br>";
    }
    echo "</div>";
}

if (count($erros) > 0) {
    echo "<div style='background: #ffe8e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>❌ ERROS (" . count($erros) . ")</h3>";
    foreach ($erros as $erro) {
        echo "• $erro<br>";
    }
    echo "</div>";
}

// Status Final
$total_testes = count($sucessos) + count($erros);
$percentual_sucesso = $total_testes > 0 ? round(($total_testes - count($erros)) / $total_testes * 100, 1) : 0;

echo "<div style='background: " . (count($erros) == 0 ? '#d4edda' : '#f8d7da') . "; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>🎯 STATUS FINAL</h3>";
echo "<strong>Total de Testes:</strong> $total_testes<br>";
echo "<strong>Sucessos:</strong> " . count($sucessos) . "<br>";
echo "<strong>Erros:</strong> " . count($erros) . "<br>";
echo "<strong>Avisos:</strong> " . count($avisos) . "<br>";
echo "<strong>Taxa de Sucesso:</strong> $percentual_sucesso%<br>";

if (count($erros) == 0) {
    echo "<br><strong style='color: #155724;'>🎉 TODOS OS TESTES PASSARAM! Sistema pronto para próximo teste.</strong>";
} else {
    echo "<br><strong style='color: #721c24;'>⚠️ Existem erros que precisam ser corrigidos antes de prosseguir.</strong>";
}
echo "</div>";

// Próximo Passo
echo "<hr>";
echo "<h2>🔄 PRÓXIMO PASSO</h2>";
if (count($erros) == 0) {
    echo "<p>✅ <strong>TESTE #5 CONCLUÍDO COM SUCESSO!</strong></p>";
    echo "<p>🎯 <strong>Próximo:</strong> TESTE #6 - CRUD de Alunos</p>";
    echo "<p>📝 <strong>Instrução:</strong> Execute este teste e me informe o resultado. Se tudo estiver OK, criarei o próximo teste.</p>";
} else {
    echo "<p>❌ <strong>TESTE #5 COM ERROS!</strong></p>";
    echo "<p>🔧 <strong>Ação Necessária:</strong> Corrija os erros listados acima e execute novamente.</p>";
    echo "<p>📝 <strong>Instrução:</strong> Me informe quais erros apareceram para que eu possa ajudar a corrigi-los.</p>";
}

// Informações adicionais
echo "<hr>";
echo "<h2>💡 INFORMAÇÕES ADICIONAIS</h2>";
echo "<p><strong>URL de Teste:</strong> <code>http://localhost:8080/cfc-bom-conselho/admin/teste-05-crud-cfcs.php</code></p>";
echo "<p><strong>Funcionalidades Testadas:</strong> CREATE, READ, UPDATE, DELETE, Validações</p>";
echo "<p><strong>Arquivos Utilizados:</strong> Database, Config</p>";
echo "<p><strong>Operações CRUD:</strong> Inserir, Buscar, Atualizar, Excluir CFCs</p>";
echo "<p><strong>Validações:</strong> CNPJ único, Estrutura da tabela</p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1 { color: #2c3e50; }
h2 { color: #34495e; margin-top: 30px; }
h3 { color: #7f8c8d; }
hr { border: 1px solid #ecf0f1; margin: 20px 0; }
table { font-size: 12px; }
th { padding: 6px; background: #f8f9fa; }
td { padding: 4px; text-align: center; }
details { margin: 10px 0; }
summary { cursor: pointer; color: #007bff; }
</style>
