<?php
session_start();
if (!isset($_SESSION['email'])) {
    header('Location: index.php');
    exit;
}
include('../includes/db.php');

$tipo = $_SESSION['tipo'];
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['inserisci_negozio'])) {
        $indirizzo = $_POST['indirizzo'];
        $apertura = $_POST['apertura'];
        $chiusura = $_POST['chiusura'];
        $query = "INSERT INTO negozio (indirizzo, orario_apertura, orario_chiusura, responsabile) VALUES ($1, $2, $3, $4)";
        $res = pg_query_params($conn, $query, [$indirizzo, $apertura, $chiusura, 1]);
        $messaggio = $res ? "Negozio inserito!" : "Errore inserimento negozio.";
    }
    if (isset($_POST['aggiungi_prodotto'])) {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: negozio.php');
            exit;
        }
        $prodotto = $_POST['prodotto'];
        $prezzo = $_POST['prezzo'];
        $query = "INSERT INTO prodotto_negozio (negozio, prodotto, prezzo_vendita) VALUES ($1, $2, $3)";
        $res = pg_query_params($conn, $query, [$id, $prodotto, $prezzo]);
        $messaggio = $res ? "Prodotto aggiunto!" : "Errore aggiunta prodotto.";
    }
    if (isset($_POST['rimuovi_prodotto'])) {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: negozio.php');
            exit;
        }
        $prodotto = $_POST['prodotto_id'];
        $query = "DELETE FROM prodotto_negozio WHERE negozio=$1 AND prodotto=$2";
        $res = pg_query_params($conn, $query, [$id, $prodotto]);
        $messaggio = $res ? "Prodotto rimosso!" : "Errore rimozione prodotto.";
    }
    if (isset($_POST['modifica_negozio'])) {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: negozio.php');
            exit;
        }
        $indirizzo = $_POST['indirizzo'];
        $apertura = $_POST['apertura'];
        $chiusura = $_POST['chiusura'];
        $query = "UPDATE negozio SET indirizzo=$1, orario_apertura=$2, orario_chiusura=$3 WHERE id=$4";
        $res = pg_query_params($conn, $query, [$indirizzo, $apertura, $chiusura, $id]);
        $messaggio = $res ? "Dati negozio aggiornati!" : "Errore modifica negozio.";
    }
    if (isset($_POST['elimina_negozio'])) {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: negozio.php');
            exit;
        }
        $query = "DELETE FROM negozio WHERE id=$1";
        pg_query_params($conn, $query, [$id]);
        header('Location: negozio.php');
        exit;
    }
}
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

  <?php if ($tipo === 'gestore'): ?>
    <h4 class="mt-4">Aggiungi un nuovo negozio</h4>
    <form method="POST" class="row g-3">
      <div class="col-md-4">
        <input type="text" name="indirizzo" class="form-control" placeholder="Indirizzo" required>
      </div>
      <div class="col-md-2">
        <input type="time" name="apertura" class="form-control" required>
      </div>
      <div class="col-md-2">
        <input type="time" name="chiusura" class="form-control" required>
      </div>
      <div class="col-md-2">
        <button type="submit" name="inserisci_negozio" class="btn btn-primary">Inserisci</button>
      </div>
    </form>
  <?php endif; ?>

<?php else: ?>
  <?php
    $id = $_GET['id'] ?? null;
    if (!$id) {
        header('Location: negozio.php');
        exit;
    }
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
          <?php if ($tipo === 'gestore'): ?><th>Azioni</th><?php endif; ?>
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
            <?php if ($tipo === 'gestore'): ?>
            <td>
              <form method="POST" class="d-inline">
                <input type="hidden" name="prodotto_id" value="<?= $r['prodotto'] ?>">
                <button type="submit" name="rimuovi_prodotto" class="btn btn-sm btn-danger">Rimuovi</button>
              </form>
            </td>
            <?php endif; ?>
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

  <?php if ($tipo === 'gestore'): ?>
    <h5 class="mt-5">Aggiungi prodotto a questo negozio</h5>
    <form method="POST" class="row g-3">
      <div class="col-md-4">
        <select name="prodotto" class="form-select" required>
          <option value="">Seleziona prodotto</option>
          <?php
          $opt = pg_query($conn, "SELECT id, nome FROM prodotto ORDER BY nome");
          while ($p = pg_fetch_assoc($opt)) {
            echo "<option value='".htmlspecialchars($p['id'])."'>".htmlspecialchars($p['nome'])."</option>";
          }
          ?>
        </select>
      </div>
      <div class="col-md-2">
        <input type="number" step="0.01" name="prezzo" class="form-control" placeholder="Prezzo" required>
      </div>
      <div class="col-md-2">
        <button type="submit" name="aggiungi_prodotto" class="btn btn-primary">Aggiungi</button>
      </div>
    </form>

    <h5 class="mt-5">Modifica negozio</h5>
    <form method="POST" class="row g-3">
      <div class="col-md-4">
        <input type="text" name="indirizzo" class="form-control" value="<?= htmlspecialchars($negozio['indirizzo']) ?>" required>
      </div>
      <div class="col-md-2">
        <input type="time" name="apertura" class="form-control" value="<?= htmlspecialchars($negozio['orario_apertura']) ?>" required>
      </div>
      <div class="col-md-2">
        <input type="time" name="chiusura" class="form-control" value="<?= htmlspecialchars($negozio['orario_chiusura']) ?>" required>
      </div>
      <div class="col-md-2">
        <button type="submit" name="modifica_negozio" class="btn btn-secondary">Salva</button>
      </div>
    </form>

    <form method="POST" class="mt-3">
      <button type="submit" name="elimina_negozio" class="btn btn-danger" onclick="return confirm('Eliminare questo negozio?')">Elimina negozio</button>
    </form>
  <?php endif; ?>
<?php endif; ?>

<?php include('footer.php') ?>
