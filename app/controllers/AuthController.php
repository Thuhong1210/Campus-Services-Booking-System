<?php

declare(strict_types=1);

class AuthController extends Controller
{
    private AuthService $authService;
    private UserRepository $userRepo;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->userRepo = new UserRepository();
    }

    public function loginForm(): void
    {
        Middleware::guest();
        $this->view('auth/login', ['title' => 'Login'], 'auth');
    }

    public function login(): void
    {
        Middleware::guest();
        $this->verifyCsrf();

        $login = trim((string) ($this->post()['login'] ?? ''));
        $password = (string) ($this->post()['password'] ?? '');

        $validator = new Validator(['login' => $login, 'password' => $password]);
        $validator->required('login')->required('password');

        if ($validator->fails()) {
            Flash::error($validator->firstError() ?? 'Invalid input.');
            $_SESSION['old_input'] = ['login' => $login];
            redirect('login.php');
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $result = $this->authService->login($login, $password, $ip);

        if ($result['success']) {
            Flash::success('Welcome back, ' . ($result['user']['full_name'] ?? 'User') . '!');
            redirect('index.php?page=dashboard');
        }

        Flash::error($result['message'] ?? 'Invalid email, username, student ID, or password.');
        $_SESSION['old_input'] = ['login' => $login];
        redirect('login.php');
    }

    public function logout(): void
    {
        $this->authService->logout();
        Flash::success('You have been logged out successfully.');
        redirect('login.php');
    }

    public function forgotPassword(): void
    {
        Middleware::guest();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyCsrf();

            $email = trim((string) ($this->post()['email'] ?? ''));
            $validator = new Validator(['email' => $email]);
            $validator->required('email', 'Email')->email('email');

            if ($validator->fails()) {
                Flash::error($validator->firstError() ?? 'Invalid email.');
                $_SESSION['old_input'] = ['email' => $email];
                redirect('index.php?page=forgot-password');
            }

            $this->authService->createPasswordResetToken($email);
            Flash::success('If an account exists with that email, a reset notification has been sent to your account.');
            redirect('login.php');
        }

        $this->view('auth/forgot_password', ['title' => 'Forgot Password'], 'auth');
    }

    public function changePassword(): void
    {
        Middleware::auth();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyCsrf();

            $data = $this->post();
            $validator = new Validator($data);
            $validator
                ->required('current_password', 'Current password')
                ->required('new_password', 'New password')
                ->required('confirm_password', 'Confirm password')
                ->min('new_password', 8, 'New password');

            if ($validator->fails()) {
                Flash::error($validator->firstError() ?? 'Validation failed.');
                redirect('index.php?page=change-password');
            }

            if (($data['new_password'] ?? '') !== ($data['confirm_password'] ?? '')) {
                Flash::error('New password and confirmation do not match.');
                redirect('index.php?page=change-password');
            }

            $result = $this->authService->changePassword(
                (int) Auth::id(),
                (string) $data['current_password'],
                (string) $data['new_password']
            );

            if ($result['success']) {
                Flash::success($result['message'] ?? 'Password changed successfully.');
                redirect('index.php?page=dashboard');
            }

            Flash::error($result['message'] ?? 'Unable to change password.');
            redirect('index.php?page=change-password');
        }

        $this->view('auth/change_password', ['title' => 'Change Password'], 'auth');
    }
}
