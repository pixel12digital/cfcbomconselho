# Implementação da Aba Documentos - Alunos

## Resumo da Implementação

Implementação completa da funcionalidade de upload e gerenciamento de documentos na aba "Documentos" do modal "Editar Aluno", seguindo o padrão do sistema de upload de foto de perfil.

## Arquivos Criados/Modificados

### 1. Backend

**`admin/api/aluno_documentos.php`** (CRIADO)
- API completa para gerenciamento de documentos
- Endpoints: GET (listar), POST (upload), DELETE (remover)
- Validações de extensão (PDF, JPG, JPEG, PNG) e tamanho (máx. 5MB)
- Criação automática da tabela `alunos_documentos` se não existir
- Armazenamento em `admin/uploads/alunos_documentos/{aluno_id}/`

### 2. Frontend

**`admin/pages/alunos.php`** (MODIFICADO)
- **Aba Documentos (HTML):** Formulário de upload + lista de documentos
- **JavaScript:**
  - `carregarDocumentos(alunoId)` - Carrega e exibe lista de documentos
  - `enviarDocumento(event)` - Upload de novo documento
  - `excluirDocumento(documentoId, alunoId)` - Remove documento
  - `formatarTamanhoArquivo(bytes)` - Formata tamanho para exibição
  - `formatarDataDocumento(dataString)` - Formata data/hora
  - `construirUrlArquivo(caminhoRelativo)` - Constrói URL absoluta
  - `carregarContadorDocumentos(alunoId)` - Carrega contador no modal Detalhes
- **Modal Detalhes:** Adicionado contador de documentos na seção "Documento e Processo"

### 3. Documentação

**`docs/FLUXO_FOTO_PERFIL_REFERENCIA.md`** (CRIADO)
- Documentação completa do fluxo de upload de foto de perfil
- Usado como referência para implementação de documentos

## Estrutura da Tabela `alunos_documentos`

```sql
CREATE TABLE IF NOT EXISTS alunos_documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    nome_original VARCHAR(255) NOT NULL,
    arquivo VARCHAR(500) NOT NULL,
    mime_type VARCHAR(100) DEFAULT 'application/octet-stream',
    tamanho_bytes INT DEFAULT 0,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_aluno_id (aluno_id),
    INDEX idx_tipo (tipo),
    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
```

### Campos

- **id:** Chave primária auto-incremento
- **aluno_id:** FK para `alunos.id` (CASCADE DELETE)
- **tipo:** Tipo do documento (rg, cpf, comprovante_residencia, foto_3x4, outro)
- **nome_original:** Nome do arquivo enviado pelo usuário
- **arquivo:** Caminho relativo do arquivo salvo (ex: `admin/uploads/alunos_documentos/123/rg_1234567890_abc123.pdf`)
- **mime_type:** Tipo MIME do arquivo
- **tamanho_bytes:** Tamanho do arquivo em bytes
- **criado_em:** Data/hora de criação

## Exemplo de JSON Retornado pela API

### GET `api/aluno_documentos.php?aluno_id=123`

```json
{
  "success": true,
  "documentos": [
    {
      "id": 1,
      "tipo": "rg",
      "nome_original": "RG_Frente.pdf",
      "arquivo": "admin/uploads/alunos_documentos/123/rg_1703123456_abc123.pdf",
      "mime_type": "application/pdf",
      "tamanho_bytes": 245678,
      "criado_em": "2024-12-20 14:30:00"
    },
    {
      "id": 2,
      "tipo": "cpf",
      "nome_original": "CPF.jpg",
      "arquivo": "admin/uploads/alunos_documentos/123/cpf_1703123457_def456.jpg",
      "mime_type": "image/jpeg",
      "tamanho_bytes": 156789,
      "criado_em": "2024-12-20 15:45:00"
    }
  ]
}
```

### POST `api/aluno_documentos.php?aluno_id=123` (FormData)

**Request:**
- `aluno_id`: 123
- `tipo`: "rg"
- `arquivo`: (file)

**Response:**
```json
{
  "success": true,
  "message": "Documento enviado com sucesso",
  "documento": {
    "id": 3,
    "tipo": "rg",
    "nome_original": "RG_Frente.pdf",
    "arquivo": "admin/uploads/alunos_documentos/123/rg_1703123458_ghi789.pdf",
    "mime_type": "application/pdf",
    "tamanho_bytes": 245678,
    "criado_em": "2024-12-20 16:00:00"
  }
}
```

### DELETE `api/aluno_documentos.php?id=3`

**Response:**
```json
{
  "success": true,
  "message": "Documento excluído com sucesso"
}
```

## Interface da Aba Documentos

### Layout

1. **Título:** "Documentos do Aluno"
2. **Descrição:** "Envie e gerencie os documentos do aluno. Formatos aceitos: PDF, JPG, PNG (máx. 5MB)."
3. **Formulário de Upload:**
   - Select: Tipo de Documento (RG, CPF, Comprovante de Residência, Foto 3x4, Outro)
   - Input File: Escolher arquivo
   - Botão: "Enviar" (com loading durante upload)
4. **Lista de Documentos:**
   - Cards em grid (2 colunas)
   - Cada card mostra:
     - Ícone do tipo de arquivo (PDF ou imagem)
     - Tipo do documento (label formatado)
     - Nome original do arquivo
     - Data de envio formatada
     - Tamanho formatado (KB/MB)
     - Botão "Abrir" (abre em nova aba)
     - Botão "Excluir" (com confirmação)

### Estados

- **Carregando:** Spinner com texto "Carregando documentos..."
- **Vazio:** Ícone + texto "Nenhum documento encontrado" + dica para enviar
- **Com documentos:** Grid de cards com informações completas
- **Erro:** Alert vermelho com mensagem de erro

## Integração com Modal Detalhes

No modal "Detalhes do Aluno", na seção "Documento e Processo", foi adicionada a linha:

```
Documentos anexados: [badge com quantidade]
```

- Badge verde se houver documentos
- Badge cinza se não houver documentos
- Badge amarelo se houver erro ao carregar

## Fluxo de Funcionamento

### 1. Upload de Documento

1. Usuário seleciona tipo e arquivo
2. Clica em "Enviar"
3. Frontend valida tamanho (5MB)
4. FormData enviado para `POST api/aluno_documentos.php?aluno_id={id}`
5. Backend valida extensão e tamanho
6. Arquivo salvo em `admin/uploads/alunos_documentos/{aluno_id}/{tipo}_{timestamp}_{uniqid}.{ext}`
7. Registro inserido na tabela `alunos_documentos`
8. Frontend recarrega lista de documentos
9. Mensagem de sucesso exibida

### 2. Listagem de Documentos

1. Ao abrir aba Documentos, `carregarDocumentos(alunoId)` é chamada
2. GET `api/aluno_documentos.php?aluno_id={id}`
3. Lista renderizada em cards
4. Cada documento pode ser aberto ou excluído

### 3. Exclusão de Documento

1. Usuário clica em "Excluir"
2. Confirmação via `confirm()`
3. DELETE `api/aluno_documentos.php?id={documento_id}`
4. Backend remove arquivo físico e registro do banco
5. Frontend recarrega lista
6. Mensagem de sucesso exibida

### 4. Contador no Detalhes

1. Ao abrir modal Detalhes, `carregarContadorDocumentos(alunoId)` é chamada
2. GET `api/aluno_documentos.php?aluno_id={id}`
3. Contador atualizado com quantidade de documentos

## Validações Implementadas

### Frontend
- Tipo de documento obrigatório
- Arquivo obrigatório
- Tamanho máximo: 5MB
- Extensões aceitas: PDF, JPG, JPEG, PNG

### Backend
- Autenticação obrigatória
- Permissão: apenas admin e secretaria
- Validação de extensão (pdf, jpg, jpeg, png)
- Validação de tamanho (máx. 5MB)
- Verificação de existência do aluno
- Criação automática de diretório se não existir
- Verificação de permissões de escrita

## Segurança

- Autenticação e autorização verificadas
- Validação de tipos de arquivo
- Validação de tamanho
- Nomes de arquivo únicos (timestamp + uniqid)
- Caminhos relativos salvos no banco
- Remoção de arquivo físico ao excluir documento
- CASCADE DELETE: documentos removidos automaticamente ao excluir aluno

## Testes Recomendados

### 1. Upload Simples
- [ ] Abrir aluno matriculado → aba Documentos
- [ ] Selecionar "RG" + arquivo PDF
- [ ] Enviar
- [ ] Verificar documento na lista
- [ ] Clicar em "Abrir" → arquivo abre em nova aba

### 2. Persistência
- [ ] Fechar modal
- [ ] Reabrir "Editar Aluno" → aba Documentos
- [ ] Confirmar que lista continua lá

### 3. Exclusão
- [ ] Clicar em "Excluir" em um documento
- [ ] Confirmar remoção
- [ ] Reabrir modal → documento não aparece mais

### 4. Detalhes do Aluno
- [ ] Abrir modal Detalhes
- [ ] Verificar contador "Documentos anexados: X"
- [ ] Confirmar que quantidade está correta

### 5. Validações
- [ ] Tentar enviar sem tipo → erro
- [ ] Tentar enviar sem arquivo → erro
- [ ] Tentar enviar arquivo > 5MB → erro
- [ ] Tentar enviar arquivo não permitido → erro

## Observações Técnicas

- A tabela é criada automaticamente na primeira requisição à API
- Diretórios são criados automaticamente por aluno (`{aluno_id}/`)
- Nomes de arquivo incluem timestamp e uniqid para evitar conflitos
- Caminhos relativos são salvos no banco (facilita migração/backup)
- Função `carregarDocumentos` é chamada automaticamente ao abrir aba Documentos
- Contador no Detalhes é carregado assincronamente após preencher modal

## Próximos Passos (Sugestões)

- [ ] Adicionar preview de imagens antes do upload
- [ ] Adicionar drag & drop para upload
- [ ] Adicionar progress bar durante upload
- [ ] Adicionar filtro por tipo de documento na listagem
- [ ] Adicionar busca por nome de arquivo
- [ ] Adicionar download em lote
- [ ] Adicionar status de aprovação/rejeição de documentos
- [ ] Adicionar observações por documento

