-- TechPulse - Tech News Portal  (import in phpMyAdmin > Import)
CREATE DATABASE IF NOT EXISTS techpulse_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE techpulse_db;

DROP TABLE IF EXISTS comments;
DROP TABLE IF EXISTS posts;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username      VARCHAR(50)  NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE categories (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name        VARCHAR(80)  NOT NULL,
  description VARCHAR(255) NULL,
  icon        VARCHAR(40)  NOT NULL DEFAULT 'bi-cpu',
  created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_category_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE posts (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  category_id INT UNSIGNED NOT NULL,
  title       VARCHAR(200) NOT NULL,
  content     TEXT         NOT NULL,
  image       VARCHAR(255) NULL,
  status      ENUM('published','draft') NOT NULL DEFAULT 'published',
  views       INT UNSIGNED NOT NULL DEFAULT 0,
  created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_posts_category (category_id),
  KEY idx_posts_status_date (status, created_at),
  CONSTRAINT fk_posts_category FOREIGN KEY (category_id)
    REFERENCES categories (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE comments (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  post_id     INT UNSIGNED NOT NULL,
  author_name VARCHAR(60)  NOT NULL DEFAULT 'Guest',
  comment     VARCHAR(500) NOT NULL,
  created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_comments_post (post_id),
  CONSTRAINT fk_comments_post FOREIGN KEY (post_id)
    REFERENCES posts (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Login: admin / admin123  (change it after submission)
INSERT INTO users (username, password_hash) VALUES
('admin', '$2y$10$BcyLDY46KvOxAHajrzwIX.yuTX2mCOxyIimXSlRIT9xAyf/jG23Ku');

INSERT INTO categories (id, name, description, icon) VALUES
(1, 'Artificial Intelligence', 'AI tools, models and how they change our work', 'bi-robot'),
(2, 'Programming',             'Languages, frameworks and clean-code habits',    'bi-code-slash'),
(3, 'Cybersecurity',           'Stay safe online and build secure websites',     'bi-shield-lock'),
(4, 'Gadgets',                 'Laptops, phones and useful tech gear',           'bi-phone');

INSERT INTO posts (category_id, title, content, status, views, created_at) VALUES
(1, 'How AI Assistants Are Changing Everyday Work',
 'AI assistants are now built into many of the tools people already use, from email apps to code editors. They can draft text, summarize long documents and suggest fixes for errors.\n\nExperts recommend treating their output as a first draft. Checking facts and understanding the result yourself is still the most important step.',
 'published', 120, NOW() - INTERVAL 1 DAY),
(1, 'What Is Machine Learning? A Simple Explanation',
 'Traditional programs follow instructions written by a developer. A machine learning model instead learns from many examples and finds patterns by itself.\n\nA spam filter is a classic example: it studies thousands of emails marked as spam or not spam, and then predicts the label of a new email.',
 'published', 85, NOW() - INTERVAL 3 DAY),
(2, 'Why PHP Is Still Powering a Large Part of the Web',
 'PHP runs many content management systems and online stores. Modern versions added typed properties, enums, attributes and big performance gains.\n\nLearning PHP with PDO and prepared statements is a great first step toward building secure database-driven sites.',
 'published', 150, NOW() - INTERVAL 2 DAY),
(2, '5 Habits of Developers Who Write Clean Code',
 'Use clear names for variables and functions so the code explains itself. Keep functions short and focused on a single task.\n\nSeparate your project into folders, for example configuration, includes and public pages. Test your work often and read your code again after a break.',
 'published', 64, NOW() - INTERVAL 5 DAY),
(3, 'Password Safety: How to Protect Your Online Accounts',
 'Reusing the same password everywhere is risky, because one leak can expose many accounts. A password manager can create and store a unique password for each site.\n\nDevelopers must also protect users by never storing plain-text passwords. Use password_hash() and password_verify() in PHP.',
 'published', 98, NOW() - INTERVAL 4 DAY),
(4, 'Choosing a Laptop for Programming Students',
 'For web development, a laptop with 8 GB of RAM or more and a fast SSD is a comfortable starting point. A clear screen and a good keyboard also help during long sessions.\n\nTools like XAMPP and Visual Studio Code run well on modest hardware, so spend your budget on reliability and battery life.',
 'published', 52, NOW() - INTERVAL 7 DAY);

INSERT INTO comments (post_id, author_name, comment) VALUES
(1, 'Sara',  'Great article, very clear!'),
(3, 'Omar',  'PHP with PDO is exactly what we study in class.'),
(5, 'Guest', 'Thanks, I just enabled two-step verification.');
