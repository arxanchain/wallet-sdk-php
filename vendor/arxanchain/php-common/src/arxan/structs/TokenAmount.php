<?php

namespace arxan\structs;

class TokenAmount {
    var $token_id = "";
    var $amount = 0;

    function __construct($token_id,$amount){
        $this->token_id = $token_id;
        $this->amount = $amount;
    }
}

