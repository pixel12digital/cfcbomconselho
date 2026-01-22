# Diretório de Fontes de Dados

Este diretório armazena arquivos CSV com dados de municípios do IBGE para uso como **Plano B** quando a API do IBGE não está disponível.

## Arquivo Esperado

**Nome:** `municipios_ibge.csv`

**Localização:** `admin/data/fontes/municipios_ibge.csv`

## Estrutura do CSV

O arquivo CSV deve ter pelo menos as seguintes colunas:

1. **Código IBGE** (opcional, mas recomendado)
2. **Nome do Município** (OBRIGATÓRIO)
3. **UF** (sigla do estado, OBRIGATÓRIO)

### Exemplo de formato:

```csv
1100015,Alta Floresta D'Oeste,RO
1100379,Alto Alegre dos Parecis,RO
1100403,Alto Paraíso,RO
...
```

Ou sem código IBGE:

```csv
Alta Floresta D'Oeste,RO
Alto Alegre dos Parecis,RO
Alto Paraíso,RO
...
```

## Onde Obter o CSV

1. Acesse: https://www.ibge.gov.br/explica/codigos-dos-municipios.php
2. Baixe a lista completa de municípios
3. Salve neste diretório como: `municipios_ibge.csv`

## Como Usar

Após colocar o arquivo CSV aqui, execute:

```bash
php admin/data/importar_municipios_ibge.php
```

Ou use o painel web:

```
admin/tools/atualizar_municipios.php
```

