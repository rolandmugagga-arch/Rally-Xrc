<?php require 'inc.php';

$m = $_GET['m'] ?? 'login';
$err = '';
$K = ['Driver', 'Co-driver', 'Team owner', 'Mechanic', 'Marshal', 'Fan'];

if ($m === 'out') {
    session_destroy();
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    chk();

    if ($m === 'register') {
        $n = trim($_POST['name'] ?? '');
        $e = strtolower(trim($_POST['email'] ?? ''));
        $p = $_POST['pass'] ?? '';

        if (!$n || !filter_var($e, FILTER_VALIDATE_EMAIL) || strlen($p) < 8) {
            $err = 'Enter your name, a valid email and a password of 8 or more characters.';
        } else {
            try {
                $db->prepare('INSERT INTO users(name, email, pass, kind, age_group, car, phone) VALUES(?,?,?,?,?,?,?)')->execute([
                    $n,
                    $e,
                    password_hash($p, PASSWORD_DEFAULT),
                    in_array($_POST['kind'] ?? '', $K) ? $_POST['kind'] : 'Fan',
                    ($_POST['age'] ?? '') === 'Youth' ? 'Youth' : 'Adult',
                    trim($_POST['car'] ?? ''),
                    trim($_POST['phone'] ?? '')
                ]);
                session_regenerate_id(true);
                $_SESSION['uid'] = (int) $db->lastInsertId();
                header('Location: index.php#drivers');
                exit;
            } catch (Exception $x) {
                $err = 'That email is already registered. Log in instead.';
            }
        }
    } else {
        $s = $db->prepare('SELECT * FROM users WHERE email = ?');
        $s->execute([strtolower(trim($_POST['email'] ?? ''))]);
        $u = $s->fetch(PDO::FETCH_ASSOC);

        if ($u && password_verify($_POST['pass'] ?? '', $u['pass'])) {
            session_regenerate_id(true);
            $_SESSION['uid'] = (int) $u['id'];
            header('Location: ' . ($u['role'] === 'admin' ? 'admin.php' : 'index.php'));
            exit;
        }
        $err = 'Wrong email or password.';
    }
}

head($m === 'register' ? 'Join RALLY X' : 'Log in');
?>
<main class="wrap narrow">
  <h2><?= $m === 'register' ? 'Join RALLY X' : 'Log in' ?></h2>
  <?php if ($err): ?><p class="err"><?= h($err) ?></p><?php endif; ?>
  <form method="post">
    <input type="hidden" name="t" value="<?= h(tok()) ?>">
    <?php if ($m === 'register'): ?>
      <label>Full name<input name="name" required></label>
      <label>I am a<select name="kind"><?php foreach ($K as $k): ?><option><?= h($k) ?></option><?php endforeach; ?></select></label>
      <label>League<select name="age"><option value="Youth">Youth</option><option value="Adult" selected>Adult</option></select></label>
      <label>Car or team (optional)<input name="car"></label>
      <label>Phone (optional, kept private)<input name="phone"></label>
    <?php endif; ?>
    <label>Email<input type="email" name="email" required></label>
    <label>Password<input type="password" name="pass" minlength="<?= $m === 'register' ? 8 : 1 ?>" required></label>
    <button class="btn btn-primary"><?= $m === 'register' ? 'Create account' : 'Log in' ?></button>
  </form>
  <p><?= $m === 'register' ? 'Already a member? <a href="auth.php?m=login">Log in</a>' : 'New here? <a href="auth.php?m=register">Join RALLY X</a>' ?></p>
</main>
<?php foot(); ?>
