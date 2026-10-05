<?php require 'inc.php';

$banner = $db->query("SELECT v FROM settings WHERE k='banner'")->fetchColumn() ?: '';
$events = $db->query('SELECT * FROM events ORDER BY day ASC LIMIT 6')->fetchAll(PDO::FETCH_ASSOC);
$posts = $db->query('SELECT * FROM posts ORDER BY id DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
$media = $db->query('SELECT * FROM media ORDER BY id DESC LIMIT 8')->fetchAll(PDO::FETCH_ASSOC);
$members = $db->query("SELECT * FROM users WHERE role!='admin' ORDER BY id DESC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);

head('RALLY X');
?>
<main>
  <?php if ($banner): ?><div class="container"><div class="banner"><?= h($banner) ?></div></div><?php endif; ?>

  <section class="hero">
    <div class="container hero-grid">
      <div class="hero-copy">
        <p class="eyebrow">Official RC rally game of Uganda</p>
        <h1>Every driver. One stage. RALLY X.</h1>
        <p class="lead">Register your car, find your rivals and race on the dirt, from first-time youths to veteran drivers.</p>
        <div class="cta-row">
          <a href="auth.php?m=register" class="btn btn-primary">Register now</a>
          <a href="#gallery" class="btn btn-ghost">See the action</a>
        </div>
      </div>
      <div class="hero-visual" aria-label="Rally X action"><div class="hero-card"><span class="badge">Stage 17</span><h3>Dust. Speed. Glory.</h3></div></div>
    </div>
  </section>

  <section id="news" class="section"><div class="container"><div class="section-heading"><p class="eyebrow">Latest</p><h2>Latest news</h2></div><div class="news-grid"><?php foreach ($posts as $post): ?><article class="news-card"><span class="meta"><?= h($post['created_at']) ?></span><h3><?= h($post['title']) ?></h3><p><?= nl2br(h($post['body'])) ?></p></article><?php endforeach; ?></div></div></section>

  <section id="leagues" class="section alt"><div class="container"><div class="section-heading"><p class="eyebrow">Leagues</p><h2>Ready to race?</h2></div><div class="league-grid"><article class="league-card"><h3>Youth league</h3><p>Under 18? Bring a car, learn stage craft and race alongside friends. Mechanics and marshals welcome.</p><a href="auth.php?m=register&age=Youth" class="link-btn">Join as youth</a></article><article class="league-card"><h3>Adult league</h3><p>Teams, sponsors and seasoned drivers compete for the top step. Link your team and show your livery.</p><a href="auth.php?m=register&age=Adult" class="link-btn">Join as adult</a></article></div></div></section>

  <section id="drivers" class="section"><div class="container"><div class="section-heading"><p class="eyebrow">The grid</p><h2>Drivers</h2></div><div class="drivers-grid"><?php foreach ($members as $member): ?><div class="driver-card"><div class="driver-avatar orange"></div><h3><?= h($member['name']) ?></h3><p><?= h($member['kind']) ?> • <?= h($member['car'] ?: 'Team profile pending') ?></p></div><?php endforeach; ?></div></div></section>

  <section id="events" class="section alt"><div class="container"><div class="section-heading"><p class="eyebrow">Race calendar</p><h2>Events</h2></div><div class="event-list"><?php foreach ($events as $event): ?><div class="event-item"><div class="event-date"><span><?= date('d', strtotime($event['day'])) ?></span><small><?= date('M', strtotime($event['day'])) ?></small></div><div class="event-copy"><h3><?= h($event['title']) ?></h3><p><?= h($event['day']) ?> • <?= h($event['place'] ?: 'TBC') ?></p></div></div><?php endforeach; ?></div></div></section>

  <section id="gallery" class="section"><div class="container"><div class="section-heading"><p class="eyebrow">Moments</p><h2>Gallery</h2></div><div class="gallery-grid"><?php foreach ($media as $item): ?><figure class="gallery-item"><?php if ($item['kind'] === 'image'): ?><img src="<?= h($item['path']) ?>" alt="<?= h($item['caption']) ?>" /><?php else: ?><video controls src="<?= h($item['path']) ?>"></video><?php endif; ?><figcaption><?= h($item['caption']) ?></figcaption></figure><?php endforeach; ?></div></div></section>

  <section id="videos" class="section alt"><div class="container"><div class="section-heading"><p class="eyebrow">Highlights</p><h2>Videos</h2></div><div class="video-grid"><div class="video-card"><div class="video-thumb" style="background: linear-gradient(rgba(0,0,0,0.28), rgba(0,0,0,0.42)), url('https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=1100&q=80') center/cover;"></div><p>RALLY X, the official RC rally game of Uganda. Drivers, teams and fans, one grid.</p></div><div class="video-card"><div class="video-thumb" style="background: linear-gradient(rgba(0,0,0,0.28), rgba(0,0,0,0.42)), url('https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?auto=format&fit=crop&w=1100&q=80') center/cover;"></div><p>Dust on the open stage. Pressure in the corner. Timing in the line.</p></div></div></div></section>
</main>
<?php foot(); ?>
