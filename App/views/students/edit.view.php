<!DOCTYPE html>
<html>
<head>
    <title>Edit Student</title>
</head>
<body>
    <h1>Edit Student</h1>
    <a href="/students">Back to List</a>

    <?php if (!empty($errors)): ?>
        <div style="color:red;">
            <ul>
                <?php foreach ($errors as $field => $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="/students/<?= htmlspecialchars($student['id']) ?>/update" method="POST">
        <label>First Name: <input type="text" name="first_name" value="<?= htmlspecialchars($student['first_name']) ?>"></label><br>
        <label>Last Name: <input type="text" name="last_name" value="<?= htmlspecialchars($student['last_name']) ?>"></label><br>
        <label>Email: <input type="email" name="email" value="<?= htmlspecialchars($student['email']) ?>"></label><br>
        <!-- Add other fields as needed -->
        <button type="submit">Update</button>
    </form>
</body>
</html>
