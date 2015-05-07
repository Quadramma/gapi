<?php
use Flintstone\Flintstone;
use Flintstone\Formatter\JsonFormatter;

Flight::route('/crud/getall(/@collectionName)', function($collectionName = null){
    Flight::trycatch(function($params){
        $collectionName = $params['collectionName'];
        $post = json_decode(file_get_contents('php://input'),FALSE);
        $res = Flight::getDefaultRes();
        $res['rta'] = Flight::crud_getAll(isset($post)?$post->collectionName:$collectionName);
        Flight::res($res);
    },array('collectionName'=>$collectionName));
});
Flight::route('POST /crud/get', function(){
    Flight::trycatch(function(){
        $post = json_decode(file_get_contents('php://input'),TRUE);
        $res = Flight::getDefaultRes();
        $collectionName = $post['collectionName'];
        unset($post['collectionName']);
        $res['rta'] = null;
        if(isset($post['id'])){
            $res['rta'] = Flight::crud_get($collectionName,$post['_id']);
        }else{
            if(($matched = Flight::validateAgainstCollection('category',$post)) != null){
                $rta['rta'] = $matched;
            }
        }
        Flight::res($res);
    });
});



Flight::route('/crud/exist(/@collectionName(/@field(/@val)))'
    , function($collectionName = null,$field=null,$val=null){
    Flight::trycatch(function($param){
        $collectionName = $param['collectionName'];
        $field = $param['field'];
        $val = $param['val'];
        //
        $post = json_decode(file_get_contents('php://input'),TRUE);
        $rta = false;
        if((isset($post)?1:0)==1 || ((isset($collectionName)?1:0)==1
            &&(isset($field)?1:0)==1
            &&(isset($val)?1:0)==1)){
            $post = array(
                'collectionName'=>$collectionName,
                'field'=>$field,
                $field=>$val
                );
        }
        if((isset($post)?1:0)==1)
            {
                $items = array();
                try{
                $items = Flight::crud_getAll($post['collectionName']);
                }catch(Exception $e){
                    //Flight::res(array('error'=>));
                    //echo $e->getLine(); //Caso 135 == cuando no existe la db
                }
                $f = $post['field'];
                $val = $post[$f];
                //
                foreach($items as $k => $v){
                    
                    if(isset($v[$f]) && $v[$f] == $val){
                        $rta = true;
                    }
                }
        }
        $res = Flight::getDefaultRes();
        $res['rta'] = $rta;
        Flight::res($res);
    },array(
            'collectionName'=>$collectionName,
            'field'=>$field,
            'val'=>$val
        ));
});


Flight::map('postHasValidations',function($post){
    return isset($post['_validate']);
});
Flight::map('getValidations',function($post,$type = 'all'){
    $type = isset($type)?$type:'all'; //restrict || condition
    $rta = array();
    foreach ($post['_validate'] as $key => $value) {
        $typeMatch = ($value[0] == $type)?true:false;
        if($type=='all') $typeMatch = true;
        if(!$typeMatch) continue;
        $rta[]= array(
            'type'=> $value[0],
            'field'=>$value[1],
            'operator'=>isset($value[2])?$value[2]:'',
        );
    }
   
    return $rta;
});
Flight::map('postRemoveValidations',function($post){
    unset($post['_validate']);
    return $post;
});

Flight::map('validateAgainstItem',function($vItem,$post){
    $validations = Flight::getValidations($post,'all');
    $conditions = Flight::getValidations($post,'condition');
    foreach ($validations as $keyRestrict => $vValidation) {
        $conditionPassedCounter = 0;
        if(!isset($post[$vValidation['field']])){return false;} //any validation field should exist on post
        if(!isset($vItem[$vValidation['field']])){
          if($vValidation['type']=='condition') return false;//a condition require the field. A restrict not.
          continue; //Existance restriction field in $db item  
        } 
        $vItemField = $vItem[$vValidation['field']];
        $vPostField = $post[$vValidation['field']];
        if(isset($vItemField)){
            switch ($vValidation['operator']) {
                case '=':
                    if($vValidation['type']=='restrict'){
                        if($vItemField==$vPostField) return false;    
                    }
                    if($vValidation['type']=='condition'){
                        if($vItemField==$vPostField) $conditionPassedCounter++;
                    }
                    break;
                case '<':
                    if($vValidation['type']=='restrict'){
                        if($vPostField<$vItemField) return false;    
                    }
                    if($vValidation['type']=='condition'){
                        if($vPostField<$vItemField) $conditionPassedCounter++;
                    }
                    break;
                default: //existance of the field
                    return false;
                    break;
            }
        }
        if($conditionPassedCounter<sizeof($conditions)) return false;
    }
    return true;
});

Flight::map('validateAgainstCollection',function($collectionName, $post){
    if(Flight::postHasValidations($post)){
        $items = Flight::crud_getAll($collectionName);
        foreach ($items as $key => $vItem) {
            if(!Flight::validateAgainstItem($vItem,$post)){
                return $vItem;
            }
        }
    }
    return null;
});

Flight::route('/crud/validationtest',function(){
    $post = array(
            'code'=>"IMAGES_HOME_LOGO",
            '_validate'=>[
                ['restrict','code','='],
            ]
        );
    $rta = array('ok'=>1);
    if(($matched = Flight::validateAgainstCollection('category',$post)) != null){
        $rta['matched'] =$matched;
    }
    Flight::res($rta);
});

Flight::route('/crud/save', function(){
    Flight::trycatch(function(){
        Flight::exitOnInvalidSession();
        $post = json_decode(file_get_contents('php://input'),TRUE);
        if((isset($post)?1:0)==0){
            $post =  array(
                    'collectionName'=>"category",
                    'code'=>'TEST',
                    'description'=>'Test'
                );
            $res['message'] = 'invalid post.';    
        }else{
            $res['message'] = 'save success';    
        }
        $collectionName = $post['collectionName'];
        unset($post['collectionName']);
        $res = Flight::getDefaultRes();
        if(($matched = Flight::validateAgainstCollection($collectionName,$post)) == null){
            $post = Flight::postRemoveValidations($post);
            $item = Flight::crud_save($collectionName,$post);
            $res['rta'] = $item;
        }else{
            $res['rta'] = $matched;
            $res['message'] = 'Not saved. Validations returns matched item.';
        }
        Flight::res($res);
    });
});
Flight::route('POST /crud/remove', function(){
    Flight::trycatch(function(){
        Flight::exitOnInvalidSession();
        $post = json_decode(file_get_contents('php://input'),FALSE);
        Flight::crud_remove($post->collectionName,$post->id);
        Flight::res(Flight::getDefaultRes());
        $res = Flight::getDefaultRes();
        $res['message'] = 'Single Remove OK';
        Flight::res($res);
    });
});
Flight::route('POST|GET /crud/clean(/@cn(/@pass))', function($cn,$pass = null){
    Flight::trycatch(function($p){
        $pass = $p['pass'];
        $cn = $p['cn'];
        $post = json_decode(file_get_contents('php://input'),FALSE);
        if(isset($cn)&&isset($pass)&&$pass=='gtf'){

        }else{
            Flight::exitOnInvalidSession();    
            $cn = $post->collectionName;
        }
        
        Flight::crud_remove_all($cn);
        $res = Flight::getDefaultRes();
        $res['message'] = 'Clean OK';
        Flight::res($res);
    },array('cn'=>$cn,'pass'=>$pass));
});



Flight::map('crud_getAll',function($collectionName){
	$collection = Flight::getFlintstoneDb($collectionName);
    return $collection->getAll();
});
Flight::map('crud_get',function($collectionName,$id){
	$collection = Flight::getFlintstoneDb($collectionName);
    return $collection->get($id);
});


Flight::map('crud_saveValidated',function($collectionName,$post){
    if(($matched = Flight::validateAgainstCollection($collectionName,$post)) == null){
        $post = Flight::postRemoveValidations($post);
        return Flight::crud_save($collectionName,$post);
    }else{
        return $matched;
    }
});

Flight::map('crud_save',function($collectionName,$array){
    $collection = Flight::getFlintstoneDb($collectionName);
    $array = Flight::prepareUniqueID($array);
    $collection->set($array[Flight::getFlintstonePrimaryKey()], $array);
    return $array;
});
Flight::map('crud_remove',function($collectionName,$id){
    $collection = Flight::getFlintstoneDb($collectionName);
    $collection->delete($id);
});

Flight::map('crud_remove_all',function($collectionName){
    $collection = Flight::getFlintstoneDb($collectionName);
    $collection->flush();
});

?>