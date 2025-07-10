<?php
session_start();
include('../includes/check-auth.php');
include('../includes/check-gestore.php');
include('../includes/db.php');

$messaggio = $_GET['msg'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_fornitore'])) {
        $piva = $_POST['piva'];
        $indirizzo = $_POST['indirizzo'];

        pg_query_params($conn,
            'INSERT INTO "Kalunga".fornitore (partita_iva, indirizzo) VALUES ($1, $2)',
            [$piva, $indirizzo]);

        header("Location: fornitore.php?msg=Fornitore aggiunto con successo");
        exit;
    }

    if (isset($_POST['add_prodotto_fornito'])) {
        $fornitore = $_POST['fornitore_id'];
        $prodotto = (int)$_POST['prodotto_id'];
        $disponibilita = (int)$_POST['disponibilita'];
        $prezzo = (float)$_POST['prezzo'];

        pg_query_params($conn,
            'INSERT INTO "Kalunga".fornitore_prodotto (fornitore, prodotto, disponibilita, costo_unitario) VALUES ($1, $2, $3, $4)',
            [$fornitore, $prodotto, $disponibilita, $prezzo]);

        header("Location: fornitore.php?fornitore=$fornitore&msg=Prodotto aggiunto al fornitore");
        exit;
    }

    if (isset($_POST['delete_prodotto_fornito'])) {
        $fornitore = $_POST['fornitore_id'];
        $prodotto = (int)$_POST['prodotto_id'];

        pg_query_params($conn,
            'DELETE FROM "Kalunga".fornitore_prodotto WHERE fornitore=$1 AND prodotto=$2',
            [$fornitore, $prodotto]);

        header("Location: fornitore.php?fornitore=$fornitore&msg=Prodotto rimosso dal fornitore");
        exit;
    }
}
?>

<?php include('header.php') ?>

<?php if ($messaggio): ?>
  <div class="alert alert-success"><?= htmlspecialchars($messaggio) ?></div>
<?php endif; ?>

<?php if (!isset($_GET['fornitore'])): ?>
  <h2>Gestione Fornitori</h2>
  <table class="table table-hover">
    <thead>
      <tr>
        <th>Partita IVA</th>
        <th>Indirizzo</th>
        <th>Azioni</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $res = pg_query($conn, 'SELECT * FROM "Kalunga".fornitore ORDER BY partita_iva');
      while ($r = pg_fetch_assoc($res)) { ?>
        <tr>
          <td><?= htmlspecialchars($r['partita_iva']) ?></td>
          <td><?= htmlspecialchars($r['indirizzo']) ?></td>
          <td>
            <a href="fornitore.php?fornitore=<?= $r['partita_iva'] ?>" class="btn btn-sm btn-primary">Gestisci</a>
          </td>
        </tr>
      <?php } ?>
    </tbody>
  </table>

  <h3 class="mt-5">Aggiungi Nuovo Fornitore</h3>
  <form method="POST" class="mb-5">
    <div class="mb-3">
      <label for="piva" class="form-label">Partita IVA</label>
      <input type="text" class="form-control" id="piva" name="piva" required>
    </div>
    <div class="mb-3">
      <label for="indirizzo" class="form-label">Indirizzo</label>
      <input type="text" class="form-control" id="indirizzo" name="indirizzo" required>
    </div>
    <button type="submit" name="add_fornitore" class="btn btn-success">Aggiungi Fornitore</button>
  </form>

<?php else: ?>
  <?php
    $piva = $_GET['fornitore'];
    $q = pg_query_params($conn, 'SELECT * FROM "Kalunga".fornitore WHERE partita_iva=$1', [$piva]);
    $fornitore = pg_fetch_assoc($q);

    $prodotti_forniti = pg_query_params($conn,
        'SELECT fp.prodotto, p.nome, fp.disponibilita, fp.costo_unitario AS prezzo_acquisto
         FROM "Kalunga".fornitore_prodotto fp
         JOIN "Kalunga".prodotto p ON p.id = fp.prodotto
         WHERE fp.fornitore = $1
         ORDER BY p.nome', [$piva]);

    $prodotti = pg_query($conn, 'SELECT id, nome FROM "Kalunga".prodotto ORDER BY nome');
  ?>
  <h3 class="mt-5">Gestisci Fornitore <?= htmlspecialchars($piva) ?></h3>
  <p><strong>Indirizzo:</strong> <?= htmlspecialchars($fornitore['indirizzo']) ?></p>

  <h4 class="mt-4">Prodotti Forniti</h4>
  <table class="table table-hover">
    <thead>
      <tr>
        <th>Prodotto</th>
        <th>Disponibilità</th>
        <th>Prezzo Acquisto</th>
        <th>Azioni</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($pf = pg_fetch_assoc($prodotti_forniti)) { ?>
        <tr>
          <td><?= htmlspecialchars($pf['nome']) ?></td>
          <td><?= htmlspecialchars($pf['disponibilita']) ?></td>
          <td><?= htmlspecialchars($pf['prezzo_acquisto']) ?></td>
          <td>
            <form method="POST" class="d-inline">
              <input type="hidden" name="fornitore_id" value="<?= htmlspecialchars($piva) ?>">
              <input type="hidden" name="prodotto_id" value="<?= htmlspecialchars($pf['prodotto']) ?>">
              <button type="submit" name="delete_prodotto_fornito" class="btn btn-sm btn-danger"
                      onclick="return confirm('Eliminare questo prodotto dal fornitore?')">Elimina</button>
            </form>
          </td>
        </tr>
      <?php } ?>
    </tbody>
  </table>

  <h4 class="mt-4">Aggiungi Prodotto Fornito</h4>
  <form method="POST" class="mb-5">
    <input type="hidden" name="fornitore_id" value="<?= htmlspecialchars($piva) ?>">
    <div class="mb-3">
      <label for="prodotto_id" class="form-label">Prodotto</label>
      <select class="form-select" id="prodotto_id" name="prodotto_id" required>
        <?php while ($p = pg_fetch_assoc($prodotti)): ?>
          <option value="<?= htmlspecialchars($p['id']) ?>"><?= htmlspecialchars($p['nome']) ?> (ID <?= $p['id'] ?>)</option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="mb-3">
      <label for="disponibilita" class="form-label">Disponibilità</label>
      <input type="number" class="form-control" id="disponibilita" name="disponibilita" required>
    </div>
    <div class="mb-3">
      <label for="prezzo" class="form-label">Prezzo Acquisto</label>
      <input type="number" step="0.01" class="form-control" id="prezzo" name="prezzo" required>
    </div>
    <button type="submit" name="add_prodotto_fornito" class="btn btn-success">Aggiungi Prodotto</button>
  </form>

  <p><a href="fornitore.php" class="btn btn-secondary">Torna alla Lista Fornitori</a></p>
<?php endif; ?>

<?php include('footer.php') ?>
