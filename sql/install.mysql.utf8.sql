CREATE TABLE IF NOT EXISTS `#__synchronization` (
  `type`     VARCHAR(255) NOT NULL DEFAULT '',
  `last_run` DATETIME     NOT NULL DEFAULT '0000-00-00 00:00:00',
  `params`   LONGTEXT     NOT NULL DEFAULT '',
  UNIQUE KEY `type` (`type`)
)