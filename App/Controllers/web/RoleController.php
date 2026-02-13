<?php

namespace App\Controllers\web;

use App\Models\Role;

class RoleController 
{
    private $model;

    /**
     * RoleController constructor.
     */
    public function __construct() {
        $this->model = new Role();
    }

    /**
     * Renders the role permissions assignment page.
     *
     * @param mixed $role_id Role ID
     * @return mixed Rendered view content
     */
    public function permissions($role_id): mixed
    {
        $role = $this->model->permissions($role_id);
        $allPermissions = (array)$this->model->allPermissions();
        $rolePermissions = (array)array_column((array)$this->model->permissions($role_id), 'id');

        return view('roles/permissions', compact('role', 'allPermissions', 'rolePermissions'));
    }

    /**
     * Updates permissions assigned to a role.
     *
     * @param mixed $role_id Role ID
     * @return mixed Redirect response
     */
    public function updatePermissions($role_id): mixed
    {
        $permission_ids = (array)($_POST['permissions'] ?? []);
        $this->model->syncPermissions($role_id, $permission_ids);

        return redirect('/admin/roles');
    }

}
