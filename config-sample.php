<?php
/*
 * For instructions about how you should compile this file, please refer to:
 * https://saveinbitcoin.com/install/
 *
 * When done, please rename this file to "config.php" and upload it again.
 * 
*/

/* Script details */
define("INSTALL_DATE","2019-03-24"); //will ignore deposits and withdrawals made before this date. Format: YYYY-MM-DD
define("ENABLE_REPORT",false); // set true to see a detailed report in the home page
define("OUTPUT_LOGS",false); // set true to output detailed logs at run time as run.php is executed
define("DELETE_LOGS_OLDER_THAN",30); //will delete log files older than this number of days

/* MySQL database credentials */
define("DB_NAME", ""); // Your MySQL database name
define("DB_USER", ""); // Your MySQL username
define("DB_PASSWORD", ""); // Your MySQL password
define("DB_HOST", "localhost"); // Your MySQL host

/* Exchange Details */
define("EXCHANGE","bitstamp"); // "bitstamp" is the only supported exchange at the moment
define("EXCHANGE_CUSTOMERID", ""); // For bitstamp, you can retrieve this in your account
define("DEPOSITCURRENCY","eur"); // "eur" or "usd"
define("MINIMUMORDER", "5"); //minimum accepted order at the exchange

/* Main account, used only for withdrawals */
define("MAINAPI_KEY","");
define("MAINAPI_SECRET","");

/* Sub account, used for orders */
define("TRADINGAPI_KEY","");
define("TRADINGAPI_SECRET","");
define("SUBACCOUNTID","");

/* Trading details */
define("PAIR","btceur"); // "btceur" or "btcusd"
define("DEPOSITINTERVAL",30); // number of days expected between new deposits

/* Where should it withdraw */
define("BTCWALLET", ""); // Your Bitcoin wallet address
define("WITHDRAWMIN","0.00006"); // Mimimum amount of bitcoin for withdrawal

/* Alerts settings */
define("EMAIL_RECIPIENT","you@example.com"); // Where do you want to receive alerts and notifications
define("ALERTS_INTERVAL",2); // After how many days should I resend the alerts?
define("EMAIL_NOTIFICATIONS",true); // Send an email notification on every action (set to "false" if you want to receive only error alerts)
define("ALERT_DEPOSITS",DEPOSITINTERVAL+10); // After how many days do you want to receive an alert if there are no new FIAT deposits to the exchange
define("ALERT_WITHDRAWALS",7); // After how many days without new incoming transactions to your wallet do you want to receive an alert

/* Email settings for alerts and notifications */
define("EMAIL_SENDER_ADDRESS","you@example.com");
define("EMAIL_SENDER_NAME","Save in Bitcoin");
define("EMAIL_SIGNATURE","Sent by Save in Bitcoin\n\nhttps://saveinbitcoin.com");

/* SMTP settings: you can use those from your email provider, or sign up for a free account at Sendgrid for example */
define("SMTP_SERVER",""); // Your SMTP server
define("SMTP_USER",""); // Your SMTP username
define("SMTP_PASS",""); // Your SMTP password
define("SMTP_PORT","587"); // Default SMTP port

/* Date format used in logs */
define("DATEFORMAT","Y-m-d H:i:s"); 

/* End of configuration. You do not need to edit anything below this line */
require("init.php");

?>