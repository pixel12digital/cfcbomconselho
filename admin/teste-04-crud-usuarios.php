<?php
/**
 * TESTE #4: CRUD de Usuários
 * Este teste verifica se as operações Create, Read, Update, Delete estão funcionando
 */

// Configurações de teste
$erros = [];
$sucessos = [];
$avisos = [];

echo "<h1>🔍 TESTE #4: CRUD de Usuários</h1>";
echo "<p><strong>Data/Hora:</strong> " . date('d/m/Y H:i:s') . "</p>";
echo "<p><strong>Ambiente:</strong> " . ($_SERVER['SERVER_PORT'] == '8080' ? 'XAMPP Local (Porta 8080)' : 'Produção') . "</p>";
echo "<hr>";

// Teste 4.1: Verificar se conseguimos incluir os arquivos necessários
echo "<h2>4.1 Inclusão de Arquivos Necessários</h2>";

try {
    require_once '../includes/config.php';
    require_once '../includes/database.php';
    require_once '../includes/models/UserModel.php';
    
    echo "✅ <strong>Arquivos necessários</strong> - INCLUÍDOS COM SUCESSO<br>";
    $sucessos[] = "Arquivos necessários incluídos";
} catch (Exception $e) {
    echo "❌ <strong>Erro ao incluir arquivos</strong> - " . $e->getMessage() . "<br>";
    $erros[] = "Erro ao incluir arquivos: " . $e->getMessage();
}

// Teste 4.2: Verificar conexão com banco
echo "<h2>4.2 Conexão com Banco de Dados</h2>";

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

// Teste 4.3: Verificar estrutura da tabela usuarios
echo "<h2>4.3 Estrutura da Tabela 'usuarios'</h2>";

if (isset($pdo)) {
    try {
        $stmt = $pdo->query("DESCRIBE usuarios");
        $colunas = $stmt->fetchAll();
        
        $colunas_necessarias = ['id', 'nome', 'email', 'senha', 'tipo', 'status', 'created_at', 'updated_at'];
        $colunas_encontradas = array_column($colunas, 'Field');
        
        $colunas_faltando = array_diff($colunas_necessarias, $colunas_encontradas);
        
        if (empty($colunas_faltando)) {
            echo "✅ <strong>Estrutura da tabela</strong> - COMPLETA<br>";
            $sucessos[] = "Estrutura da tabela usuarios completa";
        } else {
            echo "⚠️ <strong>Estrutura da tabela</strong> - FALTANDO: " . implode(', ', $colunas_faltando) . "<br>";
            $avisos[] = "Estrutura da tabela usuarios incompleta: " . implode(', ', $colunas_faltando);
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

// Teste 4.4: Verificar se a classe UserModel está funcionando
echo "<h2>4.4 Verificação da Classe UserModel</h2>";

if (class_exists('UserModel')) {
    try {
        $userModel = new UserModel($pdo);
        echo "✅ <strong>Instância UserModel</strong> - CRIADA COM SUCESSO<br>";
        $sucessos[] = "Instância UserModel criada";
        
        // Verificar métodos
        $metodos_necessarios = ['findByEmail', 'findById', 'findAll', 'create', 'update', 'delete', 'authenticate'];
        $metodos_encontrados = get_class_methods('UserModel');
        
        $metodos_faltando = array_diff($metodos_necessarios, $metodos_encontrados);
        
        if (empty($metodos_faltando)) {
            echo "✅ <strong>Métodos UserModel</strong> - TODOS IMPLEMENTADOS<br>";
            $sucessos[] = "Todos os métodos UserModel implementados";
        } else {
            echo "⚠️ <strong>Métodos UserModel</strong> - FALTANDO: " . implode(', ', $metodos_faltando) . "<br>";
            $avisos[] = "Métodos UserModel faltando: " . implode(', ', $metodos_faltando);
        }
        
    } catch (Exception $e) {
        echo "❌ <strong>Erro ao criar UserModel</strong> - " . $e->getMessage() . "<br>";
        $erros[] = "Erro ao criar UserModel: " . $e->getMessage();
    }
} else {
    echo "❌ <strong>Classe UserModel</strong> - NÃO ENCONTRADA<br>";
    $erros[] = "Classe UserModel não encontrada";
}

// Teste 4.5: Teste de CRUD - CREATE (Criar usuário)
echo "<h2>4.5 Teste CREATE - Criar Usuário</h2>";

if (isset($userModel)) {
    try {
        // Dados de teste para criar usuário
        $dados_teste = [
            'nome' => 'Usuário Teste CRUD',
            'email' => 'teste.crud@cfc.com',
            'senha' => 'senha123',
            'tipo' => 'instrutor',
            'status' => 'ativo'
        ];
        
        // Tentar criar usuário
        $resultado = $userModel->create($dados_teste);
        
        if ($resultado) {
            echo "✅ <strong>CREATE</strong> - USUÁRIO CRIADO COM SUCESSO<br>";
            $sucessos[] = "Usuário criado com sucesso";
            
            // Buscar o usuário criado para obter o ID
            $usuario_criado = $userModel->findByEmail($dados_teste['email']);
            if ($usuario_criado) {
                $id_teste = $usuario_criado['id'];
                echo "✅ <strong>ID do usuário criado</strong> - $id_teste<br>";
                $sucessos[] = "ID do usuário obtido: $id_teste";
            }
            
        } else {
            echo "❌ <strong>CREATE</strong> - FALHOU AO CRIAR USUÁRIO<br>";
            $erros[] = "Falha ao criar usuário";
        }
        
    } catch (Exception $e) {
        echo "❌ <strong>Erro no CREATE</strong> - " . $e->getMessage() . "<br>";
        $erros[] = "Erro no CREATE: " . $e->getMessage();
    }
}

// Teste 4.6: Teste de CRUD - READ (Ler usuário)
echo "<h2>4.6 Teste READ - Ler Usuário</h2>";

if (isset($userModel) && isset($id_teste)) {
    try {
        // Buscar usuário por ID
        $usuario_lido = $userModel->findById($id_teste);
        
        if ($usuario_lido) {
            echo "✅ <strong>READ por ID</strong> - USUÁRIO ENCONTRADO<br>";
            echo "📋 <strong>Dados:</strong> Nome: " . htmlspecialchars($usuario_lido['nome']) . 
                 ", Email: " . htmlspecialchars($usuario_lido['email']) . 
                 ", Tipo: " . htmlspecialchars($usuario_lido['tipo']) . "<br>";
            $sucessos[] = "Usuário lido por ID com sucesso";
        } else {
            echo "❌ <strong>READ por ID</strong> - USUÁRIO NÃO ENCONTRADO<br>";
            $erros[] = "Usuário não encontrado por ID";
        }
        
        // Buscar usuário por email
        $usuario_email = $userModel->findByEmail('teste.crud@cfc.com');
        
        if ($usuario_email) {
            echo "✅ <strong>READ por Email</strong> - USUÁRIO ENCONTRADO<br>";
            $sucessos[] = "Usuário lido por email com sucesso";
        } else {
            echo "❌ <strong>READ por Email</strong> - USUÁRIO NÃO ENCONTRADO<br>";
            $erros[] = "Usuário não encontrado por email";
        }
        
        // Listar todos os usuários
        $todos_usuarios = $userModel->findAll();
        
        if (is_array($todos_usuarios)) {
            echo "✅ <strong>READ ALL</strong> - " . count($todos_usuarios) . " USUÁRIOS ENCONTRADOS<br>";
            $sucessos[] = "Listagem de usuários funcionando";
        } else {
            echo "❌ <strong>READ ALL</strong> - FALHOU AO LISTAR USUÁRIOS<br>";
            $erros[] = "Falha ao listar usuários";
        }
        
    } catch (Exception $e) {
        echo "❌ <strong>Erro no READ</strong> - " . $e->getMessage() . "<br>";
        $erros[] = "Erro no READ: " . $e->getMessage();
    }
}

// Teste 4.7: Teste de CRUD - UPDATE (Atualizar usuário)
echo "<h2>4.7 Teste UPDATE - Atualizar Usuário</h2>";

if (isset($userModel) && isset($id_teste)) {
    try {
        // Dados para atualização
        $dados_atualizacao = [
            'nome' => 'Usuário Teste CRUD ATUALIZADO',
            'email' => 'teste.crud.atualizado@cfc.com',
            'tipo' => 'admin',
            'status' => 'ativo'
        ];
        
        // Atualizar usuário
        $resultado_update = $userModel->update($id_teste, $dados_atualizacao);
        
        if ($resultado_update) {
            echo "✅ <strong>UPDATE</strong> - USUÁRIO ATUALIZADO COM SUCESSO<br>";
            $sucessos[] = "Usuário atualizado com sucesso";
            
            // Verificar se a atualização foi feita
            $usuario_atualizado = $userModel->findById($id_teste);
            if ($usuario_atualizado && $usuario_atualizado['nome'] === $dados_atualizacao['nome']) {
                echo "✅ <strong>Verificação UPDATE</strong> - DADOS CONFIRMADOS<br>";
                $sucessos[] = "Verificação de atualização confirmada";
            } else {
                echo "⚠️ <strong>Verificação UPDATE</strong> - DADOS NÃO CONFIRMADOS<br>";
                $avisos[] = "Verificação de atualização não confirmada";
            }
            
        } else {
            echo "❌ <strong>UPDATE</strong> - FALHOU AO ATUALIZAR USUÁRIO<br>";
            $erros[] = "Falha ao atualizar usuário";
        }
        
    } catch (Exception $e) {
        echo "❌ <strong>Erro no UPDATE</strong> - " . $e->getMessage() . "<br>";
        $erros[] = "Erro no UPDATE: " . $e->getMessage();
    }
}

// Teste 4.8: Teste de CRUD - DELETE (Excluir usuário)
echo "<h2>4.8 Teste DELETE - Excluir Usuário</h2>";

if (isset($userModel) && isset($id_teste)) {
    try {
        // Excluir usuário de teste
        $resultado_delete = $userModel->delete($id_teste);
        
        if ($resultado_delete) {
            echo "✅ <strong>DELETE</strong> - USUÁRIO EXCLUÍDO COM SUCESSO<br>";
            $sucessos[] = "Usuário excluído com sucesso";
            
            // Verificar se foi realmente excluído
            $usuario_excluido = $userModel->findById($id_teste);
            if (!$usuario_excluido) {
                echo "✅ <strong>Verificação DELETE</strong> - USUÁRIO NÃO ENCONTRADO (EXCLUÍDO)<br>";
                $sucessos[] = "Verificação de exclusão confirmada";
            } else {
                echo "⚠️ <strong>Verificação DELETE</strong> - USUÁRIO AINDA ENCONTRADO<br>";
                $avisos[] = "Verificação de exclusão não confirmada";
            }
            
        } else {
            echo "❌ <strong>DELETE</strong> - FALHOU AO EXCLUIR USUÁRIO<br>";
            $erros[] = "Falha ao excluir usuário";
        }
        
    } catch (Exception $e) {
        echo "❌ <strong>Erro no DELETE</strong> - " . $e->getMessage() . "<br>";
        $erros[] = "Erro no DELETE: " . $e->getMessage();
    }
}

// Teste 4.9: Teste de Autenticação
echo "<h2>4.9 Teste de Autenticação</h2>";

if (isset($userModel)) {
    try {
        // Criar usuário para teste de autenticação
        $dados_auth = [
            'nome' => 'Usuário Auth Teste',
            'email' => 'auth.teste@cfc.com',
            'senha' => 'senha123',
            'tipo' => 'instrutor',
            'status' => 'ativo'
        ];
        
        $userModel->create($dados_auth);
        $usuario_auth = $userModel->findByEmail($dados_auth['email']);
        
        if ($usuario_auth) {
            // Testar autenticação com senha correta
            $auth_sucesso = $userModel->authenticate($dados_auth['email'], $dados_auth['senha']);
            
            if ($auth_sucesso) {
                echo "✅ <strong>Autenticação</strong> - LOGIN COM SUCESSO<br>";
                $sucessos[] = "Autenticação funcionando";
            } else {
                echo "❌ <strong>Autenticação</strong> - FALHOU NO LOGIN<br>";
                $erros[] = "Falha na autenticação";
            }
            
            // Testar autenticação com senha incorreta
            $auth_falha = $userModel->authenticate($dados_auth['email'], 'senha_errada');
            
            if (!$auth_falha) {
                echo "✅ <strong>Validação de Senha</strong> - SENHA INCORRETA REJEITADA<br>";
                $sucessos[] = "Validação de senha incorreta funcionando";
            } else {
                echo "❌ <strong>Validação de Senha</strong> - SENHA INCORRETA ACEITA<br>";
                $erros[] = "Validação de senha incorreta falhou";
            }
            
            // Limpar usuário de teste
            $userModel->delete($usuario_auth['id']);
            
        } else {
            echo "⚠️ <strong>Usuário para teste de auth</strong> - NÃO CRIADO<br>";
            $avisos[] = "Usuário para teste de autenticação não criado";
        }
        
    } catch (Exception $e) {
        echo "❌ <strong>Erro no teste de autenticação</strong> - " . $e->getMessage() . "<br>";
        $erros[] = "Erro no teste de autenticação: " . $e->getMessage();
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
    echo "<p>✅ <strong>TESTE #4 CONCLUÍDO COM SUCESSO!</strong></p>";
    echo "<p>🎯 <strong>Próximo:</strong> TESTE #5 - CRUD de CFCs</p>";
    echo "<p>📝 <strong>Instrução:</strong> Execute este teste e me informe o resultado. Se tudo estiver OK, criarei o próximo teste.</p>";
} else {
    echo "<p>❌ <strong>TESTE #4 COM ERROS!</strong></p>";
    echo "<p>🔧 <strong>Ação Necessária:</strong> Corrija os erros listados acima e execute novamente.</p>";
    echo "<p>📝 <strong>Instrução:</strong> Me informe quais erros apareceram para que eu possa ajudar a corrigi-los.</p>";
}

// Informações adicionais
echo "<hr>";
echo "<h2>💡 INFORMAÇÕES ADICIONAIS</h2>";
echo "<p><strong>URL de Teste:</strong> <code>http://localhost:8080/cfc-bom-conselho/admin/teste-04-crud-usuarios.php</code></p>";
echo "<p><strong>Funcionalidades Testadas:</strong> CREATE, READ, UPDATE, DELETE, Autenticação</p>";
echo "<p><strong>Arquivos Utilizados:</strong> UserModel, Database, Config</p>";
echo "<p><strong>Operações CRUD:</strong> Inserir, Buscar, Atualizar, Excluir usuários</p>";
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
