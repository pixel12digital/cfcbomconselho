# Instruções para Executar Diagnóstico do Aluno 167

## ⚠️ IMPORTANTE: Banco Remoto (Hostinger)

O sistema está configurado para banco remoto:
- **Host:** `auth-db803.hstgr.io`
- **Banco:** `u502697186_cfcbomconselho`
- **Usuário:** `u502697186_cfcbomconselho`

---

## Opção 1: Via Navegador (Recomendado)

### Passo 1: Identificar a URL do Sistema

A URL base está configurada em `includes/config.php`:
- **Produção:** `https://linen-mantis-198436.hostingersite.com`
- **Local:** Ajuste conforme seu ambiente

### Passo 2: Fazer Login como Admin

1. Acesse a área administrativa do sistema
2. Faça login com credenciais de administrador

### Passo 3: Identificar a URL Correta do Sistema

**Opção 1:** Verificar no navegador a URL que você usa para acessar o sistema administrativo.

**Opção 2:** Acessar o script de verificação:
```
[SEU_HOST]/admin/tools/verificar-url.php
```
Este script mostrará a URL correta para usar.

**Opção 3:** Verificar o arquivo `includes/config.php` linha 65, mas pode não refletir a URL real.

### Passo 4: Acessar o Script de Diagnóstico

Acesse a URL usando o padrão:
```
[SUA_URL_BASE]/admin/tools/diagnostico-aluno-167-turma-teorica.php?turma_id=16
```

**Exemplo:**
- Se você acessa o admin em: `https://seudominio.com/admin/index.php`
- Então o diagnóstico será: `https://seudominio.com/admin/tools/diagnostico-aluno-167-turma-teorica.php?turma_id=16`

**⚠️ IMPORTANTE:** Substitua `16` pelo ID real da turma que você está tentando matricular.

### Passo 4: Analisar os Resultados

O script exibirá:
- ✅ Dados básicos do aluno 167
- ✅ Dados da turma
- ✅ Compatibilidade de CFC
- ✅ Verificação de matrícula
- ✅ Verificação de exames
- ✅ Verificação financeira
- ✅ Simulação da query de candidatos
- ✅ Elegibilidade final

Cada seção mostrará se o critério foi atendido ou não, facilitando identificar o problema.

---

## Opção 2: Executar Queries SQL Manualmente

Se preferir executar as queries manualmente, use o documento:

📄 **`docs/QUERIES_DIAGNOSTICO_ALUNO_167.md`**

Este documento contém todas as queries SQL necessárias para verificar cada critério.

### Como Executar:

#### Via phpMyAdmin (Hostinger)

1. Acesse o painel de controle da Hostinger
2. Abra o **phpMyAdmin**
3. Selecione o banco: `u502697186_cfcbomconselho`
4. Vá para a aba **SQL**
5. Cole e execute as queries do documento, substituindo `?` pelo `turma_id`

#### Via Cliente MySQL (se tiver acesso SSH)

```bash
mysql -h auth-db803.hstgr.io -u u502697186_cfcbomconselho -p u502697186_cfcbomconselho
```

Depois cole e execute as queries.

---

## Opção 3: Verificar Logs do Servidor

O script de diagnóstico também grava logs no `error_log` do PHP. Verifique os logs do servidor para ver informações detalhadas sobre a execução.

**Logs a procurar:**
- `[TURMAS TEORICAS API]` - Logs da API de alunos aptos
- `[GUARDS EXAMES]` - Logs da validação de exames
- `[VALIDACAO FINANCEIRA EXAMES]` - Logs da validação financeira

---

## Troubleshooting

### Problema: "Acesso negado"

**Solução:** Certifique-se de estar logado como administrador no sistema antes de acessar o script.

### Problema: "Erro na conexão com banco de dados"

**Solução:** 
- Verifique se as credenciais em `includes/config.php` estão corretas
- Verifique se o acesso remoto ao MySQL está liberado na Hostinger
- Verifique se o IP do servidor está autorizado a conectar

### Problema: Script retorna erro 500

**Solução:**
- Verifique os logs de erro do PHP
- Certifique-se de que todos os arquivos necessários estão presentes:
  - `admin/includes/guards_exames.php`
  - `admin/includes/FinanceiroAlunoHelper.php`
- Verifique permissões de leitura nos arquivos

---

## Resultado Esperado

Após executar o diagnóstico, você deve identificar qual critério está falhando:

1. **CFC incompatível:** Aluno tem CFC diferente da turma
2. **Status do aluno:** Aluno não está 'ativo'
3. **Já matriculado:** Aluno já está matriculado nesta turma
4. **Exames não OK:** Exames não passam na validação
5. **Financeiro não OK:** Financeiro não passa na validação

Com base no resultado, siga para a próxima etapa: **Implementação da Correção** (conforme `docs/AUDITORIA_TURMAS_TEORICAS_MATRICULA.md`).

