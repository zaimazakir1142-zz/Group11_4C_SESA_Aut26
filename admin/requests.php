
<?php
require_once __DIR__ . '/../includes/layout.php';

$u = need('admin');

$statuses = [
    'requested',
    'approved',
    'assigned',
    'picked',
    'delivered',
    'declined'
];

$msg = '';
$msgType = 'success';

/* ===============================
   CSRF TOKEN
================================ */

if (empty($_SESSION['replate_csrf'])) {
    $_SESSION['replate_csrf'] = bin2hex(random_bytes(32));
}

/* ===============================
   UPDATE REQUEST STATUS
================================ */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';

    if (!hash_equals($_SESSION['replate_csrf'], $token)) {

        $msg = 'Invalid security token. Please try again.';
        $msgType = 'error';

    } elseif ($id < 1 || !in_array($status, $statuses, true)) {

        $msg = 'Invalid request information.';
        $msgType = 'error';

    } else {

        try {

            $exists = q(
                "SELECT id FROM requests WHERE id=?",
                [$id]
            )->fetchColumn();

            if (!$exists) {

                $msg = 'Request not found.';
                $msgType = 'error';

            } else {

                q(
                    "UPDATE requests SET status=? WHERE id=?",
                    [$status, $id]
                );

                $updatedStatus = q(
                    "SELECT status FROM requests WHERE id=?",
                    [$id]
                )->fetchColumn();

                if ($updatedStatus === $status) {

                    header(
                        'Location: ' . BASE .
                        '/admin/requests.php?updated=1'
                    );
                    exit;

                } else {

                    $msg = 'Status update failed!';
                    $msgType = 'error';
                }
            }

        } catch (PDOException $e) {

            $msg = 'Database update failed.';
            $msgType = 'error';
        }
    }
}

/* ===============================
   SUCCESS MESSAGE
================================ */

if (isset($_GET['updated'])) {
    $msg = 'Request status updated successfully!';
}

/* ===============================
   SEARCH AND FILTER
================================ */

$search = trim($_GET['search'] ?? '');
$filter = $_GET['status'] ?? '';

$where = [];
$params = [];

if ($search !== '') {

    $where[] = "(
        d.title LIKE ?
        OR receiver.name LIKE ?
        OR receiver.email LIKE ?
        OR donor.name LIKE ?
        OR volunteer.name LIKE ?
        OR d.pickup_location LIKE ?
    )";

    $like = "%$search%";

    for ($i = 0; $i < 6; $i++) {
        $params[] = $like;
    }
}

if (in_array($filter, $statuses, true)) {

    $where[] = "r.status = ?";
    $params[] = $filter;
}

/* ===============================
   GET REQUESTS
================================ */

$sql = "
    SELECT
        r.id,
        r.donation_id,
        r.receiver_id,
        r.volunteer_id,
        r.status,
        r.created,

        d.title AS food_name,
        d.pickup_location,

        donor.name AS donor_name,

        receiver.name AS receiver_name,
        receiver.email AS receiver_email,

        volunteer.name AS volunteer_name

    FROM requests r

    LEFT JOIN donations d
        ON d.id = r.donation_id

    LEFT JOIN users donor
        ON donor.id = d.donor_id

    LEFT JOIN users receiver
        ON receiver.id = r.receiver_id

    LEFT JOIN users volunteer
        ON volunteer.id = r.volunteer_id
";

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY r.id DESC LIMIT 100";

$requests = q($sql, $params)->fetchAll(PDO::FETCH_ASSOC);

/* ===============================
   SUMMARY COUNTS
================================ */

$total = q(
    "SELECT COUNT(*) FROM requests"
)->fetchColumn();

$requested = q(
    "SELECT COUNT(*) FROM requests WHERE status='requested'"
)->fetchColumn();

$approved = q(
    "SELECT COUNT(*) FROM requests WHERE status='approved'"
)->fetchColumn();

$assigned = q(
    "SELECT COUNT(*) FROM requests WHERE status='assigned'"
)->fetchColumn();

$picked = q(
    "SELECT COUNT(*) FROM requests WHERE status='picked'"
)->fetchColumn();

$delivered = q(
    "SELECT COUNT(*) FROM requests WHERE status='delivered'"
)->fetchColumn();

$declined = q(
    "SELECT COUNT(*) FROM requests WHERE status='declined'"
)->fetchColumn();

/* ===============================
   PAGE HEADER
================================ */

top('Manage Requests', 'Manage Requests');
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
    max-width:130px;
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

<h2>Manage Requests</h2>

<p>
View, search and manage all food requests on RePlate.
</p>

<?php if ($msg): ?>

    <div class="admin-message <?=e($msgType)?>">
        <?=e($msg)?>
    </div>

<?php endif; ?>

<!-- SUMMARY CARDS -->

<div class="admin-stats">

    <div class="admin-stat">
        <small>Total Requests</small>
        <h2><?=number_format((int)$total)?></h2>
    </div>

    <div class="admin-stat">
        <small>Requested</small>
        <h2><?=number_format((int)$requested)?></h2>
    </div>

    <div class="admin-stat">
        <small>Approved</small>
        <h2><?=number_format((int)$approved)?></h2>
    </div>

    <div class="admin-stat">
        <small>Assigned</small>
        <h2><?=number_format((int)$assigned)?></h2>
    </div>

    <div class="admin-stat">
        <small>Picked</small>
        <h2><?=number_format((int)$picked)?></h2>
    </div>

    <div class="admin-stat">
        <small>Delivered</small>
        <h2><?=number_format((int)$delivered)?></h2>
    </div>

    <div class="admin-stat">
        <small>Declined</small>
        <h2><?=number_format((int)$declined)?></h2>
    </div>

</div>

<!-- SEARCH AND FILTER -->

<form method="GET" class="admin-filter">

    <input
        type="text"
        name="search"
        placeholder="Search food, receiver, donor..."
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

    <a class="btn ghost" href="<?=BASE?>/admin/requests.php">
        Clear
    </a>

</form>

<!-- REQUEST TABLE -->

<div class="admin-table-wrap">

<table class="admin-table">

    <thead>

        <tr>
            <th>ID</th>
            <th>Food</th>
            <th>Donor</th>
            <th>Receiver</th>
            <th>Volunteer</th>
            <th>Pickup Location</th>
            <th>Request Date</th>
            <th>Status</th>
            <th>Update Status</th>
        </tr>

    </thead>

    <tbody>

    <?php if (!$requests): ?>

        <tr>
            <td colspan="9">
                No requests found.
            </td>
        </tr>

    <?php endif; ?>

    <?php foreach ($requests as $r): ?>

        <tr>

            <td>
                #<?= (int)$r['id'] ?>
            </td>

            <td>
                <b>
                    <?=e($r['food_name'] ?? 'Unknown Food')?>
                </b>
            </td>

            <td>
                <?=e($r['donor_name'] ?? 'Unknown')?>
            </td>

            <td>
                <?=e($r['receiver_name'] ?? 'Unknown')?>

                <br>

                <small>
                    <?=e($r['receiver_email'] ?? '')?>
                </small>
            </td>

            <td>
                <?=e($r['volunteer_name'] ?? 'Not Assigned')?>
            </td>

            <td>
                <?=e($r['pickup_location'] ?? 'Not specified')?>
            </td>

            <td>
                <?=e(
                    $r['created']
                    ? date('d M Y, h:i A', strtotime($r['created']))
                    : 'N/A'
                )?>
            </td>

            <td>

                <span class="status-pill">
                    <?=e(ucfirst($r['status'] ?? 'unknown'))?>
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
                        value="<?=(int)$r['id']?>"
                    >

                    <select name="status">

                        <?php foreach ($statuses as $s): ?>

                            <option
                                value="<?=e($s)?>"
                                <?=($r['status'] === $s) ? 'selected' : ''?>
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
    Showing up to 100 requests, newest first.
</p>

<?php bottom(); ?>
