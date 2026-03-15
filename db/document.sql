-- Tshijuka RDP – single database schema (everything in this file).
-- Import this file once into your Hostinger database: drops existing tables, creates all tables, seeds data.
DROP DATABASE IF EXISTS document;
CREATE DATABASE document CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE document;

CREATE TABLE `User` (
    `userID` INT PRIMARY KEY AUTO_INCREMENT,
    `userName` VARCHAR(255) NOT NULL,
    `userContact` VARCHAR(50) DEFAULT NULL,
    `userEmail` VARCHAR(255) NOT NULL,
    `userPassword` VARCHAR(255) NOT NULL,
    `userRole` ENUM('Document Seeker', 'Document Issuer', 'Admin', 'Admissions Office') NOT NULL,
    UNIQUE KEY `uq_userEmail` (`userEmail`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Subscribe (Document Issuer + Admissions Office)
-- roleType: 'Document Issuer' uses documentIssuerName/Contact/Email;
--           'Admissions Office' uses name (and User.userName/userContact).
-- -----------------------------------------------------------------------------
CREATE TABLE `Subscribe` (
    `subscribeID` INT AUTO_INCREMENT PRIMARY KEY,
    `userID` INT NOT NULL,
    `roleType` ENUM('Document Issuer', 'Admissions Office') NOT NULL DEFAULT 'Document Issuer',
    `documentIssuerName` VARCHAR(255) DEFAULT NULL,
    `documentIssuerContact` VARCHAR(50) DEFAULT NULL,
    `documentIssuerEmail` VARCHAR(255) DEFAULT NULL,
    `name` VARCHAR(255) DEFAULT NULL,
    `createdAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`userID`) REFERENCES `User`(`userID`) ON DELETE CASCADE,
    KEY `idx_subscribe_user` (`userID`),
    KEY `idx_subscribe_role` (`roleType`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- DocumentType
-- -----------------------------------------------------------------------------
CREATE TABLE `DocumentType` (
    `documentTypeID` INT PRIMARY KEY AUTO_INCREMENT,
    `typeName` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Status
-- -----------------------------------------------------------------------------
CREATE TABLE `Status` (
    `statusID` INT PRIMARY KEY AUTO_INCREMENT,
    `statusName` VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Document
-- -----------------------------------------------------------------------------
CREATE TABLE `Document` (
    `documentID` VARCHAR(50) PRIMARY KEY,
    `userID` INT DEFAULT NULL,
    `documentIssuerID` INT DEFAULT NULL,
    `documentTypeID` INT DEFAULT NULL,
    `statusID` INT DEFAULT NULL,
    `description` TEXT NOT NULL,
    `location` VARCHAR(255) DEFAULT NULL,
    `imagePath` VARCHAR(255) DEFAULT NULL,
    `imageData` LONGBLOB DEFAULT NULL COMMENT 'Image/file binary stored in DB for reliable viewing',
    `imageMime` VARCHAR(50) DEFAULT NULL COMMENT 'MIME type e.g. image/jpeg, application/pdf',
    `submissionDate` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `completionDate` DATE DEFAULT NULL,
    `payment_confirmed_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Set when admin confirms agent paid institution (cross-border); institution can then send document',
    FOREIGN KEY (`userID`) REFERENCES `User`(`userID`) ON DELETE SET NULL,
    FOREIGN KEY (`documentIssuerID`) REFERENCES `User`(`userID`) ON DELETE SET NULL,
    FOREIGN KEY (`documentTypeID`) REFERENCES `DocumentType`(`documentTypeID`),
    FOREIGN KEY (`statusID`) REFERENCES `Status`(`statusID`),
    KEY `idx_doc_user` (`userID`),
    KEY `idx_doc_issuer` (`documentIssuerID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- PrelossDocuments (optional pre-loss storage)
-- -----------------------------------------------------------------------------
CREATE TABLE `PrelossDocuments` (
    `prelossID` INT AUTO_INCREMENT PRIMARY KEY,
    `userID` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `filePath` VARCHAR(255) NOT NULL,
    `uploadedOn` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`userID`) REFERENCES `User`(`userID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- IssuerStoredDocuments (issuing institutions upload to platform without seeker request)
-- -----------------------------------------------------------------------------
CREATE TABLE `IssuerStoredDocuments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `userID` INT NOT NULL COMMENT 'Document Issuer userID',
    `title` VARCHAR(255) NOT NULL,
    `documentTypeID` INT DEFAULT NULL,
    `description` VARCHAR(500) DEFAULT NULL,
    `filePath` VARCHAR(255) NOT NULL,
    `uploadedOn` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`userID`) REFERENCES `User`(`userID`) ON DELETE CASCADE,
    FOREIGN KEY (`documentTypeID`) REFERENCES `DocumentType`(`documentTypeID`) ON DELETE SET NULL,
    KEY `idx_issuer_stored_user` (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- TshijukaPackHistory
-- -----------------------------------------------------------------------------
CREATE TABLE `TshijukaPackHistory` (
    `packID` INT AUTO_INCREMENT PRIMARY KEY,
    `userID` INT NOT NULL,
    `documentIDs` TEXT NOT NULL,
    `classification` VARCHAR(255) NOT NULL,
    `institutionEmail` VARCHAR(255) NOT NULL,
    `sharedOn` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`userID`) REFERENCES `User`(`userID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Chat (seeker–issuer + admissions; receiverID/filePath for admissions flow)
-- -----------------------------------------------------------------------------
CREATE TABLE `Chat` (
    `chatID` INT AUTO_INCREMENT PRIMARY KEY,
    `documentID` VARCHAR(50) NOT NULL,
    `senderID` INT NOT NULL,
    `receiverID` INT DEFAULT NULL,
    `message` TEXT NOT NULL,
    `filePath` VARCHAR(255) DEFAULT NULL,
    `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`documentID`) REFERENCES `Document`(`documentID`) ON DELETE CASCADE,
    FOREIGN KEY (`senderID`) REFERENCES `User`(`userID`) ON DELETE CASCADE,
    FOREIGN KEY (`receiverID`) REFERENCES `User`(`userID`) ON DELETE SET NULL,
    KEY `idx_chat_document` (`documentID`),
    KEY `idx_chat_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- UserMfa (email OTP for Document Seekers – login MFA)
-- -----------------------------------------------------------------------------
CREATE TABLE `UserMfa` (
    `userID` INT PRIMARY KEY,
    `mfaEnabled` TINYINT(1) NOT NULL DEFAULT 1,
    `createdAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`userID`) REFERENCES `User`(`userID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- PasswordResetOtp (forgot password: OTP sent by email, verified then reset)
-- -----------------------------------------------------------------------------
CREATE TABLE `PasswordResetOtp` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(255) NOT NULL,
    `otpHash` VARCHAR(64) NOT NULL,
    `expiresAt` DATETIME NOT NULL,
    `createdAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_reset_email_exp` (`email`, `expiresAt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- PaystackPayments (Paystack payment records + optional document link)
-- -----------------------------------------------------------------------------
CREATE TABLE `PaystackPayments` (
    `payment_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `reference` VARCHAR(100) NOT NULL,
    `paystack_reference` VARCHAR(100) DEFAULT NULL,
    `payment_method` VARCHAR(50) DEFAULT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    `currency` VARCHAR(10) NOT NULL DEFAULT 'GHS',
    `description` VARCHAR(255) DEFAULT NULL,
    `document_id` VARCHAR(50) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `verified_at` TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `User`(`userID`) ON DELETE CASCADE,
    KEY `idx_pay_ref` (`reference`),
    KEY `idx_pay_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- CountryAgents (cross-border: agents per country pay institutions via Momo; admin compensates via bank)
-- -----------------------------------------------------------------------------
CREATE TABLE `CountryAgents` (
    `agent_id` INT AUTO_INCREMENT PRIMARY KEY,
    `country_code` VARCHAR(10) NOT NULL COMMENT 'e.g. GHS, NGN, KES',
    `agent_name` VARCHAR(255) NOT NULL,
    `contact_phone` VARCHAR(50) DEFAULT NULL,
    `contact_email` VARCHAR(255) DEFAULT NULL,
    `momo_number` VARCHAR(50) NOT NULL COMMENT 'Mobile money number for paying institutions',
    `momo_provider` VARCHAR(100) DEFAULT NULL COMMENT 'e.g. MTN Mobile Money, M-Pesa',
    `bank_name` VARCHAR(255) DEFAULT NULL COMMENT 'For compensation from admin',
    `bank_account_number` VARCHAR(100) DEFAULT NULL,
    `bank_account_name` VARCHAR(255) DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_agent_country` (`country_code`),
    KEY `idx_agent_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- PaymentAgentFlow (links received payment to agent: agent_notified -> agent_paid_momo -> compensation_sent)
-- -----------------------------------------------------------------------------
CREATE TABLE `PaymentAgentFlow` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `payment_id` INT NOT NULL,
    `agent_id` INT NOT NULL,
    `institution_country` VARCHAR(10) DEFAULT NULL COMMENT 'Target country for the institution',
    `status` ENUM('pending_agent', 'agent_notified', 'agent_paid_momo', 'compensation_sent') NOT NULL DEFAULT 'pending_agent',
    `amount_local` DECIMAL(12,2) DEFAULT NULL COMMENT 'Amount in local currency for agent/institution',
    `notes` TEXT DEFAULT NULL,
    `assigned_at` TIMESTAMP NULL DEFAULT NULL,
    `agent_paid_at` TIMESTAMP NULL DEFAULT NULL,
    `compensation_sent_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`payment_id`) REFERENCES `PaystackPayments`(`payment_id`) ON DELETE CASCADE,
    FOREIGN KEY (`agent_id`) REFERENCES `CountryAgents`(`agent_id`) ON DELETE RESTRICT,
    KEY `idx_flow_payment` (`payment_id`),
    KEY `idx_flow_agent` (`agent_id`),
    KEY `idx_flow_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- SEED DATA
-- =============================================================================

INSERT INTO `DocumentType` (`typeName`) VALUES
('Identity'), ('Educational'), ('History'), ('Contract');

INSERT INTO `Status` (`statusName`) VALUES
('Pending'), ('In Progress'), ('Completed'), ('Cancelled');

-- Users (passwords: bcrypt; change in production)
INSERT INTO `User` (`userName`, `userContact`, `userEmail`, `userPassword`, `userRole`) VALUES
('John Doe', '123456789', 'student1@example.com', '$2y$10$dhMRIr7g.obQYOiFKJIFzecHO75NnN9dOqwvzZXEeg4s7QG3zKQgG', 'Document Seeker'),
('ABC Institute', '111222333', 'school@example.com', '$2y$10$CXFe..fqSVApR7ql5orEceMAOtfrPqEY4elP2nKYgh5gyNQR4xHUm', 'Document Issuer'),
('Tresor Ndala', '999888777', 'ndalabuzangu@gmail.com', '$2y$10$kQI0uEvnKg9rpexEYwpWr.Q7xQYNSoFjGdn3D1HxWdGRxBN0xfUJy', 'Admin');

INSERT INTO `Subscribe` (`userID`, `roleType`, `documentIssuerName`, `documentIssuerContact`, `documentIssuerEmail`) VALUES
(2, 'Document Issuer', 'ABC Institute', '111222333', 'school@example.com');

INSERT INTO `Document` (`documentID`, `userID`, `documentIssuerID`, `documentTypeID`, `statusID`, `description`, `location`, `imagePath`) VALUES
('document_001', 1, 2, 1, 1, 'Document in the main hall', 'Main Hall', 'uploads/images/RPT001.jpg'),
('document_002', 1, 2, 2, 3, 'Document in the library', 'Library', 'uploads/images/RPT002.jpg');

INSERT INTO `Chat` (`documentID`, `senderID`, `message`) VALUES
('document_001', 1, 'Hello, I submitted my document request.'),
('document_001', 2, 'We received your request and are working on it.');

ALTER TABLE `User` ADD COLUMN `terms_accepted_at` DATETIME NULL DEFAULT NULL;
ALTER TABLE `User` ADD COLUMN `privacy_accepted_at` DATETIME NULL DEFAULT NULL;

-- =============================================================================
-- END OF SCHEMA
-- =============================================================================
