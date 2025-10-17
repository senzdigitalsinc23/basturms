<?php

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Core\Session;
use App\Core\View;
use App\Models\Role;
use App\Models\User;

class AdminController extends Controller
{
    protected View $view;

    public function __construct(View $view) {
        $this->view = $view;
        $this->view->layout('layouts.main');
        
        /* if(!isLoggedIn()){
            redirect('/web/login');
        } */
    }
    public function index()
    {
        return $this->view->render('admin/index', [
            'title' => 'Welcome to My Framework',
        ]);
        exit;
    }

    public function users()
    {
        $users = [];

        $users = User::all();

        $roles = Role::all();

        return $this->view->render('admin/users', [
            'errors' => [],
            'users' => $users ?? [],
            'roles' => $roles ?? []
        ]);
    }

    public function createUser()  {
        $roles = User::getRoles();

        
        return view('admin/create_user', [
            'roles' => $roles ?? []
        ]);
    }

    public function students()
    {        
        return view('admin/students');
    }

    public function roles(){
        $roles = [];

        $roles = Role::all();

        //show($roles);

        return view('admin/roles', [
            'roles' => $roles
        ]);
    }

    public function createRole() {
        $roles = [];

        $roles = Role::all();

        show($roles);

        return view('admin/create_role', [
            'permissions' => $permisions
        ]);
    }

    public function permissions() {
        $permisions = [];

        return view('admin/permissions', [
            'permissions' => $permisions
        ]);
    }

    public function createPermission() {
        $permisions = [];

        return view('admin/create_permission', [
            'permissions' => $permisions
        ]);
    }

    

}
