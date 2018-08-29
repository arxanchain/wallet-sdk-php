<?php

namespace arxan\structs;

class TransferCTokenBody {
    var $from = "";
    var $to = "";
    var $tokens = array();

    function __construct($from,$to,$tokens){
        $this->from = $from;
        $this->to = $to;
        $this->tokens = $tokens;
    }

    function getFrom(){
        return $this->from;
    }

    function getTo(){
        return $this->to;
    }

    function getTokens(){
        return $this->tokens;
    }
}

