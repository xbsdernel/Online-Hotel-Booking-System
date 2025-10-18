#!/usr/bin/env python3
"""
Mock Backend Server for Hotel Booking System
This simulates the PHP backend API for testing purposes
"""

from http.server import HTTPServer, BaseHTTPRequestHandler
import json
import urllib.parse
from datetime import datetime, timedelta
import uuid
import os
import mimetypes

# Mock data
users = {
    "admin": {"id": 1, "username": "admin", "password": "admin123", "role": "admin", "email": "admin@hotel.com", "full_name": "Administrator"},
    "user1": {"id": 2, "username": "user1", "password": "password", "role": "user", "email": "user1@email.com", "full_name": "John Doe"}
}

hotels = [
    {
        "id": 1,
        "name": "Grand Hotel",
        "city": "New York",
        "address": "123 Main St, New York, NY",
        "description": "Luxury hotel in the heart of the city",
        "amenities": "WiFi, Pool, Gym, Restaurant",
        "rating": 4.5,
        "image": "/images/hotel-placeholder.svg"
    },
    {
        "id": 2,
        "name": "Beach Resort",
        "city": "Miami",
        "address": "456 Ocean Ave, Miami, FL",
        "description": "Beautiful beachfront resort",
        "amenities": "WiFi, Pool, Beach Access, Spa",
        "rating": 4.8,
        "image": "/images/hotel-placeholder.svg"
    },
    {
        "id": 3,
        "name": "Mountain Lodge",
        "city": "Denver",
        "address": "789 Mountain Rd, Denver, CO",
        "description": "Cozy lodge with mountain views",
        "amenities": "WiFi, Fireplace, Hiking Trails",
        "rating": 4.2,
        "image": "/images/hotel-placeholder.svg"
    }
]

rooms = [
    # Grand Hotel (New York) - Hotel ID 1
    {"id": 1, "hotel_id": 1, "type": "Standard Room", "price": 150.00, "available": True, "description": "Comfortable room with city view"},
    {"id": 2, "hotel_id": 1, "type": "Deluxe Room", "price": 200.00, "available": True, "description": "Spacious room with premium amenities"},
    {"id": 3, "hotel_id": 1, "type": "Executive Suite", "price": 300.00, "available": True, "description": "Luxury suite with separate living area"},
    
    # Beach Resort (Miami) - Hotel ID 2
    {"id": 4, "hotel_id": 2, "type": "Ocean View Room", "price": 250.00, "available": True, "description": "Beautiful ocean view from your room"},
    {"id": 5, "hotel_id": 2, "type": "Beachfront Suite", "price": 350.00, "available": True, "description": "Direct beach access with private balcony"},
    {"id": 6, "hotel_id": 2, "type": "Presidential Suite", "price": 500.00, "available": True, "description": "Ultimate luxury with panoramic ocean views"},
    
    # Mountain Lodge (Denver) - Hotel ID 3
    {"id": 7, "hotel_id": 3, "type": "Standard Cabin", "price": 120.00, "available": True, "description": "Cozy cabin with mountain views"},
    {"id": 8, "hotel_id": 3, "type": "Deluxe Cabin", "price": 180.00, "available": True, "description": "Spacious cabin with fireplace"},
    {"id": 9, "hotel_id": 3, "type": "Mountain Suite", "price": 250.00, "available": True, "description": "Premium suite with panoramic mountain views"},
]

bookings = []
reviews = [
    {"id": 1, "hotel_id": 1, "user_id": 2, "rating": 5, "comment": "Excellent service!", "date": "2024-01-15"},
    {"id": 2, "hotel_id": 2, "user_id": 2, "rating": 4, "comment": "Great location!", "date": "2024-01-10"}
]

sessions = {}

class MockAPIHandler(BaseHTTPRequestHandler):
    def do_OPTIONS(self):
        """Handle preflight CORS requests"""
        print(f"OPTIONS request received: {self.path}")
        print(f"Headers: {dict(self.headers)}")
        self.send_response(200)
        self.send_header('Access-Control-Allow-Origin', '*')
        self.send_header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        self.send_header('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        self.end_headers()

    def send_cors_headers(self):
        """Send CORS headers"""
        self.send_header('Access-Control-Allow-Origin', '*')
        self.send_header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        self.send_header('Access-Control-Allow-Headers', 'Content-Type, Authorization')

    def send_json_response(self, data, status=200):
        """Send JSON response with CORS headers"""
        self.send_response(status)
        self.send_header('Content-Type', 'application/json')
        self.send_cors_headers()
        self.end_headers()
        self.wfile.write(json.dumps(data).encode())

    def get_post_data(self):
        """Get POST data from request"""
        content_length = int(self.headers.get('Content-Length', 0))
        if content_length > 0:
            post_data = self.rfile.read(content_length)
            return json.loads(post_data.decode())
        return {}
    
    def serve_static_file(self, path):
        """Serve static files from frontend directory"""
        # Default to index.html for root path
        if path == '/':
            path = '/index.html'
        
        # Remove leading slash and construct file path
        file_path = os.path.join('frontend', path.lstrip('/'))
        
        try:
            if os.path.exists(file_path) and os.path.isfile(file_path):
                # Get MIME type
                mime_type, _ = mimetypes.guess_type(file_path)
                if mime_type is None:
                    mime_type = 'application/octet-stream'
                
                # Read and serve file
                with open(file_path, 'rb') as f:
                    content = f.read()
                
                self.send_response(200)
                self.send_header('Content-Type', mime_type)
                self.send_cors_headers()
                self.end_headers()
                self.wfile.write(content)
            else:
                # File not found
                self.send_response(404)
                self.send_header('Content-Type', 'text/html')
                self.send_cors_headers()
                self.end_headers()
                self.wfile.write(b'<h1>404 - File Not Found</h1>')
        except Exception as e:
            print(f"Error serving static file {file_path}: {e}")
            self.send_response(500)
            self.send_header('Content-Type', 'text/html')
            self.send_cors_headers()
            self.end_headers()
            self.wfile.write(b'<h1>500 - Internal Server Error</h1>')

    def do_GET(self):
        """Handle GET requests"""
        parsed_path = urllib.parse.urlparse(self.path)
        path = parsed_path.path
        query_params = urllib.parse.parse_qs(parsed_path.query)
        
        # Handle API requests
        if path.startswith('/api/'):
            if path == '/api/auth.php':
                # Check session
                auth_header = self.headers.get('Authorization')
                if auth_header and auth_header.startswith('Bearer '):
                    session_id = auth_header.split(' ')[1]
                    if session_id in sessions:
                        user_data = sessions[session_id]
                        self.send_json_response({
                            "success": True,
                            "user": {
                                "id": user_data["id"],
                                "username": user_data["username"],
                                "role": user_data["role"],
                                "email": user_data["email"]
                            }
                        })
                        return
                
                self.send_json_response({"success": False, "message": "Not authenticated"}, 401)
                return
        
            elif path.startswith('/api/hotels.php'):
                # Check if it's a specific hotel request
                hotel_id = query_params.get('id', [None])[0]
                
                if hotel_id:
                    # Get specific hotel
                    try:
                        hotel_id = int(hotel_id)
                        hotel = next((h for h in hotels if h['id'] == hotel_id), None)
                        if hotel:
                            # Create a copy to avoid modifying the original
                            hotel_data = hotel.copy()
                            hotel_data['country'] = 'USA'  # Add missing country field
                            hotel_data['min_price'] = 99  # Add min_price field
                            hotel_data['price_per_night'] = 150  # Add price_per_night field
                            
                            hotel_rooms = [r for r in rooms if r['hotel_id'] == hotel_id]
                            hotel_reviews = [r for r in reviews if r['hotel_id'] == hotel_id]
                            
                            # Format rooms to match expected structure
                            formatted_rooms = []
                            for room in hotel_rooms:
                                formatted_rooms.append({
                                    'id': room['id'],
                                    'room_type': room['type'],
                                    'price_per_night': room['price'],
                                    'capacity': 2,
                                    'available_rooms': 5,
                                    'amenities': 'WiFi, TV, AC'
                                })
                            
                            hotel_data['rooms'] = formatted_rooms
                            hotel_data['reviews'] = hotel_reviews
                            self.send_json_response({"success": True, "data": hotel_data})
                        else:
                            self.send_json_response({"success": False, "message": "Hotel not found"}, 404)
                    except ValueError:
                        self.send_json_response({"success": False, "message": "Invalid hotel ID"}, 400)
                else:
                    # Search hotels
                    city = query_params.get('city', [''])[0].lower()
                    filtered_hotels = hotels
                    if city:
                        filtered_hotels = [h for h in hotels if city in h['city'].lower()]
                    
                    # Add missing fields and rooms to each hotel
                    formatted_hotels = []
                    for hotel in filtered_hotels:
                        hotel_data = hotel.copy()
                        hotel_data['country'] = 'USA'
                        hotel_data['min_price'] = 99
                        hotel_data['price_per_night'] = 150
                        hotel_rooms = [r for r in rooms if r['hotel_id'] == hotel['id']]
                        hotel_data['rooms'] = hotel_rooms
                        formatted_hotels.append(hotel_data)
                    
                    self.send_json_response({"success": True, "data": formatted_hotels})
                return

            elif path == '/api/bookings.php':
                # Get user bookings
                print(f"🔍 GET /api/bookings.php called")
                session_id = self.headers.get('Authorization', '').replace('Bearer ', '')
                print(f"🔑 Session ID: {session_id}")
                print(f"📋 Available sessions: {list(sessions.keys())}")
                print(f"📦 Total bookings in system: {len(bookings)}")
                
                if session_id not in sessions:
                    print("❌ Session not found - user not logged in")
                    self.send_json_response({"success": False, "message": "Not logged in"}, 401)
                    return
                
                user = sessions[session_id]
                print(f"👤 User found: {user['username']} (ID: {user['id']})")
                user_bookings = []
                for booking in bookings:
                    print(f"🏨 Checking booking: {booking}")
                    if booking['user_id'] == user['id']:
                        print(f"✅ Found matching booking for user {user['id']}")
                        # Find hotel details
                        hotel = next((h for h in hotels if h['id'] == booking['hotel_id']), None)
                        # Find room details
                        room = next((r for r in rooms if r['id'] == booking['room_id']), None)
                        
                        enhanced_booking = booking.copy()
                        enhanced_booking['hotel_name'] = hotel['name'] if hotel else 'Unknown Hotel'
                        enhanced_booking['room_type'] = room['type'] if room else 'Unknown Room'
                        enhanced_booking['booking_id'] = f"BK{booking['id']:06d}"
                        enhanced_booking['checkin_date'] = booking['check_in']
                        enhanced_booking['checkout_date'] = booking['check_out']
                        enhanced_booking['booking_date'] = booking['created_at']
                        user_bookings.append(enhanced_booking)
                
                print(f"📊 Returning {len(user_bookings)} bookings for user")
                self.send_json_response({"success": True, "bookings": user_bookings})
                return

            elif path == '/api/reviews.php':
                # Get hotel reviews
                hotel_id = int(query_params.get('hotel_id', [0])[0])
                hotel_reviews = [r for r in reviews if r['hotel_id'] == hotel_id]
                self.send_json_response({"success": True, "reviews": hotel_reviews})
                return

            else:
                self.send_json_response({"success": False, "message": "Endpoint not found"}, 404)
                return
        
        # Handle static file serving
        self.serve_static_file(path)

    def do_POST(self):
        """Handle POST requests"""
        print(f"POST request received: {self.path}")
        print(f"Headers: {dict(self.headers)}")
        parsed_path = urllib.parse.urlparse(self.path)
        path = parsed_path.path
        data = self.get_post_data()
        print(f"POST data: {data}")

        if path == '/api/auth.php':
            action = data.get('action')
            
            if action == 'login':
                username = data.get('username')
                password = data.get('password')
                
                user = users.get(username)
                if user and user['password'] == password:
                    session_id = str(uuid.uuid4())
                    sessions[session_id] = user
                    self.send_json_response({
                        "success": True, 
                        "message": "Login successful",
                        "user": user,
                        "session_id": session_id
                    })
                else:
                    self.send_json_response({"success": False, "message": "Invalid credentials"}, 401)
            
            elif action == 'register':
                username = data.get('username')
                email = data.get('email')
                password = data.get('password')
                full_name = data.get('full_name')
                phone = data.get('phone')
                
                if username in users:
                    self.send_json_response({"success": False, "message": "Username already exists"}, 400)
                else:
                    new_user = {
                        "id": len(users) + 1,
                        "username": username,
                        "email": email,
                        "password": password,
                        "full_name": full_name,
                        "phone": phone,
                        "role": "user"
                    }
                    users[username] = new_user
                    session_id = str(uuid.uuid4())
                    sessions[session_id] = new_user
                    self.send_json_response({
                        "success": True,
                        "message": "Registration successful",
                        "user": new_user,
                        "session_id": session_id
                    })
            
            elif action == 'logout':
                session_id = self.headers.get('Authorization', '').replace('Bearer ', '')
                if session_id in sessions:
                    del sessions[session_id]
                self.send_json_response({"success": True, "message": "Logged out"})

        elif path == '/api/bookings.php':
            # Create booking
            session_id = self.headers.get('Authorization', '').replace('Bearer ', '')
            if session_id not in sessions:
                self.send_json_response({"success": False, "message": "Not logged in"}, 401)
                return
            
            user = sessions[session_id]
            booking = {
                "id": len(bookings) + 1,
                "user_id": user['id'],
                "hotel_id": data.get('hotel_id'),
                "room_id": data.get('room_id'),
                "check_in": data.get('check_in'),
                "check_out": data.get('check_out'),
                "guests": data.get('guests'),
                "total_price": data.get('total_price'),
                "status": "confirmed",
                "created_at": datetime.now().isoformat()
            }
            bookings.append(booking)
            self.send_json_response({"success": True, "message": "Booking created", "booking": booking})

        elif path == '/api/reviews.php':
            # Add review
            session_id = self.headers.get('Authorization', '').replace('Bearer ', '')
            if session_id not in sessions:
                self.send_json_response({"success": False, "message": "Not logged in"}, 401)
                return
            
            user = sessions[session_id]
            review = {
                "id": len(reviews) + 1,
                "hotel_id": data.get('hotel_id'),
                "user_id": user['id'],
                "rating": data.get('rating'),
                "comment": data.get('comment'),
                "date": datetime.now().strftime('%Y-%m-%d')
            }
            reviews.append(review)
            self.send_json_response({"success": True, "message": "Review added", "review": review})

        else:
            self.send_json_response({"success": False, "message": "Endpoint not found"}, 404)

    def do_PUT(self):
        """Handle PUT requests"""
        self.send_json_response({"success": True, "message": "Update successful"})

    def do_DELETE(self):
        """Handle DELETE requests"""
        self.send_json_response({"success": True, "message": "Delete successful"})

def run_server(port=8080):
    """Run the mock server"""
    server_address = ('', port)
    httpd = HTTPServer(server_address, MockAPIHandler)
    print(f"Mock backend server running on http://localhost:{port}")
    print("Available endpoints:")
    print("  GET  /api/auth.php - Check session")
    print("  POST /api/auth.php - Login/Register/Logout")
    print("  GET  /api/hotels.php - Search hotels")
    print("  GET  /api/hotels.php/{id} - Get hotel details")
    print("  GET  /api/bookings.php - Get user bookings")
    print("  POST /api/bookings.php - Create booking")
    print("  GET  /api/reviews.php - Get hotel reviews")
    print("  POST /api/reviews.php - Add review")
    print("\nDefault login credentials:")
    print("  Username: admin, Password: admin123 (Admin)")
    print("  Username: user1, Password: password (User)")
    
    try:
        httpd.serve_forever()
    except KeyboardInterrupt:
        print("\nShutting down server...")
        httpd.shutdown()

if __name__ == '__main__':
    run_server()