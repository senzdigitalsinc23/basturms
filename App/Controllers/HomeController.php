<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Middleware\AuthMiddleware;
use App\Models\User;

class HomeController {

    protected View $view;
    protected Database $db;


    // Inject View via constructor
    public function __construct(View $view)
    {
        $this->view = $view;
        $this->view->layout('layouts.main');
    }

    public function index() {        //show(empty($params));

        
        $users = (new User())->all();

        return $this->view->render('home', [
            'title' => 'Welcome to My Framework',
            'users' => $users
        ]);

        exit;
    }

    
}