<?php

$ch = curl_init(); 
$url = "http://103.67.193.150:15007/wallet-ng/v1/wallet/register";
curl_setopt ($ch, CURLOPT_URL, $url); 

// 设置http头
$header = array();
$header[] = 'API-Key:eZUDImzTp1528874024';
//$header[] = 'Content-Encoding:*';
$header[] = 'Content-Type: application/json;charset=utf-8'; 
$header[] = 'Bc-Invoke-Mode:sync'; 

//$header[] = 'Accept-Encoding:*';

// 设置http body
$body = array(
   // "id"=> "",
    "type"=> "Organization",
    "access"=> "songtest5",
    "phone"=> "18337177372",
    "email"=> "Tom@163.com",
    "secret"=> "SONGsong110",
);

// 对body进行json编码
$json_str = json_encode($body);
echo "json_str :","\n",$json_str,"\n";


$base64_str = base64_encode($json_str);
echo "base64data :","\n",$base64_str,"\n";

$path = "/etc/arxanchin/key/client_certs";
$api_key = "eZUDImzTp1528874024";
$mode1 = 1;

$cmd1 = "crypto-util" . " -apikey " .  $api_key . " -data " . $base64_str. " -path " . $path . " -mode " . $mode1  ;

//echo $cmd , "\n";
//
exec("$cmd1",$signed_data);
var_dump($signed_data);

echo "signed_data: ","\n",$signed_data[0],"\n";



////var_dump($res);
//



//curl_setopt($ch, CURLOPT_HEADER, $header); 
//curl_setopt($ch, CURLOPT_HEADER, 0); 
curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
curl_setopt($ch, CURLOPT_POSTFIELDS, $signed_data[0]); 
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST'); 
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

$res_data= curl_exec($ch); 

echo "res :","\n",$res_data,"\n"; 

$mode2 = 2;
$cmd2 = "crypto-util" . " -apikey " .  $api_key . " -data " . $res_data . " -path " . $path . " -mode " . $mode2  ;

exec("$cmd2",$res2);

echo "res2:","\n";
echo $res2[0], "\n";

$obj = json_decode($res2[0]);
//var_dump($obj);

if ($obj->Payload != ""){
    $obj = json_decode($obj->Payload);
    var_dump($obj);
}

//echo $obj["Payload"];


//var_dump($res2);



//echo "res:","\n",$res1[0],"\n";

//list($res_header, $res_body) = explode("\r\n\r\n", $file_contents, 2);

//echo $res_body ,"\n";

