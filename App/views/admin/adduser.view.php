<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container mt-5" style="max-width: 600px;">
    <h3><?= isset($user) ? 'Edit User' : 'Add New User' ?></h3>

    <form action="<?= $action ?>" method="POST">
        <div class="mb-3">
            <label>Name</label>
            <input type="text" name="name" class="form-control" value="<?= old('name', $user['name'] ?? '') ?>" required>
        </div>

        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="<?= old('email', $user['email'] ?? '') ?>" required>
        </div>

        <div class="mb-3">
            <label>Password <?= isset($user) ? '(leave blank to keep existing)' : '' ?></label>
            <input type="password" name="password" class="form-control">
        </div>

        <div class="mb-3">
            <label>Role</label>
            <select name="role" class="form-select" required>
                <?php foreach ($roles as $role): ?>
                    <option value="<?= $role ?>" <?= (old('role', $user['role'] ?? '') === $role) ? 'selected' : '' ?>>
                        <?= ucfirst($role) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button class="btn btn-primary"><?= isset($user) ? 'Update' : 'Create' ?></button>
    </form>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
