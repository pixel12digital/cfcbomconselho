# CADASTRO DE SALAS IMPLEMENTADO

## ONDE CADASTRAR SALAS

### **Localização no Menu:**
- **Menu Principal** → **Configurações** → **Salas de Aula**
- **Menu Flyout** → **Configurações** → **Salas de Aula**
- **Link Direto:** `admin/?page=configuracoes-salas`

## FUNCIONALIDADES IMPLEMENTADAS

### ✅ **Cadastro de Salas**
- Nome da sala (obrigatório)
- Capacidade de alunos (padrão: 30)
- Equipamentos disponíveis:
  - Projetor
  - Quadro
  - Ar Condicionado
  - Computadores
  - Internet
  - Sistema de Som
- Status (Ativa/Inativa)

### ✅ **Gestão Completa**
- **Criar** nova sala
- **Editar** sala existente
- **Excluir** sala (apenas se não estiver em uso)
- **Visualizar** equipamentos e capacidade
- **Controle** de turmas ativas por sala

### ✅ **Validações Inteligentes**
- Nome único por CFC
- Capacidade mínima de 1 aluno
- Impede exclusão de salas em uso
- Verificação de conflitos

## COMO USAR

### **1. Acessar o Cadastro**
1. No menu lateral, clique em **"Configurações"**
2. Selecione **"Salas de Aula"**
3. Ou acesse diretamente: `admin/?page=configuracoes-salas`

### **2. Criar Nova Sala**
1. Clique em **"Nova Sala"**
2. Preencha os dados:
   - **Nome:** Ex: "Sala 1", "Laboratório", "Auditório"
   - **Capacidade:** Número de alunos (padrão: 30)
   - **Equipamentos:** Marque os disponíveis
   - **Status:** Ativa (para uso) ou Inativa
3. Clique em **"Salvar Sala"**

### **3. Editar Sala Existente**
1. Na lista de salas, clique em **"Editar"**
2. Modifique os dados necessários
3. Clique em **"Salvar Sala"**

### **4. Excluir Sala**
1. Na lista de salas, clique em **"Excluir"**
2. Confirme a exclusão
3. **Nota:** Só é possível excluir salas que não estão sendo usadas

## INTEGRAÇÃO COM SISTEMA DE TURMAS

### **No Sistema de Turmas Teóricas:**
- As salas cadastradas aparecem automaticamente no dropdown de seleção
- O sistema verifica conflitos de horários por sala
- Capacidade é respeitada para matrícula de alunos
- Equipamentos são exibidos na visualização da turma

### **Validações Automáticas:**
- ✅ Sala disponível no horário selecionado
- ✅ Capacidade suficiente para número de alunos
- ✅ Equipamentos adequados para o tipo de aula

## ESTRUTURA DO BANCO DE DADOS

### **Tabela `salas`:**
```sql
CREATE TABLE salas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    capacidade INT NOT NULL DEFAULT 30,
    equipamentos JSON DEFAULT NULL,
    ativa BOOLEAN DEFAULT TRUE,
    cfc_id INT NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_cfc_ativa (cfc_id, ativa),
    FOREIGN KEY (cfc_id) REFERENCES cfcs(id) ON DELETE CASCADE
);
```

## SALAS PADRÃO INCLUÍDAS

O sistema já vem com 3 salas pré-cadastradas:
- **Sala 1** - 30 alunos (Projetor, Ar Condicionado, Quadro)
- **Sala 2** - 25 alunos (Projetor, Quadro)
- **Sala 3** - 35 alunos (Projetor, Ar Condicionado, Quadro, 10 Computadores)

## PRÓXIMOS PASSOS

1. **Execute a migração** se ainda não fez: `admin/executar-migracao-turmas-teoricas.php`
2. **Acesse o cadastro de salas** e configure conforme necessário
3. **Crie suas turmas teóricas** usando as salas cadastradas
4. **Teste o agendamento** para verificar conflitos de sala

---

**O sistema de cadastro de salas está completamente implementado e integrado!** 🚀🏫
