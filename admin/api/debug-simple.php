<?php
// API super simples para debug
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Cache-Control: no-cache');

// Dados mockados para teste
$alunoMock = [
    'id' => 102,
    'nome' => 'Aluno Duplicado',
    'cpf' => '123.456.789-00',
    'email' => 'duplicado@email.com',
    'telefone' => '(11) 99999-9999',
    'status' => 'ativo',
    'cfc_id' => 1,
    'cfc_nome' => 'CFC Exemplo'
];

$id = $_GET['id'] ?? null;

if ($id == 102) {
    echo json_encode(['success' => true, 'aluno' => $alunoMock]);
} else {
    echo json_encode(['success' => false, 'error' => 'Aluno não encontrado']);
}
?>
