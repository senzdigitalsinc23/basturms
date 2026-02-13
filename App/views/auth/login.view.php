<style>
  .content-area {
  display: block;
}
</style>


<div class="login-container">
  <div class="login-card">
    <h4 class="login-title">Login to BasturMS</h4>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div id="alert-box" class="alert d-none"></div>

    <form method="POST" id="loginForm" class="needs-validation" novalidate>
        <?php if (isset($errors)): ?>
            <?php foreach($errors as $error) : ?>
                <?php if(is_array($error)) :?>
                <?php foreach($error as $err) : ?>
                    <div class="alert alert-danger"><?= $err ?></div>
                <?php endforeach ?>
                <?php else :?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif ?>
            <?php endforeach ?>
        <?php endif; ?>
      <div class="mb-3 form-group">
        <i class="bi bi-envelope form-icon"></i>
        <input type="email" name="email" class="form-control" value="<?=old('email')?>" placeholder="Email address" required>
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
        <small>Don't have an account? <a href="/web/register">Register</a></small>
      </div>
    </form>
  </div>
</div>

<?php remove('_old') ?> 

<script>
    const toggle = document.getElementById('togglePassword');
    const password = document.getElementById('password');
    toggle.addEventListener('click', () => {
      const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
      password.setAttribute('type', type);
      toggle.classList.toggle('bi-eye');
      toggle.classList.toggle('bi-eye-slash');
    });

    
document.getElementById("loginForm").addEventListener("submit", function(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);

    fetch("/api/login?apiKey=devKey123", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {console.log(data);//return;
    
    
        const alertBox = document.getElementById("alert-box");
        alertBox.classList.remove("d-none");
        alertBox.classList.add("alert");
        
        if (data.success) {
            window.location.href = data.redirect;
        } else {
            alertBox.classList.add("alert-danger");
            alertBox.innerText = data.message;
        }
    })
    .catch(err => {
        console.error(err);
    });
});
  </script>
  <script src="/assets/js/frontend-validation.js"></script>
