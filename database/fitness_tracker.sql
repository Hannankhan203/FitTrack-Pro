-- database/fitness_tracker.sql
CREATE DATABASE IF NOT EXISTS fitness_tracker;
USE fitness_tracker;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    height DECIMAL(5,2),
    weight DECIMAL(5,2),
    age INT,
    goal ENUM('lose', 'gain', 'maintain'),
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE workouts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    exercise VARCHAR(100),
    sets INT,
    reps INT,
    weight DECIMAL(6,2),
    duration INT,
    date DATE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE meals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    food_name VARCHAR(200),
    calories INT,
    protein DECIMAL(6,2),
    carbs DECIMAL(6,2),
    fat DECIMAL(6,2),
    quantity DECIMAL(6,2) DEFAULT 1,
    meal_time ENUM('breakfast','lunch','dinner','snack'),
    date DATE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    weight DECIMAL(5,2),
    waist DECIMAL(5,2),
    chest DECIMAL(5,2),
    date DATE DEFAULT (CURRENT_DATE),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

INSERT INTO users (name, email, password, role) VALUES 
('Admin User', 'admin@wtp.com', '$2y$10$z5j5yj5yj5yj5yj5yj5yjOa8b3uF6b3v7t3r3y3u3i3o3p3a3s3d', 'admin');
-- Password for admin is: admin123