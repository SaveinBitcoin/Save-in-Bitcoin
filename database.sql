SET NAMES utf8;
SET time_zone = '+00:00';

DROP TABLE IF EXISTS `alerts`;
CREATE TABLE `alerts` (
  `alert` varchar(255) NOT NULL,
  `datetime` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `currentbalance`;
CREATE TABLE `currentbalance` (
  `account` varchar(255) NOT NULL,
  `currency` varchar(3) NOT NULL,
  `amount` decimal(16,8) NOT NULL,
  `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `account_currency` (`account`,`currency`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `deposits`;
CREATE TABLE `deposits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `datetime` datetime NOT NULL,
  `currency` varchar(3) NOT NULL,
  `amount` decimal(16,2) NOT NULL,
  `sent2subaccount` decimal(16,2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `datetime` (`datetime`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `fees`;
CREATE TABLE `fees` (
  `pair` varchar(255) NOT NULL,
  `fee` decimal(4,2) NOT NULL,
  UNIQUE KEY `pair` (`pair`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `trades`;
CREATE TABLE `trades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` varchar(255) NOT NULL,
  `datetime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `amount` decimal(16,2) NOT NULL,
  `price` decimal(16,2) NOT NULL,
  `pair` varchar(255) NOT NULL,
  `spend` decimal(16,2) NOT NULL,
  `bought` decimal(16,8) NOT NULL,
  `fees` decimal(8,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `walletlog`;
CREATE TABLE `walletlog` (
  `txid` varchar(255) NOT NULL,
  `height` int(11) NOT NULL,
  `datetime` datetime NOT NULL,
  `type` varchar(255) NOT NULL,
  `value` decimal(16,8) NOT NULL,
  `wallet` varchar(255) NOT NULL,
  UNIQUE KEY `txid` (`txid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `withdrawals`;
CREATE TABLE `withdrawals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `datetime` datetime NOT NULL,
  `currency` varchar(3) NOT NULL,
  `amount` decimal(16,8) NOT NULL,
  `sent2mainaccount` decimal(16,8) NOT NULL,
  `exchange_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `datetime` (`datetime`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;