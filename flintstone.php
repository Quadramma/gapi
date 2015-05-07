<?php
use Flintstone\Flintstone;
use Flintstone\Formatter\JsonFormatter;

Flight::map('getFlintstoneDb',function($n){
	$dbOptions = Flight::getFlintstoneOpts();
    $db = Flintstone::load($n, $dbOptions);
    return $db;
});
Flight::map('getFlintstoneOpts',function(){
    return array('dir' => 'db');
});
Flight::map('getFlintstonePrimaryKey',function(){
	return "_id";
});

?>