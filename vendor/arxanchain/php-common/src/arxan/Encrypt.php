<?php

namespace arxan;

//require_once (__DIR__ . "/../log/log.php");
require_once (__DIR__ . "/errCode.php");

// ecc 签名加密
class Encrypt{
    var $api_key;
    var $path;
    var $mode1 = 1; // 加密与签名
    var $mode2 = 2; // 验签与解密

    function __construct($path,$api_key){
        $this->api_key = $api_key;
        $this->path = $path;
    }

    function signAndEncrypt($data,&$cipher_text){
        if(empty($data)){
            //$message = "invalid params";
            //ErrLogChain(__FILE__,__LINE__,$message);
            return errCode["InvalidParamsErrCode"];
        }

        //1.进行json编码
        $json_str = json_encode($data);
        if ($json_str == ""){
            $cipher_text = "";
            return errCode["SerializeDataFail"];
        }

        //2.base64编码
        $base64_str = base64_encode($json_str);
        if ($base64_str == ""){
            $cipher_text = "";
            return errCode["SerializeDataFail"];
        }

        // 拼装加密命令
        $bin = __DIR__ . "/../../utils/bin/crypto-util";
        $cmd = $bin . " -apikey " .  $this->api_key . " -data " . $base64_str. " -path " . $this->path . " -mode " . $this->mode1;

        //3.加密签名
        exec($cmd,$out);
        if (empty($out)){
            $cipher_text = "";
            return errCode["SerializeDataFail"];
        }
        $cipher_text = $out[0];
        return 0;
    }

    function decryptAndVerify($cipher_text,&$data){
        if($cipher_text == ""){
            return errCode["InvalidParamsErrCode"];
        }

        // 拼装解密命令
        $bin = __DIR__ . "/../../utils/bin/crypto-util";
        $cmd = $bin . " -apikey " .  $this->api_key . " -data " . $cipher_text. " -path " . $this->path . " -mode " . $this->mode2;

        //1.验签与解密
        exec($cmd,$out);
        if (empty($out)){
            return errCode["DeserializeDataFail"];
        }
        
        //2.json解码
        $data = json_decode($out[0],true);
        if (empty($data)){
            $data = $out[0];
            return errCode["DeserializeDataFail"];
        }

        if ($data["ErrCode"] == 0) {
            if($data["Payload"]!=""){
                $data["Payload"] = json_decode($data["Payload"],true);
            }
        }
        
        return $data["ErrCode"];
    } 
}


