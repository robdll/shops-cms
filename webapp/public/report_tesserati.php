<?php
session_start();
include('../includes/check-auth.php');
include('../includes/check-gestore.php');
include('../includes/db.php');

$negozi = pg_query($conn, 'SELECT id, indirizzo FROM "Kalunga".negozio WHERE eliminato = FALSE ORDER BY id');

$filtra_negozio = isset($_GET['negozio']) ? (int)$_GET['negozio'] : 0;

if ($filtra_negozio > 0) {
    $res = pg_query_params($conn,
        'SELECT t.tessera_id, t.data_rilascio, t.nome, t.cognome, t.codice_fiscale, t.negozio
         FROM "Kalunga".lista_tesserati_negozio t
         WHERE t.negozio = $1
         ORDER BY t.data_rilascio DESC',
        [$filtra_negozio]);
} else {
    $res = pg_query($conn,
        'SELECT t.tessera_id, t.data_rilascio, t.nome, t.cognome, t.codice_fiscale, t.negozio
         FROM "Kalunga".lista_tesserati_negozio t
         ORDER BY t.data_rilascio DESC');
}
?>
<?php include('header.php') ?>

<h2>Report Tesserati</h2>

<form method="GET" class="mb-4">
  <div class="row">
    <div class="col-md-6">
      <label for="negozio" class="form-label">Filtra per Negozio</label>
      <select class="form-select" id="negozio" name="negozio" onchange="this.form.submit()">
        <option value="0">Tutti i negozi</option>
        <?php while ($n = pg_fetch_assoc($negozi)): ?>
          <option value="<?= $n['id'] ?>" <?= ($filtra_negozio == $n['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($n['indirizzo']) ?> (ID <?= $n['id'] ?>)
          </option>
        <?php endwhile; ?>
      </select>
    </div>
  </div>
</form>

<table class="table table-hover">
  <thead>
    <tr>
      <th>ID Tessera</th>
      <th>Data Rilascio</th>
      <th>Nome</th>
      <th>Cognome</th>
      <th>Codice Fiscale</th>
      <th>ID Negozio</th>
    </tr>
  </thead>
  <tbody>
    <?php while ($r = pg_fetch_assoc($res)) { ?>
      <tr>
        <td><?= htmlspecialchars($r['tessera_id']) ?></td>
        <td><?= htmlspecialchars($r['data_rilascio']) ?></td>
        <td><?= htmlspecialchars($r['nome']) ?></td>
        <td><?= htmlspecialchars($r['cognome']) ?></td>
        <td><?= htmlspecialchars($r['codice_fiscale']) ?></td>
        <td><?= htmlspecialchars($r['negozio']) ?></td>
      </tr>
    <?php } ?>
  </tbody>
</table>

<?php include('footer.php') ?>
