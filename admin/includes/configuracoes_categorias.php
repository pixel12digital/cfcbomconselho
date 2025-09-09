<?php
/**
 * Classe para gerenciar configurações de categorias de habilitação
 * 
 * Esta classe permite gerenciar as configurações de quantidades de aulas
 * para cada categoria de CNH de forma flexível.
 */

require_once __DIR__ . '/../../includes/database.php';

class ConfiguracoesCategorias {
    private $db;
    private static $instance = null;

    private function __construct() {
        $this->db = Database::getInstance();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new ConfiguracoesCategorias();
        }
        return self::$instance;
    }

    /**
     * Buscar todas as configurações
     */
    public function getAllConfiguracoes() {
        return $this->db->fetchAll("SELECT * FROM configuracoes_categorias ORDER BY categoria ASC");
    }

    /**
     * Buscar configuração por categoria
     */
    public function getConfiguracaoByCategoria($categoria) {
        return $this->db->fetch("SELECT * FROM configuracoes_categorias WHERE categoria = ?", [$categoria]);
    }

    /**
     * Decompor categoria combinada em categorias individuais
     * Ex: AB -> [A, B], AC -> [A, C], etc.
     */
    public function decomporCategoriaCombinada($categoriaCombinada) {
        $categoriasIndividuais = [];
        
        // Mapear categorias combinadas para suas componentes
        $mapeamento = [
            'AB' => ['A', 'B'],
            'AC' => ['A', 'C'],
            'AD' => ['A', 'D'],
            'AE' => ['A', 'E'],
            'BC' => ['B', 'C'],
            'BD' => ['B', 'D'],
            'BE' => ['B', 'E'],
            'CD' => ['C', 'D'],
            'CE' => ['C', 'E'],
            'DE' => ['D', 'E'],
            'ABC' => ['A', 'B', 'C'],
            'ABD' => ['A', 'B', 'D'],
            'ABE' => ['A', 'B', 'E'],
            'ACD' => ['A', 'C', 'D'],
            'ACE' => ['A', 'C', 'E'],
            'ADE' => ['A', 'D', 'E'],
            'BCD' => ['B', 'C', 'D'],
            'BCE' => ['B', 'C', 'E'],
            'BDE' => ['B', 'D', 'E'],
            'CDE' => ['C', 'D', 'E'],
            'ABCD' => ['A', 'B', 'C', 'D'],
            'ABCE' => ['A', 'B', 'C', 'E'],
            'ABDE' => ['A', 'B', 'D', 'E'],
            'ACDE' => ['A', 'C', 'D', 'E'],
            'BCDE' => ['B', 'C', 'D', 'E'],
            'ABCDE' => ['A', 'B', 'C', 'D', 'E']
        ];
        
        if (isset($mapeamento[$categoriaCombinada])) {
            return $mapeamento[$categoriaCombinada];
        }
        
        // Se não for uma categoria combinada conhecida, retorna a própria categoria
        return [$categoriaCombinada];
    }

    /**
     * Buscar configurações para categorias individuais de uma categoria combinada
     */
    public function getConfiguracoesParaCategoriaCombinada($categoriaCombinada) {
        $categoriasIndividuais = $this->decomporCategoriaCombinada($categoriaCombinada);
        $configuracoes = [];
        
        foreach ($categoriasIndividuais as $categoria) {
            $config = $this->getConfiguracaoByCategoria($categoria);
            if ($config) {
                $configuracoes[$categoria] = $config;
            }
        }
        
        return $configuracoes;
    }

    /**
     * Buscar configuração por ID
     */
    public function getConfiguracaoById($id) {
        return $this->db->fetch("SELECT * FROM configuracoes_categorias WHERE id = ?", [$id]);
    }

    /**
     * Salvar configuração (criar ou atualizar)
     */
    public function saveConfiguracao($data) {
        $categoria = $data['categoria'] ?? '';
        
        if (empty($categoria)) {
            throw new Exception('Categoria é obrigatória');
        }

        // Verificar se já existe
        $existente = $this->getConfiguracaoByCategoria($categoria);
        
        // Calcular total de aulas teóricas
        $totalAulasTeoricas = (int)($data['legislacao_transito_aulas'] ?? 0) +
                              (int)($data['primeiros_socorros_aulas'] ?? 0) +
                              (int)($data['meio_ambiente_cidadania_aulas'] ?? 0) +
                              (int)($data['direcao_defensiva_aulas'] ?? 0) +
                              (int)($data['mecanica_basica_aulas'] ?? 0);
        
        $configData = [
            'categoria' => $categoria,
            'nome' => $data['nome'] ?? '',
            'tipo' => $data['tipo'] ?? 'primeira_habilitacao',
            'horas_teoricas' => $totalAulasTeoricas, // Total de aulas, não horas
            'horas_praticas_total' => (int)($data['horas_praticas_total'] ?? 0),
            'horas_praticas_moto' => (int)($data['horas_praticas_moto'] ?? 0),
            'horas_praticas_carro' => (int)($data['horas_praticas_carro'] ?? 0),
            'horas_praticas_carga' => (int)($data['horas_praticas_carga'] ?? 0),
            'horas_praticas_passageiros' => (int)($data['horas_praticas_passageiros'] ?? 0),
            'horas_praticas_combinacao' => (int)($data['horas_praticas_combinacao'] ?? 0),
            'legislacao_transito_aulas' => (int)($data['legislacao_transito_aulas'] ?? 0),
            'primeiros_socorros_aulas' => (int)($data['primeiros_socorros_aulas'] ?? 0),
            'meio_ambiente_cidadania_aulas' => (int)($data['meio_ambiente_cidadania_aulas'] ?? 0),
            'direcao_defensiva_aulas' => (int)($data['direcao_defensiva_aulas'] ?? 0),
            'mecanica_basica_aulas' => (int)($data['mecanica_basica_aulas'] ?? 0),
            'observacoes' => $data['observacoes'] ?? '',
            'ativo' => isset($data['ativo']) ? (bool)$data['ativo'] : true
        ];

        if ($existente) {
            // Atualizar
            $this->db->update('configuracoes_categorias', $configData, 'categoria = ?', [$categoria]);
            return $existente['id'];
        } else {
            // Criar novo
            return $this->db->insert('configuracoes_categorias', $configData);
        }
    }

    /**
     * Restaurar configuração para valores padrão
     */
    public function restoreDefault($categoria) {
        $defaults = $this->getDefaultConfigurations();
        
        if (!isset($defaults[$categoria])) {
            throw new Exception("Configuração padrão para categoria '$categoria' não encontrada");
        }

        $defaultData = $defaults[$categoria];
        $defaultData['categoria'] = $categoria;
        
        return $this->saveConfiguracao($defaultData);
    }

    /**
     * Obter configurações padrão
     */
    public function getDefaultConfigurations() {
        return [
            'A' => [
                'nome' => 'Motocicletas',
                'tipo' => 'primeira_habilitacao',
                'horas_teoricas' => 45,
                'horas_praticas_total' => 20,
                'horas_praticas_moto' => 20,
                'horas_praticas_carro' => 0,
                'horas_praticas_carga' => 0,
                'horas_praticas_passageiros' => 0,
                'horas_praticas_combinacao' => 0,
                'legislacao_transito_aulas' => 12,
                'primeiros_socorros_aulas' => 8,
                'meio_ambiente_cidadania_aulas' => 8,
                'direcao_defensiva_aulas' => 12,
                'mecanica_basica_aulas' => 14,
                'observacoes' => 'Configuração padrão - Motocicletas'
            ],
            'B' => [
                'nome' => 'Automóveis',
                'tipo' => 'primeira_habilitacao',
                'horas_teoricas' => 45,
                'horas_praticas_total' => 20,
                'horas_praticas_moto' => 0,
                'horas_praticas_carro' => 20,
                'horas_praticas_carga' => 0,
                'horas_praticas_passageiros' => 0,
                'horas_praticas_combinacao' => 0,
                'legislacao_transito_aulas' => 12,
                'primeiros_socorros_aulas' => 8,
                'meio_ambiente_cidadania_aulas' => 8,
                'direcao_defensiva_aulas' => 12,
                'mecanica_basica_aulas' => 14,
                'observacoes' => 'Configuração padrão - Automóveis'
            ],
            'AB' => [
                'nome' => 'Motocicletas + Automóveis',
                'tipo' => 'primeira_habilitacao',
                'horas_teoricas' => 45,
                'horas_praticas_total' => 40,
                'horas_praticas_moto' => 20,
                'horas_praticas_carro' => 20,
                'horas_praticas_carga' => 0,
                'horas_praticas_passageiros' => 0,
                'horas_praticas_combinacao' => 0,
                'legislacao_transito_aulas' => 12,
                'primeiros_socorros_aulas' => 8,
                'meio_ambiente_cidadania_aulas' => 8,
                'direcao_defensiva_aulas' => 12,
                'mecanica_basica_aulas' => 14,
                'observacoes' => 'Configuração padrão - Motocicletas + Automóveis'
            ],
            'ACC' => [
                'nome' => 'Automóveis + Motocicletas + Ciclomotores',
                'tipo' => 'primeira_habilitacao',
                'horas_teoricas' => 45,
                'horas_praticas_total' => 40,
                'horas_praticas_moto' => 20,
                'horas_praticas_carro' => 20,
                'horas_praticas_carga' => 0,
                'horas_praticas_passageiros' => 0,
                'horas_praticas_combinacao' => 0,
                'legislacao_transito_aulas' => 12,
                'primeiros_socorros_aulas' => 8,
                'meio_ambiente_cidadania_aulas' => 8,
                'direcao_defensiva_aulas' => 12,
                'mecanica_basica_aulas' => 14,
                'observacoes' => 'Configuração padrão - ACC'
            ],
            'C' => [
                'nome' => 'Veículos de Carga',
                'tipo' => 'adicao',
                'horas_teoricas' => 15,
                'horas_praticas_total' => 15,
                'horas_praticas_moto' => 0,
                'horas_praticas_carro' => 0,
                'horas_praticas_carga' => 15,
                'horas_praticas_passageiros' => 0,
                'horas_praticas_combinacao' => 0,
                'observacoes' => 'Configuração padrão - Veículos de Carga'
            ],
            'D' => [
                'nome' => 'Veículos de Passageiros',
                'tipo' => 'adicao',
                'horas_teoricas' => 15,
                'horas_praticas_total' => 15,
                'horas_praticas_moto' => 0,
                'horas_praticas_carro' => 0,
                'horas_praticas_carga' => 0,
                'horas_praticas_passageiros' => 15,
                'horas_praticas_combinacao' => 0,
                'observacoes' => 'Configuração padrão - Veículos de Passageiros'
            ],
            'E' => [
                'nome' => 'Combinação de Veículos',
                'tipo' => 'adicao',
                'horas_teoricas' => 15,
                'horas_praticas_total' => 15,
                'horas_praticas_moto' => 0,
                'horas_praticas_carro' => 0,
                'horas_praticas_carga' => 0,
                'horas_praticas_passageiros' => 0,
                'horas_praticas_combinacao' => 15,
                'observacoes' => 'Configuração padrão - Combinação de Veículos'
            ],
            'AC' => [
                'nome' => 'Motocicletas + Veículos de Carga',
                'tipo' => 'combinada',
                'horas_teoricas' => 60,
                'horas_praticas_total' => 35,
                'horas_praticas_moto' => 20,
                'horas_praticas_carro' => 0,
                'horas_praticas_carga' => 15,
                'horas_praticas_passageiros' => 0,
                'horas_praticas_combinacao' => 0,
                'observacoes' => 'Configuração padrão - AC'
            ],
            'AD' => [
                'nome' => 'Motocicletas + Veículos de Passageiros',
                'tipo' => 'combinada',
                'horas_teoricas' => 60,
                'horas_praticas_total' => 35,
                'horas_praticas_moto' => 20,
                'horas_praticas_carro' => 0,
                'horas_praticas_carga' => 0,
                'horas_praticas_passageiros' => 15,
                'horas_praticas_combinacao' => 0,
                'observacoes' => 'Configuração padrão - AD'
            ],
            'AE' => [
                'nome' => 'Motocicletas + Combinação de Veículos',
                'tipo' => 'combinada',
                'horas_teoricas' => 60,
                'horas_praticas_total' => 35,
                'horas_praticas_moto' => 20,
                'horas_praticas_carro' => 0,
                'horas_praticas_carga' => 0,
                'horas_praticas_passageiros' => 0,
                'horas_praticas_combinacao' => 15,
                'observacoes' => 'Configuração padrão - AE'
            ],
            'BC' => [
                'nome' => 'Automóveis + Veículos de Carga',
                'tipo' => 'combinada',
                'horas_teoricas' => 60,
                'horas_praticas_total' => 35,
                'horas_praticas_moto' => 0,
                'horas_praticas_carro' => 20,
                'horas_praticas_carga' => 15,
                'horas_praticas_passageiros' => 0,
                'horas_praticas_combinacao' => 0,
                'observacoes' => 'Configuração padrão - BC'
            ],
            'BD' => [
                'nome' => 'Automóveis + Veículos de Passageiros',
                'tipo' => 'combinada',
                'horas_teoricas' => 60,
                'horas_praticas_total' => 35,
                'horas_praticas_moto' => 0,
                'horas_praticas_carro' => 20,
                'horas_praticas_carga' => 0,
                'horas_praticas_passageiros' => 15,
                'horas_praticas_combinacao' => 0,
                'observacoes' => 'Configuração padrão - BD'
            ],
            'BE' => [
                'nome' => 'Automóveis + Combinação de Veículos',
                'tipo' => 'combinada',
                'horas_teoricas' => 60,
                'horas_praticas_total' => 35,
                'horas_praticas_moto' => 0,
                'horas_praticas_carro' => 20,
                'horas_praticas_carga' => 0,
                'horas_praticas_passageiros' => 0,
                'horas_praticas_combinacao' => 15,
                'observacoes' => 'Configuração padrão - BE'
            ],
            'CD' => [
                'nome' => 'Veículos de Carga + Passageiros',
                'tipo' => 'combinada',
                'horas_teoricas' => 30,
                'horas_praticas_total' => 30,
                'horas_praticas_moto' => 0,
                'horas_praticas_carro' => 0,
                'horas_praticas_carga' => 15,
                'horas_praticas_passageiros' => 15,
                'horas_praticas_combinacao' => 0,
                'observacoes' => 'Configuração padrão - CD'
            ],
            'CE' => [
                'nome' => 'Veículos de Carga + Combinação',
                'tipo' => 'combinada',
                'horas_teoricas' => 30,
                'horas_praticas_total' => 30,
                'horas_praticas_moto' => 0,
                'horas_praticas_carro' => 0,
                'horas_praticas_carga' => 15,
                'horas_praticas_passageiros' => 0,
                'horas_praticas_combinacao' => 15,
                'observacoes' => 'Configuração padrão - CE'
            ],
            'DE' => [
                'nome' => 'Veículos de Passageiros + Combinação',
                'tipo' => 'combinada',
                'horas_teoricas' => 30,
                'horas_praticas_total' => 30,
                'horas_praticas_moto' => 0,
                'horas_praticas_carro' => 0,
                'horas_praticas_carga' => 0,
                'horas_praticas_passageiros' => 15,
                'horas_praticas_combinacao' => 15,
                'observacoes' => 'Configuração padrão - DE'
            ]
        ];
    }

    /**
     * Verificar se uma categoria existe
     */
    public function categoriaExists($categoria) {
        $config = $this->getConfiguracaoByCategoria($categoria);
        return $config !== false;
    }

    /**
     * Obter estatísticas das configurações
     */
    public function getEstatisticas() {
        $total = $this->db->count('configuracoes_categorias');
        $ativas = $this->db->count('configuracoes_categorias', 'ativo = 1');
        $inativas = $total - $ativas;
        
        return [
            'total' => $total,
            'ativas' => $ativas,
            'inativas' => $inativas
        ];
    }

    /**
     * Desativar configuração
     */
    public function desativarConfiguracao($categoria) {
        return $this->db->update('configuracoes_categorias', ['ativo' => false], 'categoria = ?', [$categoria]);
    }

    /**
     * Ativar configuração
     */
    public function ativarConfiguracao($categoria) {
        return $this->db->update('configuracoes_categorias', ['ativo' => true], 'categoria = ?', [$categoria]);
    }

    /**
     * Deletar configuração
     */
    public function deletarConfiguracao($categoria) {
        return $this->db->delete('configuracoes_categorias', 'categoria = ?', [$categoria]);
    }
    
    /**
     * Obter disciplinas teóricas de uma categoria
     */
    public function getDisciplinasTeoricas($categoria) {
        $config = $this->getConfiguracaoByCategoria($categoria);
        if (!$config) {
            return null;
        }
        
        return [
            'legislacao_transito' => [
                'nome' => 'Legislação de Trânsito',
                'aulas' => (int)$config['legislacao_transito_aulas'],
                'minutos' => (int)$config['legislacao_transito_aulas'] * 50
            ],
            'primeiros_socorros' => [
                'nome' => 'Primeiros Socorros',
                'aulas' => (int)$config['primeiros_socorros_aulas'],
                'minutos' => (int)$config['primeiros_socorros_aulas'] * 50
            ],
            'meio_ambiente_cidadania' => [
                'nome' => 'Meio Ambiente e Cidadania',
                'aulas' => (int)$config['meio_ambiente_cidadania_aulas'],
                'minutos' => (int)$config['meio_ambiente_cidadania_aulas'] * 50
            ],
            'direcao_defensiva' => [
                'nome' => 'Direção Defensiva',
                'aulas' => (int)$config['direcao_defensiva_aulas'],
                'minutos' => (int)$config['direcao_defensiva_aulas'] * 50
            ],
            'mecanica_basica' => [
                'nome' => 'Mecânica Básica',
                'aulas' => (int)$config['mecanica_basica_aulas'],
                'minutos' => (int)$config['mecanica_basica_aulas'] * 50
            ]
        ];
    }
    
    /**
     * Calcular total de aulas teóricas
     */
    public function calcularTotalAulasTeoricas($disciplinas) {
        $total = 0;
        foreach ($disciplinas as $disciplina) {
            $total += (int)$disciplina;
        }
        return $total;
    }
    
    /**
     * Validar disciplinas teóricas
     */
    public function validarDisciplinasTeoricas($disciplinas) {
        $erros = [];
        
        foreach ($disciplinas as $nome => $aulas) {
            if ($aulas < 0) {
                $erros[] = "A quantidade de aulas de {$nome} não pode ser negativa";
            }
            if ($aulas > 100) {
                $erros[] = "A quantidade de aulas de {$nome} não pode ser maior que 100";
            }
        }
        
        return $erros;
    }
}
?>