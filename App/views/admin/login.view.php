<?php layout('header') ?>

  <div class="login-card">
    <h4 class="login-title">Login to BasturMS</h4>

    <?php if (!empty($error)): ?>
      <div class="alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form action="/web/login" method="POST" class="needs-validation" novalidate>
      <div class="mb-3 form-group">
        <i class="bi bi-envelope form-icon"></i>
        <input type="email" name="email" class="form-control" placeholder="Email address" required>
      </div>

      <div class="mb-3 form-group">
        <i class="bi bi-lock form-icon"></i>
        <input type="password" name="password" class="form-control" id="password" placeholder="Password" required>
        <i class="bi bi-eye-slash toggle-password" id="togglePassword"></i>
      </div>

      <div class="mb-3 d-flex justify-content-between">
        <div>
          <input type="checkbox" name="remember" id="remember"> <label for="remember">Remember me</label>
        </div>
        <a href="/forgot-password">Forgot Password?</a>
      </div>

      <button type="submit" class="btn btn-primary w-100">Sign In</button>

      <div class="mt-3 text-center">
        <small>Don't have an account? <a href="/register">Register</a></small>
      </div>
    </form>
  </div>

  <?php layout('footer'); remove('_old') ?> 

  <script>
    const toggle = document.getElementById('togglePassword');
    const password = document.getElementById('password');
    toggle.addEventListener('click', () => {
      const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
      password.setAttribute('type', type);
      toggle.classList.toggle('bi-eye');
      toggle.classList.toggle('bi-eye-slash');
    });

