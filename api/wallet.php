<?php

require (__DIR__ . "/../../php-common/cryption/crypto.php");
require (__DIR__ . "/../../php-common/cryption/sign.php");

interface WalletApi {
    // 注册钱包
    function register($register_body,&$response);
    // 获取钱包的基本信息
  //  function getWalletInfo();
    // 创建数字资产
   function createPOE($poe_body,$sign_body,&$response);
    // 上传数字资产凭证
   // function uploadPOEFile();
    // 发行资产
  //  function issuerAsset();
    // 转让资产
   // function transferAsset();
    // 交易历史
   // function tranfserTxn();
}

class WalletClient implements WalletApi {
    var $host;
    var $cert_path;
    var $api_key;
    var $did;
    var $curl_client;
    var $ecc_client;
    var $sign_client;
    var $header;

    function __construct($host,$api_key,$cert_path,$did){
        $this->host = $host;
        $this->api_key = $api_key;
        $this->cert_path = $cert_path;
        $this->did = $did;
        $this->curl_client = curl_init();
        $this->ecc_client = new encrypt($cert_path,$api_key);
        $this->sign_client = NULL;

        // 设置http请求头
        $header = array();
        $header[] = 'API-Key:' . $api_key;
        $header[] = 'Content-Type: application/json;charset=utf-8'; 
        $header[] = 'Bc-Invoke-Mode:sync';
        curl_setopt($this->curl_client, CURLOPT_HTTPHEADER, $header);
        curl_setopt($this->curl_client, CURLOPT_RETURNTRANSFER, 1);        
    }

    function register($register_body,&$response){
        $this->ecc_client->signAndEncrypt($register_body,$request);
        if ($data == ""){
            return -1;
        }
        curl_setopt($this->curl_client, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl_client, CURLOPT_POSTFIELDS, $request);
        $url = $this->host . "/wallet-ng/v1/wallet/register";
        curl_setopt ($this->curl_client, CURLOPT_URL, $url);
        $res = curl_exec($this->curl_client);
        if ($res == ""){
            echo "curl error" ,"\n";
            return -1;
        }

        $this->ecc_client->DecryptAndVerify($res,$data);
        if (empty($data)){
            echo "decrypt_and_verify error" , "\n"; 
            return -2;
        }

        $response = $data;
        return 0;
    }

    function createPOE($poe_body,$sign_body,&$response) {
        // 创建ed25519对象
        $this->sign_client = new Signature($sign_body);

        // 签名
        $this->sign_client->sign($poe_body,$signed_data);

        // 销毁签名对象
        unset($this->sign_client); 
        $this->sign_client = NULL;

        // 签名返回的数据，交给ecc去加密签名
        $this->ecc_client->signAndEncrypt($signed_data,$request);

        // 发送请求
        curl_setopt($this->curl_client, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl_client, CURLOPT_POSTFIELDS, $request);
        $url = $this->host . "/wallet-ng/v1/poe/create";
        curl_setopt ($this->curl_client, CURLOPT_URL, $url);

        $res = curl_exec($this->curl_client);
        if ($res == ""){
            echo "curl error" ,"\n";
            return -1;
        }

        $this->ecc_client->decryptAndVerify($res,$data);
        if (empty($data)){
            echo "decrypt_and_verify error" , "\n"; 
            return -2;
        }

        $response = $data;
        return 0;
    }

    /*
    function getWalletInfo(){

    }

    function uploadPOEFile(){

    }

    function issuerAsset(){

    }

    function transferAsset(){

    }

    function tranfserTxn(){

    }

     */

    // 析构函数
    function __destruct(){
        curl_close($this->curl_client);
    }

}
