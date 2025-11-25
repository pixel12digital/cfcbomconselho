# Ajuste Layout Desktop - Dashboard do Aluno

**Data:** 2025-11-25  
**Objetivo:** Ajustar o layout do dashboard do aluno apenas para desktop, organizando cards em múltiplas colunas para aproveitar melhor o espaço.

---

## Problema Identificado

No desktop, as seções "Seu Progresso" e "Próximas Aulas" ocupavam toda a largura disponível, criando:
- Cards de progresso muito largos
- Lista de aulas em coluna única muito longa
- Seção "Ações Rápidas" empurrada para o final da rolagem

### Contexto

- Layout mobile estava OK e não foi alterado
- Já existe tela completa de aulas em `aluno/aulas.php`
- Dashboard deve ser apenas um resumo, não listagem completa

---

## Solução Implementada

### 1. Seção "Seu Progresso" - Grid Responsivo

**Arquivo:** `aluno/dashboard.php` (linhas 259-317)

**Antes:**
- `col-12 col-md-6` (2 colunas no tablet, 1 no mobile)

**Depois:**
- `col-12 col-md-6 col-lg-4` (3 colunas no desktop, 2 no tablet, 1 no mobile)

**Breakpoints:**
- **Mobile (< 768px):** 1 coluna (`col-12`)
- **Tablet (768px - 991px):** 2 colunas (`col-md-6`)
- **Desktop (≥ 992px):** 3 colunas (`col-lg-4`)

### 2. Seção "Próximas Aulas" - Grid de 2 Colunas no Desktop

**Arquivo:** `aluno/dashboard.php` (linhas 338-427)

**Antes:**
```html
<div class="aula-list">
    <div class="aula-item">...</div>
</div>
```

**Depois:**
```html
<div class="aula-list row g-3">
    <div class="col-12 col-md-6">
        <div class="aula-item">...</div>
    </div>
</div>
```

**Breakpoints:**
- **Mobile (< 768px):** 1 coluna (`col-12`)
- **Desktop (≥ 768px):** 2 colunas (`col-md-6`)

### 3. Ajustes de CSS

**Arquivo:** `assets/css/aluno-dashboard.css`

**Adicionado:**

1. **Altura consistente dos cards de progresso no desktop:**
```css
@media (min-width: 992px) {
    .progresso-card {
        min-height: 100px;
    }
}
```

2. **Ajustes para grid de próximas aulas:**
```css
/* Ajustar .aula-list para funcionar como grid Bootstrap */
.aula-list.row {
    margin-left: 0;
    margin-right: 0;
}

/* Remover margin-bottom do .aula-item quando dentro do grid */
.aula-list.row .aula-item {
    margin-bottom: 0;
    height: 100%;
}

/* Desktop: garantir altura consistente dos cards de aula */
@media (min-width: 768px) {
    .aula-list.row .aula-item {
        display: flex;
        flex-direction: column;
        min-height: 200px;
    }
    
    .aula-item .aula-actions {
        margin-top: auto;
    }
}

/* Mobile: manter comportamento original */
@media (max-width: 767.98px) {
    .aula-list.row .aula-item {
        margin-bottom: 0.75rem;
    }
}
```

---

## Arquivos Modificados

### Páginas PHP
1. **`aluno/dashboard.php`**
   - Linha 261: Adicionado `col-lg-4` ao card "Exames Médico e Psicológico"
   - Linha 280: Adicionado `col-lg-4` ao card "Aulas Teóricas"
   - Linha 299: Adicionado `col-lg-4` ao card "Aulas Práticas"
   - Linha 338: Transformado `.aula-list` em `.aula-list row g-3`
   - Linha 340: Envolvido `.aula-item` em `<div class="col-12 col-md-6">`
   - Linha 425: Fechado o div wrapper do grid

### Arquivos CSS
1. **`assets/css/aluno-dashboard.css`**
   - Adicionado media query para altura mínima dos cards de progresso no desktop
   - Adicionados estilos para grid de próximas aulas
   - Ajustes para garantir altura consistente e espaçamento correto

---

## Comportamento Final

### Desktop (≥ 1200px)
- **"Seu Progresso":** 3 cards lado a lado (Exames, Aulas Teóricas, Aulas Práticas)
- **"Próximas Aulas":** Cards em 2 colunas por linha
- **"Ações Rápidas":** Visível com menos rolagem

### Tablet (768px - 1024px)
- **"Seu Progresso":** 2 cards por linha (3º card quebra para linha de baixo)
- **"Próximas Aulas":** 2 colunas (ou 1 se necessário para equilíbrio)
- Layout não quebra

### Mobile (375px - 480px)
- **"Seu Progresso":** 1 card por linha (comportamento original mantido)
- **"Próximas Aulas":** 1 card por linha (comportamento original mantido)
- Nada desalinhado ou cortado lateralmente

---

## Garantias

✅ **Não alterado:**
- Lógica PHP (queries, filtros, regras de negócio)
- Layout mobile (comportamento original mantido)
- Outras páginas do sistema
- Header/footer

✅ **Compatibilidade:**
- Bootstrap 5 grid system utilizado
- Media queries padrão do Bootstrap
- CSS não quebra layout existente

---

## Critérios de Aceite

✅ **Desktop (≥ 1200px):**
- "Seu Progresso" exibe 3 cards lado a lado
- "Próximas Aulas" mostra cards em 2 colunas
- "Ações Rápidas" fica visível com menos rolagem

✅ **Tablet (768px - 1024px):**
- Progresso: 2 cards por linha
- Próximas Aulas: 2 colunas (ou 1 se necessário)
- Layout não quebra

✅ **Mobile (375px - 480px):**
- Layout permanece idêntico ao original
- 1 card por linha em ambas as seções
- Nada desalinhado ou cortado

✅ **Console:**
- Nenhum erro novo de JS ou 404

---

## Notas Técnicas

- Utilizado grid system do Bootstrap 5 (`row`, `col-*`)
- Gap entre cards gerenciado por `g-3` do Bootstrap
- Altura consistente garantida via CSS flexbox
- Media queries seguem breakpoints padrão do Bootstrap
- Comentários adicionados no código para facilitar manutenção

---

**Ajuste concluído:** Layout desktop otimizado, mantendo comportamento mobile original.

