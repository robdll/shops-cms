<?php
session_start();

include('../includes/check-auth.php');

$nome = htmlspecialchars($_SESSION['nome']);
$tipo = $_SESSION['tipo'];
?>

<?php include('header.php') ?>

<h2 class="mb-4">Benvenuto <?= $nome ?></h2>

<div class="row">
  <div class="col-md-4">
    <div class="card mb-4 shadow">
      <div class="card-body">
        <h5 class="card-title">Acquisti</h5>
        <a href="negozio.php" class="btn btn-primary">Vai ai Negozi</a>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card mb-4 shadow">
      <div class="card-body">
        <h5 class="card-title">Tessera Fedeltà</h5>
        <a href="tessera.php" class="btn btn-primary">Visualizza Saldo</a>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card mb-4 shadow">
      <div class="card-body">
        <h5 class="card-title">Configurazioni</h5>
        <a href="modifica-password.php" class="btn btn-primary">Cambia Password</a>
      </div>
    </div>
  </div>
</div>

<?php if ($tipo === 'gestore'): ?>
<h4 class="mb-3">Gestione</h4>
<div class="row">
  <div class="col-md-4">
    <div class="card mb-4 shadow">
      <div class="card-body">
        <h5 class="card-title">Utenti</h5>
        <a href="utente.php" class="btn btn-info">Vai ai Clienti</a>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card mb-4 shadow">
      <div class="card-body">
        <h5 class="card-title">Negozi</h5>
        <a href="gestione-negozio.php" class="btn btn-info">Vai alla Lista Negozi</a>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card mb-4 shadow">
      <div class="card-body">
        <h5 class="card-title">Prodotti</h5>
        <a href="prodotto.php" class="btn btn-info">Vai al Catalogo</a>
      </div>
    </div>
  </div>
</div>
<div class="row">
  <div class="col-md-4">
    <div class="card mb-4 shadow">
      <div class="card-body">
        <h5 class="card-title">Fornitori</h5>
        <a href="fornitore.php" class="btn btn-info">Vai ai Fornitori</a>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card mb-4 shadow">
      <div class="card-body">
        <h5 class="card-title">Ordini</h5>
        <a href="approvvigionamento.php" class="btn btn-info">Effettua Ordini</a>
      </div>
    </div>
  </div>
</div>

<h4 class="mb-3">Reportistica</h4>
<div class="row">
  <div class="col-md-4">
    <div class="card mb-4 shadow">
      <div class="card-body">
        <h5 class="card-title">Tesserati</h5>
        <a href="report_tesserati.php" class="btn btn-success">Visualizza Utenti Tesserati</a>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card mb-4 shadow">
      <div class="card-body">
        <h5 class="card-title">Utenti Affezionati</h5>
        <a href="report_clienti_300.php" class="btn btn-success">Visualizza tessere con 300 Punti</a>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card mb-4 shadow">
      <div class="card-body">
        <h5 class="card-title">Storico ordini</h5>
        <a href="report_ordini_fornitore.php" class="btn btn-success">Visualizza ordini effettuati</a>
      </div>
    </div>
  </div>
</div>

<?php endif; ?>

<?php include('footer.php') ?>
