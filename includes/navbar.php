<header class="topbar">
    <div>
        <div class="eyebrow">Sistem Informasi Posyandu & Deteksi Stunting</div>
        <h1><?= htmlspecialchars($page_title ?? "Dashboard") ?></h1>
    </div>
    <div class="profile">
        <div class="avatar"><?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?></div>
        <div>
            <strong><?= htmlspecialchars($user['name'] ?? 'Pengguna') ?></strong>
            <small><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $user['role'] ?? ''))) ?></small>
        </div>
    </div>
</header>
