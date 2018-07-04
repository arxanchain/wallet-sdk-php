<?php

namespace arxan\structs;

class RegisterWalletBody {
    var $type = "";
    var $access = "";
    var $phone = "";
    var $email = "";
    var $secret = "";

    function __construct($type,$access,$secret,$phone = "",$email = ""){
        $this->type = $type;
        $this->access = $access;
        $this->phone = $phone;
        $this->email = $email;
        $this->secret = $secret;
    }

    function getType(){
        return $this->type;
    }

    function getAccess(){
        return $this->access;
    }

    function getPhone(){
        return $this->phone;
    }

    function getEmail(){
        return $this->email;
    }

    function getSecret(){
        return $this->secret;
    }
}

