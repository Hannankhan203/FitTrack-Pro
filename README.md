# FitTrack Pro - Fitness & Nutrition Tracker

FitTrack Pro is a comprehensive web-based fitness tracking application that helps users monitor their workouts, nutrition, and progress towards fitness goals.

## Tech Stack

### Frontend

- **HTML5** - Semantic markup
- **CSS3** - Custom styling with CSS variables and gradients
- **Bootstrap 5** - Responsive framework
- **JavaScript (ES6+)** - Interactive functionality
- **Chart.js** - Data visualization for progress charts
- **Font Awesome** - Icon library
- **Google Fonts** - Typography (Poppins, Montserrat)

### Backend

- **PHP 7.4+** - Server-side scripting
- **MySQL** - Database management
- **PDO** - Database interaction with prepared statements
- **Sessions** - User authentication and state management

### Additional Features

- **PWA Support** - Service Worker for offline functionality
- **RESTful API** - For data operations
- **Responsive Design** - Mobile-first approach
- **Drag & Drop** - For meal planning

## Features

### User Authentication

- User registration with password validation
- Secure login system with session management
- Admin/user role-based access control

### Dashboard

- Overview of daily/weekly fitness metrics
- Progress visualization with charts
- Quick access to all features
- Goal tracking with progress indicators

### Workout Management

- Log strength and cardio exercises
- Exercise library with icons
- Intelligent form fields (removes weight for bodyweight exercises)
- Workout history tracking
- Delete functionality for exercises

### Nutrition Tracking

- Meal planning by time (breakfast, lunch, dinner, snacks)
- Food database with nutritional information
- Macronutrient breakdown
- Water intake tracking
- Meal history with delete functionality

### Progress Monitoring

- Weight tracking with chart visualization
- Progress photos with comparison view
- Goal setting and progress calculation
- Achievement badges system

### Admin Features

- User management dashboard
- View all registered users
- Admin-only access control

## Database Schema

The application uses a MySQL database with the following tables:

### Users

- User authentication and profile information
- Fitness goals and preferences

### Workouts

- Exercise logs with sets, reps, weight, and duration
- Date-based tracking

### Meals

- Food items with nutritional information
- Categorized by meal time

### Progress

- Weight tracking over time
- Progress photos metadata

### Achievements

- User badges and accomplishments

## Installation

1. Clone the repository to your web server directory
2. Import the database schema from `/database/fitness-tracker.sql`
3. Configure database connection in `/includes/db.php`
4. Set up your web server (Apache recommended with mod_rewrite)
5. Access the application through your web browser

## Default Admin Account

- Email: admin@wtp.com
- Password: admin123

## File Structure

```bash
/fitness-tracker
├── admin/
│   └── dashboard.php
├── api/
│   ├── clear-meals.php
│   ├── debug-workout.php
│   ├── delete-meal.php
│   ├── log-weight.php
│   ├── nutrition.php
│   ├── save-meal.php
│   ├── workout-delete.php
│   └── workout-save.php
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── img/
│   │   ├── favicon-16x16.png
│   │   └── favicon-32x32.png
│   └── js/
│       ├── chart-config.js
│       └── main.js
├── database/
│   └── fitness-tracker.sql
├── includes/
│   ├── db.php
│   ├── footer.php
│   ├── functions.php
│   └── header.php
├── meals/
│   ├── log-meal.php
│   ├── planner.php
│   └── search-food.php
├── progress/
│   ├── charts.php
│   ├── photos.php
│   └── weight.php
├── uploads/
├── workouts/
│   ├── exercise.json
│   ├── history.php
│   └── log.php
├── .htaccess
├── dashboard.php
├── index.php
├── login.php
├── logout.php
├── manifest.json
├── profile.php
├── README.md
├── register.php
└── sw.js
```

## Security Features

- Password hashing with bcrypt
- SQL injection prevention using PDO prepared statements
- Session-based authentication
- Input validation and sanitization
- File upload restrictions

## Performance Optimizations

- PWA for offline functionality
- Caching strategies
- Optimized database queries
- Lazy loading for images
- Minified assets

## Browser Support

- Chrome 60+
- Firefox 55+
- Safari 11+
- Edge 79+

## License

This project is for educational purposes.

## Contact

For support or inquiries, please use the contact form in the application.
