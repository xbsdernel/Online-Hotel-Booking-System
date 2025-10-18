<?php
require_once '../config/database.php';
session_start();

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getDBConnection();

switch($method) {
    case 'GET':
        if (isset($_GET['hotel_id'])) {
            getHotelReviews($pdo, $_GET['hotel_id']);
        } else {
            getAllReviews($pdo);
        }
        break;
    case 'POST':
        addReview($pdo);
        break;
    case 'DELETE':
        deleteReview($pdo);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function addReview($pdo) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $user_id = $input['user_id'];
        $hotel_id = $input['hotel_id'];
        $booking_id = $input['booking_id'];
        $rating = $input['rating'];
        $comment = $input['comment'] ?? '';
        
        // Validate rating
        if ($rating < 1 || $rating > 5) {
            http_response_code(400);
            echo json_encode(['error' => 'Rating must be between 1 and 5']);
            return;
        }
        
        // Check if user has completed booking for this hotel
        $stmt = $pdo->prepare("
            SELECT id FROM bookings 
            WHERE id = ? AND user_id = ? AND hotel_id = ? AND status = 'completed'
        ");
        $stmt->execute([$booking_id, $user_id, $hotel_id]);
        
        if (!$stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'You can only review hotels you have stayed at']);
            return;
        }
        
        // Check if review already exists
        $stmt = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND hotel_id = ? AND booking_id = ?");
        $stmt->execute([$user_id, $hotel_id, $booking_id]);
        
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['error' => 'You have already reviewed this booking']);
            return;
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Add review
        $stmt = $pdo->prepare("
            INSERT INTO reviews (user_id, hotel_id, booking_id, rating, comment) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$user_id, $hotel_id, $booking_id, $rating, $comment]);
        
        // Update hotel rating
        updateHotelRating($pdo, $hotel_id);
        
        $pdo->commit();
        
        echo json_encode(['success' => true, 'message' => 'Review added successfully']);
    } catch(Exception $e) {
        $pdo->rollback();
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function getHotelReviews($pdo, $hotel_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT r.*, u.full_name as user_name 
            FROM reviews r 
            JOIN users u ON r.user_id = u.id 
            WHERE r.hotel_id = ? 
            ORDER BY r.created_at DESC
        ");
        
        $stmt->execute([$hotel_id]);
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $reviews]);
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function getAllReviews($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT r.*, u.full_name as user_name, h.name as hotel_name 
            FROM reviews r 
            JOIN users u ON r.user_id = u.id 
            JOIN hotels h ON r.hotel_id = h.id 
            ORDER BY r.created_at DESC
        ");
        
        $stmt->execute();
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $reviews]);
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function deleteReview($pdo) {
    try {
        $review_id = $_GET['id'];
        
        // Get hotel_id before deleting
        $stmt = $pdo->prepare("SELECT hotel_id FROM reviews WHERE id = ?");
        $stmt->execute([$review_id]);
        $review = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$review) {
            http_response_code(404);
            echo json_encode(['error' => 'Review not found']);
            return;
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Delete review
        $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
        $stmt->execute([$review_id]);
        
        // Update hotel rating
        updateHotelRating($pdo, $review['hotel_id']);
        
        $pdo->commit();
        
        echo json_encode(['success' => true, 'message' => 'Review deleted successfully']);
    } catch(Exception $e) {
        $pdo->rollback();
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function updateHotelRating($pdo, $hotel_id) {
    $stmt = $pdo->prepare("
        UPDATE hotels 
        SET rating = (
            SELECT ROUND(AVG(rating), 1) 
            FROM reviews 
            WHERE hotel_id = ?
        ) 
        WHERE id = ?
    ");
    $stmt->execute([$hotel_id, $hotel_id]);
}
?>