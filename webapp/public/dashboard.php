<?php
session_start();
if (!isset($_SESSION['email']) || !isset($_SESSION['tipo']) || !isset($_SESSION['nome'])) {
    header('Location: login.php');
    exit;
}

$nome = $_SESSION['nome'];
$tipo = $_SESSION['tipo'];
?>

<h2>Dashboard</h2>
<p>Ciao <?php echo htmlspecialchars($nome) ?> (<?php echo htmlspecialchars($tipo) ?>)</p>

<!-- per tutti gli utenti -->
<p><a href="tessera.php">Visualizza saldo punti</a></p>
<p><a href="modifica-password.php">Modifica password</a></p>

<!-- per tutti gli utenti, ma con funzionalità specifiche per gestori -->
<p><a href="negozio.php">Negozi</a></p>

<!-- per gestori -->
<?php if ($tipo === 'gestore') { ?>
    <p><a href="report.php">Visualizza report</a></p>
    <p><a href="fornitore.php">Gestisci fornitori</a></p>
    <p><a href="approvvigionamento.php">Approvvigionamenti</a></p>
<?php } ?>
