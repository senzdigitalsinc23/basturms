<?php layout(['header', 'sidebar', 'navbar']); ?>

<h2 class="mb-4 mt-4">Add Permission</h2>
<form action="/admin/permissions/store" method="POST">
  <input type="text" name="name" placeholder="Permission Name" required>
  <input type="text" name="description" placeholder="Description" required>
  <button type="submit" class="btn btn-success">Save</button>
</form>

<?php layout('footer') ?>