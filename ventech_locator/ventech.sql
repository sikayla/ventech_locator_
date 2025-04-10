-- Create the database if it doesn't exist
CREATE DATABASE IF NOT EXISTS ventech_db;
USE ventech_db;

-- USERS TABLE
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    contact_number VARCHAR(20),
    location VARCHAR(100),
    client_name VARCHAR(100),
    client_email VARCHAR(100),
    client_phone VARCHAR(20),
    client_address TEXT,
    role ENUM('admin', 'guest', 'client') DEFAULT 'guest',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- SAMPLE USER
INSERT INTO users (username, email, password, contact_number, location, role)
VALUES (
    'kaylaok123',  -- Change this to a unique username
    'kaylatizon5@gmail.com',
    '$2y$10$4ULv/NJcXUyCZBkFQyDtr.0g6IxE5ZBlAi4pbxv2.67xdWamNEoqC',
    '09612345678',
    'Manila',
    'client'
);


-- VENUE TABLE
CREATE TABLE IF NOT EXISTS venue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    image_path VARCHAR(255),
    description TEXT,
    latitude DOUBLE NOT NULL DEFAULT 0,
    longitude DOUBLE NOT NULL DEFAULT 0,
    location VARCHAR(255),
    additional_info TEXT,
    reviews INT DEFAULT 0 CHECK (reviews >= 0),
    amenities TEXT,
    wifi ENUM('yes', 'no') DEFAULT 'no',
    parking ENUM('yes', 'no') DEFAULT 'no',
    status ENUM('open', 'closed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_lat_long ON venue(latitude, longitude);
CREATE FULLTEXT INDEX idx_venue_search ON venue(title, description);

-- RESERVATIONS TABLE
CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venue_id INT NOT NULL,
    guest_name VARCHAR(100),
    guest_email VARCHAR(100),
    check_in DATE,
    check_out DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (venue_id) REFERENCES venue(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- UNAVAILABLE DATES
CREATE TABLE IF NOT EXISTS unavailable_dates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venue_id INT NOT NULL,
    unavailable_date DATE NOT NULL,
    FOREIGN KEY (venue_id) REFERENCES venue(id) ON DELETE CASCADE,
    UNIQUE (venue_id, unavailable_date)
) ENGINE=InnoDB;

-- AMENITIES TABLE
CREATE TABLE IF NOT EXISTS venue_amenities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venue_id INT NOT NULL,
    amenity_name VARCHAR(100) NOT NULL,
    FOREIGN KEY (venue_id) REFERENCES venue(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- VENUE IMAGES
CREATE TABLE IF NOT EXISTS venue_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venue_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (venue_id) REFERENCES venue(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- VENUE DETAILS
CREATE TABLE IF NOT EXISTS venue_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venue_id INT NOT NULL,
    video_tour VARCHAR(255),
    map_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (venue_id) REFERENCES venue(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- MEDIA (IMAGE/VIDEO)
CREATE TABLE IF NOT EXISTS venue_media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venue_id INT NOT NULL,
    media_type ENUM('image', 'video') NOT NULL,
    media_url VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (venue_id) REFERENCES venue(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- VENUE REVIEWS
CREATE TABLE IF NOT EXISTS venue_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venue_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (venue_id) REFERENCES venue(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_reviews ON venue_reviews(venue_id, rating);

-- CLIENT INFO (For storing backup client profile optionally)
CREATE TABLE IF NOT EXISTS client_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venue_id INT,
    client_name VARCHAR(100) NOT NULL,
    client_email VARCHAR(100) NOT NULL,
    client_phone VARCHAR(20),
    client_address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (venue_id) REFERENCES venue(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- EVENT BOOKINGS TABLE (OPTIONAL - For date-specific bookings)
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venue_id INT NOT NULL,
    user_id INT NOT NULL,
    event_date DATE NOT NULL,
    num_persons INT DEFAULT 1,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (venue_id) REFERENCES venue(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE (venue_id, user_id, event_date)
) ENGINE=InnoDB;
