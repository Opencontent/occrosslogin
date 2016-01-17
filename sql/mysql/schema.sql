CREATE TABLE IF NOT EXISTS `ezoctoken` (
  `user_id` int(11) NOT NULL,
  `time` int(11) NOT NULL,
  `session_id` varchar(32) NOT NULL,
  `token` varchar(32) NOT NULL,
  PRIMARY KEY (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

