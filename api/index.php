<?php
/**
 * Vercel Serverless Entrypoint & Front Controller
 * Menghandle routing aplikasi SiTumbuh saat dideploy di Vercel
 */

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = urldecode($uri);

// Route root / index
if ($uri === '/' || $uri === '' || $uri === '/index.php') {
    require __DIR__ . '/../index.php';
    exit;
}

$file = __DIR__ . '/..' . $uri;

// Cek apakah ada file PHP dengan nama yang sama (misal /login -> /login.php)
if (!file_exists($file) && file_exists($file . '.php')) {
    $file .= '.php';
}

if (file_exists($file) && is_file($file)) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

    if ($ext === 'php') {
        require $file;
        exit;
    }

    $mimes = [
        'json' => 'application/json',
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'svg'  => 'image/svg+xml',
        'ico'  => 'image/x-icon'
    ];

    if (isset($mimes[$ext])) {
        header('Content-Type: ' . $mimes[$ext]);
        readfile($file);
        exit;
    }
}

http_response_code(404);
echo "404 Not Found";
