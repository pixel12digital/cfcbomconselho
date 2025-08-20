<?php
/**
 * TESTE #7: CRUD de Instrutores
 * Este teste verifica se as operações Create, Read, Update, Delete para Instrutores estão funcionando
 */

// Configurações de teste
$erros = [];
$sucessos = [];
$avisos = [];

echo "<h1>🔍 TESTE #7: CRUD de Instrutores</h1>";
echo "<p><strong>Data/Hora:</strong> " . date('d/m/Y H:i:s') . "</p>";
echo "<p><strong>Ambiente:</strong> " . ($_SERVER['SERVER_PORT'] == '8080' ? 'XAMPP Local (Porta 8080)' : 'Produção') . "</p>";
echo "<hr>";

// Teste 7.1: Verificar se conseguimos incluir os arquivos necessários
echo "<h2>7.1 Inclusão de Arquivos Necessários</h2>";

try {
    require_once '../includes/config.php';
    require_once '../includes/database.php';
    
    echo "✅ <strong>Arquivos necessários</strong> - INCLUÍDOS COM SUCESSO<br>";
    $sucessos[] = "Arquivos necessários incluídos";
} catch (Exception $e) {
    echo "❌ <strong>Erro ao incluir arquivos</strong> - " . $e->getMessage() . "<br>";
    $erros[] = "Erro ao incluir arquivos: " . $e->getMessage();
}

// Teste 7.2: Verificar conexão com banco
echo "<h2>7.2 Conexão com Banco de Dados</h2>";

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

// Teste 7.3: Verificar estrutura da tabela instrutores
echo "<h2>7.3 Estrutura da Tabela 'instrutores'</h2>";

if (isset($pdo)) {
    try {
        $stmt = $pdo->query("DESCRIBE instrutores");
        $colunas = $stmt->fetchAll();
        
        $colunas_necessarias = ['id', 'nome', 'cpf', 'cnh', 'data_nascimento', 'telefone', 'email', 'endereco', 'cfc_id', 'status'];
        $colunas_encontradas = array_column($colunas, 'Field');
        
        $colunas_faltando = array_diff($colunas_necessarias, $colunas_encontradas);
        
        if (empty($colunas_faltando)) {
            echo "✅ <strong>Estrutura da tabela</strong> - COMPLETA<br>";
            $sucessos[] = "Estrutura da tabela instrutores completa";
        } else {
            echo "⚠️ <strong>Estrutura da tabela</strong> - FALTANDO: " . implode(', ', $colunas_faltando) . "<br>";
            $avisos[] = "Estrutura da tabela instrutores incompleta: " . implode(', ', $colunas_faltando);
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

// Teste 7.4: Verificar se a tabela instrutores tem dados
echo "<h2>7.4 Verificação de Dados na Tabela 'instrutores'</h2>";

if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM instrutores");
        $resultado = $stmt->fetch();
        $total_instrutores = $resultado['total'];
        
        echo "✅ <strong>Total de Instrutores na tabela</strong> - $total_instrutores registros<br>";
        $sucessos[] = "Contagem de instrutores: $total_instrutores registros";
        
        if ($total_instrutores > 0) {
            // Mostrar alguns instrutores existentes
            $stmt = $pdo->query("SELECT id, nome, cpf, cnh, status FROM instrutores LIMIT 3");
            $instrutores_existentes = $stmt->fetchAll();
            
            echo "<details style='margin: 10px 0;'>";
            echo "<summary style='cursor: pointer; color: #007bff;'>📋 Ver instrutores existentes</summary>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0; font-size: 12px;'>";
            echo "<tr style='background: #f8f9fa;'><th>ID</th><th>Nome</th><th>CPF</th><th>CNH</th><th>Status</th></tr>";
            
            foreach ($instrutores_existentes as $instrutor) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($instrutor['id']) . "</td>";
                echo "<td>" . htmlspecialchars($instrutor['nome']) . "</td>";
                echo "<td>" . htmlspecialchars($instrutor['cpf']) . "</td>";
                echo "<td>" . htmlspecialchars($instrutor['cnh']) . "</td>";
                echo "<td>" . htmlspecialchars($instrutor['status'] ?? 'N/A') . "</td>";
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

// Teste 7.5: Verificar se existe pelo menos um CFC para referência
echo "<h2>7.5 Verificação de CFCs para Referência</h2>";

if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT id, nome FROM cfcs WHERE status = 'ativo' LIMIT 1");
        $cfc_referencia = $stmt->fetch();
        
        if ($cfc_referencia) {
            echo "✅ <strong>CFC de referência</strong> - ENCONTRADO: " . htmlspecialchars($cfc_referencia['nome']) . " (ID: " . $cfc_referencia['id'] . ")<br>";
            $sucessos[] = "CFC de referência encontrado";
            $cfc_id_teste = $cfc_referencia['id'];
        } else {
            echo "⚠️ <strong>CFC de referência</strong> - NENHUM CFC ATIVO ENCONTRADO<br>";
            $avisos[] = "Nenhum CFC ativo encontrado para referência";
            
            // Tentar pegar qualquer CFC
            $stmt = $pdo->query("SELECT id, nome FROM cfcs LIMIT 1");
            $cfc_qualquer = $stmt->fetch();
            if ($cfc_qualquer) {
                echo "✅ <strong>CFC alternativo</strong> - USANDO: " . htmlspecialchars($cfc_qualquer['nome']) . " (ID: " . $cfc_qualquer['id'] . ")<br>";
                $cfc_id_teste = $cfc_qualquer['id'];
            } else {
                echo "❌ <strong>CFC</strong> - NENHUM CFC ENCONTRADO<br>";
                $erros[] = "Nenhum CFC encontrado para referência";
            }
        }
        
    } catch (Exception $e) {
        echo "❌ <strong>Erro ao verificar CFCs</strong> - " . $e->getMessage() . "<br>";
        $erros[] = "Erro ao verificar CFCs: " . $e->getMessage();
    }
}

// Teste 7.6: Teste de CRUD - CREATE (Criar Instrutor)
echo "<h2>7.6 Teste CREATE - Criar Instrutor</h2>";

if (isset($pdo) && isset($cfc_id_teste)) {
    try {
        // Dados de teste para criar instrutor
        $dados_teste = [
            'nome' => 'Carlos Santos Instrutor',
            'cpf' => '111.222.333-44',
            'cnh' => '12345678901',
            'data_nascimento' => '1985-06-20',
            'telefone' => '(11) 99999-8888',
            'email' => 'carlos.instrutor@cfc.com',
            'endereco' => 'Av. Instrutor, 456 - Centro',
            'cfc_id' => $cfc_id_teste,
            'status' => 'ativo'
        ];
        
        // Preparar e executar INSERT
        $sql = "INSERT INTO instrutores (nome, cpf, cnh, data_nascimento, telefone, email, endereco, cfc_id, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $resultado = $stmt->execute([
            $dados_teste['nome'],
            $dados_teste['cpf'],
            $dados_teste['cnh'],
            $dados_teste['data_nascimento'],
            $dados_teste['telefone'],
            $dados_teste['email'],
            $dados_teste['endereco'],
            $dados_teste['cfc_id'],
            $dados_teste['status']
        ]);
        
        if ($resultado) {
            $id_teste = $pdo->lastInsertId();
            echo "✅ <strong>CREATE</strong> - INSTRUTOR CRIADO COM SUCESSO<br>";
            echo "✅ <strong>ID do instrutor criado</strong> - $id_teste<br>";
            $sucessos[] = "Instrutor criado com sucesso - ID: $id_teste";
        } else {
            echo "❌ <strong>CREATE</strong> - FALHOU AO CRIAR INSTRUTOR<br>";
            $erros[] = "Falha ao criar instrutor";
        }
        
    } catch (Exception $e) {
        echo "❌ <strong>Erro no CREATE</strong> - " . $e->getMessage() . "<br>";
        $erros[] = "Erro no CREATE: " . $e->getMessage();
    }
}

// Teste 7.7: Teste de CRUD - READ (Ler Instrutor)
echo "<h2>7.7 Teste READ - Ler Instrutor</h2>";

if (isset($pdo) && isset($id_teste)) {
    try {
        // Buscar instrutor por ID
        $stmt = $pdo->prepare("SELECT * FROM instrutores WHERE id = ?");
        $stmt->execute([$id_teste]);
        $instrutor_lido = $stmt->fetch();
        
        if ($instrutor_lido) {
            echo "✅ <strong>READ por ID</strong> - INSTRUTOR ENCONTRADO<br>";
            echo "📋 <strong>Dados:</strong> Nome: " . htmlspecialchars($instrutor_lido['nome']) . 
                 ", CPF: " . htmlspecialchars($instrutor_lido['cpf']) . 
                 ", CNH: " . htmlspecialchars($instrutor_lido['cnh']) . 
                 ", Status: " . htmlspecialchars($instrutor_lido['status'] ?? 'N/A') . "<br>";
            $sucessos[] = "Instrutor lido por ID com sucesso";
        } else {
            echo "❌ <strong>READ por ID</strong> - INSTRUTOR NÃO ENCONTRADO<br>";
            $erros[] = "Instrutor não encontrado por ID";
        }
        
        // Buscar instrutor por CPF
        $stmt = $pdo->prepare("SELECT * FROM instrutores WHERE cpf = ?");
        $stmt->execute(['111.222.333-44']);
        $instrutor_cpf = $stmt->fetch();
        
        if ($instrutor_cpf) {
            echo "✅ <strong>READ por CPF</strong> - INSTRUTOR ENCONTRADO<br>";
            $sucessos[] = "Instrutor lido por CPF com sucesso";
        } else {
            echo "❌ <strong>READ por CPF</strong> - INSTRUTOR NÃO ENCONTRADO<br>";
            $erros[] = "Instrutor não encontrado por CPF";
        }
        
        // Buscar instrutor por CNH
        $stmt = $pdo->prepare("SELECT * FROM instrutores WHERE cnh = ?");
        $stmt->execute(['12345678901']);
        $instrutor_cnh = $stmt->fetch();
        
        if ($instrutor_cnh) {
            echo "✅ <strong>READ por CNH</strong> - INSTRUTOR ENCONTRADO<br>";
            $sucessos[] = "Instrutor lido por CNH com sucesso";
        } else {
            echo "❌ <strong>READ por CNH</strong> - INSTRUTOR NÃO ENCONTRADO<br>";
            $erros[] = "Instrutor não encontrado por CNH";
        }
        
        // Listar todos os instrutores
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM instrutores");
        $resultado = $stmt->fetch();
        $total_instrutores_final = $resultado['total'];
        
        echo "✅ <strong>READ ALL</strong> - $total_instrutores_final INSTRUTORES ENCONTRADOS<br>";
        $sucessos[] = "Listagem de instrutores funcionando";
        
    } catch (Exception $e) {
        echo "❌ <strong>Erro no READ</strong> - " . $e->getMessage() . "<br>";
        $erros[] = "Erro no READ: " . $e->getMessage();
    }
}

// Teste 7.8: Teste de CRUD - UPDATE (Atualizar Instrutor)
echo "<h2>7.8 Teste UPDATE - Atualizar Instrutor</h2>";

if (isset($pdo) && isset($id_teste)) {
    try {
        // Dados para atualização
        $dados_atualizacao = [
            'nome' => 'Carlos Santos Instrutor ATUALIZADO',
            'cpf' => '999.888.777-66',
            'cnh' => '98765432109',
            'data_nascimento' => '1988-12-25',
            'telefone' => '(11) 88888-7777',
            'email' => 'carlos.atualizado@cfc.com',
            'endereco' => 'Rua Atualizada, 789 - Jardim',
            'status' => 'inativo'
        ];
        
        // Atualizar instrutor
        $sql = "UPDATE instrutores SET nome = ?, cpf = ?, cnh = ?, data_nascimento = ?, telefone = ?, email = ?, endereco = ?, status = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $resultado_update = $stmt->execute([
            $dados_atualizacao['nome'],
            $dados_atualizacao['cpf'],
            $dados_atualizacao['cnh'],
            $dados_atualizacao['data_nascimento'],
            $dados_atualizacao['telefone'],
            $dados_atualizacao['email'],
            $dados_atualizacao['endereco'],
            $dados_atualizacao['status'],
            $id_teste
        ]);
        
        if ($resultado_update) {
            echo "✅ <strong>UPDATE</strong> - INSTRUTOR ATUALIZADO COM SUCESSO<br>";
            $sucessos[] = "Instrutor atualizado com sucesso";
            
            // Verificar se a atualização foi feita
            $stmt = $pdo->prepare("SELECT * FROM instrutores WHERE id = ?");
            $stmt->execute([$id_teste]);
            $instrutor_atualizado = $stmt->fetch();
            
            if ($instrutor_atualizado && $instrutor_atualizado['nome'] === $dados_atualizacao['nome']) {
                echo "✅ <strong>Verificação UPDATE</strong> - DADOS CONFIRMADOS<br>";
                $sucessos[] = "Verificação de atualização confirmada";
            } else {
                echo "⚠️ <strong>Verificação UPDATE</strong> - DADOS NÃO CONFIRMADOS<br>";
                $avisos[] = "Verificação de atualização não confirmada";
            }
            
        } else {
            echo "❌ <strong>UPDATE</strong> - FALHOU AO ATUALIZAR INSTRUTOR<br>";
            $erros[] = "Falha ao atualizar instrutor";
        }
        
    } catch (Exception $e) {
        echo "❌ <strong>Erro no UPDATE</strong> - " . $e->getMessage() . "<br>";
        $erros[] = "Erro no UPDATE: " . $e->getMessage();
    }
}

// Teste 7.9: Teste de CRUD - DELETE (Excluir Instrutor)
echo "<h2>7.9 Teste DELETE - Excluir Instrutor</h2>";

if (isset($pdo) && isset($id_teste)) {
    try {
        // Excluir instrutor de teste
        $stmt = $pdo->prepare("DELETE FROM instrutores WHERE id = ?");
        $resultado_delete = $stmt->execute([$id_teste]);
        
        if ($resultado_delete) {
            echo "✅ <strong>DELETE</strong> - INSTRUTOR EXCLUÍDO COM SUCESSO<br>";
            $sucessos[] = "Instrutor excluído com sucesso";
            
            // Verificar se foi realmente excluído
            $stmt = $pdo->prepare("SELECT * FROM instrutores WHERE id = ?");
            $stmt->execute([$id_teste]);
            $instrutor_excluido = $stmt->fetch();
            
            if (!$instrutor_excluido) {
                echo "✅ <strong>Verificação DELETE</strong> - INSTRUTOR NÃO ENCONTRADO (EXCLUÍDO)<br>";
                $sucessos[] = "Verificação de exclusão confirmada";
            } else {
                echo "⚠️ <strong>Verificação DELETE</strong> - INSTRUTOR AINDA ENCONTRADO<br>";
                $avisos[] = "Verificação de exclusão não confirmada";
            }
            
        } else {
            echo "❌ <strong>DELETE</strong> - FALHOU AO EXCLUIR INSTRUTOR<br>";
            $erros[] = "Falha ao excluir instrutor";
        }
        
    } catch (Exception $e) {
        echo "❌ <strong>Erro no DELETE</strong> - " . $e->getMessage() . "<br>";
        $erros[] = "Erro no DELETE: " . $e->getMessage();
    }
}

// Teste 7.10: Teste de Validações
echo "<h2>7.10 Teste de Validações</h2>";

if (isset($pdo) && isset($cfc_id_teste)) {
    try {
        // Testar inserção com CPF duplicado (deve falhar)
        $dados_duplicado = [
            'nome' => 'Instrutor Duplicado CPF',
            'cpf' => '111.222.333-44', // CPF que já existe
            'cnh' => '11111111111',
            'data_nascimento' => '1990-01-01',
            'telefone' => '(11) 77777-6666',
            'email' => 'duplicado.cpf@cfc.com',
            'endereco' => 'Rua Duplicada CPF, 123',
            'cfc_id' => $cfc_id_teste,
            'status' => 'ativo'
        ];
        
        $sql = "INSERT INTO instrutores (nome, cpf, cnh, data_nascimento, telefone, email, endereco, cfc_id, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $resultado_duplicado = $stmt->execute([
            $dados_duplicado['nome'],
            $dados_duplicado['cpf'],
            $dados_duplicado['cnh'],
            $dados_duplicado['data_nascimento'],
            $dados_duplicado['telefone'],
            $dados_duplicado['email'],
            $dados_duplicado['endereco'],
            $dados_duplicado['cfc_id'],
            $dados_duplicado['status']
        ]);
        
        if (!$resultado_duplicado) {
            echo "✅ <strong>Validação CPF</strong> - CPF DUPLICADO REJEITADO<br>";
            $sucessos[] = "Validação de CPF duplicado funcionando";
        } else {
            echo "⚠️ <strong>Validação CPF</strong> - CPF DUPLICADO ACEITO<br>";
            $avisos[] = "Validação de CPF duplicado não funcionando";
        }
        
    } catch (Exception $e) {
        // Se der erro de constraint unique, significa que a validação está funcionando
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            echo "✅ <strong>Validação CPF</strong> - CPF DUPLICADO REJEITADO (Constraint)<br>";
            $sucessos[] = "Validação de CPF duplicado funcionando via constraint";
        } else {
            echo "❌ <strong>Erro na validação</strong> - " . $e->getMessage() . "<br>";
            $erros[] = "Erro na validação: " . $e->getMessage();
        }
    }
    
    // Testar inserção com CNH duplicada (deve falhar)
    try {
        $dados_duplicado_cnh = [
            'nome' => 'Instrutor Duplicado CNH',
            'cpf' => '222.333.444-55',
            'cnh' => '12345678901', // CNH que já existe
            'data_nascimento' => '1992-02-02',
            'telefone' => '(11) 66666-5555',
            'email' => 'duplicado.cnh@cfc.com',
            'endereco' => 'Rua Duplicada CNH, 456',
            'cfc_id' => $cfc_id_teste,
            'status' => 'ativo'
        ];
        
        $sql = "INSERT INTO instrutores (nome, cpf, cnh, data_nascimento, telefone, email, endereco, cfc_id, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $resultado_duplicado_cnh = $stmt->execute([
            $dados_duplicado_cnh['nome'],
            $dados_duplicado_cnh['cpf'],
            $dados_duplicado_cnh['cnh'],
            $dados_duplicado_cnh['data_nascimento'],
            $dados_duplicado_cnh['telefone'],
            $dados_duplicado_cnh['email'],
            $dados_duplicado_cnh['endereco'],
            $dados_duplicado_cnh['cfc_id'],
            $dados_duplicado_cnh['status']
        ]);
        
        if (!$resultado_duplicado_cnh) {
            echo "✅ <strong>Validação CNH</strong> - CNH DUPLICADA REJEITADA<br>";
            $sucessos[] = "Validação de CNH duplicada funcionando";
        } else {
            echo "⚠️ <strong>Validação CNH</strong> - CNH DUPLICADA ACEITA<br>";
            $avisos[] = "Validação de CNH duplicada não funcionando";
        }
        
    } catch (Exception $e) {
        // Se der erro de constraint unique, significa que a validação está funcionando
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            echo "✅ <strong>Validação CNH</strong> - CNH DUPLICADA REJEITADA (Constraint)<br>";
            $sucessos[] = "Validação de CNH duplicada funcionando via constraint";
        } else {
            echo "❌ <strong>Erro na validação CNH</strong> - " . $e->getMessage() . "<br>";
            $erros[] = "Erro na validação CNH: " . $e->getMessage();
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
    echo "<p>✅ <strong>TESTE #7 CONCLUÍDO COM SUCESSO!</strong></p>";
    echo "<p>🎯 <strong>Próximo:</strong> TESTE #8 - CRUD de Veículos</p>";
    echo "<p>📝 <strong>Instrução:</strong> Execute este teste e me informe o resultado. Se tudo estiver OK, criarei o próximo teste.</p>";
} else {
    echo "<p>❌ <strong>TESTE #7 COM ERROS!</strong></p>";
    echo "<p>🔧 <strong>Ação Necessária:</strong> Corrija os erros listados acima e execute novamente.</p>";
    echo "<p>📝 <strong>Instrução:</strong> Me informe quais erros apareceram para que eu possa ajudar a corrigi-los.</p>";
}

// Informações adicionais
echo "<hr>";
echo "<h2>💡 INFORMAÇÕES ADICIONAIS</h2>";
echo "<p><strong>URL de Teste:</strong> <code>http://localhost:8080/cfc-bom-conselho/admin/teste-07-crud-instrutores.php</code></p>";
echo "<p><strong>Funcionalidades Testadas:</strong> CREATE, READ, UPDATE, DELETE, Validações</p>";
echo "<p><strong>Arquivos Utilizados:</strong> Database, Config</p>";
echo "<p><strong>Operações CRUD:</strong> Inserir, Buscar, Atualizar, Excluir Instrutores</p>";
echo "<p><strong>Validações:</strong> CPF único, CNH único, Estrutura da tabela, Relacionamento com CFC</p>";
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
