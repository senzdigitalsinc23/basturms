<!-- views/admin/students/index.php -->
<?php ob_start(); layout('header', 'navbar', 'sidebar')?>
<h2 class="mb-4">All Students</h2>
<a href="/web/admin/students/create" class="btn btn-primary mb-3">Add Student</a>
<table class="table table-bordered table-hover">
    <thead>
        <tr>
            <th>#ID</th><th>Name</th><th>Gender</th><th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($students as $student): ?>
        <tr>
            <td><?= $student['id'] ?></td>
            <td><?= $student['name'] ?></td>
            <td><?= $student['gender'] ?></td>
            <td>
                <a href="/admin/students/<?= $student['id'] ?>/edit" class="btn btn-sm btn-warning">Edit</a>
                <a href="/admin/students/<?= $student['id'] ?>/delete" class="btn btn-sm btn-danger">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php layout('footer'); $content = ob_get_clean();?>

