<?php
session_start();
include('../includes/db.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $query = "SELECT id, nome, tipo FROM utente WHERE email=$1 AND password=$2";
    $result = pg_query_params($conn, $query, [$username, $password]);

    if ($result && pg_num_rows($result) === 1) {
        $row = pg_fetch_assoc($result);
        $_SESSION['email'] = $username;
        $_SESSION['nome'] = $row['nome'];
        $_SESSION['tipo'] = $row['tipo'];
        header('Location: dashboard.php');
        exit;
    } else {
        $error = "Credenziali errate. Riprova.";
    }
}
?>

<?php include('header.php') ?>

<div class="row justify-content-center">
  <div class="col-md-4">
    <div class="card shadow">
      <div class="card-body">
        <h4 class="card-title mb-4 text-center">Login</h4>

        <?php if ($error): ?>
          <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
          <div class="mb-3">
            <label class="form-label">Email</label>
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
