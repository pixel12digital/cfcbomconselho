<?php
/**
 * Helpers para Categoria CNH e Tipo de Serviço
 * 
 * Centraliza a lógica de priorização de categoria e tipo de serviço:
 * 1. Prioridade: Dados da matrícula ativa
 * 2. Fallback: Dados do aluno
 * 3. Fallback: Dados de operações
 * 
 * Reutilizável entre módulos de alunos e turmas teóricas
 */

/**
 * Função helper para obter categoria priorizando matrícula ativa
 * 
 * @param array $aluno Array do aluno (pode conter categoria_cnh_matricula e categoria_cnh)
 * @return string Categoria a ser exibida
 */
function obterCategoriaExibicao($aluno) {
    // Prioridade 1: Categoria da matrícula ativa
    if (!empty($aluno['categoria_cnh_matricula'])) {
        return $aluno['categoria_cnh_matricula'];
    }
    // Prioridade 2: Categoria do aluno (fallback)
    if (!empty($aluno['categoria_cnh'])) {
        return $aluno['categoria_cnh'];
    }
    // Prioridade 3: Tentar extrair de operações
    if (!empty($aluno['operacoes'])) {
        $operacoes = is_string($aluno['operacoes']) ? json_decode($aluno['operacoes'], true) : $aluno['operacoes'];
        if (is_array($operacoes) && !empty($operacoes)) {
            $primeiraOp = $operacoes[0];
            return $primeiraOp['categoria'] ?? $primeiraOp['categoria_cnh'] ?? 'N/A';
        }
    }
    return 'N/A';
}

/**
 * Função helper para obter tipo de serviço priorizando matrícula ativa
 * 
 * @param array $aluno Array do aluno (pode conter tipo_servico_matricula e tipo_servico)
 * @return string Tipo de serviço a ser exibido
 */
function obterTipoServicoExibicao($aluno) {
    // Prioridade 1: Tipo de serviço da matrícula ativa
    if (!empty($aluno['tipo_servico_matricula'])) {
        return $aluno['tipo_servico_matricula'];
    }
    // Prioridade 2: Tipo de serviço do aluno (fallback)
    if (!empty($aluno['tipo_servico'])) {
        return $aluno['tipo_servico'];
    }
    // Prioridade 3: Tentar extrair de operações
    if (!empty($aluno['operacoes'])) {
        $operacoes = is_string($aluno['operacoes']) ? json_decode($aluno['operacoes'], true) : $aluno['operacoes'];
        if (is_array($operacoes) && !empty($operacoes)) {
            $primeiraOp = $operacoes[0];
            return $primeiraOp['tipo_servico'] ?? $primeiraOp['tipo'] ?? 'Primeira Habilitação';
        }
    }
    return 'Primeira Habilitação';
}

