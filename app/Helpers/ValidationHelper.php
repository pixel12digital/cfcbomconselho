<?php

namespace App\Helpers;

class ValidationHelper
{
    /**
     * Valida CPF brasileiro
     * @param string $cpf CPF (com ou sem formatação)
     * @return bool
     */
    public static function validateCpf($cpf)
    {
        // Remove formatação
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        
        // Verifica se tem 11 dígitos
        if (strlen($cpf) != 11) {
            return false;
        }
        
        // Verifica se todos os dígitos são iguais (CPF inválido)
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }
        
        // Validação dos dígitos verificadores
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Valida formato de email
     * @param string $email
     * @return bool
     */
    public static function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Valida CEP brasileiro
     * @param string $cep CEP (com ou sem formatação)
     * @return bool
     */
    public static function validateCep($cep)
    {
        $cep = preg_replace('/[^0-9]/', '', $cep);
        return strlen($cep) === 8 && preg_match('/^[0-9]{8}$/', $cep);
    }
    
    /**
     * Valida UF (2 letras maiúsculas)
     * @param string $uf
     * @return bool
     */
    public static function validateUf($uf)
    {
        $validUfs = [
            'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO',
            'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI',
            'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'
        ];
        return in_array(strtoupper($uf), $validUfs);
    }
    
    /**
     * Valida data (formato Y-m-d)
     * @param string $date
     * @return bool
     */
    public static function validateDate($date)
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
    
    /**
     * Valida se data é plausível (não futura, não muito antiga)
     * @param string $date Data no formato Y-m-d
     * @param int $minAge Idade mínima (anos)
     * @param int $maxAge Idade máxima (anos)
     * @return bool
     */
    public static function validateBirthDate($date, $minAge = 16, $maxAge = 120)
    {
        if (!self::validateDate($date)) {
            return false;
        }
        
        $birthDate = new \DateTime($date);
        $today = new \DateTime();
        $age = $today->diff($birthDate)->y;
        
        return $age >= $minAge && $age <= $maxAge;
    }
    
    /**
     * Formata CPF
     * @param string $cpf
     * @return string
     */
    public static function formatCpf($cpf)
    {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        if (strlen($cpf) === 11) {
            return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
        }
        return $cpf;
    }
    
    /**
     * Formata CEP
     * @param string $cep
     * @return string
     */
    public static function formatCep($cep)
    {
        $cep = preg_replace('/[^0-9]/', '', $cep);
        if (strlen($cep) === 8) {
            return substr($cep, 0, 5) . '-' . substr($cep, 5, 3);
        }
        return $cep;
    }
    
    /**
     * Formata telefone
     * @param string $phone
     * @return string
     */
    public static function formatPhone($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) === 10) {
            return '(' . substr($phone, 0, 2) . ') ' . substr($phone, 2, 4) . '-' . substr($phone, 6, 4);
        } elseif (strlen($phone) === 11) {
            return '(' . substr($phone, 0, 2) . ') ' . substr($phone, 2, 5) . '-' . substr($phone, 7, 4);
        }
        return $phone;
    }
}
