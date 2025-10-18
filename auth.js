// Simplified Authentication System for Web Course
// Implements the "2 logs" requirement: Login and Logout

document.addEventListener('DOMContentLoaded', function() {
    // Redirect if already logged in (for login/register pages)
    if (currentUser && (window.location.pathname.includes('login.html') || window.location.pathname.includes('register.html'))) {
        window.location.href = '../index.html';
        return;
    }

    // Setup form handlers
    setupLoginForm();
    setupRegisterForm();
});

// Setup login form
function setupLoginForm() {
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }
}

// Setup register form
function setupRegisterForm() {
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', handleRegister);
    }
}

// Handle login form submission (LOG 1: LOGIN)
async function handleLogin(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const loginData = {
        username: formData.get('username'),
        password: formData.get('password')
    };

    // Basic validation
    if (!loginData.username || !loginData.password) {
        showMessage('Please fill in all fields', 'error');
        return;
    }

    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    
    try {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Logging in...';

        console.log('🔐 LOGIN ATTEMPT:', {
            username: loginData.username,
            timestamp: new Date().toISOString(),
            action: 'login'
        });

        const response = await fetch(`${API_BASE}/auth.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'login',
                ...loginData
            })
        });

        const data = await response.json();

        if (data.success) {
            console.log('✅ LOGIN SUCCESS:', {
                username: loginData.username,
                userId: data.user.id,
                role: data.user.role,
                timestamp: new Date().toISOString()
            });

            // Store session ID
            localStorage.setItem('sessionId', data.session_id);

            showMessage('Login successful!', 'success');
            
            // Update current user
            currentUser = data.user;
            window.currentUser = data.user;
            if (typeof updateNavigation === 'function') {
                updateNavigation(true);
            } else if (window.updateNavigation) {
                window.updateNavigation(true);
            }

            // Redirect after successful login
            setTimeout(() => {
                window.location.href = '../index.html';
            }, 1000);
        } else {
            console.log('❌ LOGIN FAILED:', {
                username: loginData.username,
                reason: data.message,
                timestamp: new Date().toISOString()
            });
            showMessage(data.message || 'Login failed', 'error');
        }
    } catch (error) {
        console.error('🚨 LOGIN ERROR:', error);
        showMessage('Error during login. Please try again.', 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
}

// Handle register form submission
async function handleRegister(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const registerData = {
        username: formData.get('username'),
        email: formData.get('email'),
        password: formData.get('password'),
        confirmPassword: formData.get('confirmPassword'),
        full_name: formData.get('fullName'),
        phone: formData.get('phone')
    };

    // Validation
    if (!registerData.username || !registerData.email || !registerData.password) {
        showMessage('Please fill in all required fields', 'error');
        return;
    }

    if (registerData.password !== registerData.confirmPassword) {
        showMessage('Passwords do not match', 'error');
        return;
    }

    if (registerData.password.length < 6) {
        showMessage('Password must be at least 6 characters long', 'error');
        return;
    }

    if (!isValidEmail(registerData.email)) {
        showMessage('Please enter a valid email address', 'error');
        return;
    }

    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    
    try {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Creating Account...';

        console.log('📝 REGISTRATION ATTEMPT:', {
            username: registerData.username,
            email: registerData.email,
            timestamp: new Date().toISOString()
        });

        const response = await fetch(`${API_BASE}/auth.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'register',
                username: registerData.username,
                email: registerData.email,
                password: registerData.password,
                full_name: registerData.full_name,
                phone: registerData.phone
            })
        });

        const data = await response.json();

        if (data.success) {
            console.log('✅ REGISTRATION SUCCESS:', {
                username: registerData.username,
                email: registerData.email,
                userId: data.user.id,
                timestamp: new Date().toISOString()
            });

            // Store session ID and auto-login
            localStorage.setItem('sessionId', data.session_id);
            currentUser = data.user;
            window.currentUser = data.user;

            showMessage('Account created successfully! Redirecting...', 'success');
            
            // Redirect to home page after registration
            setTimeout(() => {
                window.location.href = '../index.html';
            }, 1500);
        } else {
            console.log('❌ REGISTRATION FAILED:', {
                username: registerData.username,
                email: registerData.email,
                reason: data.message,
                timestamp: new Date().toISOString()
            });
            showMessage(data.message || 'Registration failed', 'error');
        }
    } catch (error) {
        console.error('🚨 REGISTRATION ERROR:', error);
        showMessage('Error during registration. Please try again.', 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
}

// Enhanced logout function (LOG 2: LOGOUT)
async function logout() {
    if (!currentUser) {
        return;
    }

    try {
        console.log('🚪 LOGOUT ATTEMPT:', {
            username: currentUser.username,
            userId: currentUser.id,
            timestamp: new Date().toISOString(),
            action: 'logout'
        });

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
            console.log('✅ LOGOUT SUCCESS:', {
                username: currentUser.username,
                userId: currentUser.id,
                timestamp: new Date().toISOString()
            });

            // Clear session storage and current user
            localStorage.removeItem('sessionId');
            currentUser = null;
            window.currentUser = null;
            updateNavigation(false);
            
            showMessage('Logged out successfully', 'success');
            
            // Redirect to home page after logout
            setTimeout(() => {
                window.location.href = '../index.html';
            }, 1000);
        } else {
            console.log('❌ LOGOUT FAILED:', {
                reason: data.message,
                timestamp: new Date().toISOString()
            });
        }
    } catch (error) {
        console.error('🚨 LOGOUT ERROR:', error);
        showMessage('Error logging out', 'error');
    }
}

// Make logout function globally available
window.logout = logout;