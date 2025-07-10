<?php
session_start();

include('../includes/check-auth.php');
include('../includes/check-gestore.php');
include('../includes/db.php');

$result = pg_query($conn, "SELECT * FROM clienti_con_piu_di_300_punti ORDER BY saldo_punti DESC");
?>

<h2>Clienti con più di 300 punti</h2>
<table border="1">
    <tr><th>Tessera</th><th>Saldo punti</th><th>Data rilascio</th><th>Cliente</th><th>Codice fiscale</th></tr>
    <?php while ($row = pg_fetch_assoc($result)) { ?>
        <tr>
            <td><?php echo htmlspecialchars($row['tessera_id']) ?></td>
            <td><?php echo htmlspecialchars($row['saldo_punti']) ?></td>
            <td><?php echo htmlspecialchars($row['data_rilascio']) ?></td>
            <td><?php echo htmlspecialchars($row['nome'] . ' ' . $row['cognome']) ?></td>
            <td><?php echo htmlspecialchars($row['codice_fiscale']) ?></td>
        </tr>
    <?php } ?>
</table>

<p><a href="report.php">Torna ai report</a></p>
