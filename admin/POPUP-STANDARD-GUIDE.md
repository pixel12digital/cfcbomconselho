# 📋 Guia de Padronização de Popups - CFC Bom Conselho

## 🎯 Objetivo

Este guia estabelece o padrão visual e estrutural para todos os popups do sistema, garantindo consistência na experiência do usuário e facilitando a manutenção do código.

## 🎨 Paleta de Cores Oficial

- **Azul Principal:** `#023A8D` (azul marinho)
- **Laranja Secundário:** `#F7931E` (laranja vibrante)
- **Gradiente Azul:** `linear-gradient(135deg, #023A8D 0%, #1e5bb8 100%)`

## 📁 Arquivos de Referência

1. **CSS:** `admin/assets/css/popup-reference.css`
2. **HTML:** `admin/popup-reference.html`
3. **Documentação:** `admin/POPUP-STANDARD-GUIDE.md`

## 🚀 Como Implementar um Novo Popup

### Passo 1: Incluir o CSS
```html
<link href="assets/css/popup-reference.css" rel="stylesheet">
```

### Passo 2: Estrutura HTML Base
```html
<div class="popup-modal" id="meuPopup">
    <div class="popup-modal-wrapper">
        
        <!-- HEADER -->
        <div class="popup-modal-header">
            <div class="header-content">
                <div class="header-icon">
                    <i class="fas fa-[ICONE]"></i>
                </div>
                <div class="header-text">
                    <h5>[TÍTULO DO POPUP]</h5>
                    <small>[SUBTÍTULO DESCRITIVO]</small>
                </div>
            </div>
            <button type="button" class="popup-modal-close" onclick="fecharPopup()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <!-- CONTEÚDO -->
        <div class="popup-modal-content">
            <!-- SEU CONTEÚDO AQUI -->
        </div>
        
        <!-- FOOTER -->
        <div class="popup-modal-footer">
            <div class="popup-footer-info">
                <small>
                    <i class="fas fa-info-circle"></i>
                    [MENSAGEM INFORMATIVA]
                </small>
            </div>
            <div class="popup-footer-actions">
                <button type="button" class="popup-secondary-button" onclick="fecharPopup()">
                    <i class="fas fa-times"></i>
                    Fechar
                </button>
                <button type="button" class="popup-save-button" onclick="salvarDados()">
                    <i class="fas fa-save"></i>
                    Salvar
                </button>
            </div>
        </div>
        
    </div>
</div>
```

### Passo 3: JavaScript Básico
```javascript
// Abrir popup
function abrirPopup() {
    const popup = document.getElementById('meuPopup');
    popup.classList.add('show', 'popup-fade-in');
    document.body.style.overflow = 'hidden';
}

// Fechar popup
function fecharPopup() {
    const popup = document.getElementById('meuPopup');
    popup.classList.remove('show');
    document.body.style.overflow = 'auto';
}

// Event listeners
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') fecharPopup();
});

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('popup-modal')) fecharPopup();
});
```

## 🧩 Componentes Disponíveis

### Barra de Busca
```html
<div class="popup-search-container">
    <div class="popup-search-wrapper">
        <input type="text" class="popup-search-input" placeholder="Buscar...">
        <i class="fas fa-search popup-search-icon"></i>
    </div>
</div>
```

### Estatísticas
```html
<div class="popup-stats-container">
    <div class="popup-stats-wrapper">
        <div class="popup-stats-item">
            <div class="popup-stats-icon">
                <div class="icon-circle">
                    <i class="fas fa-[ICONE]"></i>
                </div>
            </div>
            <div class="popup-stats-text">
                <h6>Total: <span class="stats-number">0</span></h6>
            </div>
        </div>
    </div>
</div>
```

### Seção com Título e Botão
```html
<div class="popup-section-header">
    <div class="popup-section-title">
        <h6>[TÍTULO DA SEÇÃO]</h6>
        <small>[DESCRIÇÃO DA SEÇÃO]</small>
    </div>
    <button type="button" class="popup-primary-button" onclick="acao()">
        <i class="fas fa-plus"></i>
        [TEXTO DO BOTÃO]
    </button>
</div>
```

### Grid de Itens
```html
<div class="popup-items-grid">
    <div class="popup-item-card">
        <div class="popup-item-card-header">
            <div class="popup-item-card-content">
                <h6 class="popup-item-card-title">[TÍTULO DO ITEM]</h6>
                <div class="popup-item-card-code">[CÓDIGO/ID]</div>
            </div>
            <div class="popup-item-card-actions">
                <button type="button" class="popup-item-card-menu">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
            </div>
        </div>
    </div>
</div>
```

### Estados de Carregamento
```html
<!-- Loading -->
<div class="popup-loading-state">
    <div class="popup-loading-spinner"></div>
    <div class="popup-loading-text">
        <h6>Carregando...</h6>
        <p>Aguarde enquanto processamos</p>
    </div>
</div>

<!-- Vazio -->
<div class="popup-empty-state">
    <div class="empty-icon">
        <i class="fas fa-[ICONE]"></i>
    </div>
    <h5>Nenhum item encontrado</h5>
    <p>Descrição do estado vazio</p>
    <button type="button" class="popup-primary-button">
        <i class="fas fa-plus"></i>
        Criar Novo
    </button>
</div>

<!-- Erro -->
<div class="popup-error-state">
    <div class="error-icon">
        <i class="fas fa-exclamation-triangle"></i>
    </div>
    <h5>Erro ao carregar</h5>
    <p>Descrição do erro</p>
    <button type="button" class="popup-secondary-button">
        <i class="fas fa-redo"></i>
        Tentar Novamente
    </button>
</div>
```

## 🎨 Classes de Estilo

### Botões
- `popup-primary-button` - Botão principal (azul)
- `popup-secondary-button` - Botão secundário (cinza)
- `popup-save-button` - Botão de salvar (azul)

### Cards
- `popup-item-card` - Card básico
- `popup-item-card.active` - Card ativo (borda verde)
- `popup-item-card.modified` - Card modificado (borda amarela)
- `popup-item-card.new` - Card novo (borda verde + animação)

### Utilitários
- `popup-hidden` - Esconder elemento
- `popup-visible` - Mostrar elemento
- `popup-text-center` - Texto centralizado
- `popup-text-muted` - Texto cinza
- `popup-text-primary` - Texto azul
- `popup-shadow-sm` - Sombra pequena
- `popup-shadow` - Sombra média

## 📱 Responsividade

O sistema é totalmente responsivo:

- **Desktop (≥1200px):** Modal centralizado, grid com 3-4 colunas
- **Tablet (768px-1199px):** Modal adaptado, grid com 2-3 colunas
- **Mobile (<768px):** Modal em tela cheia, grid com 1 coluna

## ♿ Acessibilidade

- Focus states visíveis para todos os elementos interativos
- Suporte a navegação por teclado (ESC para fechar)
- Contraste adequado seguindo WCAG 2.1
- Estados de loading e erro claramente identificados

## 🔧 Personalização

### O que PODE ser personalizado:
- Título e subtítulo do header
- Ícone do header (use Font Awesome)
- Conteúdo do modal
- Botões do footer
- Funcionalidades JavaScript

### O que NÃO deve ser modificado:
- Classes CSS base
- Estrutura HTML principal
- Paleta de cores oficial
- Sistema de responsividade

## 📋 Checklist de Implementação

- [ ] CSS de referência incluído
- [ ] Estrutura HTML base implementada
- [ ] JavaScript básico adicionado
- [ ] Ícone do header definido
- [ ] Título e subtítulo preenchidos
- [ ] Conteúdo específico implementado
- [ ] Botões do footer configurados
- [ ] Estados de loading/erro implementados
- [ ] Responsividade testada
- [ ] Acessibilidade verificada

## 🐛 Solução de Problemas

### Popup não abre
- Verificar se o CSS está incluído
- Verificar se o ID do popup está correto
- Verificar se não há erros JavaScript no console

### Estilos não aplicados
- Verificar se o CSS de referência está carregado
- Verificar se as classes CSS estão corretas
- Verificar se não há conflitos com outros CSS

### Responsividade quebrada
- Verificar se o viewport meta tag está presente
- Verificar se as media queries estão funcionando
- Testar em diferentes tamanhos de tela

## 📞 Suporte

Para dúvidas ou problemas com a implementação:
1. Consulte este guia
2. Verifique o arquivo de referência HTML
3. Teste com o popup de demonstração
4. Verifique o console do navegador para erros

---

**Última atualização:** Janeiro 2025  
**Versão:** 1.0  
**Autor:** Sistema CFC Bom Conselho
