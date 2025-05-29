<?php

function addPlugin($name, $bySetting = null, $isDefault = false) {
    global $SETTINGS;

    $name = ucfirst($name);
    if (!preg_match('/^[A-Za-z]{1}[A-Za-z0-9]{0,39}$/',$name)) quit("Nome plugin non valido: $name",$bySetting);

    $file = "{$SETTINGS['myPath']}plugins/{$name}.php";
    if (!file_exists($file) or !is_file($file)) quit("Errore caricamento plugin: $name",$bySetting);

    call_user_func(function ($_PLUGIN_FILE_) { require $_PLUGIN_FILE_;  }, $file);

    _loadPlugin($name,$bySetting,$isDefault);

}

function loadAllTypes() {
    global $SETTINGS;

    $done = glob("{$SETTINGS['myPath']}default/_*.php");

    foreach ($done as $file) {
        $name = pathinfo($file,PATHINFO_FILENAME);
        call_user_func(function ($_PLUGIN_FILE_) { require $_PLUGIN_FILE_;  }, $file);
        _loadPlugin($name,null,true);
    }

    $list = glob("{$SETTINGS['myPath']}default/*.php");

    foreach ($list as $file) {
        if (in_array($file,$done)) continue;
        $name = pathinfo($file,PATHINFO_FILENAME);
        call_user_func(function ($_PLUGIN_FILE_) { require $_PLUGIN_FILE_;  }, $file);
        _loadPlugin($name,null,true);
    }
}

function finalizeTypes() {
    global $PLUGIN_CONTEXT;

    if (!isset($PLUGIN_CONTEXT['type'])) $PLUGIN_CONTEXT['type'] = [];

    foreach ($PLUGIN_CONTEXT['type'] as $name => &$cox) {
        if (!isset($cox['char'])) $cox['char'] = setProjectSymbolFromSource("$name@projectType",$cox['symbol']);
    }

}

function loadAllPlugins() {

    $list = getAvailablePluginsList();

    foreach ($list as $item) {
        if ($item[0] == '_') continue;
        if (!isPluginLoaded($item)) addPlugin($item);
    }

    finalizeTypes();
}

function getPlugins() {
    global $PLUGIN;
    global $PLUGIN_CONTEXT;

    $metadata = pluginCall('getPluginMetadata',true);
    if (!is_array($metadata)) $metadata = [];

    $o = [];
    foreach ($PLUGIN as $name => $obj) {
        $o[$name] = [
            'name'      =>  $name,
            'default'   =>  $PLUGIN_CONTEXT['default'][$name],
        ];

        if (isset($PLUGIN_CONTEXT['type'][$name])) {
            $o[$name]['type'] = $PLUGIN_CONTEXT['type'][$name];
        }

        if (isset($metadata[$name]) and is_array($metadata[$name])) {
            foreach ($metadata[$name] as $k => $v) {
                if (!isset($o[$name][$k])) $o[$name][$k] = $v;
            }
        }

    }

    return $o;
}

function getProjectTypeMap() {
    global $PLUGIN;
    global $PLUGIN_CONTEXT;

    $metadata = pluginCall('getPluginMetadata',true);
    if (!is_array($metadata)) $metadata = [];

    $o = [];
    foreach ($PLUGIN as $name => $obj) {
        if (!isset($PLUGIN_CONTEXT['type'][$name])) continue;

        $o[$name] = [
            'name'      =>  $name,
            'default'   =>  $PLUGIN_CONTEXT['default'][$name],
            'type'      =>  $PLUGIN_CONTEXT['type'][$name]
        ];

        if (isset($metadata[$name]) and is_array($metadata[$name])) {
            foreach ($metadata[$name] as $k => $v) {
                if (!isset($o[$name][$k])) $o[$name][$k] = $v;
            }
        }

    }

    return $o;

}

function getProjectTypeClassMap() {
    global $PLUGIN_CONTEXT;
    $o = [];
    foreach ($PLUGIN_CONTEXT['type'] as $name => $item) {
      $o[ $item['class'] ] = $name;
    }
    return $o;
}

function getProjectTypeInfoByClass($class) {
    global $PLUGIN_CONTEXT;

    if (!isset($PLUGIN_CONTEXT['type'])) return false;

    foreach ($PLUGIN_CONTEXT['type'] as $name => $data) {
        if ($data['class'] == $class) {
            $data['name'] = $name;
            return $data;
        }
    }

    return false;
}

function getProjectTypeInfoByPath($path) {
    global $PLUGIN_CONTEXT;

    $class = getProjectTypeByPath($path);
    if (!$class) return false;

    if (!isset($PLUGIN_CONTEXT['type'])) return false;

    foreach ($PLUGIN_CONTEXT['type'] as $name => $data) {
        if ($data['class'] == $class) {
            $data['name'] = $name;
            return $data;
        }
    }

    return false;
}

function _loadPlugin($name, $bySetting, $isDefault) {
    global $PLUGIN;
    global $PLUGIN_CONTEXT;

    $name = ucfirst($name);
    if (!preg_match('/^(_|)[A-Za-z]{1}[A-Za-z0-9]{0,39}$/',$name)) quit("Nome plugin non valido: $name",$bySetting);

    if (isset($PLUGIN[$name])) quit("Plugin $name gia attivato",$bySetting);

    $class = "pScan\\plugins\\$name";
    if (!class_exists($class)) quit("Errore parsing plugin: $name",$bySetting);
    if ($name[0] == '_') return;

    $PLUGIN[$name] = new $class();

    $ref = new ReflectionClass($class);
    if (!isset($PLUGIN_CONTEXT['method'])) $PLUGIN_CONTEXT['method'] = [];
    if (!isset($PLUGIN_CONTEXT['type'])) $PLUGIN_CONTEXT['type'] = [];
    if (!isset($PLUGIN_CONTEXT['default'])) $PLUGIN_CONTEXT['default'] = [];
    $PLUGIN_CONTEXT['default'][$name] = $isDefault;

    $methods = $ref->getMethods();

    foreach ($methods as $method) {

        if (!$method->isPublic()) continue;

        $mName = $method->getName();

        if (!isset($PLUGIN_CONTEXT['method'])) $PLUGIN_CONTEXT['method'][$mName] = [];

        if ($mName == 'parseDirectory') {

            if (method_exists($PLUGIN[$name],'getTypeName')) {
                $title = $PLUGIN[$name]->getTypeName();
            } else {
                $title = $class;
            }

            $symbol = null;

            if (method_exists($PLUGIN[$name],'getTypeSymbolSource')) {
                $symbol = $PLUGIN[$name]->getTypeSymbolSource();
            }

            $PLUGIN_CONTEXT['type'][$name] = [
                'class'     =>  $class,
                'title'     =>  $title,
                'symbol'    =>  $symbol
            ];
        }
        $PLUGIN_CONTEXT['method'][$mName][] = $class;
        $PLUGIN_CONTEXT['method'][$mName] = array_unique($PLUGIN_CONTEXT['method'][$mName]);
    }
}

function isPluginMethod($name) {
    global $PLUGIN_CONTEXT;
    return isset($PLUGIN_CONTEXT['method']) && isset($PLUGIN_CONTEXT['method'][$name]);
}

function isPluginLoaded($name) {
    global $PLUGIN;
    $name = ucfirst($name);
    return isset($PLUGIN[$name]);
}

function getAvailablePluginsList() {
    global $SETTINGS;
    $pattern = "{$SETTINGS['myPath']}plugins/*.php";
    $list = glob($pattern);
    $o = [];
    foreach ($list as $item) {
        $name = pathinfo($item,PATHINFO_FILENAME);
        $name = ucfirst($name);
        $o[] = $name;
    }

    return $o;
}

function clearPlugins() {
    global $PLUGIN;
    $PLUGIN = [];
}

function initPlugins() {
    global $PLUGIN;
    $ptr = 0;

    $tmp = [];
    foreach ($PLUGIN as $name => $obj) {

        if (method_exists($obj,'getPriority')) {

            $pri = $obj->getPriority();
            $pri = is_numeric($pri) ? intval($pri) : 0;

        } else {

            $pri = 0;

        }

        $sid = base_convert($pri,10,36);
        $sid = str_pad($sid,6,'0',STR_PAD_LEFT);
        $sid.= str_pad(base_convert($ptr,10,36),2,'0',STR_PAD_LEFT);
        $tmp[$sid] = [ $name, $obj ];
        $ptr++;
    }

    krsort($tmp);
    $PLUGIN = [];
    foreach ($tmp as $item) {
        $PLUGIN[$item[0]] = $item[1];
    }

}


function pluginFixArray(&$array, $method, $forAll, ...$params) {
    $x = _pluginCall($method,$forAll,$params);

    if ($forAll) {

        foreach ($x as $item) {
            if (is_array($item)) $array = array_replace_recursive($array,$item);
        }

    } else {

        if (is_array($x)) $array = array_replace_recursive($array,$x);

    }

}

function pluginCall($method, $forAll, ...$params) {
    return _pluginCall($method,$forAll,$params);
}

function _pluginCall($method, $forAll, $params) {
    global $PLUGIN;

    $out = $forAll ? [] : null;

    foreach ($PLUGIN as $name => $obj) {
        try {

            if (method_exists($obj,$method)) {

                $x = call_user_func_array([$obj,$method],$params);
                if ($x !== null) {

                    if ($forAll) {
                        $out[$name] = $x;
                    } else {
                        return $x;
                    }

                }

            }

        } catch (Exception $error) {

            echo "\n";
            tty(12,0,$error->getMessage());
            tty(4,0," dal plugin $name al metodo $method");
            echo "\n";
            tty(4,0,$error->getTraceAsString());
            echo "\n";
            quit("Errore plugin");

        }

    }

    return $out;
}