<?php


Flight::map('generateToken',function($user,$tokenReq,$tokenExp){
    //$serverReqTime = microtime();
    $mt = explode(' ', microtime());
    $serverReqTime = $mt[1] * 1000 + round($mt[0] * 1000);
    $serverReqTimeOffset =  $serverReqTime - $tokenReq;
     return base64_encode(json_encode(array(
        "id" => $user["_id"]
        ,"tokenExp" => $tokenExp
        ,'timeOffset'=> $serverReqTimeOffset
    ))); 
});

Flight::map('proccessToken',function(){
    $token = Flight::decodeToken();
    $message = 'Everything works just fine';
    $res = array(
        'ok' => true,
        'message'=>$message,
        'errorcode'=>0
    );
    //Validations
    if(!$token->valid){
        $res['ok'] =false;
        $res['message'] = 'Invalid token (token not found in request)';
        $res['errorcode'] = QJERRORCODES::$API_INVALID_TOKEN;        
    }else{
        if(Flight::isTokenExpired($token)){
            $res['ok'] =false;
            $seconds = Flight::tokenExpiredSeconds($token);
            $res['message'] = 'Token expired ($seconds ago)';
            $res['errorcode'] = QJERRORCODES::$API_TOKEN_EXPIRED;
            $token->valid = false;
        }else{
            $res['sessionInfo'] = Flight::tokenExpiredSeconds($token) . " seconds remain";
            $res['errorcode'] = 0;
        }    
    }    
    return array(
        'res'=>$res,
        'token'=>$token
    );
});

Flight::map("decodeToken",function(){
    $headers = getallheaders();
    $hasToken = (isset($headers["auth-token"])  &&$headers["auth-token"] != "");
    $token = (isset($headers["auth-token"])?$headers["auth-token"]:"");
    if(!$hasToken){
        $hasToken = (isset($headers["Auth-Token"])  &&$headers["Auth-Token"] != "");
        $token = (isset($headers["Auth-Token"])?$headers["Auth-Token"]:"");
    }
    if($hasToken){
        $tokenDecoded = base64_decode($token);
        $tokenData = json_decode($tokenDecoded);
        $tokenData->valid = true;
        return $tokenData;
    }else{
        return (object)array('valid'=>false);
    }
});

//Aux
Flight::map('isTokenExpired',function($tokenData){
    //$serverReqTime = get_millis();
    $mt = explode(' ', microtime());
    $serverReqTime = $mt[1] * 1000 + round($mt[0] * 1000);
    $serverReqTimeFixed =  $serverReqTime + $tokenData->timeOffset;
    $diff = $tokenData->tokenExp - $serverReqTimeFixed;
    return ($diff < 0) ? true : false;
});
Flight::map('tokenExpiredSeconds',function($tokenData){
    //$serverReqTime = get_millis();
    $mt = explode(' ', microtime());
    $serverReqTime = $mt[1] * 1000 + round($mt[0] * 1000);
    $serverReqTimeFixed =  $serverReqTime + $tokenData->timeOffset;
    $diff = $tokenData->tokenExp - $serverReqTimeFixed;
    return abs($diff) / 1000;
});

?>