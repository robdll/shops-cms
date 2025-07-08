<?php
session_start();
if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit;
}

include('../includes/db.php');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'];

    $query = "UPDATE utente SET password = $1 WHERE email = $2";
    $result = pg_query_params($conn, $query, array($new_password, $_SESSION['email']));

    if ($result) {
        $success = "Password aggiornata con successo";
    } else {
        $error = "Errore nell'aggiornamento della password";
    }
}
?>
<h2>Modifica password</h2>
<form method="POST">
    <input type="password" name="new_password" placeholder="Nuova password" required><br>
    <button type="submit">Aggiorna</button>
</form>
<?php
if ($error) echo "<p style='color:red'>$error</p>";
if ($success) echo "<p style='color:green'>$success</p>";
?>
<p><a href="dashboard.php">Torna alla dashboard</a></p>
