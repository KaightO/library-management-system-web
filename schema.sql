-- Simple Library Management System schema (MySQL / MariaDB)
-- Create a database first (example): CREATE DATABASE library_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- Then run: USE library_db; and execute this file.

CREATE TABLE IF NOT EXISTS books (
  id               VARCHAR(32)  NOT NULL,
  isbn             VARCHAR(32)  NULL,
  title            VARCHAR(255) NOT NULL,
  author           VARCHAR(255) NOT NULL,
  category         VARCHAR(80)  NULL,
  publish_year     INT          NULL,
  copies_total     INT          NOT NULL DEFAULT 1,
  copies_available INT          NOT NULL DEFAULT 1,
  shelf            VARCHAR(40)  NULL,
  language         VARCHAR(40)  NULL,
  description      TEXT         NULL,
  created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS members (
  id         VARCHAR(32)  NOT NULL,
  name       VARCHAR(255) NOT NULL,
  status     VARCHAR(20)  NOT NULL DEFAULT 'Active',
  phone      VARCHAR(40)  NULL,
  email      VARCHAR(255) NULL,
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
);

-- Optional seed data
INSERT INTO books (id, isbn, title, author, category, publish_year, copies_total, copies_available, shelf, language)
VALUES
  ('BK-00001', '9780132350884', 'Clean Code', 'Robert C. Martin', 'Technology', 2008, 5, 5, 'A-3', 'English'),
  ('BK-00231', NULL, 'A Brief History of Time', 'Stephen Hawking', 'Science', 1988, 2, 2, 'B-1', 'English'),
  ('BK-00412', NULL, '1984', 'George Orwell', 'Fiction', 1949, 3, 2, 'C-2', 'English')
ON DUPLICATE KEY UPDATE id = id;

INSERT INTO members (id, name, status, phone, email)
VALUES
  ('MB-0017', 'A. Member', 'Active', '+1 555 0789', 'member@library.test'),
  ('MB-0031', 'Jordan Reader', 'Pending', '+1 555 0990', 'jordan@library.test')
ON DUPLICATE KEY UPDATE id = id;
