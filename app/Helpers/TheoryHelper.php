<?php

namespace App\Helpers;

class TheoryHelper
{
    /**
     * Formata carga horária teórica como "X aulas (Y min)"
     * 
     * @param int|null $totalMinutes Total de minutos
     * @param int|null $lessonsCount Quantidade de aulas (se já existir)
     * @param int|null $lessonMinutes Minutos por aula (padrão 50)
     * @param bool $showHoursLiteral Se true, adiciona formato literal (ex: 8h20)
     * @return string Ex: "3 aulas (150 min)" ou "3 aulas (150 min) (2h30)"
     */
    public static function formatTheoryWorkload($totalMinutes, $lessonsCount = null, $lessonMinutes = null, $showHoursLiteral = false)
    {
        if (empty($totalMinutes) || $totalMinutes <= 0) {
            return '<span class="text-muted">-</span>';
        }

        // Determinar minutos por aula
        $lessonMins = $lessonMinutes ?? 50;
        
        // Determinar quantidade de aulas
        if ($lessonsCount !== null && $lessonsCount > 0) {
            $lessons = $lessonsCount;
        } else {
            // Inferir: ceil(total / lesson_minutes)
            $lessons = (int)ceil($totalMinutes / $lessonMins);
        }

        // Formato principal: "X aulas (Y min)"
        $result = "{$lessons} " . ($lessons === 1 ? 'aula' : 'aulas') . " ({$totalMinutes} min)";

        // Opcional: adicionar formato literal em horas
        if ($showHoursLiteral && $totalMinutes >= 60) {
            $hours = (int)floor($totalMinutes / 60);
            $minutes = $totalMinutes % 60;
            
            if ($minutes > 0) {
                $result .= " ({$hours}h" . str_pad($minutes, 2, '0', STR_PAD_LEFT) . ")";
            } else {
                $result .= " ({$hours}h)";
            }
        }

        return $result;
    }
}
