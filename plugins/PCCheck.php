<?php
namespace pScan\plugins;

class PCCheck extends _ProjectType
{

    const POINTS_PTR = 1440 * 4 + 4;

    private $isPoints = false;

    function __construct() {
        $opt = getopt('',['chk-p']);
        $this->isPoints = isset($opt['chk-p']);
    }

    public function getHelpParamMap() {
        return [
            'chk-p' =>  'Imposta la modalità a punti anziché contare le ore.'
        ];
    }

    public function getTypeName() {
        return 'PCCheck';
    }

    public function getTypeSymbolSource() {
        return 'C0F';
    }

    public function getPluginMetadata() {
        return [
            'desc'  =>  'Importa monitoraggio I/O, PCCheck V1.0 / V1.1'
        ];

    }

    public function getProjectType($path) {
        $path = fixPath($path);

        $try = [
            'init',
            'YEAR.db',
            'Week.db',
            'value.db',
            'User.db'
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

        $pathIdFile = "{$dir}pathId";
        $pathId = null;
        if (file_exists($pathIdFile)) $pathId = file_get_contents($pathIdFile);
        if ($pathId) $pathId = trim($pathId);

        $totSum = 0;

        if (!$pathId) {
            $data = stat($dir);
            $pathId = pack('QQN',$data['dev'],$data['ino'],$data['ctime']);
            $pathId.= file_get_contents("{$dir}/init");
            $pathId = "{$dir}\n{$pathId}";
            $pathId = hash('ripemd128',$pathId);
            file_put_contents($pathIdFile,$pathId);
        }

        $interval = [];

        $list = glob("{$dir}Day-*-*-*.db");
        foreach ($list as $file) {

            $name = basename($file);
            $x = filemtime($file);
            $lastUpd = max($lastUpd,$x);

            if (!preg_match('/^Day\\-(?<d>[0-9]{1,2})\\-(?<m>[0-9]{1,2})\\-(?<y>[0-9]{4})\\.db$/', $name,$match)) {
                tty(7,0,"Salto: $file");
                echo "\n";
                continue;
            }

            $data = file_get_contents($file);

            if ($this->isPoints) $data = substr($data,self::POINTS_PTR);

            $data = unpack('N*',$data);
            $data = array_values($data);

            if (count($data) < 1440) {
                tty(6,0,"Errore: $file");
                echo "\n";
                continue;
            }

            $minuteArray = [];
            for ($i = 0; $i < 1440; $i++) {
                if ($data[$i] !=0) {
                    if (!isset($minuteArray[$i])) $totSum++;
                    $minuteArray[$i] = true;
                }
            }

            $data = splitDayIntervals($minuteArray);
            $dayBaseTime = gmmktime(0,0,0,$match['m'],$match['d'],$match['y']);
            foreach ($data as $datum) {

                $timeStart = $dayBaseTime + ($datum[0] * 60);
                $timeLength = $datum[1] * 60;

                $interval[] = [
                    'type'  =>  1,
                    'pid'   =>  $projectId,
                    'start' =>  $timeStart,
                    'len'   =>  $timeLength,
                    'time'  =>  $timeLength
                ];

            }

        }

        $projectData['type'] = get_class($this);

        return [
            'tag'       =>  $pathId,
            'time'      =>  $lastUpd,
            'file'      =>  $file,
            'totSum'    =>  $totSum * 60,
            'projectId' =>  $projectId,
            'projData'  =>  $projectData,
            'data'      =>  $interval
        ];

    }

}