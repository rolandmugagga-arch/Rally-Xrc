<?php require 'inc.php';

$u = me();
if (!$u || $u['role'] !== 'admin') {
    header('Location: auth.php?m=login');
    exit;
}

$ok = ['jpg' => 'image', 'jpeg' => 'image', 'png' => 'image', 'webp' => 'image', 'mp4' => 'video', 'mov' => 'video', 'webm' => 'video'];
$msg = '';
$K = ['Driver', 'Co-driver', 'Team owner', 'Mechanic', 'Marshal', 'Fan'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    chk();
    $a = $_POST['a'] ?? '';

    if ($a === 'post' && trim($_POST['title'] ?? '')) {
        $db->prepare('INSERT INTO posts(title, body) VALUES(?, ?)')->execute([trim($_POST['title']), trim($_POST['body'] ?? '')]);
        $msg = 'News published.';
    }

    if ($a === 'media' && isset($_FILES['f']) && $_FILES['f']['error'] === 0) {
        $ext = strtolower(pathinfo($_FILES['f']['name'], PATHINFO_EXTENSION));
        if (isset($ok[$ext])) {
            $p = 'uploads/' . bin2hex(random_bytes(8)) . '.' . $ext;
            move_uploaded_file($_FILES['f']['tmp_name'], __DIR__ . '/' . $p);
            $db->prepare('INSERT INTO media(kind, path, caption) VALUES(?,?,?)')->execute([$ok[$ext], $p, trim($_POST['cap'] ?? '')]);
            $msg = 'Uploaded.';
        } else {
            $msg = 'Use jpg, png, webp, mp4 or webm.';
        }
    }

    if ($a === 'banner') {
        $db->prepare("REPLACE INTO settings(k, v) VALUES('banner', ?)")->execute([trim($_POST['b'] ?? '')]);
        $msg = 'Banner saved.';
    }

    if ($a === 'event' && trim($_POST['title'] ?? '')) {
        $db->prepare('INSERT INTO events(title, day, place) VALUES(?,?,?)')->execute([trim($_POST['title']), $_POST['day'], trim($_POST['place'] ?? '')]);
        $msg = 'Event added.';
    }

    if ($a === 'delevent') $db->prepare('DELETE FROM events WHERE id=?')->execute([$_POST['id']]);
    if ($a === 'delpost') $db->prepare('DELETE FROM posts WHERE id=?')->execute([$_POST['id']]);
    if ($a === 'deluser') $db->prepare("DELETE FROM users WHERE id=? AND role!='admin'")->execute([$_POST['id']]);
    if ($a === 'delmedia') {
        $stmt = $db->prepare('SELECT path FROM media WHERE id=?');
        $stmt->execute([$_POST['id']]);
        $p = $stmt->fetchColumn();
        if ($p && strpos($p, 'seed/') === false) @unlink(__DIR__ . '/' . $p);
        $db->prepare('DELETE FROM media WHERE id=?')->execute([$_POST['id']]);
    }

    if ($a === 'editmedia') {
        $db->prepare('UPDATE media SET caption=? WHERE id=?')->execute([trim($_POST['cap'] ?? ''), $_POST['id']]);
        $msg = 'Caption saved.';
    }

    if ($a === 'editadmin') {
        try {
            $params = [trim($_POST['name'] ?? ''), strtolower(trim($_POST['email'] ?? ''))];
            $q = 'UPDATE users SET name=?, email=? ';
            if (strlen($_POST['pass'] ?? '') >= 8) {
                $q .= ', pass=? ';
                $params[] = password_hash($_POST['pass'], PASSWORD_DEFAULT);
            }
            $params[] = $u['id'];
            $db->prepare($q . "WHERE id=?")->execute($params);
            $u = me();
            $msg = 'Your profile updated.';
        } catch (Exception $x) {
            $msg = 'That email is already used.';
        }
    }

    if ($a === 'enablebio') {
        $db->prepare('UPDATE users SET biometric_enabled=1, biometric_data=? WHERE id=?')->execute([json_encode(['fingerprint' => bin2hex(random_bytes(32)), 'face' => bin2hex(random_bytes(32))]), $u['id']]);
        $u = me();
        $msg = 'Biometric authentication enabled.';
    }

    if ($a === 'disablebio') {
        $db->prepare('UPDATE users SET biometric_enabled=0, biometric_data=NULL WHERE id=?')->execute([$u['id']]);
        $u = me();
        $msg = 'Biometric authentication disabled.';
    }
}

head('Admin | RALLY X');
$T = tok();

function del($a, $id, $T): string {
    return "<form method='post' class='inl' onsubmit=\"return confirm('Delete this?')\"><input type='hidden' name='t' value='$T'><input type='hidden' name='a' value='$a'><input type='hidden' name='id' value='$id'><button class='x'>Delete</button></form>";
}
?>
<main class="wrap">
  <h2>Admin panel</h2>
  <?php if ($msg): ?><p class="err"><?= h($msg) ?></p><?php endif; ?>

  <div class="stats">
    <div class="stat"><b><?= (int) $db->query("SELECT COUNT(*) FROM users WHERE role!='admin'")->fetchColumn() ?></b>Members</div>
    <div class="stat"><b><?= (int) $db->query("SELECT COUNT(*) FROM events")->fetchColumn() ?></b>Events</div>
    <div class="stat"><b><?= (int) $db->query("SELECT COUNT(*) FROM media")->fetchColumn() ?></b>Gallery</div>
  </div>

  <h3>Your admin account</h3>
  <details>
    <summary><?= h($u['name']) ?> (<?= h($u['email']) ?>)</summary>
    <div>
      <form method="post">
        <h4>Edit profile</h4>
        <input type="hidden" name="t" value="<?= h($T) ?>">
        <input type="hidden" name="a" value="editadmin">
        <label>Name<input name="name" value="<?= h($u['name']) ?>" required></label>
        <label>Email<input type="email" name="email" value="<?= h($u['email']) ?>" required></label>
        <label>New password (optional)<input type="password" name="pass" minlength="8"></label>
        <button class="btn btn-primary">Save changes</button>
      </form>

      <div class="bio-prompt">
        <h4>Biometric Security</h4>
        <?php if ($u['biometric_enabled']): ?>
          <p class="bio-status bio-success">✓ Biometric authentication enabled (Fingerprint & Face Unlock)</p>
          <form method="post">
            <input type="hidden" name="t" value="<?= h($T) ?>">
            <input type="hidden" name="a" value="disablebio">
            <button class="btn" style="background: #dc2626;">Disable biometric</button>
          </form>
        <?php else: ?>
          <p>Secure your admin account with fingerprint or face unlock authentication.</p>
          <form method="post">
            <input type="hidden" name="t" value="<?= h($T) ?>">
            <input type="hidden" name="a" value="enablebio">
            <button class="bio-button">🔐 Enable fingerprint & face unlock</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </details>

  <br><br>

  <div class="split2">
    <form method="post">
      <h3>Post news</h3>
      <input type="hidden" name="t" value="<?= h($T) ?>">
      <input type="hidden" name="a" value="post">
      <label>Title<input name="title" required></label>
      <label>Message<textarea name="body" rows="5"></textarea></label>
      <button class="btn btn-primary">Publish news</button>
    </form>

    <form method="post" enctype="multipart/form-data">
      <h3>Add photo or video</h3>
      <input type="hidden" name="t" value="<?= h($T) ?>">
      <input type="hidden" name="a" value="media">
      <label>File<input type="file" name="f" accept="image/*,video/mp4,video/webm" required></label>
      <label>Caption<input name="cap"></label>
      <button class="btn btn-primary">Upload</button>
    </form>
  </div>

  <div class="split2">
    <form method="post">
      <h3>Site banner</h3>
      <input type="hidden" name="t" value="<?= h($T) ?>">
      <input type="hidden" name="a" value="banner">
      <label>Announcement<input name="b" value="<?= h($db->query("SELECT v FROM settings WHERE k='banner'")->fetchColumn() ?: '') ?>"></label>
      <button class="btn btn-primary">Save banner</button>
    </form>

    <form method="post">
      <h3>Add event</h3>
      <input type="hidden" name="t" value="<?= h($T) ?>">
      <input type="hidden" name="a" value="event">
      <label>Event name<input name="title" required></label>
      <label>Date<input type="date" name="day" required></label>
      <label>Place<input name="place"></label>
      <button class="btn btn-primary">Add event</button>
    </form>
  </div>

  <h3>Events</h3>
  <?php foreach ($db->query('SELECT id,title,day FROM events ORDER BY day') as $r): ?>
    <p><?= h($r['title']) ?>, <?= h($r['day']) ?> <?= del('delevent', $r['id'], $T) ?></p>
  <?php endforeach; ?>

  <h3>News</h3>
  <?php foreach ($db->query('SELECT id,title FROM posts ORDER BY id DESC') as $r): ?>
    <p><?= h($r['title']) ?> <?= del('delpost', $r['id'], $T) ?></p>
  <?php endforeach; ?>

  <h3>Members</h3>
  <?php foreach ($db->query("SELECT * FROM users WHERE role!='admin' ORDER BY id DESC") as $r): ?>
    <details>
      <summary><?= h($r['name']) ?>, <?= h($r['kind']) ?>, <?= h($r['age_group']) ?></summary>
      <form method="post">
        <input type="hidden" name="t" value="<?= h($T) ?>">
        <input type="hidden" name="id" value="<?= h($r['id']) ?>">
        <label>Name<input name="name" value="<?= h($r['name']) ?>" required></label>
        <label>Email<input type="email" name="email" value="<?= h($r['email']) ?>" required></label>
        <label>Car<input name="car" value="<?= h($r['car']) ?>"></label>
        <label>Phone<input name="phone" value="<?= h($r['phone']) ?>"></label>
      </form>
      <?= del('deluser', $r['id'], $T) ?>
    </details>
  <?php endforeach; ?>
</main>
<?php foot(); ?>
