<?php

namespace App\Controllers\Web;

use App\Core\Auth;
use App\Core\View;

class LoginController
{
    protected Auth $auth;
    protected View $view;

    /**
     * LoginController constructor.
     *
     * @param Auth $auth Authentication service
     * @param View $view View engine for rendering templates
     */
    public function __construct(Auth $auth, View $view)
    {
        $this->auth = $auth;
        $this->view = $view;
    }

    /**
     * Renders the login form.
     *
     * @return mixed Rendered view content
     */
    public function showLoginForm(): mixed
    {
        return $this->view->render('login', []);
    }

    /**
     * Handles login request.
     *
     * @return mixed Rendered view content or redirect
     */
    public function login(): mixed
    {
        $email = (string)($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        if ($this->auth->attempt($email, $password)) {
            header('Location: /dashboard');
            exit;
        }

        return $this->view->render('login', ['error' => 'Invalid credentials']);
    }

    /**
     * Handles logout request.
     *
     * @return void
     */
    public function logout(): void
    {
        $this->auth->logout();
        header('Location: /login');
        exit;
    }
}
