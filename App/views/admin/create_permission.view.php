<?php ob_start(); ?>
<?php layout(['header', 'navbar', 'sidebar']) ?>

<h2 class="mb-4 mt-5">Permissions</h2>

<a href="/web/admin/permission/create" class="btn btn-primary mb-3">Add Permission</a>
<table class="table table-bordered">
    <thead>
        <tr><th>ID</th><th>Name</th><th>Actions</th></tr>
    </thead>
    <tbody>
        <?php foreach ($permissions as $perm): ?>
        <tr>
            <td><?= $perm['id'] ?></td>
            <td><?= $perm['name'] ?></td>
            <td>
                <a href="/web/admin/permissions/<?= $perm['id'] ?>/edit" class="btn btn-sm btn-warning">Edit</a>
                <a href="/web/admin/permissions/<?= $perm['id'] ?>/delete" class="btn btn-sm btn-danger">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php layout('footer') ?>
