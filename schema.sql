-- Hotel Booking System Database Schema

CREATE DATABASE IF NOT EXISTS hotel_booking;
USE hotel_booking;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    role ENUM('customer', 'admin') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Hotels table
CREATE TABLE hotels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    address VARCHAR(255) NOT NULL,
    city VARCHAR(50) NOT NULL,
    country VARCHAR(50) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    rating DECIMAL(2,1) DEFAULT 0,
    image_url VARCHAR(255),
    amenities TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Rooms table
CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hotel_id INT NOT NULL,
    room_type VARCHAR(50) NOT NULL,
    description TEXT,
    price_per_night DECIMAL(10,2) NOT NULL,
    capacity INT NOT NULL,
    total_rooms INT NOT NULL,
    available_rooms INT NOT NULL,
    amenities TEXT,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE
);

-- Bookings table
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    hotel_id INT NOT NULL,
    room_id INT NOT NULL,
    check_in_date DATE NOT NULL,
    check_out_date DATE NOT NULL,
    guests INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'refunded') DEFAULT 'pending',
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);

-- Reviews table
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    hotel_id INT NOT NULL,
    booking_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);

-- Insert sample data

-- Sample admin user (password: admin123)
INSERT INTO users (username, email, password, full_name, role) VALUES 
('admin', 'admin@hotelbook.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin');

-- Sample hotels
INSERT INTO hotels (name, description, address, city, country, phone, email, rating, image_url, amenities) VALUES 
('Grand Plaza Hotel', 'Luxury hotel in the heart of the city with world-class amenities', '123 Main Street', 'New York', 'USA', '+1-555-0123', 'info@grandplaza.com', 4.5, 'https://via.placeholder.com/400x300', 'WiFi,Pool,Gym,Restaurant,Spa,Parking'),
('Ocean View Resort', 'Beautiful beachfront resort with stunning ocean views', '456 Beach Road', 'Miami', 'USA', '+1-555-0456', 'info@oceanview.com', 4.2, 'https://via.placeholder.com/400x300', 'WiFi,Pool,Beach Access,Restaurant,Bar,Spa'),
('Mountain Lodge', 'Cozy mountain retreat perfect for nature lovers', '789 Mountain Trail', 'Denver', 'USA', '+1-555-0789', 'info@mountainlodge.com', 4.0, 'https://via.placeholder.com/400x300', 'WiFi,Fireplace,Hiking Trails,Restaurant,Parking');

-- Sample rooms
INSERT INTO rooms (hotel_id, room_type, description, price_per_night, capacity, total_rooms, available_rooms, amenities, image_url) VALUES 
(1, 'Standard Room', 'Comfortable room with city view', 150.00, 2, 20, 15, 'WiFi,TV,AC,Mini Bar', 'https://via.placeholder.com/400x300'),
(1, 'Deluxe Suite', 'Spacious suite with premium amenities', 300.00, 4, 10, 8, 'WiFi,TV,AC,Mini Bar,Balcony,Jacuzzi', 'https://via.placeholder.com/400x300'),
(2, 'Ocean View Room', 'Room with direct ocean view', 200.00, 2, 25, 20, 'WiFi,TV,AC,Ocean View,Balcony', 'https://via.placeholder.com/400x300'),
(2, 'Beach Villa', 'Private villa steps from the beach', 500.00, 6, 5, 3, 'WiFi,TV,AC,Private Beach,Kitchen,Pool', 'https://via.placeholder.com/400x300'),
(3, 'Mountain Cabin', 'Rustic cabin with mountain views', 120.00, 4, 15, 12, 'WiFi,Fireplace,Mountain View,Kitchen', 'https://via.placeholder.com/400x300');