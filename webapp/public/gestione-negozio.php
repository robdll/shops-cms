<?php
session_start();

include('../includes/check-auth.php');
include('../includes/check-gestore.php');
include('../includes/db.php');

$messaggio = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $indirizzo = $_POST['indirizzo'];
        $apertura = $_POST['apertura'];
        $chiusura = $_POST['chiusura'];
        $responsabile = $_POST['responsabile'];
        pg_query_params($conn,
            "INSERT INTO negozio (indirizzo, orario_apertura, orario_chiusura, responsabile, eliminato)
             VALUES ($1, $2, $3, $4, FALSE)",
            [$indirizzo, $apertura, $chiusura, $responsabile]);
        $messaggio = "Negozio aggiunto con successo.";
    }

    if (isset($_POST['delete_id'])) {
        $delete_id = (int)$_POST['delete_id'];
        pg_query_params($conn, "UPDATE negozio SET eliminato = TRUE WHERE id = $1", [$delete_id]);
        $messaggio = "Negozio ID $delete_id eliminato.";
    }

    if (isset($_POST['update'])) {
        $edit_id = (int)$_POST['edit_id'];
        $indirizzo = $_POST['indirizzo_edit'];
        $apertura = $_POST['apertura_edit'];
        $chiusura = $_POST['chiusura_edit'];
        $responsabile = $_POST['responsabile_edit'];

        pg_query_params($conn,
            "UPDATE negozio SET indirizzo=$1, orario_apertura=$2, orario_chiusura=$3, responsabile=$4 WHERE id=$5",
            [$indirizzo, $apertura, $chiusura, $responsabile, $edit_id]);
        $messaggio = "Negozio ID $edit_id aggiornato con successo.";
    }

    // aggiunta prodotto al negozio
    if (isset($_POST['add_prodotto'])) {
        $negozio_id = (int)$_POST['negozio_id'];
        $prodotto_id = (int)$_POST['prodotto_id'];
        $prezzo_vendita = $_POST['prezzo_vendita'];
        pg_query_params($conn,
            'INSERT INTO prodotto_negozio (negozio, prodotto, prezzo_vendita) VALUES ($1, $2, $3)',
            [$negozio_id, $prodotto_id, $prezzo_vendita]);
        header("Location: gestione-negozio.php?id=$negozio_id");
        exit;
    }

    // elimina prodotto dal negozio
    if (isset($_POST['delete_prodotto_id'])) {
        $negozio_id = (int)$_POST['negozio_id'];
        $prodotto_id = (int)$_POST['delete_prodotto_id'];
        pg_query_params($conn,
            'DELETE FROM prodotto_negozio WHERE negozio = $1 AND prodotto = $2',
            [$negozio_id, $prodotto_id]);
        header("Location: gestione-negozio.php?id=$negozio_id");
        exit;
    }
}

// carica lista responsabili
$responsabili = pg_query($conn, "SELECT id, nome, cognome FROM utente WHERE tipo = 'gestore' ORDER BY nome");
?>

<?php include('header.php') ?>

<?php if ($messaggio): ?>
  <div class="alert alert-success"><?= htmlspecialchars($messaggio) ?></div>
<?php endif; ?>

<?php if (!isset($_GET['id'])): ?>
  <h2>Gestione Negozi</h2>
  <table class="table table-hover">
    <thead>
      <tr>
        <th>ID</th>
        <th>Indirizzo</th>
        <th>Apertura</th>
        <th>Chiusura</th>
        <th>Responsabile</th>
        <th>Azioni</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $res = pg_query($conn, "SELECT * FROM negozio WHERE eliminato = FALSE ORDER BY id");
      while ($r = pg_fetch_assoc($res)) { ?>
        <tr>
          <td><?= htmlspecialchars($r['id']) ?></td>
          <td><?= htmlspecialchars($r['indirizzo']) ?></td>
          <td><?= htmlspecialchars($r['orario_apertura']) ?></td>
          <td><?= htmlspecialchars($r['orario_chiusura']) ?></td>
          <td><?= htmlspecialchars($r['responsabile']) ?></td>
          <td>
            <a href="gestione-negozio.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-primary">Gestisci</a>
            <form method="POST" class="d-inline">
              <input type="hidden" name="delete_id" value="<?= $r['id'] ?>">
              <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Sei sicuro?')">Elimina</button>
            </form>
          </td>
        </tr>
      <?php } ?>
    </tbody>
  </table>

  <h2 class="mt-5">Aggiungi Nuovo Negozio</h2>
  <form method="POST" class="mb-5">
    <div class="row mb-3">
      <div class="col-md-6">
        <label for="indirizzo" class="form-label">Indirizzo</label>
        <input type="text" class="form-control" id="indirizzo" name="indirizzo" required>
      </div>
      <div class="col-md-4">
        <label for="responsabile" class="form-label">Responsabile</label>
        <select class="form-select" id="responsabile" name="responsabile" required>
          <option value="">Seleziona Responsabile</option>
          <?php
          pg_result_seek($responsabili, 0); 
          while ($r = pg_fetch_assoc($responsabili)): ?>
            <option value="<?= htmlspecialchars($r['id']) ?>">
              <?= htmlspecialchars($r['nome'] . ' ' . $r['cognome']) ?> (ID <?= $r['id'] ?>)
            </option>
          <?php endwhile; ?>
        </select>
      </div>
    </div>

    <div class="row mb-3">
      <div class="col-md-3">
        <label for="apertura" class="form-label">Orario Apertura</label>
        <input type="time" class="form-control" id="apertura" name="apertura" required>
      </div>
      <div class="col-md-3">
        <label for="chiusura" class="form-label">Orario Chiusura</label>
        <input type="time" class="form-control" id="chiusura" name="chiusura" required>
      </div>
    </div>

    <button type="submit" name="add" class="btn btn-success">Aggiungi Negozio</button>
  </form>
<?php endif; ?>

<?php if (isset($_GET['id'])): ?>
  <?php
    $id = $_GET['id'];
    $q = pg_query_params($conn, "SELECT * FROM negozio WHERE id=$1", [$id]);
    $negozio = pg_fetch_assoc($q);

    // carica prodotti presenti
    $prodotti = pg_query_params($conn, 
      'SELECT pn.prodotto, p.nome, pn.prezzo_vendita 
       FROM prodotto_negozio pn
       JOIN prodotto p ON p.id = pn.prodotto
       WHERE pn.negozio = $1
       ORDER BY p.nome', 
      [$id]);

    // carica prodotti NON ancora presenti
    $prodotti_disponibili = pg_query_params($conn,
      'SELECT p.id, p.nome 
       FROM prodotto p 
       WHERE NOT EXISTS (
         SELECT 1 FROM prodotto_negozio pn
         WHERE pn.prodotto = p.id AND pn.negozio = $1
       )
       ORDER BY p.nome', 
      [$id]);
  ?>
  <h3 class="mt-5">Gestisci Negozio ID <?= htmlspecialchars($id) ?></h3>
  <form method="POST" class="mb-5">
    <input type="hidden" name="edit_id" value="<?= htmlspecialchars($id) ?>">
    <div class="row mb-3">
      <div class="col-md-6">
        <label for="indirizzo_edit" class="form-label">Indirizzo</label>
        <input type="text" class="form-control" id="indirizzo_edit" name="indirizzo_edit"
               value="<?= htmlspecialchars($negozio['indirizzo']) ?>" required>
      </div>
      <div class="col-md-4">
        <label for="responsabile_edit" class="form-label">Responsabile</label>
        <select class="form-select" id="responsabile_edit" name="responsabile_edit" required>
          <?php
          $resp2 = pg_query($conn, "SELECT id, nome, cognome FROM utente WHERE tipo = 'gestore' ORDER BY nome");
          while ($r = pg_fetch_assoc($resp2)): ?>
            <option value="<?= htmlspecialchars($r['id']) ?>"
              <?= $r['id'] == $negozio['responsabile'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($r['nome'] . ' ' . $r['cognome']) ?> (ID <?= $r['id'] ?>)
            </option>
          <?php endwhile; ?>
        </select>
      </div>
    </div>
    <div class="row mb-3">
      <div class="col-md-3">
        <label for="apertura_edit" class="form-label">Orario Apertura</label>
        <input type="time" class="form-control" id="apertura_edit" name="apertura_edit"
               value="<?= htmlspecialchars($negozio['orario_apertura']) ?>" required>
      </div>
      <div class="col-md-3">
        <label for="chiusura_edit" class="form-label">Orario Chiusura</label>
        <input type="time" class="form-control" id="chiusura_edit" name="chiusura_edit"
               value="<?= htmlspecialchars($negozio['orario_chiusura']) ?>" required>
      </div>
    </div>
    <button type="submit" name="update" class="btn btn-warning">Salva Modifiche</button>
    <a href="gestione-negozio.php" class="btn btn-secondary">Indietro</a>
  </form>

  <h4 class="mt-5">Prodotti disponibili in questo negozio</h4>
  <table class="table">
    <thead>
      <tr>
        <th>Nome Prodotto</th>
        <th>Prezzo Vendita (€)</th>
        <th>Azioni</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($r = pg_fetch_assoc($prodotti)): ?>
        <tr>
          <td><?= htmlspecialchars($r['nome']) ?></td>
          <td><?= htmlspecialchars($r['prezzo_vendita']) ?></td>
          <td>
            <form method="post" class="d-inline">
              <input type="hidden" name="negozio_id" value="<?= htmlspecialchars($id) ?>">
              <input type="hidden" name="delete_prodotto_id" value="<?= htmlspecialchars($r['prodotto']) ?>">
              <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Sei sicuro?')">Elimina</button>
            </form>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <h4 class="mt-4">Aggiungi prodotto a questo negozio</h4>
  <form method="post" class="mb-5">
    <input type="hidden" name="negozio_id" value="<?= htmlspecialchars($id) ?>">
    <div class="row mb-3">
      <div class="col-md-6">
        <label for="prodotto_id" class="form-label">Prodotto</label>
        <select name="prodotto_id" class="form-select" required>
          <?php while ($r = pg_fetch_assoc($prodotti_disponibili)): ?>
            <option value="<?= htmlspecialchars($r['id']) ?>"><?= htmlspecialchars($r['nome']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label for="prezzo_vendita" class="form-label">Prezzo (€)</label>
        <input type="number" step="0.01" name="prezzo_vendita" class="form-control" required>
      </div>
    </div>
    <button type="submit" name="add_prodotto" class="btn btn-success">Aggiungi Prodotto</button>
  </form>
<?php endif; ?>

<?php include('footer.php') ?>
