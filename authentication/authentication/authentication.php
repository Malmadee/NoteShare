<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="assets/css/authentication.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Urbanist:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <title>NoteShare | Login & Registration</title>
    <style>
        .error-message {
            position: fixed;
            bottom: 20px;
            left: 20px;
            background-color: #4a4a4a;
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
            display: none;
            z-index: 1000;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
            max-width: 400px;
            word-wrap: break-word;
        }
        .error-message.show {
            display: block;
            animation: slideIn 0.3s ease-in-out;
        }
        .success-message {
            position: fixed;
            bottom: 20px;
            left: 20px;
            background-color: #4a4a4a;
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
            display: none;
            z-index: 1000;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }
        .success-message.show {
            display: block;
            animation: slideIn 0.3s ease-in-out;
        }
        @keyframes slideIn {
            from {
                transform: translateX(-100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        .input-box.error input {
            border-color: #e74c3c;
        }
        .background-video {
            object-fit: cover;
            background-color: #000;
        }
    </style>
</head>
<body>
<!-- Background Video -->
<video autoplay muted loop playsinline preload="auto" class="background-video">
    <source src="assets/images/tablet.mp4" type="video/mp4">
    Your browser does not support the video tag.
</video>

<!-- Dark Overlay for Better Text Visibility -->
<div class="video-overlay"></div>

<!-- Add this wherever you need the navigation bar -->
<div id="nav-placeholder"></div>
<div class="wrapper">
    <nav class="nav">
        <div class="nav-logo">
            <img src="assets/images/logo.png" alt="Logo">
            <p>NoteShare</p>
        </div>
        <div class="nav-button">
            <button class="btn white-btn" id="loginBtn" onclick="login()">Sign In</button>
            <button class="btn white-btn" id="registerBtn" onclick="register()">Sign Up</button>
        </div>
    </nav>

    <div class="form-box">

        <!-- Login Form -->
        <div class="login-container" id="login">
            <div class="top">
                <header>Sign In</header>
            </div>
            <form id="loginForm">
                <div class="input-box">
                    <input type="email" class="input-field" id="loginEmail" name="email" placeholder="Email" required>
                    <i class="bx bx-user"></i>
                </div>
                <div class="input-box">
                    <input type="password" class="input-field" id="loginPassword" name="password" placeholder="Password" required>
                    <i class="bx bx-lock-alt"></i>
                </div>
                <div class="input-box">
                    <button type="submit" class="submit">Login</button>
                </div>
                <div class="two-col">
                    <div class="one">
                        <input type="checkbox" id="login-check">
                        <label for="login-check"> Remember Me</label>
                    </div>
                    <div class="two">
                        <label><a href="#">Forgot password?</a></label>
                    </div>
                </div>
            </form>
        </div>

        <!-- Registration Form -->
        <div class="register-container" id="register">
            <div class="top">
                <header>Sign Up</header>
            </div>
            <form id="registerForm">
                <div class="two-forms">
                    <div class="input-box">
                        <input type="text" class="input-field" id="firstName" name="first_name" placeholder="First Name" required>
                        <i class="bx bx-user"></i>
                    </div>
                    <div class="input-box">
                        <input type="text" class="input-field" id="lastName" name="last_name" placeholder="Last Name" required>
                        <i class="bx bx-user"></i>
                    </div>
                </div>
                <div class="input-box">
                    <input type="email" class="input-field" id="registerEmail" name="email" placeholder="Email" required>
                    <i class="bx bx-envelope"></i>
                </div>
                <div class="input-box">
                    <input type="password" class="input-field" id="registerPassword" name="password" placeholder="Password" required>
                    <i class="bx bx-lock-alt"></i>
                </div>
                <div class="input-box">
                    <button type="submit" class="submit">Register</button>
                </div>
                <div class="two-col">
                    <div class="one">
                        <input type="checkbox" id="register-check">
                        <label for="register-check"> Remember Me</label>
                    </div>
                    <div class="two">
                        <label><a href="#">Terms & conditions</a></label>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Message Boxes -->
<div id="loginError" class="error-message"></div>
<div id="loginSuccess" class="success-message"></div>
<div id="registerError" class="error-message"></div>
<div id="registerSuccess" class="success-message"></div>

<script>
    var a = document.getElementById("loginBtn");
    var b = document.getElementById("registerBtn");
    var x = document.getElementById("login");
    var y = document.getElementById("register");

    function login() {
        x.style.left = "0";
        y.style.left = "100%";
        a.className = "btn white-btn";
        b.className = "btn";
        x.style.opacity = 1;
        y.style.opacity = 0;
        // Clear register form and messages when switching to login
        document.getElementById('registerForm').reset();
        hideMessage('registerError');
        hideMessage('registerSuccess');
    }

    function register() {
        x.style.left = "-100%";
        y.style.left = "0";
        a.className = "btn";
        b.className = "btn white-btn";
        x.style.opacity = 0;
        y.style.opacity = 1;
        // Clear login form and messages when switching to register
        document.getElementById('loginForm').reset();
        hideMessage('loginError');
        hideMessage('loginSuccess');
    }

    // Helper function to show messages
    function showMessage(elementId, message) {
        const element = document.getElementById(elementId);
        element.textContent = message;
        element.classList.add('show');
    }

    // Helper function to hide messages
    function hideMessage(elementId) {
        const element = document.getElementById(elementId);
        element.classList.remove('show');
        element.textContent = '';
    }

    // Validate email format
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    // Login Form Handler
    document.getElementById('loginForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const email = document.getElementById('loginEmail').value.trim();
        const password = document.getElementById('loginPassword').value;
        
        hideMessage('loginError');
        hideMessage('loginSuccess');
        
        // Client-side validation
        if (!email) {
            showMessage('loginError', 'Email is required');
            return;
        }
        
        if (!password) {
            showMessage('loginError', 'Password is required');
            return;
        }
        
        if (!isValidEmail(email)) {
            showMessage('loginError', 'Invalid email format');
            return;
        }
        
        const formData = new FormData();
        formData.append('email', email);
        formData.append('password', password);
        
        try {
            const response = await fetch('login.php', {
                method: 'POST',
                body: formData
            });
            
            const text = await response.text();
            const data = JSON.parse(text);
            
            if (data.success) {
                showMessage('loginSuccess', 'Login Succesfull');
                // Clear form
                document.getElementById('loginForm').reset();
                setTimeout(() => {
                    window.location.href = 'home.html';
                }, 1500);
            } else {
                if (data.errors && Array.isArray(data.errors)) {
                    showMessage('loginError', data.errors[0]);
                } else if (data.message) {
                    showMessage('loginError', data.message);
                } else {
                    showMessage('loginError', 'Login failed. Please try again.');
                }
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage('loginError', 'Connection error. Make sure PHP server is running.');
        }
    });

    // Register Form Handler
    document.getElementById('registerForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const firstName = document.getElementById('firstName').value.trim();
        const lastName = document.getElementById('lastName').value.trim();
        const email = document.getElementById('registerEmail').value.trim();
        const password = document.getElementById('registerPassword').value;
        
        hideMessage('registerError');
        hideMessage('registerSuccess');
        
        // Client-side validation
        if (!firstName) {
            showMessage('registerError', 'First Name is required');
            return;
        }
        
        if (!lastName) {
            showMessage('registerError', 'Last Name is required');
            return;
        }
        
        if (!email) {
            showMessage('registerError', 'Email is required');
            return;
        }
        
        if (!isValidEmail(email)) {
            showMessage('registerError', 'Invalid email format');
            return;
        }
        
        if (!password) {
            showMessage('registerError', 'Password is required');
            return;
        }
        
        if (password.length < 6) {
            showMessage('registerError', 'Password must be at least 6 characters.');
            return;
        }
        
        const formData = new FormData();
        formData.append('first_name', firstName);
        formData.append('last_name', lastName);
        formData.append('email', email);
        formData.append('password', password);
        
        try {
            const response = await fetch('register.php', {
                method: 'POST',
                body: formData
            });
            
            const text = await response.text();
            const data = JSON.parse(text);
            
            if (data.success) {
                showMessage('registerSuccess', 'Registration Succesfull!');
                // Clear form
                document.getElementById('registerForm').reset();
                setTimeout(() => {
                    window.location.href = 'home.html';
                }, 1500);
            } else {
                if (data.errors && Array.isArray(data.errors)) {
                    showMessage('registerError', data.errors[0]);
                } else if (data.message) {
                    showMessage('registerError', data.message);
                } else {
                    showMessage('registerError', 'Registration failed. Please try again.');
                }
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage('registerError', 'Connection error. Make sure PHP server is running and database is set up.');
        }
    });
</script>

</body>
</html>
