<?php
session_start();

echo "<pre>SESSION: "; print_r($_SESSION); echo "</pre>";

include('../includes/check-auth.php');
include('../includes/check-gestore.php');
include('../includes/db.php');

$messaggio = $_GET['msg'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $nome = $_POST['nome'];
        $cognome = $_POST['cognome'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $cf = $_POST['cf'];
        $tipo = $_POST['tipo'];

        pg_query_params($conn,
            "INSERT INTO utente (nome, cognome, email, password, codice_fiscale, tipo) VALUES ($1, $2, $3, $4, $5, $6)",
            [$nome, $cognome, $email, $password, $cf, $tipo]);

        header("Location: utenti.php?msg=Utente aggiunto con successo");
        exit;
    }

    if (isset($_POST['update'])) {
        $id = (int)$_POST['id'];
        $nome = $_POST['nome_edit'];
        $cognome = $_POST['cognome_edit'];
        $cf = $_POST['cf_edit'];
        $tipo = $_POST['tipo_edit'];

        pg_query_params($conn,
            "UPDATE utente SET nome=$1, cognome=$2, codice_fiscale=$3, tipo=$4 WHERE id=$5",
            [$nome, $cognome, $cf, $tipo, $id]);

        header("Location: utenti.php?msg=Utente ID $id aggiornato con successo");
        exit;
    }

    if (isset($_POST['assegna_tessera'])) {
        $utente_id = (int)$_POST['utente_id'];
        $negozio_id = (int)$_POST['negozio_id'];

        pg_query_params($conn,
            "INSERT INTO tessera (saldo_punti, data_rilascio, negozio, utente) VALUES (0, current_date, $1, $2)",
            [$negozio_id, $utente_id]);

        header("Location: utenti.php?msg=Tessera assegnata a utente ID $utente_id");
        exit;
    }
}
?>

<?php include('header.php') ?>

<?php if ($messaggio): ?>
  <div class="alert alert-success"><?= htmlspecialchars($messaggio) ?></div>
<?php endif; ?>

<?php if (!isset($_GET['id'])): ?>
  <h2>Gestione Utenti</h2>
  <table class="table table-hover">
    <thead>
      <tr>
        <th>ID</th>
        <th>Nome</th>
        <th>Cognome</th>
        <th>Codice Fiscale</th>
        <th>Email</th>
        <th>ID Tessera</th>
        <th>Azioni</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $res = pg_query($conn, 
          "SELECT u.id, u.nome, u.cognome, u.codice_fiscale, u.email, t.id AS tessera_id
           FROM utente u
           LEFT JOIN tessera t ON u.id = t.utente
           ORDER BY u.id");
      while ($r = pg_fetch_assoc($res)) { ?>
        <tr>
          <td><?= htmlspecialchars($r['id']) ?></td>
          <td><?= htmlspecialchars($r['nome']) ?></td>
          <td><?= htmlspecialchars($r['cognome']) ?></td>
          <td><?= htmlspecialchars($r['codice_fiscale']) ?></td>
          <td><?= htmlspecialchars($r['email']) ?></td>
          <td><?= htmlspecialchars($r['tessera_id'] ?? '') ?></td>
          <td>
            <a href="utenti.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-primary">Gestisci</a>
          </td>
        </tr>
      <?php } ?>
    </tbody>
  </table>

  <h3 class="mt-5">Aggiungi Nuovo Utente</h3>
  <form method="POST" class="mb-5">
    <div class="mb-3">
      <label for="nome" class="form-label">Nome</label>
      <input type="text" class="form-control" id="nome" name="nome" required>
    </div>
    <div class="mb-3">
      <label for="cognome" class="form-label">Cognome</label>
      <input type="text" class="form-control" id="cognome" name="cognome" required>
    </div>
    <div class="mb-3">
      <label for="email" class="form-label">Email</label>
      <input type="email" class="form-control" id="email" name="email" required>
    </div>
    <div class="mb-3">
      <label for="password" class="form-label">Password</label>
      <input type="password" class="form-control" id="password" name="password" required>
    </div>
    <div class="mb-3">
      <label for="cf" class="form-label">Codice Fiscale</label>
      <input type="text" class="form-control" id="cf" name="cf" required>
    </div>
    <div class="mb-3">
      <label for="tipo" class="form-label">Tipo Utente</label>
      <select class="form-select" id="tipo" name="tipo" required>
        <option value="cliente">Cliente</option>
        <option value="gestore">Gestore</option>
      </select>
    </div>
    <button type="submit" name="add" class="btn btn-success">Aggiungi Utente</button>
  </form>

<?php else: ?>
  <?php
    $id = $_GET['id'];
    $q = pg_query_params($conn, "SELECT * FROM utente WHERE id=$1", [$id]);
    $utente = pg_fetch_assoc($q);
    $negozi = pg_query($conn, "SELECT id, indirizzo FROM negozio WHERE eliminato = FALSE ORDER BY id");
  ?>
  <h3 class="mt-5">Gestisci Utente ID <?= htmlspecialchars($id) ?></h3>
  <form method="POST" class="mb-4">
    <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
    <div class="mb-3">
      <label for="nome_edit" class="form-label">Nome</label>
      <input type="text" class="form-control" id="nome_edit" name="nome_edit"
             value="<?= htmlspecialchars($utente['nome']) ?>" required>
    </div>
    <div class="mb-3">
      <label for="cognome_edit" class="form-label">Cognome</label>
      <input type="text" class="form-control" id="cognome_edit" name="cognome_edit"
             value="<?= htmlspecialchars($utente['cognome']) ?>" required>
    </div>
    <div class="mb-3">
      <label for="cf_edit" class="form-label">Codice Fiscale</label>
      <input type="text" class="form-control" id="cf_edit" name="cf_edit"
             value="<?= htmlspecialchars($utente['codice_fiscale']) ?>" required>
    </div>
    <div class="mb-3">
      <label for="tipo_edit" class="form-label">Tipo Utente</label>
      <select class="form-select" id="tipo_edit" name="tipo_edit" required>
        <option value="cliente" <?= $utente['tipo'] === 'cliente' ? 'selected' : '' ?>>Cliente</option>
        <option value="gestore" <?= $utente['tipo'] === 'gestore' ? 'selected' : '' ?>>Gestore</option>
      </select>
    </div>
    <button type="submit" name="update" class="btn btn-warning">Salva Modifiche</button>
    <a href="utenti.php" class="btn btn-secondary">Indietro</a>
  </form>

  <h4>Assegna Tessera a questo Utente</h4>
  <form method="POST" class="mb-5">
    <input type="hidden" name="utente_id" value="<?= htmlspecialchars($id) ?>">
    <div class="mb-3">
      <label for="negozio_id" class="form-label">Negozi Disponibili</label>
      <select class="form-select" id="negozio_id" name="negozio_id" required>
        <?php while ($n = pg_fetch_assoc($negozi)): ?>
          <option value="<?= htmlspecialchars($n['id']) ?>">
            <?= htmlspecialchars($n['indirizzo']) ?> (ID <?= $n['id'] ?>)
          </option>
        <?php endwhile; ?>
      </select>
    </div>
    <button type="submit" name="assegna_tessera" class="btn btn-success">Assegna Tessera</button>
  </form>
<?php endif; ?>

<?php include('footer.php') ?>
