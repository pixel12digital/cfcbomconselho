# 🔍 Verificação de Ícones PWA

## Status Atual

Os ícones PWA estão localizados em `/pwa/icons/` e incluem:

- ✅ icon-192.png (192x192)
- ✅ icon-512.png (512x512)
- ✅ icon-192-maskable.png (192x192 com padding)
- ✅ icon-512-maskable.png (512x512 com padding)
- ✅ Outros tamanhos (72, 96, 128, 144, 152, 384)

## ⚠️ Ação Necessária

**Verificar se os ícones contêm o logo do CFC Bom Conselho.**

### Como Verificar

1. Abra qualquer ícone em um visualizador de imagens
2. Verifique se contém:
   - Logo "CFC Bom Conselho"
   - Cores da marca (verde, amarelo, vermelho)
   - Elementos visuais do logo

### Se os Ícones NÃO Contiverem o Logo

#### Opção 1: Gerar Novos Ícones (Recomendado)

1. Use o logo oficial do CFC (`assets/logo.png`)
2. Execute o script de geração:

```bash
cd pwa
php generate-icons.php
```

**Requisitos:**
- Ter um arquivo `icon-source.png` (512x512) com o logo do CFC
- PHP com extensão GD habilitada

#### Opção 2: Gerar Manualmente

Use uma ferramenta online como:
- [PWA Asset Generator](https://github.com/onderceylan/pwa-asset-generator)
- [RealFaviconGenerator](https://realfavicongenerator.net/)
- [PWA Builder](https://www.pwabuilder.com/imageGenerator)

**Configurações:**
- Tamanho fonte: 512x512
- Tamanhos gerados: 72, 96, 128, 144, 152, 192, 384, 512
- Maskable: 192 e 512 (com padding de 20%)

#### Opção 3: Usar Ferramenta de Design

1. Abra o logo do CFC em um editor (Photoshop, GIMP, Figma)
2. Crie um ícone 512x512 com o logo centralizado
3. Exporte em PNG
4. Use o script `generate-icons.php` para gerar todos os tamanhos

### Ícones Maskable

Os ícones maskable (com padding) são importantes para Android, pois permitem que o sistema adapte o ícone a diferentes formas (círculo, quadrado arredondado, etc.).

**Requisitos:**
- Conteúdo importante deve estar dentro de 80% do centro
- 10% de padding em cada lado
- Fundo pode ser transparente ou cor sólida

### Teste dos Ícones

Após gerar/atualizar os ícones:

1. Acesse: `https://cfcbomconselho.com.br/pwa/test-icons.html` (se existir)
2. Ou teste diretamente no manifest:
   - Abra DevTools (F12)
   - Vá para "Application" > "Manifest"
   - Verifique se os ícones aparecem corretamente

### Checklist de Validação

- [ ] Ícones contêm logo do CFC
- [ ] Ícones são legíveis em tamanho pequeno (192x192)
- [ ] Ícones maskable têm padding adequado
- [ ] Todos os arquivos estão acessíveis (sem 404)
- [ ] Manifest referencia corretamente os ícones
- [ ] Teste visual: ícones aparecem corretamente no app instalado

---

**Nota:** Os ícones atuais podem estar funcionando, mas é importante verificar se contêm o branding correto do CFC. Se não contiverem, siga as instruções acima para gerar novos ícones com o logo oficial.
