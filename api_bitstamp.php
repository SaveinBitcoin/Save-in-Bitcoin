<?php
/* BEGIN BITSTAMP API FUNCTIONS */
function querybitstamp($endpoint,$customerid,$apikey,$apisecret,$data=array()) {
    // General purpose Bitstamp API caller: performs a call to the Bitstamp API, with the specified parameters
    
    //$nonce = str_replace('.','',microtime(true));
    $nonce = microtime(true)*10000;
    $message = $nonce.$customerid.$apikey;
    $signature = strtoupper(hash_hmac('sha256',$message,$apisecret));
    
    $query = array(
                "key" => $apikey,
                "signature" => $signature,
                "nonce" => $nonce
                );
    foreach($data as $key => $value) {
        $query[$key] = $value;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$endpoint);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($query) );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($ch);
    curl_close ($ch);    

    $server_output =  json_decode($output,true);
    if($server_output['status'] == 'error'){
        die("API ERROR: ".print_r($server_output['reason'],true)." (Code: ".$server_output['code'].") - Endpoint: ".$endpoint."\nQuery: ".print_r($query,true));
    }
    
    return $output;
}


function getbalance($customerid,$apikey,$apisecret,$currency='') {
    
    // Retrieves the balance data for the specified account
    
    $balance = querybitstamp('https://www.bitstamp.net/api/v2/balance/',$customerid,$apikey,$apisecret);
    
    if(!empty($currency)) {
        $balance = json_decode($balance,true);    
        $balance = $balance[$currency];
    }
    return $balance;    
}

function getransactions($customerid,$apikey,$apisecret,$pair='') {
    // Fetch all transactions associated with the specified account
    $history = querybitstamp('https://www.bitstamp.net/api/v2/user_transactions/',$customerid,$apikey,$apisecret);
    return $history;        
}


function savetransactions($history) {
    
    // Saves the FIAT deposits in the database
    
    global $db;
    
    $history = json_decode($history,true);
    
    $deposits = $total = 0;
    //$log .= print_r($history,true);
    // save deposits in fiat currency
    foreach($history as $transaction) {
        
        if(strtotime(INSTALL_DATE) < strtotime($transaction['datetime'])) {
        
            if(($transaction['type'] == 0) && ($transaction[DEPOSITCURRENCY] > 0)) { // FIAT deposit
                
                    $log .= "Found deposit of ". $transaction[DEPOSITCURRENCY] . DEPOSITCURRENCY . " on date: ".$transaction['datetime']."\n";
                    $record = array(
                                'datetime' =>  MySQL::SQLValue($transaction['datetime'], MySQL::SQLVALUE_DATETIME ),
                                'currency' => MySQL::SQLValue(DEPOSITCURRENCY),
                                'amount' => MySQL::SQLValue($transaction[DEPOSITCURRENCY])
                                );        
                    $insert = $db->AutoInsertUpdate("deposits", $record, $record);
                    $totaldeposits = $totaldeposits + $transaction[DEPOSITCURRENCY];
                    $deposits++;
                
            }
            /*
            if(($transaction['type'] == 1) && ($transaction['btc'] != 0)) { // BTC withdrawal                
                    $log .= "Found withdrawal of ". -$transaction['btc'] . "btc on date: ".$transaction['datetime']."\n";
            }
            */
        }
        
    }
    $log .= $deposits ." Deposits found, total: ".$totaldeposits.DEPOSITCURRENCY."\n";
    return $log;
}



function savecurrentbalance($balance,$account) {
    
    // Save the current FIAT and BTC balance of the specified account to the database
    global $db;    
    
    $balance = json_decode($balance,true);

    // save BTC balance
    $record = array(
                'updated' =>  'CURRENT_TIMESTAMP',
                'account' => MySQL::SQLValue($account),
                'currency' => MySQL::SQLValue('btc'),
                'amount' => MySQL::SQLValue($balance['btc_available'])
                );
    $update = array(
                'account' => MySQL::SQLValue($account),
                'currency' => MySQL::SQLValue('btc'),
                );    
    $insert = $db->AutoInsertUpdate("currentbalance", $record, $update);
   // if (! $insert) $db->Kill();
        

    // save Fiat balance
    $record = array(
                'updated' =>  'CURRENT_TIMESTAMP',
                'account' => MySQL::SQLValue($account),
                'currency' => MySQL::SQLValue(DEPOSITCURRENCY),
                'amount' => MySQL::SQLValue($balance[DEPOSITCURRENCY.'_available'])
                );       
    $update = array(
                'account' => MySQL::SQLValue($account),
                'currency' => MySQL::SQLValue(DEPOSITCURRENCY),
                );
    //var_dump($update);
    $insert = $db->AutoInsertUpdate("currentbalance", $record, $update);
   // if (! $insert) $db->Kill();

    $log .= "There are ".$balance['btc_available']." BTC and ".$balance[DEPOSITCURRENCY.'_available']." ".DEPOSITCURRENCY." on " .$account." account\n";
    
    // Save info about current fees
     $record = array(
                'fee' => $balance['btcusd_fee'],
                'pair' => MySQL::SQLValue('btcusd')
                );
     $update = array(
                'pair' => MySQL::SQLValue('btcusd')
                );
    $insert = $db->AutoInsertUpdate("fees", $record, $update);
   // if (! $insert) $db->Kill();

    // Save info about current fees
     $record = array(
                'fee' => $balance['btceur_fee'],
                'pair' => MySQL::SQLValue('btceur')       
                );
     $update = array(
                'pair' => MySQL::SQLValue('btceur')      
                );
    $insert = $db->AutoInsertUpdate("fees", $record, $update);
   // if (! $insert) $db->Kill();
    

    return $log;
                
}
function trade($customerid,$apikey,$apisecret,$amount,$id="",$pair=PAIR) {
    
    // Performs an Instant buy order and saves result to the database
    global $db;
    
    $data = array('amount' => $amount);
    
    $trade = querybitstamp('https://www.bitstamp.net/api/v2/buy/instant/'.$pair.'/',$customerid,$apikey,$apisecret,$data);
    $trade = json_decode($trade,true);
    
    // Calculate fees and purchase value    
    $filter["pair"] = MySQL::SQLValue(PAIR);
    $select  = $db->SelectRows("fees", $filter);
    if (! $select) $db->Kill();
    $db->MoveFirst();
    while (! $db->EndOfSeek()) {        
        $row = $db->Row();
        $fee = $row->fee;        
        $cost = $amount*$fee;
        $cost = ceil($cost)/100; // round to upper cent
    }

    $traded = $amount - $cost;
    $bought = $traded/$trade['price'];

    $record = array(
                'id' => MySQL::SQLValue($id),
                'datetime' =>  'CURRENT_TIMESTAMP',
                'status' => MySQL::SQLValue('executed'),
                'amount' => MySQL::SQLValue($traded),
                'price' => MySQL::SQLValue($trade['price']),
                'pair' => MySQL::SQLValue($pair),
                'bought' => MySQL::SQLValue($bought),
                'fees' => MySQL::SQLValue($cost),
                
                );
    //$insert = $db->InsertRow("trades", $record);    
    $update = array(
                'id' => MySQL::SQLValue($id),
                );
    $insert = $db->AutoInsertUpdate("trades", $record, $update);    
    //if (! $insert) $db->Kill();
    
    $log .= "Trade executed: buy ".round($bought,8)." BTC for ".$traded.DEPOSITCURRENCY." (fees: ".$cost.DEPOSITCURRENCY.")\n";
    if(EMAIL_NOTIFICATIONS) sendmail("BTC Purchase executed","Bought ".round($bought,8)." BTC for ".$traded.DEPOSITCURRENCY." (fees: ".$cost.DEPOSITCURRENCY.")");
    return $log;
    
}

function transferbalance($amount,$currency,$direction) {
    
    global $db;
    
    switch($direction) {
        case 'main2trading': // Transfer FIAT balance from Main account to Sub account

            // get main account balance
            $balance = getbalance(EXCHANGE_CUSTOMERID,MAINAPI_KEY,MAINAPI_SECRET);
            $balance = json_decode($balance,true);
            $balance = $balance[$currency.'_available'];
            
            if($amount > $balance) {
                $log .= "Error: available FIAT balance is ".$balance.$currency."; cannot transfer ".$amount.$currency." to subaccount";
                
                $subject = "[ACTION NEEDED] Unable to transfer your recent deposit to trading account";        
                $body = $log;        
                $log .= sendmail($subject,$body);
                
                return $log;
            }
   
            $amount = number_format((float)$amount, 2, '.', '');
            $data = array('amount' => $amount,
                        'currency' => strtoupper(DEPOSITCURRENCY),
                          'subAccount' => SUBACCOUNTID
                        );
            // perform transfer
            $transfer = querybitstamp('https://www.bitstamp.net/api/v2/transfer-from-main/',EXCHANGE_CUSTOMERID,MAINAPI_KEY,MAINAPI_SECRET,$data);
            
            $log .= "Transferred ".$amount.DEPOSITCURRENCY." From main account to trading account\n";
            
            break;
            
        case 'trading2main': // Transfer BTC balance from Trading account to Main account (for withdrawals)

            $balance =  getbalance(EXCHANGE_CUSTOMERID,TRADINGAPI_KEY,TRADINGAPI_SECRET);
            $balance = json_decode($balance,true);
            $balance = $balance[$currency.'_available'];
            
            if($amount > $balance) {
                $log .= "Error: available BTC balance is ".$balance.$currency."; cannot transfer ".$amount.$currency." to subaccount";
                
                $subject = "[ACTION NEEDED] Unable to transfer your BTC purchases to Main account for withdrawal";        
                $body = $log;        
                $log .= sendmail($subject,$body);
                
                return $log;
            }

            $amount = number_format((float)$amount, 8, '.', '');
            $data = array('amount' => $amount,
                        'currency' => strtoupper($currency),
                        );
            $transfer = querybitstamp('https://www.bitstamp.net/api/v2/transfer-to-main/',EXCHANGE_CUSTOMERID,TRADINGAPI_KEY,TRADINGAPI_SECRET,$data);
            
            $log .= "Transferred ".$amount.$currency." From trading account to main account\n";
            
            break;        

    }
    
    // Save balances
    $balance = getbalance(EXCHANGE_CUSTOMERID,MAINAPI_KEY,MAINAPI_SECRET);
    $log .= savecurrentbalance($balance,'main');    
    $balance =  getbalance(EXCHANGE_CUSTOMERID,TRADINGAPI_KEY,TRADINGAPI_SECRET);
    $log .= savecurrentbalance($balance,'trading');

    return $log;
}





function withdraw($amount="",$currency="btc") {

    global $db;

    $balance =  getbalance(EXCHANGE_CUSTOMERID,TRADINGAPI_KEY,TRADINGAPI_SECRET);
    $log .= savecurrentbalance($balance,'trading');
    
    $balances = array();
    
    if($amount == "") {
        // Get accounts balances
        $filter["account"] = MySQL::SQLValue("trading");
        $filter["currency"] = MySQL::SQLValue($currency);
        $select  = $db->SelectRows("currentbalance", $filter);
        if (! $select) $db->Kill();
        $db->MoveFirst();
        while (! $db->EndOfSeek()) {        
            $row = $db->Row();
            $balances[$row->account] = $row->amount;
        }
        $amount = $balances['trading'];
    } // else $amount = $amount
    
    if($amount >= WITHDRAWMIN) {
        //if($balances['trading'] <= WITHDRAWMIN) {
        
            // create a new record for this withdrawal
            $record = array(
                        'datetime' =>  MySQL::SQLValue(NOW, MySQL::SQLVALUE_DATETIME ),
                        'currency' => MySQL::SQLValue($currency),
                        'amount' => MySQL::SQLValue($amount)
                        );        
            $insert = $db->AutoInsertUpdate("withdrawals", $record, $record);


            $withdrawalid = $db->GetLastInsertID();

            // Transfer BTC to Main account
            $log .= transferbalance($amount,$currency,'trading2main');            
            
            // Update the withdrawal record in database
            $update["sent2mainaccount"] = MySQL::SQLValue($amount);
            $where = array('id' => $withdrawalid);
            if (! $db->UpdateRows("withdrawals", $update,$where)) $db->Kill;
        
            // initiate withdraw
            $data = array(
                          'amount' => $amount ,
                          'address' => BTCWALLET,
                          'currency' => strtoupper($currency),
                          'subAccount' => SUBACCOUNTID
                    );
            
            // perform withdrawal
            $transfer = querybitstamp('https://www.bitstamp.net/api/bitcoin_withdrawal/',EXCHANGE_CUSTOMERID,MAINAPI_KEY,MAINAPI_SECRET,$data);
            $transfer = json_decode($transfer);
            
            if(!$transfer->error) {
               
               // Update the withdrawal record in database
               $update["exchange_id"] = MySQL::SQLValue($transfer->id);
               $where = array('id' => $withdrawalid);        
               if (! $db->UpdateRows("withdrawals", $update,$where)) $db->Kill;                        
               
               $log .= "Requested withdrawal of ".$amount.$currency." to wallet ".BTCWALLET."\n";
                if(EMAIL_NOTIFICATIONS) sendmail("New withdrawal requested","A withdrawal of ".$amount.$currency." to wallet ".BTCWALLET." has been requested.");
               
            } else {
                $log .= print_r($transfer->error,true);
            }
        
        /*
        } else {
            $log .= "Available ".$currency." (".$balances['trading'].") is higher than requested withdraw of ".WITHDRAWMIN.$currency."\n";
        }
        */
    } else {
        $log .= "Available ".$currency." (".$balances['trading'].") is lower than mimimum withdrawable amount of ".WITHDRAWMIN.$currency."\n";
    }

    // Save balances
    $balance = getbalance(EXCHANGE_CUSTOMERID,MAINAPI_KEY,MAINAPI_SECRET);
    $log .= savecurrentbalance($balance,'main');    
    $balance =  getbalance(EXCHANGE_CUSTOMERID,TRADINGAPI_KEY,TRADINGAPI_SECRET);
    $log .= savecurrentbalance($balance,'trading');
    
    return $log;
}


/* END API FUNCTIONS */
?>