<?php
session_start();

include('../includes/check-auth.php');
include('../includes/check-gestore.php');
include('../includes/db.php');

// gestione inserimento nuovo fornitore
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aggiungi_fornitore'])) {
    $partita_iva = $_POST['partita_iva'];
    $indirizzo = $_POST['indirizzo'];
    pg_query_params($conn,
        "INSERT INTO \"Kalunga\".fornitore (partita_iva, indirizzo, eliminato) VALUES ($1, $2, false)",
        [$partita_iva, $indirizzo]);
}

// gestione selezione fornitore
if (isset($_GET['fornitore'])) {
    $fornitore = $_GET['fornitore'];

    // gestione eliminazione
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['elimina_fornitore'])) {
        pg_query_params($conn,
            "DELETE FROM \"Kalunga\".fornitore_prodotto WHERE fornitore=$1", [$fornitore]);
        pg_query_params($conn,
            "DELETE FROM \"Kalunga\".fornitore WHERE partita_iva=$1", [$fornitore]);
        header('Location: fornitore.php');
        exit;
    }

    // gestione aggiunta prodotto al fornitore
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aggiungi_prodotto'])) {
        $prodotto = intval($_POST['prodotto']);
        $costo = floatval($_POST['costo']);
        $quantita = intval($_POST['quantita']);
        pg_query_params($conn,
            "INSERT INTO \"Kalunga\".fornitore_prodotto (fornitore, prodotto, costo_unitario, disponibilita)
             VALUES ($1, $2, $3, $4)",
            [$fornitore, $prodotto, $costo, $quantita]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rimuovi_prodotto'])) {
        $prodotto_rimuovi = intval($_POST['prodotto_rimuovi']);
        pg_query_params($conn,
            "DELETE FROM fornitore_prodotto WHERE fornitore=$1 AND prodotto=$2",
            [$fornitore, $prodotto_rimuovi]);
    }

    // mostra prodotti del fornitore
    $prodotti = pg_query_params($conn,
        "SELECT p.nome, fp.costo_unitario, fp.disponibilita
         FROM \"Kalunga\".fornitore_prodotto fp
         JOIN \"Kalunga\".prodotto p ON fp.prodotto = p.id
         WHERE fp.fornitore = $1", [$fornitore]);
    ?>
    <h2>Fornitore <?php echo htmlspecialchars($fornitore) ?></h2>
    <table border="1">
        <tr><th>Prodotto</th><th>Costo unitario</th><th>Disponibilità</th></tr>
        <?php while ($p = pg_fetch_assoc($prodotti)) { ?>
            <tr>
                <td><?php echo htmlspecialchars($p['nome']) ?></td>
                <td><?php echo htmlspecialchars($p['costo_unitario']) ?></td>
                <td><?php echo htmlspecialchars($p['disponibilita']) ?></td>
            </tr>
        <?php } ?>
    </table>

    <h3>Aggiungi prodotto a questo fornitore</h3>
    <form method="POST">
        <select name="prodotto" required>
            <?php
            $all = pg_query_params($conn, 
                "SELECT p.id, p.nome
                 FROM \"Kalunga\".prodotto p
                 WHERE NOT EXISTS (
                     SELECT 1 FROM \"Kalunga\".fornitore_prodotto fp
                     WHERE fp.prodotto = p.id AND fp.fornitore = $1
                 )
                 ORDER BY p.nome",
                [$fornitore]
            );
            while ($r = pg_fetch_assoc($all)) {
                echo '<option value="'.htmlspecialchars($r['id']).'">'.htmlspecialchars($r['nome']).'</option>';
            }
            ?>
        </select>
        <input type="number" step="0.01" name="costo" placeholder="Costo unitario" required>
        <input type="number" name="quantita" placeholder="Quantità" required>
        <button type="submit" name="aggiungi_prodotto">Aggiungi prodotto</button>
    </form>

    <h3>Rimuovi prodotto da questo fornitore</h3>
    <form method="POST">
        <select name="prodotto_rimuovi" required>
            <?php
            $associati = pg_query_params($conn, 
                "SELECT p.id, p.nome
                FROM fornitore_prodotto fp
                JOIN prodotto p ON p.id = fp.prodotto
                WHERE fp.fornitore = $1
                ORDER BY p.nome", 
                [$fornitore]);
            while ($r = pg_fetch_assoc($associati)) {
                echo '<option value="'.htmlspecialchars($r['id']).'">'.htmlspecialchars($r['nome']).'</option>';
            }
            ?>
        </select>
        <button type="submit" name="rimuovi_prodotto">Rimuovi prodotto</button>
    </form>


    <form method="POST" style="margin-top:10px;">
        <button type="submit" name="elimina_fornitore" onclick="return confirm('Sei sicuro di voler eliminare questo fornitore e tutti i prodotti associati?')">Elimina fornitore</button>
    </form>

    <p><a href="fornitore.php">Torna ai fornitori</a></p>
    <p><a href="dashboard.php">Torna alla dashboard</a></p>
    <?php
    exit;
}

// lista fornitori
$result = pg_query($conn, "SELECT partita_iva, indirizzo FROM \"Kalunga\".fornitore ORDER BY partita_iva");
?>
<h2>Fornitori</h2>
<table border="1">
    <tr><th>Partita IVA</th><th>Indirizzo</th></tr>
    <?php while ($row = pg_fetch_assoc($result)) { ?>
        <tr onclick="window.location='fornitore.php?fornitore=<?php echo urlencode($row['partita_iva']) ?>'">
            <td><?php echo htmlspecialchars($row['partita_iva']) ?></td>
            <td><?php echo htmlspecialchars($row['indirizzo']) ?></td>
        </tr>
    <?php } ?>
</table>
<style>tr:hover {background:#eee; cursor:pointer;}</style>

<h3>Aggiungi nuovo fornitore</h3>
<form method="POST">
    <input type="text" name="partita_iva" placeholder="Partita IVA" required>
    <input type="text" name="indirizzo" placeholder="Indirizzo" required>
    <button type="submit" name="aggiungi_fornitore">Aggiungi fornitore</button>
</form>

<p><a href="dashboard.php">Torna alla dashboard</a></p>
