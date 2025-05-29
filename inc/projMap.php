<?php

function getProjectNameByPath($pathOrName, $normalize = true) {

    if (pathinfo($pathOrName,PATHINFO_EXTENSION) == 'xml') {
        $pathOrName = dirname($pathOrName);
        $pathOrName = dirname($pathOrName);
    }

    $name = basename($pathOrName);
    if ($normalize) {

        $name = trim($name);
        $name = preg_replace('/[^A-Za-z0-9\\-_\\.]+/','_',$name);

        if ($name == '') {
            $pathOrName = fixPath($pathOrName);
            $name = crc32($pathOrName);
            $name^= $name >> 1;
            $name&= 0x7FFFFFFF;
            $name = base_convert($name,10,36);
            $name = strtoupper($name);
            return '@'.str_pad($name,6,'0',STR_PAD_LEFT);
        }
    }

    return $name;

}

function getProjectPath($pathOrName, $noError = false) {

    $x = realpath($pathOrName);
    if (!$x or !file_exists($x)) {
        if ($noError) return false;
        quit("Errore percorso: $pathOrName");
    }
    $pathOrName = $x;

    if (pathinfo($pathOrName,PATHINFO_EXTENSION) == 'xml') {
        $pathOrName = dirname($pathOrName);
        $pathOrName = dirname($pathOrName);

    }

    $pathOrName = fixPath($pathOrName);

    if (!is_dir($pathOrName)) {
        if ($noError) return false;
        quit("Errore percorso: $pathOrName");
    }

    return $pathOrName;

}

function getProjectConfigPath($pathOrName, $ofFile = null) {

    if (pathinfo($pathOrName,PATHINFO_EXTENSION) == 'xml') {
        $pathOrName = dirname($pathOrName);
        $pathOrName = dirname($pathOrName);

    }

    $pathOrName = fixPath($pathOrName);
    $pathOrName.= '.' . PROG_NAME . '/';

    if (!file_exists($pathOrName) or !is_dir($pathOrName)) return false;

    if ($ofFile) {
        $pathOrName.=$ofFile;
        if (!file_exists($ofFile) or is_dir($ofFile)) return false;
    }

    return $pathOrName;

}

function getOptProjectFile($path, $file) {
    if (!$path) return false;
    $path = fixPath($path);
    $file = $path . $file;
    if (file_exists($file) and !is_dir($file)) return $file;
    return null;
}

function addProjectInList() {
    global $CONF;
    global $OPT;
    global $SETTINGS;

    if (!isset($OPT['aggiungi']) or isset($SETTINGS['projList'])) doHelp(); // Quit

    $path = getProjectPath($OPT['aggiungi']);
    if (!$path or !file_exists($path) or !is_dir($path)) quit('Errore percorso progetto');
    $dir = $path;

    if (!getProjectTypeByPath($path)) quit('Errore tipo di progetto');
    $name = isset($OPT['nome']) ? $OPT['nome'] : getProjectNameByPath($path);
    $title = isset($OPT['titolo']) ? $OPT['titolo'] : $name;

    getProjectsByList($CONF['projectsNameMap']); // Inizializza i simboli

    if (isset($OPT['simbolo'])) {

        setProjectSymbolFromSource($name,$OPT['simbolo']);
        $char = getProjectSymbolSource($name);

    } else {

        $char = getProjectSymbolSource($name);
        if ($char === null) {
            createProjectSymbolByName($name);
            $char = getProjectSymbolSource($name);
        }

    }

    $rmList = [];
    foreach ($CONF['projectsNameMap'] as $cName => $cPath) {
        $cPath = realpath($cPath);
        if (!$cPath) continue;
        $cPath = getProjectPath($cPath);
        if ($cPath == $dir) $rmList[] = $cName;
    }

    $rmList[] = $name;
    foreach ($rmList as $item) {
        unset($CONF['projectsNameMap'][$item]);
        unset($CONF['projectsTitleMap'][$item]);
        unset($CONF['projectsSymbolMap'][$item]);
    }

    $CONF['projectsNameMap'][$name] = $path;
    $CONF['projectsTitleMap'][$name] = $title;
    $CONF['projectsSymbolMap'][$name] = $char;

    saveProjectList();
    initSymbols(true);
    doProjectList($name); // quit

}

function addAllProjects() {
    global $CONF;
    global $SETTINGS;
    global $OPT;

    if (!isset($SETTINGS['projList'])) doHelp(); // Quit

    $list = getProjectsByList($SETTINGS['projList']);

    if (!isset($OPT['u'])) {
        $CONF['projectsNameMap'] = [];
        $CONF['projectsTitleMap'] = [];
        $CONF['projectsSymbolMap'] = [];
    }

    foreach ($list as $name => $data) {
        $CONF['projectsNameMap'][$name] = $data['file'];
        $CONF['projectsTitleMap'][$name] = $data['title'];
        $CONF['projectsSymbolMap'][$name] = getProjectSymbolSource($name);
    }

    if (!$list) quit('Nessun progetto configurato');

    saveProjectList();
    outProjectsTable($list);
    dispatch();
    exit();

}

function saveProjectList() {
    global $SETTINGS;
    global $CONF;

    $info = "Non modificare questo file!\nÈ generato automaticamente.";
    $sections = ['projectsNameMap','projectsTitleMap','projectsSymbolMap'];

    foreach ($sections as $section) {
        if (isset($CONF[$section])) ksort($CONF[$section]);
    }

    if (isset($SETTINGS['myConfDir'])) {

        if (!file_exists($SETTINGS['myConfBase'])) {

            if (!mkdir($SETTINGS['myConfBase'],0777,true)) quit("Non riesco a salvare la configurazione in {$SETTINGS['myConfBase']}");

            $path = $SETTINGS['myConfBase'] .'profile';
            if (!mkdir($path,0777,true)) quit("Non riesco a salvare la configurazione in $path");

            $path = $SETTINGS['myConfBase'] . 'config.conf';

            if (!file_exists($path)) {
                $template = "{$SETTINGS['myPath']}res/config.template";
                if (!copy($template,$path)) quit("Non riesco a copiare i template di configurazione");
            }

        }

        if (!file_exists($SETTINGS['myConfDir'])) {
            if (!mkdir($SETTINGS['myConfDir'],0777,true)) quit("Non riesco a salvare la configurazione in {$SETTINGS['myConfDir']}");
        }


    } else {
        quit("Nessuna directory di configurazione rilevata dalle variabili di ambiente.");
    }

    saveSubConfig(
        $sections,
        "{$SETTINGS['myConfDir']}projectsList.conf",
        $info);
}

function removeProject() {
    global $OPT;
    global $CONF;

    $rmList = [];
    if (preg_match('/^[A-Za-z0-9]+$/',$OPT['rimuovi'])) {

        $rmList[] = $OPT['rimuovi'];

    } else {

        $dir = getProjectPath($OPT['rimuovi']);

        foreach ($CONF['projectsNameMap'] as $cName => $path) {
            $cPath = getProjectPath($path);
            if ($cPath == $dir) $rmList[] = $cName;
        }
    }

    foreach ($rmList as $item) {
        unset($CONF['projectsNameMap'][$item]);
        unset($CONF['projectsTitleMap'][$item]);
        unset($CONF['projectsSymbolMap'][$item]);
    }

    saveProjectList();

    initSymbols(true);
    doProjectList();  // quit
}

function doProjectList($selected = null) {
    global $CONF;

    if (isset($CONF['projectsNameMap']) and count($CONF['projectsNameMap']) > 0) {
        echo "\n";
        $list = getProjectsByList($CONF['projectsNameMap']);
        outProjectsTable($list, $selected);
        dispatch();
        exit();

    }

    quit('Nessun progetto configurato');

}
