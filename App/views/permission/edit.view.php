<?php layout(['header', 'sidebar', 'navbar']); ?>

<h2 class="mb-4 mt-4">Edit Permission</h2>
<form action="/admin/permissions/update/<?= $permission['id'] ?>" method="POST">
  <input type="text" name="name" value="<?= $permission['name'] ?>" required>
  <input type="text" name="description" value="<?= $permission['description'] ?>" required>
  <button type="submit" class="btn btn-primary">Update</button>
</form>

<?php layout('footer') ?>