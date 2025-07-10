<?php
session_start();
include('../includes/db.php');

$email = $_SESSION['email'];
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

echo "<pre>";
echo "Prodotti validi:\n";
print_r($prodotti);
echo "Quantità valide:\n";
print_r($quantita_valid);
echo "Prezzi validi:\n";
print_r($prezzi_valid);
echo "Totale calcolato: $totale\n";
echo "</pre>";

if (empty($prodotti)) {
    echo "Nessun prodotto selezionato.";
    exit;
}

// crea scontrino
$insert = pg_query_params($conn,
    "INSERT INTO scontrino (data_acquisto, negozio, totale_pagato, utente) VALUES (CURRENT_DATE, $1, $2, $3) RETURNING id",
    [$negozio, $totale, $utente]);
$scontrino = pg_fetch_assoc($insert);
$id_scontrino = $scontrino['id'];

echo "Creato scontrino ID: $id_scontrino<br>";

for ($i = 0; $i < count($prodotti); $i++) {
    pg_query_params($conn,
        "INSERT INTO scontrino_prodotto (scontrino, prodotto, prezzo_unitario, quantita)
         VALUES ($1, $2, $3, $4)",
        [$id_scontrino, $prodotti[$i], $prezzi_valid[$i], $quantita_valid[$i]]);
    echo "Inserito prodotto {$prodotti[$i]} con qta {$quantita_valid[$i]} e prezzo {$prezzi_valid[$i]}<br>";
}

echo "<br>Redirect a scontrino.php?id=$id_scontrino";
header("Location: scontrino.php?id=$id_scontrino");
exit;
?>
