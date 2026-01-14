# ⚠️ ERRO: Tabelas não encontradas - Solução Rápida

## O erro que você está vendo:
```
Table 'cfc_db.students' doesn't exist
```

Isso significa que as tabelas da Fase 1 ainda não foram criadas no banco de dados.

## ✅ Solução (Escolha uma opção):

### Opção 1: Via phpMyAdmin (MAIS FÁCIL)

1. Abra o phpMyAdmin: http://localhost/phpmyadmin
2. Selecione o banco de dados `cfc_db` no menu lateral
3. Clique na aba **"SQL"** no topo
4. Abra o arquivo `EXECUTAR_FASE1.sql` (na raiz do projeto)
5. **Copie TODO o conteúdo** do arquivo
6. Cole no campo SQL do phpMyAdmin
7. Clique em **"Executar"** (ou pressione Ctrl+Enter)
8. Aguarde a mensagem de sucesso
9. Recarregue a página do sistema

### Opção 2: Via MySQL Command Line

```bash
cd c:\xampp\htdocs\cfc-v.1
mysql -u root -p cfc_db < EXECUTAR_FASE1.sql
```

(Digite a senha quando solicitado - geralmente vazio no XAMPP)

### Opção 3: Via arquivo SQL direto

1. Abra o arquivo `EXECUTAR_FASE1.sql` na raiz do projeto
2. Copie todo o conteúdo
3. Execute no seu cliente MySQL favorito

## ✅ Verificação

Após executar, verifique se as seguintes tabelas foram criadas:

- ✅ `services`
- ✅ `students`
- ✅ `enrollments`
- ✅ `steps`
- ✅ `student_steps`

Você pode verificar no phpMyAdmin:
- Selecione `cfc_db`
- Clique em "Estrutura"
- Deve aparecer as 5 tabelas acima

## 🎯 Depois de executar

1. Recarregue a página do sistema (F5)
2. Tente acessar `/alunos` novamente
3. O erro deve desaparecer

## ❓ Ainda com erro?

Se ainda aparecer erro após executar o SQL:

1. Verifique se o banco `cfc_db` existe
2. Verifique se a Fase 0 foi executada (tabelas `cfcs`, `usuarios`, etc devem existir)
3. Verifique se há erros no console do phpMyAdmin ao executar o SQL
4. Me envie a mensagem de erro completa
