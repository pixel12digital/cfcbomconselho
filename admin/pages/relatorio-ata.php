<?php
/**
 * Template: Ata da Turma
 * Sistema de Turmas Teóricas - CFC Bom Conselho
 */

if (!$dadosRelatorio) return;

$turma = $dadosRelatorio['turma'];
$aulas = $dadosRelatorio['aulas'];
$alunos = $dadosRelatorio['alunos'];
$estatisticas = $dadosRelatorio['estatisticas'];
?>

<div class="ata-content">
    <!-- Cabeçalho da Ata -->
    <div class="ata-header">
        <h3><strong>ATA DE TURMA TEÓRICA</strong></h3>
        <h4><?= htmlspecialchars($turma['nome']) ?></h4>
        <p><strong>CFC:</strong> <?= htmlspecialchars($turma['cfc_nome']) ?></p>
    </div>

    <!-- Informações da Turma -->
    <div class="ata-section">
        <h5><strong>1. INFORMAÇÕES DA TURMA</strong></h5>
        <table class="ata-table">
            <tr>
                <td width="30%"><strong>Nome da Turma:</strong></td>
                <td><?= htmlspecialchars($turma['nome']) ?></td>
            </tr>
            <tr>
                <td><strong>Instrutor:</strong></td>
                <td><?= htmlspecialchars($turma['instrutor_nome']) ?></td>
            </tr>
            <tr>
                <td><strong>Período:</strong></td>
                <td><?= date('d/m/Y', strtotime($turma['data_inicio'])) ?> a <?= date('d/m/Y', strtotime($turma['data_fim'])) ?></td>
            </tr>
            <tr>
                <td><strong>Categoria:</strong></td>
                <td><?= $turma['categoria_cnh'] ?></td>
            </tr>
            <tr>
                <td><strong>Capacidade:</strong></td>
                <td><?= $turma['capacidade_maxima'] ?> alunos</td>
            </tr>
            <tr>
                <td><strong>Frequência Mínima:</strong></td>
                <td><?= $turma['frequencia_minima'] ?>%</td>
            </tr>
            <tr>
                <td><strong>Sala/Local:</strong></td>
                <td><?= htmlspecialchars($turma['sala_local']) ?></td>
            </tr>
        </table>
    </div>

    <!-- Lista de Alunos -->
    <div class="ata-section">
        <h5><strong>2. RELAÇÃO DE ALUNOS MATRICULADOS</strong></h5>
        <table class="ata-table">
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="40%">Nome do Aluno</th>
                    <th width="15%">CPF</th>
                    <th width="10%">Categoria</th>
                    <th width="15%">Status</th>
                    <th width="15%">Frequência</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($alunos as $index => $aluno): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($aluno['aluno']['nome']) ?></td>
                    <td><?= htmlspecialchars($aluno['aluno']['cpf']) ?></td>
                    <td><?= $aluno['aluno']['categoria_cnh'] ?></td>
                    <td><?= ucfirst($aluno['aluno']['status_matricula']) ?></td>
                    <td>
                        <?= $aluno['frequencia']['percentual'] ?>%
                        <?php if ($aluno['frequencia']['aprovado']): ?>
                            <span class="badge bg-success ms-1">Aprovado</span>
                        <?php else: ?>
                            <span class="badge bg-danger ms-1">Reprovado</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Lista de Aulas -->
    <div class="ata-section">
        <h5><strong>3. RELAÇÃO DE AULAS REALIZADAS</strong></h5>
        <table class="ata-table">
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="20%">Data</th>
                    <th width="30%">Nome da Aula</th>
                    <th width="15%">Duração</th>
                    <th width="30%">Conteúdo Ministrado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($aulas as $index => $aula): ?>
                <tr>
                    <td><?= $aula['ordem'] ?></td>
                    <td><?= date('d/m/Y', strtotime($aula['data_aula'])) ?></td>
                    <td><?= htmlspecialchars($aula['nome_aula']) ?></td>
                    <td><?= $aula['duracao_minutos'] ?> min</td>
                    <td>
                        <?php if ($aula['conteudo_ministrado']): ?>
                            <?= htmlspecialchars(substr($aula['conteudo_ministrado'], 0, 100)) ?>
                            <?= strlen($aula['conteudo_ministrado']) > 100 ? '...' : '' ?>
                        <?php else: ?>
                            <em class="text-muted">Conteúdo não registrado</em>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Estatísticas Finais -->
    <div class="ata-section">
        <h5><strong>4. ESTATÍSTICAS FINAIS</strong></h5>
        <table class="ata-table">
            <tr>
                <td width="30%"><strong>Total de Aulas:</strong></td>
                <td><?= $estatisticas['total_aulas'] ?> aulas</td>
            </tr>
            <tr>
                <td><strong>Total de Alunos:</strong></td>
                <td><?= $estatisticas['total_alunos'] ?> alunos</td>
            </tr>
            <tr>
                <td><strong>Frequência Mínima Exigida:</strong></td>
                <td><?= $estatisticas['frequencia_minima'] ?>%</td>
            </tr>
            <tr>
                <td><strong>Data de Geração:</strong></td>
                <td><?= $estatisticas['data_geracao'] ?></td>
            </tr>
        </table>
    </div>

    <!-- Resumo de Aprovação -->
    <div class="ata-section">
        <h5><strong>5. RESUMO DE APROVAÇÃO POR FREQUÊNCIA</strong></h5>
        <div class="row">
            <div class="col-md-6">
                <div class="alert alert-success">
                    <h6><i class="fas fa-check-circle"></i> Alunos Aprovados</h6>
                    <p class="mb-0">
                        <?php 
                        $aprovados = array_filter($alunos, function($a) { return $a['frequencia']['aprovado']; });
                        echo count($aprovados) . ' alunos (' . round((count($aprovados) / count($alunos)) * 100, 2) . '%)';
                        ?>
                    </p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="alert alert-danger">
                    <h6><i class="fas fa-times-circle"></i> Alunos Reprovados</h6>
                    <p class="mb-0">
                        <?php 
                        $reprovados = array_filter($alunos, function($a) { return !$a['frequencia']['aprovado']; });
                        echo count($reprovados) . ' alunos (' . round((count($reprovados) / count($alunos)) * 100, 2) . '%)';
                        ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Assinaturas -->
    <div class="ata-section mt-5">
        <div class="row">
            <div class="col-md-6">
                <p class="text-center">
                    <strong>_________________________________</strong><br>
                    <?= htmlspecialchars($turma['instrutor_nome']) ?><br>
                    <em>Instrutor</em>
                </p>
            </div>
            <div class="col-md-6">
                <p class="text-center">
                    <strong>_________________________________</strong><br>
                    Coordenação Pedagógica<br>
                    <em>CFC <?= htmlspecialchars($turma['cfc_nome']) ?></em>
                </p>
            </div>
        </div>
    </div>
</div>
