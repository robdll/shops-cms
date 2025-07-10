<?php
include('../includes/db.php');
session_start();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $query = "SELECT nome, tipo FROM utente WHERE email=$1 AND password=$2";
    $result = pg_query_params($conn, $query, array($username, $password));

    if ($result && pg_num_rows($result) == 1) {
        $row = pg_fetch_assoc($result);
        $_SESSION['email'] = $username;
        $_SESSION['nome'] = $row['nome'];
        $_SESSION['tipo'] = $row['tipo'];
        header('Location: dashboard.php');
        exit;
    } else {
        $error = "Credenziali non valide";
    }
}
?>
<h2>Login</h2>
<form method="POST">
    <input type="text" name="username" placeholder="Username" required><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <button type="submit">Login</button>
</form>
<?php if ($error) echo "<p style='color:red'>$error</p>" ?>
