# Investigação: Acesso do Aluno "cliente teste 001"

**Data:** 2026-01-21  
**Aluno:** cliente teste 001 (CPF: 29561350076)

---

## 📋 Resultado da Investigação

### ✅ Status: Aluno possui acesso vinculado

**Dados do Aluno:**
- ID: 1
- Nome: cliente teste 001
- CPF: 29561350076
- Email: contato@pixel12digital.com.br
- CFC ID: 1
- **User ID vinculado: 3**

**Dados do Usuário:**
- User ID: 3
- Nome no sistema: Charles Dietrich Wutzke
- Email: contato@pixel12digital.com.br
- Status: **ativo**
- Deve trocar senha: Não
- Criado em: 14/01/2026 12:10:02
- Perfis/Roles: **ALUNO**

---

## 🔗 Como Acessar/Editar o Acesso

### Opção 1: Lista de Usuários (Recomendado)

1. **Acesse:** `/usuarios`
2. **Procure por:**
   - Nome: "Charles Dietrich Wutzke"
   - Email: "contato@pixel12digital.com.br"
3. **Na coluna "Vínculo"** deve aparecer: "Aluno: cliente teste 001"
4. **Clique em "Editar"** para gerenciar o acesso

### Opção 2: Editar Diretamente

**URL direta:** `/usuarios/3/editar`

**Ações disponíveis:**
- ✅ Alterar status (ativo/inativo)
- ✅ Gerar senha temporária
- ✅ Gerar link de ativação
- ✅ Enviar link por email
- ✅ Alterar email (se necessário)
- ✅ Alterar perfis/roles

### Opção 3: Ver Detalhes do Aluno

**URL:** `/alunos/1`

**Nota:** A página do aluno atualmente **não mostra informações do acesso vinculado**. Isso pode ser uma melhoria futura.

---

## 💡 Como Proceder de Acordo com o Sistema

### Se precisar resetar a senha:

1. Acesse `/usuarios/3/editar`
2. Na seção "Acesso e Segurança", escolha uma opção:
   - **"Gerar Senha Temporária"** - Gera uma senha que será exibida na tela (copie e envie ao aluno)
   - **"Gerar Link de Ativação"** - Gera um link que permite ao aluno definir sua própria senha
   - **"Enviar Link por Email"** - Envia o link de ativação automaticamente por email

### Se precisar verificar o acesso:

1. Acesse `/usuarios`
2. Procure pelo email `contato@pixel12digital.com.br`
3. Verifique o status e vínculo

### Se o aluno não conseguir fazer login:

1. Verifique se o status está "ativo" em `/usuarios/3/editar`
2. Gere uma nova senha temporária
3. Envie as credenciais ao aluno:
   - Email: `contato@pixel12digital.com.br`
   - Senha: (a senha temporária gerada)

---

## 🔍 Por que não aparece na lista de "Criar Acesso"?

O aluno **não aparece** na lista de "Criar Acesso" (`/usuarios/novo`) porque:

1. ✅ Já possui `user_id` vinculado (ID: 3)
2. ✅ O usuário existe e está válido na tabela `usuarios`
3. ✅ A query filtra apenas alunos **sem** acesso vinculado

**Isso é o comportamento esperado!** O sistema está funcionando corretamente.

---

## 📊 Fluxo do Sistema

```
Aluno criado → Email informado → Usuário criado automaticamente
                                      ↓
                              Vinculado ao aluno (user_id)
                                      ↓
                    Aparece em /usuarios (lista de usuários)
                    NÃO aparece em /usuarios/novo (criar acesso)
```

---

## 🛠️ Melhorias Sugeridas

### 1. Mostrar acesso na página do aluno

Adicionar na página `/alunos/{id}` uma seção mostrando:
- Se o aluno tem acesso vinculado
- Link direto para editar o acesso
- Status do acesso (ativo/inativo)

### 2. Link direto na lista de alunos

Na lista de alunos (`/alunos`), adicionar coluna ou ação:
- "Ver Acesso" - se tiver acesso vinculado
- "Criar Acesso" - se não tiver

### 3. Busca por aluno na lista de usuários

Melhorar a busca em `/usuarios` para permitir buscar por:
- Nome do aluno vinculado
- CPF do aluno vinculado

---

## 📝 Scripts de Diagnóstico

Foram criados scripts para facilitar investigações futuras:

1. **`tools/verificar_aluno_acesso.php`** - Verifica e cria acesso para aluno
2. **`tools/investigar_acesso_aluno.php`** - Investiga acesso existente de aluno

**Uso:**
```bash
php tools/investigar_acesso_aluno.php "nome do aluno"
php tools/investigar_acesso_aluno.php "CPF"
```

---

## ✅ Conclusão

O aluno "cliente teste 001" **já possui acesso ao sistema** e está funcionando corretamente:

- ✅ Usuário criado e vinculado
- ✅ Status ativo
- ✅ Perfil ALUNO configurado
- ✅ Email: contato@pixel12digital.com.br

**Para gerenciar o acesso:** `/usuarios/3/editar`

**Para ver na lista:** `/usuarios` (procure por "Charles Dietrich Wutzke" ou o email)
