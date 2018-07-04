<?php

namespace arxan\structs;
// 签名结构
class SignParam {
    var $creator = ""; 
    var $nonce = ""; 
    var $private_key = ""; 

    function __construct($creator,$nonce,$private_key){
        $this->creator = $creator;
        $this->nonce = $nonce;
        $this->private_key = $private_key;
    }   

    function getCreator(){
        return $this->creator;
    }   

    function getNonce(){
        return $this->nonce;
    }   

    function getPrivateKey(){
        return $this->private_key;
    }   

    function setCreator($creator){
        $this->creator = $creator;
    }   

    function setNonce($nonce){
        $this->nonce = $nonce;
    }   

    function setPrivateKey($private_key){
        $this->private_key = $private_key;
    }   
}

