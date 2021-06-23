-- Adminer 4.7.7 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `alternatives`;
CREATE TABLE `alternatives` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sequence_id` int(11) NOT NULL,
  `alternative_sequence_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `causal`;
CREATE TABLE `causal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cause_id` int(11) NOT NULL,
  `consequence_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `phrases`;
CREATE TABLE `phrases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `phrase` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `sequences`;
CREATE TABLE `sequences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `phrase_id` int(11) NOT NULL,
  `before_current_ending_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `sequences_equations`;
CREATE TABLE `sequences_equations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sequence_all_data_from_id` int(11) NOT NULL,
  `equate_to_record_id` int(11) NOT NULL,
  `hidden` bit(1) NOT NULL DEFAULT b'0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `tags`;
CREATE TABLE `tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sequence_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- 2021-06-23 15:09:48
