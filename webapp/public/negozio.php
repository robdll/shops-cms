<?php
session_start();
if (!isset($_SESSION['email'])) {
    header('Location: index.php');
    exit;
}
include('../includes/db.php');

$messaggio = '';
$email = $_SESSION['email'];
$res = pg_query_params($conn,
    "SELECT t.saldo_punti 
     FROM tessera t
     JOIN utente u ON t.utente = u.id
     WHERE u.email = $1",
    [$email]);
$row = pg_fetch_assoc($res);
$punti = $row['saldo_punti'] ?? null;
?>

<?php include('header.php') ?>

<?php if ($messaggio): ?>
  <div class="alert alert-info"><?= htmlspecialchars($messaggio) ?></div>
<?php endif; ?>

<?php if (!isset($_GET['id'])): ?>
  <h2>Lista Negozi</h2>
  <table class="table table-hover">
    <thead>
      <tr>
        <th>ID</th>
        <th>Indirizzo</th>
        <th>Apertura</th>
        <th>Chiusura</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $res = pg_query($conn, "SELECT * FROM negozio ORDER BY id");
      while ($r = pg_fetch_assoc($res)) { ?>
        <tr onclick="window.location='negozio.php?id=<?= $r['id'] ?>'">
          <td><?= htmlspecialchars($r['id']) ?></td>
          <td><?= htmlspecialchars($r['indirizzo']) ?></td>
          <td><?= htmlspecialchars($r['orario_apertura']) ?></td>
          <td><?= htmlspecialchars($r['orario_chiusura']) ?></td>
        </tr>
      <?php } ?>
    </tbody>
  </table>
  <style>tr:hover {cursor:pointer}</style>

<?php else: ?>
  <?php
    $id = $_GET['id'];
    $q = pg_query_params($conn, "SELECT * FROM negozio WHERE id=$1", [$id]);
    $negozio = pg_fetch_assoc($q);
  ?>
  <h2>Negozio ID <?= htmlspecialchars($id) ?></h2>
  <p><strong><?= htmlspecialchars($negozio['indirizzo']) ?></strong>
     (<?= htmlspecialchars($negozio['orario_apertura']) ?> - <?= htmlspecialchars($negozio['orario_chiusura']) ?>)</p>
  <p class="mt-4"><a href="negozio.php" class="btn btn-secondary">Torna alla Lista Negozi</a></p>

  <h4 class="mt-4">Acquista Prodotti</h4>
  <form action="compra.php" method="POST">
    <input type="hidden" name="negozio" value="<?= $id ?>">
    <table class="table table-striped">
      <thead>
        <tr>
          <th>Prodotto</th>
          <th>Prezzo</th>
          <th>Quantità</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $res = pg_query_params($conn, "SELECT p.nome, pn.prodotto, pn.prezzo_vendita 
                                       FROM prodotto_negozio pn 
                                       JOIN prodotto p ON p.id = pn.prodotto 
                                       WHERE pn.negozio=$1 ORDER BY p.nome", [$id]);
        while ($r = pg_fetch_assoc($res)) { ?>
          <tr>
            <td><?= htmlspecialchars($r['nome']) ?></td>
            <td><?= htmlspecialchars($r['prezzo_vendita']) ?></td>
            <td>
              <input type="number" class="form-control" name="quantita[<?= $r['prodotto'] ?>]" min="0" max="1000" placeholder="0">
              <input type="hidden" name="prezzo[<?= $r['prodotto'] ?>]" value="<?= $r['prezzo_vendita'] ?>">
            </td>
          </tr>
        <?php } ?>
      </tbody>
    </table>
    <div class="d-flex align-items-center mt-3">
      <?php if ($punti !== null): ?>
        <div class="d-flex align-items-center">
          <label for="sconto" class="form-label me-2 mb-0">Seleziona sconto:</label>
          <select name="sconto" id="sconto" class="form-select w-auto">
            <option value="0">Nessuno</option>
            <?php if ($punti >= 100): ?>
              <option value="5">5% (100+ punti)</option>
            <?php endif; ?>
            <?php if ($punti >= 200): ?>
              <option value="15">15% (200+ punti)</option>
            <?php endif; ?>
            <?php if ($punti >= 300): ?>
              <option value="30">30% (300+ punti)</option>
            <?php endif; ?>
          </select>
        </div>
      <?php endif; ?>
      <div class="ms-auto">
        <button type="submit" class="btn btn-success">Acquista</button>
      </div>
    </div>
  </form>
<?php endif; ?>

<?php include('footer.php') ?>
