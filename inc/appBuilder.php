<?php

const APP_DIR_TYPE_MARKER = 'pScan/HTML/app';

function copyDirRecursive($src, $dst) {

    if (!is_dir($src)) return;
    if (!is_dir($dst)) mkdir($dst, 0755, true);

    $items = scandir($src);

    foreach ($items as $item) {

        if ($item === '.' or $item === '..') continue;

        $s = $src . '/' . $item;
        $d = $dst . '/' . $item;

        if (is_dir($s)) {
            copyDirRecursive($s, $d);
        } else {
            copy($s, $d);
        }

    }

}

function rrmdirContents($dir) {

    $items = scandir($dir);

    foreach ($items as $item) {

        if ($item === '.' or $item === '..') continue;

        $path = $dir . '/' . $item;

        if (is_dir($path) and !is_link($path)) {
            rrmdirContents($path);
            rmdir($path);
        } else {
            unlink($path);
        }

    }

}

function buildApp($outDir, $data) {
    global $SETTINGS;
    global $OPT;

    $outDir = rtrim($outDir, '/\\');

    if (!file_exists($outDir)) {

        if (!mkdir($outDir, 0755, true)) quit("Impossibile creare la directory: $outDir");

    } elseif (!is_dir($outDir)) {

        quit("Il percorso esiste e non è una directory: $outDir");

    } else {

        $items = array_diff(scandir($outDir), ['.', '..']);

        if (count($items) > 0) {

            $markerFile = "$outDir/.dirType";
            $marker = file_exists($markerFile) ? trim(file_get_contents($markerFile)) : null;

            if ($marker !== APP_DIR_TYPE_MARKER) {
                quit("La directory di destinazione non è vuota e non risulta generata da --build-app: $outDir");
            }

            rrmdirContents($outDir);

        }

    }

    // Scritto subito, prima di ogni altro file: se la build viene interrotta,
    // il prossimo tentativo riconosce comunque la directory come "nostra".
    file_put_contents("$outDir/.dirType", APP_DIR_TYPE_MARKER);

    $srcBase = $SETTINGS['myPath'];

    copyDirRecursive("{$srcBase}app-src/css", "$outDir/css");
    copyDirRecursive("{$srcBase}app-src/js", "$outDir/js");

    $echartsSrc = "{$srcBase}res/vendor/echarts/echarts.min.js";
    if (file_exists($echartsSrc)) {
        if (!is_dir("$outDir/js/vendor")) mkdir("$outDir/js/vendor", 0755, true);
        copy($echartsSrc, "$outDir/js/vendor/echarts.min.js");
    }

    copyDirRecursive("{$srcBase}res/fonts", "$outDir/fonts");

    $title = isset($OPT['app-title']) ? $OPT['app-title'] : getAppConfig('page.title', 'Attività progetti');
    $desc  = isset($OPT['app-desc'])  ? $OPT['app-desc']  : getAppConfig('page.description', 'Dati generati da pScan');
    $theme = getAppConfig('page.theme', 'auto');
    if (!in_array($theme, ['auto', 'light', 'dark'])) quit("Valore non valido per page.theme (auto|light|dark): $theme");

    $weekly = computeWeeklyAggregation($data);
    $byMonth = getByMonthData($data);

    $payload = [
        'meta'          =>  [
            'title'         =>  $title,
            'desc'          =>  $desc,
            'generatedAt'   =>  date('c')
        ],
        'weekly'        =>  $weekly,
        'projectWeekly' =>  $data['projectWeekly'] ?? [],
        'projects'      =>  $data['projectsExport'] ?? [],
        'monthly'       =>  buildMonthlyExport($byMonth),
        'daily'         =>  buildDailyExport($data['days'] ?? [])
    ];

    $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
    $json = str_replace('</script>', '<\/script>', $json);

    $html = file_get_contents("{$srcBase}app-src/index.html");
    if ($html === false) quit("Template app-src/index.html non trovato");

    $footer = '<a href="https://github.com/elitacisone3/pScan" target="_blank" rel="noopener">'
        . 'pScan V' . PROG_VERSION . ' &copy; ' . date('Y') . ' EPTO'
        . '</a>';

    $themeAttr = $theme === 'auto' ? '' : (' data-theme="' . $theme . '"');

    $html = str_replace('<!--PSCAN_TITLE-->', htmlspecialchars($title, ENT_QUOTES, 'UTF-8'), $html);
    $html = str_replace('<!--PSCAN_DESC-->', htmlspecialchars($desc, ENT_QUOTES, 'UTF-8'), $html);
    $html = str_replace('<!--PSCAN_DATA_JSON-->', $json, $html);
    $html = str_replace('<!--PSCAN_FOOTER-->', $footer, $html);
    $html = str_replace('<!--PSCAN_THEME_ATTR-->', $themeAttr, $html);

    file_put_contents("$outDir/index.html", $html);

    if (getAppConfig('page.htaccess', true)) {

        $htaccess = implode("\n", [
            'AddDefaultCharset UTF-8',
            'Options -Indexes',
            '<IfModule mod_expires.c>',
            '    ExpiresActive On',
            '    ExpiresByType application/javascript "access plus 1 week"',
            '    ExpiresByType font/woff2 "access plus 1 month"',
            '    ExpiresByType font/woff "access plus 1 month"',
            '    ExpiresByType font/ttf "access plus 1 month"',
            '</IfModule>',
            ''
        ]);

        file_put_contents("$outDir/.htaccess", $htaccess);

    }

}
