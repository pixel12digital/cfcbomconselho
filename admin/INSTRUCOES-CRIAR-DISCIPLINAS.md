# 🔧 Instruções: Criar Disciplinas via Script

## 📋 Objetivo

Criar automaticamente as disciplinas padrão do CFC através do modal "Gerenciar Disciplinas" usando scripts automatizados.

## 🎯 Disciplinas que serão criadas:

1. **Legislação de Trânsito** (18h)
   - Código: `legislacao_transito`
   - Descrição: Estudo das leis e normas de trânsito
   - Cor: Vermelho (#dc3545)

2. **Direção Defensiva** (16h)
   - Código: `direcao_defensiva`
   - Descrição: Técnicas de direção segura
   - Cor: Verde (#28a745)

3. **Primeiros Socorros** (4h)
   - Código: `primeiros_socorros`
   - Descrição: Noções básicas de primeiros socorros
   - Cor: Laranja (#fd7e14)

4. **Meio Ambiente e Cidadania** (4h)
   - Código: `meio_ambiente_cidadania`
   - Descrição: Conscientização ambiental e cidadania no trânsito
   - Cor: Verde-água (#20c997)

5. **Mecânica Básica** (3h)
   - Código: `mecanica_basica`
   - Descrição: Noções básicas de mecânica veicular
   - Cor: Roxo (#6f42c1)

## 🚀 Método 1: Script via Console (Recomendado)

### **Passo 1: Acessar a página**
```
http://localhost/cfc-bom-conselho/admin/?page=turmas-teoricas&acao=nova&step=1
```

### **Passo 2: Abrir o console**
- Pressione `F12`
- Vá para a aba "Console"

### **Passo 3: Executar o script**
Copie e cole este código no console:

```javascript
// 🔧 Script para criar disciplinas via console
console.log('🔧 Script de criação de disciplinas iniciado...');

const disciplinasPadrao = [
    {
        codigo: 'legislacao_transito',
        nome: 'Legislação de Trânsito',
        descricao: 'Estudo das leis e normas de trânsito',
        carga_horaria_padrao: 18,
        cor_hex: '#dc3545',
        icone: 'gavel'
    },
    {
        codigo: 'direcao_defensiva',
        nome: 'Direção Defensiva',
        descricao: 'Técnicas de direção segura',
        carga_horaria_padrao: 16,
        cor_hex: '#28a745',
        icone: 'shield-alt'
    },
    {
        codigo: 'primeiros_socorros',
        nome: 'Primeiros Socorros',
        descricao: 'Noções básicas de primeiros socorros',
        carga_horaria_padrao: 4,
        cor_hex: '#fd7e14',
        icone: 'first-aid'
    },
    {
        codigo: 'meio_ambiente_cidadania',
        nome: 'Meio Ambiente e Cidadania',
        descricao: 'Conscientização ambiental e cidadania no trânsito',
        carga_horaria_padrao: 4,
        cor_hex: '#20c997',
        icone: 'leaf'
    },
    {
        codigo: 'mecanica_basica',
        nome: 'Mecânica Básica',
        descricao: 'Noções básicas de mecânica veicular',
        carga_horaria_padrao: 3,
        cor_hex: '#6f42c1',
        icone: 'cog'
    }
];

async function criarTodasDisciplinas() {
    try {
        console.log('🔍 Verificando disciplinas existentes...');
        
        // Verificar disciplinas existentes
        const response = await fetch('/cfc-bom-conselho/admin/api/disciplinas-clean.php?acao=listar');
        const data = await response.json();
        
        if (data.sucesso && data.disciplinas) {
            console.log(`✅ Encontradas ${data.disciplinas.length} disciplinas existentes`);
            const codigosExistentes = data.disciplinas.map(d => d.codigo);
            const disciplinasFaltando = disciplinasPadrao.filter(d => !codigosExistentes.includes(d.codigo));
            
            if (disciplinasFaltando.length === 0) {
                console.log('✅ Todas as disciplinas padrão já existem!');
                return;
            }
            
            console.log(`➕ Criando ${disciplinasFaltando.length} disciplinas faltando...`);
            
            // Criar disciplinas uma por uma
            for (let i = 0; i < disciplinasFaltando.length; i++) {
                const disciplina = disciplinasFaltando[i];
                console.log(`📝 [${i + 1}/${disciplinasFaltando.length}] Criando: ${disciplina.nome}`);
                
                const formData = new FormData();
                formData.append('acao', 'criar');
                formData.append('codigo', disciplina.codigo);
                formData.append('nome', disciplina.nome);
                formData.append('descricao', disciplina.descricao);
                formData.append('carga_horaria_padrao', disciplina.carga_horaria_padrao);
                formData.append('cor_hex', disciplina.cor_hex);
                formData.append('icone', disciplina.icone);
                formData.append('ativa', '1');
                
                const createResponse = await fetch('/cfc-bom-conselho/admin/api/disciplinas-clean.php', {
                    method: 'POST',
                    body: formData
                });
                
                const createData = await createResponse.json();
                
                if (createData.sucesso) {
                    console.log(`✅ ${disciplina.nome} criada com sucesso!`);
                } else {
                    console.error(`❌ Erro ao criar ${disciplina.nome}: ${createData.mensagem}`);
                }
                
                // Delay de 1 segundo entre criações
                if (i < disciplinasFaltando.length - 1) {
                    await new Promise(resolve => setTimeout(resolve, 1000));
                }
            }
            
            console.log('✅ Processo de criação concluído!');
            console.log('🔄 Recarregue a página para ver as novas disciplinas no seletor.');
            
        } else {
            console.error('❌ Erro ao verificar disciplinas existentes');
        }
        
    } catch (error) {
        console.error('❌ Erro no processo:', error);
    }
}

// Executar o script
criarTodasDisciplinas();
```

### **Passo 4: Aguardar conclusão**
- O script criará as disciplinas automaticamente
- Cada disciplina será criada com 1 segundo de intervalo
- Você verá os logs de progresso no console

### **Passo 5: Verificar resultado**
- Recarregue a página (`Ctrl + F5`)
- Clique no seletor de disciplinas
- As novas disciplinas devem aparecer na lista

## 🌐 Método 2: Página de Script

### **Acessar página de script:**
```
http://localhost/cfc-bom-conselho/admin/script-criar-disciplinas.html
```

### **Funcionalidades:**
- ✅ Verificar disciplinas existentes
- ✅ Criar todas as disciplinas automaticamente
- ✅ Abrir modal de disciplinas
- ✅ Interface visual com progresso

## 📋 Método 3: Manual via Modal

### **Passo 1: Abrir modal**
1. Acesse: `http://localhost/cfc-bom-conselho/admin/?page=turmas-teoricas&acao=nova&step=1`
2. Clique no botão de engrenagem ao lado de "Disciplinas do Curso"
3. Clique em "Nova Disciplina"

### **Passo 2: Criar cada disciplina**
Para cada disciplina, preencha:

**Legislação de Trânsito:**
- Código: `legislacao_transito`
- Nome: `Legislação de Trânsito`
- Descrição: `Estudo das leis e normas de trânsito`
- Carga Horária: `18`
- Cor: `#dc3545`

**Direção Defensiva:**
- Código: `direcao_defensiva`
- Nome: `Direção Defensiva`
- Descrição: `Técnicas de direção segura`
- Carga Horária: `16`
- Cor: `#28a745`

**Primeiros Socorros:**
- Código: `primeiros_socorros`
- Nome: `Primeiros Socorros`
- Descrição: `Noções básicas de primeiros socorros`
- Carga Horária: `4`
- Cor: `#fd7e14`

**Meio Ambiente e Cidadania:**
- Código: `meio_ambiente_cidadania`
- Nome: `Meio Ambiente e Cidadania`
- Descrição: `Conscientização ambiental e cidadania no trânsito`
- Carga Horária: `4`
- Cor: `#20c997`

**Mecânica Básica:**
- Código: `mecanica_basica`
- Nome: `Mecânica Básica`
- Descrição: `Noções básicas de mecânica veicular`
- Carga Horária: `3`
- Cor: `#6f42c1`

## ✅ Verificação Final

### **Teste 1: Verificar no seletor**
1. No formulário de turmas, clique no seletor de disciplinas
2. Deve aparecer todas as 5 disciplinas criadas

### **Teste 2: Verificar no modal**
1. Abra o modal "Gerenciar Disciplinas"
2. Deve mostrar todas as disciplinas na lista
3. Contador deve mostrar "Total: 5"

### **Teste 3: Verificar no banco**
Execute no console:
```javascript
fetch('/cfc-bom-conselho/admin/api/disciplinas-clean.php?acao=listar')
    .then(r => r.json())
    .then(data => {
        console.log('Disciplinas no banco:', data.disciplinas.length);
        data.disciplinas.forEach(d => console.log(`- ${d.nome} (${d.codigo})`));
    });
```

## 🚨 Solução de Problemas

### **Erro: "Código já existe"**
- A disciplina já foi criada anteriormente
- Ignore o erro e continue com as próximas

### **Erro: "Função não encontrada"**
- Recarregue a página (`Ctrl + F5`)
- Execute o script novamente

### **Erro: "API não responde"**
- Verifique se o servidor está rodando
- Verifique se a API está acessível

## 📊 Resultado Esperado

Após executar o script com sucesso:

- ✅ **5 disciplinas criadas** no banco de dados
- ✅ **Disciplinas aparecem** no seletor do formulário
- ✅ **Modal mostra** todas as disciplinas
- ✅ **Total de 45 horas** de carga horária (18+16+4+4+3)

---

**Última atualização:** 16/10/2025 20:00
**Versão:** 1.0
**Status:** ✅ Pronto para uso
