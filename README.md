Buy bitcoin automatically every day, with SaveinBitcoin
=======================================================

SaveinBitcoin is a free and open source software, that **automates the recurring conversion** of money from your bank account to your own bitcoin wallet.

Everyone can [download](https://github.com/SaveinBitcoin/Save-in-Bitcoin/releases/latest/) and use this software **_safely_** and **for Free**.

**Official website**: [SaveinBitcoin.com](https://saveinbitcoin.com/)

Why use SaveinBitcoin
=====================

> Imagine you decide to **invest a small amount of money every week into Bitcoin**, with a very long term vision (many, many years).

**Doing that manually** might involve creating an habit of making a new deposit to an exchange every week, log in there, place an order, and then withdraw the proceeds.

This requires a lot of **discipline**, and there is some **work** to do on each step every single time.

That approach is prone to forgetfulness, mistakes, and temptations to steer away from the original plan.

That’s simply **a recipe for failure**, and indeed, many people fail to save any significant amount for these exact reasons.

You don't want to find yourself among those people who **fail** to save because of a **flawed** approach, do you?

> **Automation** is the key to solve this problem.

SaveinBitcoin can help you with that.

What exactly SaveinBitcoin does?
================================

It **automates a recurring investment** in bitcoin, while minimizing costs and risks.

Once it is set up, as you send money from your bank account to your own exchange account, SaveinBitcoin will manage those funds, and execute buy orders for you.

> The software will **spread your acquisitions evenly over time**, and right after each purchase it can **send the bitcoins to a wallet** of your choice.

You just need to instruct your bank to (repeatedly) wire some money to an exchange you trust; **SaveinBitcoin can safely take care of everything else for you**.

You will **get alerts** if something gets stuck in the process, and you can opt in to **receive notifications** upon any new deposit, purchase, and withdrawal.

How it works
============

*   When a new bank wire transfer is received by the exchange, and credited to your account, it gets detected by SaveinBitcoin, which then moves the money to its own dedicated sub-account at the exchange.
*   Those funds are splitted into one or more buy orders (up to 1 per day) of equal amounts, that are scheduled to be executed at regular intervals.
*   The order type is instant, meaning it will immediately trade a fixed dollar amount for BTC, at whatever the market ask price is in that moment.
*   As soon as bitcoins are purchased and made available in your exchange sub-account, SaveinBitcoin sends them to your own wallet.

Example:
--------

*   On the 1st day of each month, you send $50 from your bank account to your exchange account.
*   A few days later, the exchange receives the deposit, and that gets detected by SaveinBitcoin.
*   SaveinBitcoin schedules 10 new buy orders of $5, one each 3 days (30 days / 10 orders).
*   Every 3 days, SaveinBitcoin will buy $5 worth of bitcoin, regardless of the price.
*   After each purchase, SaveinBitcoin requests the exchange to withdraw available bitcoins to your own wallet.
*   As a new month begins, you send another $50 (or any other amount) to the exchange, and the cycle repeats itself.

Features
========

Security
--------

SaveinBitcoin can manage your funds safely without the need for you to trust any part of it, as long as you set up your exchange account correctly, and limit the permissions for the API keys used by SaveinBitcoin.

For detailed instructions on how to do that, please refer to the section named "[Security Measures](https://saveinbitcoin.com/install/#Security_Measures)" under "How to install set up SaveinBitcoin".

Also, as usual you should take care of your own bitcoin wallet private keys. I am using an hardware wallet myself.

> If you follow the best practices for bitcoin wallet management, and the instructions provided here, you will be the only person who can spend your own funds.

Alerts
------

SaveinBitcoin will send you an email if something goes wrong:

*   When new deposits fail to arrive at the exchange after a given amount of time (so you can check with your bank and review your wire orders).
*   If scheduled trades don'get executed for any reason.
*   When your own wallet hasn'received any new bicoins after a given amount of time (so you can check with the exchange why the withdrawal requests failed).
*   If you set up everything according to the instructions, you will get an email also if SaveinBitcoin itself fails to run (so you can check your own server for uptime or other issues).

Notifications
-------------

SaveinBitcoin can send you a notification every time an action is performed:

*   A new deposit is received
*   A new trade is executed

Reports
-------

When you enable this feature, SaveinBitcoin produces an (optionally password protected) online report page that shows in real time:

*   The available balances at the exchange
*   Your own bitcoin wallet's balance
*   A list of all the deposits received by the exchange
*   The current queue of pending orders
*   An history with details for each executed orders
*   A log of all bitcoin withdrawal requests sent to the exchange
*   A list with the details of each blockchain transaction that resulted in bitcoin sent to your own wallet

Logs
----

SaveinBitcoin stores log files of everything it does.

You can choose for how long it will retain the log files.

Technical Requirements
======================

*   A bank account that allows you to send money to cryptocurrency exchanges
    *   (preferably, your banking also supports scheduling of recurring transfers).
*   An active account at one of the [Supported Exchanges](https://saveinbitcoin.com/exchanges/).
*   A bitcoin wallet address to be used only for this purpose (a paper or hardware wallet would be perfect).
*   A web hosting environment that supports PHP and MySQL.
*   SMTP settings to send emails (that can be from your email or hosting provider).
*   An email address to receive alerts and notifications.

How to install and set up SaveinBitcoin
=======================================

Create a wallet
---------------

To begin with, create a new bitcoin wallet that you will use only for SaveinBitcoin.

This way, both you and SaveinBitcoin will be able to scan the blockchain and retrieve just the transactions associated with your bitcoin saving fund activity.

You can use an hardware wallet, or an offline paper wallet generator.

Set up an exchange account
--------------------------

Bitstamp is the only [supported exchange](https://saveinbitcoin.com/exchanges/) at the moment.

Sign up for an account at [Bitstamp.net](https://bitstamp.net) and complete their verification process.

Log in to your account and take note of your Customer ID.

Create a Sub Account: you can name it SaveinBitcoin - or however you want.

### Security Measures

> **Those measures are critical**: by enabling all of them, you will make sure that no one, except you, will access your funds while they are on the exchange.

Basically you need to limit the permissions of the exchange API keys, and whitelist just your own wallet addresses for bitcoin withdrawals.

**I can'stress enough how important that is**: if you don'enable the Withdrawal Address Whitelist and don'limit the permission of the API to withdraw only to your whitelisted address, then anybody who can gain access to your API keys (for example an hacker, or an evil guy from the hosting company) can also withdraw your bitcoins to his own wallet.

So please, do both of the following:

*   Activate 2 Factor Authentication (2FA) for your account (under Account > Security).
*   Enable Withdrawal Address List (Withdrawal > Whitelist) and add your BTC wallet address - make sure to enable the API withdrawals option for this address.

Then make sure to set up the bitcoin withdrawal filter as you create the API key associated to your Main account (see below).

### API Keys

Now you need to create and activate two separate API keys, and take note of the Key and Secret code for each.

You can create API keys from Account > Security > API Access.

Below are listed the optimal settings for each API key.

#### API key associated to your Main account

Filters:

*   Add your whitelisted wallet to the Bitcoin Withdrawal Address Filter
*   Optionally add your server's IP address to the IP Address Filter

Permissions:

*   Account balance
*   User transactions
*   Transfer balance from main account
*   BTC withdrawal

#### API key associated to your Sub account

Suggestion: while creating this API key, take note of the sub account numeric ID that appears in the dropdown menu, as you will need it later.

Filters:

*   Optionally add your server's IP address to the IP Address Filter

Permissions:

*   Account balance
*   User transactions
*   Buy limit/marker order
*   Transfer to main account

Host the source code on a web server
------------------------------------

You'll have to be somehow familiar with how regular PHP/MySQL hosting works to complete this step, or ask someone who is tech-savvy to assist you.

[Download the source code](https://github.com/SaveinBitcoin/Save-in-Bitcoin/releases/latest/) and copy all the files to the root public folder of your server.

Create a new MySQL database, and import the database.sql file in there.

Configure SaveinBitcoin
-----------------------

Now open the config-sample.php file, and change the parameters according to your settings and preferences.

Here you will configure:

*   Your MySQL access credentials
*   Your API keys from the exchange
*   Your bitcoin wallet address
*   Your preferences for email alerts and notifications
*   Your email and SMTP settings

Once done, rename this file to "config.php" on your server.

Open the Database Manager (usually PhpMyAdmin) on your server, and import "database.sql".

Optionally, you can now configure an username and a password (that's useful if you want to enable the web reports).

Schedule the recurring execution
--------------------------------

While you may set up the cron job to be executed from within the hosting environment itself, it is best to use at least two external services to run it. Otherwise, if the server is down, for example, you risk not getting any notification at all.

You can sign up for free accounts at two external cron job services. I recommend [https://www.easycron.com/](https://www.easycron.com/) and [https://cron-job.org/en/](https://cron-job.org/en/)

Assuming that the public address for your server is for example https://mybitcoinsavings.myhosting.com/, then you need to instruct those cron services to request the URL https://mybitcoinsavings.myhosting.com/run.php at least once a day (once an hour is probably better; you won'really need anything more frequent than that).

You can visit that URL on your browser, to make sure everything is working as intended.

If you read "SaveinBitcoin was executed." at the end, it works.

Bank transfers
--------------

At this point, send your first deposit from your bank account to the exchange. I suggest to send a minimum amount for testing purposes.

Wait for it to arrive and get detected by SaveinBitcoin: if you had left the email notification preference enabled in the config file, you should receive an email from SaveinBitcoin informing you about that (and then other two emails should arrive: one informing you about the executed purchase and one about the withdrawal request).

If all went well, make a second deposit, again of a small amount.

If also that second deposit is received, detected, and executed, at this point you can assume everything is working as it is supposed to be; so it is safe to set up recurring deposits and/or send bigger amounts.

FAQs
====

[Read the Frequently Asked Questions and answers on the official site](https://saveinbitcoin.com/faqs/)

Roadmap
=======

[SaveinBitcoin project and development roadmap](https://saveinbitcoin.com/roadmap/)

Resources
=========

[SaveinBitcoin.com provides a list of useful resources](https://saveinbitcoin.com/resources/)

Credits
=======

The following resources were important to help developing SaveinBitcoin:

* [Ultimate MySQL wrapper class](https://github.com/GerHobbelt/ultimatemysql)
* [PHPMailer](https://github.com/PHPMailer/PHPMailer)
* [Blockchain.com](https://www.blockchain.com/) for providing an handy endpoint for querying the Bitcoin blockchain

Please donate
=============

This project was **published as a gift to the Bitcoin community**.

So far, SaveinBitcoin took me 24 hours for its developement, plus 18 hours to produce the documents, the website, and to carry on the project.

That's over 42 hours (and counting) of work I carried out mostly on weekends, **with love**.

> If you like this project and want to support its [further development](https://saveinbitcoin.com/roadmap/) (and also its author), please consider making a donation.


Bitcoin:
3CQgnFPtcChK7SqH6HzRhBTULZEpdhH3Kv

Ethereum:
0x211378C9DA55f309a87caE434a1733BA14746e96

Monero:
42jyXpmHkY61Ju43YNFfmQCQdqZ4rBMRGgkubRBBLcBy9X4tASAMFbMdXvcyg1crihSgktrRna8g3YjfZKP6FvLtG7EpBLN

> **Thank You!**