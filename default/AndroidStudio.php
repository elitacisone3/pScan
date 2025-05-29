<?php

namespace pScan\plugins;

class AndroidStudio extends _ProjectType
{

    public function getTypeName() {
        return 'Android';
    }

    public function getTypeSymbolSource() {
        return 'A03';
    }

    public function getProjectType($path) {
        $path = fixPath($path);
        if (!file_exists($path. 'build/android-profile')) return null;
        if (!file_exists($path. '.idea/workspace.xml')) return null;
        return get_class($this);
    }

    public function getPluginMetadata() {
        return [
            'desc'  =>  'Analizza i progetti di Android Studio'
        ];
    }

    public function parseDirectory($dir, $projectData)
    {

        if (!$this->getProjectType($dir)) return null;
        $dir = fixPath($dir);
        $file = $dir . '.idea/workspace.xml';

        $projectId = is_array($projectData) ? (isset($projectData['id']) ? $projectData['id'] : 0) : 0;

        $upd = filemtime($file);
        $xml = file_get_contents($file);
        if ($xml === false) quit("Errore file XML: $file");

        $data = simplify($xml);
        $task = betweenGetAndReplace($xml,'<component name="TaskManager">','</component>');
        if (!$task) quit("No task: $file");

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
        $xml = null;

        $path = $dir . 'build/android-profile/*.json';

        $list = glob($path);

        $count = [0,0,0,0];
        $timeBuffer = [];

        foreach ($list as $jsonFile) {
            $jsonFile = basename($jsonFile);

            if (
                preg_match(
                    '/^profile\\-(?<y>[0-9]{4})\\-(?<m>[0-9]{2})\\-(?<d>[0-9]{2})\\-(?<h>[0-9]{2})-(?<i>[0-9]{2})-(?<s>[0-9]{2})\\-[0-9]+\\.json$/',
                    $jsonFile,
                    $match)
            ) {

                $timeStamp = gmmktime($match['h'],$match['i'],$match['s'],$match['m'],$match['d'],$match['y']);
                $dayId = floor($timeStamp / 86400);
                $hourId = floor(($timeStamp % 86400) / 3600);

                if (!isset($timeBuffer[$dayId])) $timeBuffer[$dayId] = [];

                if (isset($timeBuffer[$dayId][$hourId])) {
                    $count[2]++;
                } else {
                    $count[3]++;
                    $timeBuffer[$dayId][$hourId] = true;
                }

                $count[1]++;
            } else {
                $count[0]++;
            }
        }

        ksort($timeBuffer);

        $interval = rebuildIntervals($timeBuffer, $projectId,$totSum,6);
        $timeBuffer = null;

        $projectData['cells'] = [
            'skipped'   =>  $count[0],
            'counted'   =>  $count[1],
            'override'  =>  $count[2],
            'total'     =>  $count[3]
        ];

        $projectData['type'] = get_class($this);

        $out = [
            'tag'       =>  $tag,
            'time'      =>  $upd,
            'file'      =>  $file,
            'totSum'    =>  $totSum,
            'projectId' =>  $projectId,
            'projData'  =>  $projectData,
            'data'      =>  $interval
        ];

        return $out;

    }

}