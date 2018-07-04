<?php

require_once (__DIR__ . "/src/arxan/SafeBoxClient.php");
require_once (__DIR__ . "/vendor/autoload.php");
use arxan\SafeBoxClient;
use arxan\Encrypt;

$host = "http://103.67.193.150:15007";
$api_key = "eZUDImzTp1528874024";
$cert_path = "/home/carl/workspace/src/github.com/arxanchain/php-common/cert/client_certs";
$did = "did:axn:c316b8d9-2d1a-42b8-b2f2-950eecd90042";


$did = "did:axn:2ffda0e3-ec97-4c24-bb9f-3ba801ca94e5";
$s_code = "我爱你中国";

$safebox = new SafeBoxClient($host,$api_key,$cert_path);
/*
// 保存秘钥
$ret = $safebox->trusteeKeyPair($wallet1["Payload"]["id"], $wallet1["Payload"]["key_pair"],$code);
if($ret != 0){
    echo "save key pair error\n";
    var_dump($code);
    echo "\n";
}
echo "code: ",$code["Payload"]["code"],"\n";
 */


// 获取私钥
$ret = $safebox->queryPrivateKey($did, $s_code,$pri);
if($ret != 0){
    echo "get pri key  error\n";
}

echo "pri key:",$pri["Payload"]["private_key"],"\n";

// 获取公钥
$ret = $safebox->queryPublicKey($did, $s_code,$pub);
if($ret != 0){
    echo "get pub key error\n";
}

echo "pub key: ",$pub["Payload"]["public_key"],"\n";

// 获取公钥 错误实例
$ret = $safebox->queryPublicKey($did, "code",$pub);
if($ret != 0){
    echo "get pub key error\n";
    var_dump($pub);
    echo "\n";
}

echo "pub key: ",$pub["Payload"]["public_key"],"\n";

// 获取安全码
$ret = $safebox->recoverAssistCode($did, $code);
if($ret != 0){
    echo "get code error\n";
}

echo "get code succ: ",$code["Payload"]["code"],"\n";

/*
// 修改安全吗
$new = "我爱你中国";
$ret = $safebox->updateAssistCode($did, $s_code,$new,$new_code);
if($ret != 0){
    echo "update code error\n";
    var_dump($new_code);
    echo "\n";
}
echo "update code succ\n";
 */

