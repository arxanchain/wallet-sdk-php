<?php
namespace arxan\structs;

class IssueAssetBody {
    var $issuer = "";
    var $owner = "";
    var $asset_id = "";

    function __construct($issuer,$owner,$asset_id){
        $this->issuer = $issuer;
        $this->owner = $owner;
        $this->asset_id = $asset_id;
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
}

