<?php

namespace arxan\structs;

class TransferAssetBody {
    var $from = "";
    var $to = "";
    var $assets= array();

    function __construct($from,$to,$assets){
        $this->from = $from;
        $this->to = $to;
        $this->assets = $assets;
    }

    function getFrom(){
        return $this->from;
    }

    function getTo(){
        return $this->to;
    }

    function getAssets(){
        return $this->assets;
    }
}

