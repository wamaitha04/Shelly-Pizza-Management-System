-- MySQL dump 10.13  Distrib 8.0.40, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: shellys_pizza
-- ------------------------------------------------------
-- Server version	5.5.5-10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Pizza');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ingredients`
--

DROP TABLE IF EXISTS `ingredients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ingredients` (
  `ingredient_id` int(11) NOT NULL AUTO_INCREMENT,
  `ingredient_name` varchar(100) NOT NULL,
  `unit` varchar(20) NOT NULL,
  `quantity_in_stock` decimal(10,2) NOT NULL DEFAULT 0.00,
  `reorder_level` decimal(10,2) NOT NULL DEFAULT 0.00,
  `last_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`ingredient_id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ingredients`
--

LOCK TABLES `ingredients` WRITE;
/*!40000 ALTER TABLE `ingredients` DISABLE KEYS */;
INSERT INTO `ingredients` VALUES (26,'Cheese','kg',5.83,2.00,'2026-09-12 11:30:41'),(27,'Dough','pieces',104.00,10.00,'2026-09-12 11:30:41'),(28,'Tomato Paste','liters',8.38,2.00,'2026-09-12 11:30:41'),(29,'Pizza Boxes','pieces',92.00,20.00,'2026-09-12 11:30:41'),(30,'Onions','kg',10.50,1.00,'2026-09-12 11:30:41');
/*!40000 ALTER TABLE `ingredients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory`
--

DROP TABLE IF EXISTS `inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory` (
  `inventory_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `reorder_level` int(11) NOT NULL DEFAULT 0,
  `last_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`inventory_id`),
  UNIQUE KEY `product_id` (`product_id`),
  CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory`
--

LOCK TABLES `inventory` WRITE;
/*!40000 ALTER TABLE `inventory` DISABLE KEYS */;
INSERT INTO `inventory` VALUES (1,1,19,5,'2026-09-07 16:00:11'),(2,2,30,5,'2026-08-19 20:44:30'),(3,3,20,5,'2026-08-19 20:44:30'),(4,4,10,5,'2026-08-31 12:13:48'),(8,5,9,5,'2026-09-01 22:09:11'),(9,6,10,5,'2026-09-02 16:58:14'),(10,7,10,5,'2026-09-07 15:57:11'),(11,8,14,5,'2026-09-07 16:05:08'),(12,9,0,5,'2026-09-12 09:25:06'),(13,10,0,5,'2026-09-12 11:29:08');
/*!40000 ALTER TABLE `inventory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) DEFAULT NULL,
  `product_name` varchar(100) NOT NULL,
  `size` varchar(30) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`product_id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,1,'Beefed-Up Desires','Medium',750.00),(2,1,'Feathered Fantasy','Medium',800.00),(3,1,'Tropic Temptation','Medium',850.00),(4,1,'margarita','Small',500.00),(5,1,'Beefed-Up Desires','Small',500.00),(6,1,'chicken pizza','Large',2000.00),(7,1,'chicken tikka','Medium',2000.00),(8,1,'hawaian','Medium',600.00),(9,1,'margarita','Medium',800.00),(10,1,'hawaian','Small',600.00);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recipe_items`
--

DROP TABLE IF EXISTS `recipe_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recipe_items` (
  `recipe_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `ingredient_id` int(11) NOT NULL,
  `quantity_required` decimal(10,3) NOT NULL,
  PRIMARY KEY (`recipe_item_id`),
  UNIQUE KEY `unique_product_ingredient` (`product_id`,`ingredient_id`),
  KEY `ingredient_id` (`ingredient_id`),
  CONSTRAINT `recipe_items_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  CONSTRAINT `recipe_items_ibfk_2` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`ingredient_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recipe_items`
--

LOCK TABLES `recipe_items` WRITE;
/*!40000 ALTER TABLE `recipe_items` DISABLE KEYS */;
INSERT INTO `recipe_items` VALUES (16,1,26,0.030),(17,1,27,1.000),(18,1,30,0.020),(19,1,29,1.000),(20,1,28,0.002),(26,8,26,7.000),(27,8,27,20.000),(28,8,30,2.000),(29,8,29,1.000),(30,8,28,0.300),(31,9,26,0.400),(32,9,27,1.000),(33,9,30,0.600),(34,9,29,1.000),(35,9,28,0.200),(36,5,26,0.020),(37,5,27,1.000),(38,5,30,0.040),(39,5,29,1.000),(40,5,28,0.040),(41,6,26,0.020),(42,6,27,1.000),(43,6,30,0.030),(44,6,29,1.000),(45,6,28,0.020),(46,7,26,0.020),(47,7,27,1.000),(48,7,30,0.040),(49,7,29,1.000),(50,7,28,0.020),(51,2,26,0.200),(52,2,27,1.000),(53,2,30,0.400),(54,2,29,1.000),(55,2,28,0.200),(56,10,26,0.020),(57,10,27,1.000),(58,10,30,0.400),(59,10,29,1.000),(60,10,28,0.020);
/*!40000 ALTER TABLE `recipe_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sale_details`
--

DROP TABLE IF EXISTS `sale_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sale_details` (
  `sale_detail_id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`sale_detail_id`),
  KEY `sale_id` (`sale_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `sale_details_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`sale_id`) ON DELETE CASCADE,
  CONSTRAINT `sale_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sale_details`
--

LOCK TABLES `sale_details` WRITE;
/*!40000 ALTER TABLE `sale_details` DISABLE KEYS */;
INSERT INTO `sale_details` VALUES (1,1,1,1,750.00,750.00),(2,2,1,1,750.00,750.00),(3,3,1,1,750.00,750.00),(4,4,1,1,750.00,750.00),(5,5,5,1,500.00,500.00);
/*!40000 ALTER TABLE `sale_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sale_items`
--

DROP TABLE IF EXISTS `sale_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sale_items` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_id` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`item_id`),
  KEY `sale_id` (`sale_id`),
  CONSTRAINT `sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`sale_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sale_items`
--

LOCK TABLES `sale_items` WRITE;
/*!40000 ALTER TABLE `sale_items` DISABLE KEYS */;
INSERT INTO `sale_items` VALUES (1,6,'Feathered Fantasy',1,800.00),(2,7,'Beefed-Up Desires',1,500.00),(3,1,'Beefed-Up Desires',1,750.00),(4,2,'Beefed-Up Desires',1,750.00),(5,3,'Beefed-Up Desires',1,750.00),(6,4,'Beefed-Up Desires',1,750.00),(7,5,'Beefed-Up Desires',1,500.00),(10,8,'Beefed-Up Desires',1,500.00),(11,9,'Beefed-Up Desires',1,500.00),(12,9,'Feathered Fantasy',2,800.00),(13,10,'chicken pizza',1,2000.00),(14,11,'chicken pizza',100,2000.00),(15,12,'chicken pizza',2147483647,2000.00),(16,13,'Beefed-Up Desires',1,750.00),(17,14,'Beefed-Up Desires',1,750.00),(18,15,'hawaian',1,600.00),(19,16,'hawaian',1,600.00),(20,17,'Beefed-Up Desires',1,750.00),(21,18,'hawaian',1,600.00);
/*!40000 ALTER TABLE `sale_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales`
--

DROP TABLE IF EXISTS `sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales` (
  `sale_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `sale_date` datetime DEFAULT current_timestamp(),
  `total_amount` decimal(10,2) NOT NULL,
  PRIMARY KEY (`sale_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales`
--

LOCK TABLES `sales` WRITE;
/*!40000 ALTER TABLE `sales` DISABLE KEYS */;
INSERT INTO `sales` VALUES (1,1,'2026-08-31 12:27:22',750.00),(2,1,'2026-09-01 10:14:19',750.00),(3,1,'2026-09-01 15:10:23',750.00),(4,1,'2026-09-01 15:23:53',750.00),(5,2,'2026-09-01 22:09:11',500.00),(6,1,'2026-09-01 22:48:03',800.00),(7,1,'2026-09-01 22:48:37',500.00),(8,1,'2026-09-02 07:13:09',500.00),(9,4,'2026-09-02 16:55:24',2100.00),(10,4,'2026-09-02 17:01:52',2000.00),(11,4,'2026-09-02 17:02:24',200000.00),(12,4,'2026-09-02 17:02:44',99999999.99),(13,4,'2026-09-07 08:20:30',750.00),(14,1,'2026-09-07 16:00:11',750.00),(15,1,'2026-09-07 16:01:36',600.00),(16,4,'2026-09-07 16:05:08',600.00),(17,8,'2026-09-12 10:13:59',750.00),(18,1,'2026-09-12 11:30:41',600.00);
/*!40000 ALTER TABLE `sales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(30) NOT NULL,
  `status` enum('pending','approved') NOT NULL DEFAULT 'approved',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `phone` (`phone`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin',NULL,NULL,'$2y$10$BfESEl/J7mgSFl5/vCTwRuCu5.rUTtqqCzu/Mvzl0oUIMmnMmU06K','owner','approved','2026-08-19 20:44:30'),(2,'mitchell',NULL,NULL,'$2y$10$F4DvK.7igSzmY3jj4CkM9OpnE5c3tzi57L5OdJHlJqAI.1X.M4RMW','cashier','approved','2026-09-01 22:08:31'),(3,'testadmin',NULL,NULL,'$2y$10$AL6Nl2xszyqhpVsrWP6pD.lHHTefZLhRF5VyJV4ns/xZcF6U9Rkee','owner','approved','2026-09-02 16:48:15'),(4,'testcashier',NULL,NULL,'$2y$10$5er1RhcqUilNG5nvoqTgleVaR3vUw6G822clCG1Rw9.kxqmwjG9hi','cashier','approved','2026-09-02 16:53:58'),(5,'maldini',NULL,NULL,'$2y$10$NA6IKNJCymWMQv4a4BZLNuX66urPnfhihYhiVoOPN99zvTeHV0Lfq','manager','approved','2026-09-07 07:52:58'),(6,'wamaitha','wamaithamitchell00@gmail.com','+254726221451','$2y$10$Od9rnVL6FFeeEkoICzGqo.RF31IxBAXTdYjBfq6WqNW06aMOCQoDK','cook','approved','2026-09-07 08:53:16'),(7,'Zeki','maldinizeki@gmail.com','0758477341','$2y$10$0NwNRxJlnPx7NCTiocBiEeZgrU5JwwCyWIXI1ipPtF6Jr7B14TQEa','cook','approved','2026-09-12 10:08:02'),(8,'Mukoya','edgeitproductions@gmail.com','0732456789','$2y$10$0tGKB.PshwuuaJGbabWyquBoxutZz.7DDH9pcmCSVZ6wYINUP/Mhm','cashier','approved','2026-09-12 10:13:36'),(9,'pru','prudencemukoya@gmail.com','0712348762','$2y$10$dx3G97H.lFusp8J3WqhSt.g32ai5xLQKVdM3aoYi8H6rIiDLPc1M2','cashier','approved','2026-09-12 10:55:36'),(10,'joy','Joymanase@gmail.com','0798654323','$2y$10$XuMzjoqZDMSq.mutimONCeS2xC5T6qcJwJpAQ/ciVxZXZbfOUO1qK','cashier','pending','2026-09-12 11:00:10');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-01  8:40:34
