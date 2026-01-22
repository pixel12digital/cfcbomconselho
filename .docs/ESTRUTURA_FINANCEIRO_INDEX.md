# Estrutura Condicional - financeiro/index.php

## Mapeamento Completo

### Bloco 1: Busca (linhas 10-35)
```
10: if (!$isAluno) - ABRE
28:   if ($search) - aninhado
30:   endif - fecha 28
35: endif - fecha 10 ✓
```

### Bloco 2: Principal (linhas 37-561)
```
37: if (!$isAluno && !empty($students) && !$student) - ABRE PRINCIPAL
70: elseif ($student) - continua principal
  | 103: if ($hasBlocked) - aninhado
  | 105: elseif ($totalDebt > 0) - continua
  | 107: else - continua
  | 109: endif - fecha 103
  | 117: if (empty($enrollments)) - aninhado
  | 123: else - continua
  | 195: endif - fecha 117
196: else - continua principal
  | 197: if (!$isAluno && !$student && (!isset($students) || empty($students))) - aninhado
  | 416: endif - fecha 197
  | 417: if (!$isAluno && !$student && empty($pendingEnrollments) && empty($search) && (empty($students) || !isset($students))) - aninhado
  |   | 423: if ($hasCards) - aninhado
  |   | 559: endif - fecha 423
  | 560: endif - fecha 417
561: endif - fecha 196 (else) e 37 (if principal) ✓
```

## Verificação

- Bloco 1: ✓ Balanceado
- Bloco 2: ✓ Balanceado

## Problema Identificado

A estrutura parece estar correta, mas o erro persiste. O problema pode ser que o PHP está esperando um `endif` adicional ou há algum problema com a estrutura do `else` na linha 196.
