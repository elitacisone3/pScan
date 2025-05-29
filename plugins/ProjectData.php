<?php

namespace pScan\plugins;

class ProjectData extends _ProjectType
{

    public function getTypeName() {
        return 'ProjectData';
    }

    public function getTypeSymbolSource() {
        return 'D1A';
    }

    public function getProjectType($path) {
        $path = fixPath($path);
        if (!file_exists("{$path}projectData.json")) return null;
        return get_class($this);
    }

    public function getPluginMetadata() {
        return [
            'desc'  =>  'Importa i dati da JSON come pseudo progetti'
        ];
    }

    public function parseDirectory($dir, $projectData) {

        $projectId = is_array($projectData) ? (isset($projectData['id']) ? $projectData['id'] : 0) : 0;

        $path = fixPath($dir);
        $file = "{$path}projectData.json";
        if (!file_exists($file)) return null;

        $raw = file_get_contents($file);
        if ($raw) $raw = json_decode($raw,true);
        if (!is_array($raw)) return null;

        $projectData['type'] = get_class($this);

        return [
            'tag'       =>  $raw['tag'],
            'time'      =>  filemtime($file),
            'file'      =>  $file,
            'totSum'    =>  $raw['tot'],
            'projectId' =>  $projectId,
            'projData'  =>  $projectData,
            'data'      =>  $raw['data']
        ];

    }

}