<?php
use Flintstone\Flintstone;
use Flintstone\Formatter\JsonFormatter;



/*
// Set dbOptions
$dbOptions = array('dir' => 'db');
$settings = Flintstone::load('settings', $dbOptions);


$users = Flintstone::load('users', $dbOptions);
$userExample = '{"id":"Max","email":"max@site.com","password":33}';
$userExampleArray = json_decode($userExample,true);
$users->set($userExampleArray["id"], $userExampleArray);

$settings->set('site_offline', 0);
$settings->set('site_back', '3 days');

// Retrieve keys
$user = $users->get('bob');
echo 'Bob, your email is ' . $user['email'];

$offline = $settings->get('site_offline');
if ($offline == 1) {
    echo 'Sorry, the website is offline<br />';
    echo 'We will be back in ' . $settings->get('site_back');
}

// Retrieve all key names
$keys = $users->getKeys(); // returns array('bob', 'joe', ...)

foreach ($keys as $username) {
    $user = $users->get($username);
    echo $username.', your email is ' . $user['email'];
    echo $username.', your password is ' . $user['password'];
}

// Delete a key
$users->delete('joe');

// Flush the database
$users->flush();
*/
?>