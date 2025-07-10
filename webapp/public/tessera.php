<?php
session_start();
if (!isset($_SESSION['email'])) {
    header('Location: index.php');
    exit;
}

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
    $saldo = $row['saldo_punti'];
}
?>

<?php include('header.php') ?>

<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card shadow">
      <div class="card-body text-center">
        <h4 class="card-title mb-4">Saldo Tessera Fedeltà</h4>

        <?php if ($saldo !== null): ?>
          <p class="fs-4">Hai <strong><?= htmlspecialchars($saldo) ?></strong> punti sulla tua tessera.</p>
          <?php if ($saldo >= 300): ?>
            <div class="alert alert-success mt-3">Complimenti! Puoi ottenere fino al 30% di sconto!</div>
          <?php elseif ($saldo >= 200): ?>
            <div class="alert alert-info mt-3">Puoi ottenere fino al 15% di sconto!</div>
          <?php elseif ($saldo >= 100): ?>
            <div class="alert alert-warning mt-3">Puoi ottenere fino al 5% di sconto!</div>
          <?php else: ?>
            <div class="alert alert-secondary mt-3">Accumula ancora punti per ottenere sconti!</div>
          <?php endif; ?>
        <?php else: ?>
          <div class="alert alert-danger">Non risulta associata alcuna tessera al tuo account.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include('footer.php') ?>
