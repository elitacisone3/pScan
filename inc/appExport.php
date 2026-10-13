<?php

function buildMonthlyExport($byMonth) {

    $out = [];

    foreach ($byMonth as $monthId => $item) {

        $days = [];

        for ($d = 1; $d <= $item['len']; $d++) {

            $pids = [];
            if (isset($item['pidMap'][$d]) and is_array($item['pidMap'][$d])) {
                foreach ($item['pidMap'][$d] as $pid) $pids[] = $pid;
            }

            $days[] = [
                'd'     =>  $d,
                'fest'  =>  !empty($item['fest'][$d]),
                'pids'  =>  $pids
            ];

        }

        $legend = [];
        if (!empty($item['pidMapX'])) {
            foreach ($item['pidMapX'] as $pid => $hours) {
                $legend[(string)$pid] = intval($hours);
            }
        }

        $out[(string)$monthId] = [
            'year'      =>  $item['year'],
            'month'     =>  $item['month'],
            'len'       =>  $item['len'],
            'min'       =>  $item['min'],
            'max'       =>  $item['max'],
            'liv'       =>  $item['heatD'][5],
            'numDays'   =>  $item['dTot'],
            'hours'     =>  $item['hours'],
            'tFes'      =>  round($item['tFes'] / 3600, 1),
            'days'      =>  $days,
            'legend'    =>  $legend
        ];

    }

    return $out;

}

function buildDailyExport($days) {

    $out = [];

    foreach ($days as $dayId => $day) {

        $hours = [];

        for ($h = 0; $h < 24; $h++) {

            $pids = [];
            if (isset($day['pidMap'][$h]) and is_array($day['pidMap'][$h])) {
                foreach ($day['pidMap'][$h] as $pid) $pids[] = $pid;
            }

            $hours[] = [
                'h'     =>  $h,
                'pids'  =>  $pids
            ];

        }

        $legend = [];
        if (!empty($day['pidMapX'])) {
            foreach ($day['pidMapX'] as $pid => $cnt) {
                $legend[(string)$pid] = intval($cnt);
            }
        }

        $out[(string)$dayId] = [
            'date'      =>  $day['date'],
            'wDay'      =>  $day['wDay'],
            'fest'      =>  !empty($day['fest']),
            'cont'      =>  $day['cont'],
            'tot'       =>  $day['tot'],
            'totSum'    =>  $day['totSum'],
            'hours'     =>  $hours,
            'legend'    =>  $legend
        ];

    }

    return $out;

}
