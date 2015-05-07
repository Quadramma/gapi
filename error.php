<?php 

Flight::map('error', function(Exception $ex){
    // Handle error
    echo $ex->getTraceAsString();
});
/*
Flight::set('flight.log_errors', true);
Flight::map('notFound', function(){
    // Handle not found
});
*/


class QJERRORCODES {
        public static $API_TOKEN_EXPIRED = 3;
        public static $API_INVALID_TOKEN = 4;
        public static $API_INVALID_CREDENTIALS = 5;
        public static $API_ROUTE_NOT_FOUND = 6;
        public static $API_UNKNOWN_EXCEPTION = 7;
        public static $API_FILE_UPLOAD_EXEDED_SIZE_LIMIT = 8;
        public static $API_INVALID_PATH = 9;
}


?>