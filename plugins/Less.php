<?php

namespace pScan\plugins;

class Less
{

    private $enabled = false;
    private $text = [];
    private $curPos = 0;
    private $lines = 0;
    private $screenHeight = 44;
    private $lineWidth = 132;
    private $maxPos = 0;

    public function getPluginMetadata() {
        return [
            'desc'  =>  "Consente lo scorrimento manuale del testo.\n(Solo sistemi linux)."
        ];
    }

    private function filterColors($text) {
        return preg_replace_callback(

            '/\\x1b[^0-9]{0,1}[0-9;]*[^0-9;]{1}/',

            function($match) {

                if (preg_match('/^\\x1b\\[(m|(38|48);5;[0-9;]+m)$/',$match[0])) {
                    return $match[0];
                } else {
                    return '';
                }
            },
            $text)
            ;
    }

    public function onInitDone() {
        global $SETTINGS;
        if (
            $SETTINGS['colors'] and
            function_exists('readline_callback_handler_install') and
            $SETTINGS['windows'] == false
        ) {

            $this->enabled = true;

        } else {

            $this->enabled = false;

        }
    }

    public function on132Mode($mode) {
        if ($this->enabled == false) return null;
        return true;
    }

    private function doFrame() {

        echo "\033[2J\033[0;0H\033[7l";

        for ($Y = 0; $Y < $this->screenHeight; $Y++) {
            $ptr = $this->curPos + $Y;
            $text = ($ptr >= $this->lines) ? '' : $this->text[$ptr];
            $y = 1+$Y;
            echo "\033[{$y};0H{$text}";
        }

        echo "\033[{$this->screenHeight};0H";
        $st = " q = Quit  [ ";
        $maxLen = strlen("{$this->lines}") + 1;
        $maxLen = min(4 , $maxLen);
        $st.= fixLen( $this->curPos + 1, $maxLen) . ' / '. fixLen($this->maxPos + 1,$maxLen);
        $st.= " ] ";
        $st = str_repeat(' ', $this->lineWidth - strlen($st)) . $st;
        tty(15,1, $st);
        echo "\033[{$this->screenHeight};0H";

    }

    public function onDispatch($text) {

        if ($this->enabled == false) return null;

        $text = str_replace(["\r\n","\r"],"\n",$text);
        $text = $this->filterColors($text);

        $this->text = explode("\n",$text);

        $this->lines = count($this->text);
        $this->curPos = 0;
        $this->maxPos = ($this->lines - $this->screenHeight) + 1;

        echo "\033c";

        if (getConfig('tty.setDefaultColors',false)) {
            echo "\033]10;rgb:C/C/C\007";
            echo "\033]11;rgb:0/0/0\007";
        }

        echo "\033[0m";
        echo "\033[2J";
        echo "\033[3J";
        echo "\033[?3h";
        echo "\033[132$|";
        echo "\033[44*|";

        readline_callback_handler_install('', function () {});

        echo "\033[?25l";

        $this->doFrame();
        $err = 0;
        while (true) {
            $read = array(STDIN);
            $write = $except = null;
            $number = @stream_select($read, $write, $except, 0, 100000);
            if ($number === false) $err++; else $err = 0;

            if ($err > 100) {
                echo "\033[2J";
                quit("Errore systemCall");
            }

            if ($number && in_array(STDIN, $read)) {

                $char = stream_get_contents(STDIN, 1);

                switch ($char) {

                    case 'q':
                    case 'Q':
                        $this->cmdQuit();
                        break;

                    case 'A':
                        $this->cmdUp();
                        break;

                    case 'B':
                        $this->cmdDn();
                        break;

                    case '1':
                        $this->cmdTop();
                        break;

                    case '4':
                        $this->cmdBottom();
                        break;

                    case '5':
                        $this->cmdPgUp();
                        break;

                    case '6':
                        $this->cmdPgDn();
                        break;

                }

            }
        }

    }

    function cmdTop() {
        $this->curPos = 0;
        $this->doFrame();
    }

    function cmdBottom() {
        $this->curPos = $this->maxPos;
        $this->doFrame();
    }

    function cmdUp() {
        $this->curPos--;
        if ($this->curPos <0) {
            $this->curPos = 0;
            echo "\x07";
        }
        $this->doFrame();
    }

    function cmdDn() {
        $this->curPos++;

        if ($this->curPos > $this->maxPos) {
            $this->curPos = $this->maxPos;
            echo "\x07";
        }
        $this->doFrame();
    }

    function cmdPgUp() {
        $this->curPos-=$this->screenHeight;
        if ($this->curPos <0) $this->curPos = 0;
        $this->doFrame();
    }

    function cmdPgDn() {
        $this->curPos+=$this->screenHeight;

        if ($this->curPos > $this->maxPos) $this->curPos = $this->maxPos;
        $this->doFrame();
    }

    function cmdQuit() {
        echo "\033[2J\033[0;0H\033[7l";
        echo "\033[?25h\033[u\033[m";

        exit(0);
    }

}