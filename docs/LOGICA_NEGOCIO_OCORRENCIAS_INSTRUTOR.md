# Lógica de Negócio: Ocorrências de Instrutor

**Data:** 22/11/2025  
**Objetivo:** Explicar a lógica, propósito e utilidade das ocorrências registradas por instrutores no sistema CFC.

---

## 🎯 Propósito Principal

As **ocorrências** são um sistema de **registro e gestão de problemas** que acontecem durante as aulas práticas e teóricas ministradas pelos instrutores. Elas servem como:

1. **Canal de Comunicação Formal** entre instrutor e secretaria/admin
2. **Registro Histórico** de problemas para análise e melhoria contínua
3. **Ferramenta de Gestão** para identificar padrões e tomar ações corretivas
4. **Documentação** para casos que possam gerar questionamentos futuros

---

## 📋 Tipos de Ocorrências e Seus Propósitos

### 1. **Atraso do Aluno** (`atraso_aluno`)

**Quando usar:**
- Aluno chega atrasado para a aula
- Aluno não comparece sem aviso prévio
- Aluno cancela em cima da hora

**Por que é útil:**
- **Gestão de Horários:** Identifica alunos com histórico de atrasos
- **Otimização de Agenda:** Permite realocar aulas quando aluno não comparece
- **Cobrança:** Pode ser usado para justificar cobrança de taxa de cancelamento
- **Análise de Padrões:** Identifica se há problemas sistemáticos (ex: sempre o mesmo aluno)

**Ação esperada da secretaria/admin:**
- Contatar o aluno para entender o motivo
- Aplicar políticas de cancelamento/atraso se aplicável
- Reagendar a aula se necessário
- Registrar observação no histórico do aluno

---

### 2. **Problema com Veículo** (`problema_veiculo`)

**Quando usar:**
- Veículo quebrou durante a aula
- Veículo não está disponível (em manutenção)
- Problemas mecânicos (freio, embreagem, etc.)
- Falta de combustível
- Problemas com documentos do veículo

**Por que é útil:**
- **Manutenção Preventiva:** Identifica veículos com problemas recorrentes
- **Gestão de Frota:** Permite planejar manutenções e substituições
- **Segurança:** Problemas de segurança (freio, direção) precisam ser resolvidos imediatamente
- **Custos:** Ajuda a calcular custos de manutenção por veículo

**Ação esperada da secretaria/admin:**
- Acionar manutenção imediata se for problema de segurança
- Registrar na ficha do veículo
- Substituir veículo para próximas aulas se necessário
- Analisar se há padrão de problemas com aquele veículo

---

### 3. **Infraestrutura** (`infraestrutura`)

**Quando usar:**
- Problemas com a sala de aula (ar condicionado, projetor, etc.)
- Problemas com o pátio de manobras
- Problemas com equipamentos (simuladores, etc.)
- Falta de material didático
- Problemas com acesso (portão, chaves, etc.)

**Por que é útil:**
- **Manutenção de Instalações:** Identifica problemas que precisam de reparo
- **Qualidade do Ensino:** Problemas de infraestrutura afetam a qualidade das aulas
- **Planejamento:** Permite planejar melhorias e manutenções preventivas
- **Custos:** Ajuda a calcular custos de manutenção de infraestrutura

**Ação esperada da secretaria/admin:**
- Acionar manutenção ou fornecedor
- Registrar na ficha do CFC
- Comunicar outros instrutores se afetar múltiplas aulas
- Planejar melhorias se for problema recorrente

---

### 4. **Comportamento do Aluno** (`comportamento_aluno`)

**Quando usar:**
- Aluno desrespeitoso ou agressivo
- Aluno não segue instruções de segurança
- Aluno usa celular durante a aula
- Aluno apresenta comportamento inadequado
- Aluno não demonstra interesse/aprendizado

**Por que é útil:**
- **Segurança:** Comportamentos perigosos precisam ser documentados
- **Qualidade do Ensino:** Identifica alunos que precisam de atenção especial
- **Decisões Administrativas:** Pode levar a suspensão ou desligamento do aluno
- **Proteção Legal:** Documenta situações que podem gerar questionamentos futuros

**Ação esperada da secretaria/admin:**
- Conversar com o aluno sobre o comportamento
- Aplicar medidas disciplinares se necessário
- Registrar no histórico do aluno
- Em casos graves, considerar suspensão ou desligamento
- Comunicar aos responsáveis (se menor de idade)

---

### 5. **Outro** (`outro`)

**Quando usar:**
- Situações que não se encaixam nos tipos acima
- Problemas específicos do contexto
- Observações gerais que precisam ser registradas

**Por que é útil:**
- **Flexibilidade:** Permite registrar situações não previstas
- **Completude:** Garante que nenhum problema fique sem registro

**Ação esperada da secretaria/admin:**
- Analisar caso a caso
- Classificar melhor se necessário
- Tomar ação apropriada

---

## 🔄 Fluxo de Trabalho (Workflow)

### **Etapa 1: Registro pelo Instrutor**

1. Instrutor identifica um problema durante a aula
2. Acessa `instrutor/ocorrencias.php`
3. Preenche o formulário:
   - **Tipo:** Seleciona o tipo de ocorrência
   - **Data:** Data em que aconteceu
   - **Aula relacionada (opcional):** Vincula a uma aula específica
   - **Descrição:** Detalha o problema
4. Salva a ocorrência
5. Status inicial: **"Aberta"**

### **Etapa 2: Visualização pela Secretaria/Admin**

1. Secretaria/Admin acessa página de ocorrências (⚠️ **NÃO IMPLEMENTADO**)
2. Visualiza lista de ocorrências abertas
3. Filtra por tipo, instrutor, data, status
4. Seleciona uma ocorrência para ver detalhes

### **Etapa 3: Análise e Resolução**

1. Secretaria/Admin analisa a ocorrência
2. Toma ação apropriada (contatar aluno, acionar manutenção, etc.)
3. Preenche campo **"Resolução"** com o que foi feito
4. Altera status para:
   - **"Em Análise"** - Se está investigando
   - **"Resolvida"** - Se foi resolvida
   - **"Arquivada"** - Se não requer mais ação

### **Etapa 4: Histórico e Análise**

1. Ocorrências resolvidas ficam no histórico
2. Admin pode analisar padrões:
   - Qual instrutor registra mais ocorrências?
   - Qual tipo de ocorrência é mais comum?
   - Há problemas recorrentes com algum veículo/aluno?
3. Usa dados para:
   - Melhorias preventivas
   - Treinamento de instrutores
   - Manutenção preventiva de veículos
   - Ações disciplinares com alunos

---

## 💼 Casos de Uso Práticos

### **Caso 1: Aluno com Atrasos Recorrentes**

**Cenário:**
- Instrutor registra 3 ocorrências de "Atraso do Aluno" para o mesmo aluno
- Secretaria visualiza o padrão
- Secretaria contata o aluno e aplica política de cancelamento
- Próximos atrasos podem resultar em suspensão

**Benefício:**
- Documentação formal do problema
- Base para decisões administrativas
- Proteção legal do CFC

---

### **Caso 2: Veículo com Problemas Mecânicos**

**Cenário:**
- Instrutor registra "Problema com Veículo" - freio falhando
- Secretaria aciona manutenção imediata
- Veículo é retirado de circulação até reparo
- Outros instrutores são avisados

**Benefício:**
- Segurança dos alunos e instrutores
- Gestão preventiva da frota
- Redução de custos (reparo antes de acidente)

---

### **Caso 3: Problema de Infraestrutura**

**Cenário:**
- Instrutor registra "Infraestrutura" - ar condicionado quebrado na sala
- Secretaria aciona técnico
- Aula é transferida para outra sala
- Outros instrutores são avisados

**Benefício:**
- Qualidade do ensino mantida
- Planejamento de manutenções
- Comunicação eficiente entre setores

---

### **Caso 4: Comportamento Inadequado do Aluno**

**Cenário:**
- Instrutor registra "Comportamento do Aluno" - aluno agressivo
- Secretaria conversa com o aluno
- Se persistir, aplica suspensão
- Registro fica no histórico do aluno

**Benefício:**
- Segurança do instrutor e outros alunos
- Base para decisões disciplinares
- Proteção legal do CFC

---

## 📊 Métricas e Análises Possíveis

### **Métricas por Instrutor:**
- Quantidade de ocorrências registradas
- Tipos mais comuns
- Taxa de resolução

### **Métricas por Tipo:**
- Qual tipo de ocorrência é mais comum?
- Qual tipo leva mais tempo para resolver?
- Qual tipo tem maior impacto?

### **Métricas por Aluno:**
- Histórico de ocorrências relacionadas
- Padrões de comportamento
- Base para decisões administrativas

### **Métricas por Veículo:**
- Problemas recorrentes
- Custos de manutenção
- Decisão de substituição

### **Métricas Gerais:**
- Total de ocorrências abertas
- Tempo médio de resolução
- Taxa de ocorrências por aula

---

## ⚠️ Limitações Atuais

### **O que NÃO está implementado:**

1. **Visualização pela Secretaria/Admin:**
   - ❌ Não há página para visualizar todas as ocorrências
   - ❌ Não há filtros por tipo, instrutor, data, status
   - ❌ Não há dashboard com métricas

2. **Resolução:**
   - ❌ Não há interface para preencher "Resolução"
   - ❌ Não há interface para alterar status
   - ❌ Não há notificações quando nova ocorrência é registrada

3. **Análises:**
   - ❌ Não há relatórios de ocorrências
   - ❌ Não há gráficos ou dashboards
   - ❌ Não há exportação de dados

---

## ✅ Valor Agregado

### **Para o Instrutor:**
- ✅ Canal formal de comunicação com a secretaria
- ✅ Registro documentado de problemas
- ✅ Histórico de suas ocorrências
- ✅ Facilita o trabalho (não precisa ligar/WhatsApp)

### **Para a Secretaria/Admin:**
- ✅ Visão centralizada de todos os problemas
- ✅ Priorização de ações (ocorrências abertas)
- ✅ Histórico para análise
- ✅ Base para decisões administrativas

### **Para o CFC:**
- ✅ Melhoria contínua (identifica padrões)
- ✅ Redução de custos (manutenção preventiva)
- ✅ Qualidade do ensino (resolução rápida de problemas)
- ✅ Proteção legal (documentação formal)

---

## 🎯 Conclusão

As ocorrências são uma **ferramenta essencial** para:

1. **Comunicação:** Canal formal entre instrutor e secretaria
2. **Gestão:** Identificação de problemas e padrões
3. **Qualidade:** Resolução rápida de problemas
4. **Análise:** Dados para melhorias contínuas
5. **Documentação:** Registro histórico para proteção legal

**Status Atual:** Funcionalidade **50% implementada**
- ✅ Instrutor pode registrar ocorrências
- ❌ Secretaria/Admin não pode visualizar/resolver

**Próximo Passo:** Implementar interface de gerenciamento no painel admin/secretaria.

---

**Arquivo criado em:** 22/11/2025  
**Última atualização:** 22/11/2025

