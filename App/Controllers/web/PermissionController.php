<?php

namespace App\Controllers\web;

use App\Models\Permission;
use App\Core\View;
use App\Core\Validator;
use App\Core\Redirect;

class PermissionController
{
    protected $model;

    public function __construct()
    {
        $this->model = new Permission();
    }

    public function index()
    {
        $permissions = $this->model->all();

        return view('permission/index', [
            'permissions' => $permissions
        ]);
    }

    public function create()
    {
        return View::render('permissions/create');
    }

    public function store()
    {
        $validator = new Validator();
        $validator->validate($_POST, [
            'name' => 'required',
            'description' => 'required',
        ]);

        $this->model->create($_POST);
        return Redirect::to('/admin/permissions');
    }

    public function edit($id)
    {
        $permission = $this->model->find($id);
        return View::render('permissions/edit', compact('permission'));
    }

    public function update($id)
    {
        $this->model->update($id, $_POST);
        return Redirect::to('/admin/permissions');
    }

    public function destroy($id)
    {
        $this->model->delete($id);
        return Redirect::to('/admin/permissions');
    }
}
