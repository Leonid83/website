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
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `email_validation` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`user_id` int(11) NOT NULL,
`secret_link` char(64) NOT NULL,
PRIMARY KEY (`id`),
FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
INDEX (`secret_link`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
```

