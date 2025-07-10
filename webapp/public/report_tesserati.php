<?php
session_start();
if (!isset($_SESSION['email']) || $_SESSION['tipo'] !== 'gestore') {
    header('Location: login.php');
    exit;
}

include('../includes/db.php');

$result = pg_query($conn, "SELECT * FROM lista_tesserati_negozio ORDER BY negozio, data_rilascio");
?>

<h2>Lista tesserati per negozio</h2>
<table border="1">
    <tr><th>Negozio</th><th>Tessera</th><th>Data rilascio</th><th>Utente</th><th>Codice fiscale</th></tr>
    <?php while ($row = pg_fetch_assoc($result)) { ?>
        <tr>
            <td><?php echo htmlspecialchars($row['negozio']) ?></td>
            <td><?php echo htmlspecialchars($row['tessera_id']) ?></td>
            <td><?php echo htmlspecialchars($row['data_rilascio']) ?></td>
            <td><?php echo htmlspecialchars($row['nome'] . ' ' . $row['cognome']) ?></td>
            <td><?php echo htmlspecialchars($row['codice_fiscale']) ?></td>
        </tr>
    <?php } ?>
</table>

<p><a href="report.php">Torna ai report</a></p>
