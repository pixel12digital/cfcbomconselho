# PLANO DE MIGRAÇÃO CFC - DOCUMENTAÇÃO COMPLETA

## 📊 SITUAÇÃO ATUAL IDENTIFICADA

### WordPress Atual (Hospedagem):
- **25 usuários** cadastrados
- **168MB de mídias** (apostilas, manuais, PDFs)
- **7 apostilas específicas** do CFC
- **8 vagas de emprego** ativas
- **11 páginas** (Sobre, Serviços, Contato, etc.)
- **Sistema de login** próprio (Forminator)

### Sistema CFC Atual (Local):
- **Banco funcional** com alunos, instrutores, veículos
- **Sistema de login** operacional
- **Interface administrativa** completa
- **Banco remoto** conectado

## 🎯 ESTRATÉGIA DE MIGRAÇÃO

### FASE 1: MIGRAÇÃO DE DADOS (ATUAL)
- [ ] Exportar dados dos 25 usuários WordPress
- [ ] Migrar usuários para banco CFC atual
- [ ] Copiar todo acervo de materiais (168MB)
- [ ] Organizar materiais em pasta `/materiais/`

### FASE 2: RECRIAÇÃO DE CONTEÚDO
- [ ] Criar páginas estáticas (Sobre, Serviços, Contato)
- [ ] Recriar página de vagas (8 vagas)
- [ ] Implementar sistema de materiais integrado
- [ ] Criar página inicial profissional

### FASE 3: INTEGRAÇÃO E TESTES
- [ ] Unificar sistema de login
- [ ] Testar acesso aos materiais
- [ ] Verificar funcionalidades existentes
- [ ] Validar migração completa

### FASE 4: LIMPEZA FINAL
- [ ] Remover WordPress completamente
- [ ] Limpar arquivos desnecessários
- [ ] Teste final completo
- [ ] Documentação final

## 📚 MATERIAIS IDENTIFICADOS

### Apostilas Principais:
1. Manual de Obtenção da CNH
2. Guia do Motociclista (Cartilha Motoboy)
3. Manual do Transporte Escolar
4. Apostila MOPP (Substâncias Perigosas)
5. Reciclagem e Meio Ambiente
6. Urgência e Emergência
7. Manual Instrutor/Candidato

### Outros Materiais:
- Links úteis (Pilotar.app)
- Formulários de contato
- Imagens e logos
- Documentos diversos

## 🔒 GARANTIAS DE SEGURANÇA
- ✅ Backup completo antes de qualquer alteração
- ✅ Preservação de dados existentes
- ✅ Teste de funcionalidades
- ✅ Migração gradual e controlada
- ✅ Possibilidade de rollback

## 📝 PRÓXIMOS PASSOS
1. Exportar usuários WordPress
2. Copiar materiais para sistema CFC
3. Migrar usuários para banco CFC
4. Organizar estrutura de arquivos

## 🚀 COMANDOS PARA EXECUTAR NA HOSPEDAGEM

### Comando 1 - Exportar usuários WordPress:
```bash
mysql -u u342734079_6JREb -p'UP2mwuZvbO' -h 127.0.0.1 u342734079_9Fzz2 -e "SELECT user_login, user_email, user_registered, display_name FROM wp_users ORDER BY user_registered;" > usuarios_wordpress_export.txt
```

### Comando 2 - Listar todos os materiais:
```bash
find wp-content/uploads/ -type f \( -name "*.pdf" -o -name "*.doc" -o -name "*.docx" -o -name "*.jpg" -o -name "*.jpeg" -o -name "*.png" \) | sort > materiais_completos.txt
```

### Comando 3 - Criar backup dos materiais:
```bash
tar -czf backup_materiais_wordpress.tar.gz wp-content/uploads/
```

### Comando 4 - Verificar tamanho dos materiais:
```bash
du -sh wp-content/uploads/
```

## 📁 ESTRUTURA LOCAL CRIADA
- `/materiais/` - Pasta para materiais migrados
- `/docs/` - Documentação da migração
- `/backup/` - Backups de segurança
