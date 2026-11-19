
<?php

/* =================================
   REPLATE DATABASE CONNECTION
================================= */

define('DB_HOST', 'localhost');
define('DB_NAME', 'replate');
define('DB_USER', 'root');
define('DB_PASS', '');

define('BASE', '/Replate');

try {

    $pdo = new PDO(
        "mysql:host=" . DB_HOST .
        ";dbname=" . DB_NAME .
        ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

} catch (PDOException $e) {

    error_log($e->getMessage());

    die(
        "Database connection failed. Please check XAMPP and database settings."
    );

}


/* =================================
   SESSION
================================= */

if (session_status() === PHP_SESSION_NONE) {

    session_start();

}


/* =================================
   DATABASE QUERY
================================= */

function q($sql, $params = []) {

    global $pdo;

    $stmt = $pdo->prepare($sql);

    $stmt->execute($params);

    return $stmt;

}


/* =================================
   SECURITY / ESCAPE
================================= */

function e($value) {

    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );

}


/* =================================
   CURRENT USER
================================= */

function me() {

    return $_SESSION['u'] ?? null;

}


/* =================================
   ROLE AUTHENTICATION
================================= */

function need($role) {

    $user = me();

    if (
        !$user ||
        !is_array($user) ||
        empty($user['id']) ||
        ($user['role'] ?? '') !== $role
    ) {

        header('Location: ' . BASE . '/login.php');

        exit;

    }

    // Return authenticated user information
    return $user;

}


/* =================================
   ROLE CHECK
================================= */

function hasRole($role) {

    $user = me();

    return (
        is_array($user) &&
        ($user['role'] ?? '') === $role
    );

}


/* =================================
   FOOD IMAGE URL
   WEBP PREFERRED OVER SVG
================================= */

function img($f) {

    $folder = __DIR__ . '/../assets/images/foods/';

    $f = basename(trim((string)$f));

    if ($f === '') {

        $f = 'plain-steamed-rice.svg';

    }

    // Prefer WEBP when SVG exists
    if (
        strtolower(pathinfo($f, PATHINFO_EXTENSION)) === 'svg'
    ) {

        $webp = pathinfo($f, PATHINFO_FILENAME) . '.webp';

        if (is_file($folder . $webp)) {

            $f = $webp;

        }

    }

    return BASE .
        '/assets/images/foods/' .
        rawurlencode($f);

}


/* =================================
   DELIVERED MEALS COUNT
================================= */

function meals() {

    try {

        $result = q("
            SELECT COALESCE(SUM(quantity), 0)
            FROM requests
            WHERE status = 'delivered'
        ");

        return (int)$result->fetchColumn();

    } catch (PDOException $e) {

        error_log(
            'Meals counter: ' . $e->getMessage()
        );

        return 0;

    }

}

?>
