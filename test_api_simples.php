<?php
// Teste simples da API de exclusão
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Teste Simples da API de Exclusão</h1>";

// Primeiro, verificar quais alunos existem
echo "<h2>1. Verificando alunos existentes...</h2>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8080/cfc-bom-conselho/admin/api/alunos.php');
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $alunos = json_decode($response, true);
    if ($alunos && isset($alunos['success']) && $alunos['success'] && isset($alunos['alunos']) && count($alunos['alunos']) > 0) {
        echo "✅ Alunos encontrados:<br>";
        foreach ($alunos['alunos'] as $aluno) {
            echo "- ID: {$aluno['id']}, Nome: {$aluno['nome']}, CPF: {$aluno['cpf']}<br>";
        }
        
        // Pegar o primeiro aluno para teste
        $primeiroAluno = $alunos['alunos'][0];
        $idParaTeste = $primeiroAluno['id'];
        
        if ($idParaTeste) {
            echo "<br><strong>Aluno selecionado para teste:</strong> ID {$idParaTeste} - {$primeiroAluno['nome']}<br>";
            
            echo "<h2>2. Testando exclusão do aluno ID {$idParaTeste}...</h2>";
            
            // Testar exclusão
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'http://localhost:8080/cfc-bom-conselho/admin/api/alunos.php');
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['id' => $idParaTeste]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);

            curl_close($ch);

            echo "<p><strong>HTTP Code:</strong> {$httpCode}</p>";

            if ($error) {
                echo "<p><strong>Erro cURL:</strong> {$error}</p>";
            } else {
                // Separar headers do body
                $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                $headers = substr($response, 0, $headerSize);
                $body = substr($response, $headerSize);
                
                echo "<h3>Body:</h3>";
                echo "<pre>" . htmlspecialchars($body) . "</pre>";
                
                // Tentar decodificar JSON
                $jsonData = json_decode($body, true);
                if ($jsonData) {
                    echo "<h3>JSON decodificado:</h3>";
                    echo "<pre>" . print_r($jsonData, true) . "</pre>";
                    
                    if (isset($jsonData['success']) && $jsonData['success']) {
                        echo "<p style='color: green;'>✅ Sucesso! Aluno excluído.</p>";
                        
                        // Reinserir o aluno para não perder dados
                        echo "<h2>3. Reinserindo aluno para preservar dados...</h2>";
                        
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, 'http://localhost:8080/cfc-bom-conselho/admin/api/alunos.php');
                        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                            'nome' => $primeiroAluno['nome'],
                            'cpf' => $primeiroAluno['cpf'],
                            'cfc_id' => $primeiroAluno['cfc_id'],
                            'rg' => $primeiroAluno['rg'] ?? '',
                            'data_nascimento' => $primeiroAluno['data_nascimento'] ?? null,
                            'telefone' => $primeiroAluno['telefone'] ?? '',
                            'email' => $primeiroAluno['email'] ?? '',
                            'endereco' => $primeiroAluno['endereco'] ?? '',
                            'categoria_cnh' => $primeiroAluno['categoria_cnh'] ?? 'B',
                            'status' => $primeiroAluno['status'] ?? 'ativo'
                        ]));
                        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                            'Content-Type: application/json',
                            'Accept: application/json'
                        ]);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_HEADER, false);

                        $responseReinserir = curl_exec($ch);
                        $httpCodeReinserir = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);

                        if ($httpCodeReinserir === 200) {
                            $resultadoReinserir = json_decode($responseReinserir, true);
                            if ($resultadoReinserir && isset($resultadoReinserir['success']) && $resultadoReinserir['success']) {
                                echo "<p style='color: green;'>✅ Aluno reinserido com sucesso!</p>";
                            } else {
                                echo "<p style='color: orange;'>⚠️ Aluno não foi reinserido</p>";
                            }
                        } else {
                            echo "<p style='color: orange;'>⚠️ Erro ao reinserir aluno (HTTP {$httpCodeReinserir})</p>";
                        }
                        
                    } else {
                        echo "<p style='color: red;'>❌ Erro: " . ($jsonData['error'] ?? 'Erro desconhecido') . "</p>";
                    }
                } else {
                    echo "<h3>❌ Não foi possível decodificar JSON</h3>";
                    echo "Erro JSON: " . json_last_error_msg() . "<br>";
                }
            }
        } else {
            echo "❌ ID do aluno é inválido<br>";
        }
        
    } else {
        echo "❌ Erro ao buscar alunos: " . ($alunos['error'] ?? 'Erro desconhecido') . "<br>";
        if (isset($alunos['alunos'])) {
            echo "Alunos encontrados: " . count($alunos['alunos']) . "<br>";
        }
    }
} else {
    echo "❌ Erro HTTP {$httpCode} ao buscar alunos<br>";
}

echo "<hr>";
echo "<p><strong>Teste concluído em:</strong> " . date('Y-m-d H:i:s') . "</p>";
?>
