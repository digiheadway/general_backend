-- CONTACTS table
CREATE TABLE contacts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  is_in_pipeline TINYINT(1) DEFAULT 0,    -- true / false (1/0)
  name VARCHAR(150) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  alter_contact VARCHAR(30),
  address TEXT,
  labels VARCHAR(255),                    -- comma separated labels
  stage VARCHAR(150),
  priority VARCHAR(50),
  requirement VARCHAR(255),
  budget BIGINT,                          -- number
  about VARCHAR(500),
  note VARCHAR(1000),
  listname VARCHAR(150),
  source VARCHAR(150),
  custom_fields VARCHAR(1000),            -- store as JSON string or CSV
  type VARCHAR(100),
  assignd_to VARCHAR(150),                -- as you asked (varchar)
  admin_id VARCHAR(150),                  -- as you asked (varchar)
  email VARCHAR(150),
  lead_scrore INT DEFAULT 0,
  last_note VARCHAR(1000),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY ix_contacts_phone (phone),
  KEY ix_contacts_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ACTIVITIES table
CREATE TABLE activities (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type ENUM('task','activity') DEFAULT 'activity', -- task or activity
  time DATETIME,                                   -- time and date
  reminder TINYINT(1) DEFAULT 0,                   -- 1 or 0
  completed TINYINT(1) DEFAULT 0,                  -- 1 or 0
  note VARCHAR(1000),
  response VARCHAR(500),
  notified TINYINT(1) DEFAULT 0,                   -- 1 or 0
  snoozed DATETIME NULL,                           -- time date or NULL
  alerty TINYINT(1) DEFAULT 0,                     -- 1 or 0
  reminder_type ENUM('alert','notification') DEFAULT 'notification',
  assigned_to VARCHAR(150),                        -- as you asked (varchar)
  contact_id BIGINT UNSIGNED NULL,                 -- optional link to contacts.id
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY ix_activities_time (time),
  KEY ix_activities_contact (contact_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- USERS table
CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(150) UNIQUE,
  password_hash VARCHAR(255),      -- store hashed password
  token VARCHAR(255),
  city VARCHAR(100),
  business_type VARCHAR(150),
  custom_fields TEXT,              -- JSON or CSV
  stages TEXT,                     -- saved stage list (JSON/C SV)
  types TEXT,                      -- saved types (JSON/CSV)
  lists TEXT,                      -- lists user can access
  sources TEXT,                    -- saved sources
  feilds_allowed TEXT,             -- fields allowed in lists (note: spelling kept as given)
  country VARCHAR(100),
  currency VARCHAR(20),
  labels TEXT,                     -- user level labels (JSON/CSV)
  company VARCHAR(200),
  admin TINYINT(1) DEFAULT 0,
  country_code VARCHAR(20),
  lang VARCHAR(10) DEFAULT 'en',
  feilds_on_list TEXT,             -- fields on list (note: spelling kept as given)
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY ix_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
