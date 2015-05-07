<?php

Flight::map('uniqueID',function($len=30){
	$hex = md5("your_random_salt_here_31415" . uniqid("", true));
    $pack = pack('H*', $hex);
    $uid = base64_encode($pack);        // max 22 chars
    $uid = ereg_replace("[^A-Za-z0-9]", "", $uid);    // mixed case
    if ($len<4)
        $len=4;
    if ($len>128)
        $len=128;                       // prevent silliness, can remove
    while (strlen($uid)<$len)
        $uid = $uid . Flight::uniqueID(22);     // append until length achieved

    return substr($uid, 0, $len);
});
Flight::map('hasIDField',function($array){
	return isset($array[Flight::getFlintstonePrimaryKey()]);
});
Flight::map('prepareUniqueID',function($array){
	if(!Flight::hasIDField($array)){
		$array[Flight::getFlintstonePrimaryKey()] = Flight::uniqueID();
	}
	//echo json_encode($array);
	return $array;
});


?>