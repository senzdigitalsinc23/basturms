<?php

namespace App\Controllers\web;

use App\Models\Role;

class RoleController 
{
    private $model;

    public function __construct() {
        $this->model = new Role();
    }

    public function permissions($role_id)
    {
        $role = $this->model->permissions($role_id);
        $allPermissions = $this->model->allPermissions();
        $rolePermissions = array_column($this->model->permissions($role_id), 'id');

        return view('roles/permissions', compact('role', 'allPermissions', 'rolePermissions'));
    }

    public function updatePermissions($role_id)
    {
        $permission_ids = $_POST['permissions'] ?? [];
        $this->model->syncPermissions($role_id, $permission_ids);

        return redirect('/admin/roles');
    }

}
