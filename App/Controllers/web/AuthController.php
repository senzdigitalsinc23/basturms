<?php

namespace App\controllers\web;

use App\Core\Controller;
use App\Core\Session;
use App\Core\View;
use App\Models\Role;
use App\Models\User;

class AuthController extends Controller
{
    protected View $view;

    /**
     * AuthController constructor.
     *
     * @param View $view View engine for rendering web templates
     */
    public function __construct(View $view)
    {
        $this->view = $view;
        $this->view->layout('layouts.main');
    }

    /**
     * Renders the login page.
     *
     * @return mixed Rendered view content
     */
    public function index(): mixed
    {       
        return $this->view->render('auth/login', [
            'title' => 'Welcome to My Framework'
        ]);
    }

    /**
     * Renders the registration form (currently redirected to admin users).
     *
     * @return mixed Rendered view content
     */
    public function registerForm(): mixed
    {
        $users = (array)Session::get('user');
        $roles = (array)Role::all();

        return $this->view->render('admin/users', [
            'title' => 'Welcome to My Framework',
            'user'  => $users,
            'roles' => $roles
        ]);
    }

    /**
     * Logs out the current user and redirects to login.
     *
     * @return mixed Rendered view content
     */
    public function logout(): mixed
    {
        Session::destroy();
        return $this->view->render('auth/login');
    }
}