<?php
session_start();


include('../includes/check-auth.php');
include('../includes/check-gestore.php');
include('../includes/db.php');

$messaggio = $_GET['msg'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $nome = $_POST['nome'];
        $descrizione = $_POST['descrizione'] ?: null;

        pg_query_params($conn,
            "INSERT INTO prodotto (nome, descrizione) VALUES ($1, $2)",
            [$nome, $descrizione]);

        header("Location: prodotto.php?msg=Prodotto aggiunto con successo");
        exit;
    }

    if (isset($_POST['update'])) {
        $id = (int)$_POST['id'];
        $nome = $_POST['nome_edit'];
        $descrizione = $_POST['descrizione_edit'] ?: null;

        pg_query_params($conn,
            "UPDATE prodotto SET nome=$1, descrizione=$2 WHERE id=$3",
            [$nome, $descrizione, $id]);

        header("Location: prodotto.php?msg=Prodotto ID $id aggiornato con successo");
        exit;
    }
}
?>

<?php include('header.php') ?>

<?php if ($messaggio): ?>
  <div class="alert alert-success"><?= htmlspecialchars($messaggio) ?></div>
<?php endif; ?>

<?php if (!isset($_GET['id'])): ?>
  <h2>Gestione Prodotti</h2>
  <table class="table table-hover">
    <thead>
      <tr>
        <th>ID</th>
        <th>Nome</th>
        <th>Descrizione</th>
        <th>Azioni</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $res = pg_query($conn, "SELECT * FROM prodotto ORDER BY id");
      while ($r = pg_fetch_assoc($res)) { ?>
        <tr>
          <td><?= htmlspecialchars($r['id']) ?></td>
          <td><?= htmlspecialchars($r['nome']) ?></td>
          <td><?= htmlspecialchars($r['descrizione'] ?? '') ?></td>
          <td>
            <a href="prodotto.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-primary">Gestisci</a>
          </td>
        </tr>
      <?php } ?>
    </tbody>
  </table>

  <h3 class="mt-5">Aggiungi Nuovo Prodotto</h3>
  <form method="POST" class="mb-5">
    <div class="mb-3">
      <label for="nome" class="form-label">Nome</label>
      <input type="text" class="form-control" id="nome" name="nome" required>
    </div>
    <div class="mb-3">
      <label for="descrizione" class="form-label">Descrizione</label>
      <textarea class="form-control" id="descrizione" name="descrizione" rows="3"></textarea>
    </div>
    <button type="submit" name="add" class="btn btn-success">Aggiungi Prodotto</button>
  </form>

<?php else: ?>
  <?php
    $id = $_GET['id'];
    $q = pg_query_params($conn, "SELECT * FROM prodotto WHERE id=$1", [$id]);
    $prodotto = pg_fetch_assoc($q);
  ?>
  <h3 class="mt-5">Gestisci Prodotto ID <?= htmlspecialchars($id) ?></h3>
  <form method="POST" class="mb-5">
    <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
    <div class="mb-3">
      <label for="nome_edit" class="form-label">Nome</label>
      <input type="text" class="form-control" id="nome_edit" name="nome_edit"
             value="<?= htmlspecialchars($prodotto['nome']) ?>" required>
    </div>
    <div class="mb-3">
      <label for="descrizione_edit" class="form-label">Descrizione</label>
      <textarea class="form-control" id="descrizione_edit" name="descrizione_edit" rows="3"><?= htmlspecialchars($prodotto['descrizione'] ?? '') ?></textarea>
    </div>
    <button type="submit" name="update" class="btn btn-warning">Salva Modifiche</button>
    <a href="prodotto.php" class="btn btn-secondary">Indietro</a>
  </form>
<?php endif; ?>

<?php include('footer.php') ?>
