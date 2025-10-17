<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
</head>
<body>
    <h2>Reset Password</h2>

    <?php if (session('success')): ?>
        <p style="color:green"><?= session('success') ?></p>
    <?php endif; ?>

    <form method="POST" action="/web/forgot-password">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="New Password" required>
        <button type="submit">Reset Password</button>
    </form>

    <p><a href="/web/login">Back to Login</a></p>
</body>
</html>
