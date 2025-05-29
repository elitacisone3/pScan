<?php
namespace pScan\plugins;

class _ProjectType
{

    public function getHelpSyntaxToken() {

        $kv = $this->getHelpParamMap();
        if (!$kv) return null;

        $o = [];
        foreach ($kv as $item => $desc) {

            $item = strlen($item) > 1 ? "--$item" : "-$item";
            $o[] = "[ $item ]";
        }

        if ($o) return $o;
        return null;

    }

    public function getHelpParamMap() {
        return null;
    }

    public function getTypeName() {
        return get_class($this);
    }

    public function getTypeSymbolSource() {
        return "?70";
    }

    public function getProjectType($path) {
        return null;
    }

    public function parseDirectory($dir, $projectData) {
        return null;
    }

}