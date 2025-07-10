<?php
session_start();
if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit;
}

include('../includes/db.php');

$email = $_SESSION['email'];
$negozio_id = intval($_POST['negozio_id']);
$quantita = $_POST['quantita'];
$sconto = intval($_POST['sconto']);

// Trova utente + tessera
$query = "SELECT u.id AS utente_id, t.id AS tessera_id
          FROM utente u LEFT JOIN tessera t ON t.utente = u.id
          WHERE u.email = $1";
$result = pg_query_params($conn, $query, [$email]);
$user = pg_fetch_assoc($result);

// Crea scontrino vuoto
$query = "INSERT INTO scontrino (data_acquisto, tessera, negozio, sconto_percentuale, totale_pagato, utente)
          VALUES (CURRENT_DATE, $1, $2, 0, 0, $3) RETURNING id";
$result = pg_query_params($conn, $query, [$user['tessera_id'], $negozio_id, $user['utente_id']]);
$scontrino_id = pg_fetch_result($result, 0, 'id');

// Prepara array prodotti/quantità/prezzi
$prodotti = [];
$quantities = [];
$prezzi = [];

foreach ($quantita as $pid => $qty) {
    $qty = intval($qty);
    if ($qty > 0) {
        $res = pg_query_params($conn, 
            "SELECT prezzo_vendita FROM prodotto_negozio WHERE negozio=$1 AND prodotto=$2",
            [$negozio_id, $pid]);
        $prezzo = pg_fetch_result($res, 0, 0);
        $prodotti[] = $pid;
        $quantities[] = $qty;
        $prezzi[] = $prezzo;
    }
}

// Richiama funzione DB
$sql = "SELECT Kalunga.crea_scontrino_con_prodotti($1, $2, $3, $4, $5)";
pg_query_params($conn, $sql, [
    $scontrino_id,
    '{' . implode(',', $prodotti) . '}',
    '{' . implode(',', $quantities) . '}',
    '{' . implode(',', $prezzi) . '}',
    $sconto
]);

header("Location: scontrino.php?id=$scontrino_id");
exit;
?>
