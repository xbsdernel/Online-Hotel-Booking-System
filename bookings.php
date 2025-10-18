<?php
require_once '../config/database.php';
session_start();

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getDBConnection();

switch($method) {
    case 'GET':
        if (isset($_GET['user_id'])) {
            getUserBookings($pdo, $_GET['user_id']);
        } else {
            getAllBookings($pdo);
        }
        break;
    case 'POST':
        createBooking($pdo);
        break;
    case 'PUT':
        updateBooking($pdo);
        break;
    case 'DELETE':
        cancelBooking($pdo);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function createBooking($pdo) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $user_id = $input['user_id'];
        $hotel_id = $input['hotel_id'];
        $room_id = $input['room_id'];
        $check_in_date = $input['check_in_date'];
        $check_out_date = $input['check_out_date'];
        $guests = $input['guests'];
        
        // Check room availability
        $stmt = $pdo->prepare("SELECT available_rooms, price_per_night FROM rooms WHERE id = ?");
        $stmt->execute([$room_id]);
        $room = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$room || $room['available_rooms'] < 1) {
            http_response_code(400);
            echo json_encode(['error' => 'Room not available']);
            return;
        }
        
        // Calculate total amount
        $checkin = new DateTime($check_in_date);
        $checkout = new DateTime($check_out_date);
        $nights = $checkin->diff($checkout)->days;
        $total_amount = $nights * $room['price_per_night'];
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Create booking
        $stmt = $pdo->prepare("
            INSERT INTO bookings (user_id, hotel_id, room_id, check_in_date, check_out_date, guests, total_amount) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$user_id, $hotel_id, $room_id, $check_in_date, $check_out_date, $guests, $total_amount]);
        $booking_id = $pdo->lastInsertId();
        
        // Update room availability
        $stmt = $pdo->prepare("UPDATE rooms SET available_rooms = available_rooms - 1 WHERE id = ?");
        $stmt->execute([$room_id]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Booking created successfully',
            'booking_id' => $booking_id,
            'total_amount' => $total_amount
        ]);
    } catch(Exception $e) {
        $pdo->rollback();
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function getUserBookings($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT b.*, h.name as hotel_name, h.address, h.city, r.room_type 
            FROM bookings b 
            JOIN hotels h ON b.hotel_id = h.id 
            JOIN rooms r ON b.room_id = r.id 
            WHERE b.user_id = ? 
            ORDER BY b.booking_date DESC
        ");
        
        $stmt->execute([$user_id]);
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $bookings]);
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function getAllBookings($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT b.*, h.name as hotel_name, u.full_name as user_name, r.room_type 
            FROM bookings b 
            JOIN hotels h ON b.hotel_id = h.id 
            JOIN users u ON b.user_id = u.id 
            JOIN rooms r ON b.room_id = r.id 
            ORDER BY b.booking_date DESC
        ");
        
        $stmt->execute();
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $bookings]);
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function updateBooking($pdo) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $stmt = $pdo->prepare("
            UPDATE bookings 
            SET status = ?, payment_status = ? 
            WHERE id = ?
        ");
        
        $stmt->execute([$input['status'], $input['payment_status'], $input['id']]);
        
        echo json_encode(['success' => true, 'message' => 'Booking updated successfully']);
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function cancelBooking($pdo) {
    try {
        $booking_id = $_GET['id'];
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Get booking details
        $stmt = $pdo->prepare("SELECT room_id FROM bookings WHERE id = ?");
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Update booking status
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
        $stmt->execute([$booking_id]);
        
        // Restore room availability
        $stmt = $pdo->prepare("UPDATE rooms SET available_rooms = available_rooms + 1 WHERE id = ?");
        $stmt->execute([$booking['room_id']]);
        
        $pdo->commit();
        
        echo json_encode(['success' => true, 'message' => 'Booking cancelled successfully']);
    } catch(Exception $e) {
        $pdo->rollback();
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>