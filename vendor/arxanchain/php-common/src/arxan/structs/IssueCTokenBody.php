<?php
namespace arxan\structs;

class IssueCTokenBody {
    var $issuer = "";
    var $owner = "";
    var $asset_id = "";
    var $amount = "";

    function __construct($issuer,$owner,$asset_id,$amount){
        $this->issuer = $issuer;
        $this->owner = $owner;
        $this->asset_id = $asset_id;
        $this->amount = $amount;
    }

    function getIssuer(){
        return $this->issuer;
    }

    function getOwner(){
        return $this->owner;
    }

    function getAssetId(){
        return $this->asset_id;
    }

    function getAmount(){
        return $this->amount;
    }
}

