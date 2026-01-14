<?php

namespace App\Config;

class Constants
{
    // Papéis (Roles)
    const ROLE_ADMIN = 'ADMIN';
    const ROLE_SECRETARIA = 'SECRETARIA';
    const ROLE_INSTRUTOR = 'INSTRUTOR';
    const ROLE_ALUNO = 'ALUNO';

    // Status de aula
    const AULA_AGENDADA = 'agendada';
    const AULA_EM_ANDAMENTO = 'em_andamento';
    const AULA_CONCLUIDA = 'concluida';
    const AULA_CANCELADA = 'cancelada';
    const AULA_NO_SHOW = 'no_show';

    // Status financeiro
    const FIN_PENDENTE = 'pendente';
    const FIN_PAGO = 'pago';
    const FIN_VENCIDO = 'vencido';
    const FIN_CANCELADO = 'cancelado';

    // Níveis de notificação
    const NOTIF_CRITICA = 'critica';
    const NOTIF_IMPORTANTE = 'importante';
    const NOTIF_INFORMATIVA = 'informativa';

    // Configurações
    const DURACAO_AULA_PADRAO = 50; // minutos
    const MIN_AULAS_PRATICAS = 2;

    // CFC único (preparado para multi-CFC)
    const CFC_ID_DEFAULT = 1;
}
