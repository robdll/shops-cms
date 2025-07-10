<?php
session_start();
include('../includes/db.php');

$email = $_SESSION['email'];
$q = pg_query_params($conn,
    "SELECT t.id AS tessera_id 
     FROM tessera t
     JOIN utente u ON t.utente = u.id
     WHERE u.email = $1",
    [$email]);
$t = pg_fetch_assoc($q);
$tessera_id = $t['tessera_id'] ?? null;

$res = pg_query_params($conn, "SELECT id FROM utente WHERE email=$1", [$email]);
$row = pg_fetch_assoc($res);
$utente = $row['id'];

$negozio = $_POST['negozio'] ?? null;
$quantita = $_POST['quantita'] ?? [];
$prezzi = $_POST['prezzo'] ?? [];
$prodotti = [];
$quantita_valid = [];
$prezzi_valid = [];
$totale = 0;

foreach ($quantita as $id_prodotto => $qta) {
    $qta = (int)$qta;
    if ($qta > 0) {
        $prodotti[] = $id_prodotto;
        $quantita_valid[] = $qta;
        $prezzi_valid[] = $prezzi[$id_prodotto];
        $totale += $prezzi[$id_prodotto] * $qta;
    }
}

if (empty($prodotti)) {
    echo "Nessun prodotto selezionato.";
    exit;
}

// crea scontrino CON tessera
$insert = pg_query_params($conn,
    "INSERT INTO scontrino (data_acquisto, tessera, negozio, sconto_percentuale, totale_pagato, utente) 
     VALUES (CURRENT_DATE, $1, $2, 0, 0, $3) RETURNING id",
    [$tessera_id, $negozio, $utente]);
$scontrino = pg_fetch_assoc($insert);
$id_scontrino = $scontrino['id'];

echo "Creato scontrino ID: $id_scontrino<br>";

// inserisci prodotti nello scontrino_prodotto
for ($i = 0; $i < count($prodotti); $i++) {
    pg_query_params($conn,
        "INSERT INTO scontrino_prodotto (scontrino, prodotto, prezzo_unitario, quantita)
         VALUES ($1, $2, $3, $4)",
        [$id_scontrino, $prodotti[$i], $prezzi_valid[$i], $quantita_valid[$i]]);
    echo "Inserito prodotto {$prodotti[$i]} con qta {$quantita_valid[$i]} e prezzo {$prezzi_valid[$i]}<br>";
}

// infine applica sconto e aggiorna saldo punti con funzione DB
$sconto = $_POST['sconto'] ?? 0;
pg_query_params($conn, 
    "SELECT applica_sconto_scontrino($1, $2, $3)",
    [$id_scontrino, (int)$sconto, $totale]);

echo "<br>Redirect a scontrino.php?id=$id_scontrino";
header("Location: scontrino.php?id=$id_scontrino");
exit;
?>
