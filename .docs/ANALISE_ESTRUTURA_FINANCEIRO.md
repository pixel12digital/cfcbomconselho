# Análise da Estrutura Condicional - financeiro/index.php

## Mapeamento dos Blocos

### Bloco 1: Busca de Aluno (linhas 10-35)
- **Linha 10:** `if (!$isAluno)` - ABRE
- **Linha 28:** `if ($search)` - aninhado
- **Linha 30:** `endif` - fecha linha 28
- **Linha 35:** `endif` - fecha linha 10 ✓

### Bloco 2: Principal de Exibição (linhas 37-417)
- **Linha 37:** `if (!$isAluno && !empty($students) && !$student)` - ABRE
- **Linha 70:** `elseif ($student)` - continua
  - **Linha 103:** `if ($hasBlocked)` - aninhado
  - **Linha 105:** `elseif ($totalDebt > 0)` - continua
  - **Linha 107:** `else` - continua
  - **Linha 109:** `endif` - fecha linha 103
  - **Linha 117:** `if (empty($enrollments))` - aninhado
  - **Linha 123:** `else` - continua
  - **Linha 195:** `endif` - fecha linha 117
- **Linha 196:** `else` - continua bloco principal
  - **Linha 197:** `if (!$isAluno && !$student && (!isset($students) || empty($students)))` - aninhado
  - **Linha 416:** `endif` - fecha linha 197
- **Linha 417:** `endif` - fecha linha 196 (else) e linha 37 (if principal) ✓

### Bloco 3: Cards de Resumo (linhas 418-561)
- **Linha 418:** `if (!$isAluno && !$student && empty($pendingEnrollments) && empty($search) && (empty($students) || !isset($students)))` - ABRE
- **Linha 424:** `if ($hasCards)` - aninhado
  - **Linha 560:** `endif` - fecha linha 424
- **Linha 561:** `endif` - fecha linha 418 ✓

## Problema Identificado

A estrutura parece estar correta, mas o erro persiste. O problema pode ser que o PHP está esperando um `endif` adicional ou há algum problema com a estrutura do `else` na linha 196.

## Solução

Vou verificar se o bloco da linha 418 deveria estar dentro do `else` da linha 196 ou se é realmente um bloco separado.
