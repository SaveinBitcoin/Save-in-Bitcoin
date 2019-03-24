<?php

// Online report

require("config.php");

if(!ENABLE_REPORT) die();

echo "<pre>";
echo "<h1>Save in Bitcoin</h1>";

echo "<h2>Balances</h2>";
readtable(
          "currentbalance",
          NULL,
          array(
                    "amount",
                    "currency",
                    "account",
                    "updated",
                    
          )
);

echo "<h2>Deposits</h2>";
readtable(
          "deposits",
          NULL,
          array(
                    "amount",
                    "currency",
                    "datetime",
                    
          ),
          "datetime",
          false
);

echo "<h2>Pending orders</h2>";
readtable(
          "trades",
          array(
                "status" => "new",
                ),
          array(
                    "spend",
                    "datetime",
                    
          ),
          "datetime",
          false
          );

echo "<h2>Executed orders</h2>";
readtable(
          "trades",
          array(
                "status" => "executed",
                ),
          array(
                    "datetime",
                    "price",
                    "bought",
                    "amount",
                    "fees",
          ),
          "datetime",
          false
          );

echo "<h2>Withdrawals to ".BTCWALLET."</h2>";
readtable(
          "withdrawals",
          NULL,
          array(
                    "datetime",
                    "amount",
                    "exchange_id",
          ),
          "datetime",
          false
          );

          
echo "<h2>Wallet log</h2>";
readtable(
          "walletlog",
          NULL,
          array(
                    "datetime",
                    "value",
                    "txid",
                    "height",
                    "type",
          ),
          "datetime",
          false
          );
    

echo "<h2>Install Date</h2>";
echo INSTALL_DATE;


echo "</pre>";
?>