<?php
session_start();
if (!isset($_SESSION['email']) || !isset($_SESSION['tipo']) || !isset($_SESSION['nome'])) {
    header('Location: login.php');
    exit;
}

include('../includes/db.php');

$tipo = $_SESSION['tipo'];
$email = $_SESSION['email'];

// gestione POST inserimento/modifica/eliminazione negozio
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tipo === 'gestore') {
    if (isset($_POST['inserisci_negozio'])) {
        $indirizzo = $_POST['indirizzo'];
        $apertura = $_POST['apertura'];
        $chiusura = $_POST['chiusura'];

        // prendi ID gestore corrente
        $res = pg_query_params($conn, "SELECT id FROM utente WHERE email=$1", [$email]);
        $gestore_id = pg_fetch_result($res, 0, 0);

        pg_query_params($conn,
            "INSERT INTO negozio (indirizzo, orario_apertura, orario_chiusura, responsabile, eliminato)
             VALUES ($1, $2, $3, $4, false)",
            [$indirizzo, $apertura, $chiusura, $gestore_id]);
    }

    if (isset($_POST['modifica_negozio'])) {
        $negozio_id = intval($_POST['negozio_id']);
        $indirizzo = $_POST['indirizzo'];
        $apertura = $_POST['apertura'];
        $chiusura = $_POST['chiusura'];
        pg_query_params($conn,
            "UPDATE negozio SET indirizzo=$1, orario_apertura=$2, orario_chiusura=$3 WHERE id=$4",
            [$indirizzo, $apertura, $chiusura, $negozio_id]);
    }

    if (isset($_POST['elimina_negozio'])) {
        $negozio_id = intval($_POST['negozio_id']);
        pg_query_params($conn,
            "UPDATE negozio SET eliminato=true WHERE id=$1",
            [$negozio_id]);
    }

    if (isset($_POST['aggiungi_prodotto'])) {
        $negozio_id = intval($_POST['negozio_id']);
        $prodotto = intval($_POST['prodotto']);
        $prezzo = floatval($_POST['prezzo']);
        pg_query_params($conn,
            "INSERT INTO prodotto_negozio (negozio, prodotto, prezzo_vendita)
             VALUES ($1, $2, $3)",
            [$negozio_id, $prodotto, $prezzo]);
    }

    if (isset($_POST['rimuovi_prodotto'])) {
        $negozio_id = intval($_POST['negozio_id']);
        $prodotto_rimuovi = intval($_POST['prodotto_rimuovi']);
        pg_query_params($conn,
            "DELETE FROM prodotto_negozio WHERE negozio=$1 AND prodotto=$2",
            [$negozio_id, $prodotto_rimuovi]);
    }
}

// se non è stato selezionato un negozio, mostra lista
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
    <style>tr:hover {background:#eee; cursor:pointer;}</style>

    <?php if ($tipo === 'gestore') { ?>
        <h3>Inserisci nuovo negozio</h3>
        <form method="POST">
            <input type="text" name="indirizzo" placeholder="Indirizzo" required>
            <input type="time" name="apertura" required>
            <input type="time" name="chiusura" required>
            <button type="submit" name="inserisci_negozio">Aggiungi negozio</button>
        </form>
    <?php } ?>

    <p><a href="dashboard.php">Torna alla dashboard</a></p>
    <?php
    exit;
}

// se è stato selezionato un negozio
$negozio_id = intval($_GET['id']);
$query = "SELECT id, indirizzo, orario_apertura, orario_chiusura FROM negozio WHERE id=$1 AND eliminato=false";
$result = pg_query_params($conn, $query, [$negozio_id]);
if (pg_num_rows($result) != 1) {
    header('Location: negozio.php');
    exit;
}
$negozio = pg_fetch_assoc($result);

// mostra prodotti
$prodotti = pg_query_params($conn,
    "SELECT p.id, p.nome, p.descrizione, pn.prezzo_vendita
     FROM prodotto_negozio pn
     JOIN prodotto p ON pn.prodotto = p.id
     WHERE pn.negozio = $1", [$negozio_id]);
?>
<h2>Negozio <?php echo htmlspecialchars($negozio['indirizzo']) ?></h2>
<table border="1">
    <tr><th>Prodotto</th><th>Prezzo</th><th>Descrizione</th></tr>
    <?php while ($p = pg_fetch_assoc($prodotti)) { ?>
        <tr>
            <td><?php echo htmlspecialchars($p['nome']) ?></td>
            <td><?php echo htmlspecialchars($p['prezzo_vendita']) ?></td>
            <td><?php echo htmlspecialchars($p['descrizione'] ?? '') ?></td>
        </tr>
    <?php } ?>
</table>

<?php if ($tipo === 'gestore') { ?>
    <h3>Gestisci negozio</h3>
    <form method="POST">
        <input type="hidden" name="negozio_id" value="<?php echo $negozio_id ?>">
        <input type="text" name="indirizzo" value="<?php echo htmlspecialchars($negozio['indirizzo']) ?>" required>
        <input type="time" name="apertura" value="<?php echo htmlspecialchars($negozio['orario_apertura']) ?>" required>
        <input type="time" name="chiusura" value="<?php echo htmlspecialchars($negozio['orario_chiusura']) ?>" required>
        <button type="submit" name="modifica_negozio">Salva modifiche</button>
    </form>

    <h3>Aggiungi prodotto a questo negozio</h3>
    <form method="POST">
        <input type="hidden" name="negozio_id" value="<?php echo $negozio_id ?>">
        <select name="prodotto" required>
            <?php
            $all = pg_query_params($conn, 
                "SELECT p.id, p.nome
                 FROM prodotto p
                 WHERE NOT EXISTS (
                     SELECT 1 FROM prodotto_negozio pn
                     WHERE pn.prodotto = p.id AND pn.negozio = $1
                 )
                 ORDER BY p.nome",
                [$negozio_id]
            );
            while ($r = pg_fetch_assoc($all)) {
                echo '<option value="'.htmlspecialchars($r['id']).'">'.htmlspecialchars($r['nome']).'</option>';
            }
            ?>
        </select>
        <input type="number" step="0.01" name="prezzo" placeholder="Prezzo vendita" required>
        <button type="submit" name="aggiungi_prodotto">Aggiungi prodotto</button>
    </form>

    <h3>Rimuovi prodotto da questo negozio</h3>
    <form method="POST">
        <input type="hidden" name="negozio_id" value="<?php echo $negozio_id ?>">
        <select name="prodotto_rimuovi" required>
            <?php
            $associati = pg_query_params($conn, 
                "SELECT p.id, p.nome
                 FROM prodotto_negozio pn
                 JOIN prodotto p ON p.id = pn.prodotto
                 WHERE pn.negozio = $1
                 ORDER BY p.nome",
                [$negozio_id]
            );
            while ($r = pg_fetch_assoc($associati)) {
                echo '<option value="'.htmlspecialchars($r['id']).'">'.htmlspecialchars($r['nome']).'</option>';
            }
            ?>
        </select>
        <button type="submit" name="rimuovi_prodotto">Rimuovi prodotto</button>
    </form>

    <form method="POST" style="margin-top:10px;">
        <input type="hidden" name="negozio_id" value="<?php echo $negozio_id ?>">
        <button type="submit" name="elimina_negozio" onclick="return confirm('Sei sicuro di voler eliminare questo negozio?')">Elimina negozio</button>
    </form>
<?php } ?>

<p><a href="negozio.php">Torna ai negozi</a></p>
