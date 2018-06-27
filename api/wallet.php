<?php

require_once (__DIR__ . "/../../php-common/cryption/crypto.php");
require_once (__DIR__ . "/../../php-common/cryption/sign.php");
require_once (__DIR__ . "/../../php-common/error/error.php");
require_once (__DIR__ . "/../../php-common/log/log.php");



interface WalletApi {
    // 注册钱包 
    /*  register_body josn 对象
     *  response 返回的json对象
     * 
     * 
     */
    function register($register_body,&$response);
    // 获取钱包的基本信息
    function getWalletInfo($did,&$response);
    // 获取钱包余额
    function getWalletBalance($did,&$response); 
    // 创建数字资产
    function createPOE($poe_body,$sign_body,&$response);
    // 上传数字资产凭证
    //function uploadPOEFile($asset_id,$file,$mode,&$response); api暂时没处理
    // 发行资产
    function issuerAsset($asset_body,$sign_body,&$response);
    // 发行token
    function issuerCToken($ctoken_body,$sign_body,&$response);
    // 转让资产
    function transferAsset($transfer_body,$sign_body,&$response);
    // 转移ctoken
    function transferCToken($transfer_body,$sign_body,&$response);
    // 交易历史
    function tranfserTxn($id,$mode,&$response);
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
        $this->sign_client = NULL;

        // 设置http请求头
        $header = array();
        $header[0] = 'API-Key:' . $api_key;
        $header[1] = 'Content-Type: application/json;charset=utf-8'; 
        $header[2] = 'Bc-Invoke-Mode:sync';
        curl_setopt($this->curl_post, CURLOPT_HTTPHEADER, $header);
        //设置获取的信息以文件流的形式返回，而不是直接输出
        curl_setopt($this->curl_post, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($this->curl_get, CURLOPT_HTTPHEADER, $header);
        //设置获取的信息以文件流的形式返回，而不是直接输出
        curl_setopt($this->curl_get, CURLOPT_RETURNTRANSFER, 1);     
    }

    function register($register_body,&$response){
        if (empty($register_body)){
            return errCode["InvalidParamsErrCode"];
        }

        $ret = $this->ecc_client->signAndEncrypt($register_body,$request);
        if ($ret != 0){
            return $ret;
        }

        curl_setopt($this->curl_post, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl_post, CURLOPT_POSTFIELDS, $request);
        $url = $this->host . "/wallet-ng/v1/wallet/register";
        curl_setopt ($this->curl_post, CURLOPT_URL, $url);

        $res = curl_exec($this->curl_post);
        if ($res == ""){
            //echo "curl error" ,"\n";
            // curl 失败认为都是参数错误
            return errCode["InvalidRequestBody"];
        }

        $ret = $this->ecc_client->DecryptAndVerify($res,$data);
        if ($ret != 0){
            return $ret;
        }

        $response = $data;
        return $response["ErrCode"];
    }

    function createPOE($poe_body,$sign_body,&$response) {
        if (empty($poe_body)){
            return errCode["InvalidParamsErrCode"];
        }

        if (empty($sign_body)){
            return errCode["InvalidParamsErrCode"];
        }

        // 创建ed25519对象
        $this->sign_client = new Signature($sign_body);
        if ($this->sign_client == NULL){
            return errCode["InvalidParamsErrCode"];
        }

        // 签名
        $ret = $this->sign_client->sign($poe_body,$signed_data);
        if ($ret!=0){
            return $ret;
        }

        // 销毁签名对象
        unset($this->sign_client); 
        $this->sign_client = NULL;

        // 签名返回的数据，交给ecc去加密签名
        $ret = $this->ecc_client->signAndEncrypt($signed_data,$request);
        if ($ret !=0){
            return $ret;
        }

        // 发送请求
        curl_setopt($this->curl_post, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl_post, CURLOPT_POSTFIELDS, $request);
        $url = $this->host . "/wallet-ng/v1/poe/create";
        curl_setopt ($this->curl_post, CURLOPT_URL, $url);

        $res = curl_exec($this->curl_post);
        if ($res == ""){
            return errCode["InvalidRequestBody"];
        }

        $ret = $this->ecc_client->decryptAndVerify($res,$data);
        if($ret !=0){
            return $ret;
        }

        $response = $data;
        return $response["ErrCode"];
    }

    // TODO 上传存证文件
    /*
    function uploadPOEFile($asset_id,$file,$mode,&$response){
        // 打开文件，读文件
        //$str = file_get_contents($file);
        // 组装form数据
        $data = array(
            "poe_id"=>$asset_id,
            "poe_file"=>"$file",
            "read_only"=>"$mode",
        );


        // 设置http请求
        // 这个请求与其他的请求数据不同，为了方便，在此重新设置一个新的客户端，并在使用完毕后，销毁
        // 生成boundary随机值
        //e3 95 d0 d3 88 03 a7 7b 4e 6c f4 48 18 e4 0a 9a a5 31 01 8c c5 10 24 eb 62 be 75 9a e8 01
        //$boundary = "";

        $upload_curl = curl_init();
        $header = array();
        $header[0] = 'API-Key:' . $this->api_key;
        $header[1] = 'Content-Type:multipart/form-data';
        $header[2] = 'Bc-Invoke-Mode:sync';

        $url = $this->host . "/wallet-ng/v1/poe/upload";
        curl_setopt($upload_curl, CURLOPT_URL, $url);

        curl_setopt($upload_curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($upload_curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($upload_curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($upload_curl, CURLOPT_POSTFIELDS, $data);

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

        curl_close($upload_curl);
        $response = $data;
        return $response["ErrCode"];
    }
    */

    // 发行资产
    function issuerAsset($asset_body,$sign_body,&$response){
        if (empty($asset_body)){
            return errCode["InvalidParamsErrCode"];
        }

        if (empty($sign_body)){
            return errCode["InvalidParamsErrCode"];
        }

        // 创建ed25519对象
        $this->sign_client = new Signature($sign_body);
        if ($this->sign_client == NULL){
            return errCode["InvalidParamsErrCode"];
        }

        // 签名
        $ret = $this->sign_client->sign($asset_body,$signed_data);
        if ($ret != 0){
            return $ret;
        }

        // 销毁签名对象
        unset($this->sign_client); 
        $this->sign_client = NULL;

        // 签名返回的数据，交给ecc去加密签名
        $ret = $this->ecc_client->signAndEncrypt($signed_data,$request);
        if ($ret != 0){
            return $ret;
        }

        // 发送请求
        curl_setopt($this->curl_post, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl_post, CURLOPT_POSTFIELDS, $request);
        $url = $this->host . "/wallet-ng/v1/transaction/assets/issue";
        curl_setopt ($this->curl_post, CURLOPT_URL, $url);

        $res = curl_exec($this->curl_post);
        if ($res == ""){
            return errCode["InvalidRequestBody"];
        }

        $ret = $this->ecc_client->decryptAndVerify($res,$data);
        if ($ret != 0){
            return $ret;
        }

        $response = $data;
        return $response["ErrCode"];
    }

    // 发行token
    function issuerCToken($ctoken_body,$sign_body,&$response){
        if (empty($ctoken_body)){
            return errCode["InvalidParamsErrCode"];
        }

        if (empty($sign_body)){
            return errCode["InvalidParamsErrCode"];
        }

        // 创建ed25519对象
        $this->sign_client = new Signature($sign_body);
        if ($this->sign_client == NULL){
            return errCode["InvalidParamsErrCode"];
        }

        // 签名
        $ret = $this->sign_client->sign($ctoken_body,$signed_data);
        if ($ret != 0){
            return $ret;
        }

        // 销毁签名对象
        unset($this->sign_client); 
        $this->sign_client = NULL;

        // 签名返回的数据，交给ecc去加密签名
        $ret = $this->ecc_client->signAndEncrypt($signed_data,$request);
        if ($ret != 0){
            return $ret;
        }

        // 发送请求
        curl_setopt($this->curl_post, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl_post, CURLOPT_POSTFIELDS, $request);
        $url = $this->host . "/wallet-ng/v1/transaction/tokens/issue";
        curl_setopt ($this->curl_post, CURLOPT_URL, $url);

        $res = curl_exec($this->curl_post);
        if ($res == ""){
            //echo "curl error" ,"\n";
            return errCode["InvalidRequestBody"];
        }

        $ret = $this->ecc_client->decryptAndVerify($res,$data);
        if ($ret != 0){
            return $ret;
        }

        $response = $data;
        return $response["ErrCode"];
    }

    // 转让资产
    function transferAsset($transfer_body,$sign_body,&$response){
        if (empty($transfer_body)){
            return errCode["InvalidParamsErrCode"];
        }

        if (empty($sign_body)){
            return errCode["InvalidParamsErrCode"];
        }

        // 创建ed25519对象
        $this->sign_client = new Signature($sign_body);
        if ($this->sign_client == NULL){
            return errCode["InvalidParamsErrCode"];
        }

        // 签名
        $ret = $this->sign_client->sign($transfer_body,$signed_data);
        if ($ret != 0){
            return $ret;
        }

        // 销毁签名对象
        unset($this->sign_client); 
        $this->sign_client = NULL;

        // 签名返回的数据，交给ecc去加密签名
        $ret = $this->ecc_client->signAndEncrypt($signed_data,$request);
        if ($ret != 0){
            return $ret;
        }

        // 发送请求
        curl_setopt($this->curl_post, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl_post, CURLOPT_POSTFIELDS, $request);
        $url = $this->host . "/wallet-ng/v1/transaction/assets/transfer";
        curl_setopt ($this->curl_post, CURLOPT_URL, $url);

        $res = curl_exec($this->curl_post);
        if ($res == ""){
            //echo "curl error" ,"\n";
            return errCode["InvalidRequestBody"];
        }

        $ret = $this->ecc_client->decryptAndVerify($res,$data);
        if ($ret != 0){
            return $ret;
        }

        $response = $data;
        return $response["ErrCode"];
    }

    // 转让token
    function transferCToken($transfer_body,$sign_body,&$response){
        if (empty($transfer_body)){
            return errCode["InvalidParamsErrCode"];
        }

        if (empty($sign_body)){
            return errCode["InvalidParamsErrCode"];
        }

        // 创建ed25519对象
        $this->sign_client = new Signature($sign_body);
        if ($this->sign_client == NULL){
            return errCode["InvalidParamsErrCode"];
        }

        // 签名
        $ret = $this->sign_client->sign($transfer_body,$signed_data);
        if ($ret != 0){
            return $ret;
        }

        // 销毁签名对象
        unset($this->sign_client); 
        $this->sign_client = NULL;

        // 签名返回的数据，交给ecc去加密签名
        $ret = $this->ecc_client->signAndEncrypt($signed_data,$request);
        if ($ret != 0){
            return $ret;
        }

        // 发送请求
        curl_setopt($this->curl_post, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl_post, CURLOPT_POSTFIELDS, $request);
        $url = $this->host . "/wallet-ng/v1/transaction/tokens/transfer";
        curl_setopt($this->curl_post, CURLOPT_URL, $url);

        $res = curl_exec($this->curl_post);
        if ($res == "") {
            return errCode["InvalidRequestBody"];
        }

        $ret = $this->ecc_client->decryptAndVerify($res,$data);
        if ($ret != 0) {
            return $ret;
        }

        $response = $data;
        return $response["ErrCode"];
    }

    function tranfserTxn($did,$type,&$response){
        if($did == ""){
            return errCode["InvalidParamsErrCode"];
        }

        //发送get请求
        $url = $this->host . "/wallet-ng/v1/transaction/logs?id=" . $did . "&type=[" . $type ."]";
        curl_setopt($this->curl_get, CURLOPT_URL, $url);

        $res = curl_exec($this->curl_get);
        if ($res == ""){
            return errCode["InvalidRequestBody"];
        }

        // 验签解密
        $ret = $this->ecc_client->decryptAndVerify($res,$data);
        if ($ret != 0){
            return $ret;
        }

        $response = $data;
        return $response["ErrCode"];
    }


    function getWalletInfo($did,&$response){
        if($did == ""){
            return errCode["InvalidParamsErrCode"];
        }

        //发送get请求
        $url = $this->host . "/wallet-ng/v1/wallet/info?id=" . $did;
        curl_setopt($this->curl_get, CURLOPT_URL, $url);

        $res = curl_exec($this->curl_get);
        if ($res == ""){
            //echo "curl error" ,"\n";
            return errCode["InvalidRequestBody"];
        }

        // 验签解密
        $ret = $this->ecc_client->decryptAndVerify($res,$data);
        if ($ret != 0) {
            return $ret;
        }

        $response = $data;
        return $response["ErrCode"];
    }

    function getWalletBalance($did,&$response){
        if($did == ""){
            return errCode["InvalidParamsErrCode"];
        }

        //发送get请求
        $url = $this->host . "/wallet-ng/v1/wallet/balance?id=" . $did;
        curl_setopt($this->curl_get, CURLOPT_URL, $url);
        $res = curl_exec($this->curl_get);
        if ($res == ""){
            //echo "curl error" ,"\n";
            return errCode["InvalidRequestBody"];
        }

        // 验签解密
        $ret = $this->ecc_client->decryptAndVerify($res,$data);
        if ($ret != 0) {
            return $ret;
        }

        $response = $data;
        return $response["ErrCode"];
    }

    function sendIssueCTokenProposal($ctoken_body,$sign_body,&$response){
        if (empty($ctoken_body)){
            return errCode["InvalidParamsErrCode"];
        }

        if (empty($sign_body)){
            return errCode["InvalidParamsErrCode"];
        }

        // 创建ed25519对象
        $this->sign_client = new Signature($sign_body);
        if($this->sign_client == NULL){
            return errCode["InvalidParamsErrCode"];
        }

        // 签名
        $ret = $this->sign_client->sign($ctoken_body,$signed_data);
        if ($ret != 0){
            return $ret;
        }

        // 销毁签名对象
        unset($this->sign_client); 
        $this->sign_client = NULL;

        // 签名返回的数据，交给ecc去加密签名
        $ret = $this->ecc_client->signAndEncrypt($signed_data,$request);
        if ($ret != 0){
            return $ret;
        }

        // 发送请求
        curl_setopt($this->curl_post, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl_post, CURLOPT_POSTFIELDS, $request);
        $url = $this->host . "/wallet-ng/v1/transaction/tokens/issue/prepare";
        curl_setopt ($this->curl_post, CURLOPT_URL, $url);

        $res = curl_exec($this->curl_post);
        if ($res == ""){
            //echo "curl error" ,"\n";
            return errCode["InvalidRequestBody"];
        }

        $ret = $this->ecc_client->decryptAndVerify($res,$data);
        if ($ret != 0){
            return $ret;
        }

        $response = $data;
        return $response["ErrCode"];
    }

    function sendIssueAssetProposal($asset_body,$sign_body,&$response){
        if (empty($ctoken_body)){
            return errCode["InvalidParamsErrCode"];
        }

        if (empty($sign_body)){
            return errCode["InvalidParamsErrCode"];
        }

        // 创建ed25519对象
        $this->sign_client = new Signature($sign_body);
        if($this->sign_client == NULL){
            return errCode["InvalidParamsErrCode"];
        }

        // 签名
        $ret = $this->sign_client->sign($ctoken_body,$signed_data);
        if ($ret != 0){
            return $ret;
        }

        // 销毁签名对象
        unset($this->sign_client); 
        $this->sign_client = NULL;

        // 签名返回的数据，交给ecc去加密签名
        $ret = $this->ecc_client->signAndEncrypt($signed_data,$request);
        if ($ret != 0){
            return $ret;
        }

        // 发送请求
        curl_setopt($this->curl_post, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl_post, CURLOPT_POSTFIELDS, $request);
        $url = $this->host . "/wallet-ng/v1/transaction/tokens/issue/prepare";
        curl_setopt ($this->curl_post, CURLOPT_URL, $url);

        $res = curl_exec($this->curl_post);
        if ($res == ""){
            //echo "curl error" ,"\n";
            return errCode["InvalidRequestBody"];
        }

        $ret = $this->ecc_client->decryptAndVerify($res,$data);
        if ($ret != 0){
            return $ret;
        }

        $response = $data;
        return $response["ErrCode"];
    }

    function sendTransferCTokenProposal($asset_body,$sign_body,&$response){
        if (empty($ctoken_body)){
            return errCode["InvalidParamsErrCode"];
        }

        if (empty($sign_body)){
            return errCode["InvalidParamsErrCode"];
        }

        // 创建ed25519对象
        $this->sign_client = new Signature($sign_body);
        if($this->sign_client == NULL){
            return errCode["InvalidParamsErrCode"];
        }

        // 签名
        $ret = $this->sign_client->sign($ctoken_body,$signed_data);
        if ($ret != 0){
            return $ret;
        }

        // 销毁签名对象
        unset($this->sign_client); 
        $this->sign_client = NULL;

        // 签名返回的数据，交给ecc去加密签名
        $ret = $this->ecc_client->signAndEncrypt($signed_data,$request);
        if ($ret != 0){
            return $ret;
        }

        // 发送请求
        curl_setopt($this->curl_post, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl_post, CURLOPT_POSTFIELDS, $request);
        $url = $this->host . "/wallet-ng/v1/transaction/tokens/issue/prepare";
        curl_setopt ($this->curl_post, CURLOPT_URL, $url);

        $res = curl_exec($this->curl_post);
        if ($res == ""){
            //echo "curl error" ,"\n";
            return errCode["InvalidRequestBody"];
        }

        $ret = $this->ecc_client->decryptAndVerify($res,$data);
        if ($ret != 0){
            return $ret;
        }

        $response = $data;
        return $response["ErrCode"];
    }

    function sendTransferAssetProposal($asset_body,$sign_body,&$response){
        if (empty($ctoken_body)){
            return errCode["InvalidParamsErrCode"];
        }

        if (empty($sign_body)){
            return errCode["InvalidParamsErrCode"];
        }

        // 创建ed25519对象
        $this->sign_client = new Signature($sign_body);
        if($this->sign_client == NULL){
            return errCode["InvalidParamsErrCode"];
        }

        // 签名
        $ret = $this->sign_client->sign($ctoken_body,$signed_data);
        if ($ret != 0){
            return $ret;
        }

        // 销毁签名对象
        unset($this->sign_client); 
        $this->sign_client = NULL;

        // 签名返回的数据，交给ecc去加密签名
        $ret = $this->ecc_client->signAndEncrypt($signed_data,$request);
        if ($ret != 0){
            return $ret;
        }

        // 发送请求
        curl_setopt($this->curl_post, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl_post, CURLOPT_POSTFIELDS, $request);
        $url = $this->host . "/wallet-ng/v1/transaction/tokens/issue/prepare";
        curl_setopt ($this->curl_post, CURLOPT_URL, $url);

        $res = curl_exec($this->curl_post);
        if ($res == ""){
            //echo "curl error" ,"\n";
            return errCode["InvalidRequestBody"];
        }

        $ret = $this->ecc_client->decryptAndVerify($res,$data);
        if ($ret != 0){
            return $ret;
        }

        $response = $data;
        return $response["ErrCode"];
    }

    //TODO 待修改
    function processTx($txs_array,&$response){
        if (empty($txs_array)){
            return errCode["InvalidParamsErrCode"];
        }

        $ret = $this->ecc_client->signAndEncrypt($txs_array,$request);
        if ($ret != 0){
            return $ret;
        }
        
        curl_setopt($this->curl_post, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl_post, CURLOPT_POSTFIELDS, $request);
        $url = $this->host . "/wallet-ng/v1/transaction/process";
        curl_setopt ($this->curl_post, CURLOPT_URL, $url);

        $res = curl_exec($this->curl_post);
        if ($res == ""){
            //echo "curl error" ,"\n";
            // curl 失败认为都是参数错误
            return errCode["InvalidRequestBody"];
        }

        $ret = $this->ecc_client->DecryptAndVerify($res,$data);
        if ($ret != 0){
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
