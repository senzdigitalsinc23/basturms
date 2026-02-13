<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
</head>
<body>
    <h2>Register</h2>

    <?php if (session('errors')): ?>
        <ul style="color:red">
            <?php foreach (session('errors') as $error): ?>
                <li><?= $error ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="POST" action="/web/register">
        <div><?=$errors['duplicate'] ?? ''?></div>

        <div>
            <?=$invalidData['exist'] ?? '' ?>
            <?=$data = $invalidData ?? "" ?>
            <?php if(is_array($data)) : ?>
            <?php foreach($invalidData as $errors): ?>
                <?php if(is_array($errors)) :?>
                <?php foreach($errors as $error): ?>
                    <div style="color:red"><?=$error ?? ''?></div>
                <?php endforeach ?>
                <?php endif ?>
            <?php endforeach ?>
            <?php endif ?>
        </div>
        <input type="text" name="role_id" placeholder="Role ID" value="<?= old('role_id') ?>" required>
        <input type="text" name="name" placeholder="Full Name" value="<?= old('name') ?>" required>
        <input type="email" name="email" placeholder="Email" value="<?= old('email') ?>" required>
        <input type="password" name="password" placeholder="Password" >
        <button type="submit">Register</button>
    </form>

    <p>Already have an account? <a href="/web/login">Login here</a></p>
</body>
</html>
