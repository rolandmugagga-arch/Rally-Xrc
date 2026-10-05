<?php
session_name('rallyx');
session_start();

if (!is_dir(__DIR__ . '/uploads')) mkdir(__DIR__ . '/uploads', 0777, true);
if (!is_dir(__DIR__ . '/uploads/seed')) mkdir(__DIR__ . '/uploads/seed', 0777, true);
if (!is_dir(__DIR__ . '/data')) mkdir(__DIR__ . '/data', 0777, true);

$db = new PDO('sqlite:' . __DIR__ . '/data/site.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$db->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    pass TEXT NOT NULL,
    kind TEXT NOT NULL DEFAULT 'Fan',
    age_group TEXT NOT NULL DEFAULT 'Adult',
    car TEXT,
    phone TEXT,
    role TEXT NOT NULL DEFAULT 'member',
    biometric_enabled INTEGER DEFAULT 0,
    biometric_data TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);");

$db->exec("CREATE TABLE IF NOT EXISTS settings (
    k TEXT PRIMARY KEY,
    v TEXT NOT NULL
);");

$db->exec("CREATE TABLE IF NOT EXISTS posts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    body TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);");

$db->exec("CREATE TABLE IF NOT EXISTS events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    day TEXT NOT NULL,
    place TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);");

$db->exec("CREATE TABLE IF NOT EXISTS media (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    kind TEXT NOT NULL,
    path TEXT NOT NULL,
    caption TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);");

$seedCaptions = [
    'Wheel up on the pit stand',
    'Controllers out, cars on the slope',
    'Josh Rally Team meets RallyXRC',
    'Ford Fiesta in full livery'
];

foreach (['p1.svg', 'p2.svg', 'p3.svg', 'p4.svg'] as $i => $name) {
    $path = __DIR__ . '/uploads/seed/' . $name;
    if (!file_exists($path)) {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="800" viewBox="0 0 1200 800">
            <defs>
                <linearGradient id="bg" x1="0" x2="1" y1="0" y2="1">
                    <stop offset="0%" stop-color="#0f172a"/>
                    <stop offset="100%" stop-color="#f97316"/>
                </linearGradient>
            </defs>
            <rect width="1200" height="800" fill="url(#bg)"/>
            <circle cx="180" cy="180" r="90" fill="#facc15" opacity="0.8"/>
            <path d="M0,560 L420,240 L760,430 L1200,120 L1200,800 L0,800 Z" fill="#111827" opacity="0.75"/>
            <text x="580" y="420" text-anchor="middle" font-size="54" font-family="Arial, sans-serif" fill="#ffffff" font-weight="700">RALLY X</text>
            <text x="580" y="490" text-anchor="middle" font-size="26" font-family="Arial, sans-serif" fill="#fef3c7">Scene ' . ($i + 1) . '</text>
        </svg>';
        file_put_contents($path, $svg);
    }
}

if ((int) $db->query('SELECT COUNT(*) FROM settings')->fetchColumn() === 0) {
    $db->prepare("INSERT INTO settings(k, v) VALUES('banner', ?)")->execute([
        'Rally X season kickoff — sign up, meet the crew, and follow race week updates.'
    ]);
}

if ((int) $db->query('SELECT COUNT(*) FROM media')->fetchColumn() === 0) {
    foreach (['p1.svg', 'p2.svg', 'p3.svg', 'p4.svg'] as $i => $name) {
        $db->prepare("INSERT INTO media(kind, path, caption) VALUES('image', ?, ?)")->execute([
            'uploads/seed/' . $name,
            $seedCaptions[$i]
        ]);
    }
}

if ((int) $db->query('SELECT COUNT(*) FROM events')->fetchColumn() === 0) {
    $db->prepare("INSERT INTO events(title, day, place) VALUES(?, ?, ?)")->execute([
        'Moorland Sprint',
        date('Y-m-d', strtotime('+7 days')),
        'Ridgeway Park'
    ]);
    $db->prepare("INSERT INTO events(title, day, place) VALUES(?, ?, ?)")->execute([
        'Night Rally Challenge',
        date('Y-m-d', strtotime('+21 days')),
        'Cinder Valley'
    ]);
}

if ((int) $db->query('SELECT COUNT(*) FROM posts')->fetchColumn() === 0) {
    $db->prepare("INSERT INTO posts(title, body) VALUES(?, ?)")->execute([
        'Welcome to the season',
        'The crew is back on the road for a fresh rally calendar and a bigger community grid.'
    ]);
    $db->prepare("INSERT INTO posts(title, body) VALUES(?, ?)")->execute([
        'Service bay open',
        'Mechanics are taking bookings for prep, tune-ups and travel support ahead of the next event.'
    ]);
}

if ((int) $db->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn() === 0) {
    $db->prepare("INSERT INTO users(name, email, pass, kind, age_group, car, phone, role, biometric_enabled) VALUES(?,?,?,?,?,?,?,?,?)")->execute([
        'Roland Mugagga',
        'rolandmugagga@gmail.com',
        password_hash('Roland12', PASSWORD_DEFAULT),
        'Driver',
        'Adult',
        'Rally X',
        '+256700000000',
        'admin',
        1
    ]);
}

function tok(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function chk(): void {
    if (!isset($_POST['t']) || $_POST['t'] !== tok()) {
        http_response_code(403);
        exit('Invalid form token.');
    }
}

function h($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function me(): ?array {
    if (empty($_SESSION['uid'])) return null;
    global $db;
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['uid']]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function head(string $title = 'RALLY X'): void {
    $user = me();
    $nav = '<a href="index.php">Home</a><a href="#news">News</a><a href="#leagues">Leagues</a><a href="#drivers">Drivers</a><a href="#gallery">Gallery</a><a href="#events">Events</a><a href="#videos">Videos</a>';
    if ($user && $user['role'] === 'admin') $nav .= '<a href="admin.php">Admin</a>';
    if ($user) $nav .= '<a href="auth.php?m=out">Log out</a>';
    else $nav .= '<a href="auth.php?m=login">Log in</a>';

    echo '<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>' . h($title) . '</title>
<style>
:root {
  --bg: #0a0d12; --panel: #101822; --panel-2: #121d2a; --text: #f7f7f7; --muted: #b5c0cc; --accent: #f26b1d; --accent-2: #ff9d44; --line: rgba(255,255,255,0.08); --card-shadow: 0 24px 50px rgba(0,0,0,0.25);
}
* { box-sizing: border-box; }
html { scroll-behavior: smooth; }
body { margin: 0; font-family: "Inter", sans-serif; background: radial-gradient(circle at top left, rgba(242, 107, 29, 0.18), transparent 28%), var(--bg); color: var(--text); }
img { max-width: 100%; display: block; }
a { color: inherit; text-decoration: none; }
.wrap { max-width: 1180px; margin: 0 auto; padding: 0 20px 60px; }
.narrow { max-width: 560px; }
.container { width: min(1180px, calc(100% - 32px)); margin: 0 auto; }
.site-header { position: sticky; top: 0; z-index: 50; background: rgba(10, 13, 18, 0.7); backdrop-filter: blur(12px); border-bottom: 1px solid var(--line); }
.nav { min-height: 78px; display: flex; align-items: center; justify-content: space-between; gap: 20px; }
.brand { font-size: 2rem; font-weight: 900; letter-spacing: 0.12em; text-transform: uppercase; }
.brand span { color: var(--accent); }
.main-nav { display: flex; align-items: center; gap: 24px; font-size: 0.95rem; font-weight: 600; color: var(--muted); }
.main-nav a:hover { color: var(--text); }
.hero { padding: 80px 0 36px; }
.hero-grid { display: grid; grid-template-columns: 1.2fr 0.8fr; align-items: center; gap: 36px; }
.eyebrow { margin: 0 0 12px; color: var(--accent-2); text-transform: uppercase; letter-spacing: 0.12em; font-weight: 700; font-size: 0.8rem; }
.hero-copy h1 { margin: 0; font-size: clamp(2.8rem, 5vw, 5.2rem); line-height: 0.96; letter-spacing: -0.04em; text-transform: uppercase; }
.lead { max-width: 620px; margin-top: 20px; font-size: 1.12rem; line-height: 1.7; color: var(--muted); }
.cta-row { display: flex; flex-wrap: wrap; gap: 16px; margin-top: 28px; }
.btn, button { display: inline-flex; align-items: center; justify-content: center; min-height: 52px; padding: 0 22px; border-radius: 999px; font-weight: 800; letter-spacing: 0.02em; transition: transform 150ms ease, box-shadow 150ms ease; border: none; cursor: pointer; font-family: inherit; }
.btn:hover, button:hover { transform: translateY(-1px); }
.btn-primary { background: linear-gradient(135deg, var(--accent), var(--accent-2)); color: #fff; box-shadow: var(--card-shadow); }
.btn-ghost { border: 1px solid var(--line); background: rgba(255,255,255,0.02); color: var(--text); }
.x { background: #374151; color: #fff; border: 0; border-radius: 10px; padding: 8px 12px; font-weight: 700; cursor: pointer; }
.err { background: rgba(248,113,113,0.12); border: 1px solid rgba(248,113,113,0.4); color: #fecaca; border-radius: 12px; padding: 12px 14px; margin: 12px 0 18px; }
.banner { background: rgba(249,115,22,0.1); border: 1px solid rgba(249,115,22,0.3); color: #fef3c7; border-radius: 18px; padding: 14px 18px; margin: 24px 0; font-weight: 600; }
form { display: grid; gap: 14px; background: rgba(17,24,39,0.85); border: 1px solid var(--line); border-radius: 18px; padding: 20px; box-shadow: var(--card-shadow); }
label { display: grid; gap: 8px; color: var(--muted); font-weight: 700; }
input, textarea, select { width: 100%; border: 1px solid #374151; background: rgba(15,23,42,0.8); color: var(--text); border-radius: 10px; padding: 10px 12px; font: inherit; }
textarea { min-height: 120px; resize: vertical; }
.stats, .grid, .split2 { display: grid; gap: 20px; }
.stats { grid-template-columns: repeat(3, minmax(0,1fr)); margin: 30px 0 10px; }
.stat { background: rgba(31,41,55,0.9); border: 1px solid var(--line); border-radius: 18px; padding: 22px 18px; text-align: center; }
.stat b { display: block; font-size: 2rem; margin-bottom: 8px; color: #fff; }
.inl { display: inline-block; }
details { background: rgba(17,24,39,0.8); border-radius: 14px; border: 1px solid var(--line); padding: 12px 16px; }
summary { cursor: pointer; font-weight: 700; }
.bio-prompt { display: grid; gap: 14px; padding: 20px; background: rgba(242, 107, 29, 0.1); border: 1px solid rgba(242, 107, 29, 0.3); border-radius: 18px; margin: 20px 0; }
.bio-button { background: linear-gradient(135deg, #f97316, #facc15); color: #000; font-weight: 800; padding: 14px 20px; border-radius: 12px; border: 0; cursor: pointer; margin-top: 12px; }
.bio-status { padding: 12px; border-radius: 8px; text-align: center; font-weight: 700; }
.bio-success { background: rgba(34, 197, 94, 0.15); border: 1px solid rgba(34, 197, 94, 0.4); color: #86efac; }
.bio-error { background: rgba(248, 113, 113, 0.12); border: 1px solid rgba(248, 113, 113, 0.4); color: #fecaca; }
@media (max-width: 980px) { .hero-grid, .split2 { grid-template-columns: 1fr; } }
@media (max-width: 720px) { .main-nav { display: none; } .nav { flex-direction: column; align-items: flex-start; } }
</style>
</head>
<body>
<header class="site-header">
  <div class="wrap nav">
    <a class="brand" href="index.php">RALLY <span>X</span></a>
    <nav class="main-nav" aria-label="Main navigation">' . $nav . '</nav>
  </div>
</header>';
}

function foot(): void {
    echo '</body></html>';
}
?>
