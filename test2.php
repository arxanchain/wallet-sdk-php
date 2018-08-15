<?php

require_once (__DIR__ . "/src/arxan/WalletClient.php");
require_once (__DIR__ . "/vendor/autoload.php");

use arxan\WalletClient;
use arxan\structs\{SignParam,RegisterWalletBody,POEBody,IssueCTokenBody,IssueAssetBody,TransferAssetBody,TransferCTokenBody,TokenAmount};

$host = "http://139.198.124.163:49143";
$api_key = "Qs5osyxHp1534234207";
$cert_path = __DIR__ . "/cert/client_certs";
$did = "did:axn:358085b8-782d-406c-8f65-15d77bbe4772";
$private = "o2zMi96J1teDwXBerFstAyz5dQLKZFNFdbrvVEbUgnujP5zrKcfIM73Y1jkMZHoexc1y7VirYffRJv9sZyfGhw==";


$client = new WalletClient($host,$api_key,$cert_path,new SignParam($did,"nonce",$private));

$client->queryBlockInfo(1,$data);

var_dump($data);

$txn_id = "a4477bbf6be6c2f3bc9ca41401c8516f2c1785fcdb9721479dc7df8d11154788";
$client->getTxnDetail($txn_id,$data);
var_dump($data);
