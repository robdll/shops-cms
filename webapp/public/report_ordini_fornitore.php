<?php
session_start();

include('../includes/check-auth.php');
include('../includes/check-gestore.php');
include('../includes/db.php');

if (!isset($_GET['fornitore'])) {
    // Mostra lista fornitori
    $result = pg_query($conn, "SELECT partita_iva, indirizzo FROM fornitore ORDER BY partita_iva");
    ?>
    <h2>Storico ordini a fornitori</h2>
    <p>Seleziona un fornitore per vedere i suoi ordini:</p>
    <table border="1">
        <tr><th>Partita IVA</th><th>Indirizzo</th></tr>
        <?php while ($row = pg_fetch_assoc($result)) { ?>
            <tr onclick="window.location='report_ordini_fornitore.php?fornitore=<?php echo urlencode($row['partita_iva']) ?>'">
                <td><?php echo htmlspecialchars($row['partita_iva']) ?></td>
                <td><?php echo htmlspecialchars($row['indirizzo']) ?></td>
            </tr>
        <?php } ?>
    </table>
    <p><a href="report.php">Torna ai report</a></p>
    <style>tr:hover {background:#eee; cursor:pointer;}</style>
    <?php
    exit;
}

// Mostra ordini per un fornitore specifico
$fornitore = $_GET['fornitore'];
$query = "
    SELECT a.prodotto, a.quantita, a.prezzo_unitario, a.data_consegna, n.indirizzo AS negozio
    FROM approvvigionamento a
    JOIN negozio n ON a.negozio = n.id
    WHERE a.fornitore = $1
    ORDER BY a.data_consegna DESC";
$result = pg_query_params($conn, $query, [$fornitore]);
?>

<h2>Ordini del fornitore <?php echo htmlspecialchars($fornitore) ?></h2>
<table border="1">
    <tr><th>Negozio</th><th>Prodotto</th><th>Quantità</th><th>Prezzo unitario</th><th>Data consegna</th></tr>
    <?php while ($row = pg_fetch_assoc($result)) { ?>
        <tr>
            <td><?php echo htmlspecialchars($row['negozio']) ?></td>
            <td><?php echo htmlspecialchars($row['prodotto']) ?></td>
            <td><?php echo htmlspecialchars($row['quantita']) ?></td>
            <td><?php echo htmlspecialchars($row['prezzo_unitario']) ?></td>
            <td><?php echo htmlspecialchars($row['data_consegna']) ?></td>
        </tr>
    <?php } ?>
</table>

<p><a href="report_ordini_fornitore.php">Seleziona un altro fornitore</a></p>
<p><a href="report.php">Torna ai report</a></p>
