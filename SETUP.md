# Hotel Booking System - Setup Guide

## Prerequisites

### Required Software
1. **PHP 7.4 or higher** with the following extensions:
   - PDO
   - PDO_MySQL
   - JSON
   - Session

2. **MySQL 5.7 or higher** (or MariaDB 10.2+)

3. **Web Server** (Apache, Nginx, or PHP built-in server)

### Installation Options

#### Option 1: XAMPP (Recommended for Windows)
1. Download and install XAMPP from https://www.apachefriends.org/
2. Start Apache and MySQL services from XAMPP Control Panel

#### Option 2: WAMP (Windows Alternative)
1. Download and install WAMP from http://www.wampserver.com/
2. Start all services

#### Option 3: Manual Installation
1. Install PHP from https://www.php.net/downloads
2. Install MySQL from https://dev.mysql.com/downloads/
3. Configure PHP to work with MySQL

## Database Setup

### Step 1: Create Database
1. Open phpMyAdmin (usually at http://localhost/phpmyadmin)
2. Create a new database named `hotel_booking`
3. Import the schema from `backend/database/schema.sql`

### Step 2: Configure Database Connection
1. Open `backend/config/database.php`
2. Update the database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');        // Your MySQL username
   define('DB_PASS', '');            // Your MySQL password
   define('DB_NAME', 'hotel_booking');
   ```

## Running the Application

### Method 1: Using XAMPP/WAMP
1. Copy the entire project folder to your web server directory:
   - XAMPP: `C:\xampp\htdocs\hotel-booking\`
   - WAMP: `C:\wamp64\www\hotel-booking\`

2. Access the application:
   - Frontend: http://localhost/hotel-booking/frontend/
   - Backend API: http://localhost/hotel-booking/backend/api/

### Method 2: Using PHP Built-in Server
1. Open two command prompts/terminals

2. Start the backend server:
   ```bash
   cd backend
   php -S localhost:8080
   ```

3. Start the frontend server:
   ```bash
   cd frontend
   php -S localhost:8000
   ```

4. Access the application at http://localhost:8000

### Method 3: Using Python for Frontend (Current Setup)
1. Backend needs PHP server (see Method 2)
2. Frontend is already running on http://localhost:8000

## Default Admin Account

After importing the database schema, you can login with:
- **Username:** admin
- **Password:** admin123

**Important:** Change the admin password immediately after first login!

## API Endpoints

The backend provides the following API endpoints:

### Authentication (`/api/auth.php`)
- `POST` - Login/Register
- `GET` - Check session status

### Hotels (`/api/hotels.php`)
- `GET` - Search hotels
- `POST` - Add hotel (admin only)
- `PUT` - Update hotel (admin only)
- `DELETE` - Delete hotel (admin only)

### Bookings (`/api/bookings.php`)
- `GET` - Get user bookings
- `POST` - Create booking
- `PUT` - Update booking
- `DELETE` - Cancel booking

### Reviews (`/api/reviews.php`)
- `GET` - Get hotel reviews
- `POST` - Add review
- `DELETE` - Delete review (admin only)

## Troubleshooting

### Common Issues

1. **"Connection refused" errors**
   - Ensure MySQL is running
   - Check database credentials in `config/database.php`

2. **"CORS errors" in browser**
   - The backend includes CORS headers
   - Ensure both frontend and backend servers are running

3. **"404 Not Found" for API calls**
   - Check that the backend server is running
   - Verify the API_BASE URL in `frontend/js/main.js`

4. **Database connection errors**
   - Verify MySQL is running
   - Check username/password in database config
   - Ensure the database `hotel_booking` exists

### File Permissions
If using Apache/Nginx, ensure proper file permissions:
```bash
chmod -R 755 /path/to/hotel-booking/
```

## Security Notes

1. **Change default passwords** immediately
2. **Use HTTPS** in production
3. **Update database credentials** for production
4. **Enable PHP error logging** instead of displaying errors
5. **Implement rate limiting** for API endpoints

## Development vs Production

### Development Setup
- Use PHP built-in server
- Enable error reporting
- Use localhost database

### Production Setup
- Use Apache/Nginx
- Disable error reporting
- Use secure database credentials
- Enable HTTPS
- Implement proper logging

## Support

For issues or questions:
1. Check the troubleshooting section above
2. Verify all prerequisites are installed
3. Check browser console for JavaScript errors
4. Check PHP error logs for backend issues

## Features

✅ User registration and authentication
✅ Hotel search and filtering
✅ Hotel details and reviews
✅ Booking management
✅ Admin panel for hotel management
✅ Responsive design
✅ Review system
✅ Booking history

## Technology Stack

- **Frontend:** HTML5, CSS3, JavaScript (Vanilla)
- **Backend:** PHP 7.4+
- **Database:** MySQL 5.7+
- **Styling:** Custom CSS with responsive design
- **Architecture:** RESTful API design