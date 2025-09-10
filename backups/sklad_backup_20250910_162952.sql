-- MySQL dump 10.13  Distrib 9.3.0, for macos15.2 (arm64)
--
-- Host: localhost    Database: sklad
-- ------------------------------------------------------
-- Server version	9.3.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('laravel-cache-356a192b7913b04c54574d18c28d46e6395428ab','i:3;',1757348893),('laravel-cache-356a192b7913b04c54574d18c28d46e6395428ab:timer','i:1757348893;',1757348893),('laravel-cache-livewire-rate-limiter:59d6ad626907b5a0341aba51c3754cd265bffec5','i:3;',1757406797),('laravel-cache-livewire-rate-limiter:59d6ad626907b5a0341aba51c3754cd265bffec5:timer','i:1757406797;',1757406797);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
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
-- Table structure for table `clients`
--

DROP TABLE IF EXISTS `clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clients` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clients`
--

LOCK TABLES `clients` WRITE;
/*!40000 ALTER TABLE `clients` DISABLE KEYS */;
/*!40000 ALTER TABLE `clients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `companies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `legal_address` text COLLATE utf8mb4_unicode_ci,
  `postal_address` text COLLATE utf8mb4_unicode_ci,
  `phone_fax` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `general_director` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inn` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kpp` varchar(9) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ogrn` varchar(13) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `correspondent_account` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bik` varchar(9) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employees_count` int NOT NULL DEFAULT '0',
  `warehouses_count` int NOT NULL DEFAULT '0',
  `is_archived` tinyint(1) NOT NULL DEFAULT '0',
  `archived_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `companies_name_index` (`name`),
  KEY `companies_is_archived_index` (`is_archived`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `companies`
--

LOCK TABLES `companies` WRITE;
/*!40000 ALTER TABLE `companies` DISABLE KEYS */;
INSERT INTO `companies` VALUES (1,'ООО \"Тестовая компания 1\"','г. Москва, ул. Тестовая, д. 11','г. Москва, ул. Тестовая, д. 1','+7 (495) 123-45-67','Иванов Иван Иванович','info@test1.ru','1234567890','123456789','1234567890123','ПАО Сбербанк','40702810123456789012','30101810400000000225','044525225',0,0,0,NULL,'2025-09-01 16:19:25','2025-09-02 15:07:42',NULL),(2,'ООО \"Тестовая компания 2\"','г. Санкт-Петербург, ул. Тестовая, д. 2','г. Санкт-Петербург, ул. Тестовая, д. 2','+7 (812) 987-65-43','Петров Петр Петрович','info@test2.ru','0987654321','098765432','0987654321098','ПАО ВТБ','40702810987654321098','30101810700000000187','044525187',0,0,0,NULL,'2025-09-01 16:19:25','2025-09-01 16:19:25',NULL),(3,'вафыа','32','322332','232323','вафыа','ruslan@siraev.ru','2323232323','232323232','2323232323232','232323232323','23232323232323232323','23232323232323232323','232323232',0,0,1,'2025-09-01 19:01:12','2025-09-01 18:56:39','2025-09-01 19:01:15','2025-09-01 19:01:15'),(4,'fadsf','uiou','iouio','ououo','uoiuoi','iu@ua.ru','uiou','ioiuo','uou','oiu','oiuoiuouo','uiououo','uouou',0,0,0,NULL,'2025-09-02 01:17:12','2025-09-02 01:17:21','2025-09-02 01:17:21'),(5,'выафыва','323232','233223233223233223','232332','ывафыва','ruslan@siraev.ru','2332232332','233223233','2332232332232','233223233223233223','23233223233223233223','23322323322323322323','233223233',0,0,0,NULL,'2025-09-05 17:51:24','2025-09-05 17:51:30','2025-09-05 17:51:30');
/*!40000 ALTER TABLE `companies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `corrections`
--

DROP TABLE IF EXISTS `corrections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `corrections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `old_data` json NOT NULL,
  `new_data` json NOT NULL,
  `comment` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `corrections_product_id_foreign` (`product_id`),
  KEY `corrections_user_id_foreign` (`user_id`),
  CONSTRAINT `corrections_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `corrections_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `corrections`
--

LOCK TABLES `corrections` WRITE;
/*!40000 ALTER TABLE `corrections` DISABLE KEYS */;
/*!40000 ALTER TABLE `corrections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `discrepancies`
--

DROP TABLE IF EXISTS `discrepancies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `discrepancies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_in_transit_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` json DEFAULT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `old_quantity` int DEFAULT NULL,
  `new_quantity` int DEFAULT NULL,
  `old_color` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `new_color` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `old_size` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `new_size` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `old_weight` decimal(10,4) DEFAULT NULL,
  `new_weight` decimal(10,4) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `discrepancies_product_in_transit_id_foreign` (`product_in_transit_id`),
  KEY `discrepancies_user_id_foreign` (`user_id`),
  CONSTRAINT `discrepancies_product_in_transit_id_foreign` FOREIGN KEY (`product_in_transit_id`) REFERENCES `product_in_transit` (`id`),
  CONSTRAINT `discrepancies_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `discrepancies`
--

LOCK TABLES `discrepancies` WRITE;
/*!40000 ALTER TABLE `discrepancies` DISABLE KEYS */;
/*!40000 ALTER TABLE `discrepancies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
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
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
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
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
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
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2025_08_04_073700_create_companies_table',1),(5,'2025_08_04_073750_create_warehouses_table',1),(6,'2025_08_04_073800_add_role_and_company_fields_to_users_table',1),(7,'2025_08_04_074943_add_soft_deletes_to_companies_table',1),(8,'2025_08_04_081921_create_product_templates_table',1),(9,'2025_08_04_081950_create_product_attributes_table',1),(10,'2025_08_04_084326_create_products_table',1),(11,'2025_08_04_091754_create_product_in_transit_table',1),(12,'2025_08_04_092745_create_requests_table',1),(13,'2025_08_04_093907_create_sales_table',1),(14,'2025_08_04_095940_create_personal_access_tokens_table',1),(15,'2025_08_06_083504_fix_companies_data_length_before_constraints',1),(16,'2025_08_06_083505_update_companies_table_constraints',1),(17,'2025_08_06_083625_add_username_to_users_table_for_tests',1),(18,'2025_08_06_083626_fix_users_username_before_unique_constraint',1),(19,'2025_08_06_131428_make_attributes_nullable_in_products_table',1),(20,'2025_08_07_080322_add_attributes_to_requests_table',1),(21,'2025_08_07_090451_add_calculated_volume_to_requests_table',1),(22,'2025_08_07_093019_increase_calculated_volume_precision',1),(23,'2025_08_07_093039_increase_calculated_volume_precision_product_in_transits',1),(24,'2025_08_07_093057_increase_calculated_volume_precision_requests',1),(25,'2025_08_07_102038_update_sales_table_payment_fields',1),(26,'2025_08_07_132139_add_shipping_fields_to_product_in_transit_table',1),(27,'2025_08_07_135812_add_shipment_number_to_product_in_transit_table',1),(28,'2025_08_20_095108_fix_companies_nullable_fields',1),(29,'2025_08_20_104713_add_employees_count_and_warehouses_count_to_companies_table',1),(30,'2025_08_22_120000_add_status_to_products_table',1),(31,'2025_08_22_121000_add_shipping_fields_to_products_table',1),(32,'2025_08_28_204145_add_composite_product_key_to_sales_table',1),(33,'2025_08_28_220927_add_sold_quantity_to_products_table',1),(34,'2025_09_01_152420_create_producers_table',1),(35,'2025_09_01_152603_add_producer_id_to_products_table',1),(36,'2025_09_01_214533_create_clients_table',2),(37,'2025_09_02_085802_add_producer_id_to_product_in_transit_table',3),(38,'2025_09_02_161318_create_discrepancies_table',4),(39,'2025_09_03_135108_add_data_and_comment_to_discrepancies_table',5),(41,'2025_09_03_144900_create_corrections_table',6),(42,'2025_09_03_184123_create_corrections_table',7),(44,'2025_09_05_133307_add_reason_cancellation_to_sales_table',8),(46,'2025_09_08_152645_add_correction_fields_to_products_table',9);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
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
-- Table structure for table `producers`
--

DROP TABLE IF EXISTS `producers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `producers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Название производителя',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `producers_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `producers`
--

LOCK TABLES `producers` WRITE;
/*!40000 ALTER TABLE `producers` DISABLE KEYS */;
INSERT INTO `producers` VALUES (1,'самсуснг','2025-09-01 16:49:27','2025-09-01 16:49:27'),(2,'apple','2025-09-01 16:49:31','2025-09-01 16:49:31'),(3,'Xiaomi','2025-09-02 09:43:18','2025-09-02 09:43:18'),(7,'Heckfy','2025-09-08 05:07:17','2025-09-08 05:07:17');
/*!40000 ALTER TABLE `producers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_attributes`
--

DROP TABLE IF EXISTS `product_attributes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_attributes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_template_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `variable` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('number','text','select') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'number',
  `options` text COLLATE utf8mb4_unicode_ci,
  `unit` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT '0',
  `is_in_formula` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_attributes_product_template_id_variable_unique` (`product_template_id`,`variable`),
  KEY `product_attributes_product_template_id_sort_order_index` (`product_template_id`,`sort_order`),
  CONSTRAINT `product_attributes_product_template_id_foreign` FOREIGN KEY (`product_template_id`) REFERENCES `product_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=285 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_attributes`
--

LOCK TABLES `product_attributes` WRITE;
/*!40000 ALTER TABLE `product_attributes` DISABLE KEYS */;
INSERT INTO `product_attributes` VALUES (219,26,'1','a','number',NULL,'мм',1,1,'2025-09-05 17:02:55','2025-09-05 17:02:55',0),(220,26,'2','s','number',NULL,'мм',0,0,'2025-09-05 17:02:55','2025-09-05 17:02:55',0),(221,26,'3','f','number',NULL,'мм',1,1,'2025-09-05 17:02:55','2025-09-05 17:02:55',0),(267,25,'Высота','v','number',NULL,'мм',1,1,'2025-09-08 11:57:31','2025-09-08 11:57:31',0),(268,25,'Ширина','s','number',NULL,'мм',1,0,'2025-09-08 11:57:31','2025-09-08 11:57:31',0),(269,25,'Длина','d','number',NULL,'мм',1,0,'2025-09-08 11:57:31','2025-09-08 11:57:31',0),(270,25,'Тип','t','select','[\"v\",\"s\",\"d\"]',NULL,0,0,'2025-09-08 11:57:31','2025-09-08 11:57:31',0),(278,27,'1','a','number',NULL,NULL,1,1,'2025-09-08 13:39:59','2025-09-08 13:39:59',0),(279,27,'2','s','number',NULL,NULL,1,1,'2025-09-08 13:39:59','2025-09-08 13:39:59',0),(280,27,'3','d','number',NULL,NULL,1,1,'2025-09-08 13:39:59','2025-09-08 13:39:59',0),(281,27,'4','f','number',NULL,NULL,1,1,'2025-09-08 13:39:59','2025-09-08 13:39:59',0),(282,27,'5','g','number',NULL,NULL,1,1,'2025-09-08 13:39:59','2025-09-08 13:39:59',0),(283,27,'6','k','text',NULL,NULL,0,0,'2025-09-08 13:39:59','2025-09-08 13:39:59',0),(284,27,'выбор','l','select','[\"1dsf\",\"2sdafasd\",\"3asdfadsf\",\"4sadfasdf\",\"323\"]',NULL,0,0,'2025-09-08 13:39:59','2025-09-08 13:39:59',0);
/*!40000 ALTER TABLE `product_attributes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_in_transit`
--

DROP TABLE IF EXISTS `product_in_transit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_in_transit` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_template_id` bigint unsigned NOT NULL,
  `warehouse_id` bigint unsigned NOT NULL,
  `created_by` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `shipment_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `attributes` json NOT NULL,
  `calculated_volume` decimal(15,4) DEFAULT NULL,
  `quantity` int NOT NULL,
  `transport_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `producer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `producer_id` bigint unsigned DEFAULT NULL,
  `tracking_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expected_arrival_date` date NOT NULL,
  `actual_arrival_date` date DEFAULT NULL,
  `status` enum('ordered','in_transit','arrived','received','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ordered',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `document_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `shipping_location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shipping_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_in_transit_created_by_foreign` (`created_by`),
  KEY `product_in_transit_warehouse_id_status_index` (`warehouse_id`,`status`),
  KEY `product_in_transit_product_template_id_status_index` (`product_template_id`,`status`),
  KEY `product_in_transit_producer_index` (`producer`),
  KEY `product_in_transit_expected_arrival_date_index` (`expected_arrival_date`),
  KEY `product_in_transit_status_index` (`status`),
  KEY `product_in_transit_producer_id_foreign` (`producer_id`),
  CONSTRAINT `product_in_transit_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_in_transit_producer_id_foreign` FOREIGN KEY (`producer_id`) REFERENCES `producers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_in_transit_product_template_id_foreign` FOREIGN KEY (`product_template_id`) REFERENCES `product_templates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_in_transit_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_in_transit`
--

LOCK TABLES `product_in_transit` WRITE;
/*!40000 ALTER TABLE `product_in_transit` DISABLE KEYS */;
INSERT INTO `product_in_transit` VALUES (17,25,3,1,'Пиломатериалы: 121, 232, 343, d',NULL,NULL,'{\"id\": 540, \"name\": \"Пиломатериалы: 121, 232, 343, d\", \"notes\": null, \"status\": \"in_transit\", \"producer\": null, \"quantity\": 565, \"is_active\": 1, \"attributes\": \"{\\\"d\\\": \\\"343\\\", \\\"s\\\": \\\"232\\\", \\\"t\\\": \\\"d\\\", \\\"v\\\": \\\"121\\\"}\", \"created_at\": \"2025-09-08 08:08:13\", \"created_by\": 1, \"updated_at\": \"2025-09-08 14:11:47\", \"description\": null, \"producer_id\": 7, \"arrival_date\": \"2025-09-08\", \"warehouse_id\": 3, \"document_path\": null, \"shipping_date\": null, \"sold_quantity\": 550, \"tracking_number\": null, \"transport_number\": \"dfasf\", \"calculated_volume\": \"5440213.240000\", \"shipping_location\": null, \"actual_arrival_date\": null, \"product_template_id\": 25, \"expected_arrival_date\": null}',5440213.2400,565,'dfasf',NULL,7,NULL,'2025-09-15',NULL,'in_transit',NULL,NULL,1,'2025-09-08 11:11:47','2025-09-08 11:11:47','Склад','2025-09-08'),(18,25,1,1,'Пиломатериалы: 999, 999, 999, v',NULL,NULL,'{\"id\": 539, \"name\": \"Пиломатериалы: 999, 999, 999, v\", \"notes\": null, \"status\": \"in_transit\", \"producer\": null, \"quantity\": 221, \"is_active\": 1, \"attributes\": \"{\\\"d\\\": \\\"999\\\", \\\"s\\\": \\\"999\\\", \\\"t\\\": \\\"v\\\", \\\"v\\\": \\\"999\\\"}\", \"created_at\": \"2025-09-07 20:37:58\", \"created_by\": 1, \"updated_at\": \"2025-09-08 14:11:47\", \"description\": null, \"producer_id\": 1, \"arrival_date\": \"2025-09-07\", \"warehouse_id\": 1, \"document_path\": null, \"shipping_date\": null, \"sold_quantity\": 220, \"tracking_number\": null, \"transport_number\": \"хуй\", \"calculated_volume\": \"220337662.779000\", \"shipping_location\": null, \"actual_arrival_date\": null, \"product_template_id\": 25, \"expected_arrival_date\": null}',220337662.7790,221,'хуй',NULL,1,NULL,'2025-09-15',NULL,'in_transit',NULL,NULL,1,'2025-09-08 11:11:47','2025-09-08 11:11:47','Склад','2025-09-08'),(19,25,2,1,'Пиломатериалы: 103, 15, 39, d',NULL,'Тестовый товар #204','{\"id\": 442, \"name\": \"Пиломатериалы: 103, 15, 39, d\", \"notes\": null, \"status\": \"in_transit\", \"producer\": null, \"quantity\": 52, \"is_active\": 1, \"attributes\": \"{\\\"d\\\": 39, \\\"s\\\": 15, \\\"t\\\": \\\"d\\\", \\\"v\\\": 103}\", \"created_at\": \"2025-09-07 18:46:06\", \"created_by\": 1, \"updated_at\": \"2025-09-08 14:11:47\", \"description\": \"Тестовый товар #204\", \"producer_id\": 1, \"arrival_date\": \"2025-06-27\", \"warehouse_id\": 2, \"document_path\": null, \"shipping_date\": null, \"sold_quantity\": 38, \"tracking_number\": null, \"transport_number\": null, \"calculated_volume\": \"3133.260000\", \"shipping_location\": null, \"actual_arrival_date\": null, \"product_template_id\": 25, \"expected_arrival_date\": null}',3133.2600,52,NULL,NULL,1,NULL,'2025-09-15',NULL,'in_transit',NULL,NULL,1,'2025-09-08 11:11:47','2025-09-08 11:11:47','Склад','2025-09-08'),(20,27,1,1,'233223233223: 1, 2, 3, 4, 5, 1dsf',NULL,NULL,'{\"id\": 543, \"name\": \"233223233223: 1, 2, 3, 4, 5, 1dsf\", \"notes\": null, \"status\": \"in_transit\", \"producer\": null, \"quantity\": 1, \"is_active\": 1, \"attributes\": \"{\\\"a\\\": \\\"1\\\", \\\"d\\\": \\\"3\\\", \\\"f\\\": \\\"4\\\", \\\"g\\\": \\\"5\\\", \\\"k\\\": \\\"6\\\", \\\"l\\\": \\\"1dsf\\\", \\\"s\\\": \\\"2\\\"}\", \"correction\": null, \"created_at\": \"2025-09-08 13:15:29\", \"created_by\": 1, \"updated_at\": \"2025-09-08 15:42:56\", \"description\": null, \"producer_id\": 2, \"arrival_date\": \"2025-09-08\", \"warehouse_id\": 1, \"document_path\": \"[]\", \"shipping_date\": \"2025-09-08\", \"sold_quantity\": 0, \"tracking_number\": null, \"transport_number\": \"выфаыв\", \"calculated_volume\": \"0.120000\", \"correction_status\": null, \"shipping_location\": \"Ныва\", \"actual_arrival_date\": \"2025-09-08\", \"product_template_id\": 27, \"expected_arrival_date\": null}',0.1200,1,'выфаыв',NULL,2,NULL,'2025-09-15',NULL,'in_transit',NULL,'[]',1,'2025-09-08 12:42:56','2025-09-08 12:42:56','Ныва','2025-09-08'),(21,27,1,1,'233223233223: 3, 3, 3, 3, 3, 2sdafasd',NULL,NULL,'{\"id\": 541, \"name\": \"233223233223: 3, 3, 3, 3, 3, 2sdafasd\", \"notes\": \"333\", \"status\": \"in_transit\", \"producer\": null, \"quantity\": 13, \"is_active\": 1, \"attributes\": \"{\\\"a\\\": \\\"3\\\", \\\"d\\\": \\\"3\\\", \\\"f\\\": \\\"3\\\", \\\"g\\\": \\\"3\\\", \\\"l\\\": \\\"2sdafasd\\\", \\\"s\\\": \\\"3\\\"}\", \"correction\": null, \"created_at\": \"2025-09-08 11:30:05\", \"created_by\": 1, \"updated_at\": \"2025-09-08 15:42:56\", \"description\": null, \"producer_id\": 2, \"arrival_date\": \"2025-09-08\", \"warehouse_id\": 1, \"document_path\": \"[]\", \"shipping_date\": \"2025-09-08\", \"sold_quantity\": 0, \"tracking_number\": null, \"transport_number\": \"333\", \"calculated_volume\": \"3.159000\", \"correction_status\": null, \"shipping_location\": \"dsafsdf\", \"actual_arrival_date\": \"2025-09-08\", \"product_template_id\": 27, \"expected_arrival_date\": \"2025-09-12\"}',3.1590,13,'333',NULL,2,NULL,'2025-09-12',NULL,'in_transit','333','[]',1,'2025-09-08 12:42:56','2025-09-08 12:42:56','dsafsdf','2025-09-08'),(22,26,1,1,'gbldsf: 333, 33, 333',NULL,NULL,'{\"id\": 542, \"name\": \"gbldsf: 333, 33, 333\", \"notes\": \"333\", \"status\": \"in_transit\", \"producer\": null, \"quantity\": 1, \"is_active\": 1, \"attributes\": \"{\\\"a\\\": \\\"333\\\", \\\"f\\\": \\\"333\\\", \\\"s\\\": \\\"33\\\"}\", \"correction\": null, \"created_at\": \"2025-09-08 11:30:05\", \"created_by\": 1, \"updated_at\": \"2025-09-08 15:42:56\", \"description\": null, \"producer_id\": 7, \"arrival_date\": \"2025-09-08\", \"warehouse_id\": 1, \"document_path\": \"[\\\"documents/Счет_1908.pdf\\\"]\", \"shipping_date\": \"2025-09-08\", \"sold_quantity\": 0, \"tracking_number\": null, \"transport_number\": \"333\", \"calculated_volume\": \"3659.337000\", \"correction_status\": null, \"shipping_location\": \"dsafsdf\", \"actual_arrival_date\": \"2025-09-08\", \"product_template_id\": 26, \"expected_arrival_date\": \"2025-09-12\"}',3659.3370,1,'333',NULL,7,NULL,'2025-09-12',NULL,'in_transit','333','[\"documents\\/\\u0421\\u0447\\u0435\\u0442_1908.pdf\"]',1,'2025-09-08 12:42:56','2025-09-08 12:42:56','dsafsdf','2025-09-08'),(23,25,1,1,'Пиломатериалы: 110, 35, 230, v',NULL,'Тестовый товар #205','{\"id\": 443, \"name\": \"Пиломатериалы: 110, 35, 230, v\", \"notes\": null, \"status\": \"in_transit\", \"producer\": null, \"quantity\": 83, \"is_active\": 1, \"attributes\": \"{\\\"d\\\": 230, \\\"s\\\": 35, \\\"t\\\": \\\"v\\\", \\\"v\\\": 110}\", \"correction\": null, \"created_at\": \"2025-09-07 18:46:06\", \"created_by\": 1, \"updated_at\": \"2025-09-08 15:42:56\", \"description\": \"Тестовый товар #205\", \"producer_id\": 1, \"arrival_date\": \"2025-06-14\", \"warehouse_id\": 1, \"document_path\": null, \"shipping_date\": null, \"sold_quantity\": 30, \"tracking_number\": null, \"transport_number\": null, \"calculated_volume\": \"73496.500000\", \"correction_status\": null, \"shipping_location\": null, \"actual_arrival_date\": null, \"product_template_id\": 25, \"expected_arrival_date\": null}',73496.5000,83,NULL,NULL,1,NULL,'2025-09-15',NULL,'in_transit',NULL,NULL,1,'2025-09-08 12:42:56','2025-09-08 12:42:56','Склад','2025-09-08'),(24,25,2,1,'Пиломатериалы: 141, 44, 48, s',NULL,'Тестовый товар #207','{\"id\": 445, \"name\": \"Пиломатериалы: 141, 44, 48, s\", \"notes\": null, \"status\": \"in_transit\", \"producer\": null, \"quantity\": 156, \"is_active\": 1, \"attributes\": \"{\\\"d\\\": 48, \\\"s\\\": 44, \\\"t\\\": \\\"s\\\", \\\"v\\\": 141}\", \"correction\": null, \"created_at\": \"2025-09-07 18:46:06\", \"created_by\": 1, \"updated_at\": \"2025-09-08 15:42:56\", \"description\": \"Тестовый товар #207\", \"producer_id\": 2, \"arrival_date\": \"2025-08-16\", \"warehouse_id\": 2, \"document_path\": null, \"shipping_date\": null, \"sold_quantity\": 15, \"tracking_number\": null, \"transport_number\": null, \"calculated_volume\": \"46455.552000\", \"correction_status\": null, \"shipping_location\": null, \"actual_arrival_date\": null, \"product_template_id\": 25, \"expected_arrival_date\": null}',46455.5520,156,NULL,NULL,2,NULL,'2025-09-15',NULL,'in_transit',NULL,NULL,1,'2025-09-08 12:42:56','2025-09-08 12:42:56','Склад','2025-09-08'),(25,25,1,1,'Пиломатериалы: 122, 75, 213, d',NULL,'Тестовый товар #293','{\"id\": 531, \"name\": \"Пиломатериалы: 122, 75, 213, d\", \"notes\": null, \"status\": \"in_transit\", \"producer\": null, \"quantity\": 47, \"is_active\": 1, \"attributes\": \"{\\\"d\\\": 213, \\\"s\\\": 75, \\\"t\\\": \\\"d\\\", \\\"v\\\": 122}\", \"correction\": null, \"created_at\": \"2025-09-07 18:46:06\", \"created_by\": 1, \"updated_at\": \"2025-09-08 15:42:56\", \"description\": \"Тестовый товар #293\", \"producer_id\": 1, \"arrival_date\": \"2025-08-19\", \"warehouse_id\": 1, \"document_path\": null, \"shipping_date\": null, \"sold_quantity\": 25, \"tracking_number\": null, \"transport_number\": null, \"calculated_volume\": \"91600.650000\", \"correction_status\": null, \"shipping_location\": null, \"actual_arrival_date\": null, \"product_template_id\": 25, \"expected_arrival_date\": null}',91600.6500,47,NULL,NULL,1,NULL,'2025-09-15',NULL,'in_transit',NULL,NULL,1,'2025-09-08 12:42:56','2025-09-08 12:42:56','Склад','2025-09-08'),(26,25,2,1,'Пиломатериалы: 103, 27, 87, s',NULL,'Тестовый товар #294','{\"id\": 532, \"name\": \"Пиломатериалы: 103, 27, 87, s\", \"notes\": null, \"status\": \"in_transit\", \"producer\": null, \"quantity\": 13, \"is_active\": 1, \"attributes\": \"{\\\"d\\\": 87, \\\"s\\\": 27, \\\"t\\\": \\\"s\\\", \\\"v\\\": 103}\", \"correction\": null, \"created_at\": \"2025-09-07 18:46:06\", \"created_by\": 1, \"updated_at\": \"2025-09-08 15:42:56\", \"description\": \"Тестовый товар #294\", \"producer_id\": 1, \"arrival_date\": \"2025-06-15\", \"warehouse_id\": 2, \"document_path\": null, \"shipping_date\": null, \"sold_quantity\": 17, \"tracking_number\": null, \"transport_number\": null, \"calculated_volume\": \"3145.311000\", \"correction_status\": null, \"shipping_location\": null, \"actual_arrival_date\": null, \"product_template_id\": 25, \"expected_arrival_date\": null}',3145.3110,13,NULL,NULL,1,NULL,'2025-09-15',NULL,'in_transit',NULL,NULL,1,'2025-09-08 12:42:56','2025-09-08 12:42:56','Склад','2025-09-08'),(27,25,1,1,'Пиломатериалы: 27, 31, 31, v',NULL,'Тестовый товар #295','{\"id\": 533, \"name\": \"Пиломатериалы: 27, 31, 31, v\", \"notes\": null, \"status\": \"in_transit\", \"producer\": null, \"quantity\": 55, \"is_active\": 1, \"attributes\": \"{\\\"d\\\": 31, \\\"s\\\": 31, \\\"t\\\": \\\"v\\\", \\\"v\\\": 27}\", \"correction\": null, \"created_at\": \"2025-09-07 18:46:06\", \"created_by\": 1, \"updated_at\": \"2025-09-08 15:42:56\", \"description\": \"Тестовый товар #295\", \"producer_id\": 2, \"arrival_date\": \"2025-07-16\", \"warehouse_id\": 1, \"document_path\": null, \"shipping_date\": null, \"sold_quantity\": 40, \"tracking_number\": null, \"transport_number\": null, \"calculated_volume\": \"1427.085000\", \"correction_status\": null, \"shipping_location\": null, \"actual_arrival_date\": null, \"product_template_id\": 25, \"expected_arrival_date\": null}',1427.0850,55,NULL,NULL,2,NULL,'2025-09-15',NULL,'in_transit',NULL,NULL,1,'2025-09-08 12:42:56','2025-09-08 12:42:56','Склад','2025-09-08'),(28,25,2,1,'Пиломатериалы: 96, 68, 33, s',NULL,'Тестовый товар #296','{\"id\": 534, \"name\": \"Пиломатериалы: 96, 68, 33, s\", \"notes\": null, \"status\": \"in_transit\", \"producer\": null, \"quantity\": 128, \"is_active\": 1, \"attributes\": \"{\\\"d\\\": 33, \\\"s\\\": 68, \\\"t\\\": \\\"s\\\", \\\"v\\\": 96}\", \"correction\": null, \"created_at\": \"2025-09-07 18:46:06\", \"created_by\": 1, \"updated_at\": \"2025-09-08 15:42:56\", \"description\": \"Тестовый товар #296\", \"producer_id\": 1, \"arrival_date\": \"2025-07-09\", \"warehouse_id\": 2, \"document_path\": null, \"shipping_date\": null, \"sold_quantity\": 17, \"tracking_number\": null, \"transport_number\": null, \"calculated_volume\": \"27574.272000\", \"correction_status\": null, \"shipping_location\": null, \"actual_arrival_date\": null, \"product_template_id\": 25, \"expected_arrival_date\": null}',27574.2720,128,NULL,NULL,1,NULL,'2025-09-15',NULL,'in_transit',NULL,NULL,1,'2025-09-08 12:42:56','2025-09-08 12:42:56','Склад','2025-09-08'),(29,25,1,1,'Пиломатериалы: 136, 79, 141, s',NULL,'Тестовый товар #297','{\"id\": 535, \"name\": \"Пиломатериалы: 136, 79, 141, s\", \"notes\": null, \"status\": \"in_transit\", \"producer\": null, \"quantity\": 164, \"is_active\": 1, \"attributes\": \"{\\\"d\\\": 141, \\\"s\\\": 79, \\\"t\\\": \\\"s\\\", \\\"v\\\": 136}\", \"correction\": null, \"created_at\": \"2025-09-07 18:46:06\", \"created_by\": 1, \"updated_at\": \"2025-09-08 15:42:56\", \"description\": \"Тестовый товар #297\", \"producer_id\": 2, \"arrival_date\": \"2025-07-09\", \"warehouse_id\": 1, \"document_path\": null, \"shipping_date\": null, \"sold_quantity\": 1, \"tracking_number\": null, \"transport_number\": null, \"calculated_volume\": \"248444.256000\", \"correction_status\": null, \"shipping_location\": null, \"actual_arrival_date\": null, \"product_template_id\": 25, \"expected_arrival_date\": null}',248444.2560,164,NULL,NULL,2,NULL,'2025-09-15',NULL,'in_transit',NULL,NULL,1,'2025-09-08 12:42:56','2025-09-08 12:42:56','Склад','2025-09-08');
/*!40000 ALTER TABLE `product_in_transit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_templates`
--

DROP TABLE IF EXISTS `product_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `formula` text COLLATE utf8mb4_unicode_ci,
  `unit` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'м³',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_templates_name_index` (`name`),
  KEY `product_templates_is_active_index` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_templates`
--

LOCK TABLES `product_templates` WRITE;
/*!40000 ALTER TABLE `product_templates` DISABLE KEYS */;
INSERT INTO `product_templates` VALUES (25,'Пиломатериалы',NULL,'(v*s*d)/1000* quantity','м³',1,'2025-09-05 16:50:28','2025-09-05 16:50:28'),(26,'gbldsf',NULL,'(a*s*f)/1000* quantity','м³',1,'2025-09-05 17:02:55','2025-09-05 17:02:55'),(27,'233223233223','3232','(a*s*d*f*g)/1000* quantity','м³',1,'2025-09-05 17:53:56','2025-09-08 13:39:59');
/*!40000 ALTER TABLE `product_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_template_id` bigint unsigned NOT NULL,
  `warehouse_id` bigint unsigned NOT NULL,
  `created_by` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `correction` text COLLATE utf8mb4_unicode_ci,
  `correction_status` enum('none','correction') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attributes` json DEFAULT NULL,
  `calculated_volume` decimal(20,6) DEFAULT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `sold_quantity` int NOT NULL DEFAULT '0',
  `transport_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tracking_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `producer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shipping_location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shipping_date` date DEFAULT NULL,
  `expected_arrival_date` date DEFAULT NULL,
  `actual_arrival_date` date DEFAULT NULL,
  `arrival_date` date NOT NULL,
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'in_stock',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `document_path` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `producer_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `products_created_by_foreign` (`created_by`),
  KEY `products_warehouse_id_is_active_index` (`warehouse_id`,`is_active`),
  KEY `products_product_template_id_is_active_index` (`product_template_id`,`is_active`),
  KEY `products_producer_index` (`producer`),
  KEY `products_arrival_date_index` (`arrival_date`),
  KEY `products_status_index` (`status`),
  KEY `products_producer_id_foreign` (`producer_id`),
  CONSTRAINT `products_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `products_producer_id_foreign` FOREIGN KEY (`producer_id`) REFERENCES `producers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_product_template_id_foreign` FOREIGN KEY (`product_template_id`) REFERENCES `product_templates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `products_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=555 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (131,25,1,1,'Пиломатериалы: 10, 10, 10, v',NULL,NULL,NULL,'{\"d\": \"10\", \"s\": \"10\", \"t\": \"v\", \"v\": \"10\"}',120.000000,120,120,'FuFu',NULL,NULL,NULL,NULL,NULL,NULL,'2025-09-07','in_stock',NULL,NULL,1,'2025-09-07 12:32:25','2025-09-08 04:58:43',2),(132,25,1,1,'Пиломатериалы: 10, 10, 10, v',NULL,NULL,NULL,'{\"d\": \"10\", \"s\": \"10\", \"t\": \"v\", \"v\": \"10\"}',50.000000,50,50,'F4fFF',NULL,NULL,NULL,NULL,NULL,NULL,'2025-09-07','in_stock',NULL,NULL,1,'2025-09-07 12:32:44','2025-09-07 13:51:06',2),(133,25,1,1,'Пиломатериалы: 10, 10, 10, v',NULL,NULL,NULL,'{\"d\": \"10\", \"s\": \"10\", \"t\": \"v\", \"v\": \"10\"}',111.000000,111,104,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-09-07','in_stock',NULL,NULL,1,'2025-09-07 12:40:53','2025-09-08 11:39:05',2),(134,25,1,1,'Пиломатериалы: 105, 205, 305, v',NULL,NULL,NULL,'{\"d\": \"305\", \"s\": \"205\", \"t\": \"v\", \"v\": \"105\"}',656512.500000,100,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-09-07','in_stock',NULL,NULL,1,'2025-09-07 13:44:12','2025-09-08 05:23:21',3),(135,25,1,1,'Пиломатериалы: 4, 44, 44, v',NULL,NULL,NULL,'{\"d\": \"44\", \"s\": \"44\", \"t\": \"v\", \"v\": \"4\"}',7.744000,1,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-09-07','in_stock',NULL,NULL,1,'2025-09-07 15:33:35','2025-09-07 15:33:35',3),(136,25,2,1,'Пиломатериалы: 55, 44, 77',NULL,NULL,NULL,'{\"d\": \"77\", \"s\": \"44\", \"v\": \"55\"}',186.340000,1,0,'ааа',NULL,NULL,NULL,NULL,NULL,NULL,'2025-09-07','in_stock','ааа',NULL,1,'2025-09-07 15:33:49','2025-09-07 15:33:49',1),(137,25,1,1,'Пиломатериалы: 11, 12, 13, s',NULL,NULL,NULL,'{\"d\": \"13\", \"s\": \"12\", \"t\": \"s\", \"v\": \"11\"}',1.716000,1,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-09-07','in_stock',NULL,NULL,1,'2025-09-07 15:38:34','2025-09-07 15:38:34',2),(138,25,1,1,'Пиломатериалы: 23, 32, 44',NULL,NULL,NULL,'{\"d\": \"44\", \"s\": \"32\", \"v\": \"23\"}',32.384000,1,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-09-07','in_stock',NULL,NULL,1,'2025-09-07 15:38:47','2025-09-07 15:38:47',3),(148,25,2,1,'Пиломатериалы: 39, 12, 193, d','Тестовый товар #10',NULL,NULL,'{\"d\": 193, \"s\": 12, \"t\": \"d\", \"v\": 39}',451.620000,5,17,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-26','in_stock',NULL,NULL,1,'2025-09-07 15:44:09','2025-09-08 04:46:02',3),(149,25,2,1,'Пиломатериалы: 37, 46, 170, s','Тестовый товар #11',NULL,NULL,'{\"d\": 170, \"s\": 46, \"t\": \"s\", \"v\": 37}',25461.920000,88,18,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-09-06','in_stock',NULL,NULL,1,'2025-09-07 15:44:09','2025-09-08 04:46:02',2),(150,25,2,1,'Пиломатериалы: 56, 6, 194, v','Тестовый товар #12',NULL,NULL,'{\"d\": 194, \"s\": 6, \"t\": \"v\", \"v\": 56}',1238.496000,19,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-22','in_stock',NULL,NULL,1,'2025-09-07 15:44:09','2025-09-08 04:46:03',3),(151,25,1,1,'Пиломатериалы: 69, 10, 123, v','Тестовый товар #13',NULL,NULL,'{\"d\": 123, \"s\": 10, \"t\": \"v\", \"v\": 69}',7892.910000,93,9,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-09-03','in_stock',NULL,NULL,1,'2025-09-07 15:44:09','2025-09-08 04:46:03',1),(152,25,1,1,'Пиломатериалы: 97, 31, 160, s','Тестовый товар #14',NULL,NULL,'{\"d\": 160, \"s\": 31, \"t\": \"s\", \"v\": 97}',12990.240000,27,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-30','in_stock',NULL,NULL,1,'2025-09-07 15:44:09','2025-09-08 04:46:04',3),(153,25,2,1,'Пиломатериалы: 34, 10, 32, d','Тестовый товар #15',NULL,NULL,'{\"d\": 32, \"s\": 10, \"t\": \"d\", \"v\": 34}',304.640000,28,2,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-26','in_stock',NULL,NULL,1,'2025-09-07 15:44:09','2025-09-08 04:46:05',3),(154,25,1,1,'Пиломатериалы: 1, 16, 36, s','Тестовый товар #16',NULL,NULL,'{\"d\": 36, \"s\": 16, \"t\": \"s\", \"v\": 1}',31.680000,55,14,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-30','in_stock',NULL,NULL,1,'2025-09-07 15:44:09','2025-09-08 04:46:05',1),(155,25,2,1,'Пиломатериалы: 64, 43, 31, d','Тестовый товар #17',NULL,NULL,'{\"d\": 31, \"s\": 43, \"t\": \"d\", \"v\": 64}',682.496000,8,17,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-12','in_stock',NULL,NULL,1,'2025-09-07 15:44:09','2025-09-08 04:46:06',3),(156,25,2,1,'Пиломатериалы: 33, 30, 139, v','Тестовый товар #18',NULL,NULL,'{\"d\": 139, \"s\": 30, \"t\": \"v\", \"v\": 33}',550.440000,4,13,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-09','in_stock',NULL,NULL,1,'2025-09-07 15:44:09','2025-09-08 04:46:06',3),(157,25,2,1,'Пиломатериалы: 37, 12, 77, v','Тестовый товар #19',NULL,NULL,'{\"d\": 77, \"s\": 12, \"t\": \"v\", \"v\": 37}',1333.332000,39,9,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-31','in_stock',NULL,NULL,1,'2025-09-07 15:44:09','2025-09-08 04:46:07',3),(158,25,3,1,'Пиломатериалы: 78, 38, 99, d','Тестовый товар #20',NULL,NULL,'{\"d\": 99, \"s\": 38, \"t\": \"d\", \"v\": 78}',25822.368000,88,12,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-13','in_stock',NULL,NULL,1,'2025-09-07 15:44:09','2025-09-08 04:46:07',3),(159,25,2,1,'Пиломатериалы: 29, 10, 103, v','Тестовый товар #21',NULL,NULL,'{\"d\": 103, \"s\": 10, \"t\": \"v\", \"v\": 29}',1164.930000,39,12,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-09-01','in_stock',NULL,NULL,1,'2025-09-07 15:44:09','2025-09-08 04:46:08',2),(160,25,1,1,'Пиломатериалы: 92, 30, 195, s','Тестовый товар #22',NULL,NULL,'{\"d\": 195, \"s\": 30, \"t\": \"s\", \"v\": 92}',53820.000000,100,18,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-13','in_stock',NULL,NULL,1,'2025-09-07 15:44:09','2025-09-08 04:46:08',1),(161,25,1,1,'Пиломатериалы: 60, 39, 135, d','Тестовый товар #23',NULL,NULL,'{\"d\": 135, \"s\": 39, \"t\": \"d\", \"v\": 60}',21165.300000,67,5,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-31','in_stock',NULL,NULL,1,'2025-09-07 15:44:09','2025-09-08 04:46:09',2),(162,25,2,1,'Пиломатериалы: 66, 32, 150, d','Тестовый товар #24',NULL,NULL,'{\"d\": 150, \"s\": 32, \"t\": \"d\", \"v\": 66}',18691.200000,59,19,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-27','in_stock',NULL,NULL,1,'2025-09-07 15:44:09','2025-09-08 04:46:09',3),(163,25,1,1,'Пиломатериалы: 88, 24, 72, d','Тестовый товар #25',NULL,NULL,'{\"d\": 72, \"s\": 24, \"t\": \"d\", \"v\": 88}',6842.880000,45,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-22','in_stock',NULL,NULL,1,'2025-09-07 15:44:09','2025-09-08 04:46:37',2),(164,25,3,1,'Пиломатериалы: 37, 43, 100, s','Тестовый товар #26',NULL,NULL,'{\"d\": 100, \"s\": 43, \"t\": \"s\", \"v\": 37}',11614.300000,73,48,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-11','in_stock',NULL,NULL,1,'2025-09-07 15:44:09','2025-09-08 05:14:37',3),(165,25,1,1,'Пиломатериалы: 79, 23, 134, s','Тестовый товар #27',NULL,NULL,'{\"d\": 134, \"s\": 23, \"t\": \"s\", \"v\": 79}',6573.906000,27,7,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-19','in_stock',NULL,NULL,1,'2025-09-07 15:44:09','2025-09-08 04:46:11',1),(166,25,1,1,'Пиломатериалы: 7, 21, 62, s','Тестовый товар #28',NULL,NULL,'{\"d\": 62, \"s\": 21, \"t\": \"s\", \"v\": 7}',54.684000,6,9,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-22','in_stock',NULL,NULL,1,'2025-09-07 15:44:09','2025-09-08 04:46:12',3),(167,25,1,1,'Пиломатериалы: 44, 8, 80, s','Тестовый товар #29',NULL,NULL,'{\"d\": 80, \"s\": 8, \"t\": \"s\", \"v\": 44}',281.600000,10,14,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-28','in_stock',NULL,NULL,1,'2025-09-07 15:44:09','2025-09-08 04:46:38',3),(168,25,2,1,'Пиломатериалы: 97, 19, 14, v','Тестовый товар #30',NULL,NULL,'{\"d\": 14, \"s\": 19, \"t\": \"v\", \"v\": 97}',283.822000,11,11,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-13','in_stock',NULL,NULL,1,'2025-09-07 15:44:09','2025-09-08 04:46:38',3),(169,25,1,1,'Пиломатериалы: 92, 26, 25, s','Тестовый товар #31',NULL,NULL,'{\"d\": 25, \"s\": 26, \"t\": \"s\", \"v\": 92}',2272.400000,38,13,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-23','in_stock',NULL,NULL,1,'2025-09-07 15:44:09','2025-09-08 04:46:12',1),(170,25,1,1,'Пиломатериалы: 76, 33, 58, d','Тестовый товар #32',NULL,NULL,'{\"d\": 58, \"s\": 33, \"t\": \"d\", \"v\": 76}',872.784000,6,11,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-25','in_stock',NULL,NULL,1,'2025-09-07 15:44:09','2025-09-08 04:46:13',2),(171,25,1,1,'Пиломатериалы: 49, 48, 103, s','Тестовый товар #33',NULL,NULL,'{\"d\": 103, \"s\": 48, \"t\": \"s\", \"v\": 49}',1453.536000,6,20,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-08','in_stock',NULL,NULL,1,'2025-09-07 15:44:09','2025-09-08 04:46:13',1),(172,25,1,1,'Пиломатериалы: 20, 10, 164, v','Тестовый товар #34',NULL,NULL,'{\"d\": 164, \"s\": 10, \"t\": \"v\", \"v\": 20}',2427.200000,74,12,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-09-06','in_stock',NULL,NULL,1,'2025-09-07 15:44:09','2025-09-08 04:46:14',3),(173,25,2,1,'Пиломатериалы: 95, 19, 41, d','Тестовый товар #35',NULL,NULL,'{\"d\": 41, \"s\": 19, \"t\": \"d\", \"v\": 95}',1184.080000,16,20,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-30','in_stock',NULL,NULL,1,'2025-09-07 15:44:09','2025-09-08 04:46:14',1),(174,25,3,1,'Пиломатериалы: 14, 31, 170, d','Тестовый товар #36',NULL,NULL,'{\"d\": 170, \"s\": 31, \"t\": \"d\", \"v\": 14}',6345.080000,86,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-09-03','in_stock',NULL,NULL,1,'2025-09-07 15:44:09','2025-09-08 04:46:15',1),(175,25,3,1,'Пиломатериалы: 79, 5, 150, d','Тестовый товар #37',NULL,NULL,'{\"d\": 150, \"s\": 5, \"t\": \"d\", \"v\": 79}',3792.000000,64,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-10','in_stock',NULL,NULL,1,'2025-09-07 15:44:09','2025-09-08 04:46:15',1),(176,25,3,1,'Пиломатериалы: 83, 30, 83, d','Тестовый товар #38',NULL,NULL,'{\"d\": 83, \"s\": 30, \"t\": \"d\", \"v\": 83}',4546.740000,22,20,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-10','in_stock',NULL,NULL,1,'2025-09-07 15:44:09','2025-09-08 04:46:16',2),(177,25,2,1,'Пиломатериалы: 49, 32, 44, s','Тестовый товар #39',NULL,NULL,'{\"d\": 44, \"s\": 32, \"t\": \"s\", \"v\": 49}',6554.240000,95,16,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-09-02','in_stock',NULL,NULL,1,'2025-09-07 15:44:09','2025-09-08 04:46:21',1),(178,25,3,1,'Пиломатериалы: 59, 28, 164, d','Тестовый товар #40',NULL,NULL,'{\"d\": 164, \"s\": 28, \"t\": \"d\", \"v\": 59}',18694.032000,69,10,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-09-02','in_stock',NULL,NULL,1,'2025-09-07 15:44:09','2025-09-08 04:46:17',1),(181,25,3,1,'Пиломатериалы: 13, 19, 113, s','Тестовый товар #43',NULL,NULL,'{\"d\": 113, \"s\": 19, \"t\": \"s\", \"v\": 13}',1758.393000,63,7,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-31','in_stock',NULL,NULL,1,'2025-09-07 15:44:09','2025-09-08 04:46:17',1),(232,25,1,1,'Пиломатериалы: 47, 36, 92, s','Тестовый товар #44',NULL,NULL,'{\"d\": 92, \"s\": 36, \"t\": \"s\", \"v\": 47}',7316.208000,47,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-31','in_stock',NULL,NULL,1,'2025-09-07 15:44:18','2025-09-08 04:45:58',1),(233,25,2,1,'Пиломатериалы: 10, 17, 168, d','Тестовый товар #45',NULL,NULL,'{\"d\": 168, \"s\": 17, \"t\": \"d\", \"v\": 10}',2856.000000,100,11,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-30','in_stock',NULL,NULL,1,'2025-09-07 15:44:18','2025-09-08 04:45:58',2),(234,25,2,1,'Пиломатериалы: 9, 43, 142, v','Тестовый товар #46',NULL,NULL,'{\"d\": 142, \"s\": 43, \"t\": \"v\", \"v\": 9}',5330.538000,97,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-25','in_stock',NULL,NULL,1,'2025-09-07 15:44:18','2025-09-08 04:45:59',3),(235,25,1,1,'Пиломатериалы: 39, 17, 182, v','Тестовый товар #47',NULL,NULL,'{\"d\": 182, \"s\": 17, \"t\": \"v\", \"v\": 39}',11704.602000,97,14,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-23','in_stock',NULL,NULL,1,'2025-09-07 15:44:18','2025-09-08 04:46:00',1),(236,25,2,1,'Пиломатериалы: 26, 38, 46, s','Тестовый товар #48',NULL,NULL,'{\"d\": 46, \"s\": 38, \"t\": \"s\", \"v\": 26}',2908.672000,64,6,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-09-02','in_stock',NULL,NULL,1,'2025-09-07 15:44:18','2025-09-08 04:46:00',2),(237,25,2,1,'Пиломатериалы: 96, 17, 146, d','Тестовый товар #49',NULL,NULL,'{\"d\": 146, \"s\": 17, \"t\": \"d\", \"v\": 96}',9054.336000,38,11,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-09-03','in_stock',NULL,NULL,1,'2025-09-07 15:44:18','2025-09-08 04:46:01',1),(238,25,1,1,'Пиломатериалы: 9, 43, 125, v','Тестовый товар #50',NULL,NULL,'{\"d\": 125, \"s\": 43, \"t\": \"v\", \"v\": 9}',628.875000,13,2,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-23','in_stock',NULL,NULL,1,'2025-09-07 15:44:18','2025-09-08 04:46:01',1),(239,25,3,1,'Пиломатериалы: 136, 54, 255, d','Тестовый товар #1',NULL,NULL,'{\"d\": 255, \"s\": 54, \"t\": \"d\", \"v\": 136}',26218.080000,14,10,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-14','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:42:23',2),(240,25,2,1,'Пиломатериалы: 29, 75, 115, s','Тестовый товар #2',NULL,NULL,'{\"d\": 115, \"s\": 75, \"t\": \"s\", \"v\": 29}',10755.375000,43,41,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-14','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:42:25',2),(241,25,1,1,'Пиломатериалы: 72, 53, 67, v','Тестовый товар #3',NULL,NULL,'{\"d\": 67, \"s\": 53, \"t\": \"v\", \"v\": 72}',46532.304000,182,2,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-16','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:42:25',1),(242,25,1,1,'Пиломатериалы: 108, 72, 280, v','Тестовый товар #4',NULL,NULL,'{\"d\": 280, \"s\": 72, \"t\": \"v\", \"v\": 108}',82736.640000,38,40,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-30','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:42:27',3),(243,25,2,1,'Пиломатериалы: 139, 28, 159, v','Тестовый товар #5',NULL,NULL,'{\"d\": 159, \"s\": 28, \"t\": \"v\", \"v\": 139}',29084.916000,47,13,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-07','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:42:27',1),(244,25,2,1,'Пиломатериалы: 44, 75, 188, s','Тестовый товар #6',NULL,NULL,'{\"d\": 188, \"s\": 75, \"t\": \"s\", \"v\": 44}',14889.600000,24,12,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-02','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:42:29',2),(245,25,2,1,'Пиломатериалы: 20, 23, 251, d','Тестовый товар #7',NULL,NULL,'{\"d\": 251, \"s\": 23, \"t\": \"d\", \"v\": 20}',12238.760000,106,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-11','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:42:31',3),(246,25,1,1,'Пиломатериалы: 116, 7, 300, v','Тестовый товар #8',NULL,NULL,'{\"d\": 300, \"s\": 7, \"t\": \"v\", \"v\": 116}',12180.000000,50,36,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-21','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:42:33',1),(247,25,1,1,'Пиломатериалы: 7, 28, 254, v','Тестовый товар #9',NULL,NULL,'{\"d\": 254, \"s\": 28, \"t\": \"v\", \"v\": 7}',4829.048000,97,9,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-25','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:42:33',3),(248,25,3,1,'Пиломатериалы: 70, 33, 42, d','Тестовый товар #10',NULL,NULL,'{\"d\": 42, \"s\": 33, \"t\": \"d\", \"v\": 70}',6209.280000,64,21,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-09-05','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:42:35',2),(343,25,3,1,'Пиломатериалы: 122, 43, 147, d','Тестовый товар #105',NULL,NULL,'{\"d\": 147, \"s\": 43, \"t\": \"d\", \"v\": 122}',2313.486000,3,35,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-30','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:45:09',3),(344,25,3,1,'Пиломатериалы: 27, 41, 280, v','Тестовый товар #106',NULL,NULL,'{\"d\": 280, \"s\": 41, \"t\": \"v\", \"v\": 27}',5579.280000,18,6,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-31','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:45:10',1),(345,25,3,1,'Пиломатериалы: 43, 69, 188, d','Тестовый товар #107',NULL,NULL,'{\"d\": 188, \"s\": 69, \"t\": \"d\", \"v\": 43}',49086.048000,88,15,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-16','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:45:10',1),(346,25,1,1,'Пиломатериалы: 88, 50, 42, v','Тестовый товар #108',NULL,NULL,'{\"d\": 42, \"s\": 50, \"t\": \"v\", \"v\": 88}',31416.000000,170,20,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-23','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:45:11',1),(347,25,3,1,'Пиломатериалы: 28, 72, 68, v','Тестовый товар #109',NULL,NULL,'{\"d\": 68, \"s\": 72, \"t\": \"v\", \"v\": 28}',9459.072000,69,36,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-06','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:45:11',3),(348,25,3,1,'Пиломатериалы: 112, 75, 231, v','Тестовый товар #110',NULL,NULL,'{\"d\": 231, \"s\": 75, \"t\": \"v\", \"v\": 112}',302702.400000,156,18,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-05','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:45:12',1),(349,25,2,1,'Пиломатериалы: 121, 34, 7, v','Тестовый товар #111',NULL,NULL,'{\"d\": 7, \"s\": 34, \"t\": \"v\", \"v\": 121}',2937.396000,102,28,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-27','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:45:12',3),(350,25,1,1,'Пиломатериалы: 6, 5, 219, d','Тестовый товар #112',NULL,NULL,'{\"d\": 219, \"s\": 5, \"t\": \"d\", \"v\": 6}',512.460000,78,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-17','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:45:13',2),(351,25,1,1,'Пиломатериалы: 22, 22, 241, s','Тестовый товар #113',NULL,NULL,'{\"d\": 241, \"s\": 22, \"t\": \"s\", \"v\": 22}',18546.396000,159,18,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-31','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:45:13',3),(352,25,1,1,'Пиломатериалы: 113, 33, 8, d','Тестовый товар #114',NULL,NULL,'{\"d\": 8, \"s\": 33, \"t\": \"d\", \"v\": 113}',2386.560000,80,30,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-24','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:45:23',1),(353,25,1,1,'Пиломатериалы: 145, 26, 78, d','Тестовый товар #115',NULL,NULL,'{\"d\": 78, \"s\": 26, \"t\": \"d\", \"v\": 145}',52342.680000,178,47,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-26','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:45:24',3),(354,25,1,1,'Пиломатериалы: 19, 12, 13, d','Тестовый товар #116',NULL,NULL,'{\"d\": 13, \"s\": 12, \"t\": \"d\", \"v\": 19}',293.436000,99,11,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-10','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:45:15',1),(355,25,1,1,'Пиломатериалы: 98, 52, 275, s','Тестовый товар #117',NULL,NULL,'{\"d\": 275, \"s\": 52, \"t\": \"s\", \"v\": 98}',241040.800000,172,43,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-12','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:45:15',2),(356,25,2,1,'Пиломатериалы: 26, 46, 145, s','Тестовый товар #118',NULL,NULL,'{\"d\": 145, \"s\": 46, \"t\": \"s\", \"v\": 26}',28614.300000,165,44,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-14','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:45:16',1),(357,25,2,1,'Пиломатериалы: 123, 47, 108, s','Тестовый товар #119',NULL,NULL,'{\"d\": 108, \"s\": 47, \"t\": \"s\", \"v\": 123}',76794.804000,123,22,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-31','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:45:16',1),(358,25,2,1,'Пиломатериалы: 149, 12, 138, d','Тестовый товар #120',NULL,NULL,'{\"d\": 138, \"s\": 12, \"t\": \"d\", \"v\": 149}',20233.008000,82,36,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-28','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:45:17',2),(359,25,3,1,'Пиломатериалы: 140, 78, 101, s','Тестовый товар #121',NULL,NULL,'{\"d\": 101, \"s\": 78, \"t\": \"s\", \"v\": 140}',34190.520000,31,30,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-01','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:45:26',3),(360,25,1,1,'Пиломатериалы: 5, 30, 9, d','Тестовый товар #122',NULL,NULL,'{\"d\": 9, \"s\": 30, \"t\": \"d\", \"v\": 5}',143.100000,106,9,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-21','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:45:18',3),(361,25,1,1,'Пиломатериалы: 19, 55, 161, s','Тестовый товар #123',NULL,NULL,'{\"d\": 161, \"s\": 55, \"t\": \"s\", \"v\": 19}',26919.200000,160,45,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-13','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:45:18',3),(362,25,3,1,'Пиломатериалы: 149, 69, 114, s','Тестовый товар #124',NULL,NULL,'{\"d\": 114, \"s\": 69, \"t\": \"s\", \"v\": 149}',2344.068000,2,6,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-25','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:45:19',2),(363,25,1,1,'Пиломатериалы: 35, 45, 257, d','Тестовый товар #125',NULL,NULL,'{\"d\": 257, \"s\": 45, \"t\": \"d\", \"v\": 35}',27119.925000,67,31,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-11','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:45:19',2),(364,25,1,1,'Пиломатериалы: 75, 67, 195, s','Тестовый товар #126',NULL,NULL,'{\"d\": 195, \"s\": 67, \"t\": \"s\", \"v\": 75}',95047.875000,97,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-05','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:45:20',2),(365,25,3,1,'Пиломатериалы: 24, 79, 10, v','Тестовый товар #127',NULL,NULL,'{\"d\": 10, \"s\": 79, \"t\": \"v\", \"v\": 24}',1933.920000,102,7,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-28','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:45:21',2),(366,25,1,1,'Пиломатериалы: 124, 16, 84, v','Тестовый товар #128',NULL,NULL,'{\"d\": 84, \"s\": 16, \"t\": \"v\", \"v\": 124}',7999.488000,48,28,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-24','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:45:21',2),(367,25,1,1,'Пиломатериалы: 33, 65, 239, v','Тестовый товар #129',NULL,NULL,'{\"d\": 239, \"s\": 65, \"t\": \"v\", \"v\": 33}',50752.845000,99,34,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-12','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:45:20',2),(368,25,3,1,'Пиломатериалы: 146, 46, 95, s','Тестовый товар #130',NULL,NULL,'{\"d\": 95, \"s\": 46, \"t\": \"s\", \"v\": 146}',73372.300000,115,22,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-19','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:44:47',2),(369,25,1,1,'Пиломатериалы: 112, 39, 42, v','Тестовый товар #131',NULL,NULL,'{\"d\": 42, \"s\": 39, \"t\": \"v\", \"v\": 112}',33388.992000,182,25,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-28','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:44:47',2),(370,25,2,1,'Пиломатериалы: 49, 27, 122, d','Тестовый товар #132',NULL,NULL,'{\"d\": 122, \"s\": 27, \"t\": \"d\", \"v\": 49}',18561.690000,115,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-12','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:44:46',3),(371,25,3,1,'Пиломатериалы: 11, 38, 226, d','Тестовый товар #133',NULL,NULL,'{\"d\": 226, \"s\": 38, \"t\": \"d\", \"v\": 11}',13603.392000,144,12,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-22','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:44:45',2),(372,25,3,1,'Пиломатериалы: 123, 8, 299, s','Тестовый товар #134',NULL,NULL,'{\"d\": 299, \"s\": 8, \"t\": \"s\", \"v\": 123}',52664.664000,179,17,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-20','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:44:45',3),(373,25,3,1,'Пиломатериалы: 8, 57, 164, s','Тестовый товар #135',NULL,NULL,'{\"d\": 164, \"s\": 57, \"t\": \"s\", \"v\": 8}',5384.448000,72,48,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-09-02','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:44:44',3),(374,25,1,1,'Пиломатериалы: 83, 51, 162, v','Тестовый товар #136',NULL,NULL,'{\"d\": 162, \"s\": 51, \"t\": \"v\", \"v\": 83}',114519.582000,167,42,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-04','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:44:44',2),(375,25,2,1,'Пиломатериалы: 12, 77, 97, d','Тестовый товар #137',NULL,NULL,'{\"d\": 97, \"s\": 77, \"t\": \"d\", \"v\": 12}',3674.748000,41,48,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-13','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:44:43',2),(376,25,3,1,'Пиломатериалы: 149, 64, 163, d','Тестовый товар #138',NULL,NULL,'{\"d\": 163, \"s\": 64, \"t\": \"d\", \"v\": 149}',118131.968000,76,11,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-23','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:44:43',3),(377,25,2,1,'Пиломатериалы: 68, 11, 276, v','Тестовый товар #139',NULL,NULL,'{\"d\": 276, \"s\": 11, \"t\": \"v\", \"v\": 68}',39431.568000,191,29,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-13','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:44:42',2),(378,25,2,1,'Пиломатериалы: 35, 66, 115, s','Тестовый товар #140',NULL,NULL,'{\"d\": 115, \"s\": 66, \"t\": \"s\", \"v\": 35}',23642.850000,89,49,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-18','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:44:42',3),(379,25,2,1,'Пиломатериалы: 60, 79, 36, v','Тестовый товар #141',NULL,NULL,'{\"d\": 36, \"s\": 79, \"t\": \"v\", \"v\": 60}',33104.160000,194,30,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-31','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:44:41',2),(380,25,3,1,'Пиломатериалы: 5, 19, 284, s','Тестовый товар #142',NULL,NULL,'{\"d\": 284, \"s\": 19, \"t\": \"s\", \"v\": 5}',431.680000,16,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-12','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:44:41',1),(381,25,1,1,'Пиломатериалы: 68, 74, 36, v','Тестовый товар #143',NULL,NULL,'{\"d\": 36, \"s\": 74, \"t\": \"v\", \"v\": 68}',17028.288000,94,20,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-09-02','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:44:40',3),(382,25,1,1,'Пиломатериалы: 84, 57, 289, s','Тестовый товар #144',NULL,NULL,'{\"d\": 289, \"s\": 57, \"t\": \"s\", \"v\": 84}',174350.232000,126,27,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-27','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:44:39',2),(383,25,2,1,'Пиломатериалы: 46, 27, 122, d','Тестовый товар #145',NULL,NULL,'{\"d\": 122, \"s\": 27, \"t\": \"d\", \"v\": 46}',23334.696000,154,45,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-09','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:44:39',1),(384,25,3,1,'Пиломатериалы: 128, 66, 96, s','Тестовый товар #146',NULL,NULL,'{\"d\": 96, \"s\": 66, \"t\": \"s\", \"v\": 128}',55148.544000,68,45,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-26','in_stock',NULL,NULL,1,'2025-09-07 15:46:05','2025-09-08 04:44:38',2),(442,25,2,1,'Пиломатериалы: 103, 15, 39, d','Тестовый товар #204',NULL,NULL,'{\"d\": 39, \"s\": 15, \"t\": \"d\", \"v\": 103}',3133.260000,52,38,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-27','in_transit',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 11:11:47',1),(443,25,1,1,'Пиломатериалы: 110, 35, 230, v','Тестовый товар #205',NULL,NULL,'{\"d\": 230, \"s\": 35, \"t\": \"v\", \"v\": 110}',73496.500000,83,30,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-14','in_transit',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 12:42:56',1),(444,25,2,1,'Пиломатериалы: 72, 4, 193, v','Тестовый товар #206',NULL,NULL,'{\"d\": 193, \"s\": 4, \"t\": \"v\", \"v\": 72}',9616.032000,173,47,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-30','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:38:51',1),(445,25,2,1,'Пиломатериалы: 141, 44, 48, s','Тестовый товар #207',NULL,NULL,'{\"d\": 48, \"s\": 44, \"t\": \"s\", \"v\": 141}',46455.552000,156,15,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-16','in_transit',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 12:42:56',2),(446,25,2,1,'Пиломатериалы: 37, 21, 206, v','Тестовый товар #208',NULL,NULL,'{\"d\": 206, \"s\": 21, \"t\": \"v\", \"v\": 37}',6082.356000,38,11,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-29','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:38:53',3),(447,25,2,1,'Пиломатериалы: 62, 69, 131, v','Тестовый товар #209',NULL,NULL,'{\"d\": 131, \"s\": 69, \"t\": \"v\", \"v\": 62}',8406.270000,15,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-14','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:38:54',3),(448,25,3,1,'Пиломатериалы: 24, 33, 209, d','Тестовый товар #210',NULL,NULL,'{\"d\": 209, \"s\": 33, \"t\": \"d\", \"v\": 24}',16221.744000,98,34,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-18','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:38:58',1),(449,25,3,1,'Пиломатериалы: 68, 61, 73, s','Тестовый товар #211',NULL,NULL,'{\"d\": 73, \"s\": 61, \"t\": \"s\", \"v\": 68}',56927.152000,188,46,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-26','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:38:58',2),(450,25,3,1,'Пиломатериалы: 100, 72, 50, d','Тестовый товар #212',NULL,NULL,'{\"d\": 50, \"s\": 72, \"t\": \"d\", \"v\": 100}',59040.000000,164,44,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-09-04','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:38:58',1),(451,25,1,1,'Пиломатериалы: 83, 45, 162, s','Тестовый товар #213',NULL,NULL,'{\"d\": 162, \"s\": 45, \"t\": \"s\", \"v\": 83}',53246.160000,88,7,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-27','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:38:58',3),(452,25,2,1,'Пиломатериалы: 147, 41, 39, d','Тестовый товар #214',NULL,NULL,'{\"d\": 39, \"s\": 41, \"t\": \"d\", \"v\": 147}',37138.374000,158,13,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-21','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:39:00',2),(453,25,2,1,'Пиломатериалы: 111, 35, 298, v','Тестовый товар #215',NULL,NULL,'{\"d\": 298, \"s\": 35, \"t\": \"v\", \"v\": 111}',64832.880000,56,12,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-07','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:39:00',3),(454,25,1,1,'Пиломатериалы: 37, 29, 9, s','Тестовый товар #216',NULL,NULL,'{\"d\": 9, \"s\": 29, \"t\": \"s\", \"v\": 37}',193.140000,20,48,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-04','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:39:02',2),(455,25,1,1,'Пиломатериалы: 98, 27, 180, d','Тестовый товар #217',NULL,NULL,'{\"d\": 180, \"s\": 27, \"t\": \"d\", \"v\": 98}',27147.960000,57,10,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-24','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:39:04',2),(456,25,2,1,'Пиломатериалы: 149, 13, 88, s','Тестовый товар #218',NULL,NULL,'{\"d\": 88, \"s\": 13, \"t\": \"s\", \"v\": 149}',13295.568000,78,22,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-22','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:39:04',2),(457,25,2,1,'Пиломатериалы: 118, 9, 92, d','Тестовый товар #219',NULL,NULL,'{\"d\": 92, \"s\": 9, \"t\": \"d\", \"v\": 118}',9868.104000,101,42,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-11','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:39:16',2),(458,25,1,1,'Пиломатериалы: 37, 41, 249, s','Тестовый товар #220',NULL,NULL,'{\"d\": 249, \"s\": 41, \"t\": \"s\", \"v\": 37}',60815.013000,161,49,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-15','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:39:10',3),(459,25,1,1,'Пиломатериалы: 98, 25, 227, s','Тестовый товар #221',NULL,NULL,'{\"d\": 227, \"s\": 25, \"t\": \"s\", \"v\": 98}',76192.550000,137,30,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-09-05','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:39:47',3),(460,25,1,1,'Пиломатериалы: 141, 64, 299, s','Тестовый товар #222',NULL,NULL,'{\"d\": 299, \"s\": 64, \"t\": \"s\", \"v\": 141}',423613.632000,157,6,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-11','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:39:10',2),(461,25,1,1,'Пиломатериалы: 49, 6, 288, s','Тестовый товар #223',NULL,NULL,'{\"d\": 288, \"s\": 6, \"t\": \"s\", \"v\": 49}',8128.512000,96,35,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-23','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:39:12',2),(462,25,1,1,'Пиломатериалы: 2, 6, 225, d','Тестовый товар #224',NULL,NULL,'{\"d\": 225, \"s\": 6, \"t\": \"d\", \"v\": 2}',194.400000,72,44,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-25','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:39:12',1),(463,25,3,1,'Пиломатериалы: 111, 68, 279, s','Тестовый товар #225',NULL,NULL,'{\"d\": 279, \"s\": 68, \"t\": \"s\", \"v\": 111}',172683.144000,82,34,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-03','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:39:18',2),(464,25,2,1,'Пиломатериалы: 116, 3, 109, s','Тестовый товар #226',NULL,NULL,'{\"d\": 109, \"s\": 3, \"t\": \"s\", \"v\": 116}',5955.324000,157,34,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-09-01','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:39:20',2),(465,25,2,1,'Пиломатериалы: 13, 67, 96, v','Тестовый товар #227',NULL,NULL,'{\"d\": 96, \"s\": 67, \"t\": \"v\", \"v\": 13}',15719.808000,188,29,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-31','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:39:25',3),(466,25,2,1,'Пиломатериалы: 112, 56, 127, v','Тестовый товар #228',NULL,NULL,'{\"d\": 127, \"s\": 56, \"t\": \"v\", \"v\": 112}',46199.552000,58,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-13','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:39:27',2),(467,25,2,1,'Пиломатериалы: 132, 29, 259, v','Тестовый товар #229',NULL,NULL,'{\"d\": 259, \"s\": 29, \"t\": \"v\", \"v\": 132}',40649.532000,41,37,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-10','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:39:52',3),(468,25,1,1,'Пиломатериалы: 30, 7, 109, s','Тестовый товар #230',NULL,NULL,'{\"d\": 109, \"s\": 7, \"t\": \"s\", \"v\": 30}',4463.550000,195,48,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-25','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:39:54',1),(469,25,1,1,'Пиломатериалы: 49, 44, 254, v','Тестовый товар #231',NULL,NULL,'{\"d\": 254, \"s\": 44, \"t\": \"v\", \"v\": 49}',57500.520000,105,13,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-28','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:39:54',1),(470,25,1,1,'Пиломатериалы: 149, 24, 271, v','Тестовый товар #232',NULL,NULL,'{\"d\": 271, \"s\": 24, \"t\": \"v\", \"v\": 149}',82373.160000,85,39,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-15','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:39:56',2),(471,25,3,1,'Пиломатериалы: 149, 16, 138, d','Тестовый товар #233',NULL,NULL,'{\"d\": 138, \"s\": 16, \"t\": \"d\", \"v\": 149}',11185.728000,34,41,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-19','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:39:57',3),(472,25,3,1,'Пиломатериалы: 119, 36, 193, s','Тестовый товар #234',NULL,NULL,'{\"d\": 193, \"s\": 36, \"t\": \"s\", \"v\": 119}',29765.232000,36,42,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-22','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:40:00',3),(473,25,2,1,'Пиломатериалы: 80, 24, 155, s','Тестовый товар #235',NULL,NULL,'{\"d\": 155, \"s\": 24, \"t\": \"s\", \"v\": 80}',44044.800000,148,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-04','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:40:01',2),(474,25,1,1,'Пиломатериалы: 41, 39, 135, d','Тестовый товар #236',NULL,NULL,'{\"d\": 135, \"s\": 39, \"t\": \"d\", \"v\": 41}',40366.755000,187,39,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-10','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:40:02',3),(475,25,3,1,'Пиломатериалы: 73, 79, 155, s','Тестовый товар #237',NULL,NULL,'{\"d\": 155, \"s\": 79, \"t\": \"s\", \"v\": 73}',85812.960000,96,11,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-31','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:40:06',3),(476,25,1,1,'Пиломатериалы: 134, 12, 275, v','Тестовый товар #238',NULL,NULL,'{\"d\": 275, \"s\": 12, \"t\": \"v\", \"v\": 134}',67214.400000,152,42,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-14','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:40:07',3),(477,25,1,1,'Пиломатериалы: 27, 58, 227, s','Тестовый товар #239',NULL,NULL,'{\"d\": 227, \"s\": 58, \"t\": \"s\", \"v\": 27}',4976.748000,14,36,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-23','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:40:08',1),(478,25,2,1,'Пиломатериалы: 63, 53, 24, v','Тестовый товар #240',NULL,NULL,'{\"d\": 24, \"s\": 53, \"t\": \"v\", \"v\": 63}',14504.616000,181,17,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-15','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:40:10',3),(479,25,1,1,'Пиломатериалы: 62, 71, 76, v','Тестовый товар #241',NULL,NULL,'{\"d\": 76, \"s\": 71, \"t\": \"v\", \"v\": 62}',29106.024000,87,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-06','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:40:10',1),(480,25,1,1,'Пиломатериалы: 149, 65, 132, d','Тестовый товар #242',NULL,NULL,'{\"d\": 132, \"s\": 65, \"t\": \"d\", \"v\": 149}',74148.360000,58,24,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-27','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:40:12',2),(481,25,2,1,'Пиломатериалы: 103, 57, 279, v','Тестовый товар #243',NULL,NULL,'{\"d\": 279, \"s\": 57, \"t\": \"v\", \"v\": 103}',181818.999000,111,20,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-12','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:40:12',2),(482,25,1,1,'Пиломатериалы: 115, 47, 123, v','Тестовый товар #244',NULL,NULL,'{\"d\": 123, \"s\": 47, \"t\": \"v\", \"v\": 115}',67146.315000,101,18,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-24','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:40:15',1),(483,25,1,1,'Пиломатериалы: 80, 56, 64, v','Тестовый товар #245',NULL,NULL,'{\"d\": 64, \"s\": 56, \"t\": \"v\", \"v\": 80}',11468.800000,40,24,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-17','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:40:19',2),(484,25,2,1,'Пиломатериалы: 126, 59, 93, v','Тестовый товар #246',NULL,NULL,'{\"d\": 93, \"s\": 59, \"t\": \"v\", \"v\": 126}',4148.172000,6,31,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-22','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:40:19',1),(485,25,2,1,'Пиломатериалы: 146, 43, 213, v','Тестовый товар #247',NULL,NULL,'{\"d\": 213, \"s\": 43, \"t\": \"v\", \"v\": 146}',117674.832000,88,22,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-22','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:40:21',2),(486,25,2,1,'Пиломатериалы: 101, 43, 82, d','Тестовый товар #248',NULL,NULL,'{\"d\": 82, \"s\": 43, \"t\": \"d\", \"v\": 101}',8903.150000,25,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-24','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:40:21',2),(487,25,3,1,'Пиломатериалы: 1, 10, 110, d','Тестовый товар #249',NULL,NULL,'{\"d\": 110, \"s\": 10, \"t\": \"d\", \"v\": 1}',99.000000,90,20,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-07','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:40:23',2),(488,25,3,1,'Пиломатериалы: 10, 10, 125, v','Тестовый товар #250',NULL,NULL,'{\"d\": 125, \"s\": 10, \"t\": \"v\", \"v\": 10}',2375.000000,190,42,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-26','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:40:25',3),(489,25,2,1,'Пиломатериалы: 116, 45, 144, v','Тестовый товар #251',NULL,NULL,'{\"d\": 144, \"s\": 45, \"t\": \"v\", \"v\": 116}',126282.240000,168,37,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-14','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:40:25',2),(490,25,1,1,'Пиломатериалы: 149, 77, 138, d','Тестовый товар #252',NULL,NULL,'{\"d\": 138, \"s\": 77, \"t\": \"d\", \"v\": 149}',186826.332000,118,45,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-04','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:40:30',3),(491,25,1,1,'Пиломатериалы: 107, 9, 36, s','Тестовый товар #253',NULL,NULL,'{\"d\": 36, \"s\": 9, \"t\": \"s\", \"v\": 107}',3813.480000,110,38,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-20','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:40:32',2),(492,25,2,1,'Пиломатериалы: 73, 78, 224, d','Тестовый товар #254',NULL,NULL,'{\"d\": 224, \"s\": 78, \"t\": \"d\", \"v\": 73}',172186.560000,135,23,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-18','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:40:32',2),(493,25,2,1,'Пиломатериалы: 12, 4, 284, s','Тестовый товар #255',NULL,NULL,'{\"d\": 284, \"s\": 4, \"t\": \"s\", \"v\": 12}',1172.352000,86,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-21','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:40:42',1),(494,25,2,1,'Пиломатериалы: 18, 10, 123, s','Тестовый товар #256',NULL,NULL,'{\"d\": 123, \"s\": 10, \"t\": \"s\", \"v\": 18}',597.780000,27,5,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-09','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:40:40',2),(495,25,1,1,'Пиломатериалы: 147, 69, 48, s','Тестовый товар #257',NULL,NULL,'{\"d\": 48, \"s\": 69, \"t\": \"s\", \"v\": 147}',55502.496000,114,14,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-08','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:40:40',3),(496,25,1,1,'Пиломатериалы: 137, 12, 218, d','Тестовый товар #258',NULL,NULL,'{\"d\": 218, \"s\": 12, \"t\": \"d\", \"v\": 137}',41931.864000,117,8,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-23','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:40:38',3),(497,25,1,1,'Пиломатериалы: 130, 14, 267, s','Тестовый товар #259',NULL,NULL,'{\"d\": 267, \"s\": 14, \"t\": \"s\", \"v\": 130}',77264.460000,159,39,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-09','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:40:38',1),(498,25,3,1,'Пиломатериалы: 59, 5, 222, v','Тестовый товар #260',NULL,NULL,'{\"d\": 222, \"s\": 5, \"t\": \"v\", \"v\": 59}',5894.100000,90,4,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-03','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:40:36',3),(499,25,3,1,'Пиломатериалы: 130, 75, 203, s','Тестовый товар #261',NULL,NULL,'{\"d\": 203, \"s\": 75, \"t\": \"s\", \"v\": 130}',19792.500000,10,26,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-13','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:40:36',1),(500,25,1,1,'Пиломатериалы: 36, 49, 166, s','Тестовый товар #262',NULL,NULL,'{\"d\": 166, \"s\": 49, \"t\": \"s\", \"v\": 36}',57100.680000,195,30,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-17','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:38:04',2),(501,25,3,1,'Пиломатериалы: 112, 34, 268, d','Тестовый товар #263',NULL,NULL,'{\"d\": 268, \"s\": 34, \"t\": \"d\", \"v\": 112}',132670.720000,130,50,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-08','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:38:04',3),(502,25,2,1,'Пиломатериалы: 56, 25, 53, d','Тестовый товар #264',NULL,NULL,'{\"d\": 53, \"s\": 25, \"t\": \"d\", \"v\": 56}',7568.400000,102,18,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-28','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:38:03',2),(503,25,1,1,'Пиломатериалы: 132, 28, 156, d','Тестовый товар #265',NULL,NULL,'{\"d\": 156, \"s\": 28, \"t\": \"d\", \"v\": 132}',114162.048000,198,9,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-31','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:38:02',3),(504,25,2,1,'Пиломатериалы: 141, 80, 166, v','Тестовый товар #266',NULL,NULL,'{\"d\": 166, \"s\": 80, \"t\": \"v\", \"v\": 141}',288361.920000,154,7,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-09','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:38:02',3),(505,25,2,1,'Пиломатериалы: 62, 7, 134, v','Тестовый товар #267',NULL,NULL,'{\"d\": 134, \"s\": 7, \"t\": \"v\", \"v\": 62}',2849.644000,49,2,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-10','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:38:01',2),(506,25,2,1,'Пиломатериалы: 13, 33, 89, v','Тестовый товар #268',NULL,NULL,'{\"d\": 89, \"s\": 33, \"t\": \"v\", \"v\": 13}',3207.204000,84,28,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-07','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:38:00',2),(507,25,1,1,'Пиломатериалы: 52, 68, 145, v','Тестовый товар #269',NULL,NULL,'{\"d\": 145, \"s\": 68, \"t\": \"v\", \"v\": 52}',9741.680000,19,50,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-27','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:38:00',3),(508,25,3,1,'Пиломатериалы: 47, 70, 300, s','Тестовый товар #270',NULL,NULL,'{\"d\": 300, \"s\": 70, \"t\": \"s\", \"v\": 47}',74025.000000,75,38,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-16','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:37:59',1),(509,25,2,1,'Пиломатериалы: 80, 60, 7, s','Тестовый товар #271',NULL,NULL,'{\"d\": 7, \"s\": 60, \"t\": \"s\", \"v\": 80}',3628.800000,108,34,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-22','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:37:58',2),(510,25,1,1,'Пиломатериалы: 10, 69, 96, v','Тестовый товар #272',NULL,NULL,'{\"d\": 96, \"s\": 69, \"t\": \"v\", \"v\": 10}',6359.040000,96,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-26','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:37:58',2),(511,25,1,1,'Пиломатериалы: 41, 69, 74, s','Тестовый товар #273',NULL,NULL,'{\"d\": 74, \"s\": 69, \"t\": \"s\", \"v\": 41}',18422.448000,88,47,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-14','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:37:57',1),(512,25,1,1,'Пиломатериалы: 95, 48, 62, s','Тестовый товар #274',NULL,NULL,'{\"d\": 62, \"s\": 48, \"t\": \"s\", \"v\": 95}',38449.920000,136,14,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-07','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:37:56',1),(513,25,2,1,'Пиломатериалы: 19, 8, 133, v','Тестовый товар #275',NULL,NULL,'{\"d\": 133, \"s\": 8, \"t\": \"v\", \"v\": 19}',1455.552000,72,25,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-17','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:37:56',3),(514,25,2,1,'Пиломатериалы: 131, 41, 96, s','Тестовый товар #276',NULL,NULL,'{\"d\": 96, \"s\": 41, \"t\": \"s\", \"v\": 131}',49499.136000,96,45,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-05','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:37:55',1),(515,25,3,1,'Пиломатериалы: 96, 28, 251, v','Тестовый товар #277',NULL,NULL,'{\"d\": 251, \"s\": 28, \"t\": \"v\", \"v\": 96}',105251.328000,156,14,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-08','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:37:54',1),(516,25,3,1,'Пиломатериалы: 46, 53, 56, v','Тестовый товар #278',NULL,NULL,'{\"d\": 56, \"s\": 53, \"t\": \"v\", \"v\": 46}',1774.864000,13,11,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-21','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:37:54',2),(517,25,1,1,'Пиломатериалы: 45, 23, 172, v','Тестовый товар #279',NULL,NULL,'{\"d\": 172, \"s\": 23, \"t\": \"v\", \"v\": 45}',23142.600000,130,42,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-19','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:37:53',1),(518,25,2,1,'Пиломатериалы: 131, 71, 61, d','Тестовый товар #280',NULL,NULL,'{\"d\": 61, \"s\": 71, \"t\": \"d\", \"v\": 131}',2836.805000,5,39,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-20','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:37:52',3),(519,25,2,1,'Пиломатериалы: 88, 63, 54, s','Тестовый товар #281',NULL,NULL,'{\"d\": 54, \"s\": 63, \"t\": \"s\", \"v\": 88}',20656.944000,69,10,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-09-04','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:37:52',3),(520,25,2,1,'Пиломатериалы: 46, 49, 251, v','Тестовый товар #282',NULL,NULL,'{\"d\": 251, \"s\": 49, \"t\": \"v\", \"v\": 46}',40168.534000,71,45,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-05','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:37:28',1),(521,25,2,1,'Пиломатериалы: 39, 52, 126, s','Тестовый товар #283',NULL,NULL,'{\"d\": 126, \"s\": 52, \"t\": \"s\", \"v\": 39}',37307.088000,146,15,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-21','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:37:27',2),(522,25,1,1,'Пиломатериалы: 57, 61, 233, s','Тестовый товар #284',NULL,NULL,'{\"d\": 233, \"s\": 61, \"t\": \"s\", \"v\": 57}',45367.896000,56,45,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-04','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:37:26',3),(523,25,3,1,'Пиломатериалы: 95, 29, 284, d','Тестовый товар #285',NULL,NULL,'{\"d\": 284, \"s\": 29, \"t\": \"d\", \"v\": 95}',68852.960000,88,36,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-29','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:37:25',3),(524,25,1,1,'Пиломатериалы: 137, 77, 43, v','Тестовый товар #286',NULL,NULL,'{\"d\": 43, \"s\": 77, \"t\": \"v\", \"v\": 137}',70762.692000,156,28,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-03','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:37:24',3),(525,25,2,1,'Пиломатериалы: 56, 71, 254, s','Тестовый товар #287',NULL,NULL,'{\"d\": 254, \"s\": 71, \"t\": \"s\", \"v\": 56}',114119.152000,113,9,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-28','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:37:24',1),(526,25,1,1,'Пиломатериалы: 54, 51, 23, d','Тестовый товар #288',NULL,NULL,'{\"d\": 23, \"s\": 51, \"t\": \"d\", \"v\": 54}',1836.918000,29,33,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-02','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:37:23',2),(527,25,3,1,'Пиломатериалы: 4, 54, 159, d','Тестовый товар #289',NULL,NULL,'{\"d\": 159, \"s\": 54, \"t\": \"d\", \"v\": 4}',3606.120000,105,21,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-23','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:37:22',2),(528,25,2,1,'Пиломатериалы: 138, 53, 246, s','Тестовый товар #290',NULL,NULL,'{\"d\": 246, \"s\": 53, \"t\": \"s\", \"v\": 138}',151136.496000,84,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-20','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:37:18',2),(529,25,3,1,'Пиломатериалы: 48, 78, 208, v','Тестовый товар #291',NULL,NULL,'{\"d\": 208, \"s\": 78, \"t\": \"v\", \"v\": 48}',60742.656000,78,28,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-27','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:37:16',1),(530,25,2,1,'Пиломатериалы: 45, 10, 295, d','Тестовый товар #292',NULL,NULL,'{\"d\": 295, \"s\": 10, \"t\": \"d\", \"v\": 45}',22567.500000,170,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-19','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:36:59',2),(531,25,1,1,'Пиломатериалы: 122, 75, 213, d','Тестовый товар #293',NULL,NULL,'{\"d\": 213, \"s\": 75, \"t\": \"d\", \"v\": 122}',91600.650000,47,25,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-19','in_transit',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 12:42:56',1),(532,25,2,1,'Пиломатериалы: 103, 27, 87, s','Тестовый товар #294',NULL,NULL,'{\"d\": 87, \"s\": 27, \"t\": \"s\", \"v\": 103}',3145.311000,13,17,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-15','in_transit',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 12:42:56',1),(533,25,1,1,'Пиломатериалы: 27, 31, 31, v','Тестовый товар #295',NULL,NULL,'{\"d\": 31, \"s\": 31, \"t\": \"v\", \"v\": 27}',1427.085000,55,40,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-16','in_transit',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 12:42:56',2),(534,25,2,1,'Пиломатериалы: 96, 68, 33, s','Тестовый товар #296',NULL,NULL,'{\"d\": 33, \"s\": 68, \"t\": \"s\", \"v\": 96}',27574.272000,128,17,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-09','in_transit',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 12:42:56',1),(535,25,1,1,'Пиломатериалы: 136, 79, 141, s','Тестовый товар #297',NULL,NULL,'{\"d\": 141, \"s\": 79, \"t\": \"s\", \"v\": 136}',248444.256000,164,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-09','in_transit',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 12:42:56',2),(536,25,1,1,'Пиломатериалы: 31, 14, 238, v','Тестовый товар #298',NULL,NULL,'{\"d\": 238, \"s\": 14, \"t\": \"v\", \"v\": 31}',9192.988000,89,37,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-28','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:36:51',1),(537,25,3,1,'Пиломатериалы: 24, 45, 7, s','Тестовый товар #299',NULL,NULL,'{\"d\": 7, \"s\": 45, \"t\": \"s\", \"v\": 24}',408.240000,54,8,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-16','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:36:48',1),(538,25,3,1,'Пиломатериалы: 27, 78, 92, v','Тестовый товар #300',NULL,NULL,'{\"d\": 92, \"s\": 78, \"t\": \"v\", \"v\": 27}',18406.440000,95,50,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-10','in_stock',NULL,NULL,1,'2025-09-07 15:46:06','2025-09-08 04:36:35',3),(539,25,1,1,'Пиломатериалы: 999, 999, 999, v',NULL,NULL,NULL,'{\"d\": \"999\", \"s\": \"999\", \"t\": \"v\", \"v\": \"999\"}',220337662.779000,221,220,'хуй',NULL,NULL,NULL,NULL,NULL,NULL,'2025-09-07','for_receipt',NULL,NULL,1,'2025-09-07 17:37:58','2025-09-08 12:43:44',1),(540,25,3,1,'Пиломатериалы: 121, 232, 343, d',NULL,NULL,NULL,'{\"d\": \"343\", \"s\": \"232\", \"t\": \"d\", \"v\": \"121\"}',5440213.240000,565,550,'dfasf',NULL,NULL,NULL,NULL,NULL,'2025-09-08','2025-09-08','in_stock',NULL,NULL,1,'2025-09-08 05:08:13','2025-09-08 13:25:30',7),(541,27,1,1,'233223233223: 3, 3, 3, 3, 3, 2sdafasd',NULL,NULL,NULL,'{\"a\": \"3\", \"d\": \"3\", \"f\": \"3\", \"g\": \"3\", \"l\": \"2sdafasd\", \"s\": \"3\"}',3.159000,13,0,'333',NULL,NULL,'dsafsdf','2025-09-08','2025-09-12','2025-09-08','2025-09-08','for_receipt','333','[]',1,'2025-09-08 08:30:05','2025-09-09 06:55:16',2),(542,26,1,1,'gbldsf: 333, 33, 333',NULL,NULL,NULL,'{\"a\": \"333\", \"f\": \"333\", \"s\": \"33\"}',3659.337000,1,0,'333',NULL,NULL,'dsafsdf','2025-09-08','2025-09-12','2025-09-08','2025-09-08','in_transit','333','[\"documents/Счет_1908.pdf\"]',1,'2025-09-08 08:30:05','2025-09-08 12:42:56',7),(543,27,1,1,'233223233223: 1, 2, 3, 4, 5, 1dsf',NULL,NULL,NULL,'{\"a\": \"1\", \"d\": \"3\", \"f\": \"4\", \"g\": \"5\", \"k\": \"6\", \"l\": \"1dsf\", \"s\": \"2\"}',0.120000,1,0,'выфаыв',NULL,NULL,'Ныва','2025-09-08',NULL,'2025-09-08','2025-09-08','in_stock',NULL,'[]',1,'2025-09-08 10:15:29','2025-09-08 12:44:42',2),(545,25,3,4,'repellat quo','Suscipit consequatur recusandae expedita.',NULL,NULL,'{\"v\": 2.42}',NULL,78,0,NULL,NULL,'Spencer-Zieme','Lake Antonio',NULL,'2025-09-16',NULL,'2024-09-08','for_receipt','Est qui non magnam ut recusandae vel nisi.',NULL,1,'2025-09-08 12:30:49','2025-09-08 12:41:29',NULL),(548,26,1,4,'Тестовый товар с уточнением','Sit adipisci et occaecati minima laboriosam officiis.',NULL,NULL,'{\"a\": 9.66, \"f\": 6.4}',62.578200,38,0,'DI6644','ZH25886341',NULL,NULL,'2025-09-01',NULL,'2025-08-29','2025-03-17','in_stock',NULL,NULL,0,'2025-09-08 12:47:28','2025-09-08 12:47:28',NULL),(549,26,2,4,'Обычный товар без уточнения',NULL,NULL,NULL,'{\"a\": 9.41, \"f\": 3.17}',91.053900,99,0,NULL,NULL,'Fisher LLC',NULL,NULL,'2025-09-11','2025-09-02','2025-06-08','in_stock',NULL,NULL,1,'2025-09-08 12:47:28','2025-09-08 12:47:28',NULL),(550,25,1,5,'Товар с уточнением 1','Sunt quia ut optio consequatur nostrum adipisci necessitatibus.',NULL,NULL,'{\"v\": 4.99}',NULL,56,0,'XH4999','PE12585804','Osinski Ltd','Port Ilachester','2025-09-05',NULL,NULL,'2025-02-24','in_stock',NULL,NULL,1,'2025-09-08 13:14:19','2025-09-08 13:14:19',NULL),(551,25,3,1,'Товар с уточнением 2',NULL,NULL,NULL,'{\"v\": 1.5}',81.245800,17,0,NULL,'ZX67889034',NULL,NULL,'2025-09-05','2025-09-15','2025-08-31','2024-12-15','in_stock',NULL,NULL,1,'2025-09-08 13:14:19','2025-09-08 13:14:19',NULL),(552,26,2,1,'Обычный товар 1',NULL,NULL,NULL,'{\"a\": 2.22, \"f\": 5.31}',NULL,68,0,'AJ0553',NULL,'Gutmann-Welch','Johnschester','2025-09-06',NULL,'2025-09-05','2025-05-29','in_stock','Eveniet nam dolorem tenetur blanditiis blanditiis facilis pariatur earum.',NULL,1,'2025-09-08 13:14:20','2025-09-08 13:14:20',NULL),(553,25,3,3,'Обычный товар 2',NULL,NULL,NULL,'{\"v\": 7.66}',9.388400,11,0,'AN0615',NULL,NULL,'Oralstad',NULL,'2025-09-14','2025-08-31','2025-09-03','in_stock','Omnis sit qui magnam sit ex ipsum debitis.',NULL,1,'2025-09-08 13:14:23','2025-09-08 13:14:23',NULL),(554,25,1,1,'Пиломатериалы: 909, 909, 909, s',NULL,NULL,NULL,'{\"d\": \"909\", \"s\": \"909\", \"t\": \"s\", \"v\": \"909\"}',751089.429000,1,0,'ывфафыва',NULL,NULL,'санктпетербуг','2025-09-08',NULL,'2025-09-08','2025-09-08','in_stock',NULL,'[\"documents/Счет_1808.pdf\"]',1,'2025-09-08 13:27:39','2025-09-08 13:27:55',7);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `requests`
--

DROP TABLE IF EXISTS `requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `warehouse_id` bigint unsigned NOT NULL,
  `product_template_id` bigint unsigned DEFAULT NULL,
  `attributes` json DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `calculated_volume` decimal(15,4) DEFAULT NULL,
  `priority` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `status` enum('pending','approved','rejected','in_progress','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `admin_notes` text COLLATE utf8mb4_unicode_ci,
  `approved_by` bigint unsigned DEFAULT NULL,
  `processed_by` bigint unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `requests_approved_by_foreign` (`approved_by`),
  KEY `requests_processed_by_foreign` (`processed_by`),
  KEY `requests_warehouse_id_status_index` (`warehouse_id`,`status`),
  KEY `requests_user_id_status_index` (`user_id`,`status`),
  KEY `requests_product_template_id_status_index` (`product_template_id`,`status`),
  KEY `requests_priority_index` (`priority`),
  KEY `requests_status_index` (`status`),
  KEY `requests_created_at_index` (`created_at`),
  CONSTRAINT `requests_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `requests_processed_by_foreign` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `requests_product_template_id_foreign` FOREIGN KEY (`product_template_id`) REFERENCES `product_templates` (`id`) ON DELETE SET NULL,
  CONSTRAINT `requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `requests_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `requests`
--

LOCK TABLES `requests` WRITE;
/*!40000 ALTER TABLE `requests` DISABLE KEYS */;
INSERT INTO `requests` VALUES (9,1,1,25,'{\"d\": \"99\", \"s\": \"99\", \"t\": \"s\", \"v\": \"99\"}','ььь','ллдлд',1000,NULL,'normal','approved',NULL,1,NULL,'2025-09-08 09:01:22',NULL,NULL,1,'2025-09-07 16:00:08','2025-09-08 09:01:22'),(10,1,1,27,'{\"a\": \"33\", \"d\": \"3\", \"f\": \"3\", \"g\": \"3\", \"k\": \"3\", \"l\": \"1dsf\", \"s\": \"3\"}','тест','описание 111',1,NULL,'normal','approved',NULL,1,NULL,'2025-09-08 09:06:51',NULL,NULL,1,'2025-09-08 09:02:30','2025-09-08 09:06:51'),(11,1,1,26,'{\"1\": \"33\", \"2\": \"333\", \"3\": \"33\"}','выафыа','3333',1333,NULL,'normal','pending',NULL,NULL,NULL,NULL,NULL,NULL,1,'2025-09-08 09:19:11','2025-09-08 09:19:11'),(12,1,1,27,'{\"a\": \"33\", \"d\": \"3\", \"f\": \"3\", \"g\": \"4\", \"k\": \"5\", \"l\": \"2sdafasd\", \"s\": \"3\"}','ывафыва','33',13,NULL,'normal','pending',NULL,NULL,NULL,NULL,NULL,NULL,1,'2025-09-08 09:22:23','2025-09-08 09:22:23'),(13,1,1,26,'{\"a\": \"333\", \"f\": \"444\", \"s\": \"33\"}','ыфваы','выла',133,NULL,'normal','pending',NULL,NULL,NULL,NULL,NULL,NULL,1,'2025-09-08 09:25:35','2025-09-08 09:25:35'),(14,1,1,26,'{\"a\": \"33\", \"f\": \"33\", \"s\": \"332\"}','3223','233232',1311,473989.4280,'normal','approved',NULL,1,NULL,'2025-09-08 09:28:48',NULL,NULL,1,'2025-09-08 09:28:00','2025-09-08 09:28:48'),(15,1,2,27,'{\"a\": \"11\", \"d\": \"33\", \"f\": \"4\", \"g\": \"55\", \"k\": \"66\", \"l\": \"2sdafasd\", \"s\": \"2\"}','выафыа','333',13,2076.3600,'normal','pending',NULL,NULL,NULL,NULL,NULL,NULL,1,'2025-09-08 09:37:35','2025-09-08 09:37:35'),(16,1,1,26,'{\"a\": \"11\", \"f\": \"33\", \"s\": \"22\"}','тест','вафыва',1,7.9860,'normal','pending',NULL,NULL,NULL,NULL,NULL,NULL,1,'2025-09-08 09:48:25','2025-09-08 09:48:25'),(17,1,2,27,'{\"a\": \"7\", \"d\": \"7\", \"f\": \"3\", \"g\": \"7\", \"k\": \"7\", \"l\": \"2sdafasd\", \"s\": \"3\"}','332','asdfasdf',1,3.0870,'normal','pending',NULL,NULL,NULL,NULL,NULL,NULL,1,'2025-09-08 09:56:37','2025-09-08 09:56:45'),(18,4,3,25,'{\"d\": \"33\", \"s\": \"33\", \"t\": \"s\", \"v\": \"33\"}','sadfasdf','',133,4779.6210,'normal','approved',NULL,1,NULL,'2025-09-08 11:32:40',NULL,NULL,1,'2025-09-08 11:30:40','2025-09-08 11:32:40'),(19,4,3,25,'{\"d\": \"33\", \"s\": \"3\", \"t\": \"v\", \"v\": \"33\"}','sadfasf','',133,434.5110,'normal','pending',NULL,NULL,NULL,NULL,NULL,NULL,1,'2025-09-08 11:34:39','2025-09-08 11:34:39'),(20,3,1,27,'{\"a\": \"11\", \"d\": \"33\", \"f\": \"44\", \"g\": \"55\", \"k\": \"66\", \"l\": \"1dsf\", \"s\": \"22\"}','dfadsfasdf','',12,231913.4400,'normal','approved',NULL,1,NULL,'2025-09-08 11:40:31',NULL,NULL,1,'2025-09-08 11:40:13','2025-09-08 11:40:31');
/*!40000 ALTER TABLE `requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales`
--

DROP TABLE IF EXISTS `sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `composite_product_key` text COLLATE utf8mb4_unicode_ci COMMENT 'Оригинальный составной ключ товара при создании продажи',
  `warehouse_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `sale_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_address` text COLLATE utf8mb4_unicode_ci,
  `quantity` int NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `cash_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `nocash_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `vat_rate` decimal(5,2) NOT NULL DEFAULT '20.00',
  `vat_amount` decimal(10,2) NOT NULL,
  `price_without_vat` decimal(10,2) NOT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'RUB',
  `exchange_rate` decimal(10,4) NOT NULL DEFAULT '1.0000',
  `payment_status` enum('pending','paid','partially_paid','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reason_cancellation` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `invoice_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sale_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sales_warehouse_id_sale_date_index` (`warehouse_id`,`sale_date`),
  KEY `sales_user_id_sale_date_index` (`user_id`,`sale_date`),
  KEY `sales_product_id_sale_date_index` (`product_id`,`sale_date`),
  KEY `sales_sale_number_index` (`sale_number`),
  KEY `sales_payment_status_index` (`payment_status`),
  KEY `sales_sale_date_index` (`sale_date`),
  CONSTRAINT `sales_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales`
--

LOCK TABLES `sales` WRITE;
/*!40000 ALTER TABLE `sales` DISABLE KEYS */;
INSERT INTO `sales` VALUES (61,133,'25|1|2|Пиломатериалы: 10, 10, 10, v',1,1,'SALE-202509-0001',NULL,NULL,NULL,NULL,13,2.54,33.00,0.00,33.00,20.00,5.50,27.50,'RUB',1.0000,'cancelled','выфафыва',NULL,NULL,'2025-09-08',1,'2025-09-08 06:04:23','2025-09-08 06:19:14'),(62,133,'25|1|2|Пиломатериалы: 10, 10, 10, v',1,1,'SALE-202509-0002',NULL,NULL,NULL,NULL,31,0.10,3.00,3.00,0.00,20.00,0.50,2.50,'RUB',1.0000,'paid',NULL,NULL,NULL,'2025-09-08',1,'2025-09-08 06:20:33','2025-09-08 06:20:33'),(63,133,'25|1|2|Пиломатериалы: 10, 10, 10, v',1,1,'SALE-202509-0003',NULL,NULL,NULL,NULL,13,0.23,3.00,3.00,0.00,20.00,0.50,2.50,'RUB',1.0000,'cancelled','выфаываыва',NULL,NULL,'2025-09-08',1,'2025-09-08 06:53:39','2025-09-08 08:46:07'),(64,133,'25|1|2|Пиломатериалы: 10, 10, 10, v',1,3,'SALE-202509-0004','33','33',NULL,'33',12,5.50,66.00,33.00,33.00,20.00,11.00,55.00,'UZS',13.0000,'paid',NULL,NULL,NULL,'2025-09-08',1,'2025-09-08 11:39:05','2025-09-08 11:39:05');
/*!40000 ALTER TABLE `sales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
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
INSERT INTO `sessions` VALUES ('1MXdagGDAWkENeioHOlq5AsqsjuEZOK1mG83RRgt',NULL,'127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.3 Safari/605.1.15','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiMm82OWVGbUpFMWFzV0g2enVxYmFjd3liZWJjek50V0xBVERodWhPMyI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czozNjoiaHR0cDovL2xvY2FsaG9zdDo4MDAwL2FkbWluL3Byb2R1Y3RzIjt9czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzM6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9hZG1pbi9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1757511129),('3bKSUI2l1yEReFVd5DnBJiNjp3wvz4UHw7do9HIL',NULL,'127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.3 Safari/605.1.15','YTozOntzOjY6Il90b2tlbiI7czo0MDoiTnJpUGZNY1E4NmJZdDFjeDRTTW1aV3A5aFNwV3Q3MjdGRjlmVU5reSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzM6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9hZG1pbi9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1757443577),('hxvrEDa6HBnc5j1cSBYW3krbRSwLnAwmcECZXa93',NULL,'127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.3 Safari/605.1.15','YTozOntzOjY6Il90b2tlbiI7czo0MDoibVIyNFhLUDRJNlQwMHBVdWhQTE9xMVIzUFY0SnJwQVdPM2hDYXBUVSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzM6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9hZG1pbi9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1757488280),('lDc2nQ7OokyqzVZP93Q96WXcTC2UlDdf0hiUYh7t',1,'127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.3 Safari/605.1.15','YTo2OntzOjY6Il90b2tlbiI7czo0MDoib3pOVFBJNzV5ZzRtV3VWbEV5RDZxYjNJQmhuSHdzMTEwclhlZmxDQyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NDA6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9hZG1pbi9yZWNlaXB0cy81NDEiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO3M6MTc6InBhc3N3b3JkX2hhc2hfd2ViIjtzOjYwOiIkMnkkMTIkZ1c2NmdHMWFtR3ZuMW9hMnBScHJTT0N3OG9LenB5dC9LcGcuVS9zL3o4ZFBiOVB6STBFaTYiO3M6ODoiZmlsYW1lbnQiO2E6MDp7fX0=',1757411722),('NY9oI9kj9K7loFJAdZ68g5YHRzxnRkpm6V5EUmPx',NULL,'127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiWFV4dG1COUplNXBIaHlWOUJsU1VnSlU2azRPQ1luR2dRYnVVWGVvZiI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czozNjoiaHR0cDovL2xvY2FsaG9zdDo4MDAwL2FkbWluL3NhbGVzLzIxIjt9czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzM6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9hZG1pbi9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1757443716),('wvOENZF5GVfcWJoY2ABzM64MPNfGdlVTk4pHeVGy',1,'127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','YTo2OntzOjY6Il90b2tlbiI7czo0MDoiYk1IUlplb1JOSFVybzYzd2l4aEE4d1c4ajdtakdFaVZWbDNMZjlseCI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjE6e3M6MzoidXJsIjtzOjQwOiJodHRwOi8vbG9jYWxob3N0OjgwMDAvYWRtaW4vcmVjZWlwdHMvNTQ1Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTtzOjE3OiJwYXNzd29yZF9oYXNoX3dlYiI7czo2MDoiJDJ5JDEyJGdXNjZnRzFhbUd2bjFvYTJwUnByU09DdzhvS3pweXQvS3BnLlUvcy96OGRQYjlQekkwRWk2Ijt9',1757406763);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'operator',
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `middle_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_id` bigint unsigned DEFAULT NULL,
  `warehouse_id` bigint unsigned DEFAULT NULL,
  `is_blocked` tinyint(1) NOT NULL DEFAULT '0',
  `blocked_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_username_unique` (`username`),
  KEY `users_company_id_foreign` (`company_id`),
  KEY `users_warehouse_id_foreign` (`warehouse_id`),
  CONSTRAINT `users_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Администратор','admin','admin@sklad.ru',NULL,'$2y$12$gW66gG1amGvn1oa2pRprSOCw8oKzpyt/Kpg.U/s/z8dPb9PzI0Ei6',NULL,'2025-09-01 16:19:25','2025-09-01 16:19:25','admin','Администратор','Системы',NULL,NULL,NULL,NULL,0,NULL),(2,'Оператор ПК','operator','operator@sklad.ru',NULL,'$2y$12$ofmueAp5gjhHmy.iHpE7nOpX4cMLSigGynhgy5Qe7SbREYsTwerFi',NULL,'2025-09-01 16:19:26','2025-09-01 16:19:26','operator','Оператор','ПК','Тестовый','+71111111111111',1,1,0,NULL),(3,'Работник склада','worker','worker@sklad.ru',NULL,'$2y$12$bOIJoWOgvJRFyfj3wxihoO.qRKsjnEAiYEZWFD4z/511gzectjuyi',NULL,'2025-09-01 16:19:26','2025-09-01 16:19:26','warehouse_worker','Работник','Склада','Тестовый','+71111111111112',1,1,0,NULL),(4,'Менеджер по продажам','manager','manager@sklad.ru',NULL,'$2y$12$fZntx0n5txPk7LkElet6M.zJTaxI.M8vLJkRObJKT8THZkf70AmAK',NULL,'2025-09-01 16:19:26','2025-09-01 16:19:26','sales_manager','Менеджер','По продажам','Тестовый','+71111111111113',2,3,0,NULL),(5,'233223 233223 233223','233223233223','ruslan@siraev.ru',NULL,'$2y$12$6PD8WF1QrDUBtni4.ZEKKuXXJ07MFlj91nY/ptRcYbpJK7mQw.AZu',NULL,'2025-09-05 17:52:04','2025-09-05 17:52:04','warehouse_worker','233223','233223','233223','233223233223',1,1,0,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warehouses`
--

DROP TABLE IF EXISTS `warehouses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `warehouses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_id` bigint unsigned NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `warehouses_company_id_is_active_index` (`company_id`,`is_active`),
  CONSTRAINT `warehouses_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warehouses`
--

LOCK TABLES `warehouses` WRITE;
/*!40000 ALTER TABLE `warehouses` DISABLE KEYS */;
INSERT INTO `warehouses` VALUES (1,'Склад №1','г. Москва, ул. Складская, д. 101',1,1,'2025-09-01 16:19:25','2025-09-02 15:07:46'),(2,'Склад №2','г. Москва, ул. Складская, д. 20',1,1,'2025-09-01 16:19:25','2025-09-01 16:19:25'),(3,'Склад №3','г. Санкт-Петербург, ул. Складская, д. 30',2,1,'2025-09-01 16:19:25','2025-09-01 16:19:25');
/*!40000 ALTER TABLE `warehouses` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-09-10 17:09:18
