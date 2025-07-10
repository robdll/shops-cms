<?php
session_start();


include('../includes/check-auth.php');
include('../includes/db.php');

$email = $_SESSION['email'];
$saldo = null;

// recuperiamo saldo punti legato a utente
$query = "SELECT t.saldo_punti
          FROM tessera t
          JOIN utente u ON t.utente = u.id
          WHERE u.email = $1";

$result = pg_query_params($conn, $query, [$email]);
if ($result && pg_num_rows($result) == 1) {
    $row = pg_fetch_assoc($result);
    $saldo = $row['saldo_punti'] ?? null;
}
?>

<?php include('header.php') ?>

<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card shadow">
      <div class="card-body text-center">
        <h4 class="card-title mb-4">Saldo Tessera Fedeltà</h4>

        <?php if ($saldo === null): ?>
          <div class="alert alert-warning">
            Non risulta associata alcuna tessera al tuo account.
          </div>
        <?php else: ?>
          <p class="fs-4">Hai <strong><?= htmlspecialchars($saldo) ?></strong> punti.</p>

          <?php if ($saldo >= 300): ?>
            <div class="alert alert-success">Puoi ottenere fino al 30% di sconto!</div>
          <?php elseif ($saldo >= 200): ?>
            <div class="alert alert-info">Puoi ottenere fino al 15% di sconto!</div>
          <?php elseif ($saldo >= 100): ?>
            <div class="alert alert-warning">Puoi ottenere fino al 5% di sconto!</div>
          <?php else: ?>
            <div class="alert alert-secondary">Accumula altri punti per ottenere sconti!</div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include('footer.php') ?>
