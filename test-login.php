<?php
// =====================================================
// TESTE SIMPLES DE LOGIN - SISTEMA CFC
// =====================================================

require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Login - Sistema CFC</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .back-link {
            text-align: center;
            margin-top: 30px;
        }
        .back-link a {
            display: inline-block;
            padding: 12px 24px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .back-link a:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Teste de Login - Sistema CFC</h1>
        
        <div class="back-link">
            <a href="index.php">← Voltar para o Login</a>
        </div>
    </div>
</body>
</html>
