<?php ob_start(); ?>
<?php layout(['header', 'navbar', 'sidebar']) ?>

<h2 class="mb-4 mt-4"><?= isset($role) ? 'Edit Role' : 'Add New Role' ?></h2>
<form method="post" action="<?= $action ?>">
    <div class="mb-3">
        <label>Role Name</label>
        <input type="text" name="name" class="form-control" value="<?= $role['name'] ?? '' ?>" required>
    </div>
    <div class="mb-3">
        <label>Assign Permissions</label>
        <?php foreach ($permissions as $perm): ?>
        <div class="form-check">
            <input type="checkbox" class="form-check-input" name="permissions[]" value="<?= $perm['id'] ?>"
                <?= (isset($role) && in_array($perm['id'], $role['permission_ids'] ?? [])) ? 'checked' : '' ?>>
            <label class="form-check-label"><?= $perm['name'] ?></label>
        </div>
        <?php endforeach; ?>
    </div>
    <button class="btn btn-success">Save</button>
</form>
<?php layout('footer') ?>
