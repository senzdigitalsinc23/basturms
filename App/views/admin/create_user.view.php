<?php ob_start(); ?>
<?php layout(['header', 'navbar', 'sidebar']) ?>

<!-- Modal -->
<div class="modal fade" id="addusermodal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="exampleModalLabel">Modal title</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
            <h2 class="mb-4 mt-4"><?= isset($user) ? 'Edit User' : 'Add New User' ?></h2>
            <form method="post" action="<?= $action ?>">
                <div class="mb-3">
                    <label>Name</label>
                    <input type="text" name="name" class="form-control" value="<?= $user['name'] ?? '' ?>" required>
                </div>
                <div class="mb-3">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" value="<?= $user['email'] ?? '' ?>" required>
                </div>
                <div class="mb-3">
                    <label>Password <?= isset($user) ? '(Leave blank to keep current)' : '' ?></label>
                    <input type="password" name="password" class="form-control" <?= isset($user) ? '' : 'required' ?>>
                </div>
                <div class="mb-3">
                    <label>Role</label>
                    <select name="role_id" class="form-select" required>
                        <option value="">Select Role</option>
                        <?php foreach ($roles as $role): ?>
                        <option value="<?= $role['id'] ?>" <?= (isset($user) && $user['role_id'] == $role['id']) ? 'selected' : '' ?>>
                            <?= ucfirst($role['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn-success">Save</button>
            </form>

            <!-- Button trigger modal -->
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">
            Launch demo modal
            </button>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary">Save changes</button>
      </div>
    </div>
  </div>
</div>

<?php layout('footer'); ?>
