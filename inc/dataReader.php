<?php

function crawlerPath($path) {
    $out = [];
    $path = fixPath($path);
    if (!file_exists($path) or !is_dir($path)) quit("Errore percorso: $path");
    $dh = opendir($path);
    if (!$dh) quit("Errore accesso: $path");
    while ($file = readdir($dh)) {

        if ($file[0] == '.') continue;
        $dir = $path . $file;
        if (!is_dir($dir)) continue;

        $type = pluginCall('getProjectType',false,$dir);
        if ($type === null) continue;
        $out[] = $dir;

    }

    closedir($dh);

    return $out;
}

function getProjectTypeByPath($path) {
    $path = fixPath($path);
    if (!file_exists($path) or !is_dir($path)) quit("Errore percorso: $path");
    $type = pluginCall('getProjectType',false,$path);
    if ($type === null) return false;
    return $type;
}

function getIntervalsFrom($file, $projectData) {
    global $OPT;

    if (!is_array($projectData)) $projectData = [];

    $out = pluginCall('parseDirectory',false, getProjectPath($file), $projectData);
    if (!is_array($out)) quit("Tipo di progetto non riconosciuto: $file");

    if (isset($OPT['Q'])) {
        $out['data'] = getQuantizedIntervals($out['data'],$out['projectId'],$out['totSum'],5);
    }

    return $out;
}

function getQuantizedIntervals($intervals, $projectId, &$totSum, $type) {

    $timeBuffer = [];
    foreach ($intervals as $interval) {

        $dayId = floor($interval['start'] / 86400);
        $dayBaseTime = $dayId * 86400;
        $tcrStart = intval(($interval['start'] - $dayBaseTime) / 3600);
        $tcrLen = intval($interval['len'] / 3600);
        $time = $dayBaseTime + ($tcrStart * 3600);

        for ($i = 0; $i < $tcrLen; $i++) {

            $dayId = floor($time / 86400);
            $hourId = floor(($time % 86400) / 3600);

            if (!isset($timeBuffer[$dayId])) $timeBuffer[$dayId] = [];
            $timeBuffer[$dayId][$hourId] = true;

            $time += 3600;
        }

    }

    return rebuildIntervals($timeBuffer,$projectId,$totSum, $type);

}

function rebuildIntervals($timeBuffer, $projectId, &$totSum, $type) {
    ksort($timeBuffer);
    $intervals = [];
    $totSum = 0;

    foreach ($timeBuffer as $dayId => $hourArray) {

        $dayIntervals = splitDayIntervals($hourArray);
        $dayTimeBase = $dayId * 86400;

        foreach ($dayIntervals as $dayInterval) {

            $timeStart = $dayTimeBase + ($dayInterval[0] * 3600);
            $timeLength = $dayInterval[1] * 3600;
            $totSum+= $timeLength;

            $intervals[] = [
                'type'  =>  $type,
                'pid'   =>  $projectId,
                'start' =>  $timeStart,
                'len'   =>  $timeLength,
                'time'  =>  $timeLength
            ];

        }

    }

    return $intervals;

}

function splitDayIntervals($timeSlotArray) {

    $o = [];
    ksort($timeSlotArray);
    $isFirst = true;
    $hourStart = -1;
    $intLen = 0;

    foreach ($timeSlotArray as $slotId => $true) {

        if ($isFirst) {
            $isFirst = false;
            $hourStart = $slotId;
            $intLen = 1;
            $pred = $hourStart+1;
            continue;
        }

        if ($slotId == $pred) {

            $intLen++;
            $pred++;

        } else {

            $o[] = [$hourStart , $intLen ];
            $hourStart = $slotId;
            $intLen = 1;
            $pred = $slotId+1;

        }

    }

    if (!$isFirst) $o[] = [$hourStart, $intLen];
    return $o;

}
