<?php
session_start();

if ($_SESSION['tipo'] !== 'gestore') {
    header('Location: dashboard.php');
    exit;
}
?>
