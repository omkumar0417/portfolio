<?php
/**
 * Auth Controller
 */

declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/User.php';

class AuthController extends BaseController {
    private User $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    /**
     * Handles user sign-in and session start
     */
    public function login(): void {
        $this->requireGuest();
        
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->renderAuth('auth/login', ['pageTitle' => 'Sign In']);
            return;
        }

        // POST credentials processing
        if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
            setFlash('error', 'Security token mismatch. Please try again.');
            $this->redirect('/login');
        }

        // Rate limit checks
        if (!checkRateLimit('login_attempts', 5, 60)) {
            setFlash('error', 'Too many attempts. Please try again in a minute.');
            $this->redirect('/login');
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        if ($email === '' || $password === '') {
            setFlash('error', 'All fields are required.');
            $this->redirect('/login');
        }

        $user = $this->userModel->findByEmail($email);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['is_verified'] == 0) {
                setFlash('error', 'Please verify your email address before logging in.');
                $this->redirect('/login');
            }

            // Start session and bind data
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_avatar'] = $user['avatar'];
            
            // Load user visual settings into session
            $db = DB::getConnection();
            $settings = DB::fetch("SELECT * FROM settings WHERE user_id = ?", [$user['id']]);
            if ($settings) {
                $_SESSION['theme'] = $settings['theme'];
                $_SESSION['accent_color'] = $settings['accent_color'];
                $_SESSION['card_radius'] = (int)$settings['card_radius'];
                $_SESSION['compact_mode'] = (int)$settings['compact_mode'];
                $_SESSION['sidebar_style'] = $settings['sidebar_style'];
                $_SESSION['wallpaper'] = $settings['wallpaper'];
                $_SESSION['font_size'] = $settings['font_size'];
            }

            // Remember Me token setup
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
                DB::query("UPDATE users SET reset_token = ?, reset_token_expires = DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE id = ?", [$token, $user['id']]);
            }

            $this->userModel->logLogin((int)$user['id'], $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 'success');
            
            $redirectUrl = $_SESSION['redirect_url'] ?? '/dashboard';
            unset($_SESSION['redirect_url']);
            
            setFlash('success', "Welcome back, {$user['name']}!");
            $this->redirect($redirectUrl);
        } else {
            // Log failure attempt if email exists
            if ($user) {
                $this->userModel->logLogin((int)$user['id'], $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 'failed');
            }
            
            setFlash('error', 'Invalid email or password.');
            $this->redirect('/login');
        }
    }

    /**
     * Handles account registrations
     */
    public function signup(): void {
        $this->requireGuest();

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->renderAuth('auth/signup', ['pageTitle' => 'Create Account']);
            return;
        }

        // POST registrations processing
        if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
            setFlash('error', 'Security token mismatch.');
            $this->redirect('/signup');
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($name === '' || $email === '' || $password === '') {
            setFlash('error', 'Please fill in all required fields.');
            $this->redirect('/signup');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setFlash('error', 'Please provide a valid email address.');
            $this->redirect('/signup');
        }

        if ($password !== $confirmPassword) {
            setFlash('error', 'Passwords do not match.');
            $this->redirect('/signup');
        }

        if (strlen($password) < 8) {
            setFlash('error', 'Password must be at least 8 characters long.');
            $this->redirect('/signup');
        }

        // Verify if email is already taken
        if ($this->userModel->findByEmail($email) !== null) {
            setFlash('error', 'This email address is already registered.');
            $this->redirect('/signup');
        }

        // Create Account
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $verificationToken = bin2hex(random_bytes(32));
        
        try {
            $userId = $this->userModel->create($name, $email, $passwordHash, $verificationToken);
            
            // Dispatch verification email
            $verifyLink = APP_URL . "/verify-email?token=" . $verificationToken;
            $emailBody = "<h2>Welcome to AetherLife!</h2>
                          <p>Hi {$name},</p>
                          <p>Thank you for registering. Please verify your account by clicking the link below:</p>
                          <p><a href='{$verifyLink}' style='background-color:#6366f1;color:#fff;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;'>Verify Account</a></p>
                          <p>If the link does not work, copy and paste this URL into your browser: <br> {$verifyLink}</p>";
            
            sendEmail($email, "Verify Your AetherLife Account", $emailBody);

            setFlash('success', 'Registration successful! Please check your email to verify your account.');
            $this->redirect('/login');
        } catch (Exception $e) {
            error_log("Registration Failure: " . $e->getMessage());
            setFlash('error', 'Could not create account. Please try again.');
            $this->redirect('/signup');
        }
    }

    /**
     * Verifies user email account
     */
    public function verifyEmail(): void {
        $token = $_GET['token'] ?? '';
        if ($token === '') {
            setFlash('error', 'Invalid verification token.');
            $this->redirect('/login');
        }

        if ($this->userModel->verifyEmail($token)) {
            setFlash('success', 'Email verification complete. You can now log in.');
        } else {
            setFlash('error', 'Invalid or expired verification token.');
        }
        $this->redirect('/login');
    }

    /**
     * Password recovery link request
     */
    public function forgotPassword(): void {
        $this->requireGuest();

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->renderAuth('auth/forgot_password', ['pageTitle' => 'Recover Password']);
            return;
        }

        // POST token requests
        if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
            setFlash('error', 'Security token mismatch.');
            $this->redirect('/forgot-password');
        }

        $email = trim($_POST['email'] ?? '');
        if ($email === '') {
            setFlash('error', 'Please provide your email address.');
            $this->redirect('/forgot-password');
        }

        $user = $this->userModel->findByEmail($email);
        if ($user) {
            $resetToken = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $this->userModel->setResetToken((int)$user['id'], $resetToken, $expires);
            
            // Dispatch Reset Link
            $resetLink = APP_URL . "/reset-password?token=" . $resetToken;
            $emailBody = "<h2>AetherLife Password Reset</h2>
                          <p>Hi {$user['name']},</p>
                          <p>We received a password reset request for your account. Click the button below to set a new password (valid for 1 hour):</p>
                          <p><a href='{$resetLink}' style='background-color:#ef4444;color:#fff;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;'>Reset Password</a></p>
                          <p>If you did not request this, please ignore this email.</p>";
            
            sendEmail($email, "Reset Your AetherLife Password", $emailBody);
        }

        // To prevent account harvesting, we display the same success alert regardless of database match
        setFlash('success', 'If the email exists, a password reset link has been dispatched.');
        $this->redirect('/login');
    }

    /**
     * Set new password via token
     */
    public function resetPassword(): void {
        $this->requireGuest();
        $token = $_GET['token'] ?? $_POST['token'] ?? '';
        
        if ($token === '') {
            setFlash('error', 'Invalid token link.');
            $this->redirect('/login');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // Verify token is active
            $db = DB::getConnection();
            $user = DB::fetch("SELECT id FROM users WHERE reset_token = ? AND reset_token_expires > NOW()", [$token]);
            if (!$user) {
                setFlash('error', 'Password reset token is invalid or has expired.');
                $this->redirect('/login');
            }

            $this->renderAuth('auth/reset_password', ['pageTitle' => 'Reset Password', 'token' => $token]);
            return;
        }

        // POST new password updates
        if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
            setFlash('error', 'Security token mismatch.');
            $this->redirect("/reset-password?token={$token}");
        }

        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($password === '' || $confirmPassword === '') {
            setFlash('error', 'Fields are required.');
            $this->redirect("/reset-password?token={$token}");
        }

        if ($password !== $confirmPassword) {
            setFlash('error', 'Passwords do not match.');
            $this->redirect("/reset-password?token={$token}");
        }

        if (strlen($password) < 8) {
            setFlash('error', 'Password must be at least 8 characters long.');
            $this->redirect("/reset-password?token={$token}");
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        if ($this->userModel->resetPassword($token, $passwordHash)) {
            setFlash('success', 'Password successfully updated. You can now log in.');
            $this->redirect('/login');
        } else {
            setFlash('error', 'Failed to reset password. Link may have expired.');
            $this->redirect('/login');
        }
    }

    /**
     * Terminate user session
     */
    public function logout(): void {
        if (isset($_SESSION['user_id'])) {
            $this->userModel->logActivity((int)$_SESSION['user_id'], 'USER_LOGOUT', 'Logged out and ended session.', $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        }

        // Unset session variables
        $_SESSION = [];
        
        // Destroy cookies
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Clear Remember Me cookie
        setcookie('remember_token', '', time() - 3600, '/');
        
        session_destroy();
        $this->redirect('/login');
    }
}
