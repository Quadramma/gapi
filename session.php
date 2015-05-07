<?php

Flight::route("GET /session/isavaliable", function(){
    Flight::trycatch(function(){
        $rta = Flight::proccessToken();  //res token
        $token= $rta['token'];
        $res = $rta['res'];
        $post = json_decode(file_get_contents('php://input'),TRUE);
        //
        if(!$token->valid){
            $res['rta']=false;
        }else{
            $res['rta']=true;
        }
        $res['ok'] = true;
        $res['errorcode'] = 0;
        $res['message']= '';
        //
        $res['post'] = $post;
        Flight::res($res);
    });
});
Flight::route("POST /session/login", function(){
    Flight::trycatch(function(){
        $rta = Flight::proccessToken();  //res token
        $token= $rta['token'];
        $res = $rta['res'];
//Flight::res(array('llegamo'=>$token->valid));        
        $post = json_decode(file_get_contents('php://input'),TRUE);
        if(!$token->valid){            
            $res['ok'] = 1;
            $res['errorcode'] = 0;
            
            $name = $post['name'];
            $pass = $post['pass'];
            $res['rta']['session'] = null;
            $res['rta']['user'] = null;
            $users = Flight::crud_getAll("users");
            foreach($users as $k=>$v){
                if(isset($v['name']) && $v['name'] == $name){
                    if(isset($v['pass']) && $v['pass'] == $pass){
                        $res['rta']['user'] = $v;    
                    }
                }
            }
            if($res['rta']['user'] !== null){
                $res['message'] = 'User found, session generated.';
                $res['rta']['session'] = Flight::generateSession($res['rta']['user'],$post['tokenReq']);
            }else{
                $res['message'] = 'Invalid credentials!';
            }
        }else{
            //Actualizo el token.
//Flight::res(array($rta['token']));
            $res['rta']['user'] = Flight::crud_get('users',$rta['token']->id);
            $res['rta']['session'] = Flight::generateSession($res['rta']['user'],$post['tokenReq']);
        }
        //
        //$res['post'] = $post;
        Flight::res($res);
    });
});


Flight::map('exitOnInvalidSession',function(){
    //Flight::res(array('tamos bien'=>1));
    $rta = Flight::proccessToken();  //res token
    if(!$rta['token']->valid){
        Flight::res($rta['res']);
    }else{
        if($rta['res']['errorcode'] == QJERRORCODES::$API_TOKEN_EXPIRED){
            Flight::res($rta['res']);      
        }
    }
});
Flight::map('generateSession',function($user,$tokenReq){

    $seconds = 60 * 60;  //SESSION EXPIRATION TIME
    $tokenExp = $tokenReq + (1000 * $seconds);
    //
    return array(
            'token' => Flight::generateToken($user,$tokenReq,$tokenExp),
            'tokenReq'=>$tokenReq,
            'tokenExp'=>$tokenExp,
        );
});
?>