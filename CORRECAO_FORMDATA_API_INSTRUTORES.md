# 🔧 CORREÇÃO: FormData na API de Instrutores

## ❌ **Problema Identificado**

Ao implementar o upload de fotos, o sistema estava retornando erro:
```
HTTP 400: Bad Request - {"error":"ID do instrutor é obrigatório"}
```

### **Causa Raiz:**
- O JavaScript estava enviando dados via `FormData` (multipart/form-data)
- A API estava tentando ler dados via `file_get_contents('php://input')` (JSON)
- Quando usamos FormData, os dados vêm via `$_POST` e `$_FILES`, não via input stream

## ✅ **Solução Implementada**

### **1. Detecção de Tipo de Conteúdo**
```php
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

if (strpos($contentType, 'multipart/form-data') !== false) {
    // Dados vêm via FormData (POST + FILES)
    $data = $_POST;
} else {
    // Dados vêm via JSON
    $data = json_decode(file_get_contents('php://input'), true);
}
```

### **2. Processamento de Arrays no FormData**
- **Categorias:** `categoria_habilitacao[]` → Array no `$_POST`
- **Dias da Semana:** `dias_semana[]` → Array no `$_POST`
- **Foto:** `foto` → Arquivo no `$_FILES`

### **3. Compatibilidade Dupla**
A API agora suporta ambos os formatos:
- ✅ **JSON** (para compatibilidade com código existente)
- ✅ **FormData** (para upload de arquivos)

## 📝 **Arquivos Modificados**

### **admin/api/instrutores.php**
- ✅ Seção POST: Detecção de FormData vs JSON
- ✅ Seção PUT: Detecção de FormData vs JSON
- ✅ Processamento correto de arrays (categorias, dias da semana)
- ✅ Logs detalhados para debug

### **admin/assets/js/instrutores.js**
- ✅ Uso de FormData para envio de dados
- ✅ Configuração correta de headers (sem Content-Type para FormData)
- ✅ Adição de foto ao FormData

## 🧪 **Como Testar**

### **1. Editar Instrutor com Foto:**
1. Acesse: `admin/index.php?page=instrutores`
2. Clique em "Editar" em um instrutor
3. Selecione uma nova foto
4. Clique em "Salvar"
5. ✅ Deve salvar sem erros

### **2. Cadastrar Instrutor com Foto:**
1. Clique em "Novo Instrutor"
2. Preencha os dados
3. Selecione uma foto
4. Clique em "Salvar"
5. ✅ Deve criar sem erros

## 🔍 **Logs de Debug**

A API agora gera logs detalhados:
```
POST - Dados recebidos via FormData: {...}
POST - Arquivos recebidos: ["foto"]
POST - Categorias como array: ["A","E"]
POST - Dias da semana como array: ["segunda","terca",...]
```

## 🚀 **Status: CORRIGIDO**

- ✅ Upload de fotos funcionando
- ✅ Edição de instrutores funcionando
- ✅ Cadastro de instrutores funcionando
- ✅ Compatibilidade mantida com JSON
- ✅ Logs de debug implementados

O sistema agora processa corretamente tanto dados JSON quanto FormData, permitindo o upload de fotos sem quebrar a funcionalidade existente.
