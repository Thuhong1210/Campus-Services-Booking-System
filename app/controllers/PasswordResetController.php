<?php
declare(strict_types=1);

class PasswordResetController extends Controller
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    /** GET – Show reset form using token */
    public function reset(): void
    {
        Middleware::guest();
        $token = trim($_GET['token'] ?? '');
        if (!$token) {
            Flash::error('Invalid or missing reset token.');
            redirect('login.php');
        }

        // Validate token exists
        $db   = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT id FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW() LIMIT 1');
        $stmt->execute([$token]);
        if (!$stmt->fetch()) {
            Flash::error('This reset link is invalid or has expired. Please request a new one.');
            redirect('index.php?page=forgot-password');
        }

        $this->view('auth/reset_password', ['title' => 'Reset Password', 'token' => $token], 'auth');
    }

    /** POST – Process password reset */
    public function update(): void
    {
        Middleware::guest();
        $this->verifyCsrf();

        $token           = trim($_POST['token'] ?? '');
        $newPassword     = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($newPassword !== $confirmPassword) {
            Flash::error('Passwords do not match.');
            redirect('index.php?page=password-reset&action=reset&token=' . urlencode($token));
        }

        $result = $this->authService->resetPassword($token, $newPassword);

        if ($result['success']) {
            Flash::success($result['message']);
            redirect('login.php');
        }

        Flash::error($result['message']);
        redirect('index.php?page=password-reset&action=reset&token=' . urlencode($token));
    }

    /** GET – Show send reset request form (redirect to forgot-password) */
    public function index(): void
    {
        redirect('index.php?page=forgot-password');
    }

    /** POST – Send reset email */
    public function sendReset(): void
    {
        Middleware::guest();
        $this->verifyCsrf();
        $email = trim($_POST['email'] ?? '');
        $this->authService->createPasswordResetToken($email);
        Flash::success('If that email is registered, a reset notification has been sent.');
        redirect('login.php');
    }
}
