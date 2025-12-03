# FitTrack Pro - Fitness & Nutrition Tracker (Web Technologies Project)

[![Live Demo](https://img.shields.io/badge/Live-Demo-brightgreen?style=for-the-badge)](https://fittrack-pro.wuaze.com/fitness-tracker/)


## 🏋️‍♂️ Overview

FitTrack Pro is a comprehensive, web-based fitness tracking platform designed to help users monitor workouts, track nutrition, and achieve fitness goals through data-driven insights and intuitive interfaces.

![FitTrack Pro](https://img.shields.io/badge/FitTrack-Pro-brightgreen)  
![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4)  
![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1)  
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3)  
![PWA](https://img.shields.io/badge/PWA-Enabled-5A0FC8)

---

## ✨ Features

### 🎯 Core Functionalities

- **Personalized Dashboard** – Real-time fitness metrics and progress visualization
- **Workout Logging** – Track strength and cardio exercises with intelligent form fields
- **Meal Planning** – Comprehensive nutrition tracking with food database
- **Progress Monitoring** – Weight tracking, photo comparisons, and goal achievement
- **User Management** – Role-based access control (Admin/User)

### 📊 Advanced Features

- **Data Visualization** – Interactive charts for progress
- **Goal Setting** – SMART goal configuration
- **Achievement System** – Badges and milestones
- **PWA Support** – Installable with offline mode
- **Responsive Design** – Mobile-first UI

---

## 🛠 Tech Stack

### Frontend

- **HTML5**
- **CSS3** (variables, gradients, animations)
- **Bootstrap 5.3**
- **JavaScript ES6+**
- **Chart.js 4.0**
- **Font Awesome 6.4**
- **Select2**
- **Lightbox2**

### Backend

- **PHP 7.4+**
- **MySQL 8.0+**
- **PDO**
- **Session Management**

### DevOps & Performance

- **Service Workers**
- **REST API**
- **Caching Strategy**
- **Responsive Images**

---

## 📁 Project Structure

```bash
fitness-tracker/
├── admin/
│ └── dashboard.php
│
├── api/
│ ├── clear-meals.php
│ ├── delete-meal.php
│ ├── nutrition.php
│ ├── save-meal.php
│ ├── workout-delete.php
│ ├── workout-save.php
│ └── log-weight.php
│
├── assets/
│ ├── css/
│ │ └── style.css
│ ├── img/
│       ├── favicon-16x16.png
│       └── favicon-32x32.png
│ └── js/
│ ├── chart-config.js
│ └── main.js
│
├── database/
│ └── fitness-tracker.sql
│
├── includes/
│ ├── db.php
│ ├── functions.php
│ ├── header.php
│ └── footer.php
│
├── meals/
│ ├── planner.php
│ ├── log-meal.php
│ └── search-food.php
│
├── progress/
│ ├── charts.php
│ ├── photos.php
│ └── weight.php
│
├── uploads/
│ └── user_photos/
│
├── workouts/
│ ├── log.php
│ ├── history.php
│ └── exercise.json
│
├── .htaccess
├── dashboard.php
├── index.php
├── login.php
├── logout.php
├── manifest.json
├── profile.php
├── register.php
├── sw.js
└── README.md
```

---

## 🗄 Database Schema

### Users Table

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    height DECIMAL(5,2),
    weight DECIMAL(5,2),
    age INT,
    goal_weight DECIMAL(5,2),
    goal_type ENUM('lose', 'gain', 'maintain'),
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Workouts Table

```sql
CREATE TABLE workouts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    exercise VARCHAR(100) NOT NULL,
    sets INT DEFAULT NULL,
    reps INT DEFAULT NULL,
    weight DECIMAL(5,2) DEFAULT NULL,
    duration DECIMAL(5,2) DEFAULT NULL,
    distance DECIMAL(5,2) DEFAULT NULL,
    date DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX user_date_idx (user_id, date)
);
```

### Meals Table

```sql
CREATE TABLE meals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    food_name VARCHAR(200) NOT NULL,
    calories INT NOT NULL,
    protein DECIMAL(6,2),
    carbs DECIMAL(6,2),
    fat DECIMAL(6,2),
    meal_time ENUM('breakfast','lunch','dinner','snack'),
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX user_date_idx (user_id, date)
);
```

### Progress Table

```sql
CREATE TABLE progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    weight DECIMAL(5,2) NOT NULL,
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX user_date_idx (user_id, date)
);

```

### 🚀 Installation

- Prerequisites
- Apache / Nginx
- PHP 7.4+
- MySQL 8.0+
- Composer (optional)

### Setup

1. Clone Repository

```bash
git clone https://github.com/yourusername/fitness-tracker.git
cd fitness-tracker
```

2. Import Database

```bash
mysql -u root -p < database/fitness-tracker.sql
```

3. Configure Database

```bash
# includes/db.php
$host = 'localhost';
$db   = 'fitness_tracker';
$user = 'your_username';
$pass = 'your_password';
```

4. Set Permissions

```bash
chmod 755 uploads/
chmod 644 includes/db.php
```

5. Run App

```bash
http://localhost/fitness-tracker/
```

### 🔐 Security Features

- bcrypt hashing (password_hash)
- PDO prepared statements
- XSS prevention with htmlspecialchars()
- Secure sessions (ID regeneration)
- Validated file uploads
- CSRF tokens
- Robust input validation

### 📱 PWA Configuration

- Service Worker (sw.js)
- Manifest File
- Responsive UI
- Offline Caching

### 🔧 API Endpoints

| Method | Endpoint                | Description    |
| ------ | ----------------------- | -------------- |
| POST   | /api/save-meal.php      | Save meal      |
| DELETE | /api/delete-meal.php    | Delete meal    |
| POST   | /api/workout-save.php   | Log workout    |
| DELETE | /api/workout-delete.php | Delete workout |
| POST   | /api/log-weight.php     | Log weight     |

### 🎨 UI/UX Features

- Dynamic themes with CSS variables
- Smooth CSS animations
- ARIA accessible components
- Skeleton loaders
- Toast notifications

### 📊 Performance Optimizations

- Lazy loaded images
- Browser & SW caching
- Minified assets
- Indexed DB queries
- Compressed images

### 🧪 Testing Checklist

- Register & login
- Log workouts
- Add & delete meals
- Track weight
- Admin panel
- Mobile responsiveness
- PWA offline functionality

### 👥 Default Accounts

## Admin

- Username: Admin User
- Email: admin@wtp.com
- Password: Admin123

### 🔄 Version Control

```bash
v1.0.0 – Initial Release
- Authentication
- Workout tracking
- Meal tracking
- Progress charts
- Admin dashboard

v1.1.0 – Planned
- Social sharing
- Workout templates
- Barcode scanner
- Mobile app (React Native)
```
