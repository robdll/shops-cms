<?php
session_start();
if (!isset($_SESSION['email']) || $_SESSION['tipo'] !== 'gestore') {
    header('Location: login.php');
    exit;
}

include('../includes/db.php');

if (!isset($_GET['negozio'])) {
    // Mostra lista negozi
    $result = pg_query($conn, "SELECT id, indirizzo FROM \"Kalunga\".negozio ORDER BY id");
    ?>
    <h2>Lista tesserati per negozio</h2>
    <p>Seleziona un negozio per vedere i suoi tesserati:</p>
    <table border="1">
        <tr><th>ID</th><th>Indirizzo</th></tr>
        <?php while ($row = pg_fetch_assoc($result)) { ?>
            <tr onclick="window.location='report_tesserati.php?negozio=<?php echo urlencode($row['id']) ?>'">
                <td><?php echo htmlspecialchars($row['id']) ?></td>
                <td><?php echo htmlspecialchars($row['indirizzo']) ?></td>
            </tr>
        <?php } ?>
    </table>
    <p><a href="report.php">Torna ai report</a></p>
    <style>tr:hover {background:#eee; cursor:pointer;}</style>
    <?php
    exit;
}

// Mostra tesserati di un negozio specifico
$negozio = intval($_GET['negozio']);
$query = "
    SELECT t.tessera_id, t.data_rilascio, t.nome, t.cognome, t.codice_fiscale
    FROM \"Kalunga\".lista_tesserati_negozio t
    WHERE t.negozio = $1
    ORDER BY t.data_rilascio DESC";
$result = pg_query_params($conn, $query, [$negozio]);
?>

<h2>Tesserati del negozio <?php echo htmlspecialchars($negozio) ?></h2>
<table border="1">
    <tr><th>Tessera</th><th>Data rilascio</th><th>Nome</th><th>Cognome</th><th>Codice fiscale</th></tr>
    <?php while ($row = pg_fetch_assoc($result)) { ?>
        <tr>
            <td><?php echo htmlspecialchars($row['tessera_id']) ?></td>
            <td><?php echo htmlspecialchars($row['data_rilascio']) ?></td>
            <td><?php echo htmlspecialchars($row['nome']) ?></td>
            <td><?php echo htmlspecialchars($row['cognome']) ?></td>
            <td><?php echo htmlspecialchars($row['codice_fiscale']) ?></td>
        </tr>
    <?php } ?>
</table>

<p><a href="report_tesserati.php">Seleziona un altro negozio</a></p>
<p><a href="report.php">Torna ai report</a></p>
