<?php
session_start();
if (isset($_SESSION['utente'])) {
  header('Location: dashboard.php');
  exit;
}
?>

<?php include('header.php') ?>

<div class="row justify-content-center">
  <div class="col-md-4">
    <div class="card shadow">
      <div class="card-body">
        <h4 class="card-title mb-4 text-center">Login</h4>
        <form action="login.php" method="post">
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" class="form-control" name="username" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" class="form-control" name="password" required>
          </div>
          <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include('footer.php') ?>
