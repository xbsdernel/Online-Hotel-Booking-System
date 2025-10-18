<?php
require_once '../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getDBConnection();

switch($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            // Get specific hotel
            getHotel($pdo, $_GET['id']);
        } else {
            // Search hotels
            searchHotels($pdo);
        }
        break;
    case 'POST':
        // Add new hotel (admin only)
        addHotel($pdo);
        break;
    case 'PUT':
        // Update hotel (admin only)
        updateHotel($pdo);
        break;
    case 'DELETE':
        // Delete hotel (admin only)
        deleteHotel($pdo);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function searchHotels($pdo) {
    try {
        $city = $_GET['city'] ?? '';
        $checkin = $_GET['checkin'] ?? '';
        $checkout = $_GET['checkout'] ?? '';
        $guests = $_GET['guests'] ?? 1;
        $minPrice = $_GET['min_price'] ?? 0;
        $maxPrice = $_GET['max_price'] ?? 9999;

        $sql = "SELECT h.*, MIN(r.price_per_night) as min_price, MAX(r.price_per_night) as max_price 
                FROM hotels h 
                LEFT JOIN rooms r ON h.id = r.hotel_id 
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($city)) {
            $sql .= " AND h.city LIKE ?";
            $params[] = "%$city%";
        }
        
        if (!empty($checkin) && !empty($checkout) && $guests > 0) {
            $sql .= " AND r.available_rooms >= ? AND r.capacity >= ? 
                     AND r.price_per_night BETWEEN ? AND ?";
            $params[] = 1;
            $params[] = $guests;
            $params[] = $minPrice;
            $params[] = $maxPrice;
        }
        
        $sql .= " GROUP BY h.id ORDER BY h.rating DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $hotels]);
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function getHotel($pdo, $id) {
    try {
        // Get hotel details
        $stmt = $pdo->prepare("SELECT * FROM hotels WHERE id = ?");
        $stmt->execute([$id]);
        $hotel = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$hotel) {
            http_response_code(404);
            echo json_encode(['error' => 'Hotel not found']);
            return;
        }
        
        // Get hotel rooms
        $stmt = $pdo->prepare("SELECT * FROM rooms WHERE hotel_id = ?");
        $stmt->execute([$id]);
        $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get hotel reviews
        $stmt = $pdo->prepare("
            SELECT r.*, u.full_name as user_name 
            FROM reviews r 
            JOIN users u ON r.user_id = u.id 
            WHERE r.hotel_id = ? 
            ORDER BY r.created_at DESC 
            LIMIT 10
        ");
        $stmt->execute([$id]);
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $hotel['rooms'] = $rooms;
        $hotel['reviews'] = $reviews;
        
        echo json_encode(['success' => true, 'data' => $hotel]);
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function addHotel($pdo) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $stmt = $pdo->prepare("
            INSERT INTO hotels (name, description, address, city, country, phone, email, image_url, amenities) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $input['name'],
            $input['description'],
            $input['address'],
            $input['city'],
            $input['country'],
            $input['phone'],
            $input['email'],
            $input['image_url'],
            $input['amenities']
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Hotel added successfully']);
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function updateHotel($pdo) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $stmt = $pdo->prepare("
            UPDATE hotels 
            SET name=?, description=?, address=?, city=?, country=?, phone=?, email=?, image_url=?, amenities=? 
            WHERE id=?
        ");
        
        $stmt->execute([
            $input['name'],
            $input['description'],
            $input['address'],
            $input['city'],
            $input['country'],
            $input['phone'],
            $input['email'],
            $input['image_url'],
            $input['amenities'],
            $input['id']
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Hotel updated successfully']);
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function deleteHotel($pdo) {
    try {
        $id = $_GET['id'];
        
        $stmt = $pdo->prepare("DELETE FROM hotels WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true, 'message' => 'Hotel deleted successfully']);
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>