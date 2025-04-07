// app/services/CalendarService.php
<?php
class CalendarService {
    /**
     * Prepara os dados para exibição do calendário
     * 
     * @param int $month Mês (1-12)
     * @param int $year Ano
     * @param array $compromissos Lista de compromissos
     * @return array Dados formatados para o calendário
     */
    public function prepareCalendarData($month, $year, $compromissos) {
        // Primeiro dia do mês
        $firstDay = new DateTime("$year-$month-01");
        
        // Último dia do mês
        $lastDay = new DateTime("$year-$month-" . $firstDay->format('t'));
        
        // Dia da semana do primeiro dia (0 = Domingo, 6 = Sábado)
        $firstDayOfWeek = $firstDay->format('w');
        
        // Total de dias no mês
        $totalDays = $lastDay->format('j');
        
        // Preparar array de semanas e dias
        $weeks = [];
        $day = 1;
        $currentWeek = 0;
        
        // Inicializar a primeira semana com dias vazios
        $weeks[$currentWeek] = array_fill(0, 7, ['day' => null, 'compromissos' => []]);
        
        // Preencher com os dias do mês anterior se necessário
        for ($i = 0; $i < $firstDayOfWeek; $i++) {
            $weeks[$currentWeek][$i] = ['day' => null, 'compromissos' => []];
        }
        
        // Preencher os dias do mês
        for ($i = $firstDayOfWeek; $i < 7; $i++) {
            if ($day <= $totalDays) {
                $dateStr = sprintf("%04d-%02d-%02d", $year, $month, $day);
                $weeks[$currentWeek][$i] = ['day' => $day, 'compromissos' => []];
                $day++;
            }
        }
        
        // Continuar com as próximas semanas
        while ($day <= $totalDays) {
            $currentWeek++;
            $weeks[$currentWeek] = array_fill(0, 7, ['day' => null, 'compromissos' => []]);
            
            for ($i = 0; $i < 7; $i++) {
                if ($day <= $totalDays) {
                    $dateStr = sprintf("%04d-%02d-%02d", $year, $month, $day);
                    $weeks[$currentWeek][$i] = ['day' => $day, 'compromissos' => []];
                    $day++;
                }
            }
        }
        
        // Adicionar compromissos ao calendário
        $this->addCompromissosToCalendar($weeks, $compromissos, $month, $year, $firstDay, $lastDay);
        
        // Mapeamento dos nomes dos meses para português
        $monthNames = [
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março',
            4 => 'Abril', 5 => 'Maio', 6 => 'Junho',
            7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro',
            10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
        ];
        
        // Retornar dados para o calendário
        return [
            'month' => $month,
            'year' => $year,
            'weeks' => $weeks,
            'monthName' => $monthNames[$month] ?? $firstDay->format('F'),
            'previousMonth' => $month == 1 ? 12 : $month - 1,
            'previousYear' => $month == 1 ? $year - 1 : $year,
            'nextMonth' => $month == 12 ? 1 : $month + 1,
            'nextYear' => $month == 12 ? $year + 1 : $year
        ];
    }
    
    /**
     * Adiciona compromissos aos dias do calendário
     * Método privado auxiliar para prepareCalendarData
     */
    private function addCompromissosToCalendar(&$weeks, $compromissos, $month, $year, $firstDay, $lastDay) {
        foreach ($compromissos as $compromisso) {
            $startDate = new DateTime($compromisso['start_datetime']);
            $endDate = new DateTime($compromisso['end_datetime']);
            
            // Verificar se é o mês atual
            if ($startDate->format('Y-m') != "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) &&
                $endDate->format('Y-m') != "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT)) {
                continue;
            }
            
            // Se o compromisso começar antes do mês atual, ajustar para o primeiro dia
            if ($startDate->format('Y-m') != "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT)) {
                $startDate = new DateTime("$year-$month-01");
            }
            
            // Se o compromisso terminar depois do mês atual, ajustar para o último dia
            if ($endDate->format('Y-m') != "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT)) {
                $endDate = $lastDay;
            }
            
            // Percorrer todos os dias do compromisso
            $currentDate = clone $startDate;
            while ($currentDate <= $endDate) {
                if ($currentDate->format('Y-m') == "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT)) {
                    $day = $currentDate->format('j');
                    
                    // Encontrar a semana e dia correspondente
                    foreach ($weeks as $weekIndex => $week) {
                        foreach ($week as $dayIndex => $dayData) {
                            if ($dayData['day'] == $day) {
                                $weeks[$weekIndex][$dayIndex]['compromissos'][] = $compromisso;
                            }
                        }
                    }
                }
                $currentDate->modify('+1 day');
            }
        }
    }
}