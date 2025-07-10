<?php
session_start();
if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit;
}

include('../includes/db.php');

$email = $_SESSION['email'];
$negozio_id = intval($_POST['negozio_id']);
$quantita = $_POST['quantita']; // array associativo
$sconto = intval($_POST['sconto']);

// Ricava id utente
$query = "SELECT id FROM utente WHERE email = $1";
$result = pg_query_params($conn, $query, array($email));
$row = pg_fetch_assoc($result);
$utente_id = $row['id'];

// Ricava id tessera
$query = "SELECT id FROM tessera WHERE utente = $1";
$result = pg_query_params($conn, $query, array($utente_id));
$tessera_id = null;
if ($result && pg_num_rows($result) == 1) {
    $row = pg_fetch_assoc($result);
    $tessera_id = $row['id'];
}

// Crea lo scontrino
$query = "INSERT INTO scontrino (data_acquisto, tessera, negozio, sconto_percentuale, totale_pagato, utente)
          VALUES (CURRENT_DATE, $1, $2, 0, 0, $3) RETURNING id";
$result = pg_query_params($conn, $query, array($tessera_id, $negozio_id, $utente_id));
$row = pg_fetch_assoc($result);
$scontrino_id = $row['id'];

// Inserisce righe scontrino_prodotto
foreach ($quantita as $prodotto_id => $qty) {
    $qty = intval($qty);
    if ($qty > 0) {
        // prezzo corrente
        $query = "SELECT prezzo_vendita FROM prodotto_negozio 
                  WHERE negozio = $1 AND prodotto = $2";
        $result = pg_query_params($conn, $query, array($negozio_id, $prodotto_id));
        $row = pg_fetch_assoc($result);
        $prezzo = $row['prezzo_vendita'];

        // inserisci
        $query = "INSERT INTO scontrino_prodotto (scontrino, prodotto, prezzo_unitario, quantita)
                  VALUES ($1, $2, $3, $4)";
        pg_query_params($conn, $query, array($scontrino_id, $prodotto_id, $prezzo, $qty));
    }
}

// Redirect finale
header("Location: scontrino.php?id=$scontrino_id");
exit;
?>
