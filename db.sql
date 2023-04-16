DROP DATABASE `test`;
CREATE DATABASE `test` CHARACTER SET utf8 COLLATE utf8_general_ci;

USE `test`;

DROP TABLE `users`;
CREATE TABLE `users` (
  `id` INT PRIMARY KEY AUTO_INCREMENT, 
  `uid` VARCHAR(5000) UNIQUE NOT NULL, 
  `name` VARCHAR(5000) NOT NULL DEFAULT ''
);

DROP TABLE `trips`;
CREATE TABLE `trips` (
  `id` INT PRIMARY KEY AUTO_INCREMENT, 
  `tid` INT NOT NULL DEFAULT 0, 
  `uid` VARCHAR(5000) NOT NULL DEFAULT 0, 
  `name` VARCHAR(5000) NOT NULL DEFAULT '', 
  `others` VARCHAR(5000) NOT NULL DEFAULT ''
);

DROP TABLE `expenses`;
CREATE TABLE `expenses` (
  `id` INT PRIMARY KEY AUTO_INCREMENT, 
  `trip_id` INT NOT NULL DEFAULT 0, 
  `type` VARCHAR(5000) NOT NULL DEFAULT '', 
  `amount` FLOAT NOT NULL DEFAULT 0.0, 
  `others` VARCHAR(5000) NOT NULL DEFAULT ''
);

INSERT INTO `users`(`uid`,`name`) VALUES ('0011223456', 'Souperman!');
SELECT * FROM `users`; SELECT * FROM `trips`; SELECT * FROM `expenses`;
