<?php
session_start();
include('../includes/check-auth.php');
include('../includes/check-gestore.php');
include('../includes/db.php');

$fornitori = pg_query($conn, 'SELECT partita_iva, indirizzo FROM fornitore ORDER BY partita_iva');

$filtra_fornitore = isset($_GET['fornitore']) ? $_GET['fornitore'] : '';

if ($filtra_fornitore !== '' && $filtra_fornitore !== 'tutti') {
    $res = pg_query_params($conn,
        'SELECT o.id, o.data_consegna, o.negozio, o.fornitore, p.nome AS prodotto, o.quantita, o.prezzo_unitario
         FROM approvvigionamento o
         JOIN prodotto p ON p.id = o.prodotto
         WHERE o.fornitore = $1
         ORDER BY o.data_consegna DESC',
        [$filtra_fornitore]);
} else {
    $res = pg_query($conn,
        'SELECT o.id, o.data_consegna, o.negozio, o.fornitore, p.nome AS prodotto, o.quantita, o.prezzo_unitario
         FROM approvvigionamento o
         JOIN prodotto p ON p.id = o.prodotto
         ORDER BY o.data_consegna DESC');
}
?>
<?php include('header.php') ?>

<h2>Storico Ordini Fornitori</h2>

<form method="GET" class="mb-4">
  <div class="row">
    <div class="col-md-6">
      <label for="fornitore" class="form-label">Filtra per Fornitore</label>
      <select class="form-select" id="fornitore" name="fornitore" onchange="this.form.submit()">
        <option value="tutti">Tutti i fornitori</option>
        <?php while ($f = pg_fetch_assoc($fornitori)): ?>
          <option value="<?= htmlspecialchars($f['partita_iva']) ?>" 
            <?= ($filtra_fornitore == $f['partita_iva']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($f['indirizzo']) ?> (P.IVA <?= $f['partita_iva'] ?>)
          </option>
        <?php endwhile; ?>
      </select>
    </div>
  </div>
</form>

<table class="table table-hover">
  <thead>
    <tr>
      <th>ID Ordine</th>
      <th>Data Consegna</th>
      <th>Negozio</th>
      <th>Fornitore</th>
      <th>Prodotto</th>
      <th>Quantità</th>
      <th>Prezzo Unitario</th>
    </tr>
  </thead>
  <tbody>
    <?php while ($r = pg_fetch_assoc($res)) { ?>
      <tr>
        <td><?= htmlspecialchars($r['id']) ?></td>
        <td><?= htmlspecialchars($r['data_consegna']) ?></td>
        <td><?= htmlspecialchars($r['negozio']) ?></td>
        <td><span class="badge bg-primary"><?= htmlspecialchars($r['fornitore']) ?></span></td>
        <td><?= htmlspecialchars($r['prodotto']) ?></td>
        <td><?= htmlspecialchars($r['quantita']) ?></td>
        <td><?= htmlspecialchars($r['prezzo_unitario']) ?></td>
      </tr>
    <?php } ?>
  </tbody>
</table>

<?php include('footer.php') ?>
