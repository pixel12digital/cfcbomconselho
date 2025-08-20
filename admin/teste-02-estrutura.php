<?php
/**
 * TESTE #2: Estrutura de Arquivos e Diretórios
 * Este teste verifica se todos os arquivos estão no lugar correto e se a estrutura MVC está organizada
 */

// Configurações de teste
$erros = [];
$sucessos = [];
$avisos = [];

echo "<h1>🔍 TESTE #2: Estrutura de Arquivos e Diretórios</h1>";
echo "<p><strong>Data/Hora:</strong> " . date('d/m/Y H:i:s') . "</p>";
echo "<p><strong>Ambiente:</strong> " . ($_SERVER['SERVER_PORT'] == '8080' ? 'XAMPP Local (Porta 8080)' : 'Produção') . "</p>";
echo "<hr>";

// Teste 2.1: Verificar estrutura de diretórios principais
echo "<h2>2.1 Verificação de Diretórios Principais</h2>";

$diretorios_principais = [
    '../' => 'Raiz do Sistema',
    '../admin/' => 'Painel Administrativo',
    '../includes/' => 'Arquivos de Configuração',
    '../admin/assets/' => 'Assets do Admin',
    '../admin/assets/css/' => 'Estilos CSS',
    '../admin/assets/js/' => 'Scripts JavaScript',
    '../admin/assets/images/' => 'Imagens do Admin',
    '../admin/pages/' => 'Páginas do Admin',
    '../admin/api/' => 'APIs do Sistema',
    '../admin/teste-01-conectividade.php' => 'Teste #1 (deve existir)'
];

foreach ($diretorios_principais as $caminho => $descricao) {
    if (is_dir($caminho) || file_exists($caminho)) {
        $tipo = is_dir($caminho) ? 'Diretório' : 'Arquivo';
        $permissoes = is_dir($caminho) ? substr(sprintf('%o', fileperms($caminho)), -4) : 'N/A';
        
        echo "✅ <strong>$descricao</strong> - EXISTE ($tipo)";
        if (is_dir($caminho)) {
            echo " - Permissões: $permissoes";
        }
        echo "<br>";
        
        $sucessos[] = "$descricao existe";
        
        // Verificar permissões de diretórios
        if (is_dir($caminho) && $permissoes != '0755' && $permissoes != '0750') {
            $avisos[] = "Diretório $descricao tem permissões $permissoes (recomendado: 0755)";
        }
    } else {
        echo "❌ <strong>$descricao</strong> - NÃO ENCONTRADO<br>";
        $erros[] = "$descricao não encontrado";
    }
}

// Teste 2.2: Verificar arquivos de configuração
echo "<h2>2.2 Verificação de Arquivos de Configuração</h2>";

$arquivos_config = [
    '../includes/config.php' => 'Configurações principais',
    '../includes/database.php' => 'Classe de banco de dados',
    '../includes/auth.php' => 'Sistema de autenticação',
    '../includes/functions.php' => 'Funções utilitárias (opcional)',
    '../includes/constants.php' => 'Constantes do sistema (opcional)'
];

foreach ($arquivos_config as $arquivo => $descricao) {
    if (file_exists($arquivo)) {
        $tamanho = filesize($arquivo);
        $tamanho_kb = round($tamanho / 1024, 2);
        echo "✅ <strong>$descricao</strong> - EXISTE ($tamanho_kb KB)<br>";
        $sucessos[] = "$descricao existe";
        
        // Verificar se arquivo não está vazio
        if ($tamanho < 100) {
            $avisos[] = "$descricao parece estar vazio ou muito pequeno ($tamanho bytes)";
        }
    } else {
        if (strpos($descricao, '(opcional)') !== false) {
            echo "⚠️ <strong>$descricao</strong> - NÃO ENCONTRADO (OPCIONAL)<br>";
            $avisos[] = "$descricao não encontrado (opcional)";
        } else {
            echo "❌ <strong>$descricao</strong> - NÃO ENCONTRADO<br>";
            $erros[] = "$descricao não encontrado";
        }
    }
}

// Teste 2.3: Verificar estrutura MVC
echo "<h2>2.3 Verificação de Estrutura MVC</h2>";

$estrutura_mvc = [
    '../includes/controllers/' => 'Controllers (Model-View-Controller)',
    '../includes/models/' => 'Models (Model-View-Controller)',
    '../includes/views/' => 'Views (Model-View-Controller)',
    '../includes/middleware/' => 'Middleware (opcional)',
    '../includes/services/' => 'Services (opcional)'
];

foreach ($estrutura_mvc as $diretorio => $descricao) {
    if (is_dir($diretorio)) {
        $arquivos = scandir($diretorio);
        $arquivos_php = array_filter($arquivos, function($file) {
            return pathinfo($file, PATHINFO_EXTENSION) === 'php';
        });
        
        echo "✅ <strong>$descricao</strong> - EXISTE (" . count($arquivos_php) . " arquivos PHP)<br>";
        $sucessos[] = "$descricao existe com " . count($arquivos_php) . " arquivos PHP";
        
        // Listar arquivos PHP encontrados
        if (!empty($arquivos_php)) {
            foreach ($arquivos_php as $arquivo) {
                echo "&nbsp;&nbsp;&nbsp;&nbsp;📄 $arquivo<br>";
            }
        }
    } else {
        if (strpos($descricao, '(opcional)') !== false) {
            echo "⚠️ <strong>$descricao</strong> - NÃO ENCONTRADO (OPCIONAL)<br>";
            $avisos[] = "$descricao não encontrado (opcional)";
        } else {
            echo "❌ <strong>$descricao</strong> - NÃO ENCONTRADO<br>";
            $erros[] = "$descricao não encontrado";
        }
    }
}

// Teste 2.4: Verificar assets (CSS, JS, imagens)
echo "<h2>2.4 Verificação de Assets (CSS, JS, Imagens)</h2>";

$assets = [
    '../admin/assets/css/' => 'Arquivos CSS',
    '../admin/assets/js/' => 'Arquivos JavaScript',
    '../admin/assets/images/' => 'Imagens'
];

foreach ($assets as $diretorio => $descricao) {
    if (is_dir($diretorio)) {
        $arquivos = scandir($diretorio);
        $extensoes = [];
        
        foreach ($arquivos as $arquivo) {
            if ($arquivo != '.' && $arquivo != '..') {
                $extensao = strtolower(pathinfo($arquivo, PATHINFO_EXTENSION));
                if (!isset($extensoes[$extensao])) {
                    $extensoes[$extensao] = 0;
                }
                $extensoes[$extensao]++;
            }
        }
        
        $total_arquivos = array_sum($extensoes);
        echo "✅ <strong>$descricao</strong> - EXISTE ($total_arquivos arquivos)<br>";
        $sucessos[] = "$descricao existe com $total_arquivos arquivos";
        
        // Mostrar extensões encontradas
        if (!empty($extensoes)) {
            foreach ($extensoes as $ext => $count) {
                echo "&nbsp;&nbsp;&nbsp;&nbsp;📁 $ext: $count arquivos<br>";
            }
        }
    } else {
        echo "❌ <strong>$descricao</strong> - NÃO ENCONTRADO<br>";
        $erros[] = "$descricao não encontrado";
    }
}

// Teste 2.5: Verificar arquivos principais do admin
echo "<h2>2.5 Verificação de Arquivos Principais do Admin</h2>";

$arquivos_admin = [
    '../admin/index.php' => 'Página principal do admin',
    '../admin/login.php' => 'Página de login',
    '../admin/dashboard.php' => 'Dashboard (opcional)',
    '../admin/usuarios.php' => 'Gestão de usuários (opcional)',
    '../admin/cfcs.php' => 'Gestão de CFCs (opcional)',
    '../admin/alunos.php' => 'Gestão de alunos (opcional)',
    '../admin/instrutores.php' => 'Gestão de instrutores (opcional)',
    '../admin/veiculos.php' => 'Gestão de veículos (opcional)',
    '../admin/agendamento.php' => 'Sistema de agendamento (opcional)'
];

foreach ($arquivos_admin as $arquivo => $descricao) {
    if (file_exists($arquivo)) {
        $tamanho = filesize($arquivo);
        $tamanho_kb = round($tamanho / 1024, 2);
        echo "✅ <strong>$descricao</strong> - EXISTE ($tamanho_kb KB)<br>";
        $sucessos[] = "$descricao existe";
    } else {
        if (strpos($descricao, '(opcional)') !== false) {
            echo "⚠️ <strong>$descricao</strong> - NÃO ENCONTRADO (OPCIONAL)<br>";
            $avisos[] = "$descricao não encontrado (opcional)";
        } else {
            echo "❌ <strong>$descricao</strong> - NÃO ENCONTRADO<br>";
            $erros[] = "$descricao não encontrado";
        }
    }
}

// Teste 2.6: Verificar arquivos de teste
echo "<h2>2.6 Verificação de Arquivos de Teste</h2>";

$arquivos_teste = [
    'teste-01-conectividade.php' => 'Teste #1 - Conectividade',
    'teste-02-estrutura.php' => 'Teste #2 - Estrutura (este arquivo)',
    'PLANO_TESTES_COMPLETO.md' => 'Plano completo de testes'
];

foreach ($arquivos_teste as $arquivo => $descricao) {
    if (file_exists($arquivo)) {
        $tamanho = filesize($arquivo);
        $tamanho_kb = round($tamanho / 1024, 2);
        echo "✅ <strong>$descricao</strong> - EXISTE ($tamanho_kb KB)<br>";
        $sucessos[] = "$descricao existe";
    } else {
        echo "❌ <strong>$descricao</strong> - NÃO ENCONTRADO<br>";
        $erros[] = "$descricao não encontrado";
    }
}

// Teste 2.7: Verificar arquivos de banco de dados
echo "<h2>2.7 Verificação de Arquivos de Banco de Dados</h2>";

$arquivos_db = [
    '../database/' => 'Diretório de banco de dados (opcional)',
    '../database/schema.sql' => 'Schema do banco (opcional)',
    '../database/seed.sql' => 'Dados iniciais (opcional)',
    '../database/backup/' => 'Diretório de backup (opcional)'
];

foreach ($arquivos_db as $arquivo => $descricao) {
    if (file_exists($arquivo) || is_dir($arquivo)) {
        if (is_dir($arquivo)) {
            echo "✅ <strong>$descricao</strong> - EXISTE (Diretório)<br>";
            $sucessos[] = "$descricao existe";
        } else {
            $tamanho = filesize($arquivo);
            $tamanho_kb = round($tamanho / 1024, 2);
            echo "✅ <strong>$descricao</strong> - EXISTE ($tamanho_kb KB)<br>";
            $sucessos[] = "$descricao existe";
        }
    } else {
        echo "⚠️ <strong>$descricao</strong> - NÃO ENCONTRADO (OPCIONAL)<br>";
        $avisos[] = "$descricao não encontrado (opcional)";
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
    echo "<p>✅ <strong>TESTE #2 CONCLUÍDO COM SUCESSO!</strong></p>";
    echo "<p>🎯 <strong>Próximo:</strong> TESTE #3 - Sistema de Autenticação</p>";
    echo "<p>📝 <strong>Instrução:</strong> Execute este teste e me informe o resultado. Se tudo estiver OK, criarei o próximo teste.</p>";
} else {
    echo "<p>❌ <strong>TESTE #2 COM ERROS!</strong></p>";
    echo "<p>🔧 <strong>Ação Necessária:</strong> Corrija os erros listados acima e execute novamente.</p>";
    echo "<p>📝 <strong>Instrução:</strong> Me informe quais erros apareceram para que eu possa ajudar a corrigi-los.</p>";
}

// Informações adicionais
echo "<hr>";
echo "<h2>💡 INFORMAÇÕES ADICIONAIS</h2>";
echo "<p><strong>URL de Teste:</strong> <code>http://localhost:8080/cfc-bom-conselho/admin/teste-02-estrutura.php</code></p>";
echo "<p><strong>Estrutura Verificada:</strong> MVC, Assets, Configurações, Admin</p>";
echo "<p><strong>Permissões Recomendadas:</strong> 0755 para diretórios, 0644 para arquivos</p>";
echo "<p><strong>Arquivos Obrigatórios:</strong> config.php, database.php, auth.php, index.php</p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1 { color: #2c3e50; }
h2 { color: #34495e; margin-top: 30px; }
h3 { color: #7f8c8d; }
hr { border: 1px solid #ecf0f1; margin: 20px 0; }
</style>
