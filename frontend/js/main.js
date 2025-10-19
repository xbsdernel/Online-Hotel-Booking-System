// Main JavaScript file for common functionality

// API Base URL
const API_BASE = '/api';

// Current user data
let currentUser = null;

// Make currentUser globally accessible
window.currentUser = currentUser;

// Initialize the application
document.addEventListener('DOMContentLoaded', function() {
    checkUserSession();
    setMinDate();
});

// Check if user is logged in
async function checkUserSession() {
    try {
        const sessionId = localStorage.getItem('sessionId');
        if (!sessionId) {
            updateNavigation(false);
            // Call updateUserStatus if it exists (for home page)
            if (typeof updateUserStatus === 'function') {
                updateUserStatus();
            }
            return;
        }

        const response = await fetch(`${API_BASE}/auth.php`, {
            headers: {
                'Authorization': `Bearer ${sessionId}`
            }
        });
        const data = await response.json();
        
        if (data.success && data.user) {
            currentUser = data.user;
            window.currentUser = data.user;
            updateNavigation(true);
            // Call updateUserStatus if it exists (for home page)
            if (typeof updateUserStatus === 'function') {
                updateUserStatus();
            }
        } else {
            localStorage.removeItem('sessionId');
            currentUser = null;
            window.currentUser = null;
            updateNavigation(false);
            // Call updateUserStatus if it exists (for home page)
            if (typeof updateUserStatus === 'function') {
                updateUserStatus();
            }
        }
    } catch (error) {
        console.error('Error checking session:', error);
        localStorage.removeItem('sessionId');
        updateNavigation(false);
        // Call updateUserStatus if it exists (for home page)
        if (typeof updateUserStatus === 'function') {
            updateUserStatus();
        }
    }
}

// Update navigation based on login status
function updateNavigation(isLoggedIn) {
    const loginLink = document.getElementById('loginLink');
    const registerLink = document.getElementById('registerLink');
    const userInfo = document.getElementById('userInfo');
    const userName = document.getElementById('userName');
    
    if (isLoggedIn && currentUser) {
        if (loginLink) loginLink.style.display = 'none';
        if (registerLink) registerLink.style.display = 'none';
        if (userInfo) {
            userInfo.style.display = 'inline';
            if (userName) userName.textContent = currentUser.full_name || currentUser.username;
        }
        
        // Add admin link if user is admin
        if (currentUser.role === 'admin') {
            addAdminLink();
        }
    } else {
        if (loginLink) loginLink.style.display = 'inline';
        if (registerLink) registerLink.style.display = 'inline';
        if (userInfo) userInfo.style.display = 'none';
    }
}

// Make updateNavigation globally available
window.updateNavigation = updateNavigation;

// Add admin link to navigation
function addAdminLink() {
    const navLinks = document.querySelector('.nav-links');
    if (navLinks && !document.getElementById('adminLink')) {
        const adminLink = document.createElement('a');
        adminLink.href = 'admin.html';
        adminLink.textContent = 'Admin';
        adminLink.id = 'adminLink';
        navLinks.insertBefore(adminLink, navLinks.firstChild);
    }
}

// Logout function
async function logout() {
    try {
        const sessionId = localStorage.getItem('sessionId');
        const response = await fetch(`${API_BASE}/auth.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${sessionId}`
            },
            body: JSON.stringify({
                action: 'logout'
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            localStorage.removeItem('sessionId');
            currentUser = null;
            window.currentUser = null;
            updateNavigation(false);
            showMessage('Logged out successfully', 'success');
            
            // Redirect to home page after logout
            setTimeout(() => {
                window.location.href = '../index.html';
            }, 1000);
        }
    } catch (error) {
        console.error('Error logging out:', error);
        showMessage('Error logging out', 'error');
    }
}

// Search hotels function (used on home page)
function searchHotels() {
    const location = document.getElementById('location')?.value;
    const checkin = document.getElementById('checkin')?.value;
    const checkout = document.getElementById('checkout')?.value;
    
    // Build search URL with parameters
    let searchUrl = 'pages/search.html';
    const params = new URLSearchParams();
    
    if (location) params.append('city', location);
    if (checkin) params.append('checkin', checkin);
    if (checkout) params.append('checkout', checkout);
    
    if (params.toString()) {
        searchUrl += '?' + params.toString();
    }
    
    window.location.href = searchUrl;
}

// Set minimum date for date inputs
function setMinDate() {
    const today = new Date().toISOString().split('T')[0];
    const dateInputs = document.querySelectorAll('input[type="date"]');
    
    dateInputs.forEach(input => {
        input.min = today;
    });
}

// Show message to user
function showMessage(message, type = 'info') {
    const messageDiv = document.getElementById('message');
    if (messageDiv) {
        messageDiv.textContent = message;
        messageDiv.className = `message ${type}`;
        messageDiv.style.display = 'block';
        
        // Hide message after 5 seconds
        setTimeout(() => {
            messageDiv.style.display = 'none';
        }, 5000);
    } else {
        // Create temporary message if no message div exists
        const tempMessage = document.createElement('div');
        tempMessage.className = `message ${type}`;
        tempMessage.textContent = message;
        tempMessage.style.position = 'fixed';
        tempMessage.style.top = '20px';
        tempMessage.style.right = '20px';
        tempMessage.style.zIndex = '9999';
        tempMessage.style.padding = '1rem';
        tempMessage.style.borderRadius = '5px';
        tempMessage.style.maxWidth = '300px';
        
        if (type === 'success') {
            tempMessage.style.background = '#d4edda';
            tempMessage.style.color = '#155724';
            tempMessage.style.border = '1px solid #c3e6cb';
        } else if (type === 'error') {
            tempMessage.style.background = '#f8d7da';
            tempMessage.style.color = '#721c24';
            tempMessage.style.border = '1px solid #f5c6cb';
        }
        
        document.body.appendChild(tempMessage);
        
        setTimeout(() => {
            document.body.removeChild(tempMessage);
        }, 5000);
    }
}

// Format date for display
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString(undefined, options);
}

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}

// Generate star rating HTML
function generateStars(rating) {
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating % 1 !== 0;
    let starsHTML = '';
    
    for (let i = 0; i < fullStars; i++) {
        starsHTML += '★';
    }
    
    if (hasHalfStar) {
        starsHTML += '☆';
    }
    
    const emptyStars = 5 - Math.ceil(rating);
    for (let i = 0; i < emptyStars; i++) {
        starsHTML += '☆';
    }
    
    return starsHTML;
}

// Calculate days between two dates
function calculateDays(checkin, checkout) {
    const checkinDate = new Date(checkin);
    const checkoutDate = new Date(checkout);
    const timeDiff = checkoutDate.getTime() - checkinDate.getTime();
    return Math.ceil(timeDiff / (1000 * 3600 * 24));
}

// Get URL parameters
function getUrlParams() {
    const params = new URLSearchParams(window.location.search);
    const result = {};
    for (const [key, value] of params) {
        result[key] = value;
    }
    return result;
}

// Validate email format
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Show loading state
function showLoading(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.innerHTML = '<p>Loading...</p>';
        element.style.display = 'block';
    }
}

// Hide loading state
function hideLoading(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.style.display = 'none';
    }
}

// Handle API errors
function handleApiError(error, defaultMessage = 'An error occurred') {
    console.error('API Error:', error);
    
    if (error.message) {
        showMessage(error.message, 'error');
    } else {
        showMessage(defaultMessage, 'error');
    }
}

// Debounce function for search inputs
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
