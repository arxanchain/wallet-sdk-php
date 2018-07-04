<?php

namespace arxan;

require_once (__DIR__ . "/../../vendor/autoload.php");
use arxan\Encrypt;

interface SafeBoxApi {
    // 托管秘钥
    function trusteeKeyPair($did,$key_pair,&$response);
    // 更新安全码
    function updateAssistCode($did,$old,$new,&$response);
    // 删除秘钥
    function deleteKeyPair($did,$code,&$response);
    // 获取安全码
    function recoverAssistCode($did,&$response);
    // 获取私钥
    function queryPrivateKey($did,$code,&$response);
    // 获取公钥
    function queryPublicKey($did,$code,&$response);
    
}

class SafeBoxClient implements SafeBoxApi {
    var $host;
    var $api_key;
    var $cert_path;
    var $curl_post;
    var $curl_get;
    var $ecc_client;
    var $header;

    function __construct($host,$api_key,$cert_path){
        $this->host = $host;
        $this->api_key = $api_key;
        $this->cert_path = $cert_path;
        $this->ecc_client = new Encrypt($cert_path,$api_key);

        $this->header = array();
        $this->header[0] = 'API-Key:' . $api_key;
        $this->header[1] = 'Content-Type: application/json;charset=utf-8'; 
        $this->curl_post = curl_init();
        $this->curl_get = curl_init();
        //设置获取的信息以文件流的形式返回，而不是直接输出
        curl_setopt($this->curl_post, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->curl_post, CURLOPT_HTTPHEADER, $this->header);
        curl_setopt($this->curl_post, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl_get, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->curl_get, CURLOPT_HTTPHEADER, $this->header);
    }

    function trusteeKeyPair($did,$key_pair,&$response){
        if($did ==""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if(empty($key_pair)){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        $body = array(
            "user_did" => $did,
            "private_key" => $key_pair["private_key"],
            "public_key" => $key_pair["public_key"],
        );

        // 1.对数据进行加密签名处理
        $ret = $this->ecc_client->signAndEncrypt($body,$request);
        if ($ret != 0){
            $response = errorResponse($ret);
            return $ret;
        }

        // 2.设置http请求
        curl_setopt($this->curl_post, CURLOPT_POSTFIELDS, $request);
        $url = $this->host . "/safebox/v1/keypair/save";
        curl_setopt ($this->curl_post, CURLOPT_URL, $url);

        // 3.发送请求
        $res = curl_exec($this->curl_post);
        if ($res == ""){
            // curl 失败认为都是参数错误
            $response = errorResponse(errCode["InvalidRequestBody"]);
            return errCode["InvalidRequestBody"];
        }

        // 4.解密数据
        $ret = $this->ecc_client->DecryptAndVerify($res,$data);
        if ($ret != 0){
            $response = $data;
            return $ret;
        }

        $response = $data;
        return $response["ErrCode"];
    }

    function deleteKeyPair($did,$code,&$response){
        if($did == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if($code == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        $body = array(
            "user_did" => $did,
            "code" => $code,
        );

        // 1.对数据进行加密签名处理
        $ret = $this->ecc_client->signAndEncrypt($body,$request);
        if ($ret != 0){
            $response = errorResponse($ret);
            return $ret;
        }

        // 2.设置http请求
        $url = $this->host . "/safebox/v1/keypair/delete";
        curl_setopt ($this->curl_post, CURLOPT_URL, $url);
        curl_setopt($this->curl_post, CURLOPT_POSTFIELDS, $request);

        // 3.发送请求
        $res = curl_exec($this->curl_post);
        if ($res == ""){
            $response = errorResponse(errCode["InvalidRequestBody"]);
            return errCode["InvalidRequestBody"];
        }

        // 4.验签解密
        $ret = $this->ecc_client->decryptAndVerify($res,$data);
        if ($ret != 0) {
            $response = $data;
            return $ret;
        }

        $response = $data;
        return $response["ErrCode"];
    }

    function updateAssistCode($did,$old,$new,&$response){
        if($did == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if($old == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if($new == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        $body = array(
            "user_did" => $did,
            "original_code" => $old,
            "new_code" => $new,
        );

        // 1.对数据进行加密签名处理
        $ret = $this->ecc_client->signAndEncrypt($body,$request);
        if ($ret != 0){
            $response = errorResponse($ret);
            return $ret;
        }

        // 2.设置http请求
        $url = $this->host . "/safebox/v1/code/update";
        curl_setopt ($this->curl_post, CURLOPT_URL, $url);
        curl_setopt($this->curl_post, CURLOPT_POSTFIELDS, $request);

        // 3.发送请求
        $res = curl_exec($this->curl_post);
        if ($res == ""){
            $response = errorResponse(errCode["InvalidRequestBody"]);
            return errCode["InvalidRequestBody"];
        }

        // 4.验签解密
        $ret = $this->ecc_client->decryptAndVerify($res,$data);
        if ($ret != 0) {
            $response = $data;
            return $ret;
        }

        $response = $data;
        return $response["ErrCode"];
    }

    function QueryPrivateKey($did,$code,&$response){
        if($did == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if($code == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        //发送get请求
        $url = $this->host . "/safebox/v1/keypair/private?user_did=" . $did . "&code=" . $code;
        curl_setopt($this->curl_get, CURLOPT_URL, $url);
        $res = curl_exec($this->curl_get);
        if ($res == ""){
            $response = errorResponse(errCode["InvalidRequestBody"]);
            return errCode["InvalidRequestBody"];
        }

        // 验签解密
        $ret = $this->ecc_client->decryptAndVerify($res,$data);
        if ($ret != 0) {
            $response = $data;
            return $ret;
        }

        $response = $data;
        return $response["ErrCode"];
    }

    function queryPublicKey($did,$code,&$response){
        if($did == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if($code == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        //发送get请求
        $url = $this->host . "/safebox/v1/keypair/public?user_did=" . $did . "&code=" . $code;
        curl_setopt($this->curl_get, CURLOPT_URL, $url);
        $res = curl_exec($this->curl_get);
        if ($res == ""){
            $response = errorResponse(errCode["InvalidRequestBody"]);
            return errCode["InvalidRequestBody"];
        }

        // 验签解密
        $ret = $this->ecc_client->decryptAndVerify($res,$data);
        if ($ret != 0) {
            $response = $data;
            return $ret;
        }

        $response = $data;
        return $response["ErrCode"];
    }

    function recoverAssistCode($did,&$response){
        if($did == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        //发送get请求
        $url = $this->host . "/safebox/v1/code?user_did=" . $did;
        curl_setopt($this->curl_get, CURLOPT_URL, $url);
        $res = curl_exec($this->curl_get);
        if ($res == ""){
            $response = errorResponse(errCode["InvalidRequestBody"]);
            return errCode["InvalidRequestBody"];
        }

        // 验签解密
        $ret = $this->ecc_client->decryptAndVerify($res,$data);
        if ($ret != 0) {
            $response = $data;
            return $ret;
        }

        $response = $data;
        return $response["ErrCode"];
    }

    // 析构函数
    function __destruct(){
        curl_close($this->curl_post);
        curl_close($this->curl_get);
    }

}
