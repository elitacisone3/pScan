<?php

$MON_WEEKLY = ['__','GE','FE','MA','AP','MR','GI','LU','AG','SE','OT','NO','DI'];

function weekBucketOfDayId($dayId) {
    global $MON_WEEKLY;

    $tcr = $dayId * 86400;
    $year = intval(gmdate('Y',$tcr));
    $month = intval(gmdate('m',$tcr));
    $week = intval(gmdate('W',$tcr));
    $sid = 'W' . $year . $MON_WEEKLY[$month] . str_pad($week,2,'0',STR_PAD_LEFT);

    return [$sid, $year, $month, $week];
}

function computeWeeklyAggregation($data) {

    $week = [];

    foreach ($data['days'] as $dayId => $day) {

        list($sid, $year, $month, $w) = weekBucketOfDayId($dayId);

        if (!isset($week[$sid])) {
            $week[$sid] = [
                'date'  =>  $day['date'],
                'year'  =>  $year,
                'month' =>  $month,
                'week'  =>  $w,
                'totD'  =>  0,
                'totN'  =>  0,
                'totF'  =>  0,
                'totP'  =>  0
            ];
        }

        $week[$sid]['totD'] += $day['tot'];
        if ($day['fest']) $week[$sid]['totF'] += $day['tot'];

        for ($h = 0; $h < 24; $h++) {
            if ($day['map'][$h] > 0) {
                if ($h > 0 and $h < 8) $week[$sid]['totN']++;
                elseif ($h > 16) $week[$sid]['totP']++;
            }
        }

    }

    $quota = [
        'totD'  =>  getConfig('weekly.totD', 56),
        'totN'  =>  getConfig('weekly.totN', 14),
        'totP'  =>  getConfig('weekly.totP', 14),
        'totF'  =>  getConfig('weekly.totF', 6)
    ];

    foreach ($week as &$item) {
        foreach (['totD','totN','totF','totP'] as $key) {
            $max = $quota[$key];
            $item["{$key}P"] = $max > 0 ? round(100.0 * $item[$key] / $max, 1) : 0;
        }
    }
    unset($item);

    return $week;

}

function writeOutSimple($weekData, $file) {

    list($isCSV, $isBin, $isPack) = sniffOutExt($file);

    if ($isCSV) {

        $out = [];
        $fields = null;

        foreach ($weekData as $item) {
            if (!$fields) {
                $fields = array_keys($item);
                $out[] = implode(',',$fields);
            }
            $out[] = implode(',',$item);
        }

        $raw = implode("\r\n",$out);

    } else {

        $raw = json_encode($weekData, JSON_PRETTY_PRINT);

    }

    if ($isPack) $raw = gzencode($raw);

    file_put_contents($file, $raw);

}
