<?php

namespace arxan\structs;

class POEBody {
    var $name = "";
    var $parent_id = "";
    var $owner = "";
    var $hash = "";
    var $metadata = "";

    function __construct($name,$owner,$parent_id = "",$hash = "",$metadata = ""){
        $this->name = $name;
        $this->owner = $owner;
        $this->parent_id = $parent_id;
        $this->hash = $hash;
        $this->metadata = $metadata;
    }

    function getName(){
        return $this->name;
    }

    function getOwner(){
        return $this->owner;
    }
}

