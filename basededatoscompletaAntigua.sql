CREATE DATABASE  IF NOT EXISTS `angelow` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `angelow`;
-- MySQL dump 10.13  Distrib 8.0.43, for Win64 (x86_64)
--
-- Host: localhost    Database: angelow
-- ------------------------------------------------------
-- Server version	9.4.0

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
-- Table structure for table `access_tokens`
--

DROP TABLE IF EXISTS `access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `access_tokens` (
  `id` int NOT NULL,
  `user_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime NOT NULL,
  `is_revoked` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `access_tokens`
--

LOCK TABLES `access_tokens` WRITE;
/*!40000 ALTER TABLE `access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_notification_dismissals`
--

DROP TABLE IF EXISTS `admin_notification_dismissals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_notification_dismissals` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `notification_key` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `dismissed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_admin_notification_key` (`admin_id`,`notification_key`),
  KEY `idx_notification_key` (`notification_key`),
  CONSTRAINT `fk_admin_dismissal_user` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_notification_dismissals`
--

LOCK TABLES `admin_notification_dismissals` WRITE;
/*!40000 ALTER TABLE `admin_notification_dismissals` DISABLE KEYS */;
/*!40000 ALTER TABLE `admin_notification_dismissals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `announcements`
--

DROP TABLE IF EXISTS `announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `announcements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` enum('top_bar','promo_banner') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `subtitle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `button_text` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `button_link` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `image` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `background_color` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '#000000',
  `text_color` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '#ffffff',
  `icon` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `priority` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcements`
--

LOCK TABLES `announcements` WRITE;
/*!40000 ALTER TABLE `announcements` DISABLE KEYS */;
INSERT INTO `announcements` VALUES (1,'top_bar','Envío Gratis','¡Envío gratis en compras superiores a $50.000! | 3 cuotas sin interés',NULL,NULL,NULL,NULL,'#000000','#ffffff','fa-truck',10,1,NULL,NULL,'2025-11-11 15:38:07','2025-11-11 15:43:03'),(2,'promo_banner','¡Oferta 3x2!','¡Compra 2 prendas y llévate la 3ra con 50% de descuento!','Válido hasta el 30 de junio o hasta agotar existencias','Aprovechar oferta','/tienda/tienda.php?promo=3x2',NULL,'#ff6b6b','#ffffff','fa-tags',5,1,NULL,NULL,'2025-11-11 15:38:07','2025-11-11 15:42:12');
/*!40000 ALTER TABLE `announcements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_categories`
--

DROP TABLE IF EXISTS `audit_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_categories` (
  `audit_id` int NOT NULL,
  `category_id` int DEFAULT NULL,
  `action_type` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `old_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `new_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `action_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_categories`
--

LOCK TABLES `audit_categories` WRITE;
/*!40000 ALTER TABLE `audit_categories` DISABLE KEYS */;
INSERT INTO `audit_categories` VALUES (1,9,'INSERT',NULL,'Electrónica','2025-09-23 14:06:46'),(2,9,'DELETE','Electrónica',NULL,'2025-11-01 22:30:20'),(3,5,'UPDATE','Accesorios','Accesorios','2025-11-01 22:31:00'),(4,1,'UPDATE','Vestidos','Vestidos','2025-11-01 22:31:44'),(5,1,'UPDATE','Vestidos','Vestidos','2025-11-01 22:35:33'),(6,5,'UPDATE','Accesorios','Accesorios','2025-11-01 22:35:33'),(7,5,'UPDATE','Accesorios','Accesorios','2025-11-01 22:36:00'),(8,1,'UPDATE','Vestidos','Vestidos','2025-11-01 22:36:13'),(9,2,'UPDATE','Conjuntos','Conjuntos','2025-11-01 22:38:02'),(10,3,'UPDATE','Pijamas','Pijamas','2025-11-01 23:15:46'),(11,4,'UPDATE','Ropa Deportiva','Ropa Deportiva','2025-11-01 23:17:09');
/*!40000 ALTER TABLE `audit_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_orders`
--

DROP TABLE IF EXISTS `audit_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `orden_id` int NOT NULL,
  `accion` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `usuario_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sql_usuario` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `detalles` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=149 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_orders`
--

LOCK TABLES `audit_orders` WRITE;
/*!40000 ALTER TABLE `audit_orders` DISABLE KEYS */;
INSERT INTO `audit_orders` VALUES (1,5,'INSERT','6861e06ddcf49','root@localhost','2025-09-23 14:05:22','Se creó la orden #TEST001 con total $0.00'),(2,6,'INSERT','6861e06ddcf49','root@localhost','2025-10-05 05:18:55','Se creó la orden #ORD20251005F8BADD con total $176000.00'),(3,7,'INSERT','6861e06ddcf49','root@localhost','2025-10-08 18:23:51','Se creó la orden #ORD2025100876BCAF con total $148000.00'),(4,8,'INSERT','6861e06ddcf49','root@localhost','2025-10-08 18:40:47','Se creó la orden #ORD20251008F47279 con total $183000.00'),(5,9,'INSERT','6861e06ddcf49','root@localhost','2025-10-08 20:53:21','Se creó la orden #ORD2025100817A4CE con total $148000.00'),(6,10,'INSERT','6861e06ddcf49','root@localhost','2025-10-08 21:17:18','Se creó la orden #ORD20251008E71B8B con total $218000.00'),(7,11,'INSERT','6861e06ddcf49','root@localhost','2025-10-09 13:49:15','Se creó la orden #ORD20251009BC0908 con total $183000.00'),(8,12,'INSERT','6861e06ddcf49','root@localhost','2025-10-09 13:59:05','Se creó la orden #ORD2025100997DEB3 con total $36000.00'),(9,13,'INSERT','6861e06ddcf49','root@localhost','2025-10-09 14:10:14','Se creó la orden #ORD202510096EC1B1 con total $43000.00'),(10,14,'INSERT','6861e06ddcf49','root@localhost','2025-10-09 14:35:21','Se creó la orden #ORD20251009985677 con total $43000.00'),(11,15,'INSERT','6861e06ddcf49','root@localhost','2025-10-09 16:57:04','Se creó la orden #ORD202510090BFA71 con total $43000.00'),(12,16,'INSERT','6861e06ddcf49','root@localhost','2025-10-09 17:04:00','Se creó la orden #ORD20251009037EF6 con total $43000.00'),(13,17,'INSERT','6861e06ddcf49','root@localhost','2025-10-09 17:36:32','Se creó la orden #ORD20251009058E42 con total $78000.00'),(14,18,'INSERT','6861e06ddcf49','root@localhost','2025-10-09 17:47:28','Se creó la orden #ORD202510090588A9 con total $43000.00'),(15,19,'INSERT','6861e06ddcf49','root@localhost','2025-10-09 17:53:32','Se creó la orden #ORD20251009C69ABB con total $43000.00'),(16,20,'INSERT','6861e06ddcf49','root@localhost','2025-10-09 17:58:35','Se creó la orden #ORD20251009B7E086 con total $358000.00'),(17,21,'INSERT','6861e06ddcf49','root@localhost','2025-10-09 18:07:49','Se creó la orden #ORD202510095B1EA9 con total $43000.00'),(18,22,'INSERT','6861e06ddcf49','root@localhost','2025-10-09 18:18:44','Se creó la orden #ORD202510094B58E8 con total $43000.00'),(19,23,'INSERT','6861e06ddcf49','root@localhost','2025-10-09 18:29:47','Se creó la orden #ORD20251009B85DC4 con total $78000.00'),(20,21,'UPDATE','6861e06ddcf49','root@localhost','2025-10-12 00:31:12','Orden actualizada. Estado: pending → processing. Total: $43000.00 → $43000.00'),(21,21,'UPDATE','6861e06ddcf49','root@localhost','2025-10-12 00:35:32','Orden actualizada. Estado: processing → shipped. Total: $43000.00 → $43000.00'),(22,13,'DELETE','6861e06ddcf49','root@localhost','2025-10-12 02:07:16','Se eliminó la orden #ORD202510096EC1B1 con total $43000.00'),(23,9,'DELETE','6861e06ddcf49','root@localhost','2025-10-12 02:09:14','Se eliminó la orden #ORD2025100817A4CE con total $148000.00'),(24,8,'DELETE','6861e06ddcf49','root@localhost','2025-10-12 02:09:20','Se eliminó la orden #ORD20251008F47279 con total $183000.00'),(25,6,'DELETE','6861e06ddcf49','root@localhost','2025-10-12 03:48:43','Se eliminó la orden #ORD20251005F8BADD con total $176000.00'),(26,5,'DELETE','6861e06ddcf49','root@localhost','2025-10-12 03:48:47','Se eliminó la orden #TEST001 con total $0.00'),(27,7,'DELETE','6861e06ddcf49','root@localhost','2025-10-12 03:48:50','Se eliminó la orden #ORD2025100876BCAF con total $148000.00'),(28,10,'DELETE','6861e06ddcf49','root@localhost','2025-10-12 03:48:54','Se eliminó la orden #ORD20251008E71B8B con total $218000.00'),(29,11,'DELETE','6861e06ddcf49','root@localhost','2025-10-12 03:48:59','Se eliminó la orden #ORD20251009BC0908 con total $183000.00'),(30,12,'DELETE','6861e06ddcf49','root@localhost','2025-10-12 03:56:49','Se eliminó la orden #ORD2025100997DEB3 con total $36000.00'),(31,14,'DELETE','6861e06ddcf49','root@localhost','2025-10-12 03:56:49','Se eliminó la orden #ORD20251009985677 con total $43000.00'),(39,15,'UPDATE','6861e06ddcf49','root@localhost','2025-10-12 20:07:14','Orden actualizada. Estado: pending → processing. Total: $43000.00 → $43000.00'),(40,16,'UPDATE','6861e06ddcf49','root@localhost','2025-10-12 20:07:14','Orden actualizada. Estado: pending → processing. Total: $43000.00 → $43000.00'),(41,17,'UPDATE','6861e06ddcf49','root@localhost','2025-10-12 20:07:14','Orden actualizada. Estado: pending → processing. Total: $78000.00 → $78000.00'),(42,15,'UPDATE','6861e06ddcf49','root@localhost','2025-10-12 20:08:30','Orden actualizada. Estado: processing → pending. Total: $43000.00 → $43000.00'),(43,15,'UPDATE','6861e06ddcf49','root@localhost','2025-10-12 20:08:30','Orden actualizada. Estado: pending → processing. Total: $43000.00 → $43000.00'),(44,17,'UPDATE','6861e06ddcf49','root@localhost','2025-10-12 20:08:57','Orden actualizada. Estado: processing → pending. Total: $78000.00 → $78000.00'),(45,16,'UPDATE','6861e06ddcf49','root@localhost','2025-10-12 20:08:57','Orden actualizada. Estado: processing → pending. Total: $43000.00 → $43000.00'),(46,15,'UPDATE','6861e06ddcf49','root@localhost','2025-10-12 20:08:57','Orden actualizada. Estado: processing → pending. Total: $43000.00 → $43000.00'),(47,17,'UPDATE','6861e06ddcf49','root@localhost','2025-10-12 20:23:52','Orden actualizada. Estado: pending → processing. Total: $78000.00 → $78000.00'),(48,16,'UPDATE','6861e06ddcf49','root@localhost','2025-10-12 20:23:52','Orden actualizada. Estado: pending → processing. Total: $43000.00 → $43000.00'),(49,15,'UPDATE','6861e06ddcf49','root@localhost','2025-10-12 20:23:52','Orden actualizada. Estado: pending → processing. Total: $43000.00 → $43000.00'),(50,15,'DELETE','6861e06ddcf49','root@localhost','2025-10-12 20:24:01','Se eliminó la orden #ORD202510090BFA71 con total $43000.00'),(51,16,'DELETE','6861e06ddcf49','root@localhost','2025-10-12 20:24:01','Se eliminó la orden #ORD20251009037EF6 con total $43000.00'),(52,17,'DELETE','6861e06ddcf49','root@localhost','2025-10-12 20:24:01','Se eliminó la orden #ORD20251009058E42 con total $78000.00'),(53,24,'INSERT','6861e06ddcf49','root@localhost','2025-10-12 22:00:32','Se creó la orden #TEST-20251012170032 con total $100.00'),(54,24,'UPDATE','6861e06ddcf49','root@localhost','2025-10-12 22:00:32','Orden actualizada. Estado: processing → shipped. Total: $100.00 → $100.00'),(55,25,'INSERT','6861e06ddcf49','root@localhost','2025-10-12 22:04:35','Se creó la orden #TEST-20251012170435 con total $100.00'),(56,25,'UPDATE','6861e06ddcf49','root@localhost','2025-10-12 22:04:35','Orden actualizada. Estado: processing → shipped. Total: $100.00 → $100.00'),(57,25,'UPDATE','6861e06ddcf49','root@localhost','2025-10-12 22:04:35','Orden actualizada. Estado: shipped → delivered. Total: $100.00 → $100.00'),(58,23,'UPDATE','6861e06ddcf49','root@localhost','2025-10-12 22:10:18','Orden actualizada. Estado: pending → shipped. Total: $78000.00 → $78000.00'),(59,26,'INSERT','6861e06ddcf49','root@localhost','2025-10-12 22:19:34','Se creó la orden #ORD-20251012171934 con total $100.00'),(60,18,'DELETE','6861e06ddcf49','root@localhost','2025-10-13 01:56:39','Se eliminó la orden #ORD202510090588A9 con total $43000.00'),(61,19,'DELETE','6861e06ddcf49','root@localhost','2025-10-13 01:56:39','Se eliminó la orden #ORD20251009C69ABB con total $43000.00'),(62,20,'DELETE','6861e06ddcf49','root@localhost','2025-10-13 01:56:39','Se eliminó la orden #ORD20251009B7E086 con total $358000.00'),(63,21,'DELETE','6861e06ddcf49','root@localhost','2025-10-13 01:56:39','Se eliminó la orden #ORD202510095B1EA9 con total $43000.00'),(64,22,'DELETE','6861e06ddcf49','root@localhost','2025-10-13 01:56:39','Se eliminó la orden #ORD202510094B58E8 con total $43000.00'),(65,23,'DELETE','6861e06ddcf49','root@localhost','2025-10-13 01:56:39','Se eliminó la orden #ORD20251009B85DC4 con total $78000.00'),(66,24,'DELETE','6861e06ddcf49','root@localhost','2025-10-13 01:56:39','Se eliminó la orden #TEST-20251012170032 con total $100.00'),(67,25,'DELETE','6861e06ddcf49','root@localhost','2025-10-13 01:56:39','Se eliminó la orden #TEST-20251012170435 con total $100.00'),(68,26,'DELETE','6861e06ddcf49','root@localhost','2025-10-13 01:56:39','Se eliminó la orden #ORD-20251012171934 con total $100.00'),(69,27,'INSERT','6861e06ddcf49','root@localhost','2025-10-13 15:51:32','Se creó la orden #ORD202510134E5C9A con total $113000.00'),(70,27,'UPDATE','6861e06ddcf49','root@localhost','2025-10-13 15:53:54','Orden actualizada. Estado: pending → shipped. Total: $113000.00 → $113000.00'),(71,27,'UPDATE','6861e06ddcf49','root@localhost','2025-10-13 16:28:43','Orden actualizada. Estado: shipped → pending. Total: $113000.00 → $113000.00'),(72,27,'DELETE','6861e06ddcf49','root@localhost','2025-10-13 16:28:52','Se eliminó la orden #ORD202510134E5C9A con total $113000.00'),(73,28,'INSERT','6861e06ddcf49','root@localhost','2025-10-13 16:31:51','Se creó la orden #ORD202510137D261E con total $148000.00'),(74,28,'DELETE','6861e06ddcf49','root@localhost','2025-10-13 16:41:08','Se eliminó la orden #ORD202510137D261E con total $148000.00'),(75,29,'INSERT','6861e06ddcf49','root@localhost','2025-10-13 16:44:46','Se creó la orden #ORD20251013E7E31E con total $43000.00'),(76,29,'UPDATE','6861e06ddcf49','root@localhost','2025-10-13 16:46:45','Orden actualizada. Estado: pending → shipped. Total: $43000.00 → $43000.00'),(77,29,'DELETE','6861e06ddcf49','root@localhost','2025-10-13 16:54:18','Se eliminó la orden #ORD20251013E7E31E con total $43000.00'),(78,30,'INSERT','6861e06ddcf49','root@localhost','2025-10-13 16:58:03','Se creó la orden #ORD20251013B49894 con total $43000.00'),(79,30,'UPDATE','6861e06ddcf49','root@localhost','2025-10-13 17:08:17','Orden actualizada. Estado: pending → shipped. Total: $43000.00 → $43000.00'),(80,30,'DELETE','6861e06ddcf49','root@localhost','2025-11-12 14:17:13','Se eliminó la orden #ORD20251013B49894 con total $43000.00'),(81,1,'INSERT','6861e06ddcf49','root@localhost','2025-11-16 03:10:11','Se creó la orden #TEST37D9B8 con total $45000.00'),(82,2,'INSERT','6861e06ddcf49','root@localhost','2025-11-16 03:17:00','Se creó la orden #ORD20251115C9DBC9 con total $36000.00'),(83,3,'INSERT','6861e06ddcf49','root@localhost','2025-11-16 14:09:59','Se creó la orden #ORD2025111677ADAB con total $35000.00'),(84,4,'INSERT','6861e06ddcf49','root@localhost','2025-11-16 14:12:17','Se creó la orden #ORD20251116134F34 con total $35000.00'),(85,5,'INSERT','6861e06ddcf49','root@localhost','2025-11-16 14:16:36','Se creó la orden #ORD20251116448A7E con total $35000.00'),(86,6,'INSERT','6861e06ddcf49','root@localhost','2025-11-16 14:46:56','Se creó la orden #ORD202511160CB9A1 con total $35000.00'),(87,7,'INSERT','6861e06ddcf49','root@localhost','2025-11-16 14:54:06','Se creó la orden #ORD20251116EAA8AB con total $35000.00'),(88,7,'UPDATE','6861e06ddcf49','root@localhost','2025-11-16 15:55:10','Orden actualizada. Estado: pending → processing. Total: $35000.00 → $35000.00'),(89,7,'UPDATE','6861e06ddcf49','root@localhost','2025-11-16 16:49:52','Orden actualizada. Estado: processing → shipped. Total: $35000.00 → $35000.00'),(90,8,'INSERT','6861e06ddcf49','root@localhost','2025-11-16 16:58:11','Se creó la orden #ORD202511163AF156 con total $43000.00'),(91,7,'UPDATE','6861e06ddcf49','root@localhost','2025-11-16 17:13:02','Orden actualizada. Estado: shipped → delivered. Total: $35000.00 → $35000.00'),(92,7,'UPDATE','6861e06ddcf49','root@localhost','2025-11-16 17:13:24','Orden actualizada. Estado: delivered → shipped. Total: $35000.00 → $35000.00'),(93,7,'UPDATE','6861e06ddcf49','root@localhost','2025-11-16 17:13:38','Orden actualizada. Estado: shipped → delivered. Total: $35000.00 → $35000.00'),(94,7,'UPDATE','6861e06ddcf49','root@localhost','2025-11-16 17:16:59','Orden actualizada. Estado: delivered → shipped. Total: $35000.00 → $35000.00'),(95,7,'UPDATE','6861e06ddcf49','root@localhost','2025-11-16 17:17:05','Orden actualizada. Estado: shipped → delivered. Total: $35000.00 → $35000.00'),(96,7,'UPDATE','6861e06ddcf49','root@localhost','2025-11-16 17:21:58','Orden actualizada. Estado: delivered → shipped. Total: $35000.00 → $35000.00'),(97,7,'UPDATE','6861e06ddcf49','root@localhost','2025-11-16 17:22:07','Orden actualizada. Estado: shipped → delivered. Total: $35000.00 → $35000.00'),(98,7,'UPDATE','6861e06ddcf49','root@localhost','2025-11-16 17:28:18','Orden actualizada. Estado: delivered → shipped. Total: $35000.00 → $35000.00'),(99,7,'UPDATE','6861e06ddcf49','root@localhost','2025-11-16 17:28:24','Orden actualizada. Estado: shipped → delivered. Total: $35000.00 → $35000.00'),(100,7,'UPDATE','6861e06ddcf49','root@localhost','2025-11-16 17:33:16','Orden actualizada. Estado: delivered → shipped. Total: $35000.00 → $35000.00'),(101,7,'UPDATE','6861e06ddcf49','root@localhost','2025-11-16 17:33:22','Orden actualizada. Estado: shipped → delivered. Total: $35000.00 → $35000.00'),(102,7,'UPDATE','6861e06ddcf49','root@localhost','2025-11-16 17:36:17','Orden actualizada. Estado: delivered → shipped. Total: $35000.00 → $35000.00'),(103,7,'UPDATE','6861e06ddcf49','root@localhost','2025-11-16 17:36:22','Orden actualizada. Estado: shipped → delivered. Total: $35000.00 → $35000.00'),(104,7,'UPDATE','6861e06ddcf49','root@localhost','2025-11-16 17:42:53','Orden actualizada. Estado: delivered → shipped. Total: $35000.00 → $35000.00'),(105,7,'UPDATE','6861e06ddcf49','root@localhost','2025-11-16 17:43:00','Orden actualizada. Estado: shipped → delivered. Total: $35000.00 → $35000.00'),(106,7,'UPDATE','6861e06ddcf49','root@localhost','2025-11-16 17:45:57','Orden actualizada. Estado: delivered → shipped. Total: $35000.00 → $35000.00'),(107,7,'UPDATE','6861e06ddcf49','root@localhost','2025-11-16 17:46:22','Orden actualizada. Estado: shipped → delivered. Total: $35000.00 → $35000.00'),(108,7,'UPDATE','6861e06ddcf49','root@localhost','2025-11-16 17:48:44','Orden actualizada. Estado: delivered → shipped. Total: $35000.00 → $35000.00'),(109,7,'UPDATE','6861e06ddcf49','root@localhost','2025-11-16 17:48:49','Orden actualizada. Estado: shipped → delivered. Total: $35000.00 → $35000.00'),(110,7,'UPDATE','6861e06ddcf49','root@localhost','2025-11-16 17:49:19','Orden actualizada. Estado: delivered → shipped. Total: $35000.00 → $35000.00'),(111,7,'UPDATE','6861e06ddcf49','root@localhost','2025-11-16 17:49:24','Orden actualizada. Estado: shipped → delivered. Total: $35000.00 → $35000.00'),(112,7,'UPDATE','6861e06ddcf49','root@localhost','2025-11-16 17:50:15','Orden actualizada. Estado: delivered → shipped. Total: $35000.00 → $35000.00'),(113,7,'UPDATE','6861e06ddcf49','root@localhost','2025-11-16 17:50:21','Orden actualizada. Estado: shipped → delivered. Total: $35000.00 → $35000.00'),(114,7,'UPDATE','6861e06ddcf49','root@localhost','2025-11-16 17:51:37','Orden actualizada. Estado: delivered → shipped. Total: $35000.00 → $35000.00'),(115,7,'UPDATE','6861e06ddcf49','root@localhost','2025-11-16 17:51:42','Orden actualizada. Estado: shipped → delivered. Total: $35000.00 → $35000.00'),(116,7,'UPDATE','6861e06ddcf49','root@localhost','2025-11-16 17:55:36','Orden actualizada. Estado: delivered → shipped. Total: $35000.00 → $35000.00'),(117,7,'UPDATE','6861e06ddcf49','root@localhost','2025-11-16 17:55:41','Orden actualizada. Estado: shipped → delivered. Total: $35000.00 → $35000.00'),(118,7,'UPDATE','6861e06ddcf49','root@localhost','2025-11-16 18:02:11','Orden actualizada. Estado: delivered → shipped. Total: $35000.00 → $35000.00'),(119,8,'UPDATE','6861e06ddcf49','root@localhost','2025-11-16 23:53:06','Orden actualizada. Estado: pending → cancelled. Total: $43000.00 → $43000.00'),(120,8,'UPDATE','6861e06ddcf49','root@localhost','2025-11-17 01:01:57','Orden actualizada. Estado: cancelled → shipped. Total: $43000.00 → $43000.00'),(121,8,'UPDATE','6861e06ddcf49','root@localhost','2025-11-17 01:02:07','Orden actualizada. Estado: shipped → cancelled. Total: $43000.00 → $43000.00'),(122,8,'UPDATE','6861e06ddcf49','root@localhost','2025-11-17 01:02:51','Orden actualizada. Estado: cancelled → shipped. Total: $43000.00 → $43000.00'),(123,8,'UPDATE','6861e06ddcf49','root@localhost','2025-11-17 01:03:03','Orden actualizada. Estado: shipped → cancelled. Total: $43000.00 → $43000.00'),(124,8,'UPDATE','6861e06ddcf49','root@localhost','2025-11-17 01:03:54','Orden actualizada. Estado: cancelled → shipped. Total: $43000.00 → $43000.00'),(125,8,'UPDATE','6861e06ddcf49','root@localhost','2025-11-17 01:12:56','Orden actualizada. Estado: shipped → cancelled. Total: $43000.00 → $43000.00'),(126,8,'UPDATE','6861e06ddcf49','root@localhost','2025-11-17 03:00:43','Orden actualizada. Estado: cancelled → refunded. Total: $43000.00 → $43000.00'),(127,8,'UPDATE','6861e06ddcf49','root@localhost','2025-11-17 03:04:07','Orden actualizada. Estado: refunded → processing. Total: $43000.00 → $43000.00'),(128,8,'UPDATE','6861e06ddcf49','root@localhost','2025-11-17 03:04:30','Orden actualizada. Estado: processing → refunded. Total: $43000.00 → $43000.00'),(129,8,'UPDATE','6861e06ddcf49','root@localhost','2025-11-17 03:07:02','Orden actualizada. Estado: refunded → cancelled. Total: $43000.00 → $43000.00'),(130,8,'UPDATE','6861e06ddcf49','root@localhost','2025-11-17 03:07:14','Orden actualizada. Estado: cancelled → refunded. Total: $43000.00 → $43000.00'),(131,8,'UPDATE','6861e06ddcf49','root@localhost','2025-11-17 03:09:33','Orden actualizada. Estado: refunded → cancelled. Total: $43000.00 → $43000.00'),(132,8,'UPDATE','6861e06ddcf49','root@localhost','2025-11-17 03:09:43','Orden actualizada. Estado: cancelled → refunded. Total: $43000.00 → $43000.00'),(133,8,'UPDATE','6861e06ddcf49','root@localhost','2025-11-17 03:12:09','Orden actualizada. Estado: refunded → delivered. Total: $43000.00 → $43000.00'),(134,8,'UPDATE','6861e06ddcf49','root@localhost','2025-11-17 03:13:09','Orden actualizada. Estado: delivered → cancelled. Total: $43000.00 → $43000.00'),(135,8,'UPDATE','6861e06ddcf49','root@localhost','2025-11-17 03:13:17','Orden actualizada. Estado: cancelled → refunded. Total: $43000.00 → $43000.00'),(136,8,'UPDATE','6861e06ddcf49','root@localhost','2025-11-17 03:21:19','Orden actualizada. Estado: refunded → delivered. Total: $43000.00 → $43000.00'),(137,8,'UPDATE','6861e06ddcf49','root@localhost','2025-11-17 03:21:36','Orden actualizada. Estado: delivered → refunded. Total: $43000.00 → $43000.00'),(138,8,'UPDATE','6861e06ddcf49','root@localhost','2025-11-17 03:21:55','Orden actualizada. Estado: refunded → cancelled. Total: $43000.00 → $43000.00'),(139,8,'UPDATE','6861e06ddcf49','root@localhost','2025-11-17 03:22:09','Orden actualizada. Estado: cancelled → refunded. Total: $43000.00 → $43000.00'),(140,8,'UPDATE','6861e06ddcf49','root@localhost','2025-11-17 03:23:57','Orden actualizada. Estado: refunded → cancelled. Total: $43000.00 → $43000.00'),(141,8,'UPDATE','6861e06ddcf49','root@localhost','2025-11-17 03:24:11','Orden actualizada. Estado: cancelled → refunded. Total: $43000.00 → $43000.00'),(142,8,'UPDATE','6861e06ddcf49','root@localhost','2025-11-17 03:33:36','Orden actualizada. Estado: refunded → cancelled. Total: $43000.00 → $43000.00'),(143,8,'UPDATE','6861e06ddcf49','root@localhost','2025-11-17 03:37:00','Orden actualizada. Estado: cancelled → refunded. Total: $43000.00 → $43000.00'),(144,8,'UPDATE','6861e06ddcf49','root@localhost','2025-11-17 03:37:40','Orden actualizada. Estado: refunded → cancelled. Total: $43000.00 → $43000.00'),(145,8,'UPDATE','6861e06ddcf49','root@localhost','2025-11-17 03:38:38','Orden actualizada. Estado: cancelled → delivered. Total: $43000.00 → $43000.00'),(146,6,'UPDATE','6861e06ddcf49','root@localhost','2025-11-17 04:11:45','Orden actualizada. Estado: pending → cancelled. Total: $35000.00 → $35000.00'),(147,6,'UPDATE','6861e06ddcf49','root@localhost','2025-11-17 04:12:38','Orden actualizada. Estado: cancelled → refunded. Total: $35000.00 → $35000.00'),(148,5,'UPDATE','6861e06ddcf49','root@localhost','2025-11-17 18:04:02','Orden actualizada. Estado: pending → delivered. Total: $35000.00 → $35000.00');
/*!40000 ALTER TABLE `audit_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_users`
--

DROP TABLE IF EXISTS `audit_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `accion` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `usuario_modificador` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sql_usuario` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `detalles` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_users`
--

LOCK TABLES `audit_users` WRITE;
/*!40000 ALTER TABLE `audit_users` DISABLE KEYS */;
INSERT INTO `audit_users` VALUES (1,'TEST_USER_001','INSERT','TEST_USER_001','root@localhost','2025-09-23 14:05:33','Se creó el usuario: Usuario Prueba (prueba@ejemplo.com). Rol: customer'),(2,'TEST_USER_001','UPDATE','TEST_USER_001','root@localhost','2025-09-23 14:05:33','Usuario actualizado. Cambios: Nombre: Usuario Prueba → Usuario Modificado. Rol: customer → admin. '),(3,'TEST_USER_001','DELETE','TEST_USER_001','root@localhost','2025-09-23 14:05:33','Se eliminó el usuario: Usuario Modificado (prueba@ejemplo.com). Rol: admin'),(4,'68d315bcd96ef','INSERT','68d315bcd96ef','root@localhost','2025-09-23 21:48:45','Se creó el usuario: andres (andres90@gmail.com). Rol: customer'),(5,'68d315cf194ba','INSERT','68d315cf194ba','root@localhost','2025-09-23 21:49:03','Se creó el usuario: Braian Andres Oquendo Durango (tracongames2@gmail.com). Rol: customer'),(6,'6862b7448112f','UPDATE','6862b7448112f','root@localhost','2025-11-07 11:31:34','Usuario actualizado. Cambios: Rol: delivery → customer. Bloqueo: 0 → 1. '),(7,'691b491806be8','INSERT','691b491806be8','root@localhost','2025-11-17 16:11:04','Se creó el usuario: andres (andres80@gmail.com). Rol: customer');
/*!40000 ALTER TABLE `audit_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bank_account_config`
--

DROP TABLE IF EXISTS `bank_account_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bank_account_config` (
  `id` int NOT NULL AUTO_INCREMENT,
  `bank_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `account_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `account_type` enum('ahorros','corriente') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `account_holder` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `identification_type` enum('cc','ce','nit') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'cc',
  `identification_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_by` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank_account_config`
--

LOCK TABLES `bank_account_config` WRITE;
/*!40000 ALTER TABLE `bank_account_config` DISABLE KEYS */;
INSERT INTO `bank_account_config` VALUES (1,'040','13311','ahorros','ffff','cc','1222224242','braianoquendurango@gmail.com','3013636902',0,'6860007924a6a','2025-10-04 21:26:59','2025-10-04 21:27:46'),(2,'031','13311','ahorros','Braian Oquendo','cc','1023526011','braianoquendurango@gmail.com','3013636902',1,'6860007924a6a','2025-10-04 21:28:27','2025-11-17 14:36:07');
/*!40000 ALTER TABLE `bank_account_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bulk_discount_rules`
--

DROP TABLE IF EXISTS `bulk_discount_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bulk_discount_rules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `min_quantity` int NOT NULL,
  `max_quantity` int DEFAULT NULL,
  `discount_percentage` decimal(5,2) NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bulk_discount_rules`
--

LOCK TABLES `bulk_discount_rules` WRITE;
/*!40000 ALTER TABLE `bulk_discount_rules` DISABLE KEYS */;
INSERT INTO `bulk_discount_rules` VALUES (1,30,50,10.00,1,'2025-07-27 15:50:11','2025-07-27 15:50:11'),(2,51,NULL,20.00,1,'2025-07-27 15:50:38','2025-07-27 15:50:46');
/*!40000 ALTER TABLE `bulk_discount_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cart_items`
--

DROP TABLE IF EXISTS `cart_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cart_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cart_id` int NOT NULL,
  `product_id` int NOT NULL,
  `color_variant_id` int DEFAULT NULL,
  `size_variant_id` int DEFAULT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_cart_items_cart` (`cart_id`),
  KEY `fk_cart_items_product` (`product_id`),
  KEY `fk_cart_items_color_variant` (`color_variant_id`),
  KEY `fk_cart_items_size_variant` (`size_variant_id`),
  CONSTRAINT `fk_cart_items_cart` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cart_items_color_variant` FOREIGN KEY (`color_variant_id`) REFERENCES `product_color_variants` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_cart_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cart_items_size_variant` FOREIGN KEY (`size_variant_id`) REFERENCES `product_size_variants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart_items`
--

LOCK TABLES `cart_items` WRITE;
/*!40000 ALTER TABLE `cart_items` DISABLE KEYS */;
INSERT INTO `cart_items` VALUES (1,7,71,NULL,NULL,1,'2025-11-15 21:38:07','2025-11-18 22:21:02'),(11,3,71,25,34,1,'2025-11-18 00:15:03','2025-11-18 00:15:03'),(12,8,71,25,34,1,'2025-11-19 10:29:23','2025-11-19 10:40:55');
/*!40000 ALTER TABLE `cart_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `carts`
--

DROP TABLE IF EXISTS `carts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `carts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `session_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `carts`
--

LOCK TABLES `carts` WRITE;
/*!40000 ALTER TABLE `carts` DISABLE KEYS */;
INSERT INTO `carts` VALUES (3,'6861e06ddcf49',NULL,'2025-06-29 19:55:24','2025-06-29 19:55:24'),(4,'68d315cf194ba',NULL,'2025-09-23 16:53:53','2025-09-23 16:53:53'),(5,NULL,'cpa8ar35bm1p3oi604d8b1qvfq','2025-10-12 22:18:09','2025-10-12 22:26:40'),(6,NULL,'6qlsafl3kmsl6kk0h8a37ki7rm','2025-11-11 22:20:11','2025-11-11 22:20:11'),(7,NULL,'39deb53f9e818ed52b6e26fa43dd30ca','2025-11-15 21:38:07','2025-11-15 21:38:07'),(8,NULL,'vpc2rkjmo7m24t3am1ivc37tgu','2025-11-19 10:29:23','2025-11-19 10:29:23');
/*!40000 ALTER TABLE `carts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `parent_id` int DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Vestidos','vestidos','Vestidos infantiles para ocasiones especiales','uploads/categories/category_1762036573.jpeg',NULL,1,'2025-06-21 21:11:42','2025-11-01 17:36:13'),(2,'Conjuntos','conjuntos','Conjuntos de ropa coordinados','uploads/categories/category_1762036682.jpg',NULL,1,'2025-06-21 21:11:42','2025-11-01 17:38:02'),(3,'Pijamas','pijamas','Pijamas y ropa para dormir','uploads/categories/category_1762038946.webp',NULL,1,'2025-06-21 21:11:42','2025-11-01 18:15:46'),(4,'Ropa Deportiva','ropa-deportiva','Ropa para actividades físicas','uploads/categories/category_1762039029.webp',NULL,1,'2025-06-21 21:11:42','2025-11-01 18:17:09'),(5,'Accesorios','accesorios','Complementos y accesorios infantiles','uploads/categories/category_1762036560.jpg',NULL,1,'2025-06-21 21:11:42','2025-11-01 17:36:00'),(6,'Ropa Casual','ropa-casual','Ropa informal para el día a día',NULL,NULL,1,'2025-06-21 21:11:42','2025-06-21 21:11:42'),(7,'Ropa Formal','ropa-formal','Ropa para eventos especiales',NULL,NULL,1,'2025-06-21 21:11:42','2025-06-21 21:11:42'),(8,'Ropa de Baño','ropa-de-bano','Trajes de baño y ropa playera',NULL,NULL,1,'2025-06-21 21:11:42','2025-06-21 21:11:42');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_categories_insert` AFTER INSERT ON `categories` FOR EACH ROW INSERT INTO audit_categories(category_id, action_type, new_name)
VALUES (NEW.id, 'INSERT', NEW.name) */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_categories_update` AFTER UPDATE ON `categories` FOR EACH ROW INSERT INTO audit_categories(category_id, action_type, old_name, new_name)
VALUES (OLD.id, 'UPDATE', OLD.name, NEW.name) */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_categories_delete` AFTER DELETE ON `categories` FOR EACH ROW INSERT INTO audit_categories(category_id, action_type, old_name)
VALUES (OLD.id, 'DELETE', OLD.name) */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `collections`
--

DROP TABLE IF EXISTS `collections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `collections` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `launch_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `collections`
--

LOCK TABLES `collections` WRITE;
/*!40000 ALTER TABLE `collections` DISABLE KEYS */;
INSERT INTO `collections` VALUES (1,'Verano Mágico','verano-magico','Colección de verano con colores vibrantes y diseños frescos','uploads/collections/collection_17620386951843.webp','2025-05-01',1,'2025-06-29 00:15:58','2025-11-01 18:11:35'),(2,'Aventura Infantil','aventura-infantil','Ropa cómoda y resistente para pequeños exploradores','uploads/collections/collection_17620387744925.jpg','2025-04-15',1,'2025-06-29 00:15:58','2025-11-01 18:12:54'),(3,'Dulces Sueños','dulces-suenos','Pijamas y ropa de dormir ultra suaves',NULL,'2025-03-20',1,'2025-06-29 00:15:58','2025-06-29 00:15:58'),(4,'Colección Clásica','coleccion-clasica','Diseños atemporales para ocasiones especiales',NULL,'2025-01-10',1,'2025-06-29 00:15:58','2025-06-29 00:15:58'),(5,'Mini Trendsetters','mini-trendsetters','Las últimas tendencias en moda infantil','uploads/collections/collection_17620386345155.jpg','2025-06-01',1,'2025-06-29 00:15:58','2025-11-01 18:10:34');
/*!40000 ALTER TABLE `collections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `colombian_banks`
--

DROP TABLE IF EXISTS `colombian_banks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `colombian_banks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `bank_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `bank_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `colombian_banks`
--

LOCK TABLES `colombian_banks` WRITE;
/*!40000 ALTER TABLE `colombian_banks` DISABLE KEYS */;
INSERT INTO `colombian_banks` VALUES (1,'001','Banco de Bogotá',1),(2,'002','Banco Popular',1),(3,'006','Banco Santander',1),(4,'007','BBVA Colombia',1),(5,'009','Citibank',1),(6,'012','Banco GNB Sudameris',1),(7,'013','Banco AV Villas',1),(8,'014','Banco de Occidente',1),(9,'019','Bancoomeva',1),(10,'023','Banco Itaú',1),(11,'031','Bancolombia',1),(12,'032','Banco Caja Social',1),(13,'040','Banco Agrario de Colombia',1),(14,'051','Bancamía',1),(15,'052','Banco WWB',1),(16,'053','Banco Falabella',1),(17,'054','Banco Pichincha',1),(18,'058','Banco ProCredit',1),(19,'059','Banco Mundo Mujer',1),(20,'060','Banco Finandina',1),(21,'061','Bancoomeva S.A.',1),(22,'062','Banco Davivienda',1),(23,'063','Banco Cooperativo Coopcentral',1),(24,'065','Banco Santander',1),(25,'101','Nequi',1),(26,'102','Daviplata',1),(27,'103','Movii',1);
/*!40000 ALTER TABLE `colombian_banks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `colors`
--

DROP TABLE IF EXISTS `colors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `colors` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `hex_code` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `colors`
--

LOCK TABLES `colors` WRITE;
/*!40000 ALTER TABLE `colors` DISABLE KEYS */;
INSERT INTO `colors` VALUES (1,'Blanco','#FFFFFF',1,'2025-06-21 19:10:34'),(2,'Negro','#000000',1,'2025-06-21 19:10:34'),(3,'Rojo','#FF0000',1,'2025-06-21 19:10:34'),(4,'Azul','#0000FF',1,'2025-06-21 19:10:34'),(5,'Azul Marino','#000080',1,'2025-06-21 19:10:34'),(6,'Azul Cielo','#87CEEB',1,'2025-06-21 19:10:34'),(7,'Rosado','#FFC0CB',1,'2025-06-21 19:10:34'),(8,'Rosado Pastel','#FFD1DC',1,'2025-06-21 19:10:34'),(9,'Morado','#800080',1,'2025-06-21 19:10:34'),(10,'Lila','#C8A2C8',1,'2025-06-21 19:10:34'),(11,'Amarillo','#FFFF00',1,'2025-06-21 19:10:34'),(12,'Amarillo Pastel','#FFFACD',1,'2025-06-21 19:10:34'),(13,'Verde','#008000',1,'2025-06-21 19:10:34'),(14,'Verde Mentha','#98FF98',1,'2025-06-21 19:10:34'),(15,'Naranja','#FFA500',1,'2025-06-21 19:10:34'),(16,'Melón','#FDBCB4',1,'2025-06-21 19:10:34'),(17,'Gris','#808080',1,'2025-06-21 19:10:34'),(18,'Beige','#F5F5DC',1,'2025-06-21 19:10:34'),(19,'Café','#A52A2A',1,'2025-06-21 19:10:34'),(20,'Estampado',NULL,1,'2025-06-21 19:10:34');
/*!40000 ALTER TABLE `colors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `discount_code_products`
--

DROP TABLE IF EXISTS `discount_code_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `discount_code_products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `discount_code_id` int NOT NULL,
  `product_id` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_discount_code_products_code` (`discount_code_id`),
  KEY `fk_discount_code_products_product` (`product_id`),
  CONSTRAINT `fk_discount_code_products_code` FOREIGN KEY (`discount_code_id`) REFERENCES `discount_codes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_discount_code_products_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `discount_code_products`
--

LOCK TABLES `discount_code_products` WRITE;
/*!40000 ALTER TABLE `discount_code_products` DISABLE KEYS */;
/*!40000 ALTER TABLE `discount_code_products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `discount_code_usage`
--

DROP TABLE IF EXISTS `discount_code_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `discount_code_usage` (
  `id` int NOT NULL AUTO_INCREMENT,
  `discount_code_id` int NOT NULL,
  `user_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `order_id` int DEFAULT NULL,
  `used_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_discount_code_usage_code` (`discount_code_id`),
  CONSTRAINT `fk_discount_code_usage_code` FOREIGN KEY (`discount_code_id`) REFERENCES `discount_codes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `discount_code_usage`
--

LOCK TABLES `discount_code_usage` WRITE;
/*!40000 ALTER TABLE `discount_code_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `discount_code_usage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `discount_codes`
--

DROP TABLE IF EXISTS `discount_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `discount_codes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `discount_type_id` int NOT NULL,
  `discount_value` decimal(10,2) DEFAULT NULL,
  `max_uses` int DEFAULT NULL,
  `used_count` int DEFAULT '0',
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `is_single_use` tinyint(1) DEFAULT '0',
  `created_by` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'ID del admin que lo creó',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `discount_codes`
--

LOCK TABLES `discount_codes` WRITE;
/*!40000 ALTER TABLE `discount_codes` DISABLE KEYS */;
INSERT INTO `discount_codes` VALUES (1,'A4BE2B7B',1,20.00,NULL,0,NULL,NULL,1,1,'6860007924a6a','2025-09-28 09:44:51','2025-09-28 16:36:25'),(14,'81B32E38',1,40.00,NULL,0,NULL,NULL,1,0,'6860007924a6a','2025-10-04 13:40:21','2025-10-04 14:55:57'),(15,'E20FA9C5',1,20.00,NULL,0,NULL,NULL,1,0,'6860007924a6a','2025-10-04 15:41:22','2025-10-04 15:41:22');
/*!40000 ALTER TABLE `discount_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `discount_types`
--

DROP TABLE IF EXISTS `discount_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `discount_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `discount_types`
--

LOCK TABLES `discount_types` WRITE;
/*!40000 ALTER TABLE `discount_types` DISABLE KEYS */;
INSERT INTO `discount_types` VALUES (1,'Porcentaje','Descuento porcentual sobre el total',1,'2025-07-27 17:38:50','2025-07-27 17:38:50'),(2,'Monto fijo','Descuento de monto fijo',1,'2025-07-27 17:38:50','2025-07-27 17:38:50'),(3,'Envío gratis','Descuento para envío gratuito',1,'2025-07-27 17:38:50','2025-07-27 17:38:50');
/*!40000 ALTER TABLE `discount_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `eliminaciones_auditoria`
--

DROP TABLE IF EXISTS `eliminaciones_auditoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eliminaciones_auditoria` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `accion` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Eliminado',
  `fecha_eliminacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `eliminaciones_auditoria`
--

LOCK TABLES `eliminaciones_auditoria` WRITE;
/*!40000 ALTER TABLE `eliminaciones_auditoria` DISABLE KEYS */;
/*!40000 ALTER TABLE `eliminaciones_auditoria` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fixed_amount_discounts`
--

DROP TABLE IF EXISTS `fixed_amount_discounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fixed_amount_discounts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `discount_code_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `min_order_amount` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_fixed_amount_discounts_code` (`discount_code_id`),
  CONSTRAINT `fk_fixed_amount_discounts_code` FOREIGN KEY (`discount_code_id`) REFERENCES `discount_codes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fixed_amount_discounts`
--

LOCK TABLES `fixed_amount_discounts` WRITE;
/*!40000 ALTER TABLE `fixed_amount_discounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `fixed_amount_discounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `free_shipping_discounts`
--

DROP TABLE IF EXISTS `free_shipping_discounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `free_shipping_discounts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `discount_code_id` int NOT NULL,
  `shipping_method_id` int DEFAULT NULL COMMENT 'NULL para todos los métodos',
  PRIMARY KEY (`id`),
  KEY `fk_free_shipping_discounts_code` (`discount_code_id`),
  CONSTRAINT `fk_free_shipping_discounts_code` FOREIGN KEY (`discount_code_id`) REFERENCES `discount_codes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `free_shipping_discounts`
--

LOCK TABLES `free_shipping_discounts` WRITE;
/*!40000 ALTER TABLE `free_shipping_discounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `free_shipping_discounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `google_auth`
--

DROP TABLE IF EXISTS `google_auth`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `google_auth` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `google_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `access_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `google_auth`
--

LOCK TABLES `google_auth` WRITE;
/*!40000 ALTER TABLE `google_auth` DISABLE KEYS */;
INSERT INTO `google_auth` VALUES (4,'6860007924a6a','100021586628962750893','ya29.a0AS3H6NxWuxsKzvVZ78hlXIpNbkEuyBhlqCM-TAZVUOGequUWV3a07XgX6zOV1CBF5qW5qfR_7FaFucKHLluMZBfjTZw_MhKSVmbokJERwtzQbROc1a4BocIWIQ0ZL_W40z-KWYjh0I9SLbEtH3W2B_XqUlIn12l0fHRav4jESwaCgYKAZwSARESFQHGX2MihiMSealW_Ok6ItYSjWrP7Q0177','2025-07-09 18:18:26'),(5,'68d315cf194ba','113148158052315120773','ya29.a0AQQ_BDTgZMejCPKvcenRcWzqFCDIVWn5qe70mTYR0Gl0z_BCBYe0xJKMW9pEAOlBNltLU6Fi4zZFIy9VYPy5b2y62ceUstxtrcyETYU_wdxnE3n-Pp0yhw4BTj8oNzZrsrOyCs2c7pDnAJCPzJdzpbtpU6dls4CNwilL9vfXt2cApsiw9vVNL8HYnEZflS8JlGtIht4aCgYKASUSARESFQHGX2Midxmzb7bnEUh1t_EFn8RQKw0206','2025-09-23 16:49:03');
/*!40000 ALTER TABLE `google_auth` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_attempts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `attempt_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_attempts`
--

LOCK TABLES `login_attempts` WRITE;
/*!40000 ALTER TABLE `login_attempts` DISABLE KEYS */;
INSERT INTO `login_attempts` VALUES (1,'braianoquen@gmail.com','::1','2025-07-20 11:18:27'),(2,'3013636902','::1','2025-07-21 15:18:28'),(3,'braianoquen@gmail.com','::1','2025-10-04 10:53:32'),(4,'braianoquen@gmail.com','::1','2025-10-04 10:53:35'),(5,'braianoquendurango@gmail.com','::1','2025-10-09 08:45:50'),(6,'braianoquendurango@gmail.com','::1','2025-11-01 15:33:58'),(7,'braianoquen@gmail.com','::1','2025-11-01 17:00:15'),(8,'braianoquen2@gmail.com','::1','2025-11-01 18:41:12'),(9,'braianoquen@gmail.com','::1','2025-11-01 18:42:18'),(10,'braianoquendurango@gmail.com','::1','2025-11-07 06:15:10'),(11,'braianoquen@example.com','127.0.0.1','2025-11-15 17:03:33'),(12,'braianoquen@gmail.com','::1','2025-11-15 17:04:26'),(13,'braianoquendurango@gmail.com','::1','2025-11-15 17:36:58'),(14,'braianoquendurango@gmail.com','::1','2025-11-15 17:37:08'),(15,'braianoquen@gmail.com','::1','2025-11-16 08:26:48'),(16,'braianoquen@gmail.com','::1','2025-11-16 10:41:52'),(17,'braianoquen@gmail.com','::1','2025-11-16 10:42:12'),(18,'braianoquendurango@gmail.com','::1','2025-11-16 18:35:07'),(19,'braianoquendurango@gmail.com','::1','2025-11-16 18:35:17'),(20,'braianoquendurango@gmail.com','::1','2025-11-16 23:32:39'),(21,'braianoquendurango@gmail.com','::1','2025-11-17 22:07:26'),(22,'braianoquendurango@gmail.com','::1','2025-11-17 23:21:09');
/*!40000 ALTER TABLE `login_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2025_11_14_000500_create_auth_support_tables',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_preferences`
--

DROP TABLE IF EXISTS `notification_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_preferences` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `type_id` int NOT NULL,
  `email_enabled` tinyint(1) DEFAULT '1',
  `sms_enabled` tinyint(1) DEFAULT '0',
  `push_enabled` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_notification_preferences_user` (`user_id`),
  KEY `fk_notification_preferences_type` (`type_id`),
  CONSTRAINT `fk_notification_preferences_type` FOREIGN KEY (`type_id`) REFERENCES `notification_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notification_preferences_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_preferences`
--

LOCK TABLES `notification_preferences` WRITE;
/*!40000 ALTER TABLE `notification_preferences` DISABLE KEYS */;
INSERT INTO `notification_preferences` VALUES (1,'6861e06ddcf49',2,1,0,1,'2025-11-17 23:34:07','2025-11-17 23:38:52'),(2,'6861e06ddcf49',3,1,0,1,'2025-11-17 23:34:07','2025-11-17 23:38:52'),(3,'6861e06ddcf49',1,1,0,1,'2025-11-17 23:34:07','2025-11-17 23:38:52');
/*!40000 ALTER TABLE `notification_preferences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_queue`
--

DROP TABLE IF EXISTS `notification_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_queue` (
  `id` int NOT NULL AUTO_INCREMENT,
  `notification_id` int NOT NULL,
  `channel` enum('email','sms','push') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('pending','processing','sent','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `attempts` tinyint DEFAULT '0',
  `last_attempt_at` datetime DEFAULT NULL,
  `scheduled_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `sent_at` datetime DEFAULT NULL,
  `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_queue`
--

LOCK TABLES `notification_queue` WRITE;
/*!40000 ALTER TABLE `notification_queue` DISABLE KEYS */;
/*!40000 ALTER TABLE `notification_queue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_types`
--

DROP TABLE IF EXISTS `notification_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `template` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_types`
--

LOCK TABLES `notification_types` WRITE;
/*!40000 ALTER TABLE `notification_types` DISABLE KEYS */;
INSERT INTO `notification_types` VALUES (1,'order','Notificaciones relacionadas con pedidos','Tu pedido #{order_id} ha cambiado de estado a: {status}',1,'2025-11-12 08:37:38','2025-11-12 08:37:38'),(2,'product','Notificaciones de productos (disponibilidad, nuevo stock)','El producto {product_name} está {status}',1,'2025-11-12 08:37:38','2025-11-12 08:37:38'),(3,'promotion','Ofertas y promociones especiales','Nueva promoción: {promotion_name}',1,'2025-11-12 08:37:38','2025-11-12 08:37:38'),(4,'account','Notificaciones de cuenta de usuario','Cambio en tu cuenta: {change_description}',1,'2025-11-12 08:37:38','2025-11-12 08:37:38'),(5,'system','Notificaciones del sistema','Mensaje del sistema: {message}',1,'2025-11-12 08:37:38','2025-11-12 08:37:38');
/*!40000 ALTER TABLE `notification_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `type_id` int NOT NULL,
  `title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `related_entity_type` enum('order','product','promotion','system','account') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `related_entity_id` int DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `is_email_sent` tinyint(1) DEFAULT '0',
  `is_sms_sent` tinyint(1) DEFAULT '0',
  `is_push_sent` tinyint(1) DEFAULT '0',
  `expires_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `read_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_notifications_user` (`user_id`),
  KEY `fk_notifications_type` (`type_id`),
  CONSTRAINT `fk_notifications_type` FOREIGN KEY (`type_id`) REFERENCES `notification_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,'6861e06ddcf49',1,'Pedido entregado #ORD20251116EAA8AB','Tu pedido #ORD20251116EAA8AB ha sido marcado como entregado. Encuentra tu factura adjunta en el correo.','order',7,1,0,0,0,NULL,'2025-11-16 12:55:47','2025-11-16 12:55:50'),(2,'6861e06ddcf49',1,'Tu envío está en camino','Tu pedido #ORD20251116EAA8AB ha salido para entrega. Pronto lo recibirás.','order',7,1,0,0,0,NULL,'2025-11-16 13:02:11','2025-11-16 13:02:18'),(3,'6861e06ddcf49',1,'Tu envío está en camino','Tu pedido #ORD202511163AF156 ha salido para entrega. Pronto lo recibirás.','order',8,1,0,0,0,NULL,'2025-11-16 20:01:57','2025-11-16 20:22:14'),(4,'6861e06ddcf49',1,'Pedido cancelado','Tu pedido #ORD202511163AF156 ha sido cancelado. Iniciaremos el reembolso en las próximas horas.','order',8,1,0,0,0,NULL,'2025-11-16 20:02:07','2025-11-16 20:22:14'),(5,'6861e06ddcf49',1,'Tu envío está en camino','Tu pedido #ORD202511163AF156 ha salido para entrega. Pronto lo recibirás.','order',8,1,0,0,0,NULL,'2025-11-16 20:02:51','2025-11-16 20:22:14'),(6,'6861e06ddcf49',1,'Pago aprobado','Hemos recibido el pago de tu pedido #ORD202511163AF156. Gracias.','order',8,1,0,0,0,NULL,'2025-11-16 20:02:57','2025-11-16 20:22:14'),(7,'6861e06ddcf49',1,'Pedido cancelado','Tu pedido #ORD202511163AF156 ha sido cancelado. Iniciaremos el reembolso en las próximas horas.','order',8,1,0,0,0,NULL,'2025-11-16 20:03:03','2025-11-16 20:22:14'),(8,'6861e06ddcf49',1,'Tu envío está en camino','Tu pedido #ORD202511163AF156 ha salido para entrega. Pronto lo recibirás.','order',8,1,0,0,0,NULL,'2025-11-16 20:03:54','2025-11-16 20:22:14'),(9,'6861e06ddcf49',1,'Pedido Confirmado','Tu pedido #1024 ha sido confirmado y está siendo preparado para envío.','order',1024,1,0,0,0,NULL,'2025-11-12 11:38:12','2025-11-12 08:51:10'),(10,'6861e06ddcf49',1,'Pedido en Camino','Tu pedido #1015 ha salido para entrega. Esperalo pronto.','order',1015,1,0,0,0,NULL,'2025-11-11 13:38:12','2025-11-11 14:38:12'),(11,'6861e06ddcf49',1,'Pedido Entregado','Tu pedido #1008 ha sido entregado exitosamente. ¡Gracias por tu compra!','order',1008,1,0,0,0,NULL,'2025-11-09 13:38:12','2025-11-09 15:38:12'),(12,'6861e06ddcf49',2,'Producto Disponible','¡Buenas noticias! El producto \"Vestido Rosa Princesa\" que agregaste a tu wishlist ya está disponible.','product',45,1,0,0,0,NULL,'2025-11-12 08:38:12','2025-11-12 08:51:10'),(13,'6861e06ddcf49',2,'Nuevo Stock','El producto \"Pantalón Mezclilla Niño\" ha vuelto a estar en stock.','product',32,1,0,0,0,NULL,'2025-11-11 13:38:12','2025-11-12 08:51:10'),(14,'6861e06ddcf49',3,'¡Oferta Especial del Fin de Semana!','Descuento del 30% en toda la colección primavera-verano. ¡No te lo pierdas!','promotion',5,1,0,0,0,NULL,'2025-11-12 10:38:12','2025-11-12 08:51:10'),(15,'6861e06ddcf49',3,'Cupón de Bienvenida','Usa el cupón BIENVENIDO20 para obtener 20% de descuento en tu próxima compra.','promotion',3,1,0,0,0,NULL,'2025-11-05 13:38:12','2025-11-06 13:38:12'),(16,'6861e06ddcf49',4,'Perfil Actualizado','Tu información de perfil ha sido actualizada correctamente.','account',NULL,1,0,0,0,NULL,'2025-11-10 13:38:12','2025-11-10 14:08:12'),(17,'6861e06ddcf49',4,'Nueva Dirección Agregada','Se ha agregado una nueva dirección de envío a tu cuenta.','account',NULL,1,0,0,0,NULL,'2025-11-12 07:38:12','2025-11-12 08:51:10'),(18,'6861e06ddcf49',5,'Actualización del Sistema','Hemos mejorado nuestra plataforma con nuevas funcionalidades. Descúbrelas ahora.','system',NULL,1,0,0,0,NULL,'2025-11-07 13:38:12','2025-11-08 13:38:12'),(19,'6861e06ddcf49',5,'Bienvenido a Angelow','Gracias por registrarte. Explora nuestra colección de ropa infantil y descubre las mejores ofertas.','system',NULL,1,0,0,0,NULL,'2025-11-02 13:38:12','2025-11-02 13:48:12'),(20,'6861e06ddcf49',1,'Pedido entregado #ORD202511163AF156','Tu pedido #ORD202511163AF156 ha sido marcado como entregado. Encuentra tu factura adjunta en el correo.','order',8,1,0,0,0,NULL,'2025-11-16 22:12:13','2025-11-16 22:13:31'),(21,'6861e06ddcf49',1,'Pago rechazado','Tu pago para el pedido #ORD202511163AF156 no fue aprobado. Revisa la referencia o contáctanos.','order',8,1,0,0,0,NULL,'2025-11-16 22:12:23','2025-11-16 22:13:31'),(23,'6861e06ddcf49',1,'Pedido entregado #ORD202511163AF156','Tu pedido #ORD202511163AF156 ha sido marcado como entregado. Encuentra tu factura adjunta en el correo.','order',8,1,0,0,0,NULL,'2025-11-16 22:21:30','2025-11-16 22:24:57'),(26,'6861e06ddcf49',1,'Reembolso exitoso','Tu reembolso del pedido #ORD202511163AF156 fue acreditado con éxito. Dependiendo de tu banco, lo verás reflejado en 24-72 horas.','order',8,1,0,0,0,NULL,'2025-11-16 22:24:18','2025-11-16 22:24:57'),(27,'6861e06ddcf49',1,'Reembolso exitoso','Confirmamos el reembolso de tu pedido #ORD202511163AF156. Verás el dinero reflejado según los tiempos de tu banco.','order',8,1,0,0,0,NULL,'2025-11-16 22:24:18','2025-11-16 22:24:57'),(29,'6861e06ddcf49',1,'Reembolso exitoso','Tu reembolso del pedido #ORD202511163AF156 fue acreditado con éxito. Dependiendo de tu banco, lo verás reflejado en 24-72 horas.','order',8,1,0,0,0,NULL,'2025-11-16 22:37:06','2025-11-16 23:01:47'),(30,'6861e06ddcf49',1,'Reembolso exitoso','Confirmamos el reembolso de tu pedido #ORD202511163AF156. Verás el dinero reflejado según los tiempos de tu banco.','order',8,1,0,0,0,NULL,'2025-11-16 22:37:06','2025-11-16 23:01:47'),(32,'6861e06ddcf49',1,'Pedido entregado #ORD202511163AF156','Tu pedido #ORD202511163AF156 ha sido marcado como entregado. Encuentra tu factura adjunta en el correo.','order',8,1,0,0,0,NULL,'2025-11-16 22:38:48','2025-11-16 23:01:47'),(33,'6861e06ddcf49',1,'Pago aprobado','Hemos recibido el pago de tu pedido #ORD202511163AF156. Gracias.','order',8,1,0,0,0,NULL,'2025-11-16 22:58:40','2025-11-16 23:01:47'),(34,'6861e06ddcf49',1,'Pago rechazado','Tu pago para el pedido #ORD202511163AF156 no fue aprobado. Revisa la referencia o contáctanos.','order',8,1,0,0,0,NULL,'2025-11-16 23:01:19','2025-11-16 23:01:47'),(35,'6861e06ddcf49',1,'Pago aprobado','Hemos recibido el pago de tu pedido #ORD202511163AF156. Gracias.','order',8,1,0,0,0,NULL,'2025-11-16 23:01:27','2025-11-16 23:01:47'),(37,'6861e06ddcf49',1,'Reembolso exitoso','Tu reembolso del pedido #ORD202511160CB9A1 fue acreditado con éxito. Dependiendo de tu banco, lo verás reflejado en 24-72 horas.','order',6,1,0,0,0,NULL,'2025-11-16 23:12:44','2025-11-17 08:26:59'),(38,'6861e06ddcf49',1,'Reembolso exitoso','Confirmamos el reembolso de tu pedido #ORD202511160CB9A1. Verás el dinero reflejado según los tiempos de tu banco.','order',6,1,0,0,0,NULL,'2025-11-16 23:12:44','2025-11-17 08:26:59'),(39,'6861e06ddcf49',1,'Pedido entregado #ORD20251116448A7E','Tu pedido #ORD20251116448A7E ha sido marcado como entregado. Encuentra tu factura adjunta en el correo.','order',5,1,0,0,0,NULL,'2025-11-17 13:04:12','2025-11-17 22:31:06'),(40,'6861e06ddcf49',1,'Pago aprobado','Hemos recibido el pago de tu pedido #ORD20251116134F34. Gracias.','order',4,1,0,0,0,NULL,'2025-11-17 13:05:02','2025-11-17 22:31:06'),(41,'6860007924a6a',3,'Oferta: Ropa deportiva - 22% OFF','¡Ropa deportiva ahora por $35.000 (antes $45.000) — 22% de descuento!','promotion',71,0,0,0,0,NULL,'2025-11-17 23:20:44',NULL),(42,'6861e06ddcf49',3,'Oferta: Ropa deportiva - 22% OFF','¡Ropa deportiva ahora por $35.000 (antes $45.000) — 22% de descuento!','promotion',71,1,0,0,0,NULL,'2025-11-17 23:20:44','2025-11-17 23:20:49');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `color_variant_id` int DEFAULT NULL,
  `size_variant_id` int DEFAULT NULL,
  `product_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `variant_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_order_items_order` (`order_id`),
  KEY `fk_order_items_product` (`product_id`),
  KEY `fk_order_items_color_variant` (`color_variant_id`),
  KEY `fk_order_items_size_variant` (`size_variant_id`),
  CONSTRAINT `fk_order_items_color_variant` FOREIGN KEY (`color_variant_id`) REFERENCES `product_color_variants` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_items_size_variant` FOREIGN KEY (`size_variant_id`) REFERENCES `product_size_variants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (1,1,71,NULL,NULL,'Ropa deportiva','',35000.00,1,35000.00,'2025-11-15 22:10:11'),(2,2,71,NULL,NULL,'Ropa deportiva','Color: Negro - Talla: XS',35000.00,1,35000.00,'2025-11-15 22:17:00'),(3,3,71,NULL,NULL,'Ropa deportiva','Color: Negro - Talla: XS',35000.00,1,35000.00,'2025-11-16 09:09:59'),(4,4,71,NULL,NULL,'Ropa deportiva','Color: Negro - Talla: XS',35000.00,1,35000.00,'2025-11-16 09:12:17'),(5,5,71,NULL,NULL,'Ropa deportiva','Color: Negro - Talla: XS',35000.00,1,35000.00,'2025-11-16 09:16:36'),(6,6,71,NULL,NULL,'Ropa deportiva','Color: Negro - Talla: XS',35000.00,1,35000.00,'2025-11-16 09:46:56'),(7,7,71,NULL,NULL,'Ropa deportiva','Color: Negro - Talla: XS',35000.00,1,35000.00,'2025-11-16 09:54:06'),(8,8,71,NULL,NULL,'Ropa deportiva','Color: Negro - Talla: XS',35000.00,1,35000.00,'2025-11-16 11:58:11');
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_status_history`
--

DROP TABLE IF EXISTS `order_status_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_status_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `changed_by` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `changed_by_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Nombre del usuario',
  `change_type` enum('status','payment_status','shipping','address','notes','created','cancelled','refunded','items','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'other',
  `field_changed` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Campo específico que cambió',
  `old_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Valor anterior',
  `new_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Valor nuevo',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Descripción legible del cambio',
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_order_history_order` (`order_id`),
  KEY `fk_order_history_user` (`changed_by`),
  CONSTRAINT `fk_order_history_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_history_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Historial detallado de cambios en órdenes';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_status_history`
--

LOCK TABLES `order_status_history` WRITE;
/*!40000 ALTER TABLE `order_status_history` DISABLE KEYS */;
INSERT INTO `order_status_history` VALUES (1,1,'6861e06ddcf49','Cliente','created','order_created',NULL,'TEST37D9B8','Orden #TEST37D9B8 creada con total de $45,000',NULL,NULL,'2025-11-15 22:10:11'),(2,2,'6861e06ddcf49','Cliente','created','order_created',NULL,'ORD20251115C9DBC9','Orden #ORD20251115C9DBC9 creada con total de $36,000',NULL,NULL,'2025-11-15 22:17:00'),(3,3,'6861e06ddcf49','Cliente','created','order_created',NULL,'ORD2025111677ADAB','Orden #ORD2025111677ADAB creada con total de $35,000',NULL,NULL,'2025-11-16 09:09:59'),(4,4,'6861e06ddcf49','Cliente','created','order_created',NULL,'ORD20251116134F34','Orden #ORD20251116134F34 creada con total de $35,000',NULL,NULL,'2025-11-16 09:12:17'),(5,5,'6861e06ddcf49','Cliente','created','order_created',NULL,'ORD20251116448A7E','Orden #ORD20251116448A7E creada con total de $35,000',NULL,NULL,'2025-11-16 09:16:36'),(6,6,'6861e06ddcf49','Cliente','created','order_created',NULL,'ORD202511160CB9A1','Orden #ORD202511160CB9A1 creada con total de $35,000',NULL,NULL,'2025-11-16 09:46:56'),(7,7,'6861e06ddcf49','Cliente','created','order_created',NULL,'ORD20251116EAA8AB','Orden #ORD20251116EAA8AB creada con total de $35,000',NULL,NULL,'2025-11-16 09:54:06'),(8,7,'6860007924a6a','Braian','status','status','pending','processing','Estado cambiado de \"Pendiente\" a \"En proceso\"','127.0.0.1 (localhost)',NULL,'2025-11-16 10:55:10'),(9,7,'6860007924a6a','Braian','payment_status','payment_status','pending','paid','Estado de pago cambiado de \"Pendiente\" a \"Pagado\"','::1',NULL,'2025-11-16 11:09:31'),(10,7,'6860007924a6a','Braian','payment_status','payment_status','pending','paid','Cambio de estado de pago','::1',NULL,'2025-11-16 11:09:31'),(11,7,'6860007924a6a','Braian','status','status','processing','shipped','Estado cambiado de \"En proceso\" a \"Enviado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 11:49:52'),(12,8,'6861e06ddcf49','Cliente','created','order_created',NULL,'ORD202511163AF156','Orden #ORD202511163AF156 creada con total de $43,000',NULL,NULL,'2025-11-16 11:58:11'),(13,7,'6860007924a6a','Braian','status','status','shipped','delivered','Estado cambiado de \"Enviado\" a \"Entregado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 12:13:02'),(14,7,'6860007924a6a','Braian','status','status','delivered','shipped','Estado cambiado de \"Entregado\" a \"Enviado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 12:13:24'),(15,7,'6860007924a6a','Braian','status','status','shipped','delivered','Estado cambiado de \"Enviado\" a \"Entregado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 12:13:38'),(16,7,'6860007924a6a','Braian','status','status','delivered','shipped','Estado cambiado de \"Entregado\" a \"Enviado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 12:16:59'),(17,7,'6860007924a6a','Braian','status','status','shipped','delivered','Estado cambiado de \"Enviado\" a \"Entregado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 12:17:05'),(18,7,'6860007924a6a','Braian','status','status','delivered','shipped','Estado cambiado de \"Entregado\" a \"Enviado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 12:21:58'),(19,7,'6860007924a6a','Braian','status','status','shipped','delivered','Estado cambiado de \"Enviado\" a \"Entregado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 12:22:07'),(20,7,'6860007924a6a','Braian','status','status','delivered','shipped','Estado cambiado de \"Entregado\" a \"Enviado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 12:28:18'),(21,7,'6860007924a6a','Braian','status','status','shipped','delivered','Estado cambiado de \"Enviado\" a \"Entregado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 12:28:24'),(22,7,'6860007924a6a','Braian','status','status','delivered','shipped','Estado cambiado de \"Entregado\" a \"Enviado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 12:33:16'),(23,7,'6860007924a6a','Braian','status','status','shipped','delivered','Estado cambiado de \"Enviado\" a \"Entregado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 12:33:22'),(24,7,'6860007924a6a','Braian','status','status','delivered','shipped','Estado cambiado de \"Entregado\" a \"Enviado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 12:36:17'),(25,7,'6860007924a6a','Braian','status','status','shipped','delivered','Estado cambiado de \"Enviado\" a \"Entregado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 12:36:22'),(26,7,'6860007924a6a','Braian','status','status','delivered','shipped','Estado cambiado de \"Entregado\" a \"Enviado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 12:42:53'),(27,7,'6860007924a6a','Braian','status','status','shipped','delivered','Estado cambiado de \"Enviado\" a \"Entregado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 12:43:00'),(28,7,'6860007924a6a','Braian','status','status','delivered','shipped','Estado cambiado de \"Entregado\" a \"Enviado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 12:45:57'),(29,7,'6860007924a6a','Braian','status','status','shipped','delivered','Estado cambiado de \"Enviado\" a \"Entregado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 12:46:22'),(30,7,'6860007924a6a','Braian','status','status','delivered','shipped','Estado cambiado de \"Entregado\" a \"Enviado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 12:48:44'),(31,7,'6860007924a6a','Braian','status','status','shipped','delivered','Estado cambiado de \"Enviado\" a \"Entregado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 12:48:49'),(32,7,'6860007924a6a','Braian','status','status','delivered','shipped','Estado cambiado de \"Entregado\" a \"Enviado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 12:49:19'),(33,7,'6860007924a6a','Braian','status','status','shipped','delivered','Estado cambiado de \"Enviado\" a \"Entregado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 12:49:24'),(34,7,'6860007924a6a','Braian','status','status','delivered','shipped','Estado cambiado de \"Entregado\" a \"Enviado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 12:50:15'),(35,7,'6860007924a6a','Braian','status','status','shipped','delivered','Estado cambiado de \"Enviado\" a \"Entregado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 12:50:21'),(36,7,'6860007924a6a','Braian','status','status','delivered','shipped','Estado cambiado de \"Entregado\" a \"Enviado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 12:51:37'),(37,7,'6860007924a6a','Braian','status','status','shipped','delivered','Estado cambiado de \"Enviado\" a \"Entregado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 12:51:42'),(38,7,'6860007924a6a','Braian','status','status','delivered','shipped','Estado cambiado de \"Entregado\" a \"Enviado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 12:55:36'),(39,7,'6860007924a6a','Braian','status','status','shipped','delivered','Estado cambiado de \"Enviado\" a \"Entregado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 12:55:41'),(40,7,'6860007924a6a','Braian','status','status','delivered','shipped','Estado cambiado de \"Entregado\" a \"Enviado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 13:02:11'),(41,8,NULL,'Sistema','status','status','pending','cancelled','Estado cambiado de \"Pendiente\" a \"Cancelado\"',NULL,NULL,'2025-11-16 18:53:06'),(42,8,'6860007924a6a','Braian','status','status','cancelled','shipped','Estado cambiado de \"Cancelado\" a \"Enviado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 20:01:57'),(43,8,'6860007924a6a','Braian','status','status','shipped','cancelled','Estado cambiado de \"Enviado\" a \"Cancelado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 20:02:07'),(44,8,'6860007924a6a','Braian','status','status','cancelled','shipped','Estado cambiado de \"Cancelado\" a \"Enviado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 20:02:51'),(45,8,'6860007924a6a','Braian','payment_status','payment_status','pending','paid','Estado de pago cambiado de \"Pendiente\" a \"Pagado\"','::1',NULL,'2025-11-16 20:02:57'),(46,8,'6860007924a6a','Braian','payment_status','payment_status','pending','paid','Cambio de estado de pago','::1',NULL,'2025-11-16 20:02:57'),(47,8,'6860007924a6a','Braian','status','status','shipped','cancelled','Estado cambiado de \"Enviado\" a \"Cancelado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 20:03:03'),(48,8,'6860007924a6a','Braian','status','status','cancelled','shipped','Estado cambiado de \"Cancelado\" a \"Enviado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 20:03:54'),(49,8,'6860007924a6a','Braian','payment_status','payment_status','paid','pending','Estado de pago cambiado de \"Pagado\" a \"Pendiente\"','::1',NULL,'2025-11-16 20:12:20'),(50,8,'6860007924a6a','Braian','payment_status','payment_status','paid','pending','Cambio de estado de pago','::1',NULL,'2025-11-16 20:12:20'),(51,8,'6860007924a6a','Braian','payment_status','payment_status','pending','paid','Estado de pago cambiado de \"Pendiente\" a \"Pagado\"','::1',NULL,'2025-11-16 20:12:49'),(52,8,'6860007924a6a','Braian','payment_status','payment_status','pending','paid','Cambio de estado de pago','::1',NULL,'2025-11-16 20:12:49'),(53,8,'6860007924a6a','Braian','status','status','shipped','cancelled','Estado cambiado de \"Enviado\" a \"Cancelado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 20:12:56'),(54,8,'6860007924a6a','Braian','payment_status','payment_status','paid','refunded','Estado de pago cambiado de \"Pagado\" a \"Reembolsado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 20:12:56'),(55,8,'6860007924a6a','Braian','status','status','cancelled','refunded','Estado cambiado de \"Cancelado\" a \"Reembolsado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 22:00:43'),(56,8,'6860007924a6a','Braian','payment_status','payment_status','refunded','paid','Estado de pago cambiado de \"Reembolsado\" a \"Pagado\"','::1',NULL,'2025-11-16 22:03:56'),(57,8,'6860007924a6a','Braian','payment_status','payment_status','refunded','paid','Cambio de estado de pago','::1',NULL,'2025-11-16 22:03:56'),(58,8,'6860007924a6a','Braian','status','status','refunded','processing','Estado cambiado de \"Reembolsado\" a \"En proceso\"','127.0.0.1 (localhost)',NULL,'2025-11-16 22:04:07'),(59,8,'6860007924a6a','Braian','payment_status','payment_status','paid','refunded','Estado de pago cambiado de \"Pagado\" a \"Reembolsado\"','::1',NULL,'2025-11-16 22:04:12'),(60,8,'6860007924a6a','Braian','payment_status','payment_status','paid','refunded','Cambio de estado de pago','::1',NULL,'2025-11-16 22:04:12'),(61,8,'6860007924a6a','Braian','status','status','processing','refunded','Estado cambiado de \"En proceso\" a \"Reembolsado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 22:04:30'),(62,8,'6860007924a6a','Braian','payment_status','payment_status','refunded','paid','Estado de pago cambiado de \"Reembolsado\" a \"Pagado\"','::1',NULL,'2025-11-16 22:06:58'),(63,8,'6860007924a6a','Braian','payment_status','payment_status','refunded','paid','Cambio de estado de pago','::1',NULL,'2025-11-16 22:06:58'),(64,8,'6860007924a6a','Braian','status','status','refunded','cancelled','Estado cambiado de \"Reembolsado\" a \"Cancelado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 22:07:02'),(65,8,'6860007924a6a','Braian','payment_status','payment_status','paid','refunded','Estado de pago cambiado de \"Pagado\" a \"Reembolsado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 22:07:02'),(66,8,'6860007924a6a','Braian','status','status','cancelled','refunded','Estado cambiado de \"Cancelado\" a \"Reembolsado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 22:07:14'),(67,8,'6860007924a6a','Braian','status','status','refunded','cancelled','Estado cambiado de \"Reembolsado\" a \"Cancelado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 22:09:33'),(68,8,'6860007924a6a','Braian','status','status','cancelled','refunded','Estado cambiado de \"Cancelado\" a \"Reembolsado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 22:09:43'),(69,8,'6860007924a6a','Braian','payment_status','payment_status','refunded','paid','Estado de pago cambiado de \"Reembolsado\" a \"Pagado\"','::1',NULL,'2025-11-16 22:12:03'),(70,8,'6860007924a6a','Braian','payment_status','payment_status','refunded','paid','Cambio de estado de pago','::1',NULL,'2025-11-16 22:12:03'),(71,8,'6860007924a6a','Braian','status','status','refunded','delivered','Estado cambiado de \"Reembolsado\" a \"Entregado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 22:12:09'),(72,8,'6860007924a6a','Braian','payment_status','payment_status','paid','failed','Estado de pago cambiado de \"Pagado\" a \"Fallido\"','::1',NULL,'2025-11-16 22:12:23'),(73,8,'6860007924a6a','Braian','payment_status','payment_status','paid','failed','Cambio de estado de pago','::1',NULL,'2025-11-16 22:12:23'),(74,8,'6860007924a6a','Braian','status','status','delivered','cancelled','Estado cambiado de \"Entregado\" a \"Cancelado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 22:13:09'),(75,8,'6860007924a6a','Braian','status','status','cancelled','refunded','Estado cambiado de \"Cancelado\" a \"Reembolsado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 22:13:17'),(76,8,'6860007924a6a','Braian','payment_status','payment_status','failed','refunded','Estado de pago cambiado de \"Fallido\" a \"Reembolsado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 22:13:17'),(77,8,'6860007924a6a','Braian','status','status','refunded','delivered','Estado cambiado de \"Reembolsado\" a \"Entregado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 22:21:19'),(78,8,'6860007924a6a','Braian','status','status','delivered','refunded','Estado cambiado de \"Entregado\" a \"Reembolsado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 22:21:36'),(79,8,'6860007924a6a','Braian','status','status','refunded','cancelled','Estado cambiado de \"Reembolsado\" a \"Cancelado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 22:21:55'),(80,8,'6860007924a6a','Braian','status','status','cancelled','refunded','Estado cambiado de \"Cancelado\" a \"Reembolsado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 22:22:09'),(81,8,'6860007924a6a','Braian','status','status','refunded','cancelled','Estado cambiado de \"Reembolsado\" a \"Cancelado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 22:23:57'),(82,8,'6860007924a6a','Braian','status','status','cancelled','refunded','Estado cambiado de \"Cancelado\" a \"Reembolsado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 22:24:11'),(83,8,NULL,'6860007924','refunded','payment_status','43000','refunded','Reembolso de 43.000 registrado en sistema. Ref: 21212121','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0','2025-11-16 22:24:11'),(84,8,'6860007924a6a','Braian','status','status','refunded','cancelled','Estado cambiado de \"Reembolsado\" a \"Cancelado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 22:33:36'),(85,8,'6860007924a6a','Braian','status','status','cancelled','refunded','Estado cambiado de \"Cancelado\" a \"Reembolsado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 22:37:00'),(86,8,'6860007924a6a','Braian','status','status','refunded','cancelled','Estado cambiado de \"Reembolsado\" a \"Cancelado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 22:37:40'),(87,8,'6860007924a6a','Braian','status','status','cancelled','delivered','Estado cambiado de \"Cancelado\" a \"Entregado\"','127.0.0.1 (localhost)',NULL,'2025-11-16 22:38:38'),(88,8,'6860007924a6a','Braian','payment_status','payment_status','refunded','paid','Estado de pago actualizado','::1',NULL,'2025-11-16 22:58:40'),(89,8,'6860007924a6a','Braian','payment_status','payment_status','refunded','paid','Cambio de estado de pago','::1',NULL,'2025-11-16 22:58:40'),(90,8,'6860007924a6a','Braian','payment_status','payment_status','paid','failed','Estado de pago actualizado','::1',NULL,'2025-11-16 23:01:19'),(91,8,'6860007924a6a','Braian','payment_status','payment_status','paid','failed','Cambio de estado de pago','::1',NULL,'2025-11-16 23:01:19'),(92,8,'6860007924a6a','Braian','payment_status','payment_status','failed','paid','Estado de pago actualizado','::1',NULL,'2025-11-16 23:01:27'),(93,8,'6860007924a6a','Braian','payment_status','payment_status','failed','paid','Cambio de estado de pago','::1',NULL,'2025-11-16 23:01:27'),(94,6,NULL,'Sistema','status','status','pending','cancelled','Estado cambiado: pending ÔåÆ cancelled',NULL,NULL,'2025-11-16 23:11:45'),(95,6,NULL,'Sistema','payment_status','payment_status','pending','refunded','Estado de pago actualizado',NULL,NULL,'2025-11-16 23:11:45'),(96,6,'6860007924a6a','Braian','status','status','cancelled','refunded','Estado cambiado: cancelled ÔåÆ refunded','127.0.0.1 (localhost)',NULL,'2025-11-16 23:12:38'),(97,6,NULL,'6860007924','refunded','payment_status','35000','refunded','Reembolso de 35.000 registrado en sistema. Ref: 454543432323','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0','2025-11-16 23:12:38'),(98,5,'6860007924a6a','Braian','status','status','pending','delivered','Estado cambiado: pending ÔåÆ delivered','127.0.0.1 (localhost)',NULL,'2025-11-17 13:04:02'),(99,4,'6860007924a6a','Braian','payment_status','payment_status','pending','paid','Estado de pago actualizado','::1',NULL,'2025-11-17 13:05:02'),(100,4,'6860007924a6a','Braian','payment_status','payment_status','pending','paid','Cambio de estado de pago','::1',NULL,'2025-11-17 13:05:02');
/*!40000 ALTER TABLE `order_status_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_views`
--

DROP TABLE IF EXISTS `order_views`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_views` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `user_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `viewed_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_order_views_order` (`order_id`),
  KEY `fk_order_views_user` (`user_id`),
  CONSTRAINT `fk_order_views_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_views_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tabla para rastrear qué órdenes han sido vistas por cada administrador';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_views`
--

LOCK TABLES `order_views` WRITE;
/*!40000 ALTER TABLE `order_views` DISABLE KEYS */;
INSERT INTO `order_views` VALUES (1,1,'6860007924a6a','2025-11-16 08:27:13'),(2,2,'6860007924a6a','2025-11-16 08:27:13'),(4,3,'6860007924a6a','2025-11-16 10:30:11'),(5,4,'6860007924a6a','2025-11-16 10:30:11'),(6,5,'6860007924a6a','2025-11-16 10:30:11'),(7,6,'6860007924a6a','2025-11-16 10:30:11'),(8,7,'6860007924a6a','2025-11-16 10:30:11'),(11,8,'6860007924a6a','2025-11-16 11:58:35');
/*!40000 ALTER TABLE `order_views` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `invoice_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled','refunded') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `subtotal` decimal(10,2) NOT NULL,
  `shipping_cost` decimal(10,2) DEFAULT '0.00',
  `discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `payment_status` enum('pending','paid','failed','refunded') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `shipping_address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `shipping_city` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `shipping_method_id` int DEFAULT NULL,
  `shipping_address_id` int DEFAULT NULL COMMENT 'FK a user_addresses - Dirección de envío actual',
  `billing_address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `billing_address_id` int DEFAULT NULL COMMENT 'FK a user_addresses - Dirección de facturación',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `invoice_resolution` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Resolución DIAN para facturación',
  `invoice_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_shipping_method_id` (`shipping_method_id`),
  CONSTRAINT `fk_orders_shipping_method` FOREIGN KEY (`shipping_method_id`) REFERENCES `shipping_methods` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,'TEST37D9B8',NULL,'6861e06ddcf49','pending',35000.00,10000.00,0.00,45000.00,'transfer','pending','Dirección de prueba','Medellín',NULL,NULL,NULL,NULL,NULL,'2025-11-15 22:10:11','2025-11-15 22:10:11',NULL,NULL),(2,'ORD20251115C9DBC9',NULL,'6861e06ddcf49','pending',35000.00,8000.00,0.00,36000.00,'transfer','pending','Calle 63A, Comuna 8 - Villa Hermosa','Medellín',NULL,8,NULL,NULL,NULL,'2025-11-15 22:17:00','2025-11-15 22:17:00',NULL,NULL),(3,'ORD2025111677ADAB',NULL,'6861e06ddcf49','pending',35000.00,0.00,0.00,35000.00,'transfer','pending','Calle 63A, Comuna 8 - Villa Hermosa','Medellín',4,8,NULL,NULL,NULL,'2025-11-16 09:09:59','2025-11-16 09:09:59',NULL,NULL),(4,'ORD20251116134F34',NULL,'6861e06ddcf49','pending',35000.00,0.00,0.00,35000.00,'transfer','paid','Calle 63A, Comuna 8 - Villa Hermosa','Medellín',4,8,NULL,NULL,NULL,'2025-11-16 09:12:17','2025-11-17 13:05:02',NULL,NULL),(5,'ORD20251116448A7E',NULL,'6861e06ddcf49','delivered',35000.00,0.00,0.00,35000.00,'transfer','pending','Calle 63A, Comuna 8 - Villa Hermosa','Medellín',4,8,NULL,NULL,NULL,'2025-11-16 09:16:36','2025-11-17 13:04:02',NULL,NULL),(6,'ORD202511160CB9A1',NULL,'6861e06ddcf49','refunded',35000.00,0.00,0.00,35000.00,'transfer','refunded','Calle 63A, Comuna 8 - Villa Hermosa','Medellín',4,8,NULL,NULL,NULL,'2025-11-16 09:46:56','2025-11-16 23:12:38',NULL,NULL),(7,'ORD20251116EAA8AB',NULL,'6861e06ddcf49','shipped',35000.00,0.00,0.00,35000.00,'transfer','paid','Calle 63A, Comuna 8 - Villa Hermosa','Medellín',4,8,NULL,NULL,NULL,'2025-11-16 09:54:06','2025-11-16 13:02:11',NULL,NULL),(8,'ORD202511163AF156',NULL,'6861e06ddcf49','delivered',35000.00,8000.00,0.00,43000.00,'transfer','paid','Calle 63A, Comuna 8 - Villa Hermosa','Medellín',2,8,NULL,NULL,NULL,'2025-11-16 11:58:11','2025-11-16 23:01:27',NULL,NULL);
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `auditoria_orden_insert` AFTER INSERT ON `orders` FOR EACH ROW BEGIN
  INSERT INTO audit_orders (orden_id, accion, usuario_id, sql_usuario, detalles)
  VALUES (
    NEW.id, 
    'INSERT', 
    NEW.user_id,          
    CURRENT_USER(),       
    CONCAT('Se creó la orden #', NEW.order_number, ' con total $', NEW.total)
  );
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `track_order_creation` AFTER INSERT ON `orders` FOR EACH ROW BEGIN
DECLARE created_by_id VARCHAR(20);
DECLARE created_by_name_val VARCHAR(100);
DECLARE user_exists INT;
SET created_by_id = COALESCE(@current_user_id, NEW.user_id);
SET created_by_name_val = COALESCE(@current_user_name, 'Cliente');
IF created_by_id IS NOT NULL THEN
SELECT COUNT(*) INTO user_exists FROM users WHERE id COLLATE utf8mb4_general_ci = created_by_id COLLATE utf8mb4_general_ci;
IF user_exists = 0 THEN
SET created_by_id = NULL;
SET created_by_name_val = CONCAT('Usuario no encontrado (', COALESCE(created_by_id, 'desconocido'), ')');
END IF;
END IF;
INSERT INTO order_status_history
(order_id, changed_by, changed_by_name, change_type, field_changed, old_value, new_value, description, ip_address)
VALUES (
NEW.id,
created_by_id, -- Puede ser NULL
created_by_name_val,
'created',
'order_created',
NULL,
NEW.order_number,
CONCAT('Orden #', NEW.order_number, ' creada con total de $', FORMAT(NEW.total, 0)),
COALESCE(@current_user_ip, NULL)
);
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `auditoria_orden_update` AFTER UPDATE ON `orders` FOR EACH ROW BEGIN
  IF (NEW.status != OLD.status OR NEW.total != OLD.total) THEN
    INSERT INTO audit_orders (orden_id, accion, usuario_id, sql_usuario, detalles)
    VALUES (
      NEW.id, 
      'UPDATE', 
      NEW.user_id,         
      CURRENT_USER(),    
      CONCAT('Orden actualizada. Estado: ', OLD.status, ' → ', NEW.status, 
             '. Total: $', OLD.total, ' → $', NEW.total)
    );
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `track_order_changes_update` AFTER UPDATE ON `orders` FOR EACH ROW BEGIN
  DECLARE changed_by_id VARCHAR(20);
  DECLARE changed_by_name_val VARCHAR(100);
  DECLARE user_exists INT;
  SET changed_by_id = @current_user_id;
  SET changed_by_name_val = COALESCE(@current_user_name, 'Sistema');
  IF changed_by_id IS NOT NULL THEN
    SELECT COUNT(*) INTO user_exists FROM users WHERE id COLLATE utf8mb4_general_ci = changed_by_id COLLATE utf8mb4_general_ci;
    IF user_exists = 0 THEN
      SET changed_by_id = NULL;
      SET changed_by_name_val = CONCAT('Usuario no encontrado (', COALESCE(@current_user_id, 'desconocido'), ')');
    END IF;
  END IF;

  IF (NEW.status != OLD.status) THEN
    INSERT INTO order_status_history
    (order_id, changed_by, changed_by_name, change_type, field_changed, old_value, new_value, description, ip_address)
    VALUES (
      NEW.id,
      changed_by_id,
      changed_by_name_val,
      'status',
      'status',
      OLD.status,
      NEW.status,
      CONCAT('Estado cambiado: ', OLD.status, ' ÔåÆ ', NEW.status),
      COALESCE(@current_user_ip, NULL)
    );
  END IF;

  IF (NEW.payment_status != OLD.payment_status) THEN
    INSERT INTO order_status_history
    (order_id, changed_by, changed_by_name, change_type, field_changed, old_value, new_value, description, ip_address)
    VALUES (
      NEW.id,
      changed_by_id,
      changed_by_name_val,
      'payment_status',
      'payment_status',
      OLD.payment_status,
      NEW.payment_status,
      'Estado de pago actualizado',
      COALESCE(@current_user_ip, NULL)
    );
  END IF;

  

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `auditoria_orden_delete` BEFORE DELETE ON `orders` FOR EACH ROW BEGIN
  INSERT INTO audit_orders (orden_id, accion, usuario_id, sql_usuario, detalles)
  VALUES (
    OLD.id, 
    'DELETE', 
    OLD.user_id,         
    CURRENT_USER(),        
    CONCAT('Se eliminó la orden #', OLD.order_number, ' con total $', OLD.total)
  );
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `is_used` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_transactions`
--

DROP TABLE IF EXISTS `payment_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int DEFAULT NULL,
  `user_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reference_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `payment_proof` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('pending','verified','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `admin_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `verified_by` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_payment_transactions_order` (`order_id`),
  KEY `fk_payment_transactions_verified_by` (`verified_by`),
  CONSTRAINT `fk_payment_transactions_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_payment_transactions_verified_by` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_transactions`
--

LOCK TABLES `payment_transactions` WRITE;
/*!40000 ALTER TABLE `payment_transactions` DISABLE KEYS */;
INSERT INTO `payment_transactions` VALUES (22,NULL,'6861e06ddcf49',43000.00,'21212121','uploads/payment_proofs/proof_6861e06ddcf49_1760374683.png','pending',NULL,NULL,NULL,'2025-10-13 11:58:03','2025-10-13 11:58:03'),(23,1,'6861e06ddcf49',45000.00,'TESTREF',NULL,'pending',NULL,NULL,NULL,'2025-11-15 22:10:11','2025-11-15 22:10:11'),(24,2,'6861e06ddcf49',36000.00,'454543432323','uploads/payment_proofs/proof_6861e06ddcf49_1763263020.png','pending',NULL,NULL,NULL,'2025-11-15 22:17:00','2025-11-15 22:17:00'),(25,3,'6861e06ddcf49',35000.00,'454543432323','uploads/payment_proofs/proof_6861e06ddcf49_1763302199.png','pending',NULL,NULL,NULL,'2025-11-16 09:09:59','2025-11-16 09:09:59'),(26,4,'6861e06ddcf49',35000.00,'454543432323','uploads/payment_proofs/proof_6861e06ddcf49_1763302337.png','pending',NULL,NULL,NULL,'2025-11-16 09:12:17','2025-11-16 09:12:17'),(27,5,'6861e06ddcf49',35000.00,'454543432323','uploads/payment_proofs/proof_6861e06ddcf49_1763302596.png','pending',NULL,NULL,NULL,'2025-11-16 09:16:36','2025-11-16 09:16:36'),(28,6,'6861e06ddcf49',35000.00,'454543432323','uploads/payment_proofs/proof_6861e06ddcf49_1763304416.png','pending',NULL,NULL,NULL,'2025-11-16 09:46:56','2025-11-16 09:46:56'),(29,7,'6861e06ddcf49',35000.00,'454543432323','uploads/payment_proofs/proof_6861e06ddcf49_1763304846.png','pending',NULL,NULL,NULL,'2025-11-16 09:54:06','2025-11-16 09:54:06'),(30,8,'6861e06ddcf49',43000.00,'21212121','uploads/payment_proofs/proof_6861e06ddcf49_1763312291.png','pending',NULL,NULL,NULL,'2025-11-16 11:58:11','2025-11-16 11:58:11'),(31,8,'6861e06ddcf49',-43000.00,'21212121',NULL,'verified','Reembolso registrado · Ref: 21212121',NULL,'2025-11-16 22:24:11','2025-11-16 22:24:11','2025-11-18 22:27:37'),(32,6,'6861e06ddcf49',-35000.00,'454543432323',NULL,'verified','Reembolso registrado · Ref: 454543432323',NULL,'2025-11-16 23:12:38','2025-11-16 23:12:38','2025-11-18 22:27:37');
/*!40000 ALTER TABLE `payment_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `percentage_discounts`
--

DROP TABLE IF EXISTS `percentage_discounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `percentage_discounts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `discount_code_id` int NOT NULL,
  `percentage` decimal(5,2) NOT NULL,
  `max_discount_amount` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `percentage_discounts`
--

LOCK TABLES `percentage_discounts` WRITE;
/*!40000 ALTER TABLE `percentage_discounts` DISABLE KEYS */;
INSERT INTO `percentage_discounts` VALUES (1,1,20.00,0.00),(14,14,40.00,0.00),(15,15,20.00,0.00);
/*!40000 ALTER TABLE `percentage_discounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `popular_searches`
--

DROP TABLE IF EXISTS `popular_searches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `popular_searches` (
  `id` int NOT NULL AUTO_INCREMENT,
  `search_term` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `search_count` int NOT NULL DEFAULT '1',
  `last_searched` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `popular_searches`
--

LOCK TABLES `popular_searches` WRITE;
/*!40000 ALTER TABLE `popular_searches` DISABLE KEYS */;
INSERT INTO `popular_searches` VALUES (1,'ropa deportiva de niños',2,'2025-11-16 08:31:39'),(2,'ropa',31,'2025-11-16 11:57:41'),(3,'ropa',1,'2025-11-16 20:38:52'),(4,'Ropa deportiva',27,'2025-11-16 23:58:29'),(5,'ropa',1,'2025-11-17 10:05:31'),(8,'ropa niña',1,'2025-09-23 09:09:03'),(33,'ropa deportica',1,'2025-10-13 11:30:54'),(57,'ropa}',1,'2025-11-12 09:08:03'),(58,'ropa',1,'2025-11-17 11:23:18'),(59,'ropa',1,'2025-11-17 11:24:08'),(60,'ropa',1,'2025-11-17 15:59:34'),(61,'ropa',1,'2025-11-17 16:45:58');
/*!40000 ALTER TABLE `popular_searches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_collections`
--

DROP TABLE IF EXISTS `product_collections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_collections` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `collection_id` int NOT NULL,
  `display_order` int DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_product_collections_product` (`product_id`),
  KEY `fk_product_collections_collection` (`collection_id`),
  CONSTRAINT `fk_product_collections_collection` FOREIGN KEY (`collection_id`) REFERENCES `collections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_product_collections_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Relación entre productos y colecciones';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_collections`
--

LOCK TABLES `product_collections` WRITE;
/*!40000 ALTER TABLE `product_collections` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_collections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_color_variants`
--

DROP TABLE IF EXISTS `product_color_variants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_color_variants` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `color_id` int DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fk_product_color_variants_product` (`product_id`),
  KEY `fk_product_color_variants_color` (`color_id`),
  CONSTRAINT `fk_product_color_variants_color` FOREIGN KEY (`color_id`) REFERENCES `colors` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_product_color_variants_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_color_variants`
--

LOCK TABLES `product_color_variants` WRITE;
/*!40000 ALTER TABLE `product_color_variants` DISABLE KEYS */;
INSERT INTO `product_color_variants` VALUES (25,71,2,1),(26,71,18,0);
/*!40000 ALTER TABLE `product_color_variants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_images`
--

DROP TABLE IF EXISTS `product_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_images` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `color_variant_id` int DEFAULT NULL,
  `image_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `alt_text` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `order` int DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `is_primary` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fk_product_images_product` (`product_id`),
  KEY `fk_product_images_color_variant` (`color_variant_id`),
  CONSTRAINT `fk_product_images_color_variant` FOREIGN KEY (`color_variant_id`) REFERENCES `product_color_variants` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_product_images_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=96 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_images`
--

LOCK TABLES `product_images` WRITE;
/*!40000 ALTER TABLE `product_images` DISABLE KEYS */;
INSERT INTO `product_images` VALUES (95,71,NULL,'uploads/productos/69150408e8b9c_687c4f85cd486_conjunto_niño2.jpg','Ropa deportiva - Imagen principal',0,'2025-11-12 17:02:48',1);
/*!40000 ALTER TABLE `product_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_questions`
--

DROP TABLE IF EXISTS `product_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_questions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `user_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `question` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_product_questions_product` (`product_id`),
  KEY `fk_product_questions_user` (`user_id`),
  CONSTRAINT `fk_product_questions_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_product_questions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_questions`
--

LOCK TABLES `product_questions` WRITE;
/*!40000 ALTER TABLE `product_questions` DISABLE KEYS */;
INSERT INTO `product_questions` VALUES (2,71,'6861e06ddcf49','es de calidad?','2025-11-17 22:42:43');
/*!40000 ALTER TABLE `product_questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_reviews`
--

DROP TABLE IF EXISTS `product_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_reviews` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `user_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `order_id` int DEFAULT NULL COMMENT 'Para verificar compra',
  `rating` tinyint(1) NOT NULL COMMENT '1-5 estrellas',
  `title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `images` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'JSON de imágenes subidas',
  `is_verified` tinyint(1) DEFAULT '0' COMMENT 'Compra verificada',
  `is_approved` tinyint(1) DEFAULT '1' COMMENT 'Moderación',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_product_reviews_product` (`product_id`),
  KEY `fk_product_reviews_order` (`order_id`),
  KEY `fk_product_reviews_user` (`user_id`),
  CONSTRAINT `fk_product_reviews_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_product_reviews_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_product_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_reviews`
--

LOCK TABLES `product_reviews` WRITE;
/*!40000 ALTER TABLE `product_reviews` DISABLE KEYS */;
INSERT INTO `product_reviews` VALUES (1,71,'6861e06ddcf49',8,5,'excelente','me encanta',NULL,1,1,'2025-11-17 08:52:07','2025-11-17 08:52:07');
/*!40000 ALTER TABLE `product_reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_size_variants`
--

DROP TABLE IF EXISTS `product_size_variants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_size_variants` (
  `id` int NOT NULL AUTO_INCREMENT,
  `color_variant_id` int NOT NULL,
  `size_id` int DEFAULT NULL,
  `sku` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `barcode` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `compare_price` decimal(10,2) DEFAULT NULL,
  `quantity` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `fk_product_size_variants_color_variant` (`color_variant_id`),
  KEY `fk_product_size_variants_size` (`size_id`),
  CONSTRAINT `fk_product_size_variants_color_variant` FOREIGN KEY (`color_variant_id`) REFERENCES `product_color_variants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_product_size_variants_size` FOREIGN KEY (`size_id`) REFERENCES `sizes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_size_variants`
--

LOCK TABLES `product_size_variants` WRITE;
/*!40000 ALTER TABLE `product_size_variants` DISABLE KEYS */;
INSERT INTO `product_size_variants` VALUES (34,25,1,'O',NULL,35000.00,NULL,35,1),(35,26,2,'P',NULL,45000.00,NULL,45,1);
/*!40000 ALTER TABLE `product_size_variants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `productos_auditoria`
--

DROP TABLE IF EXISTS `productos_auditoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `productos_auditoria` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `accion` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Creado',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `productos_auditoria`
--

LOCK TABLES `productos_auditoria` WRITE;
/*!40000 ALTER TABLE `productos_auditoria` DISABLE KEYS */;
INSERT INTO `productos_auditoria` VALUES (1,'Camiseta','Creado','2025-09-23 14:05:59','2025-09-23 14:05:59'),(2,'Pantalón','Creado','2025-09-23 14:05:59','2025-09-23 14:05:59');
/*!40000 ALTER TABLE `productos_auditoria` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `tr_insert_producto` BEFORE INSERT ON `productos_auditoria` FOR EACH ROW BEGIN
    SET NEW.created_at = CURRENT_TIMESTAMP;
    SET NEW.accion = 'Creado';
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `tr_update_producto` BEFORE UPDATE ON `productos_auditoria` FOR EACH ROW BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
    SET NEW.accion = 'Modificado';
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `tr_delete_producto` BEFORE DELETE ON `productos_auditoria` FOR EACH ROW BEGIN
    INSERT INTO eliminaciones_auditoria (nombre)
    VALUES (OLD.nombre);
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `brand` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `gender` enum('niño','niña','bebe','unisex') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'unisex',
  `collection` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `material` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `care_instructions` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `compare_price` decimal(10,2) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `category_id` int NOT NULL,
  `collection_id` int DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=72 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (71,'Ropa deportiva','ropa-deportiva','','angelow','niño',NULL,'','',45000.00,35000.00,4,NULL,1,1,'2025-11-12 17:02:48','2025-11-17 23:20:44');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `question_answers`
--

DROP TABLE IF EXISTS `question_answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `question_answers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `question_id` int NOT NULL,
  `user_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `answer` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `is_seller` tinyint(1) DEFAULT '0' COMMENT '1=respuesta del vendedor',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_question_answers_question` (`question_id`),
  KEY `fk_question_answers_user` (`user_id`),
  CONSTRAINT `fk_question_answers_question` FOREIGN KEY (`question_id`) REFERENCES `product_questions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_question_answers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `question_answers`
--

LOCK TABLES `question_answers` WRITE;
/*!40000 ALTER TABLE `question_answers` DISABLE KEYS */;
/*!40000 ALTER TABLE `question_answers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `review_votes`
--

DROP TABLE IF EXISTS `review_votes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `review_votes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `review_id` int NOT NULL,
  `user_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `is_helpful` tinyint(1) NOT NULL COMMENT '1=útil, 0=no útil',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_review_votes_review` (`review_id`),
  KEY `fk_review_votes_user` (`user_id`),
  CONSTRAINT `fk_review_votes_review` FOREIGN KEY (`review_id`) REFERENCES `product_reviews` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_review_votes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `review_votes`
--

LOCK TABLES `review_votes` WRITE;
/*!40000 ALTER TABLE `review_votes` DISABLE KEYS */;
/*!40000 ALTER TABLE `review_votes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `search_history`
--

DROP TABLE IF EXISTS `search_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `search_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `search_term` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `search_history`
--

LOCK TABLES `search_history` WRITE;
/*!40000 ALTER TABLE `search_history` DISABLE KEYS */;
INSERT INTO `search_history` VALUES (1,'6861e06ddcf49','da','2025-07-17 19:11:43'),(2,'6861e06ddcf49','ropa deportiva de niños','2025-07-21 17:16:30'),(3,'6861e06ddcf49','ropa','2025-11-17 16:45:58'),(4,'6861e06ddcf49','ropa','2025-11-17 16:45:58'),(5,'6861e06ddcf49','Ropa deportiva','2025-10-20 12:52:46'),(6,'6861e06ddcf49','ropa niña','2025-09-23 09:09:03'),(7,'68d315cf194ba','ropa','2025-09-23 16:52:27'),(8,'68d315cf194ba','ropa deportiva','2025-09-23 16:52:36'),(9,'6861e06ddcf49','ropa deportica','2025-10-13 11:30:54'),(10,'6860007924a6a','ropa','2025-11-11 15:53:07'),(11,'6861e06ddcf49','ropa}','2025-11-12 09:08:03'),(12,'691b491806be8','ropa','2025-11-17 11:24:08');
/*!40000 ALTER TABLE `search_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('492wkTKhjknXbJuNxMvFyEKy9mPW7HKgBaVUdteG',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiUm00TEl1bm0wY21yZ0NFWVBldG5OUkp3SU02eVBZeXczR0wwOFJYQyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9sb2dpbiI7czo1OiJyb3V0ZSI7czoxNToiY3VzdG9tZXJzLmxvZ2luIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1763184229),('DBnwg3WEDpilc2ICO9anDS7dGkY7IBHxgllFh9CA','6860007924a6a','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0','YTo1OntzOjY6Il90b2tlbiI7czo0MDoiRVdQYzFaOFVIU3pNYXUyekdvZFBHMHhYY3dGeGRocVl2RW1TNmVERCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9taS1jdWVudGEiO3M6NToicm91dGUiO3M6MTk6ImN1c3RvbWVycy5kYXNoYm9hcmQiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjM6InVybCI7YTowOnt9czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO3M6MTM6IjY4NjAwMDc5MjRhNmEiO30=',1763240781),('uClKxuRaajJdgPKCkT18jnJ7zKdjDOttQf4uT9wV','6861e06ddcf49','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiU3J4SkRLaGNiYW9VaEhWOWJta1VlbzN6SlMzYkt5b1NvZXZLU0psRiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9taS1jdWVudGEiO3M6NToicm91dGUiO3M6MTk6ImN1c3RvbWVycy5kYXNoYm9hcmQiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7czoxMzoiNjg2MWUwNmRkY2Y0OSI7fQ==',1763236709),('VQYTgoplVbIgD7jvh6kHngtN8IWCOqMWjeNxvo8e',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiYkhXa2Vack45WG8zY3hpWDRwNWJwb1h2VUpZMmllbVBYSVBtVzdpTiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9sb2dpbiI7czo1OiJyb3V0ZSI7czoxNToiY3VzdG9tZXJzLmxvZ2luIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1763228307);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shipping_methods`
--

DROP TABLE IF EXISTS `shipping_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shipping_methods` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `base_cost` decimal(10,2) NOT NULL DEFAULT '0.00',
  `delivery_time` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `free_shipping_threshold` decimal(10,2) DEFAULT NULL,
  `available_cities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'JSON array de ciudades disponibles',
  `estimated_days_min` int DEFAULT '1',
  `estimated_days_max` int DEFAULT '3',
  `city` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Medellín',
  `free_shipping_minimum` decimal(10,2) DEFAULT NULL,
  `icon` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'fas fa-truck',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shipping_methods`
--

LOCK TABLES `shipping_methods` WRITE;
/*!40000 ALTER TABLE `shipping_methods` DISABLE KEYS */;
INSERT INTO `shipping_methods` VALUES (1,'Envío Rápido','Entrega en 24-48 horas',15000.00,'1-2 días hábiles',1,'2025-09-28 09:33:13','2025-10-04 17:00:58',NULL,NULL,1,3,'Medellín',0.00,'fas fa-shipping-fast'),(2,'Envío Estándar','Entrega en 3-5 días hábiles',8000.00,'3-5 días hábiles',1,'2025-09-28 09:33:13','2025-10-04 17:01:25',NULL,NULL,1,3,'Medellín',0.00,'fas fa-truck'),(4,'Recogida en Tienda','Recogida en punto físico',0.00,'Inmediato',1,'2025-09-28 09:33:13','2025-10-04 17:00:01',NULL,NULL,1,3,'Medellín',0.00,'fas fa-walking');
/*!40000 ALTER TABLE `shipping_methods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shipping_price_rules`
--

DROP TABLE IF EXISTS `shipping_price_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shipping_price_rules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `min_price` decimal(10,2) NOT NULL,
  `max_price` decimal(10,2) DEFAULT NULL,
  `shipping_cost` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shipping_price_rules`
--

LOCK TABLES `shipping_price_rules` WRITE;
/*!40000 ALTER TABLE `shipping_price_rules` DISABLE KEYS */;
INSERT INTO `shipping_price_rules` VALUES (1,50000.00,100000.00,8000.00,1,'2025-07-27 15:17:16','2025-07-27 15:21:17'),(2,0.00,49999.00,20000.00,1,'2025-07-27 15:19:03','2025-07-27 15:19:03');
/*!40000 ALTER TABLE `shipping_price_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `site_settings`
--

DROP TABLE IF EXISTS `site_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `site_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `category` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'general',
  `updated_by` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_settings`
--

LOCK TABLES `site_settings` WRITE;
/*!40000 ALTER TABLE `site_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `site_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sizes`
--

DROP TABLE IF EXISTS `sizes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sizes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sizes`
--

LOCK TABLES `sizes` WRITE;
/*!40000 ALTER TABLE `sizes` DISABLE KEYS */;
INSERT INTO `sizes` VALUES (1,'XS','Extra Small',1,'2025-06-21 20:47:14'),(2,'S','Small',1,'2025-06-21 20:47:14'),(3,'M','Medium',1,'2025-06-21 20:47:14'),(4,'L','Large',1,'2025-06-21 20:47:14'),(5,'XL','Extra Large',1,'2025-06-21 20:47:14'),(6,'XXL','Double Extra Large',1,'2025-06-21 20:47:14'),(7,'3XL','Triple Extra Large',1,'2025-06-21 20:47:14');
/*!40000 ALTER TABLE `sizes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sliders`
--

DROP TABLE IF EXISTS `sliders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sliders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Título del slide',
  `subtitle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Subtítulo o descripción',
  `image` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Ruta de la imagen',
  `link` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'URL de destino al hacer clic',
  `order_position` int NOT NULL DEFAULT '0' COMMENT 'Orden de visualización',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1 = Activo, 0 = Inactivo',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tabla para gestionar slides del carousel principal';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sliders`
--

LOCK TABLES `sliders` WRITE;
/*!40000 ALTER TABLE `sliders` DISABLE KEYS */;
INSERT INTO `sliders` VALUES (1,'Bienvenido a Angelow','Moda infantil de calidad','uploads/sliders/slider_1762037218_83c0a163.jpg','/tienda',1,1,'2025-11-01 17:20:17','2025-11-01 17:46:58'),(2,'Nueva Colección Verano','Descubre las últimas tendencias','uploads/sliders/slider_1762037457_eae6c808.jpg','/tienda?collection=verano',2,1,'2025-11-01 17:20:17','2025-11-01 17:50:57'),(3,'Ofertas Especiales','Hasta 30% de descuento','uploads/sliders/slider_1762037480_11f1960c.jpg','/tienda?offers=true',3,1,'2025-11-01 17:20:17','2025-11-01 17:51:20');
/*!40000 ALTER TABLE `sliders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_history`
--

DROP TABLE IF EXISTS `stock_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `variant_id` int NOT NULL,
  `user_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `previous_qty` int NOT NULL,
  `new_qty` int NOT NULL,
  `operation` enum('add','subtract','set','transfer_in','transfer_out') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_history`
--

LOCK TABLES `stock_history` WRITE;
/*!40000 ALTER TABLE `stock_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_addresses`
--

DROP TABLE IF EXISTS `user_addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_addresses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `address_type` enum('casa','apartamento','oficina','otro') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'casa',
  `alias` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `recipient_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `recipient_phone` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `complement` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `neighborhood` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `building_type` enum('casa','apartamento','edificio','conjunto','local') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'casa',
  `building_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `apartment_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `delivery_instructions` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `is_default` tinyint(1) DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `gps_latitude` decimal(10,8) DEFAULT NULL,
  `gps_longitude` decimal(11,8) DEFAULT NULL,
  `gps_accuracy` decimal(10,2) DEFAULT NULL,
  `gps_timestamp` datetime DEFAULT NULL,
  `gps_used` tinyint(1) DEFAULT '0' COMMENT 'Indica si se usó GPS (1) o no (0)',
  PRIMARY KEY (`id`),
  KEY `fk_user_addresses_user` (`user_id`),
  CONSTRAINT `fk_user_addresses_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_addresses`
--

LOCK TABLES `user_addresses` WRITE;
/*!40000 ALTER TABLE `user_addresses` DISABLE KEYS */;
INSERT INTO `user_addresses` VALUES (2,'6861e06ddcf49','oficina','Trabajo','Braian Oquendo','3013636902','Cra 16D #57 B 163','Bloque 3','Belen','edificio','El miranda','210','llamar antes de llegar',0,1,'2025-07-13 17:18:49','2025-11-12 08:27:40',NULL,NULL,NULL,NULL,0),(3,'68d315cf194ba','apartamento','casa','leidy','428239348923','cra 16d','bloque2','belen','apartamento','poblado','427823','irwokojr',0,1,'2025-09-23 16:50:50','2025-09-23 16:51:39',NULL,NULL,NULL,NULL,0),(4,'68d315cf194ba','casa','casa22','leidy','428239348923','cra 16d','bloque2','belen','apartamento','poblado','427823','mkfwkljrlwkr',1,1,'2025-09-23 16:51:36','2025-09-23 16:51:39',NULL,NULL,NULL,NULL,0),(8,'6861e06ddcf49','casa','casa','leidy','428239348923','Calle 63A',NULL,'Comuna 8 - Villa Hermosa','casa',NULL,NULL,NULL,1,1,'2025-10-13 11:56:49','2025-11-12 08:27:40',6.25617528,-75.55546772,NULL,'2025-10-13 11:56:49',1),(10,'68d315bcd96ef','casa','Casa Principal','Usuario de Prueba','3001234567','Calle 123 #45-67',NULL,'Centro','casa',NULL,NULL,NULL,1,1,'2025-11-12 09:10:58','2025-11-12 09:10:58',NULL,NULL,NULL,NULL,0);
/*!40000 ALTER TABLE `user_addresses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_applied_discounts`
--

DROP TABLE IF EXISTS `user_applied_discounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_applied_discounts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `discount_code_id` int NOT NULL,
  `discount_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `applied_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime NOT NULL,
  `is_used` tinyint(1) DEFAULT '0',
  `used_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_user_applied_discounts_user` (`user_id`),
  KEY `fk_user_applied_discounts_code` (`discount_code_id`),
  CONSTRAINT `fk_user_applied_discounts_code` FOREIGN KEY (`discount_code_id`) REFERENCES `discount_codes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_applied_discounts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_applied_discounts`
--

LOCK TABLES `user_applied_discounts` WRITE;
/*!40000 ALTER TABLE `user_applied_discounts` DISABLE KEYS */;
INSERT INTO `user_applied_discounts` VALUES (3,'6861e06ddcf49',15,'E20FA9C5',42000.00,'2025-10-04 23:16:59','2025-11-03 23:16:59',1,'2025-10-05 00:18:55'),(4,'6861e06ddcf49',15,'E20FA9C5',42000.00,'2025-10-04 23:22:13','2025-11-03 23:22:13',1,'2025-10-04 23:23:35'),(5,'6861e06ddcf49',15,'E20FA9C5',42000.00,'2025-10-04 23:23:35','2025-11-03 23:23:35',1,'2025-10-04 23:45:21'),(6,'6861e06ddcf49',15,'E20FA9C5',42000.00,'2025-10-04 23:45:21','2025-11-03 23:45:21',1,'2025-10-04 23:50:05'),(7,'6861e06ddcf49',15,'E20FA9C5',42000.00,'2025-10-04 23:50:05','2025-11-03 23:50:05',1,'2025-10-05 00:18:55'),(8,'6861e06ddcf49',15,'E20FA9C5',7000.00,'2025-10-09 08:58:35','2025-11-08 08:58:35',1,'2025-10-09 08:58:45'),(9,'6861e06ddcf49',15,'E20FA9C5',7000.00,'2025-10-09 08:58:45','2025-11-08 08:58:45',1,'2025-10-09 08:59:05'),(11,'6861e06ddcf49',15,'E20FA9C5',7000.00,'2025-11-15 22:03:18','2025-12-15 22:03:18',1,'2025-11-15 22:04:38'),(12,'6861e06ddcf49',15,'E20FA9C5',7000.00,'2025-11-15 22:04:38','2025-12-15 22:04:38',1,'2025-11-15 22:17:00');
/*!40000 ALTER TABLE `user_applied_discounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `role` enum('customer','admin') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'customer',
  `is_blocked` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_access` datetime DEFAULT NULL,
  `remember_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tabla de usuarios. Rol delivery eliminado - gestionado en app separada desde Nov 2025';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES ('6860007924a6a','Braian','braianoquen@gmail.com',NULL,'$2y$10$safkUgrODd3iixhDIq/y9eG7RnlUq.I3MAq3OsG4PXOsT7bZoss76',NULL,'admin',0,'2025-06-28 09:47:21','2025-11-19 15:44:49','2025-11-19 15:44:49',NULL,NULL),('6861e06ddcf49','Braian','braianoquendurango@gmail.com','3013636902','$2y$10$K5B1CBsezIVKb2osCQrgEuTwIr.JMvG2EVPUYZqIhS9yzuboS8prq','6861e06ddcf49_1763441031_8ddfda41.jpg','customer',0,'2025-06-29 19:55:10','2025-11-19 12:52:26','2025-11-19 12:52:24',NULL,NULL),('6862b7448112f','Juan','braianoquen2@gmail.com',NULL,'$2y$10$lIkReeDLfMBHL7Mj2Vqrk.0LhoLlVboNNliNulgXzEiIrrexwMtrS',NULL,'customer',1,'2025-06-30 11:11:48','2025-11-07 06:31:34','2025-11-01 18:41:22',NULL,NULL),('68d315bcd96ef','andres','andres90@gmail.com','3013636902','$2y$10$vhD59LnPpeGeVIyLVJjdV.hHfKop6S3r2EyQ.mKbQ.4YzFcZ5YeJq',NULL,'customer',0,'2025-09-23 16:48:45','2025-09-23 16:48:49','2025-09-23 16:48:49',NULL,NULL),('68d315cf194ba','Braian Andres Oquendo Durango','tracongames2@gmail.com',NULL,NULL,NULL,'customer',0,'2025-09-23 16:49:03','2025-09-23 16:55:02','2025-09-23 16:55:02',NULL,NULL),('691b491806be8','andres','andres80@gmail.com','3013636902','$2y$10$MbPfBZ9MPLYlxrh/1WKciuJq9piCVcCa.7xSB9ttV5FlbvSU/RVFS',NULL,'customer',0,'2025-11-17 11:11:04','2025-11-17 11:44:17','2025-11-17 11:44:17',NULL,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `auditoria_usuario_insert` AFTER INSERT ON `users` FOR EACH ROW BEGIN
  INSERT INTO audit_users (usuario_id, accion, usuario_modificador, sql_usuario, detalles)
  VALUES (
    NEW.id, 
    'INSERT', 
    NEW.id, -- En una inserción, el usuario que se crea es el mismo que "se modifica"
    CURRENT_USER(),       
    CONCAT('Se creó el usuario: ', NEW.name, ' (', NEW.email, '). Rol: ', NEW.role)
  );
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `auditoria_usuario_update` AFTER UPDATE ON `users` FOR EACH ROW BEGIN
  DECLARE cambios TEXT DEFAULT '';
  
  -- Verificar cambios en los campos principales
  IF (NEW.name != OLD.name) THEN
    SET cambios = CONCAT(cambios, 'Nombre: ', OLD.name, ' → ', NEW.name, '. ');
  END IF;
  
  IF (NEW.email != OLD.email) THEN
    SET cambios = CONCAT(cambios, 'Email: ', OLD.email, ' → ', NEW.email, '. ');
  END IF;
  
  IF (NEW.role != OLD.role) THEN
    SET cambios = CONCAT(cambios, 'Rol: ', OLD.role, ' → ', NEW.role, '. ');
  END IF;
  
  IF (NEW.is_blocked != OLD.is_blocked) THEN
    SET cambios = CONCAT(cambios, 'Bloqueo: ', OLD.is_blocked, ' → ', NEW.is_blocked, '. ');
  END IF;
  
  IF (NEW.phone != OLD.phone OR (NEW.phone IS NULL AND OLD.phone IS NOT NULL) OR (NEW.phone IS NOT NULL AND OLD.phone IS NULL)) THEN
    SET cambios = CONCAT(cambios, 'Teléfono: ', COALESCE(OLD.phone, 'NULL'), ' → ', COALESCE(NEW.phone, 'NULL'), '. ');
  END IF;
  
  -- Solo registrar si hubo cambios relevantes
  IF (LENGTH(cambios) > 0) THEN
    INSERT INTO audit_users (usuario_id, accion, usuario_modificador, sql_usuario, detalles)
    VALUES (
      NEW.id, 
      'UPDATE', 
      NEW.id, -- Asumiendo que el usuario se modifica a sí mismo o hay otro sistema de tracking
      CURRENT_USER(),    
      CONCAT('Usuario actualizado. Cambios: ', cambios)
    );
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `auditoria_usuario_delete` BEFORE DELETE ON `users` FOR EACH ROW BEGIN
  INSERT INTO audit_users (usuario_id, accion, usuario_modificador, sql_usuario, detalles)
  VALUES (
    OLD.id, 
    'DELETE', 
    OLD.id, -- El usuario que se elimina
    CURRENT_USER(),        
    CONCAT('Se eliminó el usuario: ', OLD.name, ' (', OLD.email, '). Rol: ', OLD.role)
  );
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `variant_images`
--

DROP TABLE IF EXISTS `variant_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `variant_images` (
  `id` int NOT NULL AUTO_INCREMENT,
  `color_variant_id` int NOT NULL,
  `product_id` int NOT NULL,
  `image_id` int DEFAULT NULL,
  `image_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `alt_text` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `order` int DEFAULT '0',
  `is_primary` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_variant_images_color` (`color_variant_id`),
  KEY `fk_variant_images_product` (`product_id`),
  KEY `fk_variant_images_image` (`image_id`),
  CONSTRAINT `fk_variant_images_color` FOREIGN KEY (`color_variant_id`) REFERENCES `product_color_variants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_variant_images_image` FOREIGN KEY (`image_id`) REFERENCES `product_images` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_variant_images_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=79 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `variant_images`
--

LOCK TABLES `variant_images` WRITE;
/*!40000 ALTER TABLE `variant_images` DISABLE KEYS */;
INSERT INTO `variant_images` VALUES (73,25,71,NULL,'uploads/productos/69150408e9140_687c4f85cd486_conjunto_niño2.jpg','Ropa deportiva - Imagen 1',0,1,'2025-11-17 23:20:44'),(74,25,71,NULL,'uploads/productos/69150408e974b_687c4f85cdaf5_deportivo.jpg','Ropa deportiva - Imagen 2',1,0,'2025-11-17 23:20:44'),(75,26,71,NULL,'uploads/productos/69150408e9d55_687c4f8612d1c_coleccion primavera.jpg','Ropa deportiva - Imagen 1',0,1,'2025-11-17 23:20:44'),(76,26,71,NULL,'uploads/productos/69150408e9fac_687c4f8623faa_conjunto_niño.jpg','Ropa deportiva - Imagen 2',1,0,'2025-11-17 23:20:44'),(77,26,71,NULL,'uploads/productos/69150408ea213_687c4f8624a68_deportivo2.jpg','Ropa deportiva - Imagen 3',2,0,'2025-11-17 23:20:44'),(78,26,71,NULL,'uploads/productos/69150408ea507_687c4f8623137_conjunto_niña2.jpg','Ropa deportiva - Imagen 4',3,0,'2025-11-17 23:20:44');
/*!40000 ALTER TABLE `variant_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wishlist`
--

DROP TABLE IF EXISTS `wishlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wishlist` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `product_id` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_wishlist_user` (`user_id`),
  KEY `fk_wishlist_product` (`product_id`),
  CONSTRAINT `fk_wishlist_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wishlist_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wishlist`
--

LOCK TABLES `wishlist` WRITE;
/*!40000 ALTER TABLE `wishlist` DISABLE KEYS */;
INSERT INTO `wishlist` VALUES (1,'6860007924a6a',71,'2025-11-15 16:55:46'),(4,'6861e06ddcf49',71,'2025-11-17 22:18:39');
/*!40000 ALTER TABLE `wishlist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'angelow'
--

--
-- Dumping routines for database 'angelow'
--
/*!50003 DROP PROCEDURE IF EXISTS `add_category` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `add_category`(IN `cat_name` VARCHAR(100))
BEGIN
    INSERT INTO categories(name) VALUES (cat_name);
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `CleanExpiredDiscounts` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `CleanExpiredDiscounts`()
BEGIN
    -- Eliminar descuentos aplicados que expiraron hace más de 2 meses
    DELETE FROM user_applied_discounts 
    WHERE expires_at < DATE_SUB(NOW(), INTERVAL 2 MONTH);
    
    -- También puedes limpiar códigos de descuento expirados si lo deseas
    UPDATE discount_codes 
    SET is_active = 0 
    WHERE end_date IS NOT NULL AND end_date < NOW();
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `GenerateUserNotifications` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `GenerateUserNotifications`(IN `p_user_id` VARCHAR(20))
BEGIN
    -- Declarar variables
    DECLARE v_notification_text TEXT DEFAULT '';
    DECLARE v_order_count INT DEFAULT 0;
    DECLARE v_cart_item_count INT DEFAULT 0;
    DECLARE v_current_date DATETIME DEFAULT CURRENT_TIMESTAMP;

    -- Crear tabla temporal para almacenar notificaciones
    CREATE TEMPORARY TABLE IF NOT EXISTS temp_notifications (
        message TEXT NOT NULL
    );

    -- Contar pedidos pendientes (status no completado)
    SELECT COUNT(*) INTO v_order_count
    FROM orders o
    WHERE o.user_id = p_user_id 
    AND o.status NOT IN ('completed', 'cancelled');

    -- Contar artículos en el carrito
    SELECT COUNT(*) INTO v_cart_item_count
    FROM cart_items ci
    JOIN carts c ON ci.cart_id = c.id
    WHERE c.user_id = p_user_id;

    -- Insertar notificación de encabezado
    INSERT INTO temp_notifications (message)
    VALUES (CONCAT('Notificaciones para ', (SELECT name FROM users WHERE id = p_user_id), ' (', p_user_id, '):\n'));

    -- Notificación de pedidos pendientes
    IF v_order_count > 0 THEN
        INSERT INTO temp_notifications (message)
        VALUES (CONCAT('- Tienes ', v_order_count, ' pedido(s) pendiente(s) por procesar.\n'));
        
        -- Verificar pedidos próximos a vencer (ejemplo: dentro de 3 días)
        INSERT INTO temp_notifications (message)
        SELECT CONCAT('- Atención: El pedido #', o.order_number, ' vence el ', DATE(o.expires_at), '.\n')
        FROM orders o
        WHERE o.user_id = p_user_id 
        AND o.status NOT IN ('completed', 'cancelled')
        AND DATEDIFF(o.expires_at, v_current_date) <= 3
        AND DATEDIFF(o.expires_at, v_current_date) >= 0;
    ELSE
        INSERT INTO temp_notifications (message)
        VALUES ('- No tienes pedidos pendientes.\n');
    END IF;

    -- Notificación de artículos en el carrito
    IF v_cart_item_count > 0 THEN
        INSERT INTO temp_notifications (message)
        VALUES (CONCAT('- Tienes ', v_cart_item_count, ' artículo(s) en tu carrito. ¡Completa tu compra!\n'));
    ELSE
        INSERT INTO temp_notifications (message)
        VALUES ('- Tu carrito está vacío.\n');
    END IF;

    -- Devolver todas las notificaciones
    SELECT GROUP_CONCAT(message SEPARATOR '') AS notifications
    FROM temp_notifications;

    -- Limpiar tabla temporal
    DROP TEMPORARY TABLE IF EXISTS temp_notifications;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `GetFilteredProducts` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetFilteredProducts`(IN `p_search_query` VARCHAR(255), IN `p_category_id` INT, IN `p_gender` VARCHAR(10), IN `p_min_price` DECIMAL(10,2), IN `p_max_price` DECIMAL(10,2), IN `p_sort_by` VARCHAR(20), IN `p_limit` INT, IN `p_offset` INT, IN `p_user_id` VARCHAR(20))
BEGIN
    
    SELECT
        p.id,
        p.name,
        p.slug,
        p.description,
        p.gender,
        p.category_id,
        p.is_featured,
        p.created_at,
        (SELECT pi.image_path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) as primary_image,
        MIN(psv.price) as min_price,
        MAX(psv.price) as max_price,
        IFNULL((SELECT COUNT(*) FROM wishlist w WHERE w.user_id = p_user_id COLLATE utf8mb4_general_ci AND w.product_id = p.id), 0) as is_favorite,
        IFNULL((SELECT AVG(rating) FROM product_reviews pr WHERE pr.product_id = p.id AND pr.is_approved = 1), 0) as avg_rating,
        IFNULL((SELECT COUNT(*) FROM product_reviews pr WHERE pr.product_id = p.id AND pr.is_approved = 1), 0) as review_count
    FROM
        products p
    LEFT JOIN
        product_color_variants pcv ON p.id = pcv.product_id
    LEFT JOIN
        product_size_variants psv ON pcv.id = psv.color_variant_id
    WHERE
        p.is_active = 1
        AND (p_search_query IS NULL OR p_search_query = '' OR p.name LIKE CONCAT('%', p_search_query, '%') COLLATE utf8mb4_general_ci OR p.description LIKE CONCAT('%', p_search_query, '%') COLLATE utf8mb4_general_ci)
        AND (p_category_id IS NULL OR p.category_id = p_category_id)
        AND (p_gender IS NULL OR p_gender = '' OR p.gender = p_gender COLLATE utf8mb4_general_ci)
        AND (p_min_price IS NULL OR psv.price >= p_min_price)
        AND (p_max_price IS NULL OR psv.price <= p_max_price)
    GROUP BY
        p.id
    ORDER BY
        CASE WHEN p_sort_by = 'price_asc' THEN MIN(psv.price) END ASC,
        CASE WHEN p_sort_by = 'price_desc' THEN MIN(psv.price) END DESC,
        CASE WHEN p_sort_by = 'name_asc' THEN p.name END ASC,
        CASE WHEN p_sort_by = 'name_desc' THEN p.name END DESC,
        CASE WHEN p_sort_by = 'popular' THEN p.is_featured END DESC,
        p.is_featured DESC,
        p.created_at DESC
    LIMIT p_limit OFFSET p_offset;

    
    SELECT COUNT(DISTINCT p.id) as total
    FROM products p
    LEFT JOIN product_color_variants pcv ON p.id = pcv.product_id
    LEFT JOIN product_size_variants psv ON pcv.id = psv.color_variant_id
    WHERE p.is_active = 1
        AND (p_search_query IS NULL OR p_search_query = '' OR p.name LIKE CONCAT('%', p_search_query, '%') COLLATE utf8mb4_general_ci OR p.description LIKE CONCAT('%', p_search_query, '%') COLLATE utf8mb4_general_ci)
        AND (p_category_id IS NULL OR p.category_id = p_category_id)
        AND (p_gender IS NULL OR p_gender = '' OR p.gender = p_gender COLLATE utf8mb4_general_ci)
        AND (p_min_price IS NULL OR psv.price >= p_min_price)
        AND (p_max_price IS NULL OR psv.price <= p_max_price);
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `GetOrderHistory` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetOrderHistory`(IN `p_order_id` INT)
BEGIN
    SELECT 
        osh.*,
        o.order_number,
        u.name as changed_by_full_name,
        u.role as changed_by_role
    FROM order_status_history osh
    LEFT JOIN orders o ON osh.order_id = o.id
    LEFT JOIN users u ON osh.changed_by = u.id
    WHERE osh.order_id = p_order_id
    ORDER BY osh.created_at DESC, osh.id DESC;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_categories` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_categories`()
BEGIN
    SELECT * FROM categories;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `SearchProductsAndTerms` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `SearchProductsAndTerms`(IN `search_term` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci, IN `user_id` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci)
BEGIN
        -- Primer conjunto de resultados: Productos coincidentes (máximo 5)
        SELECT 
            p.id,
            p.name,
            p.slug,
            COALESCE(pi.image_path, 'uploads/products/default-product.jpg') as image_path
        FROM products p
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        WHERE (
            p.name LIKE CONCAT('%', search_term, '%') COLLATE utf8mb4_general_ci
            OR p.description LIKE CONCAT('%', search_term, '%') COLLATE utf8mb4_general_ci
            OR p.brand LIKE CONCAT('%', search_term, '%') COLLATE utf8mb4_general_ci
        )
        AND p.is_active = 1
        ORDER BY 
            CASE 
                WHEN p.name LIKE CONCAT(search_term, '%') COLLATE utf8mb4_general_ci THEN 1
                WHEN p.name LIKE CONCAT('%', search_term, '%') COLLATE utf8mb4_general_ci THEN 2
                ELSE 3
            END,
            p.name
        LIMIT 5;

        -- Segundo conjunto de resultados: Términos de búsqueda sugeridos (nombres de productos)
        SELECT DISTINCT p.name as search_term_result
        FROM products p
        WHERE p.name LIKE CONCAT('%', search_term, '%') COLLATE utf8mb4_general_ci
        AND p.is_active = 1
        AND p.name IS NOT NULL
        AND p.name != ''
        LIMIT 6;
    END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-29 11:02:15
