/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `account_transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `account_transfers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `transfer_date` date NOT NULL,
  `from_account_id` bigint(20) unsigned NOT NULL,
  `to_account_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `account_transfers_from_account_id_foreign` (`from_account_id`),
  KEY `account_transfers_to_account_id_foreign` (`to_account_id`),
  KEY `account_transfers_created_by_foreign` (`created_by`),
  CONSTRAINT `account_transfers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `account_transfers_from_account_id_foreign` FOREIGN KEY (`from_account_id`) REFERENCES `accounts` (`id`),
  CONSTRAINT `account_transfers_to_account_id_foreign` FOREIGN KEY (`to_account_id`) REFERENCES `accounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `account_code` varchar(255) NOT NULL,
  `account_name` varchar(255) NOT NULL,
  `account_type` enum('Asset','Liability','Equity','Income','Expense') NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `opening_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `current_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounts_account_code_unique` (`account_code`),
  KEY `accounts_parent_id_foreign` (`parent_id`),
  CONSTRAINT `accounts_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `module` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `activity_logs_user_id_foreign` (`user_id`),
  KEY `activity_logs_module_index` (`module`),
  CONSTRAINT `activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `backup_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `backup_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `file_name` varchar(255) NOT NULL,
  `file_size` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `backup_logs_created_by_foreign` (`created_by`),
  CONSTRAINT `backup_logs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bank_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bank_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `account_name` varchar(255) NOT NULL,
  `bank_name` varchar(255) NOT NULL,
  `account_number` varchar(255) NOT NULL,
  `branch` varchar(255) DEFAULT NULL,
  `balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bank_accounts_account_number_unique` (`account_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bank_reconciliations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bank_reconciliations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `bank_account_id` bigint(20) unsigned NOT NULL,
  `statement_date` date NOT NULL,
  `statement_balance` decimal(15,2) NOT NULL,
  `book_balance` decimal(15,2) NOT NULL,
  `reconciled_date` date DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bank_reconciliations_bank_account_id_foreign` (`bank_account_id`),
  KEY `bank_reconciliations_created_by_foreign` (`created_by`),
  CONSTRAINT `bank_reconciliations_bank_account_id_foreign` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bank_reconciliations_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `barcode_print_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `barcode_print_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `quantity` int(11) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `barcode_print_logs_product_id_foreign` (`product_id`),
  KEY `barcode_print_logs_user_id_foreign` (`user_id`),
  CONSTRAINT `barcode_print_logs_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `barcode_print_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `barcode_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `barcode_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `barcode_settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `barcode_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `barcode_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `paper_size` varchar(255) NOT NULL DEFAULT 'A4',
  `content_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`content_settings`)),
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `branch_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branch_users` (
  `branch_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`branch_id`,`user_id`),
  KEY `branch_users_user_id_foreign` (`user_id`),
  CONSTRAINT `branch_users_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `branch_users_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `branches_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `brands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `brands` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `brands_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `business_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `business_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `business_settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `capital_drawings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `capital_drawings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('capital','drawing') NOT NULL,
  `transaction_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `account_id` bigint(20) unsigned NOT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `capital_drawings_account_id_foreign` (`account_id`),
  KEY `capital_drawings_created_by_foreign` (`created_by`),
  CONSTRAINT `capital_drawings_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`),
  CONSTRAINT `capital_drawings_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_slug_unique` (`slug`),
  KEY `categories_parent_id_foreign` (`parent_id`),
  CONSTRAINT `categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customer_due_summaries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customer_due_summaries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `due_amount` decimal(15,2) NOT NULL,
  `date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_due_summaries_customer_id_foreign` (`customer_id`),
  KEY `customer_due_summaries_date_index` (`date`),
  CONSTRAINT `customer_due_summaries_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customer_opening_balances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customer_opening_balances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `date` date NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_opening_balances_customer_id_foreign` (`customer_id`),
  KEY `customer_opening_balances_created_by_foreign` (`created_by`),
  CONSTRAINT `customer_opening_balances_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customer_opening_balances_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customer_payment_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customer_payment_allocations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_payment_id` bigint(20) unsigned NOT NULL,
  `sale_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_payment_allocations_customer_payment_id_foreign` (`customer_payment_id`),
  KEY `customer_payment_allocations_sale_id_foreign` (`sale_id`),
  CONSTRAINT `customer_payment_allocations_customer_payment_id_foreign` FOREIGN KEY (`customer_payment_id`) REFERENCES `customer_payments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_payment_allocations_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customer_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customer_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` varchar(255) NOT NULL DEFAULT 'Cash',
  `reference_no` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_payments_customer_id_foreign` (`customer_id`),
  KEY `customer_payments_created_by_foreign` (`created_by`),
  KEY `customer_payments_payment_date_index` (`payment_date`),
  CONSTRAINT `customer_payments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customer_payments_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customer_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customer_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `transactionable_type` varchar(255) DEFAULT NULL,
  `transactionable_id` bigint(20) unsigned DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `type` varchar(255) NOT NULL,
  `debit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `credit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `balance_after` decimal(15,2) NOT NULL DEFAULT 0.00,
  `reference_no` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_transactions_customer_id_foreign` (`customer_id`),
  KEY `cust_txn_type_id_index` (`transactionable_type`,`transactionable_id`),
  KEY `customer_transactions_created_by_foreign` (`created_by`),
  KEY `customer_transactions_transaction_date_index` (`transaction_date`),
  KEY `customer_transactions_type_index` (`type`),
  KEY `customer_transactions_reference_no_index` (`reference_no`),
  CONSTRAINT `customer_transactions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customer_transactions_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `tax_number` varchar(255) DEFAULT NULL,
  `points` int(11) NOT NULL DEFAULT 0,
  `balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `opening_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `credit_limit` decimal(15,2) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customers_created_by_foreign` (`created_by`),
  KEY `customers_updated_by_foreign` (`updated_by`),
  KEY `customers_name_index` (`name`),
  KEY `customers_email_index` (`email`),
  KEY `customers_phone_index` (`phone`),
  CONSTRAINT `customers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customers_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `daily_closings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `daily_closings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `opening_cash` decimal(15,2) NOT NULL DEFAULT 0.00,
  `retail_sales` decimal(15,2) NOT NULL DEFAULT 0.00,
  `wholesale_sales` decimal(15,2) NOT NULL DEFAULT 0.00,
  `customer_due_collections` decimal(15,2) NOT NULL DEFAULT 0.00,
  `supplier_payments` decimal(15,2) NOT NULL DEFAULT 0.00,
  `expenses` decimal(15,2) NOT NULL DEFAULT 0.00,
  `purchase_payments` decimal(15,2) NOT NULL DEFAULT 0.00,
  `cash_in` decimal(15,2) NOT NULL DEFAULT 0.00,
  `cash_out` decimal(15,2) NOT NULL DEFAULT 0.00,
  `expected_cash_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `actual_cash_counted` decimal(15,2) NOT NULL DEFAULT 0.00,
  `difference` decimal(15,2) NOT NULL DEFAULT 0.00,
  `closing_note` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `daily_closings_date_unique` (`date`),
  KEY `daily_closings_created_by_foreign` (`created_by`),
  CONSTRAINT `daily_closings_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `daily_summaries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `daily_summaries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `total_sales` decimal(15,2) NOT NULL DEFAULT 0.00,
  `retail_sales` decimal(15,2) NOT NULL DEFAULT 0.00,
  `wholesale_sales` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_purchases` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_profit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_expenses` decimal(15,2) NOT NULL DEFAULT 0.00,
  `customer_due_collected` decimal(15,2) NOT NULL DEFAULT 0.00,
  `supplier_due_paid` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `daily_summaries_date_unique` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `damaged_stocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `damaged_stocks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` bigint(20) unsigned NOT NULL DEFAULT 1,
  `damage_no` varchar(255) NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `quantity` decimal(15,2) NOT NULL,
  `damage_type` varchar(255) NOT NULL,
  `reason` text NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `estimated_loss` decimal(15,2) NOT NULL,
  `responsible_person` varchar(255) DEFAULT NULL,
  `document_path` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `damaged_stocks_damage_no_unique` (`damage_no`),
  KEY `damaged_stocks_product_id_foreign` (`product_id`),
  KEY `damaged_stocks_created_by_foreign` (`created_by`),
  KEY `damaged_stocks_store_id_foreign` (`store_id`),
  CONSTRAINT `damaged_stocks_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `damaged_stocks_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `damaged_stocks_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `developer_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `developer_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `developer_settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `distribution_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `distribution_order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `quantity` decimal(15,2) NOT NULL,
  `reserved_quantity` decimal(15,2) NOT NULL DEFAULT 0.00,
  `unit_price` decimal(15,2) NOT NULL,
  `discount_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `distribution_order_items_order_id_foreign` (`order_id`),
  KEY `distribution_order_items_product_id_foreign` (`product_id`),
  CONSTRAINT `distribution_order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `distribution_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `distribution_order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `distribution_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `distribution_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_no` varchar(255) NOT NULL,
  `lorry_id` bigint(20) unsigned NOT NULL,
  `trip_id` bigint(20) unsigned DEFAULT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `salesperson_id` bigint(20) unsigned DEFAULT NULL,
  `order_date` datetime NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `reserved_stock` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `distribution_orders_order_no_unique` (`order_no`),
  KEY `distribution_orders_lorry_id_foreign` (`lorry_id`),
  KEY `distribution_orders_trip_id_foreign` (`trip_id`),
  KEY `distribution_orders_customer_id_foreign` (`customer_id`),
  KEY `distribution_orders_salesperson_id_foreign` (`salesperson_id`),
  KEY `distribution_orders_created_by_foreign` (`created_by`),
  KEY `distribution_orders_order_date_index` (`order_date`),
  KEY `distribution_orders_status_index` (`status`),
  CONSTRAINT `distribution_orders_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `distribution_orders_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `distribution_orders_lorry_id_foreign` FOREIGN KEY (`lorry_id`) REFERENCES `lorries` (`id`),
  CONSTRAINT `distribution_orders_salesperson_id_foreign` FOREIGN KEY (`salesperson_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `distribution_orders_trip_id_foreign` FOREIGN KEY (`trip_id`) REFERENCES `distribution_trips` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `distribution_trips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `distribution_trips` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `trip_no` varchar(255) NOT NULL,
  `lorry_id` bigint(20) unsigned NOT NULL,
  `driver_id` bigint(20) unsigned DEFAULT NULL,
  `helper_id` bigint(20) unsigned DEFAULT NULL,
  `route_name` varchar(255) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `opening_odometer` decimal(12,2) NOT NULL DEFAULT 0.00,
  `closing_odometer` decimal(12,2) DEFAULT NULL,
  `opening_cash` decimal(15,2) NOT NULL DEFAULT 0.00,
  `closing_cash` decimal(15,2) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'planned',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `distribution_trips_trip_no_unique` (`trip_no`),
  KEY `distribution_trips_lorry_id_foreign` (`lorry_id`),
  KEY `distribution_trips_driver_id_foreign` (`driver_id`),
  KEY `distribution_trips_helper_id_foreign` (`helper_id`),
  KEY `distribution_trips_created_by_foreign` (`created_by`),
  KEY `distribution_trips_status_index` (`status`),
  CONSTRAINT `distribution_trips_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `distribution_trips_driver_id_foreign` FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `distribution_trips_helper_id_foreign` FOREIGN KEY (`helper_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `distribution_trips_lorry_id_foreign` FOREIGN KEY (`lorry_id`) REFERENCES `lorries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `due_payment_reminders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `due_payment_reminders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `party_type` enum('Customer','Supplier') NOT NULL,
  `party_id` bigint(20) unsigned NOT NULL,
  `due_amount` decimal(15,2) NOT NULL,
  `due_date` date NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `due_payment_reminders_due_date_index` (`due_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `email_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'sent',
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `email_logs_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `expense_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expense_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `expense_categories_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `expense_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expense_documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `expense_id` bigint(20) unsigned NOT NULL,
  `document_path` varchar(255) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expense_documents_expense_id_foreign` (`expense_id`),
  CONSTRAINT `expense_documents_expense_id_foreign` FOREIGN KEY (`expense_id`) REFERENCES `expenses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expenses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `expense_no` varchar(255) NOT NULL,
  `expense_date` date NOT NULL,
  `expense_category_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` varchar(255) NOT NULL,
  `paid_from_account_id` bigint(20) unsigned NOT NULL,
  `lorry_id` bigint(20) unsigned DEFAULT NULL,
  `trip_id` bigint(20) unsigned DEFAULT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `expenses_expense_no_unique` (`expense_no`),
  KEY `expenses_expense_category_id_foreign` (`expense_category_id`),
  KEY `expenses_paid_from_account_id_foreign` (`paid_from_account_id`),
  KEY `expenses_created_by_foreign` (`created_by`),
  KEY `expenses_lorry_id_foreign` (`lorry_id`),
  KEY `expenses_trip_id_foreign` (`trip_id`),
  CONSTRAINT `expenses_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `expenses_expense_category_id_foreign` FOREIGN KEY (`expense_category_id`) REFERENCES `expense_categories` (`id`),
  CONSTRAINT `expenses_lorry_id_foreign` FOREIGN KEY (`lorry_id`) REFERENCES `lorries` (`id`) ON DELETE SET NULL,
  CONSTRAINT `expenses_paid_from_account_id_foreign` FOREIGN KEY (`paid_from_account_id`) REFERENCES `accounts` (`id`),
  CONSTRAINT `expenses_trip_id_foreign` FOREIGN KEY (`trip_id`) REFERENCES `distribution_trips` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` varchar(255) NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hold_sale_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hold_sale_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `hold_sale_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `variant_id` bigint(20) unsigned DEFAULT NULL,
  `quantity` decimal(15,2) NOT NULL,
  `cost_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `selling_price` decimal(15,2) NOT NULL,
  `price_type` enum('retail','wholesale') NOT NULL DEFAULT 'retail',
  `subtotal` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hold_sale_items_hold_sale_id_foreign` (`hold_sale_id`),
  KEY `hold_sale_items_product_id_foreign` (`product_id`),
  KEY `hold_sale_items_variant_id_foreign` (`variant_id`),
  CONSTRAINT `hold_sale_items_hold_sale_id_foreign` FOREIGN KEY (`hold_sale_id`) REFERENCES `hold_sales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hold_sale_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `hold_sale_items_variant_id_foreign` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hold_sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hold_sales` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `reference_no` varchar(255) NOT NULL,
  `hold_date` date NOT NULL,
  `pos_type` enum('retail','wholesale') NOT NULL DEFAULT 'retail',
  `total_amount` decimal(15,2) NOT NULL,
  `discount_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `estimated_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `note` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hold_sales_reference_no_unique` (`reference_no`),
  KEY `hold_sales_customer_id_foreign` (`customer_id`),
  KEY `hold_sales_created_by_foreign` (`created_by`),
  KEY `hold_sales_hold_date_index` (`hold_date`),
  CONSTRAINT `hold_sales_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `hold_sales_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoice_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoice_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` smallint(5) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `journal_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `journal_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `journal_no` varchar(255) NOT NULL,
  `journal_date` date NOT NULL,
  `reference_type` varchar(255) DEFAULT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_auto_generated` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `journal_entries_journal_no_unique` (`journal_no`),
  KEY `journal_entries_created_by_foreign` (`created_by`),
  CONSTRAINT `journal_entries_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `journal_entry_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `journal_entry_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `journal_entry_id` bigint(20) unsigned NOT NULL,
  `account_id` bigint(20) unsigned NOT NULL,
  `debit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `credit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `journal_entry_items_journal_entry_id_foreign` (`journal_entry_id`),
  KEY `journal_entry_items_account_id_foreign` (`account_id`),
  CONSTRAINT `journal_entry_items_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`),
  CONSTRAINT `journal_entry_items_journal_entry_id_foreign` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lorries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lorries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lorry_no` varchar(255) NOT NULL,
  `plate_no` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `driver_name` varchar(255) DEFAULT NULL,
  `driver_phone` varchar(255) DEFAULT NULL,
  `helper_name` varchar(255) DEFAULT NULL,
  `route_area` varchar(255) DEFAULT NULL,
  `capacity_note` varchar(255) DEFAULT NULL,
  `opening_odometer` decimal(12,2) NOT NULL DEFAULT 0.00,
  `current_odometer` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lorries_lorry_no_unique` (`lorry_no`),
  KEY `lorries_created_by_foreign` (`created_by`),
  KEY `lorries_plate_no_index` (`plate_no`),
  KEY `lorries_status_index` (`status`),
  CONSTRAINT `lorries_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lorry_collections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lorry_collections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `collection_no` varchar(255) NOT NULL,
  `lorry_id` bigint(20) unsigned NOT NULL,
  `trip_id` bigint(20) unsigned DEFAULT NULL,
  `collection_date` date NOT NULL,
  `payment_method` varchar(255) NOT NULL DEFAULT 'Cash',
  `amount` decimal(15,2) NOT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lorry_collections_collection_no_unique` (`collection_no`),
  KEY `lorry_collections_lorry_id_foreign` (`lorry_id`),
  KEY `lorry_collections_trip_id_foreign` (`trip_id`),
  KEY `lorry_collections_created_by_foreign` (`created_by`),
  KEY `lorry_collections_collection_date_index` (`collection_date`),
  CONSTRAINT `lorry_collections_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lorry_collections_lorry_id_foreign` FOREIGN KEY (`lorry_id`) REFERENCES `lorries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lorry_collections_trip_id_foreign` FOREIGN KEY (`trip_id`) REFERENCES `distribution_trips` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lorry_reconciliations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lorry_reconciliations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reconciliation_no` varchar(255) NOT NULL,
  `lorry_id` bigint(20) unsigned NOT NULL,
  `trip_id` bigint(20) unsigned DEFAULT NULL,
  `reconciliation_date` date NOT NULL,
  `sales_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `collections_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `expenses_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `expected_cash` decimal(15,2) NOT NULL DEFAULT 0.00,
  `counted_cash` decimal(15,2) NOT NULL DEFAULT 0.00,
  `variance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lorry_reconciliations_reconciliation_no_unique` (`reconciliation_no`),
  KEY `lorry_reconciliations_lorry_id_foreign` (`lorry_id`),
  KEY `lorry_reconciliations_trip_id_foreign` (`trip_id`),
  KEY `lorry_reconciliations_created_by_foreign` (`created_by`),
  KEY `lorry_reconciliations_reconciliation_date_index` (`reconciliation_date`),
  KEY `lorry_reconciliations_status_index` (`status`),
  CONSTRAINT `lorry_reconciliations_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lorry_reconciliations_lorry_id_foreign` FOREIGN KEY (`lorry_id`) REFERENCES `lorries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lorry_reconciliations_trip_id_foreign` FOREIGN KEY (`trip_id`) REFERENCES `distribution_trips` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lorry_sale_returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lorry_sale_returns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `return_no` varchar(255) NOT NULL,
  `sale_id` bigint(20) unsigned DEFAULT NULL,
  `lorry_id` bigint(20) unsigned DEFAULT NULL,
  `trip_id` bigint(20) unsigned DEFAULT NULL,
  `return_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `reason` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lorry_sale_returns_return_no_unique` (`return_no`),
  KEY `lorry_sale_returns_sale_id_foreign` (`sale_id`),
  KEY `lorry_sale_returns_lorry_id_foreign` (`lorry_id`),
  KEY `lorry_sale_returns_trip_id_foreign` (`trip_id`),
  KEY `lorry_sale_returns_created_by_foreign` (`created_by`),
  KEY `lorry_sale_returns_return_date_index` (`return_date`),
  KEY `lorry_sale_returns_status_index` (`status`),
  CONSTRAINT `lorry_sale_returns_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lorry_sale_returns_lorry_id_foreign` FOREIGN KEY (`lorry_id`) REFERENCES `lorries` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lorry_sale_returns_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lorry_sale_returns_trip_id_foreign` FOREIGN KEY (`trip_id`) REFERENCES `distribution_trips` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lorry_stock_transfer_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lorry_stock_transfer_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `transfer_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `variant_id` bigint(20) unsigned DEFAULT NULL,
  `quantity` decimal(15,2) NOT NULL,
  `cost_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `selling_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lorry_stock_transfer_items_transfer_id_foreign` (`transfer_id`),
  KEY `lorry_stock_transfer_items_product_id_foreign` (`product_id`),
  KEY `lorry_stock_transfer_items_variant_id_foreign` (`variant_id`),
  CONSTRAINT `lorry_stock_transfer_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lorry_stock_transfer_items_transfer_id_foreign` FOREIGN KEY (`transfer_id`) REFERENCES `lorry_stock_transfers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lorry_stock_transfer_items_variant_id_foreign` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lorry_stock_transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lorry_stock_transfers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `transfer_no` varchar(255) NOT NULL,
  `transfer_type` varchar(255) NOT NULL,
  `from_store_id` bigint(20) unsigned DEFAULT NULL,
  `to_store_id` bigint(20) unsigned DEFAULT NULL,
  `from_lorry_id` bigint(20) unsigned DEFAULT NULL,
  `to_lorry_id` bigint(20) unsigned DEFAULT NULL,
  `trip_id` bigint(20) unsigned DEFAULT NULL,
  `transfer_date` date NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lorry_stock_transfers_transfer_no_unique` (`transfer_no`),
  KEY `lorry_stock_transfers_from_store_id_foreign` (`from_store_id`),
  KEY `lorry_stock_transfers_to_store_id_foreign` (`to_store_id`),
  KEY `lorry_stock_transfers_from_lorry_id_foreign` (`from_lorry_id`),
  KEY `lorry_stock_transfers_to_lorry_id_foreign` (`to_lorry_id`),
  KEY `lorry_stock_transfers_trip_id_foreign` (`trip_id`),
  KEY `lorry_stock_transfers_created_by_foreign` (`created_by`),
  KEY `lorry_stock_transfers_transfer_type_index` (`transfer_type`),
  KEY `lorry_stock_transfers_transfer_date_index` (`transfer_date`),
  KEY `lorry_stock_transfers_status_index` (`status`),
  CONSTRAINT `lorry_stock_transfers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lorry_stock_transfers_from_lorry_id_foreign` FOREIGN KEY (`from_lorry_id`) REFERENCES `lorries` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lorry_stock_transfers_from_store_id_foreign` FOREIGN KEY (`from_store_id`) REFERENCES `stores` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lorry_stock_transfers_to_lorry_id_foreign` FOREIGN KEY (`to_lorry_id`) REFERENCES `lorries` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lorry_stock_transfers_to_store_id_foreign` FOREIGN KEY (`to_store_id`) REFERENCES `stores` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lorry_stock_transfers_trip_id_foreign` FOREIGN KEY (`trip_id`) REFERENCES `distribution_trips` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lorry_stocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lorry_stocks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lorry_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `variant_id` bigint(20) unsigned DEFAULT NULL,
  `quantity` decimal(15,2) NOT NULL DEFAULT 0.00,
  `minimum_stock` decimal(15,2) NOT NULL DEFAULT 0.00,
  `maximum_stock` decimal(15,2) DEFAULT NULL,
  `last_loaded_at` timestamp NULL DEFAULT NULL,
  `last_sold_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lorry_stock_unique_item` (`lorry_id`,`product_id`,`variant_id`),
  KEY `lorry_stocks_product_id_foreign` (`product_id`),
  KEY `lorry_stocks_variant_id_foreign` (`variant_id`),
  CONSTRAINT `lorry_stocks_lorry_id_foreign` FOREIGN KEY (`lorry_id`) REFERENCES `lorries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lorry_stocks_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lorry_stocks_variant_id_foreign` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lorry_user_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lorry_user_assignments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `lorry_id` bigint(20) unsigned NOT NULL,
  `lorry_role` varchar(255) NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `can_sell` tinyint(1) NOT NULL DEFAULT 0,
  `can_create_orders` tinyint(1) NOT NULL DEFAULT 0,
  `can_collect` tinyint(1) NOT NULL DEFAULT 0,
  `can_add_expenses` tinyint(1) NOT NULL DEFAULT 0,
  `can_view_cost` tinyint(1) NOT NULL DEFAULT 0,
  `can_view_profit` tinyint(1) NOT NULL DEFAULT 0,
  `can_reconcile` tinyint(1) NOT NULL DEFAULT 0,
  `active_from` date NOT NULL,
  `active_until` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `assigned_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lorry_user_assignments_user_id_lorry_id_unique` (`user_id`,`lorry_id`),
  KEY `lorry_user_assignments_lorry_id_foreign` (`lorry_id`),
  KEY `lorry_user_assignments_assigned_by_foreign` (`assigned_by`),
  KEY `lorry_user_assignments_lorry_role_index` (`lorry_role`),
  CONSTRAINT `lorry_user_assignments_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lorry_user_assignments_lorry_id_foreign` FOREIGN KEY (`lorry_id`) REFERENCES `lorries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lorry_user_assignments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `low_stock_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `low_stock_alerts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `current_stock` decimal(15,2) NOT NULL,
  `alert_quantity` decimal(15,2) NOT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `low_stock_alerts_product_id_foreign` (`product_id`),
  CONSTRAINT `low_stock_alerts_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notification_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notification_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notification_settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `passkeys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `passkeys` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `credential_id` varchar(255) NOT NULL,
  `credential` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`credential`)),
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `passkeys_credential_id_unique` (`credential_id`),
  KEY `passkeys_user_id_index` (`user_id`),
  CONSTRAINT `passkeys_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payment_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_methods` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_methods_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL DEFAULT 'web',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `petty_cash_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `petty_cash_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entry_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `transaction_type` enum('deposit','expense') NOT NULL,
  `recipient_name` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `petty_cash_transactions_created_by_foreign` (`created_by`),
  CONSTRAINT `petty_cash_transactions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pos_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pos_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pos_settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `printer_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `printer_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `printer_settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_daily_summaries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_daily_summaries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `quantity_sold` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_sales_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_cost_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_profit_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_daily_summaries_date_product_id_unique` (`date`,`product_id`),
  KEY `product_daily_summaries_product_id_foreign` (`product_id`),
  KEY `product_daily_summaries_date_index` (`date`),
  CONSTRAINT `product_daily_summaries_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_images` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_images_product_id_foreign` (`product_id`),
  CONSTRAINT `product_images_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_location_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_location_stock` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `store_location_id` bigint(20) unsigned NOT NULL,
  `quantity` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_location_stock_product_id_foreign` (`product_id`),
  KEY `product_location_stock_store_location_id_foreign` (`store_location_id`),
  CONSTRAINT `product_location_stock_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_location_stock_store_location_id_foreign` FOREIGN KEY (`store_location_id`) REFERENCES `store_locations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_prices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_prices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `cost_price` decimal(15,2) NOT NULL,
  `retail_price` decimal(15,2) NOT NULL,
  `wholesale_price` decimal(15,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_prices_product_id_foreign` (`product_id`),
  CONSTRAINT `product_prices_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_serials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_serials` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `serial_number` varchar(255) NOT NULL,
  `status` enum('available','sold','returned') NOT NULL DEFAULT 'available',
  `purchase_id` bigint(20) unsigned DEFAULT NULL,
  `sale_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_serials_serial_number_unique` (`serial_number`),
  KEY `product_serials_product_id_foreign` (`product_id`),
  KEY `product_serials_purchase_id_foreign` (`purchase_id`),
  KEY `product_serials_sale_id_foreign` (`sale_id`),
  KEY `product_serials_status_index` (`status`),
  CONSTRAINT `product_serials_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_serials_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_serials_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_store_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_store_stock` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `store_id` bigint(20) unsigned NOT NULL,
  `quantity` decimal(15,2) NOT NULL DEFAULT 0.00,
  `minimum_stock` decimal(15,2) NOT NULL DEFAULT 5.00,
  `maximum_stock` decimal(15,2) DEFAULT NULL,
  `last_purchase` timestamp NULL DEFAULT NULL,
  `last_sale` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_store_stock_product_id_store_id_unique` (`product_id`,`store_id`),
  KEY `product_store_stock_store_id_foreign` (`store_id`),
  CONSTRAINT `product_store_stock_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_store_stock_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_variants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_variants` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `color` varchar(255) DEFAULT NULL,
  `storage` varchar(255) DEFAULT NULL,
  `ram` varchar(255) DEFAULT NULL,
  `sku` varchar(255) DEFAULT NULL,
  `cost_price` decimal(15,2) DEFAULT NULL,
  `retail_price` decimal(15,2) DEFAULT NULL,
  `wholesale_price` decimal(15,2) DEFAULT NULL,
  `stock_quantity` decimal(15,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_variants_sku_unique` (`sku`),
  KEY `product_variants_product_id_foreign` (`product_id`),
  CONSTRAINT `product_variants_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_warranties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_warranties` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `duration_months` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_warranties_product_id_foreign` (`product_id`),
  CONSTRAINT `product_warranties_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `brand_id` bigint(20) unsigned DEFAULT NULL,
  `unit_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `sku` varchar(255) NOT NULL,
  `barcode` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `cost_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `retail_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `wholesale_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `stock_alert_quantity` int(11) NOT NULL DEFAULT 5,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_sku_unique` (`sku`),
  UNIQUE KEY `products_barcode_unique` (`barcode`),
  KEY `products_category_id_foreign` (`category_id`),
  KEY `products_brand_id_foreign` (`brand_id`),
  KEY `products_unit_id_foreign` (`unit_id`),
  KEY `products_created_by_foreign` (`created_by`),
  KEY `products_updated_by_foreign` (`updated_by`),
  CONSTRAINT `products_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purchase_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_id` bigint(20) unsigned NOT NULL,
  `document_path` varchar(255) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_documents_purchase_id_foreign` (`purchase_id`),
  CONSTRAINT `purchase_documents_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purchase_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `quantity` decimal(15,2) NOT NULL,
  `unit_cost` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount_percent` decimal(8,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_percent` decimal(8,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `profit_margin` decimal(8,2) NOT NULL DEFAULT 0.00,
  `cost_price` decimal(15,2) NOT NULL,
  `retail_price` decimal(15,2) NOT NULL,
  `wholesale_price` decimal(15,2) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_items_purchase_id_foreign` (`purchase_id`),
  KEY `purchase_items_product_id_foreign` (`product_id`),
  CONSTRAINT `purchase_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `purchase_items_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purchase_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_id` bigint(20) unsigned NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` varchar(255) NOT NULL DEFAULT 'Cash',
  `reference_no` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_payments_purchase_id_foreign` (`purchase_id`),
  KEY `purchase_payments_created_by_foreign` (`created_by`),
  KEY `purchase_payments_payment_date_index` (`payment_date`),
  CONSTRAINT `purchase_payments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_payments_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purchase_return_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_return_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_return_id` bigint(20) unsigned NOT NULL,
  `purchase_item_id` bigint(20) unsigned DEFAULT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `quantity` decimal(15,2) NOT NULL,
  `cost_price` decimal(15,2) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_return_items_purchase_return_id_foreign` (`purchase_return_id`),
  KEY `purchase_return_items_product_id_foreign` (`product_id`),
  KEY `purchase_return_items_purchase_item_id_foreign` (`purchase_item_id`),
  CONSTRAINT `purchase_return_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `purchase_return_items_purchase_item_id_foreign` FOREIGN KEY (`purchase_item_id`) REFERENCES `purchase_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_return_items_purchase_return_id_foreign` FOREIGN KEY (`purchase_return_id`) REFERENCES `purchase_returns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purchase_returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_returns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_id` bigint(20) unsigned DEFAULT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `return_no` varchar(255) NOT NULL,
  `return_date` date NOT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `refund_method` varchar(255) NOT NULL DEFAULT 'Supplier Credit',
  `refund_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `refund_status` varchar(255) NOT NULL DEFAULT 'Pending',
  `reason` text DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_returns_return_no_unique` (`return_no`),
  KEY `purchase_returns_purchase_id_foreign` (`purchase_id`),
  KEY `purchase_returns_supplier_id_foreign` (`supplier_id`),
  KEY `purchase_returns_created_by_foreign` (`created_by`),
  KEY `purchase_returns_return_date_index` (`return_date`),
  CONSTRAINT `purchase_returns_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_returns_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_returns_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purchases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchases` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` bigint(20) unsigned NOT NULL DEFAULT 1,
  `purchase_no` varchar(255) DEFAULT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `invoice_no` varchar(255) NOT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `purchase_date` date NOT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount_type` varchar(255) NOT NULL DEFAULT 'none',
  `discount_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `shipping_cost` decimal(15,2) NOT NULL DEFAULT 0.00,
  `shipping_handling` varchar(255) NOT NULL DEFAULT 'purchase_total',
  `grand_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(15,2) NOT NULL,
  `paid_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `due_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `document_path` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `stock_updated` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(255) NOT NULL DEFAULT 'received',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchases_invoice_no_unique` (`invoice_no`),
  UNIQUE KEY `purchases_purchase_no_unique` (`purchase_no`),
  KEY `purchases_supplier_id_foreign` (`supplier_id`),
  KEY `purchases_created_by_foreign` (`created_by`),
  KEY `purchases_reference_no_index` (`reference_no`),
  KEY `purchases_purchase_date_index` (`purchase_date`),
  KEY `purchases_store_id_foreign` (`store_id`),
  CONSTRAINT `purchases_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchases_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`),
  CONSTRAINT `purchases_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `repair_activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repair_activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `repair_job_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `repair_activity_logs_repair_job_id_foreign` (`repair_job_id`),
  KEY `repair_activity_logs_user_id_foreign` (`user_id`),
  CONSTRAINT `repair_activity_logs_repair_job_id_foreign` FOREIGN KEY (`repair_job_id`) REFERENCES `repair_jobs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `repair_activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `repair_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repair_assignments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `repair_job_id` bigint(20) unsigned NOT NULL,
  `technician_id` bigint(20) unsigned NOT NULL,
  `assigned_by` bigint(20) unsigned DEFAULT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `repair_assignments_repair_job_id_foreign` (`repair_job_id`),
  KEY `repair_assignments_technician_id_foreign` (`technician_id`),
  KEY `repair_assignments_assigned_by_foreign` (`assigned_by`),
  CONSTRAINT `repair_assignments_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`),
  CONSTRAINT `repair_assignments_repair_job_id_foreign` FOREIGN KEY (`repair_job_id`) REFERENCES `repair_jobs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `repair_assignments_technician_id_foreign` FOREIGN KEY (`technician_id`) REFERENCES `repair_technicians` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `repair_charge_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repair_charge_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `repair_job_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `repair_charge_items_repair_job_id_foreign` (`repair_job_id`),
  CONSTRAINT `repair_charge_items_repair_job_id_foreign` FOREIGN KEY (`repair_job_id`) REFERENCES `repair_jobs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `repair_checklists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repair_checklists` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `repair_job_id` bigint(20) unsigned NOT NULL,
  `items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`items`)),
  `custom_items` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `repair_checklists_repair_job_id_foreign` (`repair_job_id`),
  CONSTRAINT `repair_checklists_repair_job_id_foreign` FOREIGN KEY (`repair_job_id`) REFERENCES `repair_jobs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `repair_devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repair_devices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `repair_job_id` bigint(20) unsigned NOT NULL,
  `device_type` varchar(255) DEFAULT NULL,
  `brand` varchar(255) DEFAULT NULL,
  `model` varchar(255) DEFAULT NULL,
  `imei_1` varchar(255) DEFAULT NULL,
  `imei_2` varchar(255) DEFAULT NULL,
  `serial_number` varchar(255) DEFAULT NULL,
  `color` varchar(255) DEFAULT NULL,
  `storage` varchar(255) DEFAULT NULL,
  `ram` varchar(255) DEFAULT NULL,
  `carrier` varchar(255) DEFAULT NULL,
  `pattern_lock` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `pin` varchar(255) DEFAULT NULL,
  `google_lock` tinyint(1) NOT NULL DEFAULT 0,
  `apple_id_lock` tinyint(1) NOT NULL DEFAULT 0,
  `battery_percent` tinyint(3) unsigned DEFAULT NULL,
  `accessories_received` text DEFAULT NULL,
  `physical_condition` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`physical_condition`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `repair_devices_repair_job_id_foreign` (`repair_job_id`),
  CONSTRAINT `repair_devices_repair_job_id_foreign` FOREIGN KEY (`repair_job_id`) REFERENCES `repair_jobs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `repair_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repair_images` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `repair_job_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) DEFAULT NULL,
  `path` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `repair_images_repair_job_id_foreign` (`repair_job_id`),
  CONSTRAINT `repair_images_repair_job_id_foreign` FOREIGN KEY (`repair_job_id`) REFERENCES `repair_jobs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `repair_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repair_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `job_no` varchar(255) NOT NULL,
  `barcode` varchar(255) NOT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `store_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `primary_technician_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('Checked In','Diagnosis','Waiting Approval','Waiting Parts','Assigned','Under Repair','Quality Check','Ready for Pickup','Completed','Delivered','Cancelled','Rejected') NOT NULL DEFAULT 'Checked In',
  `problem_description` text DEFAULT NULL,
  `repair_notes` text DEFAULT NULL,
  `customer_request` text DEFAULT NULL,
  `estimated_cost` decimal(15,2) NOT NULL DEFAULT 0.00,
  `labor_charge` decimal(15,2) NOT NULL DEFAULT 0.00,
  `diagnosis_fee` decimal(15,2) NOT NULL DEFAULT 0.00,
  `service_charge` decimal(15,2) NOT NULL DEFAULT 0.00,
  `software_charge` decimal(15,2) NOT NULL DEFAULT 0.00,
  `other_charge` decimal(15,2) NOT NULL DEFAULT 0.00,
  `parts_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount_type` varchar(20) NOT NULL DEFAULT 'fixed',
  `discount_value` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `advance_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `balance_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `urgent` tinyint(1) NOT NULL DEFAULT 0,
  `is_warranty` tinyint(1) NOT NULL DEFAULT 0,
  `expected_completion_date` date DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `repair_jobs_job_no_unique` (`job_no`),
  UNIQUE KEY `repair_jobs_barcode_unique` (`barcode`),
  KEY `repair_jobs_customer_id_foreign` (`customer_id`),
  KEY `repair_jobs_store_id_foreign` (`store_id`),
  KEY `repair_jobs_created_by_foreign` (`created_by`),
  KEY `repair_jobs_primary_technician_id_foreign` (`primary_technician_id`),
  CONSTRAINT `repair_jobs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `repair_jobs_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `repair_jobs_primary_technician_id_foreign` FOREIGN KEY (`primary_technician_id`) REFERENCES `repair_technicians` (`id`),
  CONSTRAINT `repair_jobs_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `repair_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repair_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `repair_job_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'Internal',
  `note` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `repair_notes_repair_job_id_foreign` (`repair_job_id`),
  KEY `repair_notes_user_id_foreign` (`user_id`),
  CONSTRAINT `repair_notes_repair_job_id_foreign` FOREIGN KEY (`repair_job_id`) REFERENCES `repair_jobs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `repair_notes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `repair_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repair_notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `repair_job_id` bigint(20) unsigned DEFAULT NULL,
  `channel` varchar(255) NOT NULL,
  `event` varchar(255) NOT NULL,
  `recipient` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('Pending','Sent','Failed') NOT NULL DEFAULT 'Pending',
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `repair_notifications_repair_job_id_foreign` (`repair_job_id`),
  CONSTRAINT `repair_notifications_repair_job_id_foreign` FOREIGN KEY (`repair_job_id`) REFERENCES `repair_jobs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `repair_parts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repair_parts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `repair_job_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `store_id` bigint(20) unsigned DEFAULT NULL,
  `quantity` decimal(15,2) NOT NULL,
  `cost_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `selling_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('Reserved','Issued','Returned') NOT NULL DEFAULT 'Issued',
  `issued_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `repair_parts_repair_job_id_foreign` (`repair_job_id`),
  KEY `repair_parts_product_id_foreign` (`product_id`),
  KEY `repair_parts_store_id_foreign` (`store_id`),
  KEY `repair_parts_issued_by_foreign` (`issued_by`),
  CONSTRAINT `repair_parts_issued_by_foreign` FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`),
  CONSTRAINT `repair_parts_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `repair_parts_repair_job_id_foreign` FOREIGN KEY (`repair_job_id`) REFERENCES `repair_jobs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `repair_parts_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `repair_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repair_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `repair_job_id` bigint(20) unsigned NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` varchar(255) NOT NULL DEFAULT 'Cash',
  `reference_no` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `repair_payments_repair_job_id_foreign` (`repair_job_id`),
  KEY `repair_payments_created_by_foreign` (`created_by`),
  CONSTRAINT `repair_payments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `repair_payments_repair_job_id_foreign` FOREIGN KEY (`repair_job_id`) REFERENCES `repair_jobs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `repair_status_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repair_status_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `repair_job_id` bigint(20) unsigned NOT NULL,
  `from_status` varchar(255) DEFAULT NULL,
  `to_status` varchar(255) NOT NULL,
  `note` text DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `repair_status_logs_repair_job_id_foreign` (`repair_job_id`),
  KEY `repair_status_logs_user_id_foreign` (`user_id`),
  CONSTRAINT `repair_status_logs_repair_job_id_foreign` FOREIGN KEY (`repair_job_id`) REFERENCES `repair_jobs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `repair_status_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `repair_technicians`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repair_technicians` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `specialization` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive','On Leave') NOT NULL DEFAULT 'Active',
  `commission_rate` decimal(8,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `repair_warranties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repair_warranties` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `repair_job_id` bigint(20) unsigned NOT NULL,
  `warranty_type` varchar(255) NOT NULL DEFAULT 'Days',
  `duration` int(11) NOT NULL DEFAULT 30,
  `starts_at` date DEFAULT NULL,
  `expires_at` date DEFAULT NULL,
  `terms` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `repair_warranties_repair_job_id_foreign` (`repair_job_id`),
  CONSTRAINT `repair_warranties_repair_job_id_foreign` FOREIGN KEY (`repair_job_id`) REFERENCES `repair_jobs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `report_exports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `report_exports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `report_name` varchar(255) NOT NULL,
  `format` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `report_exports_created_by_foreign` (`created_by`),
  CONSTRAINT `report_exports_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_permissions` (
  `role_id` bigint(20) unsigned NOT NULL,
  `permission_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `role_permissions_permission_id_foreign` (`permission_id`),
  CONSTRAINT `role_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `guard_name` varchar(255) NOT NULL DEFAULT 'web',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_system_role` tinyint(1) NOT NULL DEFAULT 0,
  `is_fixed` tinyint(1) NOT NULL DEFAULT 0,
  `is_deletable` tinyint(1) NOT NULL DEFAULT 1,
  `is_renamable` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_unique` (`name`),
  UNIQUE KEY `roles_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sale_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sale_documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sale_id` bigint(20) unsigned NOT NULL,
  `document_path` varchar(255) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_documents_sale_id_foreign` (`sale_id`),
  CONSTRAINT `sale_documents_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sale_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sale_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sale_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `product_serial_id` bigint(20) unsigned DEFAULT NULL,
  `variant_id` bigint(20) unsigned DEFAULT NULL,
  `quantity` decimal(15,2) NOT NULL,
  `cost_price` decimal(15,2) NOT NULL,
  `selling_price` decimal(15,2) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `price_type` enum('retail','wholesale') NOT NULL DEFAULT 'retail',
  `discount_type` varchar(255) NOT NULL DEFAULT 'fixed',
  `discount_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `profit_amount` decimal(15,2) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_items_sale_id_foreign` (`sale_id`),
  KEY `sale_items_product_id_foreign` (`product_id`),
  KEY `sale_items_variant_id_foreign` (`variant_id`),
  KEY `sale_items_product_serial_id_foreign` (`product_serial_id`),
  CONSTRAINT `sale_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `sale_items_product_serial_id_foreign` FOREIGN KEY (`product_serial_id`) REFERENCES `product_serials` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sale_items_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sale_items_variant_id_foreign` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sale_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sale_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sale_id` bigint(20) unsigned NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` varchar(255) NOT NULL DEFAULT 'Cash',
  `reference_no` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_payments_sale_id_foreign` (`sale_id`),
  KEY `sale_payments_created_by_foreign` (`created_by`),
  KEY `sale_payments_payment_date_index` (`payment_date`),
  CONSTRAINT `sale_payments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sale_payments_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sale_return_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sale_return_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sale_return_id` bigint(20) unsigned NOT NULL,
  `sale_item_id` bigint(20) unsigned DEFAULT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `variant_id` bigint(20) unsigned DEFAULT NULL,
  `quantity` decimal(15,2) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `refund_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `selling_price` decimal(15,2) NOT NULL,
  `cost_price` decimal(15,2) NOT NULL,
  `profit_amount` decimal(15,2) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_return_items_sale_return_id_foreign` (`sale_return_id`),
  KEY `sale_return_items_product_id_foreign` (`product_id`),
  KEY `sale_return_items_sale_item_id_foreign` (`sale_item_id`),
  KEY `sale_return_items_variant_id_foreign` (`variant_id`),
  CONSTRAINT `sale_return_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `sale_return_items_sale_item_id_foreign` FOREIGN KEY (`sale_item_id`) REFERENCES `sale_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sale_return_items_sale_return_id_foreign` FOREIGN KEY (`sale_return_id`) REFERENCES `sale_returns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sale_return_items_variant_id_foreign` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sale_returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sale_returns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sale_id` bigint(20) unsigned DEFAULT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `return_no` varchar(255) NOT NULL,
  `return_date` date NOT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `refund_method` varchar(255) NOT NULL DEFAULT 'No Refund',
  `refund_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `refund_status` varchar(255) NOT NULL DEFAULT 'Pending',
  `reason` text DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sale_returns_return_no_unique` (`return_no`),
  KEY `sale_returns_sale_id_foreign` (`sale_id`),
  KEY `sale_returns_customer_id_foreign` (`customer_id`),
  KEY `sale_returns_created_by_foreign` (`created_by`),
  KEY `sale_returns_return_date_index` (`return_date`),
  KEY `sale_returns_refund_status_index` (`refund_status`),
  CONSTRAINT `sale_returns_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sale_returns_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `sale_returns_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` bigint(20) unsigned NOT NULL DEFAULT 1,
  `source_type` varchar(255) NOT NULL DEFAULT 'store',
  `lorry_id` bigint(20) unsigned DEFAULT NULL,
  `trip_id` bigint(20) unsigned DEFAULT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `invoice_no` varchar(255) NOT NULL,
  `sale_date` date NOT NULL,
  `pos_type` enum('retail','wholesale') NOT NULL DEFAULT 'retail',
  `total_amount` decimal(15,2) NOT NULL,
  `discount_type` varchar(255) NOT NULL DEFAULT 'fixed',
  `discount_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `payable_amount` decimal(15,2) NOT NULL,
  `paid_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `due_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `payment_status` varchar(255) NOT NULL DEFAULT 'Due',
  `total_cost_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_profit_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` varchar(255) NOT NULL DEFAULT 'final',
  `notes` text DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sales_invoice_no_unique` (`invoice_no`),
  KEY `sales_customer_id_foreign` (`customer_id`),
  KEY `sales_created_by_foreign` (`created_by`),
  KEY `sales_sale_date_index` (`sale_date`),
  KEY `sales_pos_type_index` (`pos_type`),
  KEY `sales_payment_status_index` (`payment_status`),
  KEY `sales_store_id_foreign` (`store_id`),
  KEY `sales_lorry_id_foreign` (`lorry_id`),
  KEY `sales_trip_id_foreign` (`trip_id`),
  KEY `sales_source_type_index` (`source_type`),
  CONSTRAINT `sales_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `sales_lorry_id_foreign` FOREIGN KEY (`lorry_id`) REFERENCES `lorries` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`),
  CONSTRAINT `sales_trip_id_foreign` FOREIGN KEY (`trip_id`) REFERENCES `distribution_trips` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sms_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sms_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `phone_number` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'sent',
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `sms_logs_phone_number_index` (`phone_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stock_adjustment_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_adjustment_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `stock_adjustment_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `current_stock` decimal(15,2) NOT NULL,
  `quantity` decimal(15,2) NOT NULL,
  `new_stock` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_adjustment_items_stock_adjustment_id_foreign` (`stock_adjustment_id`),
  KEY `stock_adjustment_items_product_id_foreign` (`product_id`),
  CONSTRAINT `stock_adjustment_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_adjustment_items_stock_adjustment_id_foreign` FOREIGN KEY (`stock_adjustment_id`) REFERENCES `stock_adjustments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stock_adjustments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_adjustments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` bigint(20) unsigned NOT NULL DEFAULT 1,
  `adjustment_no` varchar(255) NOT NULL,
  `adjustment_date` date NOT NULL,
  `adjustment_type` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'approved',
  `reason` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `document_path` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stock_adjustments_adjustment_no_unique` (`adjustment_no`),
  KEY `stock_adjustments_created_by_foreign` (`created_by`),
  KEY `stock_adjustments_store_id_foreign` (`store_id`),
  CONSTRAINT `stock_adjustments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `stock_adjustments_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stock_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_histories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `store_id` bigint(20) unsigned NOT NULL DEFAULT 1,
  `location_type` varchar(255) NOT NULL DEFAULT 'store',
  `lorry_id` bigint(20) unsigned DEFAULT NULL,
  `trip_id` bigint(20) unsigned DEFAULT NULL,
  `variant_id` bigint(20) unsigned DEFAULT NULL,
  `quantity_change` decimal(15,2) NOT NULL,
  `new_quantity` decimal(15,2) NOT NULL,
  `type` varchar(255) NOT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `quantity_before` decimal(15,2) DEFAULT NULL,
  `reference_type` varchar(255) DEFAULT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_histories_product_id_foreign` (`product_id`),
  KEY `stock_histories_variant_id_foreign` (`variant_id`),
  KEY `stock_histories_created_by_foreign` (`created_by`),
  KEY `stock_histories_store_id_foreign` (`store_id`),
  KEY `stock_histories_lorry_id_foreign` (`lorry_id`),
  KEY `stock_histories_trip_id_foreign` (`trip_id`),
  KEY `stock_histories_location_type_index` (`location_type`),
  CONSTRAINT `stock_histories_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `stock_histories_lorry_id_foreign` FOREIGN KEY (`lorry_id`) REFERENCES `lorries` (`id`) ON DELETE SET NULL,
  CONSTRAINT `stock_histories_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_histories_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`),
  CONSTRAINT `stock_histories_trip_id_foreign` FOREIGN KEY (`trip_id`) REFERENCES `distribution_trips` (`id`) ON DELETE SET NULL,
  CONSTRAINT `stock_histories_variant_id_foreign` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stock_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_movements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `quantity` decimal(15,2) NOT NULL,
  `type` enum('purchase','sale','purchase_return','sale_return','stock_adjustment','damaged_stock','stock_transfer') NOT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_movements_product_id_foreign` (`product_id`),
  KEY `stock_movements_created_by_foreign` (`created_by`),
  KEY `stock_movements_reference_no_index` (`reference_no`),
  CONSTRAINT `stock_movements_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `stock_movements_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stock_transfer_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_transfer_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `stock_transfer_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `quantity` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_transfer_items_stock_transfer_id_foreign` (`stock_transfer_id`),
  KEY `stock_transfer_items_product_id_foreign` (`product_id`),
  CONSTRAINT `stock_transfer_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `stock_transfer_items_stock_transfer_id_foreign` FOREIGN KEY (`stock_transfer_id`) REFERENCES `stock_transfers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stock_transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_transfers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `transfer_no` varchar(255) NOT NULL,
  `from_store_id` bigint(20) unsigned NOT NULL,
  `to_store_id` bigint(20) unsigned NOT NULL,
  `transfer_date` date NOT NULL,
  `status` enum('Pending','Approved','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stock_transfers_transfer_no_unique` (`transfer_no`),
  KEY `stock_transfers_from_store_id_foreign` (`from_store_id`),
  KEY `stock_transfers_to_store_id_foreign` (`to_store_id`),
  KEY `stock_transfers_created_by_foreign` (`created_by`),
  KEY `stock_transfers_approved_by_foreign` (`approved_by`),
  CONSTRAINT `stock_transfers_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  CONSTRAINT `stock_transfers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `stock_transfers_from_store_id_foreign` FOREIGN KEY (`from_store_id`) REFERENCES `stores` (`id`),
  CONSTRAINT `stock_transfers_to_store_id_foreign` FOREIGN KEY (`to_store_id`) REFERENCES `stores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `store_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `store_locations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `store_locations_branch_id_foreign` (`branch_id`),
  CONSTRAINT `store_locations_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stores` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `manager` varchar(255) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stores_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `supplier_due_summaries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplier_due_summaries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `due_amount` decimal(15,2) NOT NULL,
  `date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `supplier_due_summaries_supplier_id_foreign` (`supplier_id`),
  KEY `supplier_due_summaries_date_index` (`date`),
  CONSTRAINT `supplier_due_summaries_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `supplier_opening_balances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplier_opening_balances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `date` date NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `supplier_opening_balances_supplier_id_foreign` (`supplier_id`),
  KEY `supplier_opening_balances_created_by_foreign` (`created_by`),
  CONSTRAINT `supplier_opening_balances_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `supplier_opening_balances_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `supplier_payment_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplier_payment_allocations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_payment_id` bigint(20) unsigned NOT NULL,
  `purchase_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `supplier_payment_allocations_supplier_payment_id_foreign` (`supplier_payment_id`),
  KEY `supplier_payment_allocations_purchase_id_foreign` (`purchase_id`),
  CONSTRAINT `supplier_payment_allocations_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `supplier_payment_allocations_supplier_payment_id_foreign` FOREIGN KEY (`supplier_payment_id`) REFERENCES `supplier_payments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `supplier_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplier_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` varchar(255) NOT NULL DEFAULT 'Cash',
  `reference_no` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `supplier_payments_supplier_id_foreign` (`supplier_id`),
  KEY `supplier_payments_created_by_foreign` (`created_by`),
  KEY `supplier_payments_payment_date_index` (`payment_date`),
  CONSTRAINT `supplier_payments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `supplier_payments_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `supplier_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplier_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `transactionable_type` varchar(255) DEFAULT NULL,
  `transactionable_id` bigint(20) unsigned DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `type` varchar(255) NOT NULL,
  `debit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `credit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `balance_after` decimal(15,2) NOT NULL DEFAULT 0.00,
  `reference_no` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `supplier_transactions_supplier_id_foreign` (`supplier_id`),
  KEY `supp_txn_type_id_index` (`transactionable_type`,`transactionable_id`),
  KEY `supplier_transactions_created_by_foreign` (`created_by`),
  KEY `supplier_transactions_transaction_date_index` (`transaction_date`),
  KEY `supplier_transactions_type_index` (`type`),
  KEY `supplier_transactions_reference_no_index` (`reference_no`),
  CONSTRAINT `supplier_transactions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `supplier_transactions_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `suppliers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `tax_number` varchar(255) DEFAULT NULL,
  `balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `opening_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `bank_details` text DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `suppliers_created_by_foreign` (`created_by`),
  KEY `suppliers_updated_by_foreign` (`updated_by`),
  KEY `suppliers_name_index` (`name`),
  KEY `suppliers_company_name_index` (`company_name`),
  KEY `suppliers_email_index` (`email`),
  KEY `suppliers_phone_index` (`phone`),
  CONSTRAINT `suppliers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `suppliers_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `system_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `log_level` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `context` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tax_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tax_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tax_settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `units` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `short_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_roles` (
  `user_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `user_roles_role_id_foreign` (`role_id`),
  CONSTRAINT `user_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_roles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `user_type` varchar(255) NOT NULL DEFAULT 'office',
  `default_lorry_id` bigint(20) unsigned DEFAULT NULL,
  `two_factor_secret` text DEFAULT NULL,
  `two_factor_recovery_codes` text DEFAULT NULL,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_username_unique` (`username`),
  KEY `users_default_lorry_id_foreign` (`default_lorry_id`),
  KEY `users_user_type_index` (`user_type`),
  CONSTRAINT `users_default_lorry_id_foreign` FOREIGN KEY (`default_lorry_id`) REFERENCES `lorries` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2024_01_01_000000_create_passkeys_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2025_08_14_170933_add_two_factor_columns_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2026_06_11_212516_add_username_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2026_06_12_000001_create_user_and_settings_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2026_06_12_000002_create_product_and_party_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2026_06_12_000003_create_purchase_and_sales_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2026_06_12_000004_create_accounting_and_expenses_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2026_06_12_000005_create_reports_and_other_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2026_06_13_000001_create_product_serials_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2026_06_13_122457_add_module_to_activity_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2026_06_13_122534_add_phone_and_status_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2026_06_13_122600_add_description_to_roles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2026_06_13_123000_create_product_variants_and_stock_histories_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2026_06_13_130234_enhance_purchase_module_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2026_06_13_132500_add_variant_id_to_sale_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2026_06_14_000001_enhance_sales_module_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2026_06_14_000002_backfill_demo_sale_items',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2026_06_14_000003_backfill_demo_purchase_items',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2026_06_14_000004_create_customers_suppliers_stock_adjustments_and_damaged_stocks_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2026_07_07_130337_create_daily_closings_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2026_07_07_134634_rebuild_accounting_tables',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2026_07_07_195202_create_multi_store_tables',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2026_07_07_195438_add_store_id_to_stock_histories',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2026_07_10_000001_add_section_settings_permissions',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2026_07_10_000002_create_repair_management_tables',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2026_07_11_000003_add_repair_workspace_pricing_fields',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2026_07_11_000004_create_repair_charge_items_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2026_07_11_000005_create_distribution_management_tables',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2026_07_15_000001_create_lorry_user_access_tables',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2026_07_15_000002_add_fixed_role_flags',12);
