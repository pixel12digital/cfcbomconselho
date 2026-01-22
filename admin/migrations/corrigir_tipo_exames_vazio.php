<?php
/**
 * Script para corrigir exames com tipo vazio (NULL ou '')
 * 
 * Uso: Acesse via navegador:
 * http://localhost/cfc-bom-conselho/admin/migrations/corrigir_tipo_exames_vazio.php
 * 
 * Ou via CLI:
 * php admin/migrations/corrigir_tipo_exames_vazio.php
 */

// Headers para exibição
header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>\n";
echo "<html lang='pt-BR'>\n";
echo "<head>\n";
echo "    <meta charset='UTF-8'>\n";
echo "    <meta name='viewport' content='width=device-width, initial-scale=1.0'>\n";
echo "    <title>Corrigir Tipo de Exames Vazios</title>\n";
echo "    <style>\n";
echo "        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }\n";
echo "        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }\n";
echo "        .success { color: #28a745; padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; margin: 15px 0; }\n";
echo "        .error { color: #dc3545; padding: 15px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; margin: 15px 0; }\n";
echo "        .info { color: #0c5460; padding: 15px; background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 4px; margin: 15px 0; }\n";
echo "        .warning { color: #856404; padding: 15px; background: #fff3cd; border: 1px solid #ffeeba; border-radius: 4px; margin: 15px 0; }\n";
echo "        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; border: 1px solid #dee2e6; }\n";
echo "        h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }\n";
echo "        h2 { color: #495057; margin-top: 30px; }\n";
echo "        table { width: 100%; border-collapse: collapse; margin: 15px 0; }\n";
echo "        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #dee2e6; }\n";
echo "        th { background: #007bff; color: white; }\n";
echo "        tr:hover { background: #f8f9fa; }\n";
echo "        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 10px 5px; border: none; cursor: pointer; }\n";
echo "        .btn:hover { background: #0056b3; }\n";
echo "        .btn-danger { background: #dc3545; }\n";
echo "        .btn-danger:hover { background: #c82333; }\n";
echo "        .btn-success { background: #28a745; }\n";
echo "        .btn-success:hover { background: #218838; }\n";
echo "        .form-group { margin: 15px 0; }\n";
echo "        label { display: block; margin-bottom: 5px; font-weight: bold; }\n";
echo "        select, input { width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px; font-size: 14px; }\n";
echo "    </style>\n";
echo "</head>\n";
echo "<body>\n";
echo "<div class='container'>\n";
echo "<h1>🔧 Corrigir Tipo de Exames Vazios</h1>\n";

try {
    require_once __DIR__ . '/../../includes/config.php';
    require_once __DIR__ . '/../../includes/database.php';
    
    $db = Database::getInstance();
    
    // =====================================================
    // DEBUG: VERIFICAR ESTRUTURA DA TABELA
    // =====================================================
    echo "<h2>🔍 Debug: Estrutura da Tabela</h2>\n";
    
    try {
        $estrutura = $db->fetchAll("SHOW COLUMNS FROM exames WHERE Field = 'tipo'");
        if (!empty($estrutura)) {
            echo "<div class='info'>\n";
            echo "<strong>Estrutura do campo 'tipo':</strong><br>\n";
            echo "<pre>" . print_r($estrutura, true) . "</pre>\n";
            echo "</div>\n";
        }
        
        // Verificar valores únicos do campo tipo
        $valoresUnicos = $db->fetchAll("
            SELECT DISTINCT tipo, 
                   LENGTH(COALESCE(tipo, '')) as len,
                   HEX(COALESCE(tipo, '')) as hex,
                   tipo IS NULL as is_null
            FROM exames
            ORDER BY tipo
        ");
        
        if (!empty($valoresUnicos)) {
            echo "<div class='info'>\n";
            echo "<strong>Valores únicos encontrados no campo 'tipo':</strong><br>\n";
            echo "<table>\n";
            echo "<thead><tr><th>Tipo</th><th>Length</th><th>HEX</th><th>É NULL?</th></tr></thead>\n";
            echo "<tbody>\n";
            foreach ($valoresUnicos as $valor) {
                $tipoDisplay = $valor['is_null'] ? '<em style="color: red;">NULL</em>' : 
                              (empty($valor['tipo']) ? '<em style="color: orange;">VAZIO</em>' : htmlspecialchars($valor['tipo']));
                echo "<tr>\n";
                echo "<td>{$tipoDisplay}</td>\n";
                echo "<td>{$valor['len']}</td>\n";
                echo "<td><code>{$valor['hex']}</code></td>\n";
                echo "<td>" . ($valor['is_null'] ? 'SIM' : 'NÃO') . "</td>\n";
                echo "</tr>\n";
            }
            echo "</tbody>\n";
            echo "</table>\n";
            echo "</div>\n";
        }
    } catch (Exception $e) {
        echo "<div class='error'>\n";
        echo "Erro ao verificar estrutura: " . htmlspecialchars($e->getMessage()) . "\n";
        echo "</div>\n";
    }
    
    // Verificar se há ação de correção
    $acao = $_GET['acao'] ?? '';
    $tipoDefinir = $_GET['tipo'] ?? '';
    
    // =====================================================
    // PASSO 1: VERIFICAR EXAMES COM TIPO VAZIO
    // =====================================================
    echo "<h2>📊 Passo 1: Verificar Exames com Tipo Vazio</h2>\n";
    
    // Verificar exames com tipo vazio de forma mais abrangente
    // Incluir também tipos com apenas espaços em branco
    $queryVerificar = "
        SELECT id, aluno_id, tipo, data_agendada, hora_agendada, status, resultado,
               LENGTH(COALESCE(tipo, '')) as tipo_length,
               HEX(COALESCE(tipo, '')) as tipo_hex
        FROM exames
        WHERE COALESCE(TRIM(tipo), '') = '' OR tipo IS NULL
        ORDER BY data_agendada DESC, id DESC
    ";
    
    $examesVazios = $db->fetchAll($queryVerificar);
    $totalVazios = count($examesVazios);
    
    if ($totalVazios > 0) {
        echo "<div class='warning'>\n";
        echo "⚠️ <strong>Encontrados {$totalVazios} exames com tipo vazio</strong><br>\n";
        echo "Esses exames não aparecem nas listagens filtradas por tipo.\n";
        echo "</div>\n";
        
        // Mostrar tabela com os exames
        echo "<table>\n";
        echo "<thead>\n";
        echo "<tr>\n";
        echo "<th>ID</th>\n";
        echo "<th>Aluno ID</th>\n";
        echo "<th>Tipo Atual</th>\n";
        echo "<th>Data</th>\n";
        echo "<th>Hora</th>\n";
        echo "<th>Status</th>\n";
        echo "<th>Resultado</th>\n";
        echo "</tr>\n";
        echo "</thead>\n";
        echo "<tbody>\n";
        
        foreach ($examesVazios as $exame) {
            $tipoDisplay = empty($exame['tipo']) ? '<em style="color: red;">VAZIO</em>' : htmlspecialchars($exame['tipo']);
            $dataDisplay = $exame['data_agendada'] ? date('d/m/Y', strtotime($exame['data_agendada'])) : '-';
            $horaDisplay = $exame['hora_agendada'] ? substr($exame['hora_agendada'], 0, 5) : '-';
            
            echo "<tr>\n";
            echo "<td>{$exame['id']}</td>\n";
            echo "<td>{$exame['aluno_id']}</td>\n";
            echo "<td>{$tipoDisplay}</td>\n";
            echo "<td>{$dataDisplay}</td>\n";
            echo "<td>{$horaDisplay}</td>\n";
            echo "<td>" . htmlspecialchars($exame['status'] ?? '-') . "</td>\n";
            echo "<td>" . htmlspecialchars($exame['resultado'] ?? '-') . "</td>\n";
            echo "</tr>\n";
        }
        
        echo "</tbody>\n";
        echo "</table>\n";
        
        // =====================================================
        // PASSO 2: FORMULÁRIO PARA CORREÇÃO
        // =====================================================
        if ($acao !== 'corrigir') {
            echo "<h2>🔧 Passo 2: Definir Tipo para Correção</h2>\n";
            echo "<div class='info'>\n";
            echo "Selecione o tipo que será aplicado a TODOS os exames listados acima.<br>\n";
            echo "<strong>Atenção:</strong> Esta operação afetará {$totalVazios} registros.\n";
            echo "</div>\n";
            
            echo "<form method='GET' action=''>\n";
            echo "<input type='hidden' name='acao' value='corrigir'>\n";
            
            echo "<div class='form-group'>\n";
            echo "<label for='tipo'>Tipo a Definir:</label>\n";
            echo "<select name='tipo' id='tipo' required>\n";
            echo "<option value=''>-- Selecione o tipo --</option>\n";
            echo "<option value='medico'>Exame Médico</option>\n";
            echo "<option value='psicotecnico'>Exame Psicotécnico</option>\n";
            echo "<option value='teorico'>Prova Teórica</option>\n";
            echo "<option value='pratico'>Prova Prática</option>\n";
            echo "</select>\n";
            echo "</div>\n";
            
            echo "<button type='submit' class='btn btn-danger' onclick=\"return confirm('Tem certeza que deseja definir o tipo para TODOS os {$totalVazios} exames listados? Esta ação não pode ser desfeita facilmente.');\">\n";
            echo "🔧 Corrigir Exames\n";
            echo "</button>\n";
            
            echo "<a href='?' class='btn'>↻ Recarregar</a>\n";
            echo "</form>\n";
        }
        
        // =====================================================
        // PASSO 3: EXECUTAR CORREÇÃO
        // =====================================================
        if ($acao === 'corrigir' && !empty($tipoDefinir)) {
            echo "<h2>⚙️ Passo 3: Executando Correção</h2>\n";
            
            // Validar tipo
            $tiposValidos = ['medico', 'psicotecnico', 'teorico', 'pratico'];
            if (!in_array($tipoDefinir, $tiposValidos, true)) {
                echo "<div class='error'>\n";
                echo "❌ Erro: Tipo inválido. Tipos permitidos: " . implode(', ', $tiposValidos) . "\n";
                echo "</div>\n";
            } else {
                // Executar UPDATE usando método update() da Database
                try {
                    // Método 1: Usar COALESCE e TRIM para capturar todos os casos
                    // Atualizar registros onde tipo é '', NULL, ou apenas espaços
                    $query1 = "
                        UPDATE exames
                        SET tipo = :tipo
                        WHERE COALESCE(TRIM(tipo), '') = '' OR tipo IS NULL
                    ";
                    
                    $stmt1 = $db->query($query1, ['tipo' => $tipoDefinir]);
                    $registrosAfetados1 = $stmt1 ? $stmt1->rowCount() : 0;
                    
                    // Método 2: Se o primeiro não funcionou, tentar com método update()
                    if ($registrosAfetados1 == 0) {
                        $resultado = $db->update(
                            'exames',
                            ['tipo' => $tipoDefinir],
                            "(COALESCE(TRIM(tipo), '') = '' OR tipo IS NULL)",
                            []
                        );
                        $registrosAfetados = $resultado ? $resultado->rowCount() : 0;
                        $stmtUsado = $resultado;
                    } else {
                        $registrosAfetados = $registrosAfetados1;
                        $stmtUsado = $stmt1;
                    }
                    
                    // Verificar quantos exames com o tipo definido agora existem
                    $examesCorrigidos = $db->fetch("SELECT COUNT(*) as total FROM exames WHERE tipo = ?", [$tipoDefinir]);
                    $totalCorrigidos = $examesCorrigidos['total'] ?? 0;
                    
                    // Verificar se ainda há exames vazios
                    $examesVaziosRestantes = $db->fetch("SELECT COUNT(*) as total FROM exames WHERE tipo = '' OR tipo IS NULL");
                    $totalRestantes = $examesVaziosRestantes['total'] ?? 0;
                    
                    if ($registrosAfetados > 0) {
                        echo "<div class='success'>\n";
                        echo "✅ <strong>Correção executada com sucesso!</strong><br>\n";
                        echo "Registros atualizados: <strong>{$registrosAfetados}</strong><br>\n";
                        echo "Total de exames com tipo '{$tipoDefinir}': <strong>{$totalCorrigidos}</strong><br>\n";
                        echo "Os exames agora aparecerão nas listagens filtradas por tipo.\n";
                        echo "</div>\n";
                        
                        if ($totalRestantes > 0) {
                            echo "<div class='warning'>\n";
                            echo "⚠️ Ainda há {$totalRestantes} exames com tipo vazio. Isso pode indicar que alguns registros não foram encontrados pela condição WHERE.\n";
                            echo "</div>\n";
                        } else {
                            echo "<div class='info'>\n";
                            echo "✅ Todos os exames agora têm tipo definido!\n";
                            echo "</div>\n";
                        }
                    } else {
                        // Tentar método alternativo com query direta
                        echo "<div class='warning'>\n";
                        echo "⚠️ Nenhum registro foi atualizado com o método update(). Tentando método alternativo...<br>\n";
                        echo "</div>\n";
                        
                        // Método alternativo: tentar atualizar um por um usando IDs específicos
                        echo "<div class='info'>\n";
                        echo "Tentando atualizar registros individualmente por ID...<br>\n";
                        echo "</div>\n";
                        
                        // Obter IDs dos exames vazios
                        $idsVazios = $db->fetchAll("
                            SELECT id 
                            FROM exames 
                            WHERE COALESCE(TRIM(tipo), '') = '' OR tipo IS NULL
                        ");
                        
                        $atualizadosIndividuais = 0;
                        foreach ($idsVazios as $row) {
                            $exameId = $row['id'];
                            try {
                                $resultado = $db->update(
                                    'exames',
                                    ['tipo' => $tipoDefinir],
                                    'id = ?',
                                    [$exameId]
                                );
                                
                                if ($resultado && $resultado->rowCount() > 0) {
                                    $atualizadosIndividuais++;
                                }
                            } catch (Exception $e) {
                                echo "<div class='warning'>\n";
                                echo "Erro ao atualizar exame ID {$exameId}: " . htmlspecialchars($e->getMessage()) . "<br>\n";
                                echo "</div>\n";
                            }
                        }
                        
                        // Verificar novamente
                        $examesCorrigidos = $db->fetch("SELECT COUNT(*) as total FROM exames WHERE tipo = ?", [$tipoDefinir]);
                        $totalCorrigidos = $examesCorrigidos['total'] ?? 0;
                        $examesVaziosRestantes = $db->fetch("SELECT COUNT(*) as total FROM exames WHERE COALESCE(TRIM(tipo), '') = '' OR tipo IS NULL");
                        $totalRestantes = $examesVaziosRestantes['total'] ?? 0;
                        
                        if ($atualizadosIndividuais > 0) {
                            echo "<div class='success'>\n";
                            echo "✅ <strong>Correção executada com método individual!</strong><br>\n";
                            echo "Registros atualizados: <strong>{$atualizadosIndividuais}</strong><br>\n";
                            echo "Total de exames com tipo '{$tipoDefinir}': <strong>{$totalCorrigidos}</strong><br>\n";
                            if ($totalRestantes > 0) {
                                echo "⚠️ Ainda há {$totalRestantes} exames com tipo vazio.<br>\n";
                            }
                            echo "</div>\n";
                        } else {
                            echo "<div class='error'>\n";
                            echo "❌ Nenhum registro foi atualizado, mesmo atualizando individualmente.<br>\n";
                            echo "Total de exames vazios restantes: <strong>{$totalRestantes}</strong><br>\n";
                            echo "<strong>Possíveis causas:</strong><br>\n";
                            echo "- O campo tipo pode ser um ENUM que não permite atualização direta<br>\n";
                            echo "- Pode haver constraint ou trigger bloqueando a atualização<br>\n";
                            echo "- O campo tipo pode ter um valor padrão ou não permitir NULL/vazio<br>\n";
                            echo "- Problema de permissões ou transação não commitada<br>\n";
                            echo "<br>\n";
                            echo "<strong>Solução manual:</strong><br>\n";
                            echo "Execute diretamente no MySQL:<br>\n";
                            echo "<pre>";
                            echo "UPDATE exames SET tipo = '{$tipoDefinir}' WHERE COALESCE(TRIM(tipo), '') = '' OR tipo IS NULL;\n";
                            echo "</pre>\n";
                            echo "</div>\n";
                        }
                    }
                    
                } catch (Exception $e) {
                    echo "<div class='error'>\n";
                    echo "❌ Erro ao executar correção: " . htmlspecialchars($e->getMessage()) . "<br>\n";
                    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
                    echo "</div>\n";
                }
            }
        }
        
    } else {
        echo "<div class='success'>\n";
        echo "✅ <strong>Nenhum exame com tipo vazio encontrado!</strong><br>\n";
        echo "Todos os exames têm tipo definido corretamente.\n";
        echo "</div>\n";
    }
    
    // =====================================================
    // RESUMO GERAL
    // =====================================================
    echo "<h2>📈 Resumo Geral</h2>\n";
    
    $resumo = $db->fetchAll("
        SELECT tipo, COUNT(*) as total
        FROM exames
        GROUP BY tipo
        ORDER BY tipo
    ");
    
    echo "<table>\n";
    echo "<thead>\n";
    echo "<tr>\n";
    echo "<th>Tipo</th>\n";
    echo "<th>Total</th>\n";
    echo "</tr>\n";
    echo "</thead>\n";
    echo "<tbody>\n";
    
    foreach ($resumo as $item) {
        $tipoDisplay = empty($item['tipo']) ? '<em style="color: red;">VAZIO</em>' : htmlspecialchars($item['tipo']);
        echo "<tr>\n";
        echo "<td>{$tipoDisplay}</td>\n";
        echo "<td><strong>{$item['total']}</strong></td>\n";
        echo "</tr>\n";
    }
    
    echo "</tbody>\n";
    echo "</table>\n";
    
    // =====================================================
    // CONSULTA SQL PARA VERIFICAÇÃO
    // =====================================================
    echo "<h2>🔍 Consulta SQL para Verificação Manual</h2>\n";
    echo "<div class='info'>\n";
    echo "Use estas queries no seu cliente MySQL para verificar manualmente:\n";
    echo "</div>\n";
    echo "<pre>";
    echo "-- Verificar exames com tipo vazio\n";
    echo "SELECT id, aluno_id, tipo, data_agendada, hora_agendada\n";
    echo "FROM exames\n";
    echo "WHERE tipo = '' OR tipo IS NULL\n";
    echo "ORDER BY id DESC\n";
    echo "LIMIT 10;\n\n";
    echo "-- Verificar resumo por tipo\n";
    echo "SELECT tipo, COUNT(*) as total\n";
    echo "FROM exames\n";
    echo "GROUP BY tipo\n";
    echo "ORDER BY tipo;\n\n";
    echo "-- Verificar últimos exames criados\n";
    echo "SELECT id, aluno_id, tipo, data_agendada, hora_agendada, status\n";
    echo "FROM exames\n";
    echo "ORDER BY id DESC\n";
    echo "LIMIT 10;\n";
    echo "</pre>\n";
    
    echo "<div class='info'>\n";
    echo "✅ Script executado com sucesso!<br>\n";
    echo "Data/Hora: " . date('d/m/Y H:i:s') . "\n";
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<div class='error'>\n";
    echo "❌ Erro ao executar script: " . htmlspecialchars($e->getMessage()) . "\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
    echo "</div>\n";
}

echo "</div>\n";
echo "</body>\n";
echo "</html>\n";
?>

