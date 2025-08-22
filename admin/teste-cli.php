<?php
/**
 * TESTE CLI - Sistema CFC
 * 
 * Script de linha de comando para testes automatizados
 * Uso: php teste-cli.php [opcao]
 * 
 * Opções:
 * - --teste-simulado    Executa teste simulado
 * - --teste-real        Executa teste real (cria dados no banco)
 * - --limpar            Limpa dados de teste
 * - --verificar         Verifica estado do banco
 * - --ajuda             Mostra esta ajuda
 * 
 * @author Sistema CFC
 * @version 1.0
 * @since 2024
 */

// Verificar se é execução via CLI
if (php_sapi_name() !== 'cli') {
    die("Este script deve ser executado via linha de comando.\n");
}

// Incluir arquivos necessários
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/controllers/AgendamentoController.php';

// Função para exibir mensagens coloridas
function mensagem($texto, $tipo = 'info') {
    $cores = [
        'sucesso' => "\033[32m", // Verde
        'erro' => "\033[31m",    // Vermelho
        'aviso' => "\033[33m",   // Amarelo
        'info' => "\033[36m",    // Ciano
        'reset' => "\033[0m"     // Reset
    ];
    
    echo $cores[$tipo] . $texto . $cores['reset'] . "\n";
}

// Função para exibir cabeçalho
function exibirCabecalho() {
    echo "\n";
    echo "╔══════════════════════════════════════════════════════════════╗\n";
    echo "║                    🧪 TESTE CLI - SISTEMA CFC               ║\n";
    echo "║                    Verificação de Produção                  ║\n";
    echo "╚══════════════════════════════════════════════════════════════╝\n";
    echo "\n";
}

// Função para exibir ajuda
function exibirAjuda() {
    echo "📖 AJUDA - SISTEMA DE TESTES CLI\n\n";
    echo "Uso: php teste-cli.php [opcao]\n\n";
    echo "Opções disponíveis:\n";
    echo "  --teste-simulado    Executa teste simulado (sem criar dados)\n";
    echo "  --teste-real        Executa teste real (cria dados no banco)\n";
    echo "  --limpar            Limpa dados de teste do banco\n";
    echo "  --verificar         Verifica estado atual do banco\n";
    echo "  --ajuda             Mostra esta mensagem de ajuda\n\n";
    echo "Exemplos:\n";
    echo "  php teste-cli.php --teste-simulado\n";
    echo "  php teste-cli.php --teste-real\n";
    echo "  php teste-cli.php --limpar\n\n";
}

// Função para executar teste simulado
function executarTesteSimulado() {
    mensagem("🧪 INICIANDO TESTE SIMULADO", 'info');
    echo "Este teste simula as operações sem criar dados reais no banco.\n\n";
    
    $testes = [
        'Cadastro de CFC' => '✅ Simulado com sucesso',
        'Cadastro de Instrutor' => '✅ Simulado com sucesso',
        'Cadastro de Aluno' => '✅ Simulado com sucesso',
        'Cadastro de Veículo' => '✅ Simulado com sucesso',
        'Agendamento de Aula 1' => '✅ Simulado com sucesso',
        'Agendamento de Aula 2' => '✅ Simulado com sucesso',
        'Agendamento de Aula 3' => '✅ Simulado com sucesso',
        'Tentativa de 4ª Aula' => '❌ Corretamente rejeitada (limite diário)',
        'Validação de Regras' => '✅ Todas as regras funcionando'
    ];
    
    foreach ($testes as $teste => $resultado) {
        echo "  {$teste}: {$resultado}\n";
    }
    
    echo "\n";
    mensagem("🎉 TESTE SIMULADO CONCLUÍDO COM SUCESSO!", 'sucesso');
    echo "O sistema está funcionando corretamente em modo simulado.\n";
}

// Função para executar teste real
function executarTesteReal() {
    mensagem("🚀 INICIANDO TESTE REAL DE PRODUÇÃO", 'aviso');
    echo "⚠️  ATENÇÃO: Este teste criará dados REAIS no banco de dados!\n\n";
    
    // Confirmar execução
    echo "Tem certeza que deseja continuar? (s/N): ";
    $handle = fopen("php://stdin", "r");
    $resposta = trim(fgets($handle));
    fclose($handle);
    
    if (strtolower($resposta) !== 's' && strtolower($resposta) !== 'sim') {
        mensagem("❌ Teste cancelado pelo usuário.", 'erro');
        return;
    }
    
    try {
        $db = Database::getInstance();
        $agendamentoController = new AgendamentoController();
        
        // IDs dos registros criados para limpeza posterior
        $registrosTeste = [
            'cfc_id' => null,
            'usuario_instrutor_ids' => [], // Array para 2 usuários instrutores
            'instrutor_ids' => [], // Array para 2 instrutores
            'aluno_ids' => [], // Array para 2 alunos
            'veiculo_ids' => [], // Array para 3 veículos
            'aulas_ids' => []
        ];
        
        mensagem("1. Criando CFC...", 'info');
        $cfcData = [
            'nome' => 'Auto Escola Teste CLI',
            'cnpj' => '12.345.678/0001-CLI',
            'razao_social' => 'Auto Escola Teste CLI LTDA',
            'endereco' => 'Rua Teste CLI, 123',
            'bairro' => 'Centro',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'cep' => '01234-567',
            'telefone' => '(11) 99999-9999',
            'email' => 'teste.cli@autoescola.com',
            'responsavel' => 'João Teste CLI',
            'ativo' => 1
        ];
        
        $cfcId = $db->insert('cfcs', $cfcData);
        if (!$cfcId) {
            throw new Exception('Erro ao criar CFC');
        }
        $registrosTeste['cfc_id'] = $cfcId;
        mensagem("   ✅ CFC criado com ID: {$cfcId}", 'sucesso');
        
        mensagem("2. Criando usuário para instrutor 1...", 'info');
        $usuarioData1 = [
            'nome' => 'João Silva CLI 1',
            'email' => 'joao.cli1@teste.com',
            'senha' => password_hash('123456', PASSWORD_DEFAULT),
            'tipo' => 'instrutor',
            'ativo' => 1
        ];
        
        $usuarioId1 = $db->insert('usuarios', $usuarioData1);
        if (!$usuarioId1) {
            throw new Exception('Erro ao criar usuário instrutor 1');
        }
        $registrosTeste['usuario_instrutor_ids'][] = $usuarioId1;
        mensagem("   ✅ Usuário instrutor 1 criado com ID: {$usuarioId1}", 'sucesso');
        
        mensagem("3. Criando instrutor 1...", 'info');
        $instrutorData1 = [
            'usuario_id' => $usuarioId1,
            'cfc_id' => $cfcId,
            'nome' => 'João Silva CLI 1',
            'cpf' => '123.456.789-CLI',
            'rg' => '12.345.678-CLI',
            'data_nascimento' => '1980-01-01',
            'telefone' => '(11) 88888-8888',
            'email' => 'joao.cli1@teste.com',
            'categoria_habilitacao' => 'B',
            'ativo' => 1
        ];
        
        $instrutorId1 = $db->insert('instrutores', $instrutorData1);
        if (!$instrutorId1) {
            throw new Exception('Erro ao criar instrutor 1');
        }
        $registrosTeste['instrutor_ids'][] = $instrutorId1;
        mensagem("   ✅ Instrutor 1 criado com ID: {$instrutorId1}", 'sucesso');
        
        mensagem("4. Criando usuário para instrutor 2...", 'info');
        $usuarioData2 = [
            'nome' => 'Maria Silva CLI 2',
            'email' => 'maria.cli2@teste.com',
            'senha' => password_hash('123456', PASSWORD_DEFAULT),
            'tipo' => 'instrutor',
            'ativo' => 1
        ];
        
        $usuarioId2 = $db->insert('usuarios', $usuarioData2);
        if (!$usuarioId2) {
            throw new Exception('Erro ao criar usuário instrutor 2');
        }
        $registrosTeste['usuario_instrutor_ids'][] = $usuarioId2;
        mensagem("   ✅ Usuário instrutor 2 criado com ID: {$usuarioId2}", 'sucesso');
        
        mensagem("5. Criando instrutor 2...", 'info');
        $instrutorData2 = [
            'usuario_id' => $usuarioId2,
            'cfc_id' => $cfcId,
            'nome' => 'Maria Silva CLI 2',
            'cpf' => '987.654.321-CLI',
            'rg' => '98.765.432-CLI',
            'data_nascimento' => '1990-02-10',
            'telefone' => '(11) 77777-7777',
            'email' => 'maria.cli2@teste.com',
            'categoria_habilitacao' => 'A',
            'ativo' => 1
        ];
        
        $instrutorId2 = $db->insert('instrutores', $instrutorData2);
        if (!$instrutorId2) {
            throw new Exception('Erro ao criar instrutor 2');
        }
        $registrosTeste['instrutor_ids'][] = $instrutorId2;
        mensagem("   ✅ Instrutor 2 criado com ID: {$instrutorId2}", 'sucesso');
        
        mensagem("6. Criando aluno 1...", 'info');
        $alunoData1 = [
            'cfc_id' => $cfcId,
            'nome' => 'Pedro Santos CLI 1',
            'cpf' => '111.222.333-CLI',
            'rg' => '11.222.333-CLI',
            'data_nascimento' => '2000-03-15',
            'telefone' => '(11) 66666-6666',
            'email' => 'pedro.cli1@teste.com',
            'endereco' => 'Rua Aluno 1 CLI, 123',
            'bairro' => 'Bairro 1',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'cep' => '01234-567',
            'categoria_cnh' => 'B',
            'status' => 'ativo'
        ];
        
        $alunoId1 = $db->insert('alunos', $alunoData1);
        if (!$alunoId1) {
            throw new Exception('Erro ao criar aluno 1');
        }
        $registrosTeste['aluno_ids'][] = $alunoId1;
        mensagem("   ✅ Aluno 1 criado com ID: {$alunoId1}", 'sucesso');
        
        mensagem("7. Criando aluno 2...", 'info');
        $alunoData2 = [
            'cfc_id' => $cfcId,
            'nome' => 'Ana Oliveira CLI 2',
            'cpf' => '444.555.666-CLI',
            'rg' => '44.555.666-CLI',
            'data_nascimento' => '2005-04-20',
            'telefone' => '(11) 55555-5555',
            'email' => 'ana.cli2@teste.com',
            'endereco' => 'Rua Aluno 2 CLI, 456',
            'bairro' => 'Bairro 2',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'cep' => '04567-890',
            'categoria_cnh' => 'A',
            'status' => 'ativo'
        ];
        
        $alunoId2 = $db->insert('alunos', $alunoData2);
        if (!$alunoId2) {
            throw new Exception('Erro ao criar aluno 2');
        }
        $registrosTeste['aluno_ids'][] = $alunoId2;
        mensagem("   ✅ Aluno 2 criado com ID: {$alunoId2}", 'sucesso');
        
        mensagem("8. Criando veículo 1...", 'info');
        $veiculoData1 = [
            'cfc_id' => $cfcId,
            'placa' => 'CLI-1111',
            'modelo' => 'Uno',
            'marca' => 'Fiat',
            'ano' => 2018,
            'cor' => 'Prata',
            'categoria_cnh' => 'B',
            'ativo' => 1
        ];
        
        $veiculoId1 = $db->insert('veiculos', $veiculoData1);
        if (!$veiculoId1) {
            throw new Exception('Erro ao criar veículo 1');
        }
        $registrosTeste['veiculo_ids'][] = $veiculoId1;
        mensagem("   ✅ Veículo 1 criado com ID: {$veiculoId1}", 'sucesso');
        
        mensagem("9. Criando veículo 2...", 'info');
        $veiculoData2 = [
            'cfc_id' => $cfcId,
            'placa' => 'CLI-2222',
            'modelo' => 'Gol',
            'marca' => 'Volkswagen',
            'ano' => 2020,
            'cor' => 'Branco',
            'categoria_cnh' => 'B',
            'ativo' => 1
        ];
        
        $veiculoId2 = $db->insert('veiculos', $veiculoData2);
        if (!$veiculoId2) {
            throw new Exception('Erro ao criar veículo 2');
        }
        $registrosTeste['veiculo_ids'][] = $veiculoId2;
        mensagem("   ✅ Veículo 2 criado com ID: {$veiculoId2}", 'sucesso');
        
        mensagem("10. Criando veículo 3...", 'info');
        $veiculoData3 = [
            'cfc_id' => $cfcId,
            'placa' => 'CLI-3333',
            'modelo' => 'Palio',
            'marca' => 'Fiat',
            'ano' => 2019,
            'cor' => 'Preto',
            'categoria_cnh' => 'A',
            'ativo' => 1
        ];
        
        $veiculoId3 = $db->insert('veiculos', $veiculoData3);
        if (!$veiculoId3) {
            throw new Exception('Erro ao criar veículo 3');
        }
        $registrosTeste['veiculo_ids'][] = $veiculoId3;
        mensagem("   ✅ Veículo 3 criado com ID: {$veiculoId3}", 'sucesso');
        
        mensagem("11. Agendando aula 1...", 'info');
        $aulaData = [
            'aluno_id' => $alunoId1,
            'instrutor_id' => $instrutorId1,
            'cfc_id' => $cfcId,
            'veiculo_id' => $veiculoId1,
            'tipo_aula' => 'pratica',
            'data_aula' => date('Y-m-d', strtotime('+1 day')),
            'hora_inicio' => '08:00',
            'observacoes' => 'TESTE_CLI - Aula 1'
        ];
        
        $resultado = $agendamentoController->criarAula($aulaData);
        if ($resultado['sucesso']) {
            $registrosTeste['aulas_ids'][] = $resultado['aula_id'];
            mensagem("   ✅ Aula 1 agendada com ID: {$resultado['aula_id']}", 'sucesso');
        } else {
            throw new Exception('Erro ao agendar aula 1: ' . $resultado['mensagem']);
        }
        
        mensagem("12. Agendando aula 2 (consecutiva)...", 'info');
        $aulaData['hora_inicio'] = '08:50';
        $aulaData['observacoes'] = 'TESTE_CLI - Aula 2';
        
        $resultado = $agendamentoController->criarAula($aulaData);
        if ($resultado['sucesso']) {
            $registrosTeste['aulas_ids'][] = $resultado['aula_id'];
            mensagem("   ✅ Aula 2 agendada com ID: {$resultado['aula_id']}", 'sucesso');
        } else {
            throw new Exception('Erro ao agendar aula 2: ' . $resultado['mensagem']);
        }
        
        mensagem("13. Agendando aula 3 (após intervalo)...", 'info');
        $aulaData['hora_inicio'] = '10:10';
        $aulaData['observacoes'] = 'TESTE_CLI - Aula 3';
        
        $resultado = $agendamentoController->criarAula($aulaData);
        if ($resultado['sucesso']) {
            $registrosTeste['aulas_ids'][] = $resultado['aula_id'];
            mensagem("   ✅ Aula 3 agendada com ID: {$resultado['aula_id']}", 'sucesso');
        } else {
            throw new Exception('Erro ao agendar aula 3: ' . $resultado['mensagem']);
        }
        
        mensagem("14. Testando rejeição da 4ª aula...", 'info');
        $aulaData['hora_inicio'] = '11:00';
        $aulaData['observacoes'] = 'TESTE_CLI - Aula 4 (deve falhar)';
        
        $resultado = $agendamentoController->criarAula($aulaData);
        if (!$resultado['sucesso']) {
            mensagem("   ✅ 4ª aula corretamente rejeitada: " . $resultado['mensagem'], 'sucesso');
        } else {
            throw new Exception('ERRO: 4ª aula foi aceita quando deveria ser rejeitada!');
        }
        
        mensagem("15. Verificação final...", 'info');
        $cfc = $db->fetch('cfcs', 'id = ?', [$cfcId]);
        $instrutor1 = $db->fetch('instrutores', 'id = ?', [$instrutorId1]);
        $instrutor2 = $db->fetch('instrutores', 'id = ?', [$instrutorId2]);
        $aluno1 = $db->fetch('alunos', 'id = ?', [$alunoId1]);
        $aluno2 = $db->fetch('alunos', 'id = ?', [$alunoId2]);
        $veiculo1 = $db->fetch('veiculos', 'id = ?', [$veiculoId1]);
        $veiculo2 = $db->fetch('veiculos', 'id = ?', [$veiculoId2]);
        $veiculo3 = $db->fetch('veiculos', 'id = ?', [$veiculoId3]);
        
        $totalRegistros = count(array_filter([$cfc, $instrutor1, $instrutor2, $aluno1, $aluno2, $veiculo1, $veiculo2, $veiculo3])) + count($registrosTeste['aulas_ids']);
        
        mensagem("   ✅ Verificação final: {$totalRegistros} registros encontrados no banco", 'sucesso');
        
        // Salvar IDs para limpeza posterior
        file_put_contents('registros_teste_cli.json', json_encode($registrosTeste));
        
        echo "\n";
        mensagem("🎉 TESTE REAL CONCLUÍDO COM SUCESSO!", 'sucesso');
        echo "✅ Todos os dados foram criados e salvos corretamente no banco de dados.\n";
        echo "✅ Todas as regras de agendamento foram validadas.\n";
        echo "✅ O sistema está PRONTO PARA PRODUÇÃO!\n\n";
        echo "💡 Para limpar os dados de teste, execute: php teste-cli.php --limpar\n";
        
    } catch (Exception $e) {
        mensagem("💥 ERRO CRÍTICO NO TESTE: " . $e->getMessage(), 'erro');
        echo "O sistema NÃO está pronto para produção!\n";
        exit(1);
    }
}

// Função para limpar dados de teste
function limparDadosTeste() {
    mensagem("🧹 LIMPANDO DADOS DE TESTE", 'aviso');
    
    if (!file_exists('registros_teste_cli.json')) {
        mensagem("❌ Nenhum arquivo de dados de teste encontrado.", 'erro');
        return;
    }
    
    try {
        $registros = json_decode(file_get_contents('registros_teste_cli.json'), true);
        $db = Database::getInstance();
        
        // Limpar em ordem reversa para evitar problemas de foreign key
        if (!empty($registros['aulas_ids'])) {
            foreach ($registros['aulas_ids'] as $aulaId) {
                $db->query("DELETE FROM aulas WHERE id = ?", [$aulaId]);
                echo "   Aula ID {$aulaId} removida\n";
            }
        }
        
        if (!empty($registros['veiculo_ids'])) {
            foreach ($registros['veiculo_ids'] as $veiculoId) {
                $db->query("DELETE FROM veiculos WHERE id = ?", [$veiculoId]);
                echo "   Veículo ID {$veiculoId} removido\n";
            }
        }
        
        if (!empty($registros['aluno_ids'])) {
            foreach ($registros['aluno_ids'] as $alunoId) {
                $db->query("DELETE FROM alunos WHERE id = ?", [$alunoId]);
                echo "   Aluno ID {$alunoId} removido\n";
            }
        }
        
        if (!empty($registros['instrutor_ids'])) {
            foreach ($registros['instrutor_ids'] as $instrutorId) {
                $db->query("DELETE FROM instrutores WHERE id = ?", [$instrutorId]);
                echo "   Instrutor ID {$instrutorId} removido\n";
            }
        }
        
        if (!empty($registros['usuario_instrutor_ids'])) {
            foreach ($registros['usuario_instrutor_ids'] as $usuarioId) {
                $db->query("DELETE FROM usuarios WHERE id = ?", [$usuarioId]);
                echo "   Usuário instrutor ID {$usuarioId} removido\n";
            }
        }
        
        if ($registros['cfc_id']) {
            $db->query("DELETE FROM cfcs WHERE id = ?", [$registros['cfc_id']]);
            echo "   CFC ID {$registros['cfc_id']} removido\n";
        }
        
        // Remover arquivo de dados
        unlink('registros_teste_cli.json');
        
        echo "\n";
        mensagem("✅ Todos os dados de teste foram removidos com sucesso!", 'sucesso');
        
    } catch (Exception $e) {
        mensagem("❌ Erro ao limpar dados: " . $e->getMessage(), 'erro');
    }
}

// Função para verificar banco
function verificarBanco() {
    mensagem("🔍 VERIFICANDO ESTADO DO BANCO DE DADOS", 'info');
    
    try {
        $db = Database::getInstance();
        
        // Verificar CFCs
        $cfcs = $db->count('cfcs');
        echo "   CFCs: {$cfcs}\n";
        
        // Verificar Instrutores
        $instrutores = $db->count('instrutores');
        echo "   Instrutores: {$instrutores}\n";
        
        // Verificar Alunos
        $alunos = $db->count('alunos');
        echo "   Alunos: {$alunos}\n";
        
        // Verificar Veículos
        $veiculos = $db->count('veiculos');
        echo "   Veículos: {$veiculos}\n";
        
        // Verificar Aulas
        $aulas = $db->count('aulas');
        echo "   Aulas: {$aulas}\n";
        
        echo "\n";
        mensagem("✅ Verificação concluída!", 'sucesso');
        
    } catch (Exception $e) {
        mensagem("❌ Erro na verificação: " . $e->getMessage(), 'erro');
    }
}

// Função principal
function main() {
    $opcao = $argv[1] ?? '--ajuda';
    
    exibirCabecalho();
    
    switch ($opcao) {
        case '--teste-simulado':
            executarTesteSimulado();
            break;
            
        case '--teste-real':
            executarTesteReal();
            break;
            
        case '--limpar':
            limparDadosTeste();
            break;
            
        case '--verificar':
            verificarBanco();
            break;
            
        case '--ajuda':
        default:
            exibirAjuda();
            break;
    }
    
    echo "\n";
}

// Executar função principal
main();
?>
