<?php


Flight::map("setCrossDomainHeaders",function(){
  header("Access-Control-Allow-Headers: *,auth-token");
  header("Access-Control-Allow-Origin:http://localhost:3000");
  header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
});
Flight::route("OPTIONS *",function(){
  header("Access-Control-Allow-Headers: *,auth-token,Content-Type");
  header("Access-Control-Allow-Origin:http://localhost:3000");
  header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
});

Flight::route("OPTIONS /*/*",function(){
  header("Access-Control-Allow-Headers:*,auth-token,Content-Type");
  header("Access-Control-Allow-Origin:http://localhost:3000");
  header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
});

Flight::map('getDefaultRes',function(){
  return array(
      'ok'=>true,
      'message'=>'Everything work just fine.',
      'errorcode'=>0
    );
});

Flight::map('trycatch',function($fn,$params = array()){
    $res = Flight::getDefaultRes();
    try{
        $fn($params);
    }catch(Exception $ex){
        $res['ok'] = false;
        $res['message'] = $ex->getMessage();
        $res['errorcode'] = QJERRORCODES::$API_UNKNOWN_EXCEPTION;
    }
    Flight::res($res);
});

Flight::map("res",function($rta){
	Flight::setCrossDomainHeaders();  
  /*
  //Incluir en todas los routes seguros $token['valid'] representa un user logeado.
  //Llamar a auth/login con user,pass para logear contra tabla user.
  $rta = Flight::proccessToken();  //res token
  $token= $rta['token'];
  $res = $rta['response'];
  */
  Flight::json($rta);      
});



?>