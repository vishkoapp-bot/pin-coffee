-- 001_create_tables.sql
-- Creates items and settings tables

CREATE TABLE IF NOT EXISTS settings (
  k VARCHAR(255) PRIMARY KEY,
  v TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS items (
  id INT NOT NULL AUTO_INCREMENT,
  category VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL,
  section_icon VARCHAR(32) DEFAULT '',
  section_en VARCHAR(255) DEFAULT '',
  section_fa VARCHAR(255) DEFAULT '',
  name VARCHAR(255) NOT NULL,
  en VARCHAR(255),
  price VARCHAR(255),
  description TEXT,
  tags TEXT,
  emoji VARCHAR(32) DEFAULT '',
  featured TINYINT(1) DEFAULT 0,
  image TEXT,
  wide TINYINT(1) DEFAULT 0,
  position INT DEFAULT 0,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- index for category
CREATE INDEX idx_items_category ON items(category);
CREATE UNIQUE INDEX idx_items_slug ON items(slug);
