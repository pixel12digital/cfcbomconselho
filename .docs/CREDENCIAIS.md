# 🔐 Credenciais de Acesso

## Usuário Administrador Padrão

**Configuração:** Ver arquivo `app/Config/Credentials.php`

### Credenciais Iniciais

```
Email: admin@cfc.local
Senha: admin123
```

### ⚠️ IMPORTANTE

1. **Alterar a senha após o primeiro login!**
2. As credenciais padrão são apenas para instalação inicial
3. A senha está hashada no banco de dados (bcrypt)

### Para Alterar a Senha

**Opção 1 - Via Interface (quando implementado):**
- Acesse o sistema
- Vá em Perfil → Alterar Senha

**Opção 2 - Via Banco de Dados:**
```sql
-- Gerar novo hash (substitua 'nova_senha' pela senha desejada)
UPDATE usuarios 
SET password = '$2y$10$...' -- Hash gerado
WHERE email = 'admin@cfc.local';
```

**Opção 3 - Gerar Hash via PHP:**
```php
<?php
echo password_hash('nova_senha', PASSWORD_BCRYPT);
?>
```

### Hash da Senha Padrão

A senha `admin123` está hashada como:
```
$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
```

Este hash está no arquivo `database/seeds/001_seed_initial_data.sql`.
