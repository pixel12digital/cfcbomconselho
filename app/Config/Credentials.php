<?php

namespace App\Config;

class Credentials
{
    /**
     * Credenciais padrão do sistema (após instalação)
     * 
     * ⚠️ IMPORTANTE: Alterar a senha após o primeiro login!
     * 
     * Para alterar a senha do admin:
     * - Acesse o sistema com as credenciais abaixo
     * - Vá em Perfil/Configurações (quando implementado)
     * - Ou altere diretamente no banco de dados
     */
    
    // Credenciais do usuário administrador padrão
    const DEFAULT_ADMIN_EMAIL = 'admin@cfc.local';
    const DEFAULT_ADMIN_PASSWORD = 'admin123'; // Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
    
    /**
     * Nota: A senha acima está hashada no banco de dados usando bcrypt.
     * O hash correspondente à senha 'admin123' está no seed do banco.
     * 
     * Para gerar novo hash de senha:
     * password_hash('nova_senha', PASSWORD_BCRYPT)
     */
}
