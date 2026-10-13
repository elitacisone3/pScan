#!/usr/bin/php
<?php
/*
 * pScan
 * Copyright (C) 2007-2026 by EPTO
 *
 * This is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This source code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this source code; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require 'inc/globals.php';
require 'inc/settings.php';
require 'inc/function.php';
require 'inc/plugin.php';
require 'inc/calculator.php';
require 'inc/symbol.php';
require 'inc/projMap.php';
require 'inc/output.php';
require 'inc/help.php';
require 'inc/dataReader.php';
require 'inc/weekly.php';
require 'inc/projectStats.php';
require 'inc/appBuilder.php';
require 'inc/appExport.php';
require 'inc/appConfig.php';


initProc();

if (isset($OPT['app-config']) or isset($OPT['app-title']) or isset($OPT['app-desc'])) {
    if (!isset($OPT['build-app'])) quit("--app-config, --app-title e --app-desc sono validi solo insieme a --build-app");
}

if (isset($OPT['app-config'])) {
    parseAppConfigFile($OPT['app-config']);
}

pluginCall('onBeforeInitSymbols',true);
initSymbols();

pluginCall('onBeforeStart',true);
if (!isset($OPT['q'])) outPres();

pluginCall('onStart',true);

if (isset($OPT['help'])) doHelpFile($OPT['help']); // Quit

if (isset($SETTINGS) and @$SETTINGS['noProj'] and !isset($OPT['load-dump'])) {

    if (isset($OPT['palette'])) helpPalette(); // Quit
    if (isset($OPT['tabella'])) doProjectList(); // Quit
    if (isset($OPT['aggiungi'])) addProjectInList(); // Quit
    if (isset($OPT['rimuovi'])) removeProject(); // Quit

    if (pluginCall('onDefaultOption',true)) {
        dispatch();
        exit();
    }

    doHelp();
    exit(); // Gia fatto!!!

} elseif (isset($OPT['aggiungi'])) {

    if (isset($OPT['rimuovi'])) doHelp();
    if ($OPT['aggiungi'] != 'TUTTO') doHelp();
    if (!isset($SETTINGS['projList'])) doHelp();

    if (pluginCall('onAddAllProjects',true)) {
        dispatch();
        exit();
    }

    addAllProjects();
    exit(); //
}

if (isset($OPT['mostra'])) {
    if (isset($SETTINGS['projList'])) {
        echo implode("\n",$SETTINGS['projList']);
        dispatch();
        exit();
    } else {
        quit("Nulla da mostrare con questi parametri");
    }
}

if (pluginCall('onStartCalculator',true)) {
    dispatch();
    exit();
}

if (isset($OPT['build-app'])) {
    // Attiva la classificazione per-progetto (simboli/legenda) indipendentemente
    // da --mappa, perché --build-app ne ha sempre bisogno per i tab Mensile/Giornaliero.
    $OPT['map'] = true;
    $SETTINGS['fullNames'] = true;
}

$data = getAllData();
pluginFixArray($data,'onDataComplete',true,$data);

if (isset($OPT['mesi'])) {

    pluginCall('beforeMonths',true, $data);
    $byMonth = getByMonthData($data);
    if (!isset($OPT['dati'])) outMonthly($byMonth);
    $data['byMonth'] = $byMonth;

} else {

    pluginCall('beforeDays',true, $data);
    if (!isset($OPT['dati'])) outDaily($data);

}

if (!isset($OPT['G'])) {
    outSummary($data);
    if (isset($OPT['map'])) outLegend($data);
}

if (isset($OPT['salva'])) {

    pluginFixArray($data, 'onSave',true, $data, $OPT['salva']);

    if (!pluginCall('saveHook',true,$data,$OPT['salva'])) {
        $raw = json_encode($data,JSON_PRETTY_PRINT);
        if (pathinfo($OPT['salva'],PATHINFO_EXTENSION) == 'gz') $raw = gzencode($raw);
        file_put_contents($OPT['salva'],$raw);
    }
}

if (isset($OPT['out'])) {

    pluginFixArray($data, 'onSaveData',true, $data, $OPT['out']);

    if (!pluginCall('saveDataHook',true,$data,$OPT['out'])) {

        (function() use($data, $OPT){

            $out = [];
            list($isCSV, $isBin, $isPack) = sniffOutExt($OPT['out']);

            if (isset($OPT['out-pad'])) {

                $dayArray = [];
                $prevDayId = null;

                foreach ($data['days'] as $dayId => $dData) {

                    $dDelta = ($dayId - $prevDayId) - 1;
                    if ($prevDayId !== null and $dDelta > 0) {

                        for ($pad = $prevDayId; $pad <= $dayId; $pad++) {

                            $tcr = 86400 * $pad;

                            $wd = date('w',$tcr);
                            $f = $wd == 0 || $wd == 6;

                            $dayArray[$pad] = [
                                'date'  => date('d/m/Y',$tcr),
                                'month' => date('m',$tcr),
                                'day'   => date('d',$tcr),
                                'year'  => date('Y',$tcr),
                                'wDay'  => $wd,
                                'tot'   =>  0,
                                'fest'  =>  $f,
                                'map'   => array_pad([],24,0)
                            ];

                        }

                    }

                    $dayArray[$dayId] = $dData;
                    $prevDayId = $dayId;

                }

                ksort($dayArray);

            } else {

                $dayArray = $data['days'];

            }


            foreach ($dayArray as $dayId => $oDay) {

                $bin = 0;

                foreach ($oDay['map'] as $ptr => $val) {
                    if ($val > 0) $bin |= 1 << $ptr;
                }

                if ($isBin) {
                    $row = pack('nCCNCNCCC', $oDay['year'], $oDay['month'], $oDay['day'], $dayId, $oDay['tot'], $bin, $oDay['wDay'] , $oDay['fest'] ? 1:0, 0);
                } else {
                    $row = ["{$oDay['month']}/{$oDay['day']}/{$oDay['year']}" ,$oDay['year'], $oDay['month'], $oDay['day'], $dayId, $oDay['tot'], $bin, intval($oDay['wDay']),$oDay['fest'] ? 1:0 ];
                }

                if ($isCSV) $row = implode(',',$row);
                $out[] = $row;
                $row = null;

            }

            $fields = ['date', 'year', 'month', 'day', 'dayId', 'tot', 'bin24H', 'wDay', 'fest'];

            if ($isBin) {

                $out = implode('',$out);

            } elseif ($isCSV) {

                array_unshift($out,implode(',',$fields));
                $out = implode("\r\n",$out);

            } else {

                $out = [
                    'fields'    =>  $fields,
                    'data'      =>  $out
                ];

                $out = json_encode($out,JSON_PRETTY_PRINT);

            }

            if ($isPack) $out = gzencode($out);

            file_put_contents($OPT['out'],$out);


        })();

    }
}

if (isset($OPT['out-simple'])) {

    $weeklySimple = computeWeeklyAggregation($data);
    pluginFixArray($weeklySimple, 'onSaveSimple',true, $weeklySimple, $OPT['out-simple']);

    if (!pluginCall('saveSimpleHook',true,$weeklySimple,$OPT['out-simple'])) {
        writeOutSimple($weeklySimple, $OPT['out-simple']);
    }

}

if (isset($OPT['build-app'])) {
    buildApp($OPT['build-app'], $data);
}

pluginCall('onCompleteDispatch',true);
dispatch();
