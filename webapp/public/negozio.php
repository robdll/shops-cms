<?php
session_start();
if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit;
}

include('../includes/db.php');

// Se è stato passato un id, mostriamo il dettaglio
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $query = "SELECT id, indirizzo, orario_apertura, orario_chiusura
              FROM negozio
              WHERE id = $1 AND eliminato = false";
    $result = pg_query_params($conn, $query, array($id));

    if ($result && pg_num_rows($result) == 1) {
        $negozio = pg_fetch_assoc($result);
        ?>
        <h2>Dettaglio Negozio <?php echo htmlspecialchars($negozio['id']) ?></h2>
        <p><strong>Indirizzo:</strong> <?php echo htmlspecialchars($negozio['indirizzo']) ?></p>
        <p><strong>Apertura:</strong> <?php echo htmlspecialchars($negozio['orario_apertura']) ?></p>
        <p><strong>Chiusura:</strong> <?php echo htmlspecialchars($negozio['orario_chiusura']) ?></p>
        <p><a href="negozio.php">Torna alla lista negozi</a></p>
        <?php
    } else {
        echo "<p>Negozio non trovato o eliminato.</p>";
        echo '<p><a href="negozio.php">Torna alla lista negozi</a></p>';
    }
    exit;
}

// Altrimenti mostriamo la lista
$query = "SELECT id, orario_apertura, orario_chiusura FROM negozio WHERE eliminato = false";
$result = pg_query($conn, $query);
?>
<h2>Lista negozi</h2>
<table border="1" cellpadding="5" cellspacing="0">
    <tr>
        <th>Nome</th>
        <th>Apertura</th>
        <th>Chiusura</th>
    </tr>
    <?php while ($row = pg_fetch_assoc($result)) { ?>
        <tr onclick="window.location='negozio.php?id=<?php echo $row['id'] ?>'">
            <td><?php echo 'Negozio ' . htmlspecialchars($row['id']) ?></td>
            <td><?php echo htmlspecialchars($row['orario_apertura']) ?></td>
            <td><?php echo htmlspecialchars($row['orario_chiusura']) ?></td>
        </tr>
    <?php } ?>
</table>
<p><a href="dashboard.php">Torna alla dashboard</a></p>

<style>
tr:hover { background-color: #eee; cursor: pointer; }
</style>
