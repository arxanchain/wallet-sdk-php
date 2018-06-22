<?php

function NewWalletClient(){

}

interface WalletApi{
    // 注册钱包
    function register($register_body);
    // 获取钱包的基本信息
  //  function getWalletInfo();
    // 创建数字资产
   // function createPOE();
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
    $curl_client;
    function __construct(RestApiConfig $config){
        $this->curl_client = curl_init();        
    }

    function register($register_body){

    }

    /*
    function getWalletInfo(){

    }

    function createPOE(){

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
