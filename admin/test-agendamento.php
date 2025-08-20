<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste do Sistema de Agendamento - Sistema CFC</title>
    
    <!-- CSS do sistema -->
    <link href="assets/css/admin.css" rel="stylesheet">
    
    <!-- FullCalendar CSS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
    
    <!-- Componentes JavaScript -->
    <script src="assets/js/components.js"></script>
    
    <!-- FullCalendar JS -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    
    <!-- JavaScript específico do agendamento -->
    <script src="assets/js/agendamento.js"></script>
</head>
<body>
    <div class="container-fluid p-4">
        <h1 class="mb-4">🧪 Teste do Sistema de Agendamento</h1>
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Teste das Funcionalidades</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>✅ Funcionalidades Implementadas:</h6>
                                <ul>
                                    <li>Interface de agendamento completa</li>
                                    <li>Calendário interativo (FullCalendar)</li>
                                    <li>Modais de criação e edição</li>
                                    <li>Sistema de filtros</li>
                                    <li>Estatísticas em tempo real</li>
                                    <li>Validações de formulário</li>
                                    <li>Responsividade mobile-first</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>🚧 Funcionalidades Pendentes:</h6>
                                <ul>
                                    <li>✅ APIs de backend - <strong>IMPLEMENTADAS</strong></li>
                                    <li>✅ Verificação de disponibilidade - <strong>IMPLEMENTADA</strong></li>
                                    <li>✅ Persistência de dados - <strong>IMPLEMENTADA</strong></li>
                                    <li>🔄 Notificações automáticas - <strong>EM DESENVOLVIMENTO</strong></li>
                                </ul>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="text-center">
                            <a href="index.php?page=agendamento" class="btn btn-primary btn-lg me-2">
                                <i class="fas fa-calendar-alt"></i>
                                Acessar Sistema de Agendamento
                            </a>
                            <a href="teste-agendamento-completo.php" class="btn btn-success btn-lg me-2">
                                🧪 Teste Completo do Sistema
                            </a>
                            <a href="inserir-dados-agendamento.php" class="btn btn-info btn-lg">
                                📋 Inserir Dados de Teste
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>📋 Como Testar</h5>
                    </div>
                    <div class="card-body">
                        <ol>
                            <li><strong>Acesse o Sistema:</strong> Clique no botão acima ou navegue para "Agendamento" no menu</li>
                            <li><strong>Teste o Calendário:</strong> Navegue entre meses, semanas e dias</li>
                            <li><strong>Crie uma Nova Aula:</strong> Clique em "Nova Aula" e preencha o formulário</li>
                            <li><strong>Teste os Filtros:</strong> Use os filtros para filtrar aulas por instrutor, tipo, etc.</li>
                            <li><strong>Teste a Responsividade:</strong> Redimensione a janela ou use o DevTools mobile</li>
                            <li><strong>Teste os Modais:</strong> Abra e feche os modais, teste o ESC e clique fora</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>🔧 Dependências</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Bibliotecas Externas:</h6>
                                <ul>
                                    <li><strong>FullCalendar 6.1.10:</strong> Calendário interativo</li>
                                    <li><strong>Font Awesome:</strong> Ícones</li>
                                    <li><strong>Google Fonts:</strong> Tipografia</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>Componentes Internos:</h6>
                                <ul>
                                    <li><strong>NotificationSystem:</strong> Sistema de notificações</li>
                                    <li><strong>ModalSystem:</strong> Sistema de modais</li>
                                    <li><strong>LoadingSystem:</strong> Sistema de loading</li>
                                    <li><strong>FormValidator:</strong> Validação de formulários</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Teste das funcionalidades básicas
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🧪 Página de teste carregada');
            
            // Verificar se os sistemas estão funcionando
            const sistemas = {
                notifications: typeof notifications !== 'undefined',
                modals: typeof modals !== 'undefined',
                loading: typeof loading !== 'undefined',
                formValidator: typeof formValidator !== 'undefined',
                FullCalendar: typeof FullCalendar !== 'undefined'
            };
            
            console.log('📊 Status dos sistemas:', sistemas);
            
            // Mostrar notificação de teste
            if (sistemas.notifications) {
                setTimeout(() => {
                    notifications.success('✅ Sistema de agendamento carregado com sucesso!');
                }, 1000);
            }
        });
    </script>
</body>
</html>
