<?php

// 定义一些错误码

const errCode = array(
    "InvalidParamsErrCode" => 1000, //参数无效
    "MissingParamsErrCode" => 1001, //缺少参数
    "ParseRequestParamsError" => 1003,  //解析请求体失败
    "SerializeDataFail" => 1004,    //序列化数据失败
    "DeserializeDataFail" => 1005,  //反序列化(解析)数据失败
    "PermissionDenied" => 1009, //没有权限
    "ED25519SignFail" => 1010,  //ED25519签名失败
    "ED25519VerifyFail" => 1011,    //ED25519验签失败
    "InternalServerFailure" => 1012,    //服务内部错误
    "InvalidRequestBody" => 3000,   //无效的请求数据
    "RepeatRegistration" => 4000,   //重复注册
    "CertificateUnavailable" => 4007,   //证书不可用
    "BalancesNotSufficient" => 5015,    //余额不足
    "InvalidPrivateKey" => 6005,    //无效的私钥
    "InvalidSecurityCode" => 6006,  //无效的安全码
    "SDKDecryptAndVerifyFailed" => 10001,   //SDK解密验签失败
);

?>
