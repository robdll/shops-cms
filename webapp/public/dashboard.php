<?php
session_start();
if (!isset($_SESSION['email'])) {
    header('Location: index.php');
    exit;
}
$nome = htmlspecialchars($_SESSION['nome']);
$tipo = $_SESSION['tipo'];
?>

<?php include('header.php') ?>

<h2 class="mb-4">Benvenuto, <?= $nome ?></h2>

<div class="row">
  <?php if ($tipo === 'gestore'): ?>
    <div class="col-md-4">
      <div class="card mb-4 shadow">
        <div class="card-body">
          <h5 class="card-title">Acquista Prodotti</h5>
          <a href="compra.php" class="btn btn-primary">Vai al Catalogo</a>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card mb-4 shadow">
        <div class="card-body">
          <h5 class="card-title">Gestisci Fornitori</h5>
          <a href="fornitore.php" class="btn btn-primary">Vai ai Fornitori</a>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card mb-4 shadow">
        <div class="card-body">
          <h5 class="card-title">Approvvigionamento</h5>
          <a href="approvvigionamento.php" class="btn btn-primary">Effettua ordini</a>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card mb-4 shadow">
        <div class="card-body">
          <h5 class="card-title">Report</h5>
          <a href="report.php" class="btn btn-primary">Visualizza Report</a>
        </div>
      </div>
    </div>
  <?php else: ?>
    <div class="col-md-4">
      <div class="card mb-4 shadow">
        <div class="card-body">
          <h5 class="card-title">Visualizza Negozi</h5>
          <a href="negozio.php" class="btn btn-primary">Vai ai Negozi</a>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card mb-4 shadow">
        <div class="card-body">
          <h5 class="card-title">Saldo Tessera</h5>
          <a href="tessera.php" class="btn btn-primary">Visualizza Saldo</a>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- comune a tutti -->
  <div class="col-md-4">
    <div class="card mb-4 shadow">
      <div class="card-body">
        <h5 class="card-title">Modifica Password</h5>
        <a href="modifica-password.php" class="btn btn-primary">Cambia Password</a>
      </div>
    </div>
  </div>
</div>

<?php include('footer.php') ?>
