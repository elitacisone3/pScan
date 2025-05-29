<?php

function outDaily($data) {
    global $HOUR_MAP;
    global $OPT;
    global $WEEK_DAYS;
    global $SETTINGS;

    $days = $data['days'];
    $CONT_CHAR = [ '-' , '·' , '┌' , '│', '└' ] ;
    $dayHMax = getConfig('day.dayHMax',8);
    $dayHTot = getConfig('day.dayHTot',24);
    $doHeat = !isset($OPT['h']);
    $doOvr = !isset($OPT['o']);
    $fullEmpty = isset($OPT['E']);
    $doEmpty = isset($OPT['e']) || $fullEmpty;
    $isHead = !isset($OPT['N']);
    $compact = isset($OPT['compatto']);

    $inCont = false;

    $hasPlugin = isPluginMethod('outDayLine');

    $isPRarr = isset($OPT['nomi']);
    if (!isset($OPT['map']) or !isset($SETTINGS['projArray'])) $isPRarr = false;
    if ($isPRarr and isset($SETTINGS['fullNames'])) $isExtMap = true; else $isExtMap = false;

    $headCx = $isHead ? (2*count($HOUR_MAP)) : 0;
    $headCx+= 24;

    if ($isExtMap or $compact) {
        $hasPlugin = false;
        $doOvr = false;
        $doHeat = false;
        $lineWidth = 49 + $headCx;
        $lineWidth = 132 - $lineWidth;
        $compact = true;
    } else {
        $lineWidth = 0;
    }

    $heatLVal = [   0,  0   ,0  ,0  ,0];
    $heatLCol = [   0,  3   ,3  ,3  ,3];
    $heatMax = [    0,  4   ,4  ,4  ,4];
    $heatThr = [    0,  2   ,3  ,2  ,1];
    $heatCol = [    0,  3   ,4  ,4  ,3];
    $heatHCol = [   0,  4  ,12 ,12 ,12];


    for ($i = 1; $i<5;$i++) {
        $heatMax[$i] = getConfig("day.heatMax.{$i}",$heatMax[$i] );
        $heatThr[$i] = getConfig("day.heatThr.{$i}",$heatThr[$i]);
        $heatCol[$i] = hexdec(getConfig("day.heatColor.{$i}",$heatCol[$i]));
        $heatHCol[$i] = hexdec(getConfig("day.heatHiColor.{$i}",$heatHCol[$i]));
        $heatLVal[$i] = getConfig("day.heatLo.{$i}",$heatLVal[$i]);
        $heatLCol[$i] = hexdec(getConfig("day.heatLoColor.{$i}",$heatLCol[$i]));
    }

    if ($SETTINGS['hr']) hr();
    if ($SETTINGS['head']) outDailyHeader($doHeat,$doOvr,$headCx,true, $compact ,$hasPlugin);
    $cLine = 0;

    foreach ($days as $day) {

        if ($doEmpty and $inCont and $day['sal'] >0 ) {
            if ($fullEmpty) {

                $dFrom = 1 + $day['id'] - $day['sal'];
                for ($d = $dFrom; $d < $day['id']; $d++) {

                    if ($SETTINGS['scroll']){
                        $cLine++;
                        if ($cLine == $SETTINGS['scroll']) {
                            $cLine = 0;
                            outDailyHeader($doHeat,$doOvr,$headCx,false, $compact,$hasPlugin);
                        }
                    }

                    $tcr = $d * 86400;
                    tty(7,0, gmdate('d/m/Y',$tcr));
                    tty(8,0,' --');
                    tty(7,0, " \x01 ..:.. ..:.. ");
                    if (!$compact) tty(7,0,fixLen(0,2));
                    tty(1,0, '│');
                    echo str_repeat(' ',$headCx);
                    tty(1,0, '│');
                    tty(7,0,fixLen(0,3));
                    tty(1,0, '│');
                    echo graph(0 , $dayHMax , 8);
                    tty(1,0, '│');

                    if ($doHeat) {
                        for ($i = 1; $i<5;$i++) {

                            echo graph(0,4,4);
                            tty(1,0, '│');

                        }
                    }

                    if ($doOvr) {
                        tty(7,0, ' '.fixLen(0 , 2));
                        tty(7,0,' '.fixLen(0 , 3));
                        tty(1,0, '│');
                    }

                    if (!$compact) {
                        tty(7,0,fixLen($day['curTot'] , 5));
                        tty(1,0, '│');
                    }
                    tty(7,0,fixLen($day['totSum'] , 5));

                    if ($hasPlugin) pluginCall('outDayLine',true,null);

                    echo "\n";

                }
            } else {
                echo "\n{$day['sal']} \x01\n\n";
            }
        }

        if ($SETTINGS['scroll']) {
            $cLine++;
            if ($cLine == $SETTINGS['scroll']) {
                $cLine = 0;
                outDailyHeader($doHeat,$doOvr,$headCx,false, $compact,$hasPlugin);
            }
        }

        tty(15,0, "{$day['date']} ");
        $wDay = $WEEK_DAYS[ $day['wDay'] ];
        tty($wDay[2]!=0 ? $wDay[2] : 8,0,$wDay[0]);
        tty($day['fest'] ? 13:3,0, $day['fest'] ? '■':'·');
        echo  ' ' ;
        tty(11,0, $CONT_CHAR[$day['cont']]);
        echo  ' ' ;
        tty(3,0,tcrToTime($day['min']).' ');
        tty(3,0,tcrToTime($day['max']).' ');
        if (!$compact) tty(2,0, fixLen($day['int'],2));
        tty(1,0,'│');
        echo $day['str'];
        tty(1,0, '│');
        tty(3,0, fixLen($day['tot'],3));
        tty(1,0, '│');
        echo graph($day['tot'] , $dayHTot , 8, 7, $dayHMax,4,13);
        tty(1,0, '│');

        if ($doHeat) {
            for ($i = 1; $i<5;$i++) {

                echo graph($day['heat'][$i], $heatMax[$i],4, $heatCol[$i], $heatThr[$i], $heatHCol[$i],12,0, $heatLCol[$i] , $heatLVal[$i]);
                tty(1,0, '│');

            }
        }

        if ($doOvr) {
            tty(3,0, ' '.fixLen($day['ovr'] , 2));
            tty(3,0, ' '.fixLen($day['cnt'] , 3));
            tty(1,0, '│');
        }

        if (!$compact) {
            tty(7,0,fixLen($day['curTot'] , 5));
            tty(1,0, '│');
        }
        tty(15,0,fixLen($day['totSum'] , 5));

        if ($hasPlugin) pluginCall('outDayLine',true,$day);

        if ($isExtMap) {

            if (isset($day['pidMapX'])) outProjectsListLine($day['pidMapX'],$lineWidth);

        } elseif ($isPRarr and isset($day['prArr']) and $day['prArr']) {

            if (count($day['prArr']) > 1) {
                tty(12,0, ' +');
            } else {
                tty(3,0,$day['prArr'][0]);
            }

        }

        echo "\n";

        $inCont = true;
    }
    if ($SETTINGS['hr']) hr();
}

function outDailyHeader($doHeat, $doOvr, $strLen, $first, $compact, $isPlugin) {

    $char = $first ? '┬' : '│';

    tty(0,7, "GG/MM/AAAA ");
    tty(0,7,'G');
    tty(0,7,'F');
    echo  ' ' ;
    tty(0,7, 'C');
    echo  ' ' ;
    tty(0,7,'Dalle ');
    tty(0,7,'Alle  ');
    if (!$compact) tty(0,7, fixLen('I',2));
    tty(1,0,$char);
    tty(0,7,fixLen('Tabella Oraria',$strLen));
    tty(1,0, $char);
    tty(0,7, 'Tot');
    tty(1,0, $char);
    tty(0,7,fixLen('G.Tot',8));
    tty(1,0, $char);

    if ($doHeat) {
        for ($i = 1; $i<5;$i++) {
            tty(0,7,fixLen("Liv{$i}",4));
            tty(1,0, $char);
        }
    }

    if ($doOvr) {
        tty(0,7, ' Ov');
        tty(0,7, ' Cnt');
        tty(1,0, $char);
    }

    if (!$compact) {
        tty(0,7,fixLen('c.Tot' , 5));
        tty(1,0, $char);
    }

    tty(0,7,fixLen('s.Tot' , 5));

    if ($isPlugin) pluginCall('outDayHeader',true);

    echo "\n";

}

function outMonthly($byMonth) {
    global $OPT;
    global $SETTINGS;

    $DAY_LEVELS = ['░','▒','▓','█'];
    $DAY_COL    = [7  , 4 , 13, 12];
    $maxXDay = getConfig('month.maxXDay',8);
    $maxHeatXMonth = getConfig('month.maxHeatXMonth',4);
    $maxHXMonth = getConfig('month.maxHXMont',0);
    $autoMaxHXMonth = getConfig('month.autoMaxHXMonth',$maxHXMonth < 1);
    $maxHFXMonth = getConfig('month.maxHFXMonth',1);

    $hasPlugin = isPluginMethod('outMonthLine');

    if (isset($OPT['map']) and isset($SETTINGS['projArray'])) $isMap = true; else $isMap = false;
    if ($isMap) $mode = 1; else $mode = 0;
    $hSum = 0;
    $isHeat = isset($OPT['h']);
    $byDot = isset($OPT['D']);
    $heatId = 0;
    $heatMin = 0;
    $inCont = false;
    $prevMonth = 0;
    $doEmpty = isset($OPT['e']) || isset($OPT['E']);
    $doExt = isset($OPT['X']);
    $mapNum = isset($OPT['mapnum']);
    $compact = isset($OPT['compatto']);
    $maxWidth = $compact ? 64 : 37;

    if ($compact) {
        $doExt = false;
        $hasPlugin = false;
    }

    $extMap = isset($SETTINGS['fullNames']);
    if (!$isMap) $extMap = false;

    if ($extMap) {
        $hasPlugin = false;
        $doExt = false;
        $mode = 2;
    }

    if ($SETTINGS['hr']) hr();
    if ($SETTINGS['head']) outMonHeader($doExt,true,$hasPlugin,$mode,$compact,$maxWidth);
    $cLine = 0;

    $symbolsMap = $isMap ? getSymbolsMap() : null;

    foreach ($byMonth as $item) {

        if ($doEmpty) {

            $d = ($item['id'] - $prevMonth) - 1;

            if ($inCont and $d > 0) {

                $preId = $item['id'] - $d;

                for ($i = $preId; $i<$item['id']; $i++) {

                    if ($SETTINGS['scroll']){
                        $cLine++;
                        if ($cLine == $SETTINGS['scroll']) {
                            $cLine = 0;
                            outMonHeader($doExt,false,$hasPlugin,$mode,$compact,$maxWidth);
                        }
                    }

                    $year = floor($i / 12);
                    $month = numPad(1 + ($i % 12));
                    tty(7,0,"$year/$month ..-..");
                    tty(1,0, '│');
                    echo str_repeat(' ',31);
                    tty(1,0, '│');
                    echo str_repeat(' ',3);
                    tty(1,0, '│');
                    if (!$compact) {
                    echo str_repeat(' ',8);
                    tty(1,0, '│');
                    }
                    tty(8,0, fixLen(0,3));
                    tty(8,0, fixLen(0,4));
                    tty(1,0, '│');

                    if (!$compact) {
                        echo str_repeat(' ',8);
                        tty(1,0, '│');
                        echo str_repeat(' ',8);
                        tty(1,0, '│');
                    }

                    if ($doExt) {
                        tty(8,0,fixLen(0,5));
                        tty(8,0,fixLen(0,4));
                        tty(1,0, '│');
                    }
                    tty(8,0, fixLen(0,4));
                    echo ' ';
                    tty(7,0, fixLen($hSum,5));

                    if ($hasPlugin) pluginCall('outMonthLine',true,null);

                    echo "\n";
                }

            }

            $prevMonth = $item['id'];
            $inCont = true;

        }

        if ($SETTINGS['scroll']){
            $cLine++;
            if ($cLine == $SETTINGS['scroll']) {
                $cLine = 0;
                outMonHeader($doExt,false,$hasPlugin,$mode,$compact,$maxWidth);
            }
        }

        if ($autoMaxHXMonth) {
            $maxHXMonth = $maxXDay * $item['len'];
        }

        $hSum+=$item['hours'];

        tty(15,0, "{$item['date']} ");
        tty(7,0,
            numPad($item['min']).
            '-'.
            numPad($item['max']))
        ;
        tty(1,0, '│');

        for ($d = 1; $d <= $item['len']; $d++) {


            $v = $item['day'][$d];
            $fest = $item['fest'][$d];
            $paper = $fest ? 4 : 0;

            if ($isMap) {

                if (isset($item['pidMap'][$d])) {

                    $v = count($item['pidMap'][$d]);

                    if (!$mapNum and $v == 1) {

                        $v = $item['pidMap'][$d][0];
                        if (isset($symbolsMap[$v])) {

                            echo $symbolsMap[$v];

                        } else {

                            tty(7,8,'!');

                        }

                        continue;

                    } elseif ($mapNum or $v > 1) {

                        if ($mapNum) {
                            echo mapNumStr($v);
                        } else {
                            tty( 0,12,$v>9 ? '+' : "$v");
                        }

                        continue;
                    }
                }

            } else {

                $blo = $fest ? '▓' : '█';

            }

            if ($v > 0) {

                if ($isHeat) {

                    if (isset($item['map'][$d]) and $item['map'][$d][$heatId] > $heatMin) {
                        tty(13,$paper, $blo);
                    } else {
                        tty(15,$paper,'░');
                    }

                } else {

                    $p = intval(($v / $maxXDay) * 3);
                    if ($p > 3) $p = 3;
                    tty($DAY_COL[$p],$paper, $DAY_LEVELS[$p]);

                }


            } else {

                if ($byDot) {
                    tty($paper ? 13 : 8, 0, '·');
                } else {
                    tty(8,$paper, $d % 10);
                }


            }
        }

        for (;$d<32;$d++) {
            echo ' ';
        }

        $tFes = round($item['tFes'] / 3600,1);

        tty(1,0, '│');
        tty($item['heatD'][5] > 4 ? 13:4,0, fixLen($item['heatD'][5],3));

        if (!$compact) {
            tty(1,0, '│');
            echo graph($item['heatD'][5],$maxHeatXMonth,8,4,$maxHXMonth >> 1,13,12);
        }

        tty(1,0, '│');
        tty(7,0, fixLen($item['dTot'],3));
        tty(15,0, fixLen($item['hours'],4));
        tty(1,0, '│');

        if (!$compact) {
            echo graph($item['hours'],$maxHXMonth,8,7,$maxHXMonth >> 1,4,13);
            tty(1,0, '│');
            echo graph($tFes,$maxHFXMonth,8,7,$maxHFXMonth >> 1,4,13);
            tty(1,0, '│');
        }

        if ($doExt) {
            tty(3,0,fixLen($item['hours'],5));
            tty(3,0,fixLen($item['heatD'][5],4));
            tty(1,0, '│');
        }

        tty($tFes > 0 ? ( $tFes > $maxHFXMonth ? 12 : 13 ) : 8,0, fixLen($tFes,4));
        echo ' ';
        tty(15,0, fixLen($hSum,5));

        if ($hasPlugin) pluginCall('outMonthLine',true,$item);

        if ($extMap and isset($item['pidMapX'])) {
            $scale = 1.0 / $item['len'];
            outProjectsListLine($item['pidMapX'],$maxWidth, $scale);
        }

        echo "\n";
    }
    if ($SETTINGS['hr']) hr();
}


function outMonHeader($doExt,$first, $hasPlugin, $mode, $compact, $maxWith) {

    $char = $first ? '┬' : '│';
    tty(0,7,"AAAA/MM Da a ");
    tty(1,0, $char);
    tty(0,7,fixLen('Tab. Giorni',31));
    tty(1,0, $char);
    tty(0,7,fixLen('Liv',3));
    tty(1,0, $char);

    if (!$compact) {
        tty(0,7,fixLen('G.Livello',8));
        tty(1,0, $char);
    }

    tty(0,7, fixLen('Nu.',3));
    tty(0,7, fixLen('Cas',4));
    tty(1,0, $char);

    if (!$compact) {
        tty(0,7,fixLen('G.Ore',8));
        tty(1,0, $char);
        tty(0,7,fixLen('G.Fes',8));
        tty(1,0, $char);
    }

    if ($doExt) {
        tty(0,7,fixLen('Ore',5));
        tty(0,7,fixLen('Liv',4));
        tty(1,0, $char);
    }

    tty(0,7, fixLen('T.F.',5));

    tty(0,7, fixLen('Tot',5));

    if ($hasPlugin) pluginCall('outMonthHeader',true);
    if ($mode == 1) {
        tty(0,7,fixLen('Primo progetto',$maxWith));
    } elseif ($mode == 2) {
        tty(0,7,fixLen('Classifica progetti',$maxWith));
    }

    echo "\n";

}

function outSummary($data) {
    global $SETTINGS;
    global $OPT;

    echo "\n";
    tty(14,0,"Riassunto dati:");
    echo "\n";

    if ($SETTINGS['pro']) {
        tty(11,0,fixLen('Profili:',20));
        $x = implode(', ',$SETTINGS['pro']);
        $x = wordwrap($x,80,"\n",false);
        $x = str_replace("\n","\n".str_repeat(' ',20),$x);
        $x = rtrim($x);
        tty(10,0,$x);
        echo "\n\n";
    }

    tty(15,0,fixLen('Elaborati:',20));
    tty(10,0,$data['cntOut']);
    tty(7,0,' / ');
    tty(10,0,$data['cntAll']);
    echo "\n";

    tty(15,0,fixLen('Ore visualizzate:',20));
    tty(10,0,$data['hSum']);
    tty(7,0,' / ');
    tty(10,0,$data['hAll']);
    echo "\n";


    tty(15,0,fixLen('Ore contate:',20));
    tty(10,0,ceil($data['hSum']));
    tty(7,0,' / ');
    tty(10,0,ceil($data['hAll']));
    echo "\n";

    tty(15,0,fixLen('Tempo extra:',20));
    tty(10,0,secondsToTime($data['extra']));
    echo "\n";

    tty(15,0,fixLen('Tempo tcrTot:',20));
    tty(10,0,secondsToTime($data['tcrTot'] ) ) ;
    echo "\n";

    tty(15,0,fixLen('Totale override:',20));
    tty(10,0,secondsToTime($data['cox']['cnOvrT'])) ;
    echo "\n";

    tty(15,0,fixLen('Tempo override:',20));
    tty(10,0,secondsToTime($data['tcrTot'] ) ) ;
    echo "\n";

    tty(15,0,fixLen('Numero elementi:',20));
    tty(10,0,$data['cox']['cnItem']) ;
    echo "\n";

    tty(15,0,fixLen('Numero caselle:',20));
    tty(10,0,$data['cox']['cnCel']) ;
    echo "\n";

    tty(15,0,fixLen('Numero esclusi:',20));
    tty(10,0,$data['cox']['cnExt']) ;
    echo "\n";

    tty(15,0,fixLen('Tempo extra exc:',20));
    tty(10,0,secondsToTime($data['cox']['cnExtT'] ) ) ;
    echo "\n";

    tty(15,0,fixLen('Tempo festivo:',20));
    tty(10,0,round($data['cox']['cnFes'] / 3600,2) . ' / ' . ceil($data['cox']['cnFes'] / 3600) ) ;
    echo "\n";

    tty(15,0,fixLen('Numero festivi:',20));
    tty(10,0,$data['cox']['cxFes']) ;
    echo "\n";

    tty(15,0,fixLen('Numero festivi Toc.:',20));
    tty(10,0,$data['cox']['dxFes']) ;
    echo "\n";

    $timeLen = $data['timeTo'] - $data['timeFrom'];
    $allDaysCount = intval($timeLen / 86400);
    $allCellCount = intval($timeLen / 3600);
    $daysCount = count($data['days']);

    tty(15,0,fixLen('Caselle totali:',20));
    tty(10,0,$allCellCount) ;
    echo "\n";

    tty(15,0,fixLen('Giorni totali:',20));
    tty(10,0,$allDaysCount) ;
    echo "\n";

    tty(15,0,fixLen('Giorni visualizati:',20));
    tty(10,0,$daysCount) ;
    echo "\n";

    pluginCall('outSummary',true,$data);

    tty(15,0,fixLen('Intervallo date:',20));
    tty(10,0,gmdate('d/m/Y',$data['timeFrom']) . ' - ' . gmdate('d/m/Y',$data['timeTo'])) ;
    echo "\n\n";
    tty(10,0,'Grafici:');
    echo "\n";

    $usedCells = $data['cox']['cnCel'];
    $pCell = round(getPerc($usedCells, $allCellCount,1000), 2);
    $pDays = round(getPerc($daysCount , $allDaysCount,1000),2);

    $thr = getConfig('count.thrPermMaxCas',250);
    tty(15,0,fixLen('Livello caselle:',20));
    tty(10,0,fixLen($pCell,8));
    tty(7,0,'%%');
    tty(1,0,' │');
    echo graph($pCell,1000,30,3,$thr,13, 13,1);
    tty(1,0,'│');
    echo "\n";

    $thr = getConfig('count.thrPermMaxDay',250);
    tty(15,0,fixLen('Livello giorni:',20));
    tty(10,0,fixLen($pDays,8));
    tty(7,0,'%%');
    tty(1,0,' │');
    echo graph($pDays,1000,30,3,$thr,13, 13,1);
    tty(1,0,'│');
    echo "\n";

    $thr = getConfig('count.thrPermDayHMaxTot',500);
    $dMax = getConfig('day.dayHMax',8);
    $maxCellsThr = $allDaysCount * $dMax;
    $pMax = round(getPerc($usedCells,$maxCellsThr,1000),2);

    tty(15,0,fixLen('Livello tot.:',20));
    tty(10,0,fixLen($pMax,8));
    tty(7,0,'%%');
    tty(1,0,' │');
    echo graph($pMax,1000,30,3,$thr,13, 13,1);
    tty(1,0,'│');
    echo "\n";

    $thr = getConfig('count.thrPermDayHMaxLav',800);
    $dMax = getConfig('day.dayHMax',8);
    $maxCellsThr = $daysCount * $dMax;
    $pMax = round(getPerc($usedCells,$maxCellsThr,1000),2);

    tty(15,0,fixLen('Livello lav.:',20));
    tty(10,0,fixLen($pMax,8));
    tty(7,0,'%%');
    tty(1,0,' │');
    echo graph($pMax,1000,30,3,$thr,13, 13,1);
    tty(1,0,'│');
    echo "\n";


    $thr = getConfig('count.thrUsedFest',8);
    $p = intval(($data['cox']['fkFes'] / ($data['cox']['cxFes'] > 0 ? $data['cox']['cxFes'] : 1)) * 100);
    $c = $p >= $thr ? 12 : 7;
    tty(15,0,fixLen('Numero festivi RC.:',20));
    tty(10,0,fixLen($data['cox']['fkFes'],5));
    tty($c,0,fixLen($p,3));
    tty(7,0,' %');
    tty(1,0,' │');
    echo graph($p,100,30,4,$thr,12,13,3);
    tty(1,0,'│');
    echo "\n";
    pluginCall('outSummaryGraph',true,$data);
    echo "\n";
    tty(10,0,'Livelli:');
    echo "\n";


    $thr = [ 25,20,25,10,2 ];

    for ($i =0 ;$i <5;$i++) {

        $thr[$i] = getConfig("count.thrPLevel{$i}",$thr[$i]);

        tty(15,0,fixLen("Livello $i:",20));
        tty(10,0,fixLen($data['mix'][$i],6));
        tty(7,0,' / ');
        tty(10,0,fixLen($data['mixAll'][$i],6));
        tty(1,0,'│');

        if ($i == 0) {

            $cLo = 4;
            $cHi = 3;
            $cThr = 10;

        } else {

            $cLo = 3;
            $cHi = 4;
            $cThr = 13;

        }

        if ($data['pMix'][$i] > $thr[$i]) {
            $c = $cThr;
        } elseif ($data['pMix'][$i] > 10) {
            $c = $cHi;
        } else {
            $c = $cLo;
        }

        echo graph($data['pMix'][$i],100,10, $c,$thr[$i], $cThr,12);
        tty(1,0,'│');
        tty($c,0,fixLen($data['pMix'][$i],4));
        tty(7,0,'%');

        pluginCall('outSummaryLevel',true,$data, $i);

        echo "\n";
    }

    if (is_array($data['file']) and isset($OPT['v'])) {
        echo "\n";
        tty(15,0,'File inclusi:');
        echo "\n";
        $arr = array_unique($data['file']);

        foreach ($arr as $item) {
            tty(3,0," $item");
            echo "\n";
        }

    }

}

function outLegend($data) {
    global $SETTINGS;
    if (!isset($data['added']) or count($data['added']) == 0) return;

    echo "\n";
    if ($SETTINGS['hr']) hr();
    tty(14,0,'Totale progetti:');
    echo "\n";

    $max = isset($data['cntMap']) ? $data['cntMap'] : -1;
    $max = $max > 0 ? $max : 1;

    $ax = 0;
    $list = [];
    $maxVal = 1;
    foreach ($data['added'] as $item) {

        $x = isset($data['totMap'][$item['id']]) ? $data['totMap'][$item['id']] : 0;
        $maxVal = max($maxVal,$x);

        $x = str_pad(base_convert($x,10,36),6,'0',STR_PAD_LEFT);
        $x.= str_pad(base_convert($ax++,10,36),2,'0',STR_PAD_LEFT);

        $list[$x] = $item;

    }

    krsort($list);

    $typeList = [];

    foreach ($list as $item) {
        echo " {$item['char']} ";

        $x = isset($data['totMap'][$item['id']]) ? $data['totMap'][$item['id']] : -1;
        if ($x == -1 or $max == -1) {

            tty(8,0,fixLen("?",5));
            tty(8,0,' % ');
            tty(1,0,'│');
            tty(8,0, str_repeat('·',20));
            tty(1,0,'│');
            tty(8,0, str_repeat('·',8));
            tty(1,0,'│ ');
            tty(8,0,fixLen('?',6));

        } else {

            $p = round(getPerc($x , $max),1);
            tty(10,0,fixLen("$p",5));
            tty(7,0,' % ');
            tty(1,0,'│');
            echo graph($x,$max,20,3);
            tty(1,0,'│');
            echo graph($x,$maxVal,8,3);
            tty(1,0,'│ ');
            tty(10,0,fixLen($x,6));

        }

        tty(1,0,'│ ');

        pluginCall('outLegend',true, $item, $data);

        echo "{$item['char']} ";
        tty(10,0,fixLen($item['name'],20));
        echo " ";

        $projType = isset($item['type']) ? $item['type'] : null;
        $projInfo = isset($projType) ? getProjectTypeInfoByClass($projType) : null;

        if ($projInfo) {

            if (!isset($typeList[$projInfo['name']])) {
                $typeList[$projInfo['name']] = $projInfo;
                $typeList[$projInfo['name']]['cx'] = 0;
            }

            $typeList[$projInfo['name']]['cx']++;

            echo $projInfo['char'];

        } else {

            if (!isset($typeList['?'])) {
                $typeList['?'] = [
                    'cx'    =>  0,
                    'char'  =>  ttyS(8,0,'?')
                ];
            }

            $typeList['?']['cx']++;

            tty(8,0,'?');

        }

        echo " ";
        tty(15,0,$item['title']);
        echo "\n";

    }

    if ($SETTINGS['hr']) hr();
    tty(14,0,'Tipi di progetto:');
    echo "\n";
    if ($SETTINGS['hr']) hr();
    ksort($typeList);
    $tot = 0;
    foreach ($typeList as $item) {

        tty(1,0,'│');
        echo $item['char'];
        tty(1,0,'│');
        if (isset($item['name'])) {
            tty(10,0,fixLen($item['name'],20));
            tty(1,0,'│');
            tty(11,0,fixLen($item['title'],32));
        } else {
            tty(8,0,fixLen('?',20));
            tty(1,0,'│');
            tty(8,0,fixLen('(Non supportato)',32));
        }
        tty(1,0,'│');
        tty(15,0,fixLen($item['cx'],5));
        tty(1,0,'│');
        echo "\n";

        $tot+=$item['cx'];

    }

    tty(1,0,'│');
    echo ' ';
    tty(1,0,'│');
    echo str_repeat(' ',20);
    tty(1,0,'│');
    echo str_repeat(' ',32);
    tty(1,0,'│');
    echo str_repeat(' ',5);
    tty(1,0,'│');
    echo "\n";

    tty(1,0,'│');
    echo ' ';
    tty(1,0,'│');
    echo str_repeat(' ',20);
    tty(1,0,'│');
    tty(15,0,fixLen('Totale',32));
    tty(1,0,'│');
    tty(15,0,fixLen($tot,5));
    tty(1,0,'│');
    echo "\n";

    if ($SETTINGS['hr']) hr();

}

function getProjectsByList($curList) {
    global $CONF;

    $out = [];

    $nameMap = isset($CONF['projectsNameMap']) ? $CONF['projectsNameMap'] : [];
    foreach ($nameMap as $name => $path) {
        $rPath = getProjectPath($path,true);
        if ($rPath === false) quit("Errore percorso: $path nella definizione del progetto $name","projectsNameMap.{$name}");
        $nameMap[$name] = $rPath;
    }

    $ovrCount = 0;
    foreach ($curList as $ptr => $fileXML) {

        if (!is_string($fileXML)) quit("Errore lista progetti alla posizione $ptr");

        $projPath = getProjectPath($fileXML,true);
        if ($projPath === false) quit("Errore percorso: $fileXML alla posizione $ptr");

        $nameId = array_search($projPath,$nameMap);

        if ($nameId) {
            $isGen = false;
        } else {
            $nameId = getProjectNameByPath($fileXML);
            if (isset($out[$nameId])) {
                $ovrCount++;
                $nameId.='/'.strtoupper(base_convert($ovrCount,10,36));
            }
            $isGen = true;
        }

        if (isset($out[$nameId])) {

            if ($out[$nameId]['path'] != $projPath) {
                quit("Sovrapposizione nomi di progetto con $nameId");
            }

        }

        $optPath = getProjectConfigPath($fileXML);

        $out[$nameId] = [
            'file'  =>  $projPath,
            'name'  =>  $nameId,
            'isGen' =>  $isGen,
            'path'  =>  $projPath,
            'title' =>  getConfig("projectsTitleMap.{$nameId}",''),
            'cFile' =>  getOptProjectFile($optPath,'project.conf'),
            'char'  =>  getConfig("projectsSymbolMap.{$nameId}",''),
            'id'    =>  setProjectId($nameId)
        ];

        if ($out[$nameId]['title'] == '') $out[$nameId]['title'] = getProjectNameByPath($fileXML,false);

        if ($out[$nameId]['cFile']) {
            $cfg = parse_ini_file($out[$nameId]['cFile']);
            if (!$cfg) quit("Errore configurazione file: {$out[$nameId]['cFile']}");
        } else {
            $cfg = [];
        }

        if (isset($cfg['title']) and $cfg['title'] != '') $out[$nameId]['title'] = $cfg['title'];
        if (isset($cfg['char']) and $cfg['char'] != '') $out[$nameId]['char'] = $cfg['char'];

        $out[$nameId]['conf'] = $cfg;

        if ($out[$nameId]['char']) {
            $out[$nameId]['char'] = setProjectSymbolFromSource($nameId,$out[$nameId]['char']);
        } else {
            $out[$nameId]['char'] = createProjectSymbolByName($nameId);
        }

    }

    return $out;

}

function outProjMerge($prog, $tag, $oper, $col) {

    tty(3,0,' '.fixLen($tag,32));
    tty($col,0,' '.fixLen($oper,8).' ');
    tty(7,0,$prog['file']);
    echo "\n";

}

function outProjectsTable($list, $selected = null) {

    tty(15,0,'Tabella progetti:');
    echo "\n\n";
    tty(1,0,'┌─┬─┬'.str_repeat('─',32).'┬'.str_repeat('─',40).'┐');
    echo "\n";

    $cx = 0;
    foreach ($list as $proj) {

        $isSel = $selected === $proj['name'];

        if ($cx > 0) {
            tty(1,0,'├─┼─┼'.str_repeat('─',32).'┼'.str_repeat('─',40).'┤');
            echo "\n";
        }
        $cx++;

        $typeInfo = getProjectTypeInfoByPath($proj['path']);

        tty(1,0,'│');
        echo $proj['char'];
        tty(1,0,'│');

        if ($typeInfo) {
            echo $typeInfo['char'];
        } else {
            tty(12,0,'S');
        }

        tty(1,0,'│');
        tty($isSel ? 10 : 7 ,0, fixLen($proj['name'],32));
        tty(1,0,'│');
        tty($isSel ? 14 : 10,0, fixLen($proj['title'],40));
        tty(1,0,'│');
        echo "\n";

    }

    tty(1,0,'└─┴─┴'.str_repeat('─',32).'┴'.str_repeat('─',40).'┘');
    echo "\n";
    echo "\n";

}

function outProjectsListLine($pidMapX, $maxWidth, $scaleFactor = 1) {
    global $SETTINGS;
    global $OPT;

    $doSymbol = !isset($OPT['compatto']);
    if (isset($OPT['simboli'])) $doSymbol = true;

    $num = count($pidMapX);
    if (!$num) return;

    $cLen = 0;
    $aLen = 0;
    $count = 0;
    $lpMaxWidth = $maxWidth - 1;

    $str = '';

    foreach ($pidMapX as $itemId => $hours) {

        $hours = intval($hours * $scaleFactor);

        $name = getProjectById($itemId);
        $len = mb_strlen($name);
        $cLen+= $len;

        if ($doSymbol) $cLen+=2;

        if ($cLen > $lpMaxWidth) break;

        if ($doSymbol) {
            $str.=getProjectSymbol($name).' ';
        }

        if (isset($SETTINGS['nameLevels'])) {

            $colors = 7;
            foreach ($SETTINGS['nameLevels'] as $level => $levelColors) {
                if ($hours >= $level) {
                    $colors = $levelColors;
                    break;
                }
            }

            $str .= ttyS($colors & 15,$colors >> 4,$name);

        } else {
            $str .= ttyS($count & 1 ? 8:7,0,$name);
        }

        $aLen = $cLen;
        $count++;
        $cLen++;
        if ($cLen > $lpMaxWidth) break;
        $str .= ' ';
        $aLen = $cLen;
    }

    if ($num > $count) {
        $fill = $maxWidth - $aLen;
        $fill--;
        if ($aLen) $str .= str_repeat(' ',$fill);
        $str .= ttyS(0,13,'+');
    }

    echo $str;

}

function outText($text, $defaultWidth = 80, $defaultColor = 7) {

    $mySelf = getSelfName();
    $tokens = tagParser($text,'<{#','}>');
    $text = '';
    foreach ($tokens as $token) {

        if ($token[0]) {
            $par = explode(' ',trim($token[1]));
            $cmd = array_shift($par);

            if (preg_match('/^[A-Za-z]{1}[A-Za-z0-9]+$/',$cmd)) {
                $cmd = ucfirst($cmd);
                $cmd = "helpTextCmd{$cmd}";

                if (function_exists($cmd)) {
                    $text.= call_user_func_array($cmd,$par);
                    continue;
                }

                $par = pluginCall($cmd,true,$par);
                if ($par) {
                    $text.=implode("\n",$par);
                }

            }

        } else {
            $text.=$token[1];
        }

    }

    $text = str_replace('%%ME%%',$mySelf,$text);
    $text = str_replace("\r",'',$text);
    $text = str_replace("\t",' ',$text);
    $text = str_replace("~\n",'',$text);
    $text = str_replace("\n~",'',$text);
    $text = str_replace("\t", ' ',$text);
    $text = preg_replace('/\\x20+/',' ',$text);

    $tokens = tagParser($text);
    $color = $defaultColor;
    $left = 0;
    $width = $defaultWidth;
    $cLen = 0;
    $leftColor = $defaultColor;
    $cWord = 0;

    foreach ($tokens as $token) {

        if ($token[0]) {

            $par = explode(' ',trim($token[1]),3);
            switch ($par[0]) {

                case 'title':
                case 'desc':
                case 'data':
                    break;

                case 'head':
                    if (count($par) == 3) {
                        $x = intval($par[1]);
                        if ($x > 1) $defaultWidth = $x;

                        $x = hexdec($par[2]);
                        if ($x > 0) $defaultColor = $x;

                        if ($cLen) echo "\n";
                        $color = $defaultColor;
                        $width = $defaultWidth;
                        $left = 0;
                        $cLen = 0;
                        $cWord = 0;
                        $leftColor = $defaultColor;

                        break;
                    }
                    outBadTag($par);
                    break;

                case '/':
                case 'reset':
                    if ($cLen) echo "\n";
                    $color = $defaultColor;
                    $width = $defaultWidth;
                    $left = 0;
                    $cLen = 0;
                    $cWord = 0;
                    $leftColor = $defaultColor;

                    if (isset($par[1])) {
                        $width = intval($par[1]);
                        $width = $width > 0 ? $width : $defaultWidth;
                    }
                    break;

                case 'L':
                case 'left':

                    $left = isset($par[1]) ? intval($par[1]) : 0;
                    $width = $defaultWidth - $left;
                    $cLen = 0;
                    $cWord = 0;
                    echo "\n";

                    if ($left) {
                        if (isset($par[2])) {
                            tty($leftColor & 15, $leftColor >> 4,fixLen($par[2],$left));
                        } else {
                            tty($leftColor & 15, $leftColor >> 4,str_repeat(' ',$left));
                        }
                    }

                    break;

                case '-':
                case 'hr':
                    if ($cLen) echo "\n";
                    $cLen = 0;
                    $cWord = 0;
                    $x = 0;
                    if (isset($par[1])) $x = hexdec($par[1]) & 255;
                    $x = $x > 0 ? $x : $color;
                    tty($x & 15, $x >> 4, str_repeat('─',$width));
                    echo "\n";
                    break;

                case '.':
                    if ($cLen) echo "\n";
                    $cLen = 0;
                    $cWord = 0;
                    break;

                case 'E':
                case 'center':

                    if (count($par) == 3) {
                        if ($cLen) echo "\n";
                        $cLen = 0;
                        $cWord = 0;
                        $x = hexdec($par[1]) & 255;
                        $x = $x > 0 ? $x : $color;
                        $w = mb_strlen($par[2]);
                        $w = ($width >> 1) - ($w >> 1);
                        tty($color & 15, $color >> 4 , str_repeat(' ' ,$w));
                        tty($x & 15, $x >> 4, $par[2]);
                        echo "\n";
                        break;
                    }

                    outBadTag($par);
                    break;

                case 'M':
                case 'margin':
                    if (count($par) == 3) {

                        $ext = explode(' ',$par[2],2);

                        if (count($ext) == 2) {

                            $left = intval($par[1]);
                            $width = $defaultWidth - $left;
                            $margin = intval($ext[0]);

                            $cLen = 0;
                            $cWord = 0;
                            echo "\n";
                            tty($leftColor & 15, $leftColor >> 4,fixLen(str_repeat(' ',$margin) . $ext[1],$left));
                            break;
                        }

                    }
                    outBadTag($par);
                    break;

                case 'C':
                case 'color':
                    if (isset($par[1])) $x = hexdec($par[1]) & 255;
                    if ($x) $color = $x;
                    if (isset($par[2])) $x = hexdec($par[2]) & 255;
                    if ($x) $leftColor = $x;
                    break;

                default:
                    outBadTag($par);

            }

        } else {

            $lines = explode("\n",$token[1]);
            $lastLine = count($lines) - 1;

            foreach ($lines as $cLine => $line) {

                $words = explode(' ',$line);

                foreach ($words as $word) {

                        if (@$word[0] == '{') {

                        if (preg_match('/^\\{\\{[0-9A-Fa-f]{1,2}\\}\\}$/',$word)) {
                            $color = hexdec(trim($word,'{}'));
                            if (!$color) $color = $defaultColor;
                            continue;
                        } elseif (preg_match('/^\\{\\{(?<s>.{1,4})(?<c>[0-9A-F]{2})\\}\\}$/',$word,$match)) {

                            $c = hexdec($match['c']);
                            $word = mb_substr($match['s'],0,1);

                            if ($cWord > 0) {
                                $fAdd = true;
                                $cLen++;
                            } else {
                                $fAdd = false;
                            }

                            $cLen++;
                            $cWord++;

                            if ($cLen >= $width) {
                                $cLen = 0;
                                $cWord = 0;
                                echo "\n";
                                tty($leftColor & 15, $leftColor >> 4, str_repeat(' ',$left));
                            }

                            if ($fAdd) tty($color&15,$color>>4,' ');
                            tty($c & 15, $c >> 4, $word);
                            continue;

                        }

                    }

                    $wLen = mb_strlen($word);
                    if ($cWord > 0) $cLen++;
                    $cLen += $wLen;

                    if ($cLen >= $width) {
                        $cLen = 0;
                        $cWord = 0;
                        echo "\n";
                        tty($leftColor & 15, $leftColor >> 4, str_repeat(' ',$left));
                    }

                    if ($cWord > 0) $word = " $word";
                    tty($color & 15, $color >> 4, $word);
                    $cWord++;

                }

                if ($cLine != $lastLine) {
                    echo "\n";
                    $cLen = 0;
                    $cWord = 0;
                    tty($leftColor & 15, $leftColor >> 4, str_repeat(' ',$left));
                }

            }

        }

    }

    echo "\n";

}

function outBadTag($par) {
    $par = implode(' ',$par);
    tty(4,12,'<{');
    tty(0,12, $par);
    tty(4,12,'}>');
}

function outPres() {
    echo "\n";
    tty(14,0,PROG_NAME .' ');
    tty(7,0,"ver ");
    tty(10,0,"2.0");
    tty(3,0,"B");
    echo "\n ";
    tty(11,0,"(C) 2007-2025 by EPTO");
    echo "\n ";
    tty(15,0,"Tutti i diritti sono miei e me li tengo!");
    echo "\n";
    pluginCall('outPres',true);
    echo "\n";
}

