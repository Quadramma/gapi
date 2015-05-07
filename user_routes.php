<?php

Flight::route('GET /user', function(){
    echo '<br>'.json_encode(array("path"=>"getall","arguments"=>"none","method"=>"get"));
    echo '<br>'.json_encode(array("path"=>"get","arguments"=>"\$id","method"=>"get"));
    echo '<br>'.json_encode(array("path"=>"save","arguments"=>"\$data","method"=>"post"));
    echo '<br>'.json_encode(array("path"=>"clean","arguments"=>"none","method"=>"get","description"=>"Clears database"));
});

Flight::route('GET /user/getall', function(){
    Flight::res(Flight::crud_getAll("users"));
});

Flight::route('GET /user/get/@id', function($id){
    Flight::json(Flight::crud_get("users",$id));
});

Flight::route('POST /user/exist', function(){
    Flight::trycatch(function(){
        //Flight::exitOnInvalidSession();
        $users = Flight::crud_getAll("users");
        $rta = false;
        $post = json_decode(file_get_contents('php://input'),TRUE);
        $name = $post['name'];
        foreach($users as $k => $v){
            if(isset($v['name']) && $v['name'] == $name){
                $rta = true;
            }
        }
        $res = Flight::getDefaultRes();
        $res['rta'] = $rta;
        Flight::res($res);
    });
});

Flight::route('POST /user/save', function(){
    Flight::trycatch(function(){
        $post = json_decode(file_get_contents('php://input'),TRUE);
        $item = Flight::crud_save("users",$post);
        $res = Flight::getDefaultRes();
        $res['message'] = 'save success';
        $res['rta'] = $item;
        Flight::res($res);
    });
});

Flight::route('GET /user/remove/@id', function($id){
    Flight::crud_remove("users",$id);
});

Flight::route('GET /user/savetest', function(){
    $str = '{"name":"Max","email":"max@site.com","password":33}';
    $data = json_decode($str,true);
    Flight::crud_save("users",$data);
});

Flight::route('GET /user/clean', function(){
	Flight::trycatch(function(){
        Flight::crud_remove_all('users');
        Flight::res(Flight::getDefaultRes());
    });
});

	

?>