<?php

namespace App\Models;

use App\Config\Constants;

class Cfc extends Model
{
    protected $table = 'cfcs';

    /**
     * Busca o CFC atual (da sessão ou padrão)
     */
    public function getCurrent()
    {
        $cfcId = $_SESSION['cfc_id'] ?? Constants::CFC_ID_DEFAULT;
        return $this->find($cfcId);
    }

    /**
     * Busca o nome do CFC atual
     */
    public function getCurrentName()
    {
        $cfc = $this->getCurrent();
        return $cfc['nome'] ?? 'CFC Sistema';
    }

    /**
     * Busca o logo do CFC atual (se existir)
     */
    public function getCurrentLogo()
    {
        $cfc = $this->getCurrent();
        
        // Verificar se existe campo logo_path
        if (isset($cfc['logo_path']) && !empty($cfc['logo_path'])) {
            return $cfc['logo_path'];
        }
        
        // Fallback: verificar campo logo (se existir)
        if (isset($cfc['logo']) && !empty($cfc['logo'])) {
            return $cfc['logo'];
        }
        
        return null;
    }

    /**
     * Verifica se o CFC tem logo cadastrado
     */
    public function hasLogo()
    {
        return $this->getCurrentLogo() !== null;
    }
}
