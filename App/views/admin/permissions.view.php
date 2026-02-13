<?php ob_start(); ?>
<?php layout(['header', 'navbar', 'sidebar'])?>

<h2 class="mb-4 mt-4">Permissions</h2>

<div class="permissions-container">
    <div class="create-permission">
        
    </div>

    <div class="view-permissions">
        <a href="/web/admin/permission/create" class="btn btn-primary mb-3">Add Permission</a>
        <table class="table table-bordered">
            <thead>
                <tr><th>ID</th><th>Name</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php if($permissions) : ?>
                <?php foreach ($permissions as $perm): ?>
                <tr>
                    <td><?= $perm['id'] ?></td>
                    <td><?= $perm['name'] ?></td>
                    <td>
                        <a href="/admin/permissions/<?= $perm['id'] ?>/edit" class="btn btn-sm btn-warning">Edit</a>
                        <a href="/admin/permissions/<?= $perm['id'] ?>/delete" class="btn btn-sm btn-danger">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php else :?>
                    <tr><td colspan="5" class="text-center">No Data Found</td></tr>
                <?php endif ?>
            </tbody>
        </table>
    </div>
</div>

<?php layout('footer') ?>
