# Database

```mysql
DROP TABLE IF EXISTS `email_validation`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `friendfeed_username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `password` char(60) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'bcrypt hash',
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `clio_api_token` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email_validated` tinyint(1) DEFAULT '0',
  `account_validated` tinyint(1) DEFAULT '0',
  `freefeed_status` enum('undecided','in','out') COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`friendfeed_username`)
) ENGINE=InnoDB AUTO_INCREMENT=479 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `email_validation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `secret_link` char(64) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `secret_link` (`secret_link`),
  CONSTRAINT `email_validation_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=478 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
```

