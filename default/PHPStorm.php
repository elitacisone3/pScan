<?php

namespace pScan\plugins;

class PHPStorm extends _ProjectType
{

    public function getTypeName() {
        return 'PHPStorm';
    }

    public function getTypeSymbolSource() {
        return 'P0C';
    }

    public function getProjectType($path) {
        $path = fixPath($path);

        if (file_exists($path. 'build/android-profile')) return null;
        if (file_exists("{$path}.idea/workspace.xml")) return get_class($this);

        return null;
    }

    public function getPluginMetadata() {
        return [
            'desc'  =>  'Analizza i progetti di PHPStorm oppure altre IDE simili'
        ];
    }

    public function parseDirectory($dir, $projectData) {

        if (!$this->getProjectType($dir)) return null;
        $file = fixPath($dir).'.idea/workspace.xml';

        $projectId = is_array($projectData) ? (isset($projectData['id']) ? $projectData['id'] : 0) : 0;
        $upd = filemtime($file);
        $xml = file_get_contents($file);
        if ($xml === false) quit("Errore XML: $file");

        // Normalizza tutto il codice XML
        $data = simplify($xml);

        // Elimina i tag dal codice mammano che li estrae
        $task = betweenGetAndReplace($xml,'<component name="TaskManager">','</component>');
        if (!$task) quit("No task: $file");

        $interval = [];
        $totSum = 0;

        $tag = betweenGetAndReplace($data,'<changelist ','>');

        if ($tag) {
            $tag = " $tag";
            $tag = betweenGetAndReplace($tag,' id="','"');
        }

        if (!$tag) {
            while($x = betweenGetAndReplace($data,'<component ','/>')) {
                if (stripos($x,'name="ProjectId"') !== false) {
                    $tag = betweenGetAndReplace($x,'id="','"');
                    if ($tag) break;
                }
            }
        }

        $data = null;

        // Da qualsiasi task estrae e distrugge tutti i tag workItem
        while($dta = betweenGetAndReplace($task,'<workItem','/>')) {

            $orgDta=$dta;
            $from = betweenGetAndReplace($dta,'from="','"');
            $dur = betweenGetAndReplace($dta,'duration="','"');

            if (!$from or !$dur) {
                tty(5,0,'Attenzione: ');
                tty(4,0,"Errore intervallo tempo su: $file");
                echo "\n";
                tty(8,0," $orgDta");
                echo "\n\n";
                continue;
            }

            $timeStart = intval($from/1000);
            $timeLength = intval($dur/1000);

            $totSum+=$timeLength;

            $dayBaseTime = floor($timeStart / 86400) * 86400;
            $dayTCRStart = $timeStart - $dayBaseTime;
            $dayTCREnd = $dayTCRStart + $timeLength;

            if ($dayTCREnd < 86400) {

                $interval[] = [
                    'type'  =>  0,
                    'pid'   =>  $projectId,
                    'start' =>  $timeStart,
                    'len'   =>  $timeLength,
                    'time'  =>  $timeLength
                ];

                continue;
            }

            $dayMaxTime = 86400 - $dayTCRStart;

            $interval[] = [
                'type'  =>  1,
                'pid'   =>  $projectId,
                'start' =>  $timeStart,
                'len'   =>  $dayMaxTime,
                'time'  =>  $timeLength]
            ;

            $timeLength-=$dayMaxTime;
            $fixDays = 1;

            while($timeLength>0) {

                $isCont = $timeLength > 86400;
                $addTime = $isCont ? 86400 : $timeLength;

                $interval[] = [
                    'type'  =>  $isCont ? 2:3,
                    'pid'   =>  $projectId,
                    'start' =>  $dayBaseTime + $fixDays * 86400,
                    'len'   =>  $addTime,
                    'time'  =>  0]
                ;

                $timeLength-=$addTime;
                $fixDays++;

                if ($timeLength <0) {
                    tty(5,0,"Attenzione: ");
                    tty(4,0,"Errore dati lunghezza intervallo: $file");
                    echo "\n\n";
                }

            }

        }

        $projectData['type'] = get_class($this);

        return [
            'tag'       =>  $tag,
            'time'      =>  $upd,
            'file'      =>  $file,
            'totSum'    =>  $totSum,
            'projectId' =>  $projectId,
            'projData'  =>  $projectData,
            'data'      =>  $interval
        ];

    }

}