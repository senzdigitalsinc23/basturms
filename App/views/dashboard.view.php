<h2>User List</h2>
<ul>
    <?php foreach ($users as $user): ?>
        <li><?= htmlspecialchars($user->name) ?> -> <?= htmlspecialchars($user->email) ?></li>
    <?php endforeach; ?>
</ul>