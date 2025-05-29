<?php

function initProc() {
    global $CONF;
    global $SETTINGS;
    global $OPT;
    global $HEAT_MAP;
    global $PALETTE;
    global $VGA_COLOR;
    global $TTY_PALETTE;
    global $LEGACY_CHAR;
    global $WEEK_DAYS;

    ob_start();
    $doHelp = false;

    $OPT = [
        'dal:','al:','salva:','per:','noe','map','mappa','mapnum','mesi','colori','mono','hr',
        'ascii','dati','pr:','G2','head','scr','crw:','nomi','plugin:','132','pro:',
        'palette','tutti','aggiungi:','rimuovi:','tabella','simbolo:','titolo:','compatto',
        'gruppo:','help:','tipo:','simboli','mostra','132x44','max']
    ;

    $canArray = [ 'crw','plugin','pro','P','L','gruppo' ];

    for ($i = 0; $i < 5; $i++) {
        $OPT[] = "H{$i}:";
        $OPT[] = "h{$i}";
    }

    $OPT = getopt('DOHNMhoEeNp:XGP:L:vQqu',$OPT);
    $SETTINGS = [];
    $SETTINGS['grp2'] = false;

    foreach ($OPT as $k => $v) {
        if (is_array($v) and !in_array($k,$canArray)) {
            $k = strlen($k) > 1 ? "--$k":"-$k";
            quit("Può esserci solo un parametro $k");
        }
    }

    $j = $_SERVER['argc'];
    for ($i = 1; $i < $j; $i++) {

        $arg = $_SERVER['argv'][$i];
        if (is_numeric($arg)) continue;
        if (@$arg[0] != '-') continue;
        $x = strlen($arg);
        $y = strlen(ltrim($arg,'-'));
        $x -= $y;

        if (
            $y == 0 or
            ($x == 1 and $y != 1) or
            ($x == 1 and $y > 1) or
            ($x == 2 and $y == 1) or
            ($x > 2)
        ) {
            quit("Opzione errata: $arg");
        }
    }

    $path = dirname(dirname(__FILE__));
    $path = realpath($path);
    if (!$path) quit("Errore interpetazione __FILE__ : ".__FILE__);
    $SETTINGS['myPath'] = fixPath($path);

    if ($OPT === false) {
        $OPT = [];
        $doHelp = true;
    }

    $isWindow = stripos(PHP_OS,'win') !== false;
    $try = [];

    $mYConfInDir = 'config.conf';
    $myDir = PROG_NAME;

    $try[] = "{$SETTINGS['myPath']}config/$mYConfInDir";
    $tmp = fixPath("{$SETTINGS['myPath']}config/conf.d/");

    if (file_exists($tmp)) {
        $ls = glob("{$tmp}*.conf");
        if ($ls) $try = array_merge($try,$ls);
    }

    $x = realpath($_SERVER['PHP_SELF']);
    if (!$x) quit("Non riesco a rilevare il realpath: {$_SERVER['PHP_SELF']}");

    if ($isWindow) {

        if (isset($_SERVER['APPDATA'])) {
            $try[] = fixPath("{$_SERVER['APPDATA']}/$myDir/").$mYConfInDir;
            $SETTINGS['myConfDir'] = fixPath("{$_SERVER['APPDATA']}/$myDir/conf.d/");
            $SETTINGS['myConfBase'] = fixPath("{$_SERVER['APPDATA']}/$myDir/");
        }

        if (isset($_SERVER['USERPROFILE'])) {
            $try[] = fixPath("{$_SERVER['USERPROFILE']}/.config/{$myDir}"). $mYConfInDir;
            $SETTINGS['myConfBase'] = fixPath("{$_SERVER['USERPROFILE']}/.config/{$myDir}/");
            $SETTINGS['myConfDir'] = fixPath($_SERVER['USERPROFILE'])."/.config/{$myDir}/conf.d/";
        }

        $encoding = 'CP850'; // Si è quello di windows vecchio!

    } else {

        $try[] = fixPath("/etc/$myDir").$mYConfInDir;
        $SETTINGS['myConfDir'] = fixPath("/etc/$myDir/conf.d/");
        $SETTINGS['myConfBase'] = fixPath("/etc/$myDir/");
        $encoding = mb_internal_encoding();
        if (!$encoding) $encoding = 'UTF-8';

    }

    $SETTINGS['encoding'] = $encoding;

    if (isset($_SERVER['HOME'])) {
        $try[] = fixPath("{$_SERVER['HOME']}/.config/{$myDir}").$mYConfInDir;
        $SETTINGS['myConfDir'] = fixPath("{$_SERVER['HOME']}/.config/{$myDir}/conf.d/");
        $SETTINGS['myConfBase'] = fixPath("{$_SERVER['HOME']}/.config/$myDir/");
    }

    if (isset($SETTINGS['myConfBase'])) {
        $SETTINGS['myConfBase'] = fixPath($SETTINGS['myConfBase']);
    }

    if (isset($SETTINGS['myConfDir'])) {

        $SETTINGS['myConfDir'] = fixPath($SETTINGS['myConfDir']);

        if (file_exists($SETTINGS['myConfDir'])) {
            $ls = fixPath($SETTINGS['myConfDir']);
            $ls = glob("{$ls}*.conf");
            $try = array_merge($try,$ls);
        }
    }

    mb_internal_encoding("UTF-8");

    $CONF = [];
    parseConfigFiles($try);
    $SETTINGS['try'] = $try;

    if (isset($CONF['optModes'])) {

        $keys = [];

        foreach ($CONF['optModes'] as $key => $mode) {
            if (!preg_match('/^[A-Za-z0-9]+$/',$key)) quit("Nome template parametri non valido: $key","optModes.{$key}");
            $keys["@$key"] = $mode;
        }

        $SETTINGS['optModes'] = $keys;
        $keys = array_keys($keys);
        $keys = getopt('',$keys);

        foreach ($keys as $key => $none) {

            $mode = $SETTINGS['optModes'][$key];
            $param = parseInternalOpts($mode,'optModes.'.substr($key,1));

            foreach ($param as $k => $v) {
                if ($k == 'title') continue;
                if (!isset($OPT[$k])) $OPT[$k] = $v;
            }
        }

    }

    if (isset($OPT['mapnum'])) $OPT['map'] = false;

    if (isset($OPT['plugin'])) {
        $SETTINGS['plugins'] = is_array($OPT['plugin']) ? $OPT['plugin'] : [ $OPT['plugin'] ];
        unset($OPT['plugin']);
    }

    if (isset($OPT['mappa'])) {
        $SETTINGS['fullNames'] = true;
        $OPT['h'] = true;
        $OPT['o'] = true;
        $OPT['map'] = true;
        $OPT['nomi'] = true;
    }

    if (isset($OPT['compatto'])) {
        $OPT['h'] = true;
        $OPT['o'] = true;
        $OPT['N'] = true;
    }

    if (isset($OPT['per'])) {
        if (
            preg_match(
                '/^(?<y>[0-9]{4})\\/(?<m>[0-9]{1,2})\\/(?<d1>[0-9]{1,2})\\-(?<d2>[0-9]{1,2})$/',
                $OPT['per'],
                $match)
        ) {
            $OPT['dal'] = "{$match['d1']}/{$match['m']}/{$match['y']}";
            $OPT['al'] = "{$match['d2']}/{$match['m']}/{$match['y']}";

            unset($OPT['per']);

        } else {
            quit("Periodo non valido: {$OPT['per']}");
        }
    }

    if (isset($OPT['map'])) {
        if (isset($OPT['D'])) {
            unset($OPT['D']);
        } else {
            $OPT['D'] = false;
        }
    }

    $SETTINGS['head'] = isset($OPT['head']);
    $SETTINGS['scroll'] = isset($OPT['scr']) ? max(25,getConfig('tty.scrollEvery',25)) : 0;
    $SETTINGS['hr'] = !isset($OPT['hr']);

    if ($SETTINGS['scroll']) $SETTINGS['head'] ^= true;

    if (isset($OPT['G']) and isset($OPT['dati'])) $doHelp = true;

    $SETTINGS['grp2'] = getConfig('tty.numGraph',false);
    if (isset($OPT['G2'])) $SETTINGS['grp2'] ^= true;

    $SETTINGS['profile'] = getConfig('main.profile','');

    if (isset($OPT['pr']) and preg_match('/^[A-Za-z0-9]+$/',$OPT['pr'])) {
        $SETTINGS['profile'] = $OPT['pr'];
    }

    $SETTINGS['pro'] = [];

    if ($SETTINGS['profile'] !='') {

        $done = [];
        $find = false;

        while($SETTINGS['profile'] != '') {

            if (!preg_match('/^[A-Za-z0-9]+$/',$SETTINGS['profile'])) {
                quit("Profilo non valido: {$SETTINGS['profile']}");
            }

            if (in_array($SETTINGS['profile'],$done)) {
                quit("Profilo in loop: {$SETTINGS['profile']}");
            }

            $try = $SETTINGS['try'];

            foreach ($try as $item) {

                $item = dirname($item);
                $item = fixPath($item);
                $item.= "profile/{$SETTINGS['profile']}.conf";
                $current = $SETTINGS['profile'];

                if (file_exists($item)) {
                    $done[] = $SETTINGS['profile'];
                    $SETTINGS['profile'] = '';
                    parseConfigFiles([$item]);
                    $SETTINGS['try'][] = $item;
                    $find = true;
                    break;
                }

                if ($find) {
                    break;
                } else {
                    quit("Nessun file profilo trovato per: $current\n");
                }

            }

        }

        $SETTINGS['pro'] = $done;

    }

    $SETTINGS['encoding'] = getConfig('tty.encoding',$encoding);
    $SETTINGS['direct'] = getConfig('tty.noEncode',false);

    $SETTINGS['colors'] = getIsSupportedANSI();
    $SETTINGS['colors'] = getConfig('tty.colors',$SETTINGS['colors']);

    $SETTINGS['stdColors'] = !getIsSupportedANSIPalette();
    $SETTINGS['stdColors'] = getConfig('tty.standardColors',$SETTINGS['stdColors']);

    $SETTINGS['minLen'] = getConfig('count.minLen',120);
    $SETTINGS['windows'] = $isWindow;

    if (isset($OPT['noe'])) $SETTINGS['direct'] = true;

    $SETTINGS['fSab'] = getConfig('count.sabFest',true);
    $SETTINGS['fDom'] = getConfig('count.domFest',true);

    foreach ($WEEK_DAYS as $id => $data) {
        $WEEK_DAYS[$id][3] = getConfig("count.{$data[1]}Fest",$data[3]);
        $WEEK_DAYS[$id][2] = hexdec(getConfig("count.{$data[1]}Color",$data[2]));
    }

    $validGroup = ['project','work','fest','vacation','useDefault'];

    if (isset($CONF['fest'])) {

        foreach ($CONF['fest'] as $group => $list) {
            if ($group == 'useDefault') continue;

            if (!in_array($group,$validGroup) or !is_array($list)) quit("Errore configurazione: fest.$group");

            foreach ($list as $item) {

                $item = "$item/1/1";
                $item = str_replace(['\\','-',':','.'],'/',$item);
                $item = explode('/',$item);

                foreach ($item as &$x) {
                    $x = intval(trim($x));
                }
                unset($x);

                switch ($group) {

                    case 'work':
                        addDayType($item[1],$item[0],0,false);
                        break;

                    case 'fest':
                        addDayType($item[1],$item[0],0,true);
                        break;

                    case 'vacation':
                        addDayType($item[1],$item[0],$item[2],true);
                        break;

                    case 'project':
                        addDayType($item[1],$item[0],$item[2],false);
                        break;

                }

            }

        }
    }

    if (getConfig('fest.useDefault',true)) {
        addDayType(1,1,0,true);
        addDayType(1,6,0,true);
        addDayType(4,21,0,true);
        addDayType(4,21,0,true);
        addDayType(4,35,0,true);
        addDayType(5,1,0,true);
        addDayType(6,2,0,true);
        addDayType(8,15,0,true);
        addDayType(11,1,0,true);
        addDayType(12,8,0,true);
        addDayType(12,25,0,true);
        addDayType(12,26,0,true);
    }

    if (isset($OPT['colori'])) $SETTINGS['colors'] = true;
    if (isset($OPT['mono'])) $SETTINGS['colors'] = false;

    if ($SETTINGS['stdColors']) {

        $TTY_PALETTE = $VGA_COLOR;
        $SETTINGS['origPalette'] = $TTY_PALETTE;

    } else {

        foreach($PALETTE as $id => $item) {

            $r = 15 & ($item >> 8);
            $g = 15 & ($item >> 4);
            $b = 15 & $item;

            $TTY_PALETTE[$id] = ttyPalette($r,$g,$b);
            if ($id>15) $VGA_COLOR[$id] = $TTY_PALETTE[$id];

        }

        $SETTINGS['origPalette'] = $TTY_PALETTE;

        if (isset($CONF['palette'])) {
            $o = $TTY_PALETTE;
            foreach ($CONF['palette'] as $key => $value) {

                if (!preg_match('/^color(?<id>[0-9a-fA-F]{1,3})$/',$key, $palId)) {
                    quit("Parametro sconosciuto: $key","palette.{$key}");
                }

                $pId = hexdec($palId['id']);

                if (!preg_match('/^(?<id>[0-9a-fA-F]{1,3})$/',$value, $palVal)) {
                    quit("Valore errato: $value","palette.{$key}");
                }

                $pal = hexdec($palVal['id']);
                if (!isset($TTY_PALETTE[$pal])) quit("Colore sconosciuto: {$palVal['id']}","palette.{$key}");
                if (!isset($TTY_PALETTE[$pId])) quit("Colore sconosciuto: {$palId['id']}","palette.{$key}");
                $o[$pId] = $TTY_PALETTE[$pal];

            }
        }

    }

    clearPlugins();
    loadAllTypes();

    $list = getConfig('main.plugin',[]);

    if ($list) {

        $list = array_unique($list);

        foreach ($list as $key => $item) {
            addPlugin($item,"main.plugin.$key",true);
        }

    }

    if (isset($SETTINGS['plugins'])) {

        $SETTINGS['plugins'] = array_unique($SETTINGS['plugins']);

        foreach ($SETTINGS['plugins'] as $item) {

            if (!isPluginLoaded($item)) addPlugin($item);
        }

        unset($SETTINGS['plugins']);

    }

    initPlugins();
    finalizeTypes();

    if (isset($CONF['heatMap']) and is_array($CONF['heatMap'])) {

        $HEAT_MAP = [];
        $HEAT_CHARS = ['■','░','▒','▓','█'];
        $HEAT_COL   = [15 , 4 , 4 , 13, 12];

        foreach ($CONF['heatMap'] as $key => $value) {

            if (preg_match('/^hour(?<l>(|[0-2].)[0-9].)$/',$key,$match) and is_numeric($value) and $value > -1 and $value < 5) {

                $match = intval($match['l']) % 24;
                $HEAT_MAP[$match] = [ $value , $HEAT_CHARS[$value], $HEAT_COL[$value] ];

            } else {

                quit("Errore livello: heatMap $key");

            }
        }
    }

    if (isset($OPT['dal'])) $SETTINGS['from'] = floor(parseDate($OPT['dal']) / 1440);
    if (isset($OPT['al'])) $SETTINGS['to'] = floor(parseDate($OPT['al']) / 1440);

    for ($i = 0; $i < 5 ; $i++) {

        $key ="H$i";

        if (isset($OPT[$key])) {

            if (!isset($SETTINGS['heat'])) $SETTINGS['heat'] = [];
            $SETTINGS['heat'][$i] = intval($OPT[$key]);

        } else {

            if (isset($OPT["h$i"])) {
                if (!isset($par['heat'])) $SETTINGS['heat'] = [];
                $SETTINGS['heat'][$i] = 1;
            }

        }
    }

    if (!$SETTINGS['direct']) {

        $text = "\x09\x20\x0a\x1b";
        $test = mb_convert_encoding($text,$SETTINGS['encoding'],'UTF-8');
        if ($text != $test) quit("Encoding non supportato: {$SETTINGS['encoding']}");

        $SETTINGS['substChar'] = [];
        foreach ($LEGACY_CHAR as $item) {
            $test = mb_convert_encoding($item[0],$SETTINGS['encoding'],'UTF-8');
            if ($test == '?') $SETTINGS['substChar'][] = $item;
        }

    }

    if (isset($OPT['ascii'])) $SETTINGS['substChar'] = $LEGACY_CHAR;

    if (isset($CONF['ProjectDailyLevels'])) {

        $SETTINGS['nameLevels'] = [];
        foreach ($CONF['ProjectDailyLevels'] as $key => $value) {

            if (
                preg_match('/^hourLevel(?<l>[0-9]{1,3})$/', $key, $match) and
                preg_match('/^[0-9A-Fa-f]{1,2}$/',$value) and
                !isset($SETTINGS['nameLevels'][$match['l']])
            ) {

                $SETTINGS['nameLevels'][intval($match['l'])] = hexdec($value);

            } else {

                quit("Errore configurazione per la colorazione dei nomi","ProjectDailyLevels.{$key}");

            }

        }

        krsort($SETTINGS['nameLevels']);

    }

    if (!isset($OPT['help'])) {
        $j = count($_SERVER['argv']);
        for ($i = 1; $i < $j; $i++) {
            if ($_SERVER['argv'][$i] == '--help') {
                $OPT['help'] = '_default';
                $doHelp = false;
                break;
            }
        }
    }

    pluginCall('onInitDone',true);
    getProjectsByOPT();

    if ($doHelp) {

        outPres();
        doHelp();

    }

    if (isset($OPT['max'])) {
        set132Mode(2);
    } elseif (isset($OPT['132x44'])) {
        set132Mode(1);
    } elseif (isset($OPT['132'])) {
        set132Mode(0);
    }

    pluginCall('onStart',true);

}

function parseConfigFiles($array) {
    global $CONF;
    if (!is_array($CONF)) $CONF = [];

    foreach ($array as $file) {
        if (file_exists($file)) {
            $ini = parse_ini_file($file,true);
            if ($ini === false) quit("Errore lettura: $file");
            $CONF = array_replace_recursive($CONF,$ini);
        }
    }

}

function parseInternalOpts($iParam,$conf = null) {

    $byStr = explode(' ',$iParam);

    $j = count($byStr);
    $par = [];
    $cPar = null;

    for ($i = 0 ; $i < $j; $i++) {

        $str = $byStr[$i];

        if ($str[0] == '-') {

            $cPar = ltrim($str,'-');

            if (isset($par[$cPar])) {

                if (is_array($par[$cPar])) {

                    $par[$cPar][] = '';

                } else {

                    $par[$cPar] = [$par[$cPar]];
                    $par[$cPar][] = '';

                }

            } else {

                $par[$cPar] = false;

            }

        } elseif ($cPar !== null) {

            if ($par[$cPar] === false) {

                $par[$cPar] = $str;

            } else {

                if (is_array($par[$cPar])) {

                    if ($par[$cPar][0] === false) quit("Errore parametri interni: $iParam", $conf);

                    $c = count($par[$cPar]) - 1;

                    if ($par[$cPar][$c] == '') {

                        $par[$cPar][$c] = $str;

                    } else {
                        $par[$cPar][$c] .= " $str";
                    }

                } else {

                    $par[$cPar] .= " $str";

                }

            }

        } else {

            quit("Errore parametri interni: $iParam", $conf);

        }

    }

    return $par;

}

function getIsSupportedANSIPalette() {

    $os = php_uname('s');
    $os = strtolower($os);

    if (stripos($os,'windows') !== false) return false;
    return true;

}

function getIsSupportedANSI() {

    if (function_exists('stream_isatty')) {
        if (defined('STDOUT')) {
            if (!stream_isatty(STDOUT)) return false;
        }
    }

    $os = php_uname('s');
    $os = strtolower($os);

    if (stripos($os,'windows') !== false) {

        if (isset($_SERVER['ANSICON']) or isset($_SERVER['ANSICON_DEF'])) return true;

        $os = php_uname('r');
        $os = strtolower($os);
        $os = preg_replace('/[^0-9]+/','',$os);
        $os = intval($os);

        return $os >= 100; // Supporta windows 100 ^_^

    } else {
        return true;
    }
}

function getConfig($path,$default) {
    global $CONF;

    $cur = $CONF;
    $pathA = explode('.',$path);

    foreach ($pathA as $item) {

        if (isset($cur[$item])) {

            $cur = $cur[$item];

        } else {

            return $default;

        }
    }

    if (is_bool($default)) {

        if (is_bool($cur)) return $cur;

        if (is_numeric($cur)) {
            if ($cur == 0) return false;
            if ($cur == 1) return true;
        }

    } elseif (is_string($default)) {

        if (is_scalar($cur) and !is_bool($cur)) return "$cur";

    } else {

        if (
            is_scalar($cur) and !is_bool($cur) and
            is_numeric($cur) and !is_infinite($cur) and
            !is_nan($cur)
        ) {

            if (is_int($default)) return intval($cur);
            if (is_float($default)) return floatval($cur);
            quit("Errore nel valore di default",$path);

        }

    }

    if (is_bool($cur)) {
        $cur = $cur ? '[true]':'[false]';
    } elseif (is_array($cur)) {
        $cur = '[Array]';
    } elseif (is_null($cur)) {
        $cur = '[null]';
    } elseif (!is_scalar($cur)) {
        $cur = '['.gettype($cur).']';
    }

    quit("Tipo di valore errato: $cur", $path);
    exit(1); // Gia fatto!
}

function saveSubConfig($sectionNames, $file, $extraText = null) {
    global $CONF;
    $o = [];

    if ($extraText) {

        $extraText = str_replace("\r",'',$extraText);
        $extraText = trim($extraText,"\n");
        $extraText = explode("\n",$extraText);

        foreach ($extraText as $line) {
            $o[] = ";; $line";
        }

        $o[] = '';
    }

    foreach ($sectionNames as $sectionName) {
        if (!isset($CONF[$sectionName])) continue;
        $o[] = "[{$sectionName}]";
        foreach ($CONF[$sectionName] as $key => $value) {
            if (is_bool($value)) $value = $value ? 1:0;
            if (!is_scalar($value)) continue;
            if (is_string($value)) $value = '"' . addcslashes($value,"\\\t\r\n\"").'"';
            $o[] = "$key = $value";
        }
        $o[] = '';
    }

    $o = implode("\r\n",$o);
    return file_put_contents($file,$o);

}

function getProjectsByOpt() {
    global $SETTINGS;
    global $OPT;
    global $CONF;

    $list = [];

    if (isset($OPT['crw'])) {

        if (!is_array($OPT['crw'])) $OPT['crw'] = [$OPT['crw']];

        foreach ($OPT['crw'] as $item) {
            $tmp = crawlerPath($item);
            $list = array_merge($list,$tmp);
        }

        unset($OPT['crw']);
    }

    if (isset($OPT['L'])) {
        if (!is_array($OPT['L'])) $OPT['L']= [$OPT['L']];

        foreach ($OPT['L'] as $item) {
            $x = file($item,FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($x === false) quit("Errore file: $item");
            $list = array_merge($list,$x);
        }

        unset($OPT['L']);
    }

    if (isset($OPT['pro'])) {

        if (!is_array($OPT['pro'])) $OPT['pro'] = [$OPT['pro']];
        if (isset($CONF['projectsNameMap']) and count($CONF['projectsNameMap']) > 0) {

            foreach ($OPT['pro'] as $name) {
                if (isset($CONF['projectsNameMap'][$name])) {
                    $list[] = $CONF['projectsNameMap'][$name];
                } else {
                    quit("Il progetto $name non risulta configurato");
                }
            }

            unset($OPT['pro']);

        } else {
            quit("Non ci sono progetti configurati",'projectsNameMap');
        }
    }


    if (isset($OPT['gruppo'])) {

        $section = "Group-{$OPT['gruppo']}";
        if (!isset($CONF[$section]['project'])) quit("Gruppo di progetti non configurato: {$OPT['gruppo']}");
        if (!is_array($CONF[$section]['project'])) quit("Errore configurazione gruppo {$OPT['gruppo']}",$section);
        $list = array_merge($CONF[$section]['project'],$list);
        unset($OPT['gruppo']);

    }

    if (isset($OPT['P'])) {

        if (!is_array($OPT['P'])) $OPT['P'] = [$OPT['P']];
        $list = array_merge($list,$OPT['P']);
        unset($OPT['P']);

    }

    if (isset($OPT['tutti'])) {
        if (isset($CONF['projectsNameMap']) and count($CONF['projectsNameMap']) > 0) {

            $list = array_merge(array_values($CONF['projectsNameMap']),$list);
            unset($OPT['tutti']);

        } else {
            quit("Non ci sono progetti configurati",'projectsNameMap');
        }
    }

    if (isset($OPT['p'])) $list[] = $OPT['p'];

    $list = array_unique($list);

    if (isset($OPT['tipo'])) {

        $typeMap = getProjectTypeClassMap();
        $fltType = true;

    } else {

        $fltType = false;

    }

    $out = [];
    foreach ($list as $item) {
        if (!file_exists($item) and !is_dir($item)) quit("Percorso non trovato: $item");
        $o = realpath($item);
        if (!$o) quit("Percorso non risolto: $item");
        $o = fixPath($o);
        $type = getProjectTypeByPath($o);
        if (!$type) quit("Tipo di progetto non riconosciuto: $item");

        if ($fltType) {
            $cType = isset($typeMap[$type]) ? $typeMap[$type] : '*';
            if (strcasecmp($cType,$OPT['tipo']) != 0) continue;
        }

        $out[] = $o;

    }

    if (count($list) > 0 and count($out) == 0) quit("Il filtraggio ha rimosso tutti gli elementi ricercati");

    if ($out) {
        $SETTINGS['noProj'] = false;
        $SETTINGS['projList'] = $out;
    } else {
        $SETTINGS['noProj'] = true;
    }

}