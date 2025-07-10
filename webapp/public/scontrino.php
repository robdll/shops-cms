<?php
session_start();
if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit;
}

include('../includes/db.php');

$scontrino_id = intval($_GET['id']);

// Carica scontrino
$query = "SELECT s.*, n.indirizzo AS negozio_indirizzo
          FROM scontrino s
          JOIN negozio n ON s.negozio = n.id
          WHERE s.id = $1";
$result = pg_query_params($conn, $query, [$scontrino_id]);
$scontrino = pg_fetch_assoc($result);

// Carica prodotti
$prodotti = pg_query_params($conn,
    "SELECT p.nome, sp.quantita, sp.prezzo_unitario
     FROM scontrino_prodotto sp
     JOIN prodotto p ON sp.prodotto = p.id
     WHERE sp.scontrino = $1", [$scontrino_id]);
?>
<h2>Scontrino #<?php echo $scontrino_id ?></h2>
<p><strong>Data:</strong> <?php echo htmlspecialchars($scontrino['data_acquisto']) ?></p>
<p><strong>Negozio:</strong> <?php echo htmlspecialchars($scontrino['negozio_indirizzo']) ?></p>
<table border="1">
    <tr><th>Prodotto</th><th>Q.tà</th><th>Prezzo unitario</th></tr>
    <?php while ($p = pg_fetch_assoc($prodotti)) { ?>
        <tr>
            <td><?php echo htmlspecialchars($p['nome']) ?></td>
            <td><?php echo htmlspecialchars($p['quantita']) ?></td>
            <td><?php echo htmlspecialchars($p['prezzo_unitario']) ?></td>
        </tr>
    <?php } ?>
</table>
<p><strong>Sconto applicato:</strong> <?php echo $scontrino['sconto_percentuale'] ?>%</p>
<p><strong>Totale pagato:</strong> <?php echo $scontrino['totale_pagato'] ?> €</p>
<p><a href="negozio.php">Torna ai negozi</a></p>
