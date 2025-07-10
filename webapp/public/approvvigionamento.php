<?php
session_start();
if (!isset($_SESSION['email']) || $_SESSION['tipo'] !== 'gestore') {
    header('Location: login.php');
    exit;
}

include('../includes/db.php');

$approvv_fatti = [];
$prodotti_non_approvv = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['negozio_id'], $_POST['quantita'], $_POST['data_consegna'])) {
    $negozio_id = intval($_POST['negozio_id']);
    $data_consegna = $_POST['data_consegna'];
    $approvv_fatti = [];
    $prodotti_non_approvv = [];

    foreach ($_POST['quantita'] as $pid => $qty) {
        $qty = intval($qty);
        if ($qty > 0) {
            $res = @pg_query_params($conn,
                "INSERT INTO \"Kalunga\".approvvigionamento (negozio, prodotto, data_consegna, quantita)
                 VALUES ($1, $2, $3, $4)",
                [$negozio_id, $pid, $data_consegna, $qty]);

            if (!$res) {
                $prodotti_non_approvv[] = $pid;
            } else {
                $id = pg_fetch_result(pg_query($conn,
                    "SELECT currval(pg_get_serial_sequence('\"Kalunga\".approvvigionamento', 'id'))"), 0, 0);
                $info = pg_query_params($conn,
                    "SELECT a.id, a.fornitore, a.prezzo_unitario, a.quantita
                     FROM \"Kalunga\".approvvigionamento a WHERE a.id = $1",
                    [$id]);
                $approvv_fatti[] = pg_fetch_assoc($info);
            }
        }
    }

    $_SESSION['approvv_fatti'] = $approvv_fatti;
    $_SESSION['prodotti_non_approvv'] = $prodotti_non_approvv;

    header("Location: approvvigionamento.php?negozio=$negozio_id");
    exit;
}

if (isset($_GET['negozio'])) {
    $approvv_fatti = $_SESSION['approvv_fatti'] ?? [];
    $prodotti_non_approvv = $_SESSION['prodotti_non_approvv'] ?? [];
    unset($_SESSION['approvv_fatti'], $_SESSION['prodotti_non_approvv']);
}

if (!isset($_GET['negozio'])) {
    $result = pg_query($conn, "SELECT id, indirizzo FROM \"Kalunga\".negozio WHERE eliminato = false ORDER BY id");
    ?>
    <h2>Seleziona un negozio per effettuare approvvigionamento</h2>
    <table border="1">
        <tr><th>ID</th><th>Indirizzo</th></tr>
        <?php while ($row = pg_fetch_assoc($result)) { ?>
            <tr onclick="window.location='approvvigionamento.php?negozio=<?php echo urlencode($row['id']) ?>'">
                <td><?php echo htmlspecialchars($row['id']) ?></td>
                <td><?php echo htmlspecialchars($row['indirizzo']) ?></td>
            </tr>
        <?php } ?>
    </table>
    <style>tr:hover {background:#eee; cursor:pointer;}</style>
    <p><a href="dashboard.php">Torna alla dashboard</a></p>
    <?php
    exit;
}

$negozio_id = intval($_GET['negozio']);
$negozio = pg_query_params($conn,
    "SELECT id, indirizzo FROM \"Kalunga\".negozio WHERE id=$1 AND eliminato = false",
    [$negozio_id]);
if (pg_num_rows($negozio) != 1) {
    header("Location: approvvigionamento.php");
    exit;
}
$negozio = pg_fetch_assoc($negozio);

$storico = pg_query_params($conn,
    "SELECT a.id, a.prodotto, p.nome AS prodotto_nome, a.fornitore, a.prezzo_unitario, a.quantita, a.data_consegna
     FROM \"Kalunga\".approvvigionamento a
     JOIN \"Kalunga\".prodotto p ON a.prodotto = p.id
     WHERE a.negozio = $1
     ORDER BY a.data_consegna DESC",
    [$negozio_id]);
?>
<h2>Effettua approvvigionamento per negozio <?php echo htmlspecialchars($negozio['indirizzo']) ?></h2>
<form method="POST">
    <input type="hidden" name="negozio_id" value="<?php echo $negozio_id ?>">
    <table border="1">
        <tr><th>Prodotto</th><th>Quantità da ordinare</th></tr>
        <?php
        $prodotti = pg_query($conn, "SELECT id, nome FROM \"Kalunga\".prodotto ORDER BY nome");
        while ($p = pg_fetch_assoc($prodotti)) { ?>
            <tr>
                <td><?php echo htmlspecialchars($p['nome']) ?></td>
                <td><input type="number" name="quantita[<?php echo $p['id'] ?>]" value="0" min="0"></td>
            </tr>
        <?php } ?>
    </table>
    <label>Data consegna:</label>
    <input type="date" name="data_consegna" value="<?php echo date('Y-m-d') ?>" required>
    <button type="submit">Effettua approvvigionamento</button>
</form>

<?php if (count($prodotti_non_approvv) > 0) { ?>
    <p style="color:red;">
        I seguenti prodotti non possono essere ordinati a causa di mancanza di disponibilità:
        <?php
        $nomi = [];
        foreach ($prodotti_non_approvv as $pid) {
            $n = pg_query_params($conn, "SELECT nome FROM \"Kalunga\".prodotto WHERE id = $1", [$pid]);
            $nomi[] = pg_fetch_result($n, 0, 0);
        }
        echo htmlspecialchars(implode(', ', $nomi));
        ?>
    </p>
<?php } ?>

<?php if (count($approvv_fatti) > 0) { ?>
    <h3>Riepilogo Approvvigionamento effettuato:</h3>
    <table border="1">
        <tr><th>ID</th><th>Fornitore</th><th>Prezzo unitario</th><th>Quantità</th></tr>
        <?php foreach ($approvv_fatti as $app) { ?>
            <tr>
                <td><?php echo htmlspecialchars($app['id']) ?></td>
                <td><?php echo htmlspecialchars($app['fornitore']) ?></td>
                <td><?php echo htmlspecialchars($app['prezzo_unitario']) ?></td>
                <td><?php echo htmlspecialchars($app['quantita']) ?></td>
            </tr>
        <?php } ?>
    </table>
<?php } ?>

<h3>Storico approvvigionamenti di questo negozio</h3>
<table border="1">
    <tr><th>ID</th><th>Data</th><th>Prodotto</th><th>Fornitore</th><th>Prezzo unitario</th><th>Quantità</th></tr>
    <?php while ($row = pg_fetch_assoc($storico)) { ?>
        <tr>
            <td><?php echo htmlspecialchars($row['id']) ?></td>
            <td><?php echo htmlspecialchars($row['data_consegna']) ?></td>
            <td><?php echo htmlspecialchars($row['prodotto_nome']) ?></td>
            <td><?php echo htmlspecialchars($row['fornitore']) ?></td>
            <td><?php echo htmlspecialchars($row['prezzo_unitario']) ?></td>
            <td><?php echo htmlspecialchars($row['quantita']) ?></td>
        </tr>
    <?php } ?>
</table>

<p><a href="approvvigionamento.php">Torna alla lista negozi</a></p>
<p><a href="dashboard.php">Torna alla dashboard</a></p>
