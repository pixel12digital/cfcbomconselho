# ✅ CORREÇÃO DO CAMPO RG - INSTRUÇÕES

## 📋 Problema Identificado

O campo RG no cadastro de alunos tinha **duas restrições** que impediam o cadastro de RGs de diferentes formatos:

1. **Máscara JavaScript**: Restrita ao formato `00.000.000-0` (apenas números, formato SP)
2. **Banco de Dados**: Campo `VARCHAR(20)` que pode ser limitante

## 🔧 Correções Implementadas

### 1. Frontend (✅ CONCLUÍDO)

**Arquivo**: `admin/pages/alunos.php`

#### Mudanças realizadas:

1. **Removida a máscara JavaScript** que restringia o formato do RG
   - Antes: `new IMask(document.getElementById('rg'), { mask: '00.000.000-0' });`
   - Depois: Comentário explicando que RG não tem máscara

2. **Atualizado o campo HTML**:
   - Adicionado `maxlength="30"` para aceitar até 30 caracteres
   - Placeholder mudado para: "Digite o RG (aceita letras)"

### 2. Banco de Dados (⚠️ PENDENTE)

**Arquivo**: `atualizar_campo_rg_alunos.sql`

O script SQL foi criado e está pronto para execução.

## 🚀 Como Executar a Atualização do Banco de Dados

### Opção 1: Via phpMyAdmin (RECOMENDADO)

1. **Inicie o XAMPP** e certifique-se de que o MySQL está rodando
2. **Acesse o phpMyAdmin**: http://localhost/phpmyadmin
3. **Selecione o banco** `cfc_bom_conselho`
4. **Vá na aba "SQL"**
5. **Cole o seguinte comando**:

```sql
ALTER TABLE alunos MODIFY COLUMN rg VARCHAR(30) DEFAULT NULL;
```

6. **Clique em "Executar"**

### Opção 2: Via Terminal MySQL

1. **Inicie o XAMPP** e o MySQL
2. **Abra o terminal** e execute:

```bash
C:\xampp\mysql\bin\mysql.exe -u root -p cfc_bom_conselho
```

3. **Digite a senha** (geralmente vazia no XAMPP, apenas pressione Enter)
4. **Execute o comando**:

```sql
ALTER TABLE alunos MODIFY COLUMN rg VARCHAR(30) DEFAULT NULL;
```

5. **Verifique a alteração**:

```sql
DESCRIBE alunos;
```

Você deverá ver o campo `rg` com `varchar(30)`.

### Opção 3: Via Script PHP

1. **Inicie o MySQL no XAMPP**
2. **Execute**:

```bash
C:\xampp\php\php.exe executar_atualizacao_rg.php
```

## 📚 Formatos de RG no Brasil

O RG tem formatos diferentes em cada estado brasileiro:

| Estado | Formato | Exemplo |
|--------|---------|---------|
| SP | 00.000.000-0 | 12.345.678-9 |
| RJ | 00.000.000-0 | 12.345.678-9 |
| MG | MG-00.000.000 | MG-12.345.678 |
| RS | 0000000000 | 1234567890 |
| SC | 0.000.000 | 1.234.567 |
| PR | 00.000.000-0 | 12.345.678-9 |
| BA | 00000000-00 | 12345678-90 |

## ✅ Resultado Esperado

Após a execução das correções:

- ✅ Campo RG aceita **até 30 caracteres**
- ✅ Aceita **letras e números**
- ✅ Sem máscara restritiva
- ✅ Placeholder intuitivo: "Digite o RG (aceita letras)"
- ✅ Compatível com **todos os formatos** de RG dos estados brasileiros

## 🧪 Como Testar

1. **Acesse o sistema** e vá para a página de alunos
2. **Clique em "Novo Aluno"**
3. **Tente cadastrar** RGs de diferentes formatos:
   - Com letras: `MG-12.345.678`
   - Com 10 dígitos: `1234567890`
   - Formato tradicional: `12.345.678-9`

Todos devem funcionar sem problemas!

## 📝 Arquivos Modificados

1. ✅ `admin/pages/alunos.php` - Removida máscara e atualizado placeholder
2. ⚠️ `atualizar_campo_rg_alunos.sql` - Script SQL criado (aguardando execução)
3. 📄 `executar_atualizacao_rg.php` - Script PHP helper (opcional)

## 🎯 Status

- [x] Frontend corrigido
- [ ] Banco de dados atualizado (aguardando execução manual)

---

**Última atualização**: Outubro 2025

