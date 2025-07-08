<?php
session_start();
if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit;
}

include('../includes/db.php');

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $query = "SELECT id, indirizzo, orario_apertura, orario_chiusura
              FROM negozio
              WHERE id = $1 AND eliminato = false";
    $result = pg_query_params($conn, $query, array($id));

    if (!$result || pg_num_rows($result) != 1) {
        echo "<p>Negozio non trovato o eliminato.</p>";
        echo '<p><a href="negozio.php">Torna alla lista negozi</a></p>';
        exit;
    }

    $negozio = pg_fetch_assoc($result);

    // Prodotti del negozio
    $query = "SELECT p.id, p.nome, p.descrizione, pn.prezzo_vendita
              FROM prodotto_negozio pn
              JOIN prodotto p ON pn.prodotto = p.id
              WHERE pn.negozio = $1";
    $prodotti = pg_query_params($conn, $query, array($id));
    ?>
    <h2>Negozio <?php echo htmlspecialchars($negozio['id']) ?></h2>
    <p><strong>Indirizzo:</strong> <?php echo htmlspecialchars($negozio['indirizzo']) ?></p>
    <p><strong>Apertura:</strong> <?php echo htmlspecialchars($negozio['orario_apertura']) ?></p>
    <p><strong>Chiusura:</strong> <?php echo htmlspecialchars($negozio['orario_chiusura']) ?></p>

    <h3>Prodotti disponibili</h3>
    <form method="POST" action="compra.php">
        <input type="hidden" name="negozio_id" value="<?php echo htmlspecialchars($negozio['id']) ?>">
        <table border="1" cellpadding="5" cellspacing="0">
            <tr>
                <th>Nome</th>
                <th>Prezzo</th>
                <th>Quantità</th>
            </tr>
            <?php while ($p = pg_fetch_assoc($prodotti)) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($p['nome']) ?></td>
                    <td class="prezzo"><?php echo htmlspecialchars($p['prezzo_vendita']) ?></td>
                    <td>
                        <input type="number" name="quantita[<?php echo $p['id'] ?>]" value="0" min="0" 
                            onchange="calcolaTotale()">
                    </td>
                </tr>
            <?php } ?>
        </table>

        <p><strong>Totale:</strong> € <span id="totale">0.00</span></p>

        <label>Applica sconto:</label>
        <select name="sconto">
            <option value="0">Nessuno</option>
            <option value="5">5%</option>
            <option value="15">15%</option>
            <option value="30">30%</option>
        </select>

        <button type="submit">Compra</button>
    </form>

    <p><a href="negozio.php">Torna alla lista negozi</a></p>

    <script>
    function calcolaTotale() {
        let totale = 0;
        const righe = document.querySelectorAll('table tr');
        righe.forEach(function(riga) {
            const prezzoCell = riga.querySelector('.prezzo');
            const qtyInput = riga.querySelector('input[type="number"]');
            if (prezzoCell && qtyInput) {
                let prezzo = parseFloat(prezzoCell.textContent);
                let qty = parseInt(qtyInput.value) || 0;
                totale += prezzo * qty;
            }
        });
        document.getElementById('totale').textContent = totale.toFixed(2);
    }
    </script>
    <?php
    exit;
}

// SE NON C'È ID => LISTA NEGOZI
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
