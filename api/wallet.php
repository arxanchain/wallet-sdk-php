<?php

require_once (__DIR__ . "/../../php-common/cryption/crypto.php");
require_once (__DIR__ . "/../../php-common/cryption/sign.php");
require_once (__DIR__ . "/../../php-common/error/error.php");
require_once (__DIR__ . "/../../php-common/log/log.php");
require_once (__DIR__ . "/common.php");

interface WalletApi {
    // 注册钱包 
    function register($register_body,&$response);
    // 获取钱包的基本信息
    function getWalletInfo($did,&$response);
    // 获取钱包余额
    function getWalletBalance($did,&$response); 
    // 创建数字资产
    function createPOE($poe_body,$sign_body,&$response);
    // 查询数字资产存在 
    function getAssetInfo($did,&$response);
    // 上传数字资产凭证
    //function uploadPOEFile($asset_id,$file,$mode,&$response); //api暂时没处理
    // 发行资产
    function issuerAsset($asset_body,$sign_body,&$response);
    // 发行token
    function issuerCToken($ctoken_body,$sign_body,&$response);
    // 转让资产
    function transferAsset($transfer_body,$sign_body,&$response);
    // 转移ctoken
    function transferCToken($transfer_body,$sign_body,&$response);
    // 交易历史
    function tranfserTxn($did,$mode,&$response);
    // 预发行数字凭证
    function sendIssueCTokenProposal($ctoken_body,$sign_body,&$response);
    // 预发行数字资产
    function sendIssueAssetProposal($asset_body,$sign_body,&$response);
    // 预转让数字凭证
    function sendTransferCTokenProposal($transfer_body,$sign_body,&$response);
    // 预转让数字资产
    function sendTransferAssetProposal($transfer_body,$sign_body,&$response);
    // 交易处理
    function processTx($txs,&$response);
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

    function __construct($host,$api_key,$cert_path,$did){
        $this->host = $host;
        $this->api_key = $api_key;
        $this->cert_path = $cert_path;
        $this->did = $did;
        $this->curl_post = curl_init();
        $this->curl_get = curl_init();
        $this->ecc_client = new encrypt($cert_path,$api_key);
        $this->sign_client = new Signature();

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
    function setHeader($mode,$call_back){
        if($mode == ""||$call_back == ""){
            return;
        }

        if($mode != "Bc-Invoke-Mode"|| $mode != "Callback-Url"){
            return;
        }

        $this->header[2] = $mode . ":" .$call_back;
        curl_setopt($this->curl_post, CURLOPT_HTTPHEADER, $this->header);
        curl_setopt($this->curl_get, CURLOPT_HTTPHEADER, $this->header);
        return ;
    }

    function register($register_body,&$response){
        if (empty($register_body)){
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

        $ret = $this->ecc_client->DecryptAndVerify($res,$data);
        if ($ret != 0){
            $response = $data;
            return $ret;
        }

        $response = $data;
        return $response["ErrCode"];
    }

    function createPOE($poe_body,$sign_body,&$response) {
        if (empty($poe_body)){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if (empty($sign_body)){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        // 签名
        $ret = $this->sign_client->sign($poe_body,$sign_body,$signed_data);
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

        echo "res = \n",$res,"\n";
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
    function issuerAsset($asset_body,$sign_body,&$response){
        if (empty($asset_body)){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if (empty($sign_body)){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }
        
        //1.先执行 sendIssueAssetProposal
        $ret = $this->sendIssueAssetProposal($asset_body,$sign_body,$prepare);
        if ($ret != 0){
            echo "send asset proposal err" , "\n";
            $response = errorResponse($ret);
            return $ret;
        }

        //2. 签名
        $old_script = $prepare["Payload"][0]["txout"][0]["script"];
        $ret = $this->sign_client->signTx($old_script,$sign_body,$new_script);
        $prepare["Payload"][0]["txout"][0]["script"] = $new_script;

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
    function issuerCToken($ctoken_body,$sign_body,&$response){
        if (empty($ctoken_body)){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if (empty($sign_body)){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        // 1.发送预发行接口
        $ret = $this->sendIssueCTokenProposal($ctoken_body,$sign_body,$prepare);
        if($ret !=0){
            $response = errorResponse($ret);
            return $ret;
        }

        // 2.签名
        $old_script = $prepare["Payload"]["txs"][0]["txout"][0]["script"];
        $ret = $this->sign_client->signTx($old_script,$sign_body,$new_script);
        $prepare["Payload"]["txs"][0]["txout"][0]["script"] = $new_script;
        
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
    function transferAsset($transfer_body,$sign_body,&$response){
        if (empty($transfer_body)){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if (empty($sign_body)){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        // 1.发送预发行接口
        $ret = $this->sendTransferAssetProposal($transfer_body,$sign_body,$prepare);
        if($ret !=0){
            echo "transfer asset prepare error\n";
            $response = errorResponse($ret);
            return $ret;
        }

        // 2.签名
        $old_script = $prepare["Payload"][0]["txout"][0]["script"];
        $ret = $this->sign_client->signTx($old_script,$sign_body,$new_script);
        $prepare["Payload"][0]["txout"][0]["script"] = $new_script;

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
    function transferCToken($transfer_body,$sign_body,&$response){
        if (empty($transfer_body)){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if (empty($sign_body)){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }
        
        // 1.发送预发行接口
        $ret = $this->sendTransferCTokenProposal($transfer_body,$sign_body,$prepare);
        if($ret !=0){
            echo("sendTransferCTokenProposal error\n");
            $response = errorResponse($ret);
            return $ret;
        }
        
        // 2.签名
        $old_script = $prepare["Payload"][0]["txout"][0]["script"];
        $ret = $this->sign_client->signTx($old_script,$sign_body,$new_script);
        $prepare["Payload"][0]["txout"][0]["script"] = $new_script;
        
        // 3..确认操作
        $txs = array(
            "txs"=> $prepare["Payload"],
        );

        echo "process tx new request:\n";
        var_dump($txs);
        echo "\n";

        $ret = $this->processTx($txs,$data);
        if ($ret != 0){
            echo "process tx error\n";
            $response = $data;
            return $ret;
        }
        
        $response = $data;
        return $response["ErrCode"];
    }

    function tranfserTxn($did,$type,&$response){
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

        //echo "url = ",$url,"\n";
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

    function getAssetInfo($did,&$response){
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

    function sendIssueCTokenProposal($ctoken_body,$sign_body,&$response){
        if (empty($ctoken_body)){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if (empty($sign_body)){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        // 签名
        $ret = $this->sign_client->sign($ctoken_body,$sign_body,$signed_data);
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

        $ret = $this->ecc_client->decryptAndVerify($res,$data);
        if ($ret != 0){
            $response = $data;
            return $ret;
        }

        $response = $data;
        return $response["ErrCode"];
    }

    function sendIssueAssetProposal($asset_body,$sign_body,&$response){
        if (empty($asset_body)){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if (empty($sign_body)){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        // 签名
        $ret = $this->sign_client->sign($asset_body,$sign_body,$signed_data);
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
        
        $ret = $this->ecc_client->decryptAndVerify($res,$data);
        if ($ret != 0){
            $response = $data;
            return $ret;
        }

        $response = $data;
        return $response["ErrCode"];
    }

    function sendTransferCTokenProposal($transfer_body,$sign_body,&$response){
        if (empty($transfer_body)){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if (empty($sign_body)){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        // 签名
        $ret = $this->sign_client->sign($transfer_body,$sign_body,$signed_data);
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

        $ret = $this->ecc_client->decryptAndVerify($res,$data);
        if ($ret != 0){
            $response = $data;
            return $ret;
        }

        $response = $data;
        return $response["ErrCode"];
    }

    function sendTransferAssetProposal($transfer_body,$sign_body,&$response){
        if (empty($transfer_body)){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        if (empty($sign_body)){
            $response = errorResponse(errCode["InvalidParamsErrCode"]);
            return errCode["InvalidParamsErrCode"];
        }

        // 签名
        $ret = $this->sign_client->sign($transfer_body,$sign_body,$signed_data);
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

    // 析构函数
    function __destruct(){
        curl_close($this->curl_post);
        curl_close($this->curl_get);
    }

}
