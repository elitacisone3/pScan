<?php
const PROG_NAME = 'pScan';

$CONF = [];
$OPT = [];
$SETTINGS = [];
$PLUGIN = [];
$PLUGIN_CONTEXT = [];
$OPT_PARAMETERS = [];
$OPT_SECTIONS = [];

$SYMBOLS = [];
$HOUR_MAP = [
    0   =>  ['N',12],
    7   =>  ['M',15],
    14  =>  ['P',4],
    18  =>  ['S',13],
    22  =>  ['N',12]
];

$HEAT_MAP = [
    0   =>  [4,'█',13],
    8   =>  [0,'■',7],
    15  =>  [1,'░',4],
    16  =>  [2,'▒',4],
    18  =>  [3,'▓',12],
    22  =>  [4,'█',13],
];

$WEEK_DAYS = [
    1   =>  [ 'L', 'lun', 0, false  ],
    2   =>  [ 'M', 'mar', 0, false  ],
    3   =>  [ 'E', 'mer', 0, false  ],
    4   =>  [ 'G', 'gio', 0, false  ],
    5   =>  [ 'V', 'ven', 0, false  ],
    6   =>  [ 'S', 'sab', 4, true   ],
    7   =>  [ 'D', 'dom', 13, true  ]
];

$VGA_COLOR = [
    0,
    4,
    2,
    6,
    1,
    5,
    3,
    7,
    8,
    27,
    10,
    14,
    13,
    9,
    11,
    15,
    130,
    10,
    14,
    10,
    13]
;

$PALETTE = [
    0x000, //  0
    0x008, //  1
    0x080, //  2
    0x088, //  3
    0x800, //  4
    0x808, //  5
    0x880, //  6
    0xccc, //  7
    0x888, //  8
    0x28f, //  9 fixed
    0x0f0, //  10 A
    0x0ff, //  11 B
    0xf0f, //  12 C
    0xf00, //  13 D
    0xff0, //  14 E
    0xfff, //  15 F
    0xf80, //  16 G
    0x8f0, //  17 H
    0x08f, //  18 I
    0x0f8, //  19 J
    0xf08, //  20 K
    0x80f, //  21 L
    0x048, //  22 M
    0x084, //  23 N
    0x804, // 24  O
    0x408, // 25  P
    0x804, // 26  Q
    0x840, // 27  R
    0x480, // 28  S
    0x444, // 29  T
    0xaaf, // 30  U
    0xafa, // 31  V
    0xaff, // 32  W
    0xfaa, // 33  X
    0xfaf, // 34  Y
    0xffa, // 35  Z
    0x003, // 36 // Table 0
    0x303, // 37 // Table 1
    0x300, // 38 // Table 2
    0xcc0] // 39 // ORO!
;

$LEGACY_CHAR = [
    ['┌','/'],
    ['│','|'],
    ['└','\\'],
    ['└','\\'],
    ['┘','/'],
    ['┼','+'],
    ['┴','+'],
    ['├','+'],
    ['┤','+'],
    ['─','-'],
    ['┐','\\'],
    ['┘','/'],
    ['┬','+'],
    ['√','V'],
    ['·','.'],
    ['░',':'],
    ['▒','*'],
    ['▓','@'],
    ['█','@'],
    ['■','#'],
    ['☺','^']]
;

$TTY_PALETTE = [];

if (
    PHP_VERSION_ID < 50600 or
    !function_exists('mb_strlen') or
    !function_exists('json_decode')
) {
    echo "Caratteristiche avanzate non disponbili:\n";
    echo "Verifica che php abbia una versione di almeno 5.6, (consigliato php 8) e che ci siano i moduli installati.\n\n";
    exit(1);
}
