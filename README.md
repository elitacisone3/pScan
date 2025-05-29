# pScan
Project scan (pScan) Scannerizza più progetti di diverse IDE e ne visualizza le statistiche di utilizzo.

Totalmente realizzato in php, modificato nel tempo fin dal lontano 2007.

Questo tool, con tanto di manuale, permette di tenere una traccia nel tempo, dell'utilizzo e del lavoro su vari progetti.

È anche possibile generare delle vere e proprie "mappe del tempo", in cui si può vedere quando e quanto avanza lo sviluppo dei tuoi progetti.

Al momento supporta Android Studio (parzialmete) e PHPStorm.

Tuttavia è possibile aggiungere altri sistemi ed altre fonti di dati, tramite i plugin.

Su sistemi linux è anche possibile, tramite il plugin "less", navigare il testo colorato.

Una tabella di simboli ti aiuterà a navigare le "mappe del tempo", dei tuoi progetti.

Esistono diverse modalità di visualizzazione e personalizzazione.

Si possono vedere i dati, con i grafici, divisi in modalita giornaliero (ora per ora), oppure mensile (giorno per giorno).

Attenzione però: La modalità di visualizzazione è tramite il terminale e richiede una console che supporta i colori e almeno 132 colonne.

Funziona anche sotto Windows, però in questo caso alcune funzionalità sarano limitate. È anche possibile intallarlo, quindi legge la configurazione da /etc/pScan oppure dalla home utente. Anche sui sistemi windows è disponbilie questa funzionalità.

Si possono anche configurare i progetti specificando un percorso, un mome, un simbolo sulla mappa (Questa funzione può essere svolta automaticamente).

# Installazione:

L'installazione è manuale, ma la configurazione è molto più rapida. Consulta il manuale tramite l'opzione --help del programma.

./pScan.php --crw &lt;percorso progetti1&gt; --crw &lt;percorso progetti2&gt; -P &lt;progetto3&gt; -P &lt;progetto4&gt; --aggiungi TUTTO 

# Visualizzare le mappe:

./pScan.php --@dump --mappa --nomi --compatto --simboli --mesi

oppure:

./pScan.php --@dump --mesi
