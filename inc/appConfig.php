<?php

$APP_CONF = [];

function parseAppConfigFile($file) {
    global $APP_CONF;

    if (!file_exists($file)) quit("File di configurazione non trovato: $file");

    $ini = parse_ini_file($file, true);
    if ($ini === false) quit("Errore lettura: $file");

    $APP_CONF = array_replace_recursive($APP_CONF, $ini);

}

function getAppConfig($path, $default) {
    global $APP_CONF;

    $cur = $APP_CONF;
    $pathA = explode('.', $path);

    foreach ($pathA as $item) {

        if (isset($cur[$item])) {
            $cur = $cur[$item];
        } else {
            return $default;
        }

    }

    return resolveConfigValue($cur, $default, $path);

}
