<?php

function doHelpSyntax($tokenList) {

    $text = [];
    $line = [];
    $len = 0;

    foreach ($tokenList as $token) {
        $line[] = $token;
        $len+= 1 + strlen($token);
        if ($len > 75) {
            $text[] = implode(' ',$line);
            $line = [];
            $len = 0;
        }
    }

    if ($line) $text[] = implode(' ',$line);

    $out = '';
    foreach ($text as $line) {
        $out.="~$line\n";
    }

    return $out;

}

function doHelpParameters($paramArray) {
    $out = '';
    foreach ($paramArray as $key => $value) {
        $value = wordwrap($value,60,"\\n");
        $key = strlen($key) == 1 ? "-$key" : "--$key";
        $out.="   {$key} ~$value\n";
    }
    return rtrim($out,"\n")."\n";
}

function outHelpParamSection($paramArray, $title) {

    tty(10,0,"$title:");
    echo "\n\n";

    foreach ($paramArray as $item) {
        $param = $item[0];
        $text = trim($item[1]);
        $text = wordwrap($text,55,"\n");
        $text = trim($text);
        $text = explode("\n",$text);
        foreach ($text as $n => $line) {

            if ($n == 0) {
                tty(10,0,fixLen($param,20));
            } else {
                echo str_repeat(' ',20);
            }
            tty(7,0,$line);
            echo "\n";
        }

    }

    echo "\n";

}

function getSelfName() {
    $mySelf = $_SERVER['argv'][0];
    $mySelf = basename($mySelf);
    if (!preg_match('/^[A-Za-z0-9\\_\\-]+(\\.php|)$/',$mySelf)) $mySelf = PROG_NAME;
    return $mySelf;
}

function doHelpCapPlugin() {
    initEvidenzia();
    loadAllPlugins();

    $list = getPlugins();
    tty(15,0,'Tipi di progetto supportati:');
    echo "\n\n";

    $skip = 0;
    foreach ($list as $pName => $item) {
        if (!isset($item['type'])) {
            $skip = true;
            continue;
        }
        echo '  ';
        tty($item['default'] ? 7 : 8 , 0, $item['default'] ? '*' : '·');
        echo " {$item['type']['char']} ";
        tty(10,0,$pName);
        echo "\n";
        if (isset($item['desc'])) {
            echo '      ';
            $x = $item['desc'];
            $x = wordwrap($x,70,"\n");
            $x = str_replace("\n","\n      ",$x);
            tty(7,0,$x);
            echo "\n\n";
        }
    }

    echo "\n";

    if ($skip) {
        tty(15,0,'Altri plugin disponibili:');
        echo "\n\n";

        foreach ($list as $pName => $item) {
            if (isset($item['type'])) continue;
            echo '  ';
            tty($item['default'] ? 7 : 8 , 0, $item['default'] ? '*' : '·');
            echo '   ';
            tty(10,0,$pName);
            echo "\n";
            if (isset($item['desc'])) {
                echo '      ';
                $x = $item['desc'];
                $x = wordwrap($x,70,"\n");
                $x = str_replace("\n","\n      ",$x);
                tty(7,0,$x);
                echo "\n\n";
            }
        }

        echo "\n";

    }

    tty(7,0,'  * ');
    tty(3,0,'= Abilitato di default.');
    echo "\n";

    dispatch();
    exit();
}

function doHelp() {
    global $SETTINGS;

    loadAllPlugins();
    initEvidenzia();

    $syntaxToken = [];
    $paramMap = [];
    if (isset($SETTINGS['optModes'])) {
        foreach ($SETTINGS['optModes'] as $key => $data) {
            $data = parseInternalOpts($data);
            $syntaxToken[] = "[ --{$key} ]";
            if (isset($data['title'])) {
                $paramMap[$key] = $data['title'];
            }
        }
    }

    $data = pluginCall('getHelpSyntaxToken',true);
    foreach ($data as $datum) {
        $syntaxToken = array_merge($syntaxToken,$datum);
    }

    $data = pluginCall('getHelpParamMap',true);
    foreach ($data as $plugin => $datum) {
        foreach ($datum as $k => $v) {
            $datum[$k] = "$v\\n(Plugin $plugin).";
        }
        $paramMap = array_merge($paramMap,$datum);
    }

    $map = [
        'SYNTAX'        =>  doHelpSyntax($syntaxToken),
        'PARAMETERS'    =>  doHelpParameters($paramMap)
    ];

    $text= <<<HELP
~    { -p <progetto> | -L <file> [ -v ] [ -u ] | -P <progetto> ...
~    [ -u ]  } [ --dal <data> ] [ --al <data> ] [ { --colori | --mono } ] 
~    [ --plugin <nome> ] [ { -E | -e } ] [ --tipo <nome> ] [ -D ] 
~    [ { --mesi [ --h ] [ -X ] | [ -H ] [ -o ] [ -N ] } ] [ -O ] [ -T ] 
~    { [ --h0 ] [ --h1 ] [ --h2 ] [ --h3 ] [ --h4 ] | [ --H0 <num> ] 
~    [ --H1 <num> ] [ --H2 <num> ] [ --H3 <num> ] [ --H4 <num> ] } 
~    [ --salva <file> ] [ --ascii ] [ --G2 ] [ --pr <profilo> ] 
~    [ --scr ] [ --per <peridodo> ] [ --noe ] [ -q ] [ --nomi ]
~    [ --crw <path> ] ... [ { --map | --mapnum } ] [ --simboli ]
~    [ --palette ] [ --tutti ] [ --lista ] [ --head ] [ --mappa ]
~    [ --compatto ] [ -Q ] [ -v ] [ --mostra ] [ --dati ] [ --no-ore ]
~    [ { --132 | --132x44 | --max  } ] [ --help [ <capitolo> ] ]
%%SYNTAX%%

~    Analizza uno o più progetti di varie IDE e ne visualizza le statistiche.
.10

~    Visualizza la guida completa:

~ %%ME%% --help [ <capitolo> ] [ --evidenzia <testo> ]

~   Visualizza i plugin disponibili:

~ %%ME%% --help plugin

~    Opzioni:
    
    --help      ~Visualizza la guida completa o capitoli della guida.
    --evidenzia ~Evidenzia un testo nelle guide.\\n(Può essere utile per cercare parametri o nei testi delle\\nguide, per ottenere un risultato più visibile).
    --mostra    ~Mostra solo la lista delle directory dei progetti.
    --simboli   ~Forza l'uso dei simboli nella modalità:\\n--mappa --nomi --compatto.
    -p      	~Specifica il progetto da analizzare.
    -q          ~Toglie la presentazione.
    -P          ~Specifica più progetti.\\n(usa più volte l'opzione).
    -L          ~Specifica unn file con la lista dei progetti.
    -v          ~Imposta il verbose durante il merge dei progetti.
    -u          ~Somma i progetti più volte\\n(N.B.: Invalida i calcoli, contano solo le caselle).
    --map       ~Visualizzazione avanzata con simboli di progetto.
    --mapnum    ~Visualizzazione avanzata con contantori per casella\\ndi progetto.
    --noe       ~Codifica l'output in UTF-8.
    --crw       ~Scannerizza una o più directory cercando progetti.\\n(Usabile più volte).
    --salva     ~Salva i dati in json o json.gz
    --colori	~Crea il report a colori.
    --ascii     ~Usa solo caratteri ASCII.
    --mono		~Crea il report senza colori.
    --al    	~Filtra finendo con la data.
    --mesi		~Crea report mensile.
    --G2        ~Usa i grafici con i numeri dentro.
    --dati      ~Visualizza solo il report finale.
    --pr        ~Carica un profilo di configurazione.
    --head      ~Reimposta l'intestazione iniziale.
    --scr       ~In base alla configurazione ripete l'intestazione.
    --nomi      ~Visualizza a destra il NomeId del progetto.\\n(Se ci sono più progetti visualizza un asterico).
    --plugin    ~Attiva plugin (anche multipli).
    --132       ~Imposta il terminale a 132 colonne.
    --132x44    ~Imposta il terminale a dimensioni di 132x44 caratteri.
    --max       ~Prova a impostare il terminale con la massima dimensione.
    --palette   ~Visualizza la tavolozza dei colori ed esce.\\n(Visualizza anche la tabella codici colore per\\ncreare i simboli colorati).
    --mappa     ~Visualizza la classifica dei progetti a destra.
    --compatto  ~Mostra meno dati e rende più compatto il grafico.\\n(Crea più spazio per i nomi a destra).
    --no-ore    ~Toglie le colonne "Min H." e "Max H." a sinistra nella tabella\\ngiornaliero.
    -T          ~Visualizza solo le tabelle.
    -D		    ~Usa i puntini anzichè il numero del giorno/ore\\n(Se usato con --map inverte l'impostazione).
    -H		    ~Visualizza la mappa di calore nel modo giorni.
    -h		    ~Visualizza la mappa di calore nel modo mesi.
    -X          ~Visualizza dati extra nella modalità mensile.
    -o		    ~Toglie la colonna override.
    -O          ~Visualizza gli override nella griglia giornaliera.
    -e		    ~Inserisce i giorni mancanti come numeri.
    -E		    ~Inserisce tutte le parti mancanti.\\n(Nella modalità --mesi le opzioni -E ed -e\\nhanno lo stesso effetto).
    -N		    ~Toglie le suddivisioni delle giornate.\\n(Le suddivisioni M P S N ).
    -Q          ~Qunatizza al livello di ore i risultati.

~    Filtri:

    --per       ~Filtra per mese nel formato: aaaa/mm/gg-gg\\n(Specifica un intervallo di giorni in un mese).
    --tipo      ~Filtra in base al tipo/plugin di progetto.
    --dal       ~Filtra iniziando dalla data.
    --gruppo    ~Seleziona i progetti in base al gruppo configurato.

.10
~   Parametri definiti dalla configurazione:

%%PARAMETERS%%
.10
~   Questi comandi richiedono una configurazione aggiuntiva:

    --tutti     ~Elabora tutti i progetti.
    --lista     ~Visualizza una lista dei progetti configurati.
    --pro       ~Seleziona un progetto per nome.

HELP;

    $name = getSelfName();
    $ink = 11;
    foreach ($map as $k => $v) {
        $text = str_replace("%%{$k}%%",$v,$text);
    }
    $map = null;

    $text = preg_replace('/[\x20\x09]+/',' ',$text);

    $text.="\n~Filtri:\n";

    for ($i = 0; $i< 5; $i++) {
        $text.="--h{$i}~Filtra per livello $i.\n";
    }

    $text.="\n~Filtri estesi:\n";

    for ($i = 0; $i< 5; $i++) {
        $text.="--H{$i} ~Filtra per livello $i con valore minimo.\n";
    }

    $text = trim($text);
    $text = explode("\n",$text);

    $HILITE = [
        ['/\\x5b|\\x5d|\\x7b|\\x7d|\\x7c/', 3],
        ['/\\<[A-Za-z]+\\>/', 15],
        ['/\\.\\.\\./',3],
        ['/\\-{1,2}\\@{0,1}[A-Za-z0-9]+/',10]
    ];

    foreach ($text as $ptr => $line) {
        $line = trim($line);

        if ($line != '' and $line[0] == '.') {
            $line = substr($line,1);
            if (is_numeric($line)) {
                $ink = intval($line);
                continue;
            } else {
                pluginCall('doHelp'. ucfirst($line),true);
            }

            continue;
        }

        $tok = explode('~',$line);
        if (count($tok) == 1) {
            $tok[0] = trim($tok[0]);
            echo str_repeat(' ',16);
            tty(10,0,$tok[0]);
            echo "\n";
        } else {
            $tok[0] = trim($tok[0]);
            $tok[1] = trim($tok[1]);
            if ($tok[0] == '') {
                $tok = explode(' ',$tok[1]);
                if ($ptr == 0) tty(15,0,"$name "); else echo "    ";
                foreach ($tok as $item) {
                    $d = false;
                    foreach ($HILITE as $mode) {
                        if (preg_match($mode[0],$item)) {
                            tty($mode[1],0,"$item ");
                            $d = true;
                            break;
                        }
                    }
                    if (!$d) {
                        if ($item == '%%ME%%') {
                            tty(15,0,"$name ");
                        } else {
                            tty($ink,0, "$item ");
                        }
                    }
                }
                echo "\n";
            } else {

                echo '    ';
                tty(10,0,fixLen($tok[0],12));
                $subText = str_replace("\\n","\n",$tok[1]);
                $subText = trim($subText);
                $subText = explode("\n",$subText);
                foreach ($subText as $sPtr => $subLine) {
                    if ($sPtr > 0) echo str_repeat(' ',16);
                    tty(7,0,$subLine);
                    echo "\n";
                }
                if ($sPtr > 0) echo "\n";

            }
        }
    }

    $list = getAvailablePluginsList();
    if ($list) {
        echo "\n    ";
        tty(10,0,"Plugin disponibili:");
        echo "\n";

        foreach ($list as $item) {
            tty(7,0,"     {$item}");
            echo "\n";
        }
        echo "\n";
    }

    echo "\n    ";
    tty(10,0,"File di impostazioni:");
    echo "\n";

    foreach ($SETTINGS['try'] as $try) {
        echo "      ";
        $ok = file_exists($try);
        $char = $ok ? '*' : '·';
        tty($ok ? 3 : 8,0, "$char $try");
        echo "\n";
    }

    if (isset($SETTINGS['myConfDir'])) {
        echo "\n";
        tty(10,0,"    Percorso configurazione:");
        echo "\n";
        tty(7,0,"      {$SETTINGS['myConfDir']}");
        echo "\n\n";
    }

    if (isset($SETTINGS['myConfBase'])) {
        tty(10,0,"    Configurazione principale:");
        echo "\n";
        tty(7,0,"      {$SETTINGS['myConfBase']}");
        echo "\n\n";
    }

    echo "\n";
    tty(13,0,"N.B.: ");
    tty(15,0,"Questo programma richiede un terminale con almeno 132 colonne.");
    echo "\n\n";
    tty(13,0,"N.B.: ");
    tty(15,0,"Tutte le opzioni con più progetti potrebbero falsare i conti.");
    echo "\n\n";
    tty(13,0,"N.B.: ");
    tty(15,0,"I progetti Android Studio sono analizzati tramite i dati sul");
    echo "\n";
    tty(15,0,"      garbage collector e sono quantizzati al livello di ore.");
    pluginCall('doHelpExtraText',true);
    echo "\n\n";
    tty(13,0,"N.B.: ");
    tty(15,0,"Per vedere tutto il grafico potrebbe essere necessario");
    echo "\n";
    tty(15,0,"      ingrandire il buffer della shell.");
    echo "\n\n";
    tty(13,0,"N.B.: ");
    tty(15,0,"Usare sempre -Q quando si analizzano più progetti.");
    echo "\n";
    dispatch();
    exit;
}

function helpPalette() {
    global $SETTINGS;
    global $TTY_PALETTE;
    global $SYMBOLS;

    initEvidenzia();

    if (!$SYMBOLS) initSymbols();

    if (!$SETTINGS['colors']) {
        echo "\nColori disabilitati!\n";
        dispatch();
        exit(1);
    }

    tty(15,0,'Colori abilitati:');
    echo "\n\n";
    helpPaletteSection($TTY_PALETTE,true);
    echo "\n";
    tty(15,0,'Colori palette:');
    echo "\n\n";
    helpPaletteSection($SETTINGS['origPalette'],false);
    echo "\n";

    $text = [];
    $line = [];
    $len = 0;
    tty(15,0,'Test combinazioni:');
    echo "\n\n";
    foreach ($SYMBOLS['goodCombo'] as $combo) {

        $id = dechex($combo);
        $id = strtoupper($id);
        $id = str_pad($id,2,'0',STR_PAD_LEFT);

        $code = ttyS($combo >> 4, $combo & 15,'■■');
        $code.= ttyS(3,0,' = ');
        $code.= ttyS(15,0, fixLen($id,3));
        $text[] = $code;
        $len+=8;

        if ($len > 60) {
            $len = 0;
            $line[] = implode(' ',$text);
            $text = [];
        }

    }

    if ($text) $line[] = implode(' ',$text);
    foreach ($line as $text) {
        echo "  $text\n\n";
    }

    echo "\n";
    tty(11,0, '  * ');
    tty(3,0,"Per creare i simboli puoi usare un carattere, seguito da uno di\n    questi codici.");
    echo "\n\n";
    tty(15,0,'Test colori:');
    echo "\n\n";
    echo '  '. ttyS(13,0,fixLen('Gradiente rosso:',24)) . createGrad(1,0,0)."\n";
    echo '  '. ttyS(10,0,fixLen('Gradiente verde:',24)) . createGrad(0,1,0)."\n";
    echo '  '. ttyS(11,0,fixLen('Gradiente blu:',24)) . createGrad(0,0,1)."\n";
    echo '  '. ttyS(15,0,fixLen('Gradiente Bianco:',24)) . createGrad(1,1,1)."\n";
    echo "\n";

    dispatch();
    exit(0);

}

function createGrad($fR,$fG,$fB) {

    $o = '';
    for ($i =0 ;$i < 6; $i++) {
        $h = intval(($i / 5) * 15);
        $r = intval($h * $fR);
        $g = intval($h * $fG);
        $b = intval($h * $fB);
        if ($r>15) $r = 15;
        if ($g>15) $g = 15;
        if ($b>15) $b = 15;

        $str = ttyPalette($r,$g,$b);
        $o.= "\x1b[38;5;{$str}m█\x1b[m";
    }

    return $o;

}

function helpPaletteSection($palette, $pad) {

    $text = [];
    $line = [];
    $len = 0;

    foreach ($palette as $id => $color) {

        if ($pad and $id > 15) break;

        $id = dechex($id);
        $id = strtoupper($id);
        $id = $pad ? $id : str_pad($id,2,'0',STR_PAD_LEFT);

        $code = "\x1b[38;5;{$color}m██\x1b[m ";
        $code.= ttyS(3,0,'= ');
        $code.= ttyS(15,0, fixLen($id,3));
        $text[] = $code;
        $len+=8;

        if ($len > 60) {
            $len = 0;
            $line[] = implode(' ',$text);
            $text = [];
        }

    }

    if ($text) $line[] = implode(' ',$text);
    foreach ($line as $text) {
        echo "  $text\n\n";
    }

}

function doHelpFile($name) {
    global $SETTINGS;

    initEvidenzia();

    $try = [];

    if ($name == 'opzioni') doHelp(); // quit

    if (preg_match('/^[A-Za-z0-9]+$/',$name)) {
        $funct = 'doHelpCap'.ucfirst($name);

        if (function_exists($funct)) {
            $funct(); // exit;
            exit();
        }

        $try[] = "{$SETTINGS['myPath']}/help/{$name}.txt";
    }

    $try[] = "{$SETTINGS['myPath']}/plugins/{$name}.txt";
    $try[] = "{$SETTINGS['myPath']}/help/_default.txt";

    foreach ($try as $item) {
        if (file_exists($item)) {
            $text = file_get_contents($item);
            outText($text);
            break;
        }
    }

    dispatch();
    exit();
}

function helpTextCmdIndex() {
    global $SETTINGS;

    $try = [
        "{$SETTINGS['myPath']}help/*.txt",
        "{$SETTINGS['myPath']}plugins/*.txt"
    ];

    $list = [];
    foreach ($try as $item) {
        $list = array_merge($list,glob($item));
    }

    $o = [];

    $codedCap = [

        'opzioni'   =>  [
            'pri'   =>  20000,
            'title' =>  'Visualizza tutte le opzioni a riga di comando'
        ],

        'plugin'    =>  [
            'pri'   =>  1000,
            'title' =>  'Visualizza tutti i plugin e i tipi di progetto supportati'
        ]

    ];

    $arr = pluginCall('helpTextCmdIndex',true);
    if (is_array($arr)) {
        foreach ($arr as $item) {
            if (!isset($item['pri'])) $item['pri'] = 800;
            foreach ($item as $k => $v) {
                $codedCap[$k] = $v;
            }
        }
    }

    $ptr=0;
    foreach ($codedCap as $name => $cap) {

        $pri = isset($cap['pri']) ? intval($cap['pri']) : 0;
        $pri = 65536 - $pri;

        $sid = "$pri-$name-$ptr";

        $ptr++;
        if (isset($cap['desc'])) {

            $o[$sid] = "<{M 16 2 {$name}}>{{F}} {$cap['title']}:\n{{7}} {$cap['desc']}.";

        } else {

            $o[$sid] = "<{M 16 2 {$name}}>{{F}} {$cap['title']}.";

        }

    }

    foreach ($list as $ptr => $file) {

        $name = pathinfo($file,PATHINFO_FILENAME);
        if ($name[0] == '_') continue;
        $text = file_get_contents($file);
        $title = betweenGetAndReplace($text,'<{title ','}>');
        if ($title === false) continue;

        $desc = betweenGetAndReplace($text,'<{desc ','}>');
        $pri = betweenGetAndReplace($text,'<{data pri ','}>');
        $pri = $pri !== false ? $pri : 0;
        $pri = 65536 - intval($pri);

        $sid = "$pri-$name-$ptr";

        $code = "<{M 16 2 {$name}}>{{F}} $title";
        if ($desc) $code.=":\n{{7}} $desc."; else $code.='.';

        $o[$sid] = $code;

    }

    ksort($o);

    return implode("\n",$o);

}

function getHelpSyntaxMap() {
    global $OPT_PARAMETERS;

    $HIGHLIGHTING = [
        ['/\\x5b|\\x5d|\\x7b|\\x7d|\\x7c/'      , 3],
        ['/\\<(?<text>[A-Za-z]+)\\>/'           , '{{1}} < {{15}} * {{1}} >'],
        ['/\\.\\.\\./'                          , 3],
        ['/\\-{1,2}\\@{0,1}[A-Za-z0-9]+/'       ,10]
    ];

    $section = [ '000-default' => [] ];

    foreach ($OPT_PARAMETERS as $data) {

        if (!$data['section']) $data['section'] = '000-default';
        if (!isset($section[$data['section']])) $section[$data['section']] = [];

        $section[$data['section']][] = $data['syntax'];

    }

    ksort($section);
    foreach ($section as &$data) {
        $data = implode(' ',$data);
        $data = explode(' ',$data);

        $isMatch = false;
        foreach ($data as &$datum) {

            foreach ($HIGHLIGHTING as $regex) {
                if (preg_match($regex[0],$datum,$match)) {
                    if (is_int($regex[1])) {
                        $datum = '{{'.$regex[1].'}} '.$datum;
                    } else {
                        $datum = str_replace('*',$match['text'],$regex[1]);
                    }
                    $isMatch = true;
                    break;
                }
            }

            if (!$isMatch) $datum = "{{7}} $datum";

        }
        unset($datum);
        $data = implode(' ',$data);
    }
    unset($data);

    foreach ($section as &$item) {
         $item = "%%ME%% $item";
    }

    return $section;
}
