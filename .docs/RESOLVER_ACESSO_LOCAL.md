# Resolver Acesso Local - XAMPP

## Problema: "localhost recusou estabelecer ligação"

Isso significa que o **Apache do XAMPP não está rodando**.

## Solução Rápida

### 1. Iniciar o XAMPP

1. Abra o **XAMPP Control Panel**
2. Clique em **"Start"** ao lado de **Apache**
3. Aguarde até aparecer **"Running"** (verde)
4. Se também precisar do banco, clique em **"Start"** ao lado de **MySQL**

### 2. Verificar se está funcionando

Abra no navegador:
- `http://localhost/` → Deve mostrar a página inicial do XAMPP
- `http://localhost/cfc-v.1/public_html/` → Deve mostrar a tela de login

### 3. Se ainda não funcionar

#### Verificar porta 80
O Apache precisa usar a porta 80. Se estiver ocupada:

1. No XAMPP Control Panel, clique em **"Config"** → **"httpd.conf"**
2. Procure por `Listen 80`
3. Se estiver ocupada, pode mudar para `Listen 8080` (mas aí acessa com `http://localhost:8080/`)

#### Verificar firewall
O Windows Firewall pode estar bloqueando:
1. Abra **Windows Defender Firewall**
2. Permita o Apache através do firewall

## Verificar se o item "CFC" aparece no menu

### Pré-requisito: Estar logado como ADMIN

1. Acesse: `http://localhost/cfc-v.1/public_html/`
2. Faça login
3. Verifique se você está como **ADMIN**:
   - No canto superior direito, deve mostrar seu nome
   - Ao clicar, deve mostrar "Atuando como: **ADMIN**"

### Se não estiver como ADMIN

**Opção 1: Alterar no banco de dados**
```sql
-- Verificar role atual
SELECT id, nome, email, role FROM usuarios WHERE email = 'seu@email.com';

-- Alterar para ADMIN
UPDATE usuarios SET role = 'ADMIN' WHERE email = 'seu@email.com';
```

**Opção 2: Criar novo usuário ADMIN**
```sql
INSERT INTO usuarios (nome, email, senha, role, cfc_id, ativo, created_at)
VALUES (
    'Admin Local',
    'admin@local.com',
    '$2y$10$...', -- Hash da senha (use password_hash no PHP)
    'ADMIN',
    1, -- ID do CFC
    1,
    NOW()
);
```

### Limpar cache do navegador

1. Pressione **Ctrl + Shift + Delete**
2. Selecione **"Imagens e arquivos em cache"**
3. Clique em **"Limpar dados"**
4. Recarregue a página com **Ctrl + F5**

## Comparar Local vs Produção

### Em Produção (funcionando)
- ✅ Item "CFC" aparece no menu
- ✅ URL: `https://painel.cfcbomconselho.com.br/configuracoes/cfc`

### Localmente (deve funcionar igual)
- ✅ Código idêntico (linha 246 do `shell.php`)
- ⚠️ Precisa: XAMPP rodando + usuário ADMIN

## Checklist Rápido

- [ ] XAMPP Control Panel aberto
- [ ] Apache com status "Running" (verde)
- [ ] MySQL com status "Running" (verde) - se precisar do banco
- [ ] Acessar `http://localhost/` mostra página do XAMPP
- [ ] Acessar `http://localhost/cfc-v.1/public_html/` mostra login
- [ ] Login feito com usuário que tem role = 'ADMIN'
- [ ] Menu lateral mostra item "CFC"
- [ ] Clicar em "CFC" abre `/configuracoes/cfc`

## Próximos Passos

Após resolver o acesso local:
1. Testar upload de logo
2. Verificar se salva no disco e banco
3. Testar edição de Nome/CNPJ
4. Fazer commit das alterações
