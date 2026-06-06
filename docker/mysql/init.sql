-- ============================================================
-- MySQL Initialization Script
-- Enterprise Email Validation Platform
-- ============================================================

-- Create database
CREATE DATABASE IF NOT EXISTS email_validator
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- Create application user
CREATE USER IF NOT EXISTS 'ev_user'@'%' IDENTIFIED BY 'CHANGE_ME_STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON email_validator.* TO 'ev_user'@'%';

-- Create read-only user for replicas/reporting
CREATE USER IF NOT EXISTS 'ev_readonly'@'%' IDENTIFIED BY 'CHANGE_ME_READONLY_PASSWORD';
GRANT SELECT ON email_validator.* TO 'ev_readonly'@'%';

-- Create replication user
CREATE USER IF NOT EXISTS 'repl_user'@'%' IDENTIFIED BY 'CHANGE_ME_REPL_PASSWORD';
GRANT REPLICATION SLAVE ON *.* TO 'repl_user'@'%';

FLUSH PRIVILEGES;

-- ============================================================
-- PERFORMANCE INDEXES (added after migrations run)
-- Run these manually after migrations: mysql -u root -p email_validator < init.sql
-- ============================================================

USE email_validator;

-- Note: Laravel migrations create basic indexes.
-- These are additional composite indexes for reporting queries.

-- Validation results: fast lookup by status+date
-- CREATE INDEX idx_results_status_date ON validation_results(status, DATE(created_at));

-- Validation results: domain reputation analysis
-- CREATE INDEX idx_results_domain_status ON validation_results(domain, status);

-- Transactions: revenue reporting
-- CREATE INDEX idx_transactions_type_date ON transactions(type, DATE(created_at));

-- Validation jobs: queue processing
-- CREATE INDEX idx_jobs_status_created ON validation_jobs(status, created_at);

-- ============================================================
-- PARTITIONING (for validation_results - add after table exists)
-- Partitions validation_results by month for fast archival
-- ============================================================
-- ALTER TABLE validation_results
-- PARTITION BY RANGE (YEAR(created_at) * 100 + MONTH(created_at)) (
--     PARTITION p_2024_01 VALUES LESS THAN (202402),
--     PARTITION p_2024_02 VALUES LESS THAN (202403),
--     PARTITION p_future VALUES LESS THAN MAXVALUE
-- );
