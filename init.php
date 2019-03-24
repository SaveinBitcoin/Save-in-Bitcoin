<?php

/* Version Control */
define('REPOSITORY', 'https://github.com/SaveinBitcoin/Save-in-Bitcoin/');

// calculate execution time;
define('START_TIMER',microtime(true));


/* MySQL Class */
require("vendor/mysql.class.php");
$db = new MySQL();
if (! $db->Open(DB_NAME, DB_HOST, DB_USER, DB_PASSWORD)) {
    $db->Kill();
}

/* PHP Mailer */
require 'vendor/phpmailer/Exception.php';
require 'vendor/phpmailer/PHPMailer.php';
require 'vendor/phpmailer/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
$mail = new PHPMailer(TRUE);

/* Exchange APIs */
require('api_'.EXCHANGE.'.php');

/* SaveinBitcoin core functions */
require('functions.php');

?>

