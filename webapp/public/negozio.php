<?php
session_start();
if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit;
}

include('../includes/db.php');

$email = $_SESSION['email'];

// Trova utente + tessera
$query = "SELECT u.id AS utente_id, t.id AS tessera_id, COALESCE(t.saldo_punti,0) AS saldo_punti
          FROM utente u LEFT JOIN tessera t ON t.utente = u.id
          WHERE u.email = $1";
$result = pg_query_params($conn, $query, [$email]);
$user = pg_fetch_assoc($result);

// Se mostra lista negozi
if (!isset($_GET['id'])) {
    $result = pg_query($conn, "SELECT id, indirizzo, orario_apertura, orario_chiusura FROM negozio WHERE eliminato = false");
    ?>
    <h2>Lista negozi</h2>
    <table border="1">
        <tr><th>ID</th><th>Indirizzo</th><th>Apertura</th><th>Chiusura</th></tr>
        <?php while ($r = pg_fetch_assoc($result)) { ?>
            <tr onclick="window.location='negozio.php?id=<?php echo $r['id'] ?>'">
                <td><?php echo htmlspecialchars($r['id']) ?></td>
                <td><?php echo htmlspecialchars($r['indirizzo']) ?></td>
                <td><?php echo htmlspecialchars($r['orario_apertura']) ?></td>
                <td><?php echo htmlspecialchars($r['orario_chiusura']) ?></td>
            </tr>
        <?php } ?>
    </table>
    <p><a href="dashboard.php">Torna alla dashboard</a></p>
    <style>tr:hover {background:#eee; cursor:pointer;}</style>
    <?php exit;
}

// Se mostra prodotti del negozio selezionato
$negozio_id = intval($_GET['id']);
$query = "SELECT id, indirizzo FROM negozio WHERE id=$1 AND eliminato=false";
$result = pg_query_params($conn, $query, [$negozio_id]);
if (pg_num_rows($result) != 1) {
    echo "<p>Negozio non trovato o eliminato.</p>";
    exit;
}
$negozio = pg_fetch_assoc($result);

$prodotti = pg_query_params($conn,
    "SELECT p.id, p.nome, pn.prezzo_vendita
     FROM prodotto_negozio pn JOIN prodotto p ON pn.prodotto = p.id
     WHERE pn.negozio = $1", [$negozio_id]);
?>
<h2>Negozio <?php echo htmlspecialchars($negozio['indirizzo']) ?></h2>
<form action="compra.php" method="POST">
    <input type="hidden" name="negozio_id" value="<?php echo $negozio_id ?>">
    <table border="1">
        <tr><th>Prodotto</th><th>Prezzo</th><th>Quantità</th></tr>
        <?php while ($p = pg_fetch_assoc($prodotti)) { ?>
            <tr>
                <td><?php echo htmlspecialchars($p['nome']) ?></td>
                <td><?php echo htmlspecialchars($p['prezzo_vendita']) ?></td>
                <td><input type="number" name="quantita[<?php echo $p['id'] ?>]" value="0" min="0"></td>
            </tr>
        <?php } ?>
    </table>
    <label>Sconto:</label>
    <select name="sconto">
        <option value="0">Nessuno</option>
        <?php if ($user['saldo_punti'] >= 100) echo '<option value="5">5%</option>'; ?>
        <?php if ($user['saldo_punti'] >= 200) echo '<option value="15">15%</option>'; ?>
        <?php if ($user['saldo_punti'] >= 300) echo '<option value="30">30%</option>'; ?>
    </select>
    <button type="submit">Acquista</button>
</form>
<p><a href="negozio.php">Torna ai negozi</a></p>
