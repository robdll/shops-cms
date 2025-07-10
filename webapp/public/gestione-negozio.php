<?php
session_start();
if (!isset($_SESSION['email']) || $_SESSION['tipo'] !== 'gestore') {
    header('Location: index.php');
    exit;
}
include('../includes/db.php');

$messaggio = '';

if (isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    pg_query_params($conn, "UPDATE negozio SET eliminato = TRUE WHERE id = $1", [$delete_id]);
    $messaggio = "Negozio ID $delete_id eliminato.";
}
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
        <th>Azioni</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $res = pg_query($conn, "SELECT * FROM negozio ORDER BY id");
      while ($r = pg_fetch_assoc($res)) { ?>
        <tr>
          <td><?= htmlspecialchars($r['id']) ?></td>
          <td><?= htmlspecialchars($r['indirizzo']) ?></td>
          <td><?= htmlspecialchars($r['orario_apertura']) ?></td>
          <td><?= htmlspecialchars($r['orario_chiusura']) ?></td>
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

<?php else: ?>
  <?php
    $id = $_GET['id'];
    $q = pg_query_params($conn, "SELECT * FROM negozio WHERE id=$1", [$id]);
    $negozio = pg_fetch_assoc($q);
  ?>
  <h2>Gestione Negozio ID <?= htmlspecialchars($id) ?></h2>
  <p><strong><?= htmlspecialchars($negozio['indirizzo']) ?></strong>
     (<?= htmlspecialchars($negozio['orario_apertura']) ?> - <?= htmlspecialchars($negozio['orario_chiusura']) ?>)</p>

  <!-- puoi aggiungere un form di modifica qui -->
  <p class="mt-4"><a href="gestione-negozio.php" class="btn btn-secondary">Torna alla Lista Negozi</a></p>

<?php endif; ?>

<?php include('footer.php') ?>
