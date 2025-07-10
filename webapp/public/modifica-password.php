<?php
session_start();

include('../includes/check-auth.php');
include('../includes/db.php');

$messaggio = '';
$errore = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_SESSION['email'];
    $old_password = trim($_POST['old_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($new_password !== $confirm_password) {
        $errore = "Le nuove password non coincidono.";
    } else {
        // Verifica password attuale
        $query = "SELECT * FROM utente WHERE email = $1 AND password = $2";
        $result = pg_query_params($conn, $query, [$email, $old_password]);

        if ($result && pg_num_rows($result) === 1) {
            // Aggiorna password
            $update = "UPDATE utente SET password = $1 WHERE email = $2";
            $res = pg_query_params($conn, $update, [$new_password, $email]);
            if ($res) {
                $messaggio = "Password aggiornata con successo!";
            } else {
                $errore = "Errore durante l'aggiornamento della password.";
            }
        } else {
            $errore = "Password attuale errata.";
        }
    }
}
?>

<?php include('header.php') ?>

<div class="row justify-content-center">
  <div class="col-md-4">
    <div class="card shadow">
      <div class="card-body">
        <h4 class="card-title mb-4 text-center">Modifica Password</h4>

        <?php if ($messaggio): ?>
          <div class="alert alert-success"><?= htmlspecialchars($messaggio) ?></div>
        <?php endif; ?>
        <?php if ($errore): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($errore) ?></div>
        <?php endif; ?>

        <form method="post">
          <div class="mb-3">
            <label class="form-label">Password Attuale</label>
            <input type="password" class="form-control" name="old_password" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Nuova Password</label>
            <input type="password" class="form-control" name="new_password" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Conferma Nuova Password</label>
            <input type="password" class="form-control" name="confirm_password" required>
          </div>
          <button type="submit" class="btn btn-primary w-100">Aggiorna Password</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include('footer.php') ?>
