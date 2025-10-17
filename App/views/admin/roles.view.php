<?php ob_start(); ?>

<?php layout(['header', 'navbar', 'sidebar']) ?>

<h2 class="mb-4 mt-4">Roles</h2>
<a href="/web/admin/roles/create" class="btn btn-danger btn-sm mb-3 float-end"><?=icon('plus-circle-fill')?></a>
<table class="table table-bordered">
    <thead>
        <tr><th>ID</th><th>Name</th><th>Permissions</th><th>Actions</th></tr>
    </thead>
    <tbody>
        <?php if($roles) :?>
        <?php foreach ($roles as $role) :?>
        <tr>
            <td><?= $role['id'] ?></td>
            <td><?= $role['name'] ?></td>
            <td><?= implode(', ', $role['permissions'] ?? []) ?></td>
            <td>
                <a href="/web/admin/roles/<?= $role['id'] ?>/edit" class="btn btn-sm btn-warning">Edit</a>
                <a href="/web/admin/roles/<?= $role['id'] ?>/delete" class="btn btn-sm btn-danger">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php else :?>
            <tr><td colspan="5" class="text-center">No Data Found</td></tr>
        <?php endif ?>
    </tbody>
</table>

<?php layout('footer') ?>
