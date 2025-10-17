<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
</head>
<body>
    <h2>Forgot Password</h2>

    <?php if (session('success')): ?>
        <p style="color:green"><?= session('success') ?></p>
    <?php endif; ?>

    <form method="POST" action="/web/forgot-password">
        <input type="email" name="email" placeholder="Your email" required>
        <button type="submit">Send Reset Link</button>
    </form>

    <p><a href="/login">Back to Login</a></p>
</body>
</html>
