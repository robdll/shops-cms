<?php
session_start();
include('../includes/check-auth.php');
include('../includes/check-gestore.php');
include('../includes/db.php');

$res = pg_query($conn,
    'SELECT tessera_id, saldo_punti, nome, cognome, codice_fiscale
     FROM clienti_con_piu_di_300_punti
     ORDER BY saldo_punti DESC');
?>
<?php include('header.php') ?>

<h2>Clienti con più di 300 punti</h2>

<table class="table table-hover">
  <thead>
    <tr>
      <th>ID Tessera</th>
      <th>Saldo Punti</th>
      <th>Nome</th>
      <th>Cognome</th>
      <th>Codice Fiscale</th>
    </tr>
  </thead>
  <tbody>
    <?php while ($r = pg_fetch_assoc($res)) { ?>
      <tr>
        <td><?= htmlspecialchars($r['tessera_id']) ?></td>
        <td><?= htmlspecialchars($r['saldo_punti']) ?></td>
        <td><?= htmlspecialchars($r['nome']) ?></td>
        <td><?= htmlspecialchars($r['cognome']) ?></td>
        <td><?= htmlspecialchars($r['codice_fiscale']) ?></td>
      </tr>
    <?php } ?>
  </tbody>
</table>

<?php include('footer.php') ?>
