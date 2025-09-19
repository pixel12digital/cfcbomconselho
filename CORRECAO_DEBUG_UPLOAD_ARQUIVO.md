# 🔧 CORREÇÃO: Debug de Upload de Arquivo

## ❌ **Problema Identificado**

Após corrigir a detecção de tipo de arquivo WebP, agora temos um erro diferente:

```
HTTP 500: Internal Server Error
{"success":false,"error":"Erro interno do servidor: Erro no upload da foto: Erro ao salvar arquivo."}
```

## 🔍 **Análise dos Logs**

Os logs mostram que o processamento manual do FormData está funcionando perfeitamente:

```
PUT - Arquivo encontrado: foto = hero-bg-portrait.desktop-_1600-x-1200-px_.webp
PUT - Dados processados manualmente: {"id":"36","nome":"...","categoria_habilitacao[]":"E",...}
PUT - Arquivos processados: ["foto"]
PUT - Usando dados processados manualmente
```

**O problema está na função `processarUploadFoto`** quando tenta salvar o arquivo usando `move_uploaded_file()`.

## 🛠️ **Solução Implementada**

### **1. Logs Detalhados Adicionados:**

```php
error_log('Processando upload - Nome original: ' . $arquivo['name']);
error_log('Processando upload - Tamanho: ' . $arquivo['size']);
error_log('Processando upload - Tipo: ' . $arquivo['type']);
error_log('Processando upload - Tmp_name: ' . $arquivo['tmp_name']);
error_log('Processando upload - Erro: ' . $arquivo['error']);
error_log('Processando upload - Nome arquivo: ' . $nomeArquivo);
error_log('Processando upload - Diretório destino: ' . $diretorioDestino);
```

### **2. Verificações de Segurança:**

```php
// Verificar se o arquivo temporário existe
if (!file_exists($arquivo['tmp_name'])) {
    error_log('Arquivo temporário não existe: ' . $arquivo['tmp_name']);
    throw new Exception('Arquivo temporário não encontrado.');
}

// Verificar permissões do diretório
if (!is_writable($diretorioDestino)) {
    error_log('Diretório não é gravável: ' . $diretorioDestino);
    throw new Exception('Diretório de destino não tem permissão de escrita.');
}
```

### **3. Debug do `move_uploaded_file`:**

```php
// Mover arquivo
error_log('Tentando mover arquivo...');
if (!move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) {
    error_log('Erro no move_uploaded_file - tmp_name: ' . $arquivo['tmp_name']);
    error_log('Erro no move_uploaded_file - destino: ' . $caminhoCompleto);
    error_log('Erro no move_uploaded_file - último erro PHP: ' . error_get_last()['message']);
    throw new Exception('Erro ao salvar arquivo.');
}
```

## 📝 **Arquivos Modificados**

### **admin/api/instrutores.php**
- ✅ **Função `processarUploadFoto`**: Logs detalhados adicionados
- ✅ **Verificações de segurança**: Arquivo temporário e permissões
- ✅ **Debug completo**: Para identificar exatamente onde está o problema

## 🧪 **Como Testar e Verificar Logs**

### **1. Teste de Upload:**
1. Acesse: `admin/index.php?page=instrutores`
2. Clique em "Editar" em um instrutor
3. Selecione um arquivo WebP
4. Clique em "Salvar"

### **2. Verificar Logs do Apache:**
Após tentar salvar, verifique os logs em:
```
C:\xampp\apache\logs\error.log
```

### **3. Logs Esperados (Sucesso):**
```
Processando upload - Nome original: hero-bg-portrait.desktop-_1600-x-1200-px_.webp
Processando upload - Tamanho: 123456
Processando upload - Tipo: image/webp
Processando upload - Tmp_name: /tmp/php_XXXXXX
Processando upload - Diretório destino: ../../assets/uploads/instrutores/
Diretório existe: ../../assets/uploads/instrutores/
Caminho completo: ../../assets/uploads/instrutores/instrutor_36_1734625784.webp
Tentando mover arquivo...
Arquivo movido com sucesso para: ../../assets/uploads/instrutores/instrutor_36_1734625784.webp
```

### **4. Logs de Erro (se houver problema):**
```
Processando upload - Tmp_name: /tmp/php_XXXXXX
Arquivo temporário não existe: /tmp/php_XXXXXX
```

Ou:
```
Diretório não é gravável: ../../assets/uploads/instrutores/
```

Ou:
```
Erro no move_uploaded_file - tmp_name: /tmp/php_XXXXXX
Erro no move_uploaded_file - destino: ../../assets/uploads/instrutores/instrutor_36_1734625784.webp
Erro no move_uploaded_file - último erro PHP: [mensagem específica]
```

## 🔍 **Possíveis Causas do Problema**

### **1. Arquivo Temporário Não Existe:**
- O processamento manual do FormData pode não estar salvando o arquivo temporário corretamente
- O caminho temporário pode estar incorreto

### **2. Permissões de Diretório:**
- O diretório `assets/uploads/instrutores/` pode não ter permissão de escrita
- Problemas de permissão no Windows/XAMPP

### **3. Função `move_uploaded_file`:**
- Esta função só funciona com arquivos enviados via HTTP POST
- Como estamos processando manualmente, pode não funcionar
- Pode ser necessário usar `copy()` ou `rename()` em vez de `move_uploaded_file()`

## 🚀 **Próximos Passos**

Após testar, os logs nos dirão exatamente qual é o problema:

1. **Se arquivo temporário não existe**: Corrigir o processamento manual do FormData
2. **Se permissões**: Ajustar permissões do diretório
3. **Se `move_uploaded_file` falha**: Substituir por `copy()` ou `rename()`

## 📊 **Status: DEBUG IMPLEMENTADO**

- ✅ **Logs detalhados** para identificar o problema exato
- ✅ **Verificações de segurança** implementadas
- ✅ **Debug completo** da função de upload
- 🔄 **Aguardando logs** para identificar a causa raiz

**Teste novamente e me envie os logs para identificarmos exatamente onde está o problema!** 🔍
