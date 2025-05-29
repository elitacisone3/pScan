#!/usr/bin/php
<?php
/*
 * pScan
 * Copyright (C) 2007-2025 by EPTO
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


initProc();
pluginCall('onBeforeInitSymbols',true);
initSymbols();

pluginCall('onBeforeStart',true);
if (!isset($OPT['q'])) outPres();

pluginCall('onStart',true);

if (isset($OPT['help'])) doHelpFile($OPT['help']); // Quit

if (isset($SETTINGS) and @$SETTINGS['noProj']) {

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

pluginCall('onCompleteDispatch',true);
dispatch();
