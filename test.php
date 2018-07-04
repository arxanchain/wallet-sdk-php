<?php

require_once (__DIR__ . "/src/arxan/WalletClient.php");
require_once (__DIR__ . "/vendor/autoload.php");

use arxan\WalletClient;
use arxan\structs\{SignParam,RegisterWalletBody,POEBody,IssueCTokenBody,IssueAssetBody,TransferAssetBody,TransferCTokenBody,TokenAmount};

$host = "http://103.67.193.150:15007";
$api_key = "eZUDImzTp1528874024";
$cert_path = "/home/carl/workspace/src/github.com/arxanchain/php-common/cert/client_certs";
$did = "did:axn:c316b8d9-2d1a-42b8-b2f2-950eecd90042";
$private = "9aCdmQMO2+m0hHz9E0L87TSg6yn27gJhUdST4S7zfMgKG7cfPwfREJJfCHAEIa53bV82WJvJ5xvCvtkD/MbPLA==";


//$client = new WalletClient($host,$api_key,$cert_path,new SignParam($did,"nonce",""));
$client = new WalletClient($host,$api_key,$cert_path,new SignParam($did,"nonce",$private));

//$client->setHeader("Bc-Invoke-Mode","sync");
//$client->setHeader("Callback-Url","http://121.69.8.22:8066");

$register_body1 = new RegisterWalletBody("Organization","culture283","SONGsong110");
$register_body2 = new RegisterWalletBody("Organization","culture284","SONGsong110");

$client->register($register_body1,$register_res1);
echo "register wallet1 info:\n";
var_dump($register_res1);
echo "\n";

$client->register($register_body2,$register_res2);
echo "register wallet1 info:\n";
var_dump($register_res2);
echo "\n";

$scode1 = $register_res1["Payload"]["security_code"]; 
$scode2 = $register_res2["Payload"]["security_code"]; 

//echo "res:\n",$res,"\n";


$poe1 = new POEBody("宋松测试1",$register_res1["Payload"]["id"]); 

$sign1 = new SignParam($register_res1["Payload"]["id"],"nonce","");
$sign2 = new SignParam($register_res2["Payload"]["id"],"nonce","");

//echo "sign1:\n";
//var_dump($sign1);
//echo "\n";

// 创建资产
$ret = $client->createPOE($poe1,$sign1,$scode1,$poe_res1);
if ($ret !=0){
    "create poe error\n";
    return ;
}
echo "create poe succ :\n";
var_dump($poe_res1);
echo "\n";

// 发行token
// ...
$token = new IssueCTokenBody($client->did,$register_res1["Payload"]["id"],$poe_res1["Payload"]["id"],1000);

$ret = $client->issueCToken($token,$sign1,$scode1,$token_res);
if ($ret !=0){
    "issuer token error\n";
    return ;
}
echo "issuerCToken succ :\n";
var_dump($token_res);
echo "\n";



// 创建资产
$poe2 = new POEBody("宋松测试2",$register_res1["Payload"]["id"]);

$ret = $client->createPOE($poe2,$sign1,$scode1,$poe_res2);
if ($ret !=0){
    "create poe error\n";
    return;
}
echo "create poe succ :\n";
var_dump($poe_res2);
echo "\n";

// 发行资产
$asset= new IssueAssetBody("did:axn:c316b8d9-2d1a-42b8-b2f2-950eecd90042",$register_res1["Payload"]["id"], $poe_res2["Payload"]["id"]);

$client->issueAsset($asset,$sign1,$scode1,$asset_res);
echo "issuerAsset succ:\n";
var_dump($asset_res);
echo "\n";

$transfer_token = new TransferCTokenBody($register_res1["Payload"]["id"],$register_res2["Payload"]["id"],array(
            new TokenAmount($token_res["Payload"]["token_id"],10)
        )
);

$ret = $client->transferCToken($transfer_token,$sign1,$scode1,$transf_token_res);
if ($ret!=0){
    echo "transfer ctoken error\n";
    var_dump($transf_token_res);
    echo "\n";
    return;
}
echo "transfer ctoken succ:\n";
var_dump($transf_token_res);
echo "\n";



// 转让资产
$transfer_asset = new TransferAssetBody($register_res1["Payload"]["id"],$register_res2["Payload"]["id"],array(
        $poe_res2["Payload"]["id"]
    )
);

$ret = $client->transferAsset($transfer_asset,$sign1,$scode1,$transf_asset_res);
if($ret!=0){
    echo "transfer asset error\n";
}
echo("transfer asset succ:\n");
var_dump($transf_asset_res);
echo "\n";

$client->getWalletInfo($register_res1["Payload"]["id"],$wallet1);
echo "wallet1 info:\n";
var_dump($wallet1);
echo "\n";

$client->getWalletBalance($register_res2["Payload"]["id"],$wallet2);
echo "wallet1 balance:\n";
var_dump($wallet2);
echo "\n";
