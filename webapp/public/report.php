<?php
session_start();
include('../includes/check-auth.php');
include('../includes/check-gestore.php');
?>

<h2>Report gestore</h2>

<ul>
    <li><a href="report_tesserati.php">Lista tesserati per negozio</a></li>
    <li><a href="report_clienti_300.php">Clienti con più di 300 punti</a></li>
    <li><a href="report_ordini_fornitore.php">Storico ordini per fornitore</a></li>
</ul>

<p><a href="dashboard.php">Torna alla dashboard</a></p>
