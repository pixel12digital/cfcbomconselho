# Sistema de Configuração Flexível de Categorias - CFC

## 🎯 **Visão Geral**

Este sistema implementa uma solução completa e flexível para gerenciar as quantidades de aulas por categoria de habilitação, permitindo que o usuário configure facilmente os limites através da interface administrativa.

## 📋 **Como Funciona a Lógica**

### **1. Configuração de Categorias**
- **Localização**: Menu Configurações → Categorias de Habilitação
- **Funcionalidade**: Interface para definir quantidades de aulas por categoria
- **Exemplo**: Categoria AB = 45h teóricas + 40h práticas (20h moto + 20h carro)

### **2. Processo de Matrícula**
```
1. Usuário cadastra aluno com categoria "AB"
2. Sistema busca configuração da categoria AB
3. Sistema cria automaticamente os "slots" de aulas:
   - 45 slots para aulas teóricas
   - 20 slots para aulas práticas de moto
   - 20 slots para aulas práticas de carro
4. Aluno fica disponível para agendamento
```

### **3. Controle de Agendamento**
```
1. Usuário tenta agendar aula
2. Sistema verifica:
   - Quantas aulas já foram agendadas/concluídas
   - Qual o limite configurado para a categoria
   - Se ainda há espaço disponível
3. Se dentro do limite: permite agendamento
4. Se fora do limite: bloqueia com mensagem explicativa
```

## 🏗️ **Arquitetura do Sistema**

### **Tabelas Principais:**

#### **1. `configuracoes_categorias`**
```sql
- categoria: A, B, AB, C, D, E, etc.
- nome: "Motocicletas", "Automóveis", etc.
- tipo: primeira_habilitacao, adicao, mudanca_categoria
- horas_teoricas: 45, 20, 0, etc.
- horas_praticas_total: 20, 40, etc.
- horas_praticas_moto: 20, 0, etc.
- horas_praticas_carro: 0, 20, etc.
- horas_praticas_carga: 0, 20, etc.
- horas_praticas_passageiros: 0, 20, etc.
- horas_praticas_combinacao: 0, 20, etc.
```

#### **2. `aulas_slots`**
```sql
- aluno_id: Referência ao aluno
- tipo_aula: teorica, pratica
- tipo_veiculo: moto, carro, carga, passageiros, combinacao
- status: pendente, agendada, concluida, cancelada
- ordem: Sequência das aulas
- aula_id: Referência à aula real quando agendada
```

#### **3. `alunos` (modificada)**
```sql
- configuracao_categoria_id: Referência à configuração ativa
```

#### **4. `aulas` (modificada)**
```sql
- slot_id: Referência ao slot original
- tipo_veiculo: Tipo de veículo da aula
```

### **Classes Principais:**

#### **1. `ConfiguracoesCategorias`**
- Gerencia configurações de categorias
- Valida dados antes de salvar
- Fornece métodos para buscar configurações

#### **2. `SistemaMatricula`**
- Processa matrícula de novos alunos
- Cria slots de aulas automaticamente
- Valida dados do aluno

#### **3. `ControleLimiteAulas`**
- Verifica limites antes do agendamento
- Impede agendamentos além do permitido
- Calcula progresso do aluno

## 🔄 **Fluxo Completo do Sistema**

### **Cenário: Aluno com Categoria AB**

#### **1. Configuração Inicial**
```
Admin acessa: Configurações → Categorias
Configura categoria AB:
- Nome: "Motocicletas + Automóveis"
- Tipo: "Primeira Habilitação"
- Teóricas: 45h
- Práticas Total: 40h
- Práticas Moto: 20h
- Práticas Carro: 20h
```

#### **2. Matrícula do Aluno**
```
Sistema recebe:
- Nome: "João Silva"
- CPF: "123.456.789-00"
- Categoria: "AB"

Sistema automaticamente:
- Busca configuração da categoria AB
- Cria 45 slots para aulas teóricas
- Cria 20 slots para aulas práticas de moto
- Cria 20 slots para aulas práticas de carro
- Total: 85 slots criados
```

#### **3. Agendamento de Aulas**
```
Usuário tenta agendar:
- Aula teórica #1 → ✅ Permitido (1/45)
- Aula teórica #2 → ✅ Permitido (2/45)
- ...
- Aula teórica #45 → ✅ Permitido (45/45)
- Aula teórica #46 → ❌ Bloqueado ("Limite de aulas teóricas atingido")

Usuário tenta agendar:
- Aula prática moto #1 → ✅ Permitido (1/20)
- Aula prática moto #2 → ✅ Permitido (2/20)
- ...
- Aula prática moto #20 → ✅ Permitido (20/20)
- Aula prática moto #21 → ❌ Bloqueado ("Limite de aulas práticas de moto atingido")

Usuário tenta agendar:
- Aula prática carro #1 → ✅ Permitido (1/20)
- ...
```

#### **4. Histórico do Aluno**
```
Sistema mostra:
- Progresso Teórico: 45/45 (100%)
- Progresso Moto: 20/20 (100%)
- Progresso Carro: 15/20 (75%)
- Progresso Geral: 80/85 (94%)
```

## 🎨 **Interface do Usuário**

### **1. Página de Configurações**
- **Cards visuais** para cada categoria
- **Formulário intuitivo** para editar configurações
- **Validação em tempo real** dos valores
- **Botão "Restaurar Padrão"** para cada categoria

### **2. Histórico do Aluno**
- **Barras de progresso** separadas por tipo
- **Cards de estatísticas** detalhados
- **Informações contextuais** sobre a categoria
- **Controle visual** do progresso

### **3. Sistema de Agendamento**
- **Verificação automática** de limites
- **Mensagens explicativas** quando bloqueado
- **Sugestões** de próximas aulas disponíveis
- **Controle de veículo** por tipo de aula

## ✅ **Vantagens da Solução**

### **1. Flexibilidade Total**
- ✅ Configuração via interface (sem código)
- ✅ Adaptação a mudanças de normas
- ✅ Personalização por CFC
- ✅ Fácil manutenção

### **2. Conformidade Legal**
- ✅ Valores corretos por categoria
- ✅ Separação teórica/prática
- ✅ Controle de limites rigoroso
- ✅ Auditoria completa

### **3. Experiência do Usuário**
- ✅ Interface intuitiva
- ✅ Mensagens claras
- ✅ Progresso visual
- ✅ Controle total

### **4. Gestão Eficiente**
- ✅ Matrícula automática
- ✅ Controle de limites
- ✅ Relatórios precisos
- ✅ Histórico completo

## 🚀 **Implementação**

### **Passos para Implementar:**

1. **Executar Scripts SQL**
   ```bash
   mysql -u usuario -p database < configuracoes_categorias.sql
   mysql -u usuario -p database < aulas_slots.sql
   ```

2. **Incluir Classes**
   ```php
   require_once 'admin/includes/configuracoes_categorias.php';
   require_once 'admin/includes/sistema_matricula.php';
   require_once 'admin/includes/controle_limite_aulas.php';
   ```

3. **Configurar Categorias**
   - Acessar: Admin → Configurações → Categorias
   - Configurar cada categoria conforme necessário
   - Testar com dados de exemplo

4. **Integrar com Sistema Existente**
   - Modificar formulário de cadastro de alunos
   - Atualizar sistema de agendamento
   - Modificar histórico de alunos

### **Exemplo de Uso:**

```php
// Verificar se pode agendar aula
$verificacao = ControleLimiteAulas::verificarLimiteAgendamento(
    $alunoId, 
    'pratica', 
    'moto'
);

if ($verificacao['pode_agendar']) {
    echo "Pode agendar! Restam {$verificacao['restantes']} aulas";
} else {
    echo "Não pode agendar: {$verificacao['motivo']}";
}
```

## 📊 **Exemplo Prático: Categoria AB**

### **Configuração:**
- **Teóricas**: 45h
- **Práticas Moto**: 20h  
- **Práticas Carro**: 20h
- **Total**: 85h

### **Matrícula:**
- Sistema cria 85 slots automaticamente
- Aluno fica disponível para agendamento

### **Agendamento:**
- ✅ 45 aulas teóricas permitidas
- ✅ 20 aulas práticas de moto permitidas
- ✅ 20 aulas práticas de carro permitidas
- ❌ Qualquer aula além desses limites é bloqueada

### **Histórico:**
- Progresso separado por tipo
- Controle visual do progresso
- Informações completas sobre a categoria

## 🎯 **Conclusão**

Este sistema resolve completamente a questão da flexibilidade na configuração de categorias, oferecendo:

- **Configuração fácil** via interface
- **Controle automático** de limites
- **Conformidade legal** garantida
- **Experiência otimizada** para usuários
- **Gestão eficiente** para administradores

A solução é **escalável**, **manutenível** e **flexível**, permitindo adaptações futuras sem necessidade de alterações no código.
