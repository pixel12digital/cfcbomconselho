// 🔧 Script para criar disciplinas via console
// Execute este código no console da página: http://localhost/cfc-bom-conselho/admin/?page=turmas-teoricas&acao=nova&step=1

console.log('🔧 Script de criação de disciplinas iniciado...');

// Lista de disciplinas padrão do CFC
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

// Função para criar uma disciplina
function criarDisciplina(disciplina) {
    return new Promise((resolve, reject) => {
        console.log(`📝 Criando: ${disciplina.nome}...`);
        
        const formData = new FormData();
        formData.append('acao', 'criar');
        formData.append('codigo', disciplina.codigo);
        formData.append('nome', disciplina.nome);
        formData.append('descricao', disciplina.descricao);
        formData.append('carga_horaria_padrao', disciplina.carga_horaria_padrao);
        formData.append('cor_hex', disciplina.cor_hex);
        formData.append('icone', disciplina.icone);
        formData.append('ativa', '1');
        
        fetch('/cfc-bom-conselho/admin/api/disciplinas-clean.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error('JSON inválido: ' + e.message);
                }
            });
        })
        .then(data => {
            if (data.sucesso) {
                console.log(`✅ ${disciplina.nome} criada com sucesso!`);
                resolve(data);
            } else {
                console.error(`❌ Erro ao criar ${disciplina.nome}: ${data.mensagem || 'Erro desconhecido'}`);
                reject(data);
            }
        })
        .catch(error => {
            console.error(`❌ Erro na requisição para ${disciplina.nome}: ${error.message}`);
            reject(error);
        });
    });
}

// Função para verificar disciplinas existentes
function verificarDisciplinas() {
    return new Promise((resolve, reject) => {
        console.log('🔍 Verificando disciplinas existentes...');
        
        fetch('/cfc-bom-conselho/admin/api/disciplinas-clean.php?acao=listar')
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error('JSON inválido: ' + e.message);
                    }
                });
            })
            .then(data => {
                if (data.sucesso && data.disciplinas) {
                    console.log(`✅ Encontradas ${data.disciplinas.length} disciplinas existentes:`);
                    data.disciplinas.forEach(d => {
                        console.log(`  - ${d.nome} (${d.codigo}) - ${d.carga_horaria_padrao}h`);
                    });
                    resolve(data.disciplinas);
                } else {
                    console.error('❌ Erro ao carregar disciplinas: ' + (data.mensagem || 'Erro desconhecido'));
                    reject(data);
                }
            })
            .catch(error => {
                console.error('❌ Erro na requisição: ' + error.message);
                reject(error);
            });
    });
}

// Função principal para criar todas as disciplinas
async function criarTodasDisciplinas() {
    try {
        // Verificar disciplinas existentes
        const disciplinasExistentes = await verificarDisciplinas();
        const codigosExistentes = disciplinasExistentes.map(d => d.codigo);
        
        // Filtrar disciplinas que ainda não existem
        const disciplinasFaltando = disciplinasPadrao.filter(d => !codigosExistentes.includes(d.codigo));
        
        if (disciplinasFaltando.length === 0) {
            console.log('✅ Todas as disciplinas padrão já existem!');
            return;
        }
        
        console.log(`➕ Criando ${disciplinasFaltando.length} disciplinas faltando...`);
        
        // Criar disciplinas uma por uma com delay
        for (let i = 0; i < disciplinasFaltando.length; i++) {
            const disciplina = disciplinasFaltando[i];
            console.log(`📝 [${i + 1}/${disciplinasFaltando.length}] Processando: ${disciplina.nome}`);
            
            try {
                await criarDisciplina(disciplina);
                // Delay de 1 segundo entre criações
                if (i < disciplinasFaltando.length - 1) {
                    await new Promise(resolve => setTimeout(resolve, 1000));
                }
            } catch (error) {
                console.error(`❌ Falha ao criar ${disciplina.nome}:`, error);
            }
        }
        
        console.log('✅ Processo de criação concluído!');
        console.log('🔄 Recarregue a página para ver as novas disciplinas no seletor.');
        
    } catch (error) {
        console.error('❌ Erro no processo de criação:', error);
    }
}

// Função para abrir o modal de disciplinas
function abrirModalDisciplinas() {
    console.log('🔧 Tentando abrir modal de disciplinas...');
    
    if (typeof abrirModalDisciplinasInterno === 'function') {
        abrirModalDisciplinasInterno();
        console.log('✅ Modal de disciplinas aberto!');
    } else {
        console.error('❌ Função abrirModalDisciplinasInterno não encontrada!');
    }
}

// Expor funções globalmente
window.criarTodasDisciplinas = criarTodasDisciplinas;
window.verificarDisciplinas = verificarDisciplinas;
window.abrirModalDisciplinas = abrirModalDisciplinas;

console.log('✅ Script carregado! Use os comandos:');
console.log('  - criarTodasDisciplinas() - Criar todas as disciplinas faltando');
console.log('  - verificarDisciplinas() - Verificar disciplinas existentes');
console.log('  - abrirModalDisciplinas() - Abrir modal de disciplinas');
