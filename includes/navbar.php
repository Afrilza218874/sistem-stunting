<header class="topbar">
    <div>
        <div class="eyebrow">SISTEM INFORMASI KESEHATAN ANAK</div>
        <h1><?= htmlspecialchars($page_title ?? "Dashboard") ?></h1>
    </div>
    <div class="profile">
        <div class="avatar"><?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?></div>
        <div><strong><?= htmlspecialchars($user['name'] ?? 'Pengguna') ?></strong><small><?= htmlspecialchars(ucfirst($user['role'] ?? '')) ?></small></div>
    </div>
</header>
