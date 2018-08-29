<?php

namespace arxan;

function errorResponse($err_code){
    $response = array();
    $response["ErrCode"] = $err_code;
    $response["ErrMessage"] = array_search($err_code, errCode);
    $response["Payload"] = NULL;
    return $response;
}
