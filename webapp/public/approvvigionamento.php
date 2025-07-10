<?php
session_start();
include('../includes/check-auth.php');
include('../includes/check-gestore.php');
include('../includes/db.php');

$messaggio = $_GET['msg'] ?? '';

$negozi = pg_query($conn, 'SELECT id, indirizzo FROM negozio WHERE eliminato = FALSE ORDER BY id');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['negozio'])) {
    $negozio_id = (int)$_POST['negozio'];
    $data = $_POST['data'];

    foreach ($_POST['prodotto'] as $i => $prodotto_id) {
        $quantita = (int)$_POST['quantita'][$i];
        if ($quantita > 0) {
            pg_query_params($conn,
                'INSERT INTO approvvigionamento (negozio, prodotto, quantita, data_consegna)
                 VALUES ($1, $2, $3, $4)',
                [$negozio_id, $prodotto_id, $quantita, $data]);
        }
    }

    header("Location: approvvigionamento.php?negozio=$negozio_id&msg=Approvvigionamento effettuato con successo");
    exit;
}

$storico = [];
if (isset($_GET['negozio'])) {
    $negozio_id = (int)$_GET['negozio'];
    $storico_res = pg_query_params($conn,
        'SELECT a.data_consegna, p.nome, a.quantita, a.prezzo_unitario
        FROM approvvigionamento a
        JOIN prodotto p ON p.id = a.prodotto
        WHERE a.negozio = $1
        ORDER BY a.data_consegna DESC', [$negozio_id]);
    $storico = pg_fetch_all($storico_res) ?: [];
}

$prodotti = pg_query($conn, 'SELECT id, nome FROM prodotto ORDER BY nome');
?>
<?php include('header.php') ?>

<?php if ($messaggio): ?>
  <div class="alert alert-success"><?= htmlspecialchars($messaggio) ?></div>
<?php endif; ?>

<h2>Approvvigionamento</h2>
<form method="GET" class="mb-4">
  <div class="row">
    <div class="col-md-6">
      <label for="negozio" class="form-label">Seleziona Negozio</label>
      <select class="form-select" id="negozio" name="negozio" required onchange="this.form.submit()">
        <option value="">-- Scegli --</option>
        <?php while ($n = pg_fetch_assoc($negozi)): ?>
          <option value="<?= $n['id'] ?>" <?= (isset($_GET['negozio']) && $_GET['negozio'] == $n['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($n['indirizzo']) ?> (ID <?= $n['id'] ?>)
          </option>
        <?php endwhile; ?>
      </select>
    </div>
  </div>
</form>

<?php if (isset($_GET['negozio'])): ?>
  <h3>Storico Approvvigionamenti</h3>
  <table class="table table-hover mb-5">
    <thead>
      <tr>
        <th>Data Consegna</th>
        <th>Prodotto</th>
        <th>Quantità</th>
        <th>Prezzo</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($storico as $s): ?>
        <tr>
        <td><?= htmlspecialchars($s['data_consegna']) ?></td>
        <td><?= htmlspecialchars($s['nome']) ?></td>
        <td><?= htmlspecialchars($s['quantita']) ?></td>
        <td><?= htmlspecialchars($s['prezzo_unitario']) ?></td>

        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <h3>Effettua Nuovo Approvvigionamento</h3>
  <form method="POST" id="approvvForm">
    <input type="hidden" name="negozio" value="<?= htmlspecialchars($_GET['negozio']) ?>">

    <div class="row mb-3">
        <div class="col-md-3">
            <label for="data" class="form-label">Data Consegna</label>
            <input type="date" class="form-control" id="data" name="data" required value="<?= date('Y-m-d') ?>">
        </div>
    </div>

    <div id="prodottiContainer">
      <div class="row mb-3 prodottoRow">
        <div class="col-md-5">
          <label class="form-label">Prodotto</label>
          <select class="form-select" name="prodotto[]">
            <?php
                pg_result_seek($prodotti, 0);
                while ($p = pg_fetch_assoc($prodotti)): ?>
                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nome']) ?> (ID <?= $p['id'] ?>)</option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Quantità</label>
            <input type="number" class="form-control" name="quantita[]" min="1" required>
        </div>
      </div>
    </div>

    <button type="button" class="btn btn-secondary mb-3" onclick="aggiungiProdotto()">+ Aggiungi Prodotto</button>
    <br>
    <button type="submit" class="btn btn-success">Conferma Approvvigionamento</button>
  </form>

  <script>
    function aggiungiProdotto() {
      let container = document.getElementById('prodottiContainer')
      let nuovo = container.querySelector('.prodottoRow').cloneNode(true)
      nuovo.querySelector('input').value = ''
      container.appendChild(nuovo)
    }
  </script>
<?php endif; ?>

<?php include('footer.php') ?>
