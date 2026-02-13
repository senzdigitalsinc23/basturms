<?php

namespace App\Controllers\web;

use App\Models\Permission;
use App\Core\View;
use App\Core\Validator;
use App\Core\Redirect;

class PermissionController
{
    protected $model;

    /**
     * PermissionController constructor.
     */
    public function __construct()
    {
        $this->model = new Permission();
    }

    /**
     * Lists all permissions.
     *
     * @return mixed Rendered view content
     */
    public function index(): mixed
    {
        $permissions = (array)$this->model->all();

        return view('permission/index', [
            'permissions' => $permissions
        ]);
    }

    /**
     * Renders the permission creation form.
     *
     * @return mixed Rendered view content
     */
    public function create(): mixed
    {
        return View::render('permissions/create');
    }

    /**
     * Stores a new permission record.
     *
     * @return mixed Redirect response
     */
    public function store(): mixed
    {
        $validator = new Validator();
        $validator->validate((array)$_POST, [
            'name' => 'required',
            'description' => 'required',
        ]);

        $this->model->create((array)$_POST);
        return Redirect::to('/admin/permissions');
    }

    /**
     * Renders the permission edit form.
     *
     * @param mixed $id Permission ID
     * @return mixed Rendered view content
     */
    public function edit($id): mixed
    {
        $permission = $this->model->find($id);
        return View::render('permissions/edit', compact('permission'));
    }

    /**
     * Updates an existing permission record.
     *
     * @param mixed $id Permission ID
     * @return mixed Redirect response
     */
    public function update($id): mixed
    {
        $this->model->update($id, (array)$_POST);
        return Redirect::to('/admin/permissions');
    }

    /**
     * Deletes a permission record.
     *
     * @param mixed $id Permission ID
     * @return mixed Redirect response
     */
    public function destroy($id): mixed
    {
        $this->model->delete($id);
        return Redirect::to('/admin/permissions');
    }
}
