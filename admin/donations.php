
<?php
require_once __DIR__ . '/../includes/layout.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$u = need('admin');

$statuses = [
    'draft',
    'scheduled',
    'available',
    'reserved',
    'completed',
    'cancelled'
];

$msg = '';
$msgType = 'success';

/* =================================
   CSRF TOKEN
================================= */

if (empty($_SESSION['replate_csrf'])) {
    $_SESSION['replate_csrf'] = bin2hex(random_bytes(32));
}

/* =================================
   UPDATE DONATION STATUS
================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';

    if (!hash_equals($_SESSION['replate_csrf'], $token)) {

        $msg = 'Invalid security token. Please refresh and try again.';
        $msgType = 'error';

    } elseif ($id < 1 || !in_array($status, $statuses, true)) {

        $msg = 'Invalid donation information.';
        $msgType = 'error';

    } else {

        try {

            $check = q(
                "SELECT id FROM donations WHERE id=?",
                [$id]
            )->fetchColumn();

            if (!$check) {

                $msg = 'Donation not found.';
                $msgType = 'error';

            } else {

                q(
                    "UPDATE donations SET status=? WHERE id=?",
                    [$status, $id]
                );

                // Verify database update
                $updatedStatus = q(
                    "SELECT status FROM donations WHERE id=?",
                    [$id]
                )->fetchColumn();

                if ($updatedStatus === $status) {

                    header(
                        'Location: ' . BASE .
                        '/admin/donations.php?updated=1'
                    );
                    exit;

                } else {

                    $msg = 'Donation status update failed.';
                    $msgType = 'error';
                }
            }

        } catch (PDOException $e) {

            error_log('Donation update error: ' . $e->getMessage());

            $msg = 'Database error. Please try again.';
            $msgType = 'error';
        }
    }
}

/* =================================
   SUCCESS MESSAGE
================================= */

if (isset($_GET['updated'])) {
    $msg = 'Donation status updated successfully!';
}

/* =================================
   SEARCH AND FILTER
================================= */

$search = trim($_GET['search'] ?? '');
$filter = $_GET['status'] ?? '';

$where = [];
$params = [];

if ($search !== '') {

    $where[] = "(
        d.title LIKE ?
        OR d.pickup_location LIKE ?
        OR u.name LIKE ?
        OR u.email LIKE ?
    )";

    $like = "%$search%";

    $params = array_merge(
        $params,
        [$like, $like, $like, $like]
    );
}

if (in_array($filter, $statuses, true)) {

    $where[] = "d.status = ?";
    $params[] = $filter;
}

/* =================================
   FETCH DONATIONS
================================= */

$sql = "
    SELECT
        d.*,
        u.name AS donor_name,
        u.email AS donor_email

    FROM donations d

    LEFT JOIN users u
        ON u.id = d.donor_id
";

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY d.id DESC LIMIT 100";

$donations = q($sql, $params)->fetchAll(PDO::FETCH_ASSOC);

/* =================================
   DONATION SUMMARY
================================= */

$summary = q("
    SELECT
        COUNT(*) AS total,

        SUM(CASE WHEN status='draft'
            THEN 1 ELSE 0 END) AS draft,

        SUM(CASE WHEN status='scheduled'
            THEN 1 ELSE 0 END) AS scheduled,

        SUM(CASE WHEN status='available'
            THEN 1 ELSE 0 END) AS available,

        SUM(CASE WHEN status='reserved'
            THEN 1 ELSE 0 END) AS reserved,

        SUM(CASE WHEN status='completed'
            THEN 1 ELSE 0 END) AS completed,

        SUM(CASE WHEN status='cancelled'
            THEN 1 ELSE 0 END) AS cancelled

    FROM donations
")->fetch(PDO::FETCH_ASSOC);

top('Manage Donations', 'Manage Donations');
?>

<style>

.admin-stats {
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(145px,1fr));
    gap:15px;
    margin:20px 0;
}

.admin-stat {
    background:#fff;
    border:1px solid #e5eee8;
    border-radius:12px;
    padding:18px;
}

.admin-stat small {
    color:#718078;
}

.admin-stat h2 {
    margin:8px 0 0;
    color:#176b45;
}

.admin-table-wrap {
    overflow-x:auto;
    background:#fff;
    border-radius:12px;
    border:1px solid #e5eee8;
}

.admin-table {
    width:100%;
    border-collapse:collapse;
    min-width:1050px;
}

.admin-table th,
.admin-table td {
    padding:13px;
    border-bottom:1px solid #edf1ee;
    text-align:left;
    font-size:14px;
}

.admin-table th {
    background:#f3f8f4;
    color:#285c40;
}

.admin-table select {
    padding:8px;
    border:1px solid #d8e3db;
    border-radius:7px;
    max-width:135px;
}

.admin-filter {
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    margin:18px 0;
}

.admin-filter input,
.admin-filter select {
    padding:10px;
    border:1px solid #d8e3db;
    border-radius:8px;
}

.status-pill {
    display:inline-block;
    padding:5px 9px;
    border-radius:20px;
    background:#edf5ef;
    color:#286341;
    font-size:12px;
}

.update-form {
    display:flex;
    gap:7px;
    align-items:center;
}

.update-form button {
    border:0;
    padding:8px 11px;
    border-radius:7px;
    background:#176b45;
    color:white;
    cursor:pointer;
}

.update-form button:hover {
    background:#105536;
}

.admin-message {
    padding:12px;
    border-radius:8px;
    margin:12px 0;
}

.admin-message.success {
    background:#eaf7ee;
    color:#176b45;
}

.admin-message.error {
    background:#fdecec;
    color:#a12626;
}

@media(max-width:768px) {

    .admin-table {
        min-width:1050px;
    }

    .admin-filter input {
        width:100%;
    }

}

</style>

<h2>Manage Donations</h2>

<p>
View, search and manage all food donations on RePlate.
</p>

<!-- MESSAGE -->

<?php if ($msg): ?>

    <div class="admin-message <?=e($msgType)?>">
        <?=e($msg)?>
    </div>

<?php endif; ?>


<!-- SUMMARY CARDS -->

<div class="admin-stats">

    <div class="admin-stat">
        <small>Total Donations</small>
        <h2><?=number_format((int)$summary['total'])?></h2>
    </div>

    <div class="admin-stat">
        <small>Draft</small>
        <h2><?=number_format((int)$summary['draft'])?></h2>
    </div>

    <div class="admin-stat">
        <small>Scheduled</small>
        <h2><?=number_format((int)$summary['scheduled'])?></h2>
    </div>

    <div class="admin-stat">
        <small>Available</small>
        <h2><?=number_format((int)$summary['available'])?></h2>
    </div>

    <div class="admin-stat">
        <small>Reserved</small>
        <h2><?=number_format((int)$summary['reserved'])?></h2>
    </div>

    <div class="admin-stat">
        <small>Completed</small>
        <h2><?=number_format((int)$summary['completed'])?></h2>
    </div>

    <div class="admin-stat">
        <small>Cancelled</small>
        <h2><?=number_format((int)$summary['cancelled'])?></h2>
    </div>

</div>


<!-- SEARCH AND FILTER -->

<form method="GET" class="admin-filter">

    <input
        type="text"
        name="search"
        placeholder="Search food, donor or location..."
        value="<?=e($search)?>"
    >

    <select name="status">

        <option value="">All Statuses</option>

        <?php foreach ($statuses as $s): ?>

            <option
                value="<?=e($s)?>"
                <?=$filter === $s ? 'selected' : ''?>
            >
                <?=e(ucfirst($s))?>
            </option>

        <?php endforeach; ?>

    </select>

    <button class="btn" type="submit">
        Search
    </button>

    <a class="btn ghost" href="<?=BASE?>/admin/donations.php">
        Clear
    </a>

</form>


<!-- DONATION TABLE -->

<div class="admin-table-wrap">

<table class="admin-table">

    <thead>

        <tr>
            <th>ID</th>
            <th>Food</th>
            <th>Donor</th>
            <th>Quantity</th>
            <th>Pickup Location</th>
            <th>Deadline</th>
            <th>Status</th>
            <th>Update Status</th>
        </tr>

    </thead>

    <tbody>

    <?php if (!$donations): ?>

        <tr>
            <td colspan="8">
                No donations found.
            </td>
        </tr>

    <?php endif; ?>


    <?php foreach ($donations as $d): ?>

        <tr>

            <td>
                #<?= (int)$d['id'] ?>
            </td>

            <td>

                <b>
                    <?=e($d['title'] ?? 'Untitled')?>
                </b>

                <br>

                <small>
                    <?=e($d['description'] ?? '')?>
                </small>

            </td>

            <td>

                <?=e($d['donor_name'] ?? 'Unknown')?>

                <br>

                <small>
                    <?=e($d['donor_email'] ?? '')?>
                </small>

            </td>

            <td>

                <?= (int)$d['qty'] ?>

                <?=e($d['unit'] ?? 'meals')?>

            </td>

            <td>
                <?=e($d['pickup_location'] ?? 'Not specified')?>
            </td>

            <td>

                <?php if (!empty($d['deadline'])): ?>

                    <?=e(
                        date(
                            'd M Y, h:i A',
                            strtotime($d['deadline'])
                        )
                    )?>

                <?php else: ?>

                    —

                <?php endif; ?>

            </td>

            <td>

                <span class="status-pill">
                    <?=e(ucfirst($d['status'] ?? 'unknown'))?>
                </span>

            </td>

            <td>

                <form method="POST" class="update-form">

                    <input
                        type="hidden"
                        name="csrf"
                        value="<?=e($_SESSION['replate_csrf'])?>"
                    >

                    <input
                        type="hidden"
                        name="id"
                        value="<?=(int)$d['id']?>"
                    >

                    <select name="status">

                        <?php foreach ($statuses as $s): ?>

                            <option
                                value="<?=e($s)?>"
                                <?=($d['status'] === $s) ? 'selected' : ''?>
                            >
                                <?=e(ucfirst($s))?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                    <button type="submit">
                        Save
                    </button>

                </form>

            </td>

        </tr>

    <?php endforeach; ?>

    </tbody>

</table>

</div>


<p style="margin-top:12px;color:#718078;">

    Showing up to 100 donations, newest first.

</p>

<?php bottom(); ?>
