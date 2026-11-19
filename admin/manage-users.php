<?php
require_once __DIR__ . '/../includes/layout.php';

$u = need('admin');
$adminId = (int)$u['id'];

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['replate_csrf'])) {
    $_SESSION['replate_csrf'] = bin2hex(random_bytes(32));
}

$msg = $_SESSION['user_msg'] ?? '';
unset($_SESSION['user_msg']);

/* Handle user actions */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf'] ?? '';

    if (!hash_equals($_SESSION['replate_csrf'], $token)) {
        http_response_code(403);
        exit('Invalid security token. Please refresh the page.');
    }

    $userId = (int)($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($userId <= 0 || $userId === $adminId) {
        $_SESSION['user_msg'] = 'Invalid user selection.';
    } else {

        // Admin accounts cannot be modified here.
        $target = q(
            "SELECT id, role, status FROM users WHERE id=?",
            [$userId]
        )->fetch();

        if (!$target || $target['role'] === 'admin') {
            $_SESSION['user_msg'] = 'This account cannot be modified.';
        } else {

            if ($action === 'verify' && $target['status'] === 'pending') {

                q(
                    "UPDATE users SET status='verified'
                     WHERE id=? AND status='pending' AND role <> 'admin'",
                    [$userId]
                );

                $_SESSION['user_msg'] = 'User verified successfully.';

            } elseif ($action === 'block' && $target['status'] !== 'blocked') {

                q(
                    "UPDATE users SET status='blocked'
                     WHERE id=? AND role <> 'admin'",
                    [$userId]
                );

                $_SESSION['user_msg'] = 'User blocked successfully.';

            } elseif ($action === 'unblock' && $target['status'] === 'blocked') {

                q(
                    "UPDATE users SET status='verified'
                     WHERE id=? AND status='blocked' AND role <> 'admin'",
                    [$userId]
                );

                $_SESSION['user_msg'] = 'User activated successfully.';

            } else {
                $_SESSION['user_msg'] = 'Invalid action for this account.';
            }
        }
    }

    header('Location: ' . BASE . '/admin/manage-users.php');
    exit;
}

/* Search and filter */
$search = trim($_GET['search'] ?? '');
$role = $_GET['role'] ?? '';
$status = $_GET['status'] ?? '';

$roles = ['donor', 'receiver', 'volunteer', 'admin'];
$statuses = ['verified', 'pending', 'blocked'];

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(name LIKE ? OR email LIKE ? OR location LIKE ?)";
    $term = "%$search%";
    array_push($params, $term, $term, $term);
}

if (in_array($role, $roles, true)) {
    $where[] = "role = ?";
    $params[] = $role;
}

if (in_array($status, $statuses, true)) {
    $where[] = "status = ?";
    $params[] = $status;
}

$sql = "SELECT id, name, email, role, location, status, created
        FROM users";

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY id DESC LIMIT 200";

$users = q($sql, $params)->fetchAll();

/* Statistics */
$total = (int)q("SELECT COUNT(*) FROM users")->fetchColumn();
$donors = (int)q("SELECT COUNT(*) FROM users WHERE role='donor'")->fetchColumn();
$receivers = (int)q("SELECT COUNT(*) FROM users WHERE role='receiver'")->fetchColumn();
$volunteers = (int)q("SELECT COUNT(*) FROM users WHERE role='volunteer'")->fetchColumn();
$pending = (int)q("SELECT COUNT(*) FROM users WHERE status='pending'")->fetchColumn();

top('Manage Users', 'Manage Users');
?>

<style>
.user-wrap {
    max-width:1200px;
    margin:auto;
    padding:25px 20px;
}
.user-head {margin-bottom:22px}
.user-head h1 {margin:0 0 7px}
.user-head p {color:#718078;margin:0}

.user-stats {
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(150px,1fr));
    gap:14px;
    margin:22px 0;
}
.user-box {
    background:white;
    border:1px solid #e5eee6;
    border-radius:13px;
    padding:17px;
}
.user-box h2 {margin:0 0 5px;font-size:27px}
.user-box p {margin:0;color:#718078;font-size:13px}

.user-panel {
    background:white;
    padding:20px;
    border:1px solid #e5eee6;
    border-radius:15px;
}
.user-filter {
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    margin:18px 0;
}
.user-filter input,
.user-filter select {
    padding:11px;
    border:1px solid #dce6dd;
    border-radius:8px;
    min-width:160px;
}
.user-filter button {
    padding:11px 20px;
    background:#176b35;
    color:white;
    border:0;
    border-radius:8px;
    cursor:pointer;
}
.user-table-wrap {overflow-x:auto}
.user-table {
    width:100%;
    border-collapse:collapse;
    min-width:900px;
}
.user-table th,
.user-table td {
    padding:13px;
    text-align:left;
    border-bottom:1px solid #edf1ed;
    font-size:13px;
}
.user-table th {
    background:#f4f7f2;
    color:#52665a;
}
.role-tag,.status-tag {
    display:inline-block;
    padding:5px 9px;
    border-radius:20px;
    background:#eaf5ea;
    color:#176b35;
    font-size:11px;
    font-weight:bold;
}
.status-pending {background:#fff4d9;color:#956600}
.status-blocked {background:#ffe8e8;color:#b42318}

.action-form {display:inline-block;margin:2px}
.action-btn {
    padding:7px 10px;
    border:0;
    border-radius:6px;
    cursor:pointer;
    font-size:11px;
    font-weight:bold;
}
.btn-verify,.btn-unblock {background:#e5f5e8;color:#176b35}
.btn-block {background:#ffe8e8;color:#b42318}
.user-message {
    background:#eaf5ea;
    color:#176b35;
    padding:12px;
    border-radius:8px;
    margin-bottom:15px;
}
.user-empty {text-align:center;padding:30px;color:#718078}

@media(max-width:600px) {
    .user-wrap {padding:15px 10px}
    .user-panel {padding:12px}
}
</style>

<div class="user-wrap">

    <div class="user-head">
        <h1>👥 Manage Users</h1>
        <p>View, verify and manage RePlate platform accounts.</p>
    </div>

    <?php if ($msg): ?>
        <div class="user-message"><?=e($msg)?></div>
    <?php endif; ?>

    <div class="user-stats">

        <div class="user-box">
            <h2><?=number_format($total)?></h2>
            <p>Total Users</p>
        </div>

        <div class="user-box">
            <h2><?=$donors?></h2>
            <p>Donors</p>
        </div>

        <div class="user-box">
            <h2><?=$receivers?></h2>
            <p>Receivers</p>
        </div>

        <div class="user-box">
            <h2><?=$volunteers?></h2>
            <p>Volunteers</p>
        </div>

        <div class="user-box">
            <h2><?=$pending?></h2>
            <p>Pending Verification</p>
        </div>

    </div>

    <div class="user-panel">

        <h2>All Users</h2>

        <form method="GET" class="user-filter">

            <input
                type="text"
                name="search"
                placeholder="Search name, email or location"
                value="<?=e($search)?>"
            >

            <select name="role">
                <option value="">All Roles</option>
                <?php foreach ($roles as $r): ?>
                    <option value="<?=e($r)?>" <?=$role===$r?'selected':''?>>
                        <?=e(ucfirst($r))?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="status">
                <option value="">All Status</option>
                <?php foreach ($statuses as $s): ?>
                    <option value="<?=e($s)?>" <?=$status===$s?'selected':''?>>
                        <?=e(ucfirst($s))?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit">Search</button>

            <a href="<?=BASE?>/admin/manage-users.php"
               style="padding:11px;color:#176b35">
                Clear
            </a>

        </form>

        <div class="user-table-wrap">

        <?php if ($users): ?>

            <table class="user-table">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach ($users as $user): ?>

                    <tr>
                        <td>#<?= (int)$user['id'] ?></td>

                        <td><b><?=e($user['name'] ?? '')?></b></td>

                        <td><?=e($user['email'] ?? '')?></td>

                        <td>
                            <span class="role-tag">
                                <?=e(ucfirst($user['role'] ?? ''))?>
                            </span>
                        </td>

                        <td><?=e($user['location'] ?? '—')?></td>

                        <td>
                            <span class="status-tag status-<?=e($user['status'] ?? '')?>">
                                <?=e(ucfirst($user['status'] ?? 'unknown'))?>
                            </span>
                        </td>

                        <td><?=e($user['created'] ?? '—')?></td>

                        <td>

                            <?php if ($user['role'] !== 'admin'): ?>

                                <?php if ($user['status'] === 'pending'): ?>
                                    <form method="POST" class="action-form">
                                        <input type="hidden" name="csrf"
                                            value="<?=e($_SESSION['replate_csrf'])?>">
                                        <input type="hidden" name="user_id"
                                            value="<?=(int)$user['id']?>">
                                        <button class="action-btn btn-verify"
                                                name="action" value="verify">
                                            Verify
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <?php if ($user['status'] === 'blocked'): ?>
                                    <form method="POST" class="action-form">
                                        <input type="hidden" name="csrf"
                                            value="<?=e($_SESSION['replate_csrf'])?>">
                                        <input type="hidden" name="user_id"
                                            value="<?=(int)$user['id']?>">
                                        <button class="action-btn btn-unblock"
                                                name="action" value="unblock">
                                            Activate
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" class="action-form"
                                          onsubmit="return confirm('Are you sure you want to block this user?')">
                                        <input type="hidden" name="csrf"
                                            value="<?=e($_SESSION['replate_csrf'])?>">
                                        <input type="hidden" name="user_id"
                                            value="<?=(int)$user['id']?>">
                                        <button class="action-btn btn-block"
                                                name="action" value="block">
                                            Block
                                        </button>
                                    </form>
                                <?php endif; ?>

                            <?php else: ?>
                                <span>Protected Admin</span>
                            <?php endif; ?>

                        </td>
                    </tr>

                <?php endforeach; ?>

                </tbody>
            </table>

        <?php else: ?>

            <div class="user-empty">No users found.</div>

        <?php endif; ?>

        </div>
    </div>
</div>

<?php bottom(); ?>