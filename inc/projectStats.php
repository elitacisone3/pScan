<?php

function computeProjectWeeklyStats($intervals) {

    $out = [];

    foreach ($intervals as $interval) {

        $pid = isset($interval['pid']) ? $interval['pid'] : 0;
        $dayId = floor($interval['start'] / 86400);
        list($sid) = weekBucketOfDayId($dayId);

        if (!isset($out[$sid])) $out[$sid] = [];
        if (!isset($out[$sid][$pid])) $out[$sid][$pid] = 0;

        $out[$sid][$pid] += $interval['len'];

    }

    return $out;

}

function buildProjectsExport($added) {

    $out = [];

    foreach ($added as $item) {

        if (!is_array($item) or !isset($item['id'])) continue;

        $pid = $item['id'];

        $typeInfo = isset($item['type']) ? getProjectTypeInfoByClass($item['type']) : false;
        $typeName = $typeInfo ? $typeInfo['name'] : ($item['type'] ?? '');

        $char = null;
        $inkIndex = 0;
        $paperIndex = 0;

        $source = getProjectSymbolSource($item['name'] ?? '');
        if ($source) {
            list($sChar, $sColor) = decodeSymbol($source);
            $char = $sChar;
            $inkIndex = $sColor & 15;
            $paperIndex = ($sColor >> 4) & 15;
        }

        $out[$pid] = [
            'id'        =>  $pid,
            'name'      =>  $item['name'] ?? '',
            'title'     =>  $item['title'] ?? ($item['name'] ?? ''),
            'isGen'     =>  $item['isGen'] ?? false,
            'typeClass' =>  $item['type'] ?? '',
            'typeName'  =>  $typeName,
            'symbol'    =>  [
                'char'          =>  $char,
                'inkIndex'      =>  $inkIndex,
                'paperIndex'    =>  $paperIndex
            ]
        ];

    }

    return $out;

}
