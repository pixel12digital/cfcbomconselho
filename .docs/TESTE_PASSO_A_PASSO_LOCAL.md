# Teste Passo a Passo - Configurações CFC (Local)

## ✅ Pré-requisitos Verificados
- [x] XAMPP rodando (Apache + MySQL)
- [x] Diretórios criados (`storage/uploads/cfcs`, `storage/logs`, `public_html/icons`)
- [x] Código verificado (rotas, controller, view)

---

## 🧪 TESTE 1: Acessar Página de Configurações CFC

### Passos:
1. Acesse: `http://localhost/cfc-v.1/public_html/`
2. Faça login com usuário **ADMIN**
3. No menu lateral, procure o item **"CFC"** (deve estar após "Cursos Teóricos")
4. Clique em **"CFC"**

### Resultado Esperado:
- ✅ URL deve ser: `http://localhost/cfc-v.1/public_html/configuracoes/cfc`
- ✅ Título: "Configurações do CFC"
- ✅ Seção "Logo do CFC" visível
- ✅ Seção "Informações do CFC" visível (Nome e CNPJ)

### Se não aparecer o item "CFC":
- Verifique se está logado como ADMIN (canto superior direito)
- Limpe cache do navegador (Ctrl + F5)
- Verifique no banco: `SELECT role FROM usuarios WHERE id = ?`

---

## 🧪 TESTE 2: Verificar Informações do CFC

### Passos:
1. Na página `/configuracoes/cfc`, verifique a seção "Informações do CFC"
2. Os campos **Nome** e **CNPJ** devem estar preenchidos (se já houver dados no banco)

### Resultado Esperado:
- ✅ Campos editáveis
- ✅ Botão "Salvar Informações" visível

---

## 🧪 TESTE 3: Editar Nome e CNPJ

### Passos:
1. Edite o campo **Nome** (ex: "CFC Teste Local")
2. Edite o campo **CNPJ** (ex: "12.345.678/0001-90")
3. Clique em **"Salvar Informações"**
4. Aguarde mensagem de sucesso
5. Recarregue a página (F5)

### Resultado Esperado:
- ✅ Mensagem de sucesso: "Informações atualizadas com sucesso!"
- ✅ Após recarregar, os valores editados devem estar salvos
- ✅ Verificar no banco: `SELECT nome, cnpj FROM cfcs WHERE id = 1;`

### Se não salvar:
- Abra o Console do navegador (F12) e verifique erros
- Verifique se o token CSRF está presente no formulário

---

## 🧪 TESTE 4: Upload de Logo (COM DEBUG)

### Passos:
1. Na seção "Logo do CFC", clique em **"Escolher arquivo"**
2. Selecione uma imagem (JPG, PNG ou WEBP, máximo 5MB)
3. **Abra o Console do Navegador** (F12 → Console)
4. Clique em **"Fazer Upload"**
5. **Observe os logs no console**

### Logs Esperados no Console:
```
[UPLOAD DEBUG] ========================================
[UPLOAD DEBUG] Form submit iniciado
[UPLOAD DEBUG] Action: http://localhost/cfc-v.1/public_html/configuracoes/cfc/logo/upload
[UPLOAD DEBUG] Method: POST
[UPLOAD DEBUG] Enctype: multipart/form-data
[UPLOAD DEBUG] Arquivo selecionado: {
  hasFile: true,
  fileName: "logo.png",
  fileSize: 123456,
  fileSizeMB: "0.12 MB",
  fileType: "image/png"
}
[UPLOAD DEBUG] FormData keys: ["logo", "csrf_token"]
[UPLOAD DEBUG] CSRF Token: presente
[UPLOAD DEBUG] ========================================
[UPLOAD DEBUG] ✅ Validações passaram, enviando requisição...
[UPLOAD DEBUG] Resposta recebida
[UPLOAD DEBUG] Status: 302
[UPLOAD DEBUG] Headers de debug: {
  "x-upload-debug": "method_called=uploadLogo",
  "x-upload-debug-files": "1",
  "x-upload-debug-haslogo": "yes",
  "x-upload-debug-success": "true",
  "x-upload-debug-dbupdate": "true",
  "x-upload-debug-filepath": "storage/uploads/cfcs/cfc_1_..."
}
[UPLOAD DEBUG] Redirecionando para: http://localhost/cfc-v.1/public_html/configuracoes/cfc
```

### Resultado Esperado:
- ✅ Mensagem de sucesso: "Logo atualizado e ícones PWA gerados com sucesso!"
- ✅ Logo aparece na página (seção "Logo atual")
- ✅ Arquivo salvo em: `storage/uploads/cfcs/cfc_1_*.png`
- ✅ Ícones PWA gerados em: `public_html/icons/1/icon-192x192.png` e `icon-512x512.png`
- ✅ Banco atualizado: `SELECT logo_path FROM cfcs WHERE id = 1;`

### Se não funcionar:
- Verifique os logs no console (mensagens de erro)
- Verifique o arquivo de log: `storage/logs/upload_logo.log`
- Verifique permissões do diretório `storage/uploads/cfcs/`

---

## 🧪 TESTE 5: Verificar Ícones PWA Gerados

### Passos:
1. Após upload bem-sucedido, verifique se os ícones foram gerados
2. Execute no PowerShell (na raiz do projeto):

```powershell
# Verificar se os ícones existem
Test-Path "public_html\icons\1\icon-192x192.png"
Test-Path "public_html\icons\1\icon-512x512.png"

# Listar arquivos
Get-ChildItem "public_html\icons\1" -ErrorAction SilentlyContinue
```

### Resultado Esperado:
- ✅ Ambos os arquivos devem existir
- ✅ Tamanho: `icon-192x192.png` = 192x192 pixels
- ✅ Tamanho: `icon-512x512.png` = 512x512 pixels

---

## 🧪 TESTE 6: Verificar Manifest PWA Dinâmico

### Passos:
1. Acesse: `http://localhost/cfc-v.1/public_html/pwa-manifest.php`
2. Deve retornar JSON com os dados do CFC

### Resultado Esperado:
```json
{
    "name": "Nome do CFC (do banco)",
    "short_name": "Nome do CFC...",
    "description": "Sistema de gestão para Nome do CFC",
    "icons": [
        {
            "src": "./icons/1/icon-192x192.png",
            "sizes": "192x192",
            "type": "image/png"
        },
        {
            "src": "./icons/1/icon-512x512.png",
            "sizes": "512x512",
            "type": "image/png"
        }
    ]
}
```

### Se não mostrar ícones dinâmicos:
- Verifique se `logo_path` está preenchido no banco
- Verifique se os ícones existem em `public_html/icons/1/`

---

## 🧪 TESTE 7: Remover Logo

### Passos:
1. Se houver logo cadastrado, clique em **"Remover Logo"**
2. Confirme a remoção

### Resultado Esperado:
- ✅ Logo removido da página
- ✅ Arquivo removido de `storage/uploads/cfcs/`
- ✅ Ícones PWA removidos de `public_html/icons/1/`
- ✅ Banco atualizado: `logo_path = NULL`

---

## 📋 Checklist Final

Após todos os testes:

- [ ] Página `/configuracoes/cfc` acessível
- [ ] Edição de Nome/CNPJ funcionando
- [ ] Upload de logo funcionando
- [ ] Ícones PWA gerados corretamente
- [ ] Manifest PWA usando ícones dinâmicos
- [ ] Remoção de logo funcionando
- [ ] Logs de debug aparecendo no console
- [ ] Sem erros no console do navegador

---

## 🐛 Troubleshooting

### Erro: "Token CSRF inválido"
- **Solução:** Recarregue a página (F5) e tente novamente

### Erro: "Arquivo muito grande"
- **Solução:** Use imagem menor que 5MB

### Erro: "Tipo de arquivo inválido"
- **Solução:** Use apenas JPG, PNG ou WEBP

### Upload não salva
- **Verifique:** Permissões do diretório `storage/uploads/cfcs/`
- **Verifique:** Limites do PHP (`upload_max_filesize`, `post_max_size`)
- **Verifique:** Logs em `storage/logs/upload_logo.log`

### Ícones não são gerados
- **Verifique:** Extensão GD habilitada no PHP
- **Verifique:** Permissões do diretório `public_html/icons/`

---

## ✅ Próximo Passo

Após confirmar que tudo funciona localmente:
1. Fazer commit das alterações
2. Fazer push para o repositório
3. Testar em produção
