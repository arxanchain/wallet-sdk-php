# safebox-sdk-php
This SDK enables PHP developers to develop applications that interact with Arxanchain SafeBox.

# 使用说明

## 1.注册客户端
```code
$host :服务地址
$api_key :api-key 公司账户
$cert_path :证书目录
$safebox = new SafeBoxClient($host,$api_key,$cert_path);
```

## 2.托管秘钥
```code
$did :账户id
$key_pair = array(
    "private_key" => "private",
    "public_key" => "public",
) 账户公私钥对
$safebox = safebox->trusteeKeyPair($did,$key_pair,$response); 返回值0表示正常
$response 为请求返回的多维数组
{
    ["Method"]=> "",
    ["ErrCode"]=> 0, 
    ["ErrMessage"]=> "",
    ["Payload"]=> {
        ["code"]=> "我是中国人" // 秘钥安全码
    }
}
```

## 3.获取私钥
```code
$did :用户did 
$code :秘钥安全码
$safebox = safebox->queryPrivateKey($did,$code,$response); 返回值0表示正常
$response 为请求返回的多维数组
{
    ["Method"]=> "", 
    ["ErrCode"]=> 0,
    ["ErrMessage"]=> "", 
    ["Payload"]=> {
        ["private_key"]=> "private" // 秘钥安全码
    }   
}
```

## 4.获取安全码
```code
$did :账户id
$safebox = safebox->recoverAssistCode($did,$response); 返回值0表示正常
$response 为请求返回的多维数组
{
    ["Method"]=> "",
    ["ErrCode"]=> 0,
    ["ErrMessage"]=> "",
    ["Payload"]=> {
        ["code"]=> "我是中国人" // 秘钥安全码
    }
}
```

## 5.修改安全码
```code
$did :服务地址
$old :旧安全码
$new :新安全码
$safebox = new SafeBoxClient($did,$old,$new,$response); 返回值0表示正常
$response 为请求返回的多维数组
{
    ["Method"]=> "",
    ["ErrCode"]=> 0,
    ["ErrMessage"]=> "",
    ["Payload"]=> "",
}
```
