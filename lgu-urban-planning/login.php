<?php

// Login Page

require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Auth.php';

$auth = new Auth();
$error = '';
$dbInstance = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameInput = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Check user existence
    $user = $dbInstance->fetchOne(
        "SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1", 
        [$usernameInput, $usernameInput]
    );

    if ($user && password_verify($password, $user['password_hash'])) {

        if ((int)$user['is_verified'] === 0) {
    header("Location: register.php?step=otp&email=" . urlencode($user['email']));
    exit;
}

        // --- MANDATORY ACTIVATION CHECK ---
        if ((int)$user['is_verified'] === 0) {
            $error = "Account not verified. Please enter the OTP sent to your email first.";
        } else {
            if ($auth->login($usernameInput, $password)) {
                session_regenerate_id(true);
                $auth->redirectToDashboard();
                exit;
            } else {
                $error = 'Invalid username or password';
            }
        }
    } else {
        $error = 'Invalid username or password';
    }
}

if ($auth->isLoggedIn()) {
    $auth->redirectToDashboard();
    exit;
}

$pageTitle = "Login - LGU Urban Planning System";
include __DIR__ . '/auth/header.php'; 
?>

<style>
    .login-container { 
        flex: 1; display: flex; align-items: center; justify-content: center; 
        padding: 10px; margin-top: 5px; 
    }

    .login-card { 
        width: 100%; max-width: 380px; 
        background: rgba(255, 255, 255, 0.85); 
        padding: 15px 32px; border-radius: 18px;
        backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        position: relative; overflow: hidden;
    }

    .login-header { 
        background: linear-gradient(135deg, #6384d2, #285ccd); 
        margin: -28px -32px 25px -32px; padding: 20px; 
        text-align: center; color: white; 
    }

    .login-logo { width: 80px; margin-bottom: 10px; }

    .form-label { font-size: 13px; font-weight: 600; color: #000; margin-bottom: 5px; }

    .form-control {
        background: rgba(255,255,255,0.7) !important;
        border: 1px solid rgba(0,0,0,0.1); border-radius: 10px;
        padding: 10px 12px; color: #000;
        font-size: 13px;
    }

    .btn-login {
        width: 100%; padding: 10px;
        background: linear-gradient(135deg, #6384d2, #285ccd);
        border: none; border-radius: 12px;
        color: #fff; font-size: 16px; font-weight: 600;
        transition: 0.25s ease;
    }

    .btn-login:hover {
        background: linear-gradient(135deg, #4d76d6, #1651d0);
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(43, 91, 222, 0.45);
        color: #fff;
    }

    .password-wrapper { position: relative; }
    .password-toggle {
        position: absolute; right: 12px; top: 50%;
        transform: translateY(-50%); cursor: pointer; opacity: 0.7;
    }

</style>

<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <img src="assets/img/lgu-logo.png" alt="LGU Logo" class="login-logo">
            <h5 class="fw-bold mb-1" data-en="LGU Urban Planning" data-tl="Pagpaplano ng Lungsod">LGU Urban Planning</h5>
            <div class="small opacity-75" style="font-size: 0.65rem;" 
                 data-en="Development Permit Management System" 
                 data-tl="Sistema ng Pamamahala ng Permit">Development Permit Management System</div>
        </div>

        <div>
            <h5 class="text-center mb-4 fw-bold text-dark" data-en="Login to Your Account" data-tl="Mag-login">Login to Your Account</h5>
            
            <?php if ($error): ?>
                <div class="alert alert-danger py-2 small mb-3">
                    <i class="bi bi-exclamation-circle me-1"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">
                        <i class="bi bi-person me-1"></i> 
                        <span data-en="Username or Email" data-tl="Username o Email">Username or Email</span>
                    </label>
                    <input type="text" class="form-control shadow-sm" name="username" 
                        data-en-placeholder="Enter your username or email" 
                        data-tl-placeholder="Ilagay ang iyong username o email" 
                        placeholder="Enter your username or email" required>
                </div>

                <div class="mb-4">
                    <label class="form-label">
                        <i class="bi bi-lock me-1"></i> 
                        <span data-en="Password" data-tl="Password">Password</span>
                    </label>
                    <div class="password-wrapper">
                        <input type="password" id="password" class="form-control shadow-sm" name="password" 
                            data-en-placeholder="Enter your password" 
                            data-tl-placeholder="Ilagay ang iyong password" 
                            placeholder="Enter your password" required>
                        <i class="bi bi-eye password-toggle" onclick="togglePassword('password', 'toggleIcon')" id="toggleIcon"></i>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-login shadow-sm">
                    <i class="bi bi-box-arrow-in-right me-2"></i> 
                    <span data-en="Login" data-tl="Mag-login">Login</span>
                </button>
            </form>
            
            <div class="text-center mt-4">
                <a href="register.php" class="small text-decoration-none fw-bold" style="color: #2864ef;">
                    <i class="bi bi-person-plus me-1"></i> 
                    <span data-en="Don't have an account? Register" data-tl="Wala pang account? Mag-rehistro">Don't have an account? Register</span>
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php include __DIR__ . '/auth/footer.php'; ?>