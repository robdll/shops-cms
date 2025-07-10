<?php
session_start();

include('../includes/check-auth.php');
include('../includes/db.php');

$scontrino_id = $_GET['id'] ?? null;

// Carica scontrino
$query = "SELECT s.*, n.indirizzo AS negozio_indirizzo
          FROM scontrino s
          JOIN negozio n ON s.negozio = n.id
          WHERE s.id = $1";
$result = pg_query_params($conn, $query, [$scontrino_id]);
$scontrino = pg_fetch_assoc($result);

if (!$scontrino) {
    echo "Scontrino non trovato.";
    exit;
}

// Carica prodotti
$prodotti = pg_query_params($conn,
    "SELECT p.nome, sp.quantita, sp.prezzo_unitario
     FROM scontrino_prodotto sp
     JOIN prodotto p ON sp.prodotto = p.id
     WHERE sp.scontrino = $1", [$scontrino_id]);
?>

<?php include('header.php') ?>

<h2>Dettaglio Scontrino #<?= htmlspecialchars($scontrino_id) ?></h2>
<p><strong>Data:</strong> <?= htmlspecialchars($scontrino['data_acquisto']) ?></p>
<p><strong>Negozio:</strong> <?= htmlspecialchars($scontrino['negozio_indirizzo']) ?></p>

<h4 class="mt-4">Prodotti acquistati</h4>
<table class="table table-striped">
  <thead>
    <tr>
      <th>Prodotto</th>
      <th>Quantità</th>
      <th>Prezzo unitario</th>
      <th>Subtotale</th>
    </tr>
  </thead>
  <tbody>
    <?php while ($p = pg_fetch_assoc($prodotti)) { ?>
      <tr>
        <td><?= htmlspecialchars($p['nome']) ?></td>
        <td><?= htmlspecialchars($p['quantita']) ?></td>
        <td><?= htmlspecialchars($p['prezzo_unitario']) ?> €</td>
        <td><?= number_format($p['quantita'] * $p['prezzo_unitario'], 2) ?> €</td>
      </tr>
    <?php } ?>
  </tbody>
</table>

<p class="fs-4 mt-3"><strong>Totale pagato:</strong> <?= number_format($scontrino['totale_pagato'], 2) ?> €</p>

<p><a href="dashboard.php" class="btn btn-secondary mt-3">Torna alla dashboard</a></p>

<?php include('footer.php') ?>
