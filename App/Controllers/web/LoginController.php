<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;

class LoginController
{
    protected Auth $auth;
    protected View $view;

    public function __construct(Auth $auth, View $view)
    {
        $this->auth = $auth;
        $this->view = $view;
    }

    public function showLoginForm()
    {
        return $this->view->render('login', []);
    }

    public function login()
    {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if ($this->auth->attempt($email, $password)) {
            header('Location: /dashboard');
            exit;
        }

        return $this->view->render('login', ['error' => 'Invalid credentials']);
    }

    public function logout()
    {
        $this->auth->logout();
        header('Location: /login');
        exit;
    }
}
