<?php

namespace arxan;

//require_once (__DIR__ . "/../log/log.php");
require_once (__DIR__ . "/errCode.php");
require_once (__DIR__ . "/structs/SignParam.php");

use arxan\structs\SignParam;
use arxan\errCode;

// ed25519签名
class Signature{

    function sign($body,$sign_param,&$data){
        
        if(empty($body)){
            return errCode["InvalidParamsErrCode"];
        }

        if($sign_param == NULL){
            return errCode["InvalidParamsErrCode"];
        }

        $key = $sign_param->getPrivateKey();
        $did = $sign_param->getCreator();
        $nonce = $sign_param->getNonce();
        
        // 1.对body进行json编码
        $json_str = json_encode($body);
        if ($json_str == ""){
            return errCode["SerializeDataFail"];
        }

        $sign_body = array();
        $sign_body["creator"] = $did;
        $sign_body["nonce"] = $nonce;
        
        // 2.进行base64编码
        $base64_str = base64_encode($json_str);
        if ($base64_str == ""){
            return errCode["SerializeDataFail"];
        }

        // 3.签名
        $bin = __DIR__ . "/../../utils/bin/sign-util";
        $cmd = $bin . " -key " . $key . " -nonce " . $nonce . " -did " . $did . " -data " .$base64_str;
        // 执行签名操作
        exec($cmd,$out);
        if (empty($out)){
            return errCode["ED25519SignFail"];
        }

        // 4.组装结构
        $sign_body["signature_value"] = $out[0];

        $data["payload"] = $json_str;
        $data["signature"] = $sign_body;

        return 0;
    }

    function signTx($old_script,$sign_param,&$new_script){
        if($old_script == ""){
            return errCode["InvalidParamsErrCode"];
        }

        if($sign_param == NULL){
            return errCode["InvalidParamsErrCode"];
        }

        $old_json_str = base64_decode($old_script);
        //解开script拿到公钥
        $old_data = json_decode($old_json_str,true);

        if(empty($old_data)){
            $new_script = "";
            return errCode["DeserializeDataFail"];
        }
        
        if($old_data["publicKey"] == ""){
            $new_script = "";
            return errCode["DeserializeDataFail"];
        }

        $key = $sign_param->getPrivateKey();
        $did = $sign_param->getCreator();
        $nonce = $sign_param->getNonce();

        // 3.签名
        $bin = __DIR__ . "/../../utils/bin/sign-util";
        $cmd = $bin . " -key " . $key . " -nonce " . $nonce . " -did " . $did . " -data " .$old_data["publicKey"];

        exec($cmd,$out);
        if (empty($out)){
            return errCode["ED25519SignFail"];
        }

        $new_data = array(
            "creator" => $sign_param->getCreator(),
            "nonce" => $sign_param->getNonce(),
            "publicKey" => $old_data["publicKey"],
            "signature" => $out[0],
        );

        $new_json_str = json_encode($new_data);
        $new_script = base64_encode($new_json_str);

        if($new_script == ""){
            return errCode["ED25519SignFail"];
        }
        return 0;
    }
}
