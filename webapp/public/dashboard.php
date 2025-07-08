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
