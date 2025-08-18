# ANÁLISE DETALHADA DO SISTEMA E-CONDUTOR CFC

## Informações Gerais
- **URL**: https://econdutorcfc.com/
- **Versão**: 1.82.01
- **Empresa**: Nova Sistemas
- **Data da Análise**: $(Get-Date)

## ESTRUTURA TÉCNICA

### Frontend Framework
- **Vue.js 2**: Framework principal para reatividade e componentes
- **Bootstrap 3**: Framework CSS para interface responsiva
- **jQuery**: Biblioteca JavaScript para manipulação DOM e AJAX
- **Pinia**: Gerenciamento de estado (store) para Vue.js

### Bibliotecas e Dependências

#### CSS e UI
- Font Awesome (versões 4, 5 e 6)
- Bootstrap Select
- Bootstrap Toggle
- Datepicker personalizado
- Alertify para notificações
- Animate.css (comentado)

#### JavaScript
- **Axios v0.27.2**: Cliente HTTP para requisições
- **Moment.js**: Manipulação de datas e localização
- **Lodash**: Utilitários JavaScript
- **Currency.js**: Formatação de moeda
- **Vue-jquery-mask**: Máscaras para inputs
- **jQuery Mask**: Máscaras para campos de entrada

### Componentes Vue.js Personalizados

#### 1. Currency Input
```javascript
Vue.component('currency-input', {
  props: ['value'],
  template: `<vue-mask mask="000.000.000.000.000,00" ...>`
});
```
- Máscara para valores monetários
- Formato brasileiro (vírgula como separador decimal)
- Validação de comprimento (1-13 caracteres)

#### 2. Date Input
```javascript
Vue.component('date-input', {
  props: ['id','value'],
  template: `<vue-mask mask="00/00/0000" ...>`
});
```
- Máscara para datas no formato dd/mm/aaaa
- Validação de padrão regex
- Placeholder personalizado

### Store Global (Pinia)
```javascript
const useGlobalStore = Pinia.defineStore('globalStore', {
  state: () => ({ usuarioLogado: null }),
  actions: {
    getUserProfile(),
    isPDF(),
    isIMG(),
    exibirAlert(),
    searchCep()
  }
});
```

**Funcionalidades do Store:**
- Gerenciamento de perfil do usuário
- Validação de tipos de arquivo (PDF, imagens)
- Sistema de alertas personalizados
- Consulta de CEP via API ViaCEP

## SEGURANÇA E AUTENTICAÇÃO

### Recaptcha v3
- Implementado para proteção contra bots
- Ativado após 3 tentativas de login
- Site key: `6LcY620rAAAAAAOqRRazHRhGnrgMU5pz6O14yq-L`

### Controle de Sessão
- Sistema de expiração de sessão
- Modal de aviso quando sessão expira
- Redirecionamento automático para login

### Login Social
- **Facebook Login**: Integração com Facebook SDK
- App ID: `176576092753646`
- Escopo: email
- Autenticação via OAuth

### Controle de Tentativas
- API endpoint: `/api/v2/auth/login?ip={ip}`
- Limite de 3 tentativas antes de ativar captcha
- Detecção de IP para controle de segurança

## ARQUITETURA DE REQUISIÇÕES

### Base URL
- Configurável via cookie `baseURL`
- Headers de autorização com Bearer token
- Interceptor Axios para autenticação

### Endpoints Identificados
- `/auth/profile` - Perfil do usuário
- `/acessar` - Autenticação de login
- `/api/v2/auth/login` - Validação de tentativas

## INTERFACE E UX

### Design Responsivo
- Bootstrap 3 com breakpoints para mobile
- Navbar fixa no topo
- Layout adaptativo para diferentes tamanhos de tela

### Componentes de Interface
- **Navbar**: Logo e botão de acesso CFC
- **Formulário de Login**: Email e senha com validação
- **Modais de Loading**: Indicadores visuais de carregamento
- **Sistema de Notificações**: Alertify para mensagens

### Validações de Formulário
- Email: Regex para validação de formato
- Senha: Campo obrigatório
- Conversão automática de email para minúsculas

## INTEGRAÇÕES EXTERNAS

### APIs de Terceiros
- **Google Analytics**: UA-67821843-1
- **Google AdWords**: AW-877939079
- **ViaCEP**: Consulta de endereços por CEP
- **IPify**: Detecção de IP do usuário

### Ferramentas de Suporte
- **Chatvolt**: Chat de suporte integrado
- **Tidio**: Chat de atendimento (comentado)
- **Hotjar**: Analytics de comportamento (comentado)

## ESTRUTURA DE ARQUIVOS

### Recursos Estáticos
```
/recursos/
├── css/
│   ├── bootstrap.min.css
│   ├── estilo.css
│   ├── bootstrap-select.css
│   └── bootstrap-toggle.min.css
├── js/
│   ├── vue.min.js
│   ├── axios.min.js
│   ├── jquery.min.js
│   ├── moment-with-locales.js
│   └── custom-exceptions.js
├── img/
│   ├── logo.png
│   ├── logo-original.png
│   ├── favicon.ico
│   └── loading.gif
└── font-awesome/
    └── css/
        ├── all.min.css
        ├── v4-shims.min.css
        └── v5-font-face.min.css
```

## FUNCIONALIDADES IDENTIFICADAS

### Sistema de Login
- Autenticação por email/senha
- Login social via Facebook
- Recuperação de senha
- Controle de tentativas de acesso

### Gestão de Usuários
- Perfil de usuário
- Controle de sessão
- Detecção de plataforma e IP

### Interface Administrativa
- Acesso CFC (botão específico)
- Sistema de notificações
- Modais de carregamento

## OBSERVAÇÕES TÉCNICAS

### Performance
- Carregamento assíncrono de scripts
- Lazy loading de recursos externos
- Minificação de arquivos CSS/JS

### Compatibilidade
- Suporte a navegadores modernos
- Fallbacks para funcionalidades não suportadas
- Responsividade mobile-first

### Manutenibilidade
- Código modular com componentes Vue
- Separação clara de responsabilidades
- Sistema de versionamento de recursos

## RECOMENDAÇÕES

### Segurança
- Implementar rate limiting mais robusto
- Adicionar validação de força de senha
- Considerar autenticação de dois fatores

### Performance
- Implementar lazy loading de componentes
- Otimizar carregamento de fontes
- Considerar service worker para cache

### UX
- Melhorar feedback visual de validações
- Implementar autocomplete para campos
- Adicionar modo escuro

## CONCLUSÃO

O sistema e-condutor CFC é uma aplicação web moderna e bem estruturada, utilizando tecnologias atuais como Vue.js 2, Bootstrap 3 e Pinia. A arquitetura é robusta com separação clara de responsabilidades, sistema de autenticação seguro e interface responsiva. O código demonstra boas práticas de desenvolvimento web com componentes reutilizáveis e gerenciamento de estado centralizado.
