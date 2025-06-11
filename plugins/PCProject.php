<?php

namespace pScan\plugins;

class PCProject extends _ProjectType
{

    public function getTypeName() {
        return 'PCProject';
    }

    public function getTypeSymbolSource() {
        return 'P0F';
    }

    public function getPluginMetadata() {
        return [
            'desc'  =>  'Importa monitoraggi PCCheck I/O Test V1.4.1'
        ];

    }

    public function getProjectType($path) {
        $path = fixPath($path);

        $try = [
            'projMgr.bin',
            'maxProjH',
            'projMgr',
            'projMgr.log'
        ];

        foreach ($try as $item) {
            if (!file_exists($path. $item)) return null;
        }

        return get_class($this);

    }

    public function parseDirectory($dir, $projectData) {

        $projectId = is_array($projectData) ? (isset($projectData['id']) ? $projectData['id'] : 0) : 0;
        $lastUpd = 0;

        $dir = fixPath($dir);
        if (!file_exists($dir) or !is_dir($dir)) quit("Errore percorso: $dir");

        if (!$this->getProjectType($dir)) return null;

        $data = file($dir . 'projMgr', FILE_IGNORE_NEW_LINES);
        if (!$data) quit("Errore file in $dir");

        $ver = $data[0];
        if (
            count($ver) < 4 or
            !str_starts_with($ver,'PCCheck@') or
            !str_contains($ver,'/projMgr/')
        ) {
            quit("Errore tipo di progetto: $dir");
        }

        $pathId = $ver[2];
        if (!$pathId) quit("Errore pathId: $dir");

        $interval = [];
        $totSum = 0;

        $list = file($dir . 'projMgr.log' , FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($list === false) quit("Nessun dato su: $dir");

        foreach ($list as $line) {

            $line = preg_replace('/\\s+/',' ',$line);
            $line = trim($line);

            if ($line == '') continue;

            if (!preg_match(
                '/^(?<m>[0-9]{2})\\/(?<d>[0-9]{2})\\/(?<y>[0-9]{4})\\s(?<h>[0-9]{2}):(?<i>[0-9]{2}):(?<s>[0-9]{2})\\s*,\\s*(?<l>[0-9]+)$/',
                $line,
                $match)
            ) {
                continue;
            }

            $tcrStart = mktime($match['h'],$match['i'],$match['s'],$match['m'],$match['d'],$match['y']);
            $timeLength = intval($match['l']);
            $totSum+=$timeLength;
            $lastUpd = max($lastUpd,$tcrStart + $timeLength);

            $interval[] = [
                'type'  =>  1,
                'pid'   =>  $projectId,
                'start' =>  $tcrStart,
                'len'   =>  $timeLength,
                'time'  =>  $timeLength
            ];
        }

        $projectData['type'] = get_class($this);

        return [
            'tag'       =>  $pathId,
            'time'      =>  $lastUpd,
            'file'      =>  "{$dir}projMgr",
            'totSum'    =>  $totSum,
            'projectId' =>  $projectId,
            'projData'  =>  $projectData,
            'data'      =>  $interval
        ];

    }
    
}