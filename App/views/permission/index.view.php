<?php layout(['header', 'sidebar', 'navbar']); ?>

<h2 class="mb-4 mt-4">Permissions</h2>
<a href="/admin/permissions/create" class="btn btn-primary">Add Permission</a>
<table class="table">
  <thead>
    <tr><th>Name</th><th>Description</th><th>Actions</th></tr>
  </thead>
  <tbody>
    <?php foreach ($permissions as $permission): ?>
      <tr>
        <td><?= $permission['name'] ?></td>
        <td><?= $permission['description'] ?? '' ?></td>
        <td>
          <a href="/admin/permissions/edit/<?= $permission['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
          <a href="/admin/permissions/delete/<?= $permission['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this permission?')">Delete</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php layout('footer') ?>