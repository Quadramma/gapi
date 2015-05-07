<?php

require 'vendor/autoload.php';
require 'error.php';

require 'flintstone.php';
require 'uniqueid.php';
require 'token.php';
require 'session.php';
require 'response.php';
require 'crud.php';
require 'upload.php';


require 'user_routes.php';

define('PHPSELF',$_SERVER['PHP_SELF']);
define('HTTPHOST',$_SERVER["HTTP_HOST"]);
define('API_URL',HTTPHOST.substr(PHPSELF,0,strrpos(PHPSELF,"/")));
define('API_VERSION','1.0');
//$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
define('API_INFO',''.'{'
	.'"API_VERSION":"'.API_VERSION
	.'","API_URL":'.API_URL
	//.'","other":'.'"other_text"'
	.'}');

Flight::route('/', function(){
    echo Flight::json(API_INFO);
});
Flight::map('test', function(){
    echo "It works!";
});
Flight::start() // Starts the framework.

?>