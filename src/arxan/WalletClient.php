<?php

namespace arxan;

require_once (__DIR__ . "/../../vendor/autoload.php");
require_once (__DIR__ . "/errorResponse.php");

use arxan\Encrypt;
use arxan\Signature;
use arxan\SafeBoxClient;
use arxan\structs\SignParam;

interface WalletApi {
    // 注册钱包 
    function register($register_body,&$response);
    // 获取钱包的基本信息
    function getWalletInfo($did,&$response);
    // 获取钱包余额
    function getWalletBalance($did,&$response); 
    // 查询数字资产存证 
    function queryPOE($did,&$response);
    // 创建数字资产
    function createPOE($poe_body,$sign_param,$security_code,&$response);
    // 上传数字资产凭证
    //function uploadPOEFile($asset_id,$file,$mode,&$response); //api暂时没处理
    // 发行资产
    function issueAsset($asset_body,$sign_param,$security_code,&$response);
    // 发行token
    function issueCToken($ctoken_body,$sign_param,$security_code,&$response);
    // 转让资产
    function transferAsset($transfer_body,$sign_param,$security_code,&$response);
    // 转移ctoken
    function transferCToken($transfer_body,$sign_param,$security_code,&$response);
    // 交易历史
    function queryTxnLogs($did,$mode,&$response);
    // 预发行数字凭证
    function sendIssueCTokenProposal($ctoken_body,$sign_param,$security_code,&$response);
    // 预发行数字资产
    function sendIssueAssetProposal($asset_body,$sign_param,$security_code,&$response);
    // 预转让数字凭证
    function sendTransferCTokenProposal($transfer_body,$sign_param,$security_code,&$response);
    // 预转让数字资产
    function sendTransferAssetProposal($transfer_body,$sign_param,$security_code,&$response);
    // 交易处理
    function processTx($txs,&$response);
    // 保存秘钥
    function saveKeyPair($did,$key_pair,&$security_code);
    // 获取私钥
    function getPrivateKey($did,$security_code,&$private);
    // 获取安全码
    function getAssistCode($did,&$security_code);
    // 删除秘钥对
    //function deleteKeyPair();
    // 修改安全码
    //function updateKeyPair();
    
    // 获取最新的区块信息,包含区块高度
    function queryBlockInfo($number,&$response);
    // 获取交易的详细信息
    function getTxnDetail($txn_id,&$response);

}

class WalletClient implements WalletApi {
    var $host;
    var $cert_path;
    var $api_key;
    var $did;
    var $curl_post;
    var $curl_get;
    var $ecc_client;
    var $sign_client;
    var $header;
    var $safebox_client;
    var $sign_param;

    function __construct($host,$api_key,$cert_path,$sign_param){
        $this->host = $host;
        $this->api_key = $api_key;
        $this->cert_path = $cert_path;
        $this->did = $sign_param->getCreator();
        $this->curl_post = curl_init();
        $this->curl_get = curl_init();
        $this->ecc_client = new Encrypt($cert_path,$api_key);
        $this->sign_client = new Signature();
        $this->safebox_client = new SafeBoxClient($host,$api_key,$cert_path);
        if($sign_param == NULL){
            return;
        }
        $this->sign_param = $sign_param;

        // 设置http请求头
        $this->header = array();
        $this->header[0] = 'API-Key:' . $api_key;
        $this->header[1] = 'Content-Type: application/json;charset=utf-8'; 
        $this->header[2] = 'Bc-Invoke-Mode:sync';

        // 在此设置header是在用户不设置的情况下，默认为同步模式
        curl_setopt($this->curl_post, CURLOPT_HTTPHEADER, $this->header);
        //设置获取的信息以文件流的形式返回，而不是直接输出
        curl_setopt($this->curl_post, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($this->curl_get, CURLOPT_HTTPHEADER, $this->header);
        //设置获取的信息以文件流的形式返回，而不是直接输出
        curl_setopt($this->curl_get, CURLOPT_RETURNTRANSFER, 1);     
    }

    // 设置header，用于设置相应是同步还是异步
    function setHeader($key,$value){
        if($key == "" && $value == ""){
            return;
        }

        if($key != "Bc-Invoke-Mode" && $key != "Callback-Url"){
            return;
        }

        $this->header[2] = $key . ":" .$value;
        curl_setopt($this->curl_post, CURLOPT_HTTPHEADER, $this->header);
        curl_setopt($this->curl_get, CURLOPT_HTTPHEADER, $this->header);
        return;
    }

    function register($register_body,&$response){
        if ($register_body == NULL){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if($register_body->getType() == 0){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if($register_body->getsecret() == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        $ret = $this->ecc_client->signAndEncrypt($register_body,$request);
        if ($ret != 0){
            $response = errorResponse($ret);
            return $ret;
        }
         

        curl_setopt($this->curl_post, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl_post, CURLOPT_POSTFIELDS, $request);
        $url = $this->host . "/wallet-ng/v1/wallet/register";
        curl_setopt ($this->curl_post, CURLOPT_URL, $url);

        $res = curl_exec($this->curl_post);
        if ($res == ""){
            // curl 失败认为都是参数错误
            $response = errorResponse(errCode["InvalidRequestBody"]);
            return errCode["InvalidRequestBody"];
        }
        
        // 如果直接给的json数据则不需要解密直接返回 
        $errData= json_decode($res,TRUE);
        if (!empty($errData)){
            $response = $errData;
            return $errData["ErrCode"];
        } 

        $ret = $this->ecc_client->DecryptAndVerify($res,$data);
        if ($ret != 0){
            $response = $data;
            return $ret;
        }

        // 保存秘钥
        $ret = $this->saveKeyPair($data["Payload"]["id"],$data["Payload"]["key_pair"],$code);
        if($ret !=0){
            // 保存秘钥失败
            $response = errorResponse($ret);
            return $ret;
        }
        $data["Payload"]["key_pair"] = NULL;
        $data["Payload"]["security_code"] = $code;

        $response = $data;
        return $response["ErrCode"];
    }

    function createPOE($poe_body,$sign_param,$security_code,&$response) {
        if ($poe_body == NULL){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if($poe_body->getName() == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if($poe_body->getOwner() == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if ($sign_param == NULL){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if($sign_param->getCreator() == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        //获取秘钥
        $ret = $this->getPrivateKey($sign_param->getCreator(),$security_code,$private);
        if($ret != 0){
            $response = errorResponse($ret);
            return $ret;
        }
        $sign_param->setPrivateKey($private);

        // 签名
        $ret = $this->sign_client->sign($poe_body,$sign_param,$signed_data);
        if ($ret!=0){
            $response = errorResponse($ret);
            return $ret;
        }

        // 签名返回的数据，交给ecc去加密签名
        $ret = $this->ecc_client->signAndEncrypt($signed_data,$request);
        if ($ret !=0){
            $response = errorResponse($ret);
            return $ret;
        }

        // 发送请求
        curl_setopt($this->curl_post, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl_post, CURLOPT_POSTFIELDS, $request);
        $url = $this->host . "/wallet-ng/v1/poe/create";
        curl_setopt ($this->curl_post, CURLOPT_URL, $url);

        $res = curl_exec($this->curl_post);
        if ($res == ""){
            $response = errorResponse(errCode["InvalidRequestBody"]);
            return errCode["InvalidRequestBody"];
        }

        // 如果直接给的json数据则不需要解密直接返回 
        $errData= json_decode($res,TRUE);
        if (!empty($errData)){
            $response = $errData;
            return $errData["ErrCode"];
        } 

        $ret = $this->ecc_client->decryptAndVerify($res,$data);
        if($ret !=0){
            $response = $data;
            return $ret;
        }

        $response = $data;
        return $response["ErrCode"];
    }

    // TODO 上传存证文件
    /*
    function uploadPOEFile($asset_id,$file,$mode,&$response){
        $data = array(
            "poe_id"=> $asset_id,
            "poe_file"=> "$file",
            "read_only"=> "$mode",
        );
        $fur = "@" . $file;
        //$data["file"] = $fur;

        $upload_curl = curl_init();

        $header = array();
        $header[0] = 'API-Key:' . $this->api_key;
        $header[1] = 'Content-Type:multipart/form-data';
        $header[2] = 'Bc-Invoke-Mode:sync';

        $url = $this->host . "/wallet-ng/v1/poe/upload";
        curl_setopt($upload_curl, CURLOPT_URL, $url);

        curl_setopt($upload_curl, CURLOPT_HTTPHEADER, $header);
        //curl_setopt($upload_curl, CURLOPT_CUSTOMREQUEST, 'POST');
        //设置获取的信息以文件流的形式返回，而不是直接输出
        curl_setopt($upload_curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($upload_curl, CURLOPT_POSTFIELDS, $data);
        //表单数据，是正规的表单设置值为非0
        curl_setopt($upload_curl, CURLOPT_POST, 1);
        curl_setopt($upload_curl,CURLOPT_BINARYTRANSFER,true);

        // 发送请求
        $res = curl_exec($upload_curl);
        if ($res == ""){
            return errCode["InvalidRequestBody"];
        }

        // 加密与验签

        $ret = $this->ecc_client->decryptAndVerify($res,$data);
        if ($ret !=0){
            return $ret;
        }

        $json_obj = json_decode($res,true);

        var_dump($json_obj);

        curl_close($upload_curl);
        $response = $data;
        return 0;
        //return $response["ErrCode"];
    }
     */

    // 发行资产
    function issueAsset($asset_body,$sign_param,$security_code,&$response){
        if ($asset_body == NULL){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if($asset_body->getIssuer() == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if($asset_body->getOwner() == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if($asset_body->getAssetId() == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if ($sign_param == NULL){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if($sign_param->getCreator() == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        //1.先执行 sendIssueAssetProposal
        $ret = $this->sendIssueAssetProposal($asset_body,$sign_param,$security_code,$prepare);
        if ($ret != 0){
            $response = errorResponse($ret);
            return $ret;
        }

        //2.签名UTXO
        $ret = $this->signTxs($prepare["Payload"],$sign_param,$security_code);
        if($ret !=0){
            return $ret;
        }

        //3.执行 processTx
        $txs = array(
            "txs"=> $prepare["Payload"],
        );

        $ret = $this->processTx($txs,$data);
        if ($ret != 0){
            $response = errorResponse($ret);
            return $ret;
        }

        $response = $data;
        return $response["ErrCode"];
    }

    // 发行token
    function issueCToken($ctoken_body,$sign_param,$security_code,&$response){
        if ($ctoken_body == NULL){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if($ctoken_body->getIssuer() == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if($ctoken_body->getOwner() == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if($ctoken_body->getAssetId() == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if($ctoken_body->getAmount() == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if ($sign_param == NULL){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if($sign_param->getCreator() == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        // 1.发送预发行接口
        $ret = $this->sendIssueCTokenProposal($ctoken_body,$sign_param,$security_code,$prepare);
        if($ret !=0){
            $response = errorResponse($ret);
            return $ret;
        }

        //2.签名UTXO
        $ret = $this->signTxs($prepare["Payload"]["txs"],$sign_param,$security_code);
        if($ret !=0){
            return $ret;
        }

        // 3..确认操作
        $txs = array(
            "txs"=> $prepare["Payload"]["txs"],
        );

        $ret = $this->processTx($txs,$data);
        if ($ret != 0){
            $response = errorResponse($ret);
            return $ret;
        }

        $response = $data;
        $response["Payload"]["token_id"] = $prepare["Payload"]["token_id"];
        return $response["ErrCode"];
    }

    // 转让资产
    function transferAsset($transfer_body,$sign_param,$security_code,&$response){
        if ($transfer_body == NULL){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if($transfer_body->getFrom() == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if($transfer_body->getTo() == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if(empty($transfer_body->getAssets())){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if ($sign_param == NULL){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if($sign_param->getCreator() == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        // 1.发送预发行接口
        $ret = $this->sendTransferAssetProposal($transfer_body,$sign_param,$security_code,$prepare);
        if($ret !=0){
            $response = errorResponse($ret);
            return $ret;
        }

        // 2.签名UTXO
        $ret = $this->signTxs($prepare["Payload"],$sign_param,$security_code);
        if($ret !=0){
            return $ret;
        }

        // 3.确认操作
        $txs = array(
            "txs"=> $prepare["Payload"],
        );

        $ret = $this->processTx($txs,$data);
        if ($ret != 0){
            $response = $data;
            return $ret;
        }

        $response = $data;
        return $response["ErrCode"];
    }

    // 转让token
    function transferCToken($transfer_body,$sign_param,$security_code,&$response){
        if ($transfer_body == NULL){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if($transfer_body->getFrom() == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if($transfer_body->getTo() == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if(empty($transfer_body->getTokens())){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }
        
        if ($sign_param == NULL){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if($sign_param->getCreator() == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        // 1.发送预发行接口
        $ret = $this->sendTransferCTokenProposal($transfer_body,$sign_param,$security_code,$prepare);
        if($ret !=0){
            $response = errorResponse($ret);
            return $ret;
        }

        // 2.签名UTXO
        $ret = $this->signTxs($prepare["Payload"],$sign_param,$security_code);
        if($ret !=0){
            return $ret;
        }

        // 3.确认操作
        $txs = array(
            "txs"=> $prepare["Payload"],
        );

        $ret = $this->processTx($txs,$data);
        if ($ret != 0){
            $response = $data;
            return $ret;
        }

        $response = $data;
        return $response["ErrCode"];

    }

    function queryTxnLogs($did,$type,&$response){
        if($did == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        $url = "";
        if($type == ""){
            $url = $this->host . "/wallet-ng/v1/transaction/logs?id=" . $did;
        } else if($type == "in"){
            $url = $this->host . "/wallet-ng/v1/transaction/logs?id=" . $did . "&type=[in]";
        } else if($type == "out"){
            $url = $this->host . "/wallet-ng/v1/transaction/logs?id=" . $did . "&type=[out]";
        } else {
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        //发送get请求
        curl_setopt($this->curl_get, CURLOPT_URL, $url);

        $res = curl_exec($this->curl_get);
        if ($res == ""){
            $response = errorResponse(errCode["InvalidRequestBody"]);
            return errCode["InvalidRequestBody"];
        }

        // 验签解密
        $ret = $this->ecc_client->decryptAndVerify($res,$data);
        if ($ret != 0){
            $response = $data;
            return $ret;
        }

        $response = $data;
        return $response["ErrCode"];
    }


    function getWalletInfo($did,&$response){
        if($did == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        //发送get请求
        $url = $this->host . "/wallet-ng/v1/wallet/info?id=" . $did;
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

    function getWalletBalance($did,&$response){
        if($did == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        //发送get请求
        $url = $this->host . "/wallet-ng/v1/wallet/balance?id=" . $did;
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

    function queryPOE($did,&$response){
        if($did == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        //发送get请求
        $url = $this->host . "/wallet-ng/v1/poe?id=" . $did;
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

    function queryBlockInfo($number,&$response){
        // count 表示从新的区块往前第几个的信息
        if(!is_numeric($number)){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        $url = $this->host ."/chain-monitor/v1/chain/block_list?number=" . $number;
        $routeTag = "chain-monitor";
        $header = array();
        $header[0] = 'API-Key:' . $this->api_key;
        $header[1] = 'Route-Tag:'. $routeTag;
        $header[2] = 'Host:'. $routeTag ;
        
        curl_setopt($this->curl_get, CURLOPT_HTTPHEADER, $header);
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

    function getTxnDetail($txn_id,&$response){
        if($txn_id == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        $url = $this->host ."/chain-monitor/v1/chain/txn_detail?txn_id=" . $txn_id;
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

    function sendIssueCTokenProposal($ctoken_body,$sign_param,$security_code,&$response){
        if ($ctoken_body == NULL){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if($ctoken_body->getIssuer() == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if($ctoken_body->getOwner() == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if($ctoken_body->getAssetId() == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if($ctoken_body->getAmount() == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if ($sign_param == NULL){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if($sign_param->getCreator() == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }
        //获取秘钥
        $ret = $this->getPrivateKey($sign_param->getCreator(),$security_code,$private);
        if($ret != 0){
            $response = errorResponse($ret);
            return $ret;
        }
        $sign_param->setPrivateKey($private);
        // 签名
        $ret = $this->sign_client->sign($ctoken_body,$sign_param,$signed_data);
        if ($ret != 0){
            $response = errorResponse($ret);
            return $ret;
        }

        // 签名返回的数据，交给ecc去加密签名
        $ret = $this->ecc_client->signAndEncrypt($signed_data,$request);
        if ($ret != 0){
            $response = errorResponse($ret);
            return $ret;
        }

        // 发送请求
        curl_setopt($this->curl_post, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl_post, CURLOPT_POSTFIELDS, $request);
        $url = $this->host . "/wallet-ng/v1/transaction/tokens/issue/prepare";
        curl_setopt ($this->curl_post, CURLOPT_URL, $url);

        $res = curl_exec($this->curl_post);
        if ($res == ""){
            $response = errorResponse(errCode["InvalidRequestBody"]);
            return errCode["InvalidRequestBody"];
        }

        // 如果直接给的json数据则不需要解密直接返回 
        $errData= json_decode($res,TRUE);
        if (!empty($errData)){
            $response = $errData;
            return $errData["ErrCode"];
        } 

        $ret = $this->ecc_client->decryptAndVerify($res,$data);
        if ($ret != 0){
            $response = $data;
            return $ret;
        }

        $response = $data;
        return $response["ErrCode"];
    }

    function sendIssueAssetProposal($asset_body,$sign_param,$security_code,&$response){
        if ($asset_body == NULL){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if($asset_body->getIssuer() == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if($asset_body->getOwner() == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if($asset_body->getAssetId() == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if ($sign_param == NULL){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if($sign_param->getCreator() == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        $ret = $this->getPrivateKey($sign_param->getCreator(),$security_code,$private);
        if($ret != 0){
            $response = errorResponse($ret);
            return $ret;
        }
        $sign_param->setPrivateKey = $private;
        // 签名
        $ret = $this->sign_client->sign($asset_body,$sign_param,$signed_data);
        if ($ret != 0){
            $response = errorResponse($ret);
            return $ret;
        }

        // 签名返回的数据，交给ecc去加密签名
        $ret = $this->ecc_client->signAndEncrypt($signed_data,$request);
        if ($ret != 0){
            $response = errorResponse($ret);
            return $ret;
        }

        // 发送请求
        $url = $this->host . "/wallet-ng/v1/transaction/assets/issue/prepare";
        curl_setopt($this->curl_post, CURLOPT_URL, $url);
        curl_setopt($this->curl_post, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl_post, CURLOPT_POSTFIELDS, $request);

        $res = curl_exec($this->curl_post);
        if ($res == ""){
            $response = errorResponse(errCode["InvalidRequestBody"]);
            return errCode["InvalidRequestBody"];
        }

        // 如果直接给的json数据则不需要解密直接返回 
        $errData= json_decode($res,TRUE);
        if (!empty($errData)){
            $response = $errData;
            return $errData["ErrCode"];
        } 

        $ret = $this->ecc_client->decryptAndVerify($res,$data);
        if ($ret != 0){
            $response = $data;
            return $ret;
        }
        
        $response = $data;
        return $response["ErrCode"];
    }

    function sendTransferCTokenProposal($transfer_body,$sign_param,$security_code,&$response){
        if ($transfer_body == NULL){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if($transfer_body->getFrom() == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if($transfer_body->getTo() == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if(empty($transfer_body->getTokens())){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if ($sign_param == NULL){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if($sign_param->getCreator() == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        $ret = $this->getPrivateKey($sign_param->getCreator(),$security_code,$private);
        if($ret != 0){
            $response = errorResponse($ret);
            return $ret;
        }
        $sign_param->setPrivateKey($private);
        // 签名
        $ret = $this->sign_client->sign($transfer_body,$sign_param,$signed_data);
        if ($ret != 0){
            $response = errorResponse($ret);
            return $ret;
        }

        // 签名返回的数据，交给ecc去加密签名
        $ret = $this->ecc_client->signAndEncrypt($signed_data,$request);
        if ($ret != 0){
            $response = errorResponse($ret);
            return $ret;
        }

        // 发送请求
        curl_setopt($this->curl_post, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl_post, CURLOPT_POSTFIELDS, $request);
        $url = $this->host . "/wallet-ng/v1/transaction/tokens/transfer/prepare";
        curl_setopt ($this->curl_post, CURLOPT_URL, $url);

        $res = curl_exec($this->curl_post);
        if ($res == ""){
            $response = errorResponse(errCode["InvalidRequestBody"]);
            return errCode["InvalidRequestBody"];
        }

        // 如果直接给的json数据则不需要解密直接返回 
        $errData= json_decode($res,TRUE);
        if (!empty($errData)){
            $response = $errData;
            return $errData["ErrCode"];
        } 

        $ret = $this->ecc_client->decryptAndVerify($res,$data);
        if ($ret != 0){
            $response = $data;
            return $ret;
        }

        $response = $data;
        return $response["ErrCode"];
    }

    function sendTransferAssetProposal($transfer_body,$sign_param,$security_code,&$response){
        if ($transfer_body == NULL){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if($transfer_body->getFrom() == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if($transfer_body->getTo() == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if(empty($transfer_body->getAssets())){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if ($sign_param == NULL){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if($sign_param->getCreator() == ""){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        // 获取私钥
        $ret = $this->getPrivateKey($sign_param->getCreator(),$security_code,$private);
        if($ret != 0){
            $response = errorResponse($ret);
            return $ret;
        }
        $sign_param->setPrivateKey($private);

        // 签名
        $ret = $this->sign_client->sign($transfer_body,$sign_param,$signed_data);
        if ($ret != 0){
            $response = errorResponse($ret);
            return $ret;
        }

        // 签名返回的数据，交给ecc去加密签名
        $ret = $this->ecc_client->signAndEncrypt($signed_data,$request);
        if ($ret != 0){
            $response = errorResponse($ret);
            return $ret;
        }

        // 发送请求
        curl_setopt($this->curl_post, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl_post, CURLOPT_POSTFIELDS, $request);
        $url = $this->host . "/wallet-ng/v1/transaction/assets/transfer/prepare";
        curl_setopt ($this->curl_post, CURLOPT_URL, $url);

        $res = curl_exec($this->curl_post);
        if ($res == ""){
            $response = errorResponse(errCode["InvalidRequestBody"]);
            return errCode["InvalidRequestBody"];
        }

        // 如果直接给的json数据则不需要解密直接返回 
        $errData= json_decode($res,TRUE);
        if (!empty($errData)){
            $response = $errData;
            return $errData["ErrCode"];
        } 

        $ret = $this->ecc_client->decryptAndVerify($res,$data);
        if ($ret != 0){
            $response = $data;
            return $ret;
        }

        $response = $data;
        return $response["ErrCode"];
    }

    function processTx($txs_array,&$response){
        if (empty($txs_array)){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        $ret = $this->ecc_client->signAndEncrypt($txs_array,$request);
        if ($ret != 0){
            $response = errorResponse($ret);
            return $ret;
        }

        curl_setopt($this->curl_post, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl_post, CURLOPT_POSTFIELDS, $request);
        $url = $this->host . "/wallet-ng/v1/transaction/process";
        curl_setopt ($this->curl_post, CURLOPT_URL, $url);

        $res = curl_exec($this->curl_post);
        if ($res == ""){
            $response = errorResponse(errCode["InvalidRequestBody"]);
            return errCode["InvalidRequestBody"];
        }

        $ret = $this->ecc_client->DecryptAndVerify($res,$data);
        if ($ret != 0){
            $response = $data;
            return $ret;
        }

        $response = $data;
        return $response["ErrCode"];
    }

    function saveKeyPair($did,$key_pair,&$security_code){
        $ret = $this->safebox_client->trusteeKeyPair($did,$key_pair,$res);
        if($ret != 0){
            $security_code = "";
            return $ret;
        }
        $security_code = $res["Payload"]["code"];
        return 0;
    }

    function getPrivateKey($did,$security_code,&$private){
        $ret = $this->safebox_client->queryPrivateKey($did,$security_code,$res);
        if($ret != 0){
            $private = "";
            return $ret;
        }
        $private = $res["Payload"]["private_key"];
        return 0;
    }

    function getAssistCode($did,&$security_code){
        $ret = $this->safebox_client->recoverAssistCode($did,$res);
        if($ret != 0){
            $security_code = "";
            return $ret;
        }
        $private = $res["Payload"]["code"];
        return 0;
    }

    private function signTxs(&$txs,$sign_param,$security_code){
        for($i = 0;$i<count($txs);$i++){
            if($txs[$i]["founder"] != $sign_param->getCreator()){
                if($this->sign_param->getPrivateKey()== ""){
                    return errCode["InvalidPrivateKey"];
                }
                
                $ret = $this->signTx($txs[$i],$this->sign_param);
                if($ret != 0){
                    return $ret;
                }
            }else{
                $ret = $this->getPrivateKey($sign_param->getCreator(),$security_code,$private);
                if($ret != 0){
                    return $ret;
                }
                $sign_param->setPrivateKey($private);

                $ret = $this->signTx($txs[$i],$sign_param);
                if($ret !=0){
                    return $ret;
                }
            }
        }
        return 0;  
    }

    private function signTx(&$tx,$sign_param){
        for($i = 0;$i<count($tx["txout"]);$i++){
            $old_script = $tx["txout"][$i]["script"];
            if($old_script == ""){
                continue;
            }

            $ret = $this->sign_client->signTx($old_script,$sign_param,$new_script);
            if($ret != 0){
                $response = errorResponse($ret);
                return $ret;
            }
            $tx["txout"][$i]["script"] = $new_script;
        }
        return 0;
    }

    // 析构函数
    function __destruct(){
        curl_close($this->curl_post);
        curl_close($this->curl_get);
    }

}
