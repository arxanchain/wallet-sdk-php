<?php

require (__DIR__ . "/api/wallet.php");

$host = "http://103.67.193.150:15007";
$api_key = "eZUDImzTp1528874024";
$cert_path = "/home/carl/workspace/src/github.com/arxanchain/php-common/cryption/cert/client_certs";
$did = "did:axn:c316b8d9-2d1a-42b8-b2f2-950eecd90042";


$client = new WalletClient($host,$api_key,$cert_path,$did);

//$client->set_header("Bc-Invoke-Mode","sync");
//$client->set_header("Callback-Url","http://121.69.8.22:8066");

$register_body1 = array(
    "type"=> "Organization",
    "access"=> "culturetest3",
    "phone"=> "18337177372",
    "email"=> "Tom@163.com",
    "secret"=> "SONGsong110",
);

$register_body2 = array(
    "type"=> "Organization",
    "access"=> "culturetest4",
    "phone"=> "18337177372",
    "email"=> "Tom@163.com",
    "secret"=> "SONGsong110",
);

$client->register($register_body1,$register_res1);
echo "register wallet1 info:\n";
var_dump($register_res1);
echo "\n";

$client->register($register_body2,$register_res2);
echo "register wallet1 info:\n";
var_dump($register_res2);
echo "\n";

//echo "res:\n",$res,"\n";



$poe1 = array(
    "name"=> "宋松测试1",
    "owner"=> $register_res1["Payload"]["id"],
);

$sign_poe1= array(
    "did"=> $register_res1["Payload"]["id"],
    "nonce"=> "nonce",
    "key"=> $register_res1["Payload"]["key_pair"]["private_key"],
);

// 创建资产
$ret = $client->createPOE($poe1,$sign_poe1,$poe_res1);
if ($ret !=0){
    "create poe error\n";
    return ;
}
echo "create poe succ :\n";
var_dump($poe_res1);
echo "\n";

// 发行token
// ...
$token= array(
    "issuer"=>$client->did,
    "owner"=>$register_res1["Payload"]["id"],
    "asset_id"=> $poe_res1["Payload"]["id"],
    "amount"=> 1000,
);

$sign_token= array(
    "did"=> $register_res1["Payload"]["id"],
    "nonce"=> "nonce",
    "key"=> $register_res1["Payload"]["key_pair"]["private_key"],
);

$ret = $client->issuerCToken($token,$sign_token,$token_res);
if ($ret !=0){
    "issuer token error\n";
    return ;
}
echo "issuerCToken succ :\n";
var_dump($token_res);
echo "\n";



// 创建资产
$poe2 = array(
    "name"=> "宋松测试2",
    "owner"=> $register_res1["Payload"]["id"],
);

$sign_poe2= array(
    "did"=> $register_res1["Payload"]["id"],
    "nonce"=> "nonce",
    "key"=> $register_res1["Payload"]["key_pair"]["private_key"],
);

$ret = $client->createPOE($poe1,$sign_poe1,$poe_res2);
if ($ret !=0){
    "create poe error\n";
    return;
}
echo "create poe succ :\n";
var_dump($poe_res2);
echo "\n";

// 发行资产
$asset= array(
    "issuer"=>"did:axn:c316b8d9-2d1a-42b8-b2f2-950eecd90042",
    "owner"=>$register_res1["Payload"]["id"],
    "asset_id"=> $poe_res2["Payload"]["id"],
);

$sign_asset= array(
    "did"=> $register_res1["Payload"]["id"],
    "nonce"=> "nonce",
    "key"=> $register_res1["Payload"]["key_pair"]["private_key"],
);

$client->issuerAsset($asset,$sign_asset,$asset_res);
echo "issuerAsset succ:\n";
var_dump($asset_res);
echo "\n";


// 转让资产

$transfer_asset = array(
    "from"=> $register_res1["Payload"]["id"],
    "to"=> $register_res2["Payload"]["id"],
    "assets"=>array(
        $asset_res["Payload"]["token_id"],   
    ), 
);


$sign_asset = array(
    "did"=> $register_res1["Payload"]["id"],
    "nonce"=> "nonce",
    "key"=> $register_res1["Payload"]["key_pair"]["private_key"],
); 

$ret = $client->transferAsset($transfer_asset,$sign_asset,$transf_asset_res);
if($ret!=0){
    echo "transfer asset error\n";
}
echo("transfer asset succ:\n");
var_dump($transf_asset_res);
echo "\n";


$transfer_token = array(
    "from"=> $register_res1["Payload"]["id"],
    "to"=> $register_res2["Payload"]["id"],
    "tokens"=>array(
        array(
            "token_id"=>$token_res["Payload"]["token_id"],
            "amount"=> 10, 
        ),
    ), 
);

$sign_asset= array(
    "did"=> $register_res1["Payload"]["id"],
    "nonce"=> "nonce",
    "key"=> $register_res1["Payload"]["key_pair"]["private_key"],
); 

$ret = $client->transferCToken($transfer_token,$sign_asset,$transf_token_res);
if ($ret!=0){
    echo "transfer ctoken error\n";
    return;
}
echo "transfer ctoken succ:\n";
var_dump($transf_token_res);
echo "\n";

$client->getWalletInfo($register_res1["Payload"]["id"],$wallet1);
echo "wallet1 info:\n";
var_dump($wallet1);
echo "\n";

$client->getWalletBalance($register_res2["Payload"]["id"],$wallet2);
echo "wallet1 balance:\n";
var_dump($wallet2);
echo "\n";

/*
$file = "./1.php";
$mode = true;

$ret = $client->uploadPOEFile($asset,$file,$mode,$res1);
if ($ret!=0){
    echo "upload poe file error \n";
}

echo "uploadPOEFile succ\n";
var_dump($res1);
 */

//var_dump($res);

