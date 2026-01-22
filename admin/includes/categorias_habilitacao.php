<?php
/**
 * Configuração das Categorias de Habilitação
 * Baseado nas normas do CONTRAN/DETRAN
 * 
 * Este arquivo define as cargas horárias obrigatórias para cada categoria
 * de habilitação conforme as resoluções vigentes.
 */

class CategoriasHabilitacao {
    
    /**
     * Carga horária obrigatória por categoria
     * Baseado na Resolução CONTRAN 789/2020 e atualizações
     */
    public static function getCargaHorariaObrigatoria() {
        return [
            // CATEGORIAS BÁSICAS (Primeira habilitação)
            'A' => [
                'nome' => 'Motocicletas',
                'teorica' => 45,  // horas teóricas obrigatórias
                'pratica' => 20,  // horas práticas obrigatórias
                'simulador' => 0, // não aplicável
                'noturna' => false, // não obrigatória desde 2021
                'descricao' => 'Motocicletas, ciclomotores e triciclos'
            ],
            
            'B' => [
                'nome' => 'Automóveis',
                'teorica' => 45,  // horas teóricas obrigatórias
                'pratica' => 20,  // horas práticas obrigatórias
                'simulador' => 5, // opcional até 5h
                'noturna' => false, // não obrigatória desde 2021
                'descricao' => 'Automóveis, caminhonetes e utilitários'
            ],
            
            // CATEGORIAS COMBINADAS (Primeira habilitação)
            'AB' => [
                'nome' => 'Motocicletas + Automóveis',
                'teorica' => 45,  // mesma carga teórica
                'pratica' => 40,  // 20h moto + 20h carro
                'pratica_detalhada' => [
                    'A' => 20, // moto
                    'B' => 20  // carro
                ],
                'simulador' => 5, // opcional para carro
                'noturna' => false,
                'descricao' => 'Motocicletas e automóveis'
            ],
            
            // CATEGORIAS DE ADIÇÃO (Para quem já tem habilitação)
            'C' => [
                'nome' => 'Veículos de Carga',
                'teorica' => 0,   // não há curso teórico específico
                'pratica' => 20,  // horas práticas obrigatórias
                'simulador' => 0,
                'noturna' => false,
                'descricao' => 'Veículos de carga acima de 3.500kg',
                'requisito' => 'Ter categoria B há pelo menos 1 ano'
            ],
            
            'D' => [
                'nome' => 'Veículos de Passageiros',
                'teorica' => 0,   // não há curso teórico específico
                'pratica' => 20,  // horas práticas obrigatórias
                'simulador' => 0,
                'noturna' => false,
                'descricao' => 'Veículos de transporte de passageiros',
                'requisito' => 'Ter categoria B há pelo menos 2 anos'
            ],
            
            'E' => [
                'nome' => 'Combinação de Veículos',
                'teorica' => 0,   // não há curso teórico específico
                'pratica' => 20,  // horas práticas obrigatórias
                'simulador' => 0,
                'noturna' => false,
                'descricao' => 'Combinação de veículos (carreta, bitrem)',
                'requisito' => 'Ter categoria C ou D há pelo menos 1 ano'
            ],
            
            // CATEGORIAS COMBINADAS DE ADIÇÃO
            'AC' => [
                'nome' => 'Motocicletas + Veículos de Carga',
                'teorica' => 0,   // não há curso teórico específico
                'pratica' => 40,  // 20h moto + 20h carga
                'pratica_detalhada' => [
                    'A' => 20, // moto
                    'C' => 20  // carga
                ],
                'simulador' => 0,
                'noturna' => false,
                'descricao' => 'Motocicletas e veículos de carga',
                'requisito' => 'Ter categoria B há pelo menos 1 ano'
            ],
            
            'AD' => [
                'nome' => 'Motocicletas + Veículos de Passageiros',
                'teorica' => 0,   // não há curso teórico específico
                'pratica' => 40,  // 20h moto + 20h passageiros
                'pratica_detalhada' => [
                    'A' => 20, // moto
                    'D' => 20  // passageiros
                ],
                'simulador' => 0,
                'noturna' => false,
                'descricao' => 'Motocicletas e veículos de passageiros',
                'requisito' => 'Ter categoria B há pelo menos 2 anos'
            ],
            
            'AE' => [
                'nome' => 'Motocicletas + Combinação de Veículos',
                'teorica' => 0,   // não há curso teórico específico
                'pratica' => 40,  // 20h moto + 20h combinação
                'pratica_detalhada' => [
                    'A' => 20, // moto
                    'E' => 20  // combinação
                ],
                'simulador' => 0,
                'noturna' => false,
                'descricao' => 'Motocicletas e combinação de veículos',
                'requisito' => 'Ter categoria C ou D há pelo menos 1 ano'
            ],
            
            'BC' => [
                'nome' => 'Automóveis + Veículos de Carga',
                'teorica' => 0,   // não há curso teórico específico
                'pratica' => 40,  // 20h carro + 20h carga
                'pratica_detalhada' => [
                    'B' => 20, // carro
                    'C' => 20  // carga
                ],
                'simulador' => 5, // opcional para carro
                'noturna' => false,
                'descricao' => 'Automóveis e veículos de carga',
                'requisito' => 'Ter categoria B há pelo menos 1 ano'
            ],
            
            'BD' => [
                'nome' => 'Automóveis + Veículos de Passageiros',
                'teorica' => 0,   // não há curso teórico específico
                'pratica' => 40,  // 20h carro + 20h passageiros
                'pratica_detalhada' => [
                    'B' => 20, // carro
                    'D' => 20  // passageiros
                ],
                'simulador' => 5, // opcional para carro
                'noturna' => false,
                'descricao' => 'Automóveis e veículos de passageiros',
                'requisito' => 'Ter categoria B há pelo menos 2 anos'
            ],
            
            'BE' => [
                'nome' => 'Automóveis + Combinação de Veículos',
                'teorica' => 0,   // não há curso teórico específico
                'pratica' => 40,  // 20h carro + 20h combinação
                'pratica_detalhada' => [
                    'B' => 20, // carro
                    'E' => 20  // combinação
                ],
                'simulador' => 5, // opcional para carro
                'noturna' => false,
                'descricao' => 'Automóveis e combinação de veículos',
                'requisito' => 'Ter categoria C ou D há pelo menos 1 ano'
            ],
            
            'CD' => [
                'nome' => 'Veículos de Carga + Passageiros',
                'teorica' => 0,   // não há curso teórico específico
                'pratica' => 40,  // 20h carga + 20h passageiros
                'pratica_detalhada' => [
                    'C' => 20, // carga
                    'D' => 20  // passageiros
                ],
                'simulador' => 0,
                'noturna' => false,
                'descricao' => 'Veículos de carga e passageiros',
                'requisito' => 'Ter categoria B há pelo menos 2 anos'
            ],
            
            'CE' => [
                'nome' => 'Veículos de Carga + Combinação',
                'teorica' => 0,   // não há curso teórico específico
                'pratica' => 40,  // 20h carga + 20h combinação
                'pratica_detalhada' => [
                    'C' => 20, // carga
                    'E' => 20  // combinação
                ],
                'simulador' => 0,
                'noturna' => false,
                'descricao' => 'Veículos de carga e combinação',
                'requisito' => 'Ter categoria C há pelo menos 1 ano'
            ],
            
            'DE' => [
                'nome' => 'Veículos de Passageiros + Combinação',
                'teorica' => 0,   // não há curso teórico específico
                'pratica' => 40,  // 20h passageiros + 20h combinação
                'pratica_detalhada' => [
                    'D' => 20, // passageiros
                    'E' => 20  // combinação
                ],
                'simulador' => 0,
                'noturna' => false,
                'descricao' => 'Veículos de passageiros e combinação',
                'requisito' => 'Ter categoria D há pelo menos 1 ano'
            ],
            
            // AUTORIZAÇÃO ESPECIAL
            'ACC' => [
                'nome' => 'Autorização para Conduzir Ciclomotores',
                'teorica' => 20,  // horas teóricas obrigatórias
                'pratica' => 5,   // horas práticas obrigatórias
                'simulador' => 0,
                'noturna' => false,
                'descricao' => 'Ciclomotores até 50cc',
                'observacao' => 'Para menores de 18 anos'
            ]
        ];
    }
    
    /**
     * Obter informações de uma categoria específica
     */
    public static function getCategoria($categoria) {
        $categorias = self::getCargaHorariaObrigatoria();
        return $categorias[$categoria] ?? null;
    }
    
    /**
     * Verificar se categoria é de primeira habilitação
     */
    public static function isPrimeiraHabilitacao($categoria) {
        $primeirasHabilitacoes = ['A', 'B', 'AB', 'ACC'];
        return in_array($categoria, $primeirasHabilitacoes);
    }
    
    /**
     * Verificar se categoria é de adição
     */
    public static function isAdicao($categoria) {
        return !self::isPrimeiraHabilitacao($categoria);
    }
    
    /**
     * Obter total de horas práticas para uma categoria
     */
    public static function getTotalHorasPraticas($categoria) {
        $info = self::getCategoria($categoria);
        if (!$info) return 0;
        
        if (isset($info['pratica_detalhada'])) {
            return array_sum($info['pratica_detalhada']);
        }
        
        return $info['pratica'];
    }
    
    /**
     * Obter horas práticas por subcategoria
     */
    public static function getHorasPraticasDetalhadas($categoria) {
        $info = self::getCategoria($categoria);
        if (!$info) return [];
        
        if (isset($info['pratica_detalhada'])) {
            return $info['pratica_detalhada'];
        }
        
        return [$categoria => $info['pratica']];
    }
    
    /**
     * Obter todas as categorias disponíveis
     */
    public static function getTodasCategorias() {
        return array_keys(self::getCargaHorariaObrigatoria());
    }
    
    /**
     * Obter categorias de primeira habilitação
     */
    public static function getCategoriasPrimeiraHabilitacao() {
        return ['A', 'B', 'AB', 'ACC'];
    }
    
    /**
     * Obter categorias de adição
     */
    public static function getCategoriasAdicao() {
        $todas = self::getTodasCategorias();
        $primeiras = self::getCategoriasPrimeiraHabilitacao();
        return array_diff($todas, $primeiras);
    }
    
    /**
     * Validar se categoria existe
     */
    public static function categoriaExiste($categoria) {
        return array_key_exists($categoria, self::getCargaHorariaObrigatoria());
    }
    
    /**
     * Obter descrição completa da categoria
     */
    public static function getDescricaoCompleta($categoria) {
        $info = self::getCategoria($categoria);
        if (!$info) return 'Categoria não encontrada';
        
        $descricao = $info['nome'] . ' - ' . $info['descricao'];
        
        if (isset($info['requisito'])) {
            $descricao .= ' (Requisito: ' . $info['requisito'] . ')';
        }
        
        return $descricao;
    }
    
    /**
     * Calcular progresso por categoria
     */
    public static function calcularProgresso($categoria, $aulasConcluidas) {
        $totalNecessario = self::getTotalHorasPraticas($categoria);
        if ($totalNecessario == 0) return 100;
        
        return min(100, ($aulasConcluidas / $totalNecessario) * 100);
    }
    
    /**
     * Obter status do progresso
     */
    public static function getStatusProgresso($categoria, $aulasConcluidas) {
        $progresso = self::calcularProgresso($categoria, $aulasConcluidas);
        
        if ($progresso >= 100) return 'concluido';
        if ($progresso >= 75) return 'avancado';
        if ($progresso >= 50) return 'intermediario';
        if ($progresso >= 25) return 'iniciante';
        return 'nao_iniciado';
    }
}
