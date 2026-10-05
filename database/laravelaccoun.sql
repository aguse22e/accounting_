SET NAMES utf8mb4;
SET time_zone = "+00:00";
SET foreign_key_checks = 0;

DROP DATABASE IF EXISTS `laravelaccoun`;
CREATE DATABASE `laravelaccoun` ;
USE `laravelaccoun`;
-- user 
CREATE TABLE `user` (
  `Id_User` int(7) NOT NULL AUTO_INCREMENT,
  `User_Name` varchar(50) NOT NULL,
  `Email` varchar(50) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int(7) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`Id_User`),
  UNIQUE KEY `user_username_unique` (`username`),
  UNIQUE KEY `user_email_unique` (`Email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- kode_akun (chart of accounts)
CREATE TABLE `kode_akun` (
  `kode_akun` varchar(25) NOT NULL,
  `nama` varchar(40) NOT NULL,
  `tipe` enum('Debit','Kredit') DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`kode_akun`),
  KEY `kode_akun_deleted_by_foreign` (`deleted_by`),
  CONSTRAINT `kode_akun_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `user` (`Id_User`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- akun
CREATE TABLE `akun` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kode_akun` varchar(25) NOT NULL,
  `nama_akun` varchar(25) NOT NULL,
  `saldo_normal` enum('debit','kredit') NOT NULL,
  `tanggal` date DEFAULT NULL,
  `deskripsi` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `akun_kode_akun_unique` (`kode_akun`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- projects (
CREATE TABLE `projects` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kode_project` varchar(25) NOT NULL,
  `nama_project` varchar(100) NOT NULL,
  `saldo` decimal(15,2) NOT NULL DEFAULT '0.00',
  `tanggal` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `projects_kode_project_unique` (`kode_project`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- budget
CREATE TABLE `budget` (
  `kode_budget` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kode_project` varchar(25) NOT NULL,
  `kode_voucher` varchar(25) DEFAULT NULL,
  `deskripsi` varchar(255) NOT NULL,
  `anggaran` decimal(15,2) NOT NULL DEFAULT '0.00',
  `tanggal` datetime NOT NULL,
  `create_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`kode_budget`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- transaksi
CREATE TABLE `transaksi` (
  `kode_voucer` bigint unsigned NOT NULL,
  `kode_budget` bigint unsigned NOT NULL,
  `kode_project` varchar(25) NOT NULL,
  `akun_id` bigint unsigned DEFAULT NULL,
  `transaksi_type` bigint NOT NULL,
  `deskripsi` varchar(255) NOT NULL,
  `nominal` decimal(15,2) NOT NULL,
  `budget` decimal(15,2) NOT NULL,
  `tanggal` datetime NOT NULL,
  `create_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`kode_voucer`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- jurnal
CREATE TABLE `jurnal` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tanggal` date NOT NULL,
  `jenis_transaksi` enum('jurnal','Bank') NOT NULL,
  `kode_transaksi` varchar(20) NOT NULL,
  `keterangan` varchar(255) NOT NULL,
  `kode` varchar(15) NOT NULL,
  `lawan` varchar(15) NOT NULL,
  `tipe` enum('Debit','Kredit') NOT NULL,
  `nominal` decimal(15,2) NOT NULL,
  `id_detail` int DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- transaksi_bank
CREATE TABLE `transaksi_bank` (
  `kode_transaksi_bank` varchar(20) NOT NULL,
  `tanggal` date NOT NULL,
  `akun_kode` varchar(50) NOT NULL,
  `tipe` enum('Debit','Kredit') NOT NULL,
  `total` decimal(15,2) NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`kode_transaksi_bank`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- transaksi_bank_detail
CREATE TABLE `transaksi_bank_detail` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kode_transaksi_bank` varchar(20) NOT NULL,
  `kode_lawan` varchar(15) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- detail_jurnal
CREATE TABLE `detail_jurnal` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `jurnal_id` bigint unsigned NOT NULL,
  `kode_akun` varchar(15) NOT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- buku_besar
CREATE TABLE `buku_besar` (
  `Buku_Id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `Kode_Project` varchar(25) NOT NULL,
  `Id_akun` varchar(50) NOT NULL,
  `Nama_Akun` varchar(100) NOT NULL,
  `Deskripsi` varchar(255) NOT NULL,
  `Tanggal` datetime NOT NULL,
  `Budget` decimal(15,2) NOT NULL,
  `Saldo` decimal(15,2) NOT NULL,
  `Create_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Update_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`Buku_Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- laporan_keuangan
CREATE TABLE `laporan_keuangan` (
  `Id_Laporan` bigint unsigned NOT NULL AUTO_INCREMENT,
  `Kode_Project` varchar(25) NOT NULL,
  `Id_akun` varchar(50) NOT NULL,
  `Nama_Akun` varchar(100) NOT NULL,
  `Deskripsi` varchar(255) NOT NULL,
  `Tanggal` datetime NOT NULL,
  `Budget` decimal(15,2) NOT NULL,
  `Saldo` decimal(15,2) NOT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `Create_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Update_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id_Laporan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- vouchers
CREATE TABLE `vouchers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `no_project` varchar(25) NOT NULL,
  `kode_voucher` varchar(25) NOT NULL,
  `tipe_transaksi` enum('debit','kredit') NOT NULL DEFAULT 'debit',
  `tanggal` date NOT NULL,
  `deskripsi` varchar(255) NOT NULL,
  `jumlah` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- activity_users
CREATE TABLE `activity_users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(9) unsigned NOT NULL,
  `action` varchar(25) NOT NULL,
  `model_name` varchar(25) NOT NULL,
  `model_id` varchar(255) NOT NULL,
  `old_data` json DEFAULT NULL,
  `new_data` json DEFAULT NULL,
  `user_agent` varchar(25) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Foreign keys and supporting indexes

-- activity_users → user
ALTER TABLE `activity_users`
  ADD KEY `activity_users_user_id_foreign` (`user_id`),
  ADD CONSTRAINT `activity_users_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `user`(`Id_User`) ON DELETE CASCADE;



-- budget → projects (by unique kode_project)
ALTER TABLE `budget`
  ADD KEY `budget_kode_project_index` (`kode_project`),
  ADD CONSTRAINT `budget_kode_project_foreign` FOREIGN KEY (`kode_project`) REFERENCES `projects`(`kode_project`) ON DELETE CASCADE;

-- transaksi → budget, projects, akun
ALTER TABLE `transaksi`
  ADD KEY `transaksi_kode_budget_index` (`kode_budget`),
  ADD KEY `transaksi_kode_project_index` (`kode_project`),
  ADD KEY `transaksi_akun_id_index` (`akun_id`),
  ADD CONSTRAINT `transaksi_kode_budget_foreign` FOREIGN KEY (`kode_budget`) REFERENCES `budget`(`kode_budget`) ON DELETE CASCADE,
  ADD CONSTRAINT `transaksi_kode_project_foreign` FOREIGN KEY (`kode_project`) REFERENCES `projects`(`kode_project`) ON DELETE CASCADE,
  ADD CONSTRAINT `transaksi_akun_id_foreign` FOREIGN KEY (`akun_id`) REFERENCES `akun`(`id`) ON DELETE SET NULL;

-- jurnal → kode_akun (kode & lawan)
ALTER TABLE `jurnal`
  ADD KEY `jurnal_kode_index` (`kode`),
  ADD KEY `jurnal_lawan_index` (`lawan`),
  ADD KEY `jurnal_created_by_index` (`created_by`),
  ADD KEY `jurnal_updated_by_index` (`updated_by`),
  ADD CONSTRAINT `jurnal_kode_foreign` FOREIGN KEY (`kode`) REFERENCES `kode_akun`(`kode_akun`),
  ADD CONSTRAINT `jurnal_lawan_foreign` FOREIGN KEY (`lawan`) REFERENCES `kode_akun`(`kode_akun`),
  ADD CONSTRAINT `jurnal_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `user`(`Id_User`) ON DELETE SET NULL,
  ADD CONSTRAINT `jurnal_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `user`(`Id_User`) ON DELETE SET NULL;

-- transaksi_bank → kode_akun, user
ALTER TABLE `transaksi_bank`
  ADD KEY `transaksi_bank_akun_kode_index` (`akun_kode`),
  ADD KEY `transaksi_bank_deleted_by_index` (`deleted_by`),
  ADD CONSTRAINT `transaksi_bank_akun_kode_foreign` FOREIGN KEY (`akun_kode`) REFERENCES `kode_akun`(`kode_akun`),
  ADD CONSTRAINT `transaksi_bank_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `user`(`Id_User`) ON DELETE SET NULL;

-- transaksi_bank_detail → transaksi_bank, kode_akun
ALTER TABLE `transaksi_bank_detail`
  ADD KEY `transaksi_bank_detail_kode_transaksi_bank_index` (`kode_transaksi_bank`),
  ADD KEY `transaksi_bank_detail_kode_lawan_index` (`kode_lawan`),
  ADD CONSTRAINT `transaksi_bank_detail_kode_transaksi_bank_foreign` FOREIGN KEY (`kode_transaksi_bank`) REFERENCES `transaksi_bank`(`kode_transaksi_bank`) ON DELETE CASCADE,
  ADD CONSTRAINT `transaksi_bank_detail_kode_lawan_foreign` FOREIGN KEY (`kode_lawan`) REFERENCES `kode_akun`(`kode_akun`);

-- detail_jurnal → jurnal, kode_akun, user (audit)
ALTER TABLE `detail_jurnal`
  ADD KEY `detail_jurnal_jurnal_id_index` (`jurnal_id`),
  ADD KEY `detail_jurnal_kode_akun_index` (`kode_akun`),
  ADD KEY `detail_jurnal_created_by_index` (`created_by`),
  ADD KEY `detail_jurnal_updated_by_index` (`updated_by`),
  ADD CONSTRAINT `detail_jurnal_jurnal_id_foreign` FOREIGN KEY (`jurnal_id`) REFERENCES `jurnal`(`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `detail_jurnal_kode_akun_foreign` FOREIGN KEY (`kode_akun`) REFERENCES `kode_akun`(`kode_akun`),
  ADD CONSTRAINT `detail_jurnal_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `user`(`Id_User`) ON DELETE SET NULL,
  ADD CONSTRAINT `detail_jurnal_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `user`(`Id_User`) ON DELETE SET NULL;

-- buku_besar → projects, kode_akun
ALTER TABLE `buku_besar`
  ADD KEY `buku_besar_kode_project_index` (`Kode_Project`),
  ADD KEY `buku_besar_id_akun_index` (`Id_akun`),
  ADD CONSTRAINT `buku_besar_kode_project_foreign` FOREIGN KEY (`Kode_Project`) REFERENCES `projects`(`kode_project`) ON DELETE CASCADE,
  ADD CONSTRAINT `buku_besar_id_akun_foreign` FOREIGN KEY (`Id_akun`) REFERENCES `kode_akun`(`kode_akun`);

-- laporan_keuangan → projects, kode_akun
ALTER TABLE `laporan_keuangan`
  ADD KEY `laporan_keuangan_kode_project_index` (`Kode_Project`),
  ADD KEY `laporan_keuangan_id_akun_index` (`Id_akun`),
  ADD KEY `laporan_keuangan_created_by_index` (`created_by`),
  ADD KEY `laporan_keuangan_updated_by_index` (`updated_by`),
  ADD CONSTRAINT `laporan_keuangan_kode_project_foreign` FOREIGN KEY (`Kode_Project`) REFERENCES `projects`(`kode_project`) ON DELETE CASCADE,
  ADD CONSTRAINT `laporan_keuangan_id_akun_foreign` FOREIGN KEY (`Id_akun`) REFERENCES `kode_akun`(`kode_akun`),
  ADD CONSTRAINT `laporan_keuangan_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `user`(`Id_User`) ON DELETE SET NULL,
  ADD CONSTRAINT `laporan_keuangan_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `user`(`Id_User`) ON DELETE SET NULL;

-- vouchers → projects
ALTER TABLE `vouchers`
  ADD KEY `vouchers_no_project_index` (`no_project`),
  ADD CONSTRAINT `vouchers_no_project_foreign` FOREIGN KEY (`no_project`) REFERENCES `projects`(`kode_project`) ON DELETE CASCADE;

-- sessions → user
ALTER TABLE `sessions`
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD CONSTRAINT `sessions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `user`(`Id_User`) ON DELETE CASCADE;

-- Add missing indexes for better performance
CREATE INDEX `user_email_index` ON `user` (`Email`);

CREATE INDEX `kode_akun_nama_index` ON `kode_akun` (`nama`);
CREATE INDEX `kode_akun_tipe_index` ON `kode_akun` (`tipe`);

CREATE INDEX `projects_nama_project_index` ON `projects` (`nama_project`);
CREATE INDEX `projects_tanggal_index` ON `projects` (`tanggal`);

CREATE INDEX `budget_kode_voucher_index` ON `budget` (`kode_voucher`);
CREATE INDEX `budget_tanggal_index` ON `budget` (`tanggal`);

CREATE INDEX `transaksi_akun_id_index` ON `transaksi` (`akun_id`);
CREATE INDEX `transaksi_tanggal_index` ON `transaksi` (`tanggal`);

CREATE INDEX `jurnal_tanggal_index` ON `jurnal` (`tanggal`);
CREATE INDEX `jurnal_jenis_transaksi_index` ON `jurnal` (`jenis_transaksi`);

CREATE INDEX `transaksi_bank_tanggal_index` ON `transaksi_bank` (`tanggal`);
CREATE INDEX `transaksi_bank_tipe_index` ON `transaksi_bank` (`tipe`);

CREATE INDEX `buku_besar_tanggal_index` ON `buku_besar` (`Tanggal`);
CREATE INDEX `laporan_keuangan_tanggal_index` ON `laporan_keuangan` (`Tanggal`);

CREATE INDEX `vouchers_tanggal_index` ON `vouchers` (`tanggal`);
CREATE INDEX `vouchers_tipe_transaksi_index` ON `vouchers` (`tipe_transaksi`);

CREATE INDEX `activity_user_user_id_index` ON `activity_user` (`user_id`);
CREATE INDEX `activity_user_action_index` ON `activity_user` (`action`);
CREATE INDEX `activity_user_model_name_index` ON `activity_user` (`model_name`);

SET foreign_key_checks = 1;


