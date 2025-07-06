<?php
$host = 'localhost';
$db   = 'imdb';
$user = 'rob.dll';
$pass = 'robdll123';
$port = '5432';

$conn = pg_connect("host=$host port=$port dbname=$db user=$user password=$pass")
    or die('Connessione fallita: ' . pg_last_error());

pg_query($conn, 'SET search_path TO "Kalunga"');

?>
