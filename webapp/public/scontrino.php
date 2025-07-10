<?php
session_start();
if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit;
}

include('../includes/db.php');

$scontrino_id = intval($_GET['id']);
$sconto_applicato = false;

// recupera scontrino + negozio
$query = "SELECT s.id, s.data_acquisto, s.sconto_percentuale, s.totale_pagato, s.tessera, n.id AS negozio_id
          FROM scontrino s
          JOIN negozio n ON s.negozio = n.id
          WHERE s.id = $1";
$result = pg_query_params($conn, $query, array($scontrino_id));
$scontrino = pg_fetch_assoc($result);

// recupera saldo tessera
$saldo = 0;
if ($scontrino['tessera']) {
    $query = "SELECT saldo_punti FROM tessera WHERE id = $1";
    $result = pg_query_params($conn, $query, array($scontrino['tessera']));
    $row = pg_fetch_assoc($result);
    $saldo = $row['saldo_punti'];
}

// applica sconto solo al submit POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $perc_sconto = intval($_POST['sconto']);
    $query = "SELECT \"Kalunga\".applica_sconto_scontrino($1::integer, $2::integer)";
    pg_query_params($conn, $query, array($scontrino_id, $perc_sconto));
    $sconto_applicato = true;

    // ricarica scontrino aggiornato
    $query = "SELECT s.sconto_percentuale, s.totale_pagato FROM scontrino s WHERE s.id = $1";
    $result = pg_query_params($conn, $query, array($scontrino_id));
    $scontrino = array_merge($scontrino, pg_fetch_assoc($result));

    // aggiorna saldo tessera
    if ($scontrino['tessera']) {
        $query = "SELECT saldo_punti FROM tessera WHERE id = $1";
        $result = pg_query_params($conn, $query, array($scontrino['tessera']));
        $row = pg_fetch_assoc($result);
        $saldo = $row['saldo_punti'];
    }
}
?>
<h2>Scontrino #<?php echo htmlspecialchars($scontrino['id']) ?></h2>
<p>Data: <?php echo htmlspecialchars($scontrino['data_acquisto']) ?></p>
<p>Negozio: <?php echo 'Negozio ' . htmlspecialchars($scontrino['negozio_id']) ?></p>

<p>Sconto applicato: <?php echo htmlspecialchars($scontrino['sconto_percentuale']) ?>%</p>
<p>Totale pagato: € <?php echo htmlspecialchars($scontrino['totale_pagato']) ?></p>

<?php if (!$sconto_applicato) { ?>
    <form method="POST">
        <label>Applica sconto:</label>
        <select name="sconto">
            <option value="0">Nessuno</option>
            <?php if ($saldo >= 100) { ?>
                <option value="5">5%</option>
            <?php } ?>
            <?php if ($saldo >= 200) { ?>
                <option value="15">15%</option>
            <?php } ?>
            <?php if ($saldo >= 300) { ?>
                <option value="30">30%</option>
            <?php } ?>
        </select>
        <button type="submit">Applica sconto</button>
    </form>
<?php } else { ?>
    <p>Saldo punti aggiornato: <?php echo htmlspecialchars($saldo) ?></p>
<?php } ?>

<p><a href="dashboard.php">Torna alla dashboard</a></p>
