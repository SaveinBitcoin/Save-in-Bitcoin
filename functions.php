<?php

// SaveinBitcoin Core Functions

function run() {
    
    // Assuming something will fail (that will change eventually in case of success)
    header("HTTP/1.0 500 Internal Server Error");
    
    /* Default headers */
    header("Content-Type: text/plain");
    
    // Prevent caching
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");

    $date = date(DATEFORMAT, time());
    $installedversion = file_get_contents('version.txt');
    appendtolog("SaveinBitcoin version ".$installedversion."\nRun: ".$date."\n");
    
    $log = checkforupdates();
    appendtolog($log);
    
    appendtolog("Transfer new deposits to the Trading account and schedule the next trades :\n");
    $log = preparetrades();
    appendtolog($log);
    
    sleep(1); // allow time to execute the 1st trade within the 1st run
    
    appendtolog("Executing trading queue:\n");
    $log = executequeue();
    appendtolog($log);
    
    appendtolog("Executing withdrawals:\n");
    $log = withdraw(); //$log .= withdraw(); // use to withdraw entire balance, otherwise use $log .= withdraw(WITHDRAWMIN); //withdraw the minimum
    appendtolog($log);
    
    appendtolog("Reading wallet balance:\n");
    $log = readwalletbalance(BTCWALLET);
    appendtolog($log);
    
    // add alerts
    appendtolog("Checks:\n");
    
    $log = alert_deposits();
    appendtolog($log);
    
    $log = alert_trades();
    appendtolog($log);
    
    $log = alert_withdrawals();
    appendtolog($log);
    
    deleteoldlogs();    

    $execution_time = microtime(true) - START_TIMER;

    // If everything went well, change status to 200 OK
    echo "\nSaveinBitcoin was executed in ".round($execution_time,1)." seconds.\n\n";
    header("HTTP/1.0 200 OK");    
    return;
}


function preparetrades() {
    
    // Find new deposits, move funds to subaccount, prepare future trades
    
    global $db;
    

    // Reads the latest deposits and save info to the database
    $log .= "Updating FIAT Deposits history:\n";
    $history = getransactions(EXCHANGE_CUSTOMERID,MAINAPI_KEY,MAINAPI_SECRET);
    $log .= savetransactions($history);
    
    

    $log .= "Updating current balances:\n";
    $balance = getbalance(EXCHANGE_CUSTOMERID,MAINAPI_KEY,MAINAPI_SECRET);
    $log .= savecurrentbalance($balance,'main');

    $balance =  getbalance(EXCHANGE_CUSTOMERID,TRADINGAPI_KEY,TRADINGAPI_SECRET);
    $log .= savecurrentbalance($balance,'trading');

    // Select the deposit(s) that have NOT been transferred to the subaccount
    $filter["sent2subaccount"] = '0';
    $select  = $db->SelectRows("deposits", $filter);
    if (! $select) $db->Kill();
    
    $totalamount = 0;
    $newdeposits = array();
    
    if($db->RowCount() > 0) {
        $log .= "Found ".$db->RowCount()." new deposit(s):\n";
    } else $log .= "No new deposits found\n";
      

    $db->MoveFirst();
    while (! $db->EndOfSeek()) {
        
        $row = $db->Row();
        $log .= "1 new deposit of ".$row->amount.DEPOSITCURRENCY." on ".$row->datetime." - ID: ".$row->id."\n";
    
        if(EMAIL_NOTIFICATIONS) sendmail("New FIAT deposit received","A deposit of ".$row->amount.DEPOSITCURRENCY." was received on ".$row->datetime.".");
    
        // get the deposit id
        array_push($newdeposits, array(
                                       'amount' => $row->amount,
                                       'id' => $row->id
                                       )
                  );
        
    }    

     
    foreach($newdeposits as $deposit) {   
        // Transfer the new deposit to the trading account
        $log .= transferbalance($deposit['amount'],DEPOSITCURRENCY,'main2trading');

        // Update the deposit record in database
        $update["sent2subaccount"] = MySQL::SQLValue($deposit['amount']);
        $where = array('id' => $deposit['id']);
        if (! $db->UpdateRows("deposits", $update,$where)) $db->Kill;
        
        $totalamount = $totalamount + $deposit['amount'];
    }
    
    
    if(count($newdeposits) > 0)
    $log .= "Transferred a total of ".$totalamount.DEPOSITCURRENCY." from ".count($newdeposits)." new deposits into Trading account\n";
    
    // Create new trades records, to be executed later
    
    // How many trades can I execute in DEPOSITINTERVAL days?
    $maxtrades = $totalamount/MINIMUMORDER;
    $maxtrades = floor($maxtrades);    
    //what will be the amount of each trade?
    if($maxtrades < DEPOSITINTERVAL) {
        // Set longer daily intervals
        $amount = $totalamount/$maxtrades;
        $days = DEPOSITINTERVAL/$maxtrades;        
    } else {
        $maxtrades = DEPOSITINTERVAL;
        $amount = $totalamount/DEPOSITINTERVAL;
        $days = 1;
    }
    
    if($totalamount > 0) {
        $log .= "Scheduling ".$maxtrades." new trades of ".$amount.DEPOSITCURRENCY." each, every ".$days." days\n";
        for ($i = 1; $i <= $maxtrades; $i++) {

            if($i == 1) {
                $startDate = time();
                $when = date('Y-m-d H:i:s', $startDate);
            } else {
                $wait = $wait + $days;
                $when = date('Y-m-d H:i:s', strtotime("+".$wait." day", $startDate));
            }
            
            $values["spend"] = MySQL::SQLValue($amount);
            $values["pair"] = MySQL::SQLValue(PAIR);        
            $values["datetime"] = MySQL::SQLValue($when, MySQL::SQLVALUE_DATETIME );
            $values["status"] = MySQL::SQLValue("new");
            $insert = $db->InsertRow("trades", $values);
            if (! $insert) $db->Kill();
        }
    }
    return $log;
    
}





function executequeue() {
    
    global $db;
    
    // Select all past due, un-executed transactions 
    $select = $db->Query("SELECT * FROM trades WHERE (datetime < CURRENT_TIMESTAMP AND status = 'new')");
    if (! $select) $db->Kill();
    
    $db->MoveFirst();
    while (! $db->EndOfSeek()) {
        $row = $db->Row();
        if($row) {
            $log .= trade(EXCHANGE_CUSTOMERID,TRADINGAPI_KEY,TRADINGAPI_SECRET,$row->spend,$row->id,PAIR);
        }        
    }
    return $log;
}


function readwalletbalance($wallet) {

    global $db;

    /*
    https://blockchain.info/en/q/addressbalance/1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa
    https://blockchain.info/rawaddr/1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa
    */
    
    $endpoint = 'https://blockchain.info/rawaddr/'.$wallet;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($ch);
    curl_close ($ch);    
    
    $output = json_decode($output);
    
    // Update wallet balance
    $record = array(
                    'updated' =>  MySQL::SQLValue( date('Y-m-d H:i:s', time()), MySQL::SQLVALUE_DATETIME ),
                    'account' => MySQL::SQLValue($wallet),
                    'currency' => MySQL::SQLValue('btc'),
                    'amount' => MySQL::SQLValue($output->final_balance/100000000),
                    );
    $update = array(
                    'account' => MySQL::SQLValue($wallet),
                    'currency' => MySQL::SQLValue('btc'),
                    );    
    $insert = $db->AutoInsertUpdate("currentbalance", $record, $update);
    //if (! $insert) $db->Kill();
    
    $log .= "Found ".count($output->txs )." executed withdrawals to ".$wallet."\n";
    
    foreach($output->txs as $transaction) {
                //    $log .= print_r($transaction,true);
        $when = date('Y-m-d H:i:s', $transaction->time);
        $height = $transaction->block_height;
        $txid = $transaction->hash;
                
        foreach($transaction->out as $txout) {
            
            if($txout->addr == $wallet) {
                
                        
                if(strtotime(INSTALL_DATE) < strtotime($when)) {
                    $received = $txout->value;
                
                    $log .= ($received/100000000)."btc on date: ".$when." - Tx: ".$txid." Height: ".$height."\n";
                
                                
                    $record = array(
                        'datetime' =>  MySQL::SQLValue($when, MySQL::SQLVALUE_DATETIME ),
                        'height' => MySQL::SQLValue($height),
                        'txid' => MySQL::SQLValue($txid),
                        'type' => MySQL::SQLValue('in'),
                        'value' => MySQL::SQLValue($txout->value/100000000),
                        'wallet' => MySQL::SQLValue($wallet),
                        );
                    $update = array(
                        'txid' => MySQL::SQLValue($txid),
                        );    
                    $insert = $db->AutoInsertUpdate("walletlog", $record, $update);
                    //if (! $insert) $db->Kill();
                    
                    
                }
            }
                
        }

    }
        

    $log .= "Final Balance = ".($output->final_balance/100000000)." btc\n";
        
    return $log;

  /*  
    $endpoint = 'https://blockchain.info/en/q/addressbalance/'.$wallet;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($ch);
    curl_close ($ch);    
    
    $balance = $output / 100000000;    
    return $balance;
*/
    
}



function sendmail($subject,$body) {
    
    global $mail;
    
    if(EMAIL_SIGNATURE) $body = $body. "\n\n".EMAIL_SIGNATURE;

    $mail->setFrom(EMAIL_SENDER_ADDRESS, EMAIL_SENDER_NAME);
    $mail->addAddress(EMAIL_RECIPIENT);
    $mail->Subject = $subject;
    $mail->Body = $body;
   
    $mail->isSMTP();
    $mail->SMTPAuth = TRUE;
    $mail->Host = SMTP_SERVER;
    $mail->SMTPSecure = 'tls';
    $mail->Username = SMTP_USER;
    $mail->Password = SMTP_PASS;
    $mail->Port = SMTP_PORT;
        
    $send = $mail->send();
        
    if($send) {
        $log .= "Email sent: [".$subject."]\n";
    } else {
        $log .= "Error sending email: [".$subject."]\n";
    }

    return $log;
}




function needtoalert($reason) {
    
    global $db;

    $query = "SELECT * FROM alerts WHERE ((alert=\"".$reason."\") AND (datetime > NOW() - INTERVAL ".ALERTS_INTERVAL." DAY))";
    
    $select = $db->Query($query);
    //if (! $select) $db->Kill();
    if($db->RowCount() == 0) {
        
        // Save the information about sent alert
        $now = time();
        $when = date('Y-m-d H:i:s', $now);
        $values["alert"] = MySQL::SQLValue($reason);            
        $values["datetime"] = MySQL::SQLValue($when, MySQL::SQLVALUE_DATETIME );
        $insert = $db->InsertRow("alerts", $values);
        //if (! $insert) $db->Kill();
        
        // Send the alert
        return true;
        
    } else return false;

}


function alert_deposits() {

    // alerts when there are no new deposits
    
    global $db;
    
    $select = $db->Query("SELECT * FROM deposits WHERE (datetime > NOW() - INTERVAL ".ALERT_DEPOSITS." DAY)");
    if (! $select) $db->Kill();
    if($db->RowCount() == 0) {
           
        $check = needtoalert("No new deposits in the last ".ALERT_DEPOSITS." days");
        
        if($check) {
            $select  = $db->SelectRows("deposits", NULL,NULL,"id",false,3);
            if (! $select) $db->Kill();
            $db->MoveFirst();
            while (! $db->EndOfSeek()) {    
                $row = $db->Row();
                $latest .= round($row->amount,2).$row->currency." on ".$row->datetime."\n\n";
            }        
            
            $subject = "[ACTION NEEDED] No deposits found in the last ".ALERT_DEPOSITS." days";
            $body = "Seems like ".EXCHANGE." failed to receive new ".DEPOSITCURRENCY." deposits in the last ".ALERT_DEPOSITS." days.\n\n";
            $body .= "You may want to check with your bank to make sure transfers are being executed, and check the deposits log at ".EXCHANGE.", or review your alerts settings.\n\n";
            $body .= "For your reference, the latest logged deposits are the following:\n\n".$latest;
            
            $log .= sendmail($subject,$body);
            
        } else {
            $log .= "Deposits are late, alert was sent already less than ".ALERTS_INTERVAL." days ago\n";            
        }
        
    } else {
        $log .= "Latest deposit arrived at ".EXCHANGE." on time (less than ".ALERT_DEPOSITS." days ago)\n";
    }
    return $log;
}

function alert_trades() {
    
    // alerts when there are past due, un-executed transactions 
    
    global $db;
    
    $select = $db->Query("SELECT * FROM trades WHERE (datetime < CURRENT_TIMESTAMP AND status = 'new')");
    if (! $select) $db->Kill();
    
    if($db->RowCount() != 0) {
        
        $check = needtoalert("Failed to execute due trades");
        
        if($check) {
            
            if (! $select) $db->Kill();
            $db->MoveFirst();
            while (! $db->EndOfSeek()) {    
                $row = $db->Row();
                $failed .= "Buy ".$row->spend.DEPOSITCURRENCY." on ".$row->datetime."\n\n";
            }        
            
            $subject = "[ACTION NEEDED] Unable to execute trades";        
            $body = "Seems like ".EXCHANGE." failed to execute the latest trades.\n\n";
            $body .= "You may want to check the balances and trades log at ".EXCHANGE.".\n\n";
            $body .= "Here is a record of the trades that failed to execute:\n\n".$failed;
            
            $log .= sendmail($subject,$body);
            
        } else {
            $log .= "Trades were not executed, alert was sent already less than ".ALERTS_INTERVAL." days ago\n";            
        }
                
    } else {
        $log .= "No past due, un-executed trades\n";
    }
    return $log;
}

function alert_withdrawals() {

    // alerts when there are no recent withdrawal to your wallet
    
    global $db;
    
    $select = $db->Query("SELECT * FROM walletlog  WHERE (datetime > NOW() - INTERVAL ".ALERT_WITHDRAWALS." DAY)");
    if (! $select) $db->Kill();
    
    if($db->RowCount() == 0) {

        $check = needtoalert("No new withdrawals in the last ".ALERT_WITHDRAWALS." days");
        
        if($check) {
            
            $select  = $db->SelectRows("walletlog", NULL,NULL,"datetime",false,3);
    
            if (! $select) $db->Kill();
            $db->MoveFirst();
            while (! $db->EndOfSeek()) {    
                $row = $db->Row();
                
                $latest .= $row->datetime." received ".$row->value." btc - tx: ".$row->txid."\n\n";
            }
            
            $subject = "[ACTION NEEDED] No recent withdrawals to your wallet";        
            $body = "Seems like ".EXCHANGE." failed to send any new bitcoin to your wallet in the last ".ALERT_WITHDRAWALS." days.\n\n";
            $body .= "You may want to check the withdrawals log at ".EXCHANGE.", or review your alerts settings.\n\n";
            $body .= "Also you may want to check your bitcoin wallet: ".BTCWALLET."\n\n";
            $body .= "Here is a log of your most recent on-chain incoming transactions: \n\n".$latest;
            
            $log .= sendmail($subject,$body);

        } else {
            $log .= "Withdrawals are late, alert was sent already less than ".ALERTS_INTERVAL." days ago\n";  
        }            
        
    } else {
        $log .= "Latest bitcoin withdrawal was on time (less than ".ALERT_WITHDRAWALS." days ago)\n";
    }
    return $log;
}



function checkforupdates() {
   
   // Checks Github for new releases and sends an email if a new version is available
   
    global $db;
    
    $installedversion = file_get_contents('version.txt');
   
    //get latest version
    $endpoint = REPOSITORY."releases/latest/";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, TRUE);
    $output = curl_exec($ch);
    curl_close ($ch);    
    if (preg_match('~Location: (.*)~i', $output, $match)) {
       $location = trim($match[1]);
    }
    $currentversion = str_replace(REPOSITORY."releases/tag/","",$location);


    if(version_compare($installedversion,$currentversion,'<')) {
        $log = "New version available: ".$currentversion."\n";

        $check = needtoalert("Update SaveinBitcoin to version ".$currentversion);
        
        if($check) {            
            
            $subject = "[ACTION NEEDED] There is a new version of SaveinBitcoin available";        
            $body = "You are running an outdated version of SaveinBitcoin: version ".$installedversion.".\n\n";
            $body .= "SaveinBitcoin version ".$currentversion." is available.\n\n";
            $body .= "Download it from here:\n";
            $body .= $endpoint. "\n\n";
            
            $log .= sendmail($subject,$body);
            
        } else {
            $log .= "Update prompt was sent less than ".ALERTS_INTERVAL." days ago\n";  
        } 
        
    } else {
        $log = "Latest version is installed (".$installedversion.")\n";
    }
    
    return $log;

}




function appendtolog($log) {
    
    // write log file
    
    $logfilename = date('Y-m-d',time());
    $logfile = fopen("logs/".$logfilename.".txt", "a") or die("Unable to open log file!");
    fwrite($logfile, $log);
    fclose($logfile);
    if(OUTPUT_LOGS) echo $log;
    return;
}




function deleteoldlogs() {
    
    // churn old logs
    
    $list = scandir("logs");
    foreach($list as $file) {
        // is it a txt file?
        if (strpos($file, ".txt") !== false) {
            $file = str_replace(".txt","",$file);            
            // how old is this log file, in days?
            $diff = ((time() - strtotime($file))/60/60/24);
            //should it be deleted?
            if($diff > DELETE_LOGS_OLDER_THAN) {
                unlink("logs/".$file.".txt");
            }
        }        
    }    
}


function readtable($table, $filter = array(), $columns = NULL, $sortby = NULL, $sortascending = NULL, $limit = NULL ) {
    
    // reads a database table and outputs it

    global $db;
    
    foreach($filter as $row => $value) {
        
        $filter[$row] = MySQL::SQLValue($value);            
    }
    
    $select  = $db->SelectRows($table, $filter, $columns, $sortby, $sortascending, $limit);  
    if (! $select) $db->Kill();
    
    echo $db->GetHTML();
    
    return;
    
}







?>