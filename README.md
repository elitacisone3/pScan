# pScan

Project scan (pScan) scannerizza più progetti di diverse IDE e ne visualizza le statistiche di utilizzo, con la possibilità di generare anche una applicazione web con i dati (`--build-app`).

Totalmente realizzato in PHP, modificato nel tempo fin dal lontano 2007. Versione attuale: 3.0 (vedi `--help licenza` per il changelog completo).

Questo tool, con tanto di manuale integrato, permette di tenere una traccia nel tempo dell'utilizzo e del lavoro su vari progetti.

È anche possibile generare delle vere e proprie "mappe del tempo", in cui si può vedere quando e quanto avanza lo sviluppo dei tuoi progetti.

Al momento supporta Android Studio (parzialmente) e PHPStorm. Tuttavia è possibile aggiungere altri IDE e altre fonti di dati tramite i plugin (vedi la sezione dedicata più sotto).

Su sistemi Linux è anche possibile, tramite il plugin "Less", navigare il testo colorato.

Una tabella di simboli aiuta a navigare le "mappe del tempo" dei tuoi progetti. Esistono diverse modalità di visualizzazione e personalizzazione: i dati si possono vedere, con i grafici, divisi in modalità giornaliera (ora per ora) oppure mensile (giorno per giorno).

Attenzione: la modalità di visualizzazione a terminale richiede una console che supporta i colori e almeno 132 colonne. In alternativa, i dati possono essere esportati in una pagina web statica con `--build-app`, consultabile in un browser senza questi limiti (vedi sotto).

Funziona anche sotto Windows, con alcune funzionalità limitate. È anche possibile installarlo in modo che legga la configurazione da `/etc/pScan` oppure dalla home dell'utente, disponibile anche su Windows.

Si possono configurare i progetti specificando un percorso, un nome e un simbolo sulla mappa (quest'ultimo può essere assegnato anche in automatico).

## Installazione

L'installazione è manuale, ma la configurazione è rapida. Consulta il manuale completo con `./pScan.php --help`.

```
./pScan.php --crw <percorso progetti1> --crw <percorso progetti2> -P <progetto3> -P <progetto4> --aggiungi TUTTO
```

Prima di installare può essere prudente visualizzare la lista dei progetti che saranno inclusi:

```
./pScan.php --crw <percorso progetti> --mostra -q
```

Per i dettagli sulle varie modalità di installazione (liste di directory, singoli progetti, o entrambe insieme) vedi `--help installa`.

## Visualizzare le mappe

```
./pScan.php --tutti --mappa --nomi --compatto --simboli --mesi
```

oppure, per la vista giornaliera:

```
./pScan.php --tutti --mesi
```

Una lista più ampia di esempi pronti all'uso è disponibile con `--help esempi`.

## Salvare e condividere i dati

Oltre al salvataggio classico con `--salva` (dati già elaborati, in JSON o JSON compresso), pScan offre due modalità pensate per la condivisione:

- `--save-dump <file>`: salva su file la scansione grezza, non ancora elaborata, insieme ai parametri usati. Utile per inviare una scansione a chi non ha i progetti in locale.
- `--load-dump <file>`: ricarica un file creato con `--save-dump` e riprende l'elaborazione come se la scansione fosse appena stata eseguita, senza bisogno delle directory dei progetti originali. I parametri passati sulla riga di comando hanno la precedenza su quelli salvati nel dump.
- `--out-simple <file>`: salva un file (JSON o CSV) con l'aggregazione settimanale per categoria (Totale, Notte, Festivi, Pomeridiani), con le percentuali sulle quote configurabili nella sezione `[weekly]` della configurazione. Funziona in autonomia, non richiede `--salva`.

```
./pScan.php --tutti --save-dump dati.json
./pScan.php --load-dump dati.json --mesi
```

## Generare una applicazione web (--build-app)

`--build-app <dir>` genera, nella directory indicata, una single page application statica (HTML, CSS, JavaScript) con i dati incorporati. La pagina si apre dal file `index.html`, anche offline, senza bisogno di PHP né delle directory dei progetti originali: basta copiare la cartella generata su un server web qualsiasi, oppure aprirla direttamente dal filesystem.

```
./pScan.php --tutti --build-app webapp/
```

Uso tipico, pensato per condividere i dati con chi non ha accesso ai progetti originali: prima si salva una scansione grezza, poi la si ricarica altrove e si genera l'applicazione in un solo passaggio.

```
./pScan.php --tutti --save-dump dati.json
./pScan.php --load-dump dati.json --build-app webapp/
```

### Personalizzazione

- `--app-title <titolo>`: imposta il titolo della pagina.
- `--app-desc <descrizione>`: imposta il sottotitolo mostrato sotto il titolo.
- `--app-config <file>`: carica un file ini con le stesse impostazioni (titolo, descrizione, tema chiaro o scuro, generazione del file `.htaccess`), utile per automatizzare generazioni ripetute. I parametri sulla riga di comando hanno sempre la precedenza sul file di configurazione. Un modello commentato si trova in `res/templates/appConfig.template`.
- `--fuso-orario <ore>`: sposta la lettura di tutti gli orari in avanti o indietro di un numero intero di ore, prima che i dati vengano elaborati. Utile per correggere vecchie scansioni registrate con un fuso orario errato (si applica globalmente, a tutti i progetti).

### Contenuto della pagina generata

- **Grafici**: andamento settimanale o mensile per categoria e per progetto, con zoom sincronizzato tra i grafici.
- **Annuale**: presente solo se i dati coprono più di un anno, mostra le statistiche principali suddivise per anno.
- **Mensile** e **Giornaliero**: le stesse viste a terminale di pScan, renderizzate nel browser.
- **Totali**: distribuzione delle ore per progetto, con tabella e grafico a torta.

### Sicurezza della directory di destinazione

Se la directory indicata non è vuota, pScan rigenera il contenuto solo se trova un file `.dirType` creato da un precedente `--build-app`. In caso contrario l'operazione viene annullata senza toccare nulla, per evitare di sovrascrivere per errore una directory non pertinente.

Guida completa: `./pScan.php --help buildapp`.

## Creare un plugin

Esistono due categorie di plugin, entrambe semplici classi PHP nel namespace `pScan\plugins`:

- **Tipo di progetto**: riconosce ed elabora le directory di un IDE, come PHPStorm o Android Studio. Estende la classe `_ProjectType` e implementa almeno `getProjectType($path)` e `parseDirectory($dir, $projectData)`.
- **Plugin a eventi**: aggiunge un comportamento senza rappresentare un tipo di progetto, ad esempio un formato di salvataggio o una colonna extra nei report. Implementa uno o più metodi pubblici che corrispondono agli eventi lanciati internamente da pScan, senza bisogno di registrarsi esplicitamente: basta implementare il metodo che serve.

### Dove inserirlo

- directory `default/`: plugin caricati sempre, in automatico (qui si trovano i tipi di progetto principali, come PHPStorm e AndroidStudio).
- directory `plugins/`: plugin opzionali, da attivare esplicitamente con `--plugin <nome>` sulla riga di comando, oppure aggiungendo il nome alla voce `main.plugin` della configurazione.

Il nome del file, senza estensione, deve coincidere con il nome della classe, con la lettera iniziale maiuscola: `plugins/MioPlugin.php` deve contenere la classe `MioPlugin`.

### Metodi di un plugin di tipo progetto

- `getTypeName()`: il nome visualizzato nei report.
- `getTypeSymbolSource()`: il simbolo e i colori di default per questo tipo, nello stesso formato usato da `--simbolo`.
- `getPluginMetadata()`: un array con la chiave `desc`, la descrizione mostrata da `--help plugin`.
- `getProjectType($path)`: ritorna `get_class($this)` se la directory appartiene a questo tipo, altrimenti `null`.
- `parseDirectory($dir, $projectData)`: legge la directory e ritorna un array con gli intervalli di lavoro trovati, nello stesso formato prodotto dai plugin esistenti.

### Alcuni eventi utili per i plugin a eventi

- `onStart`, `onBeforeStart`, `onInitDone`: lanciati all'avvio del programma.
- `parseInterval(&$interval, &$cox)`: può modificare un intervallo grezzo prima che venga attribuito a giorno e ora.
- `calcDay`, `calcAllDays`, `getAllData`: lanciati dopo l'elaborazione dei dati giornalieri o completi.
- `onSaveDump`, `saveDumpHook`, `onSaveSimple`, `saveSimpleHook`: durante il salvataggio con `--save-dump` e `--out-simple`.
- `outDayLine`, `outMonthLine`, `outSummary`, `outLegend`: durante la stampa a terminale, per aggiungere colonne o righe personalizzate.
- `getHelpSyntaxToken()`, `getHelpParamMap()`: per dichiarare nuovi parametri a riga di comando propri del plugin e farli comparire nella guida.

Per un esempio completo e funzionante, consulta `plugins/ProjectData.php` (un tipo di progetto minimale che importa dati da un file JSON) e `plugins/PCCheck.php` (un tipo di progetto che dichiara anche un proprio parametro a riga di comando).

Guida completa: `./pScan.php --help plugindev`.

## Licenza

GNU General Public License v3. Testo completo e changelog: `./pScan.php --help licenza`.
