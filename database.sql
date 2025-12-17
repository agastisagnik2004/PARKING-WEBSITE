-- ParkingPro - Parking Subscription Management System
-- MySQL database schema

CREATE DATABASE IF NOT EXISTS parkingpro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE parkingpro;

-- Users
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  is_first_purchase TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Vehicles
CREATE TABLE IF NOT EXISTS vehicles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  number_plate VARCHAR(30) NOT NULL UNIQUE,
  type ENUM('car','bike','others') NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_vehicles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Subscription plans
CREATE TABLE IF NOT EXISTS subscription_plans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  duration_days INT NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Subscriptions
CREATE TABLE IF NOT EXISTS subscriptions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  vehicle_id INT NOT NULL,
  plan_id INT NOT NULL,
  start_at DATETIME NOT NULL,
  end_at DATETIME NOT NULL,
  price_paid DECIMAL(10,2) NOT NULL,
  invoice_id VARCHAR(50) NOT NULL,
  first_time_applied TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_subscriptions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_subscriptions_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
  CONSTRAINT fk_subscriptions_plan FOREIGN KEY (plan_id) REFERENCES subscription_plans(id) ON DELETE CASCADE,
  INDEX idx_subscriptions_vehicle (vehicle_id),
  INDEX idx_subscriptions_user (user_id),
  INDEX idx_subscriptions_dates (start_at, end_at)
) ENGINE=InnoDB;

-- Parking stations
CREATE TABLE IF NOT EXISTS parking_stations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  allowed_types VARCHAR(100) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Staff
CREATE TABLE IF NOT EXISTS staff (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  station_id INT NOT NULL,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_staff_station FOREIGN KEY (station_id) REFERENCES parking_stations(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Parking entries
CREATE TABLE IF NOT EXISTS park_entries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  station_id INT NOT NULL,
  vehicle_number VARCHAR(30) NOT NULL,
  vehicle_id INT NULL,
  user_id INT NULL,
  entry_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  exit_time DATETIME NULL,
  confirmed_by INT NOT NULL,
  status ENUM('entered','denied') NOT NULL,
  CONSTRAINT fk_park_entries_station FOREIGN KEY (station_id) REFERENCES parking_stations(id),
  CONSTRAINT fk_park_entries_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
  CONSTRAINT fk_park_entries_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT fk_park_entries_staff FOREIGN KEY (confirmed_by) REFERENCES staff(id)
) ENGINE=InnoDB;

-- Promotions (for bike/special discounts)
CREATE TABLE IF NOT EXISTS promotions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vehicle_type ENUM('car','bike','others') NOT NULL,
  discount_percent DECIMAL(5,2) NOT NULL,
  start_at DATETIME NOT NULL,
  end_at DATETIME NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Admins (for Admin Panel login)
CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Default data
INSERT INTO subscription_plans (name, duration_days, price) VALUES
  ('1 Year Plan', 365, 20599.00),
  ('6 Months Plan', 180, 9899.00),
  ('1 Month Plan', 30, 999.00)
ON DUPLICATE KEY UPDATE name = VALUES(name), duration_days = VALUES(duration_days), price = VALUES(price);

INSERT INTO parking_stations (name, allowed_types) VALUES
  ('Main Station', 'car,bike,others')
ON DUPLICATE KEY UPDATE name = VALUES(name), allowed_types = VALUES(allowed_types);

INSERT INTO admins (name, email, password) VALUES
  ('Super Admin', 'admin@parkingpro.local', 'admin123')
ON DUPLICATE KEY UPDATE name = VALUES(name), password = VALUES(password);
