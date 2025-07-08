<?php
session_start();
if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit;
}

include('../includes/db.php');

$email = $_SESSION['email'];

// recuperiamo id utente e saldo punti in un'unica query con JOIN
$query = "SELECT t.saldo_punti
          FROM tessera t
          JOIN utente u ON t.utente = u.id
          WHERE u.email = $1";

$result = pg_query_params($conn, $query, array($email));
$saldo = "N/A";
if ($result && pg_num_rows($result) == 1) {
    $row = pg_fetch_assoc($result);
    $saldo = $row['saldo_punti'];
}
?>
<h2>Saldo punti tessera fedeltà</h2>
<p>Il tuo saldo è: <?php echo htmlspecialchars($saldo) ?></p>
<p><a href="dashboard.php">Torna alla dashboard</a></p>
