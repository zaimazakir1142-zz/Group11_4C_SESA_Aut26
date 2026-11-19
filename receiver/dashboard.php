```php
<?php
require_once __DIR__ . '/../includes/layout.php';

$u = need('receiver');
$id = (int)$u['id'];

/* =====================================
   HANDLE RATING ACTIONS
===================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $rid = (int)($_POST['rid'] ?? 0);
    $ratingValue = max(1, min(5, (int)($_POST['rating'] ?? 5)));
    $comment = trim($_POST['comment'] ?? '');

    $request = q(
        "SELECT r.*, d.donor_id
         FROM requests r
         JOIN donations d ON d.id = r.donation_id
         WHERE r.id = ?
         AND r.receiver_id = ?
         AND r.status = 'delivered'",
        [$rid, $id]
    )->fetch();

    if (!$request) {
        header('Location: dashboard.php');
        exit;
    }

    // RATE DONOR
    if (isset($_POST['rate_donor'])) {

        $existing = q(
            "SELECT id FROM ratings
             WHERE request_id = ?
             AND reviewer_id = ?
             AND reviewee_type = 'donor'",
            [$rid, $id]
        )->fetch();

        if (!$existing) {
            q(
                "INSERT INTO ratings
                (request_id, reviewer_id, reviewee_id,
                 reviewee_type, rating, review)
                 VALUES (?, ?, ?, 'donor', ?, ?)",
                [$rid, $id, $request['donor_id'], $ratingValue, $comment]
            );
        }
    }

    // RATE VOLUNTEER
    elseif (isset($_POST['rate_volunteer']) && !empty($request['volunteer_id'])) {

        $existing = q(
            "SELECT id FROM ratings
             WHERE request_id = ?
             AND reviewer_id = ?
             AND reviewee_type = 'volunteer'",
            [$rid, $id]
        )->fetch();

        if (!$existing) {
            q(
                "INSERT INTO ratings
                (request_id, reviewer_id, reviewee_id,
                 reviewee_type, rating, review)
                 VALUES (?, ?, ?, 'volunteer', ?, ?)",
                [$rid, $id, $request['volunteer_id'], $ratingValue, $comment]
            );
        }
    }

    header('Location: dashboard.php');
    exit;
}

/* =====================================
   RECEIVER STATISTICS
===================================== */

$st = q(
    "SELECT
        (SELECT COUNT(*)
         FROM requests
         WHERE receiver_id = ?
         AND status NOT IN ('delivered','declined')) AS a,

        (SELECT COALESCE(SUM(d.qty), 0)
         FROM requests r
         JOIN donations d ON d.id = r.donation_id
         WHERE r.receiver_id = ?
         AND r.status = 'delivered') AS m,

        (SELECT COUNT(*)
         FROM requests
         WHERE receiver_id = ?) AS total",
    [$id, $id, $id]
)->fetch();

$st = $st ?: ['a' => 0, 'm' => 0, 'total' => 0];

/* =====================================
   REQUEST HISTORY
===================================== */

$list = q(
    "SELECT
        r.*,
        d.title,
        d.qty,
        d.unit,
        d.donor_id,
        u.name AS donor,

        EXISTS (
            SELECT 1 FROM ratings rt
            WHERE rt.request_id = r.id
            AND rt.reviewer_id = ?
            AND rt.reviewee_type = 'donor'
        ) AS donor_rv,

        EXISTS (
            SELECT 1 FROM ratings rt
            WHERE rt.request_id = r.id
            AND rt.reviewer_id = ?
            AND rt.reviewee_type = 'volunteer'
        ) AS volunteer_rv

     FROM requests r
     JOIN donations d ON d.id = r.donation_id
     JOIN users u ON u.id = d.donor_id

     WHERE r.receiver_id = ?
     ORDER BY r.id DESC",
    [$id, $id, $id]
)->fetchAll();

/* =====================================
   DASHBOARD
===================================== */

top('Receiver Dashboard', 'Overview');

?>

<a class="btn" style="float:right" href="find.php">
    🔍 Find available food
</a>

<div class="eyebrow">
    Receiver / NGO workspace
</div>

<h1 class="page">
    Welcome back, <?=e($u['name'] ?? 'Receiver')?>
</h1>

<p class="sub">
    Track food requests and find donations for your community.
</p>

<?php if (($u['status'] ?? '') !== 'verified'): ?>

    <div class="err">
        Your account is pending admin verification.
        You can browse food but cannot request yet.
    </div>

<?php endif; ?>

<!-- STATISTICS -->

<div class="stats">

    <div class="stat">
        <span>Active requests</span>
        <b><?=number_format((int)$st['a'])?></b>
    </div>

    <div class="stat">
        <span>Meals received</span>
        <b><?=number_format((float)$st['m'])?></b>
    </div>

    <div class="stat">
        <span>Total requests</span>
        <b><?=number_format((int)$st['total'])?></b>
    </div>

    <div class="stat">
        <span>Meals rescued (all)</span>
        <b><?=number_format((int)meals())?></b>
    </div>

</div>

<!-- MY FOOD REQUESTS -->

<div class="panel">

    <h3>My food requests</h3>

    <?php if (!empty($list)): ?>

        <?php foreach ($list as $r): ?>

            <div class="row">

                <div>
                    <b><?=e($r['title'])?></b>

                    <small>
                        <?=e($r['donor'])?>
                        · <?=e($r['qty'].' '.$r['unit'])?>
                    </small>
                </div>

                <span class="pill">
                    <?=e($r['status'])?>
                </span>

                <!-- DELIVERY CODE -->

                <?php if (
                    in_array($r['status'], ['approved','assigned','picked'])
                ): ?>

                    <span>
                        Delivery code:
                        <b><?=e($r['code'] ?? 'Pending')?></b>
                    </span>

                <?php endif; ?>

                <!-- LIVE TRACKING -->

                <?php if (
                    in_array($r['status'], ['assigned','picked'])
                ): ?>

                    <a class="btn sm"
                       href="<?=BASE?>/track.php?id=<?=(int)$r['id']?>">
                        📍 Live map
                    </a>

                <?php endif; ?>

                <!-- DONOR RATING -->

                <?php if (
                    $r['status'] === 'delivered'
                    && !$r['donor_rv']
                ): ?>

                    <form method="post"
                          style="display:flex;gap:6px;flex-wrap:wrap;align-items:center">

                        <input type="hidden" name="rid"
                               value="<?=(int)$r['id']?>">

                        <select name="rating" style="width:85px">
                            <option value="5">5 ⭐</option>
                            <option value="4">4 ⭐</option>
                            <option value="3">3 ⭐</option>
                            <option value="2">2 ⭐</option>
                            <option value="1">1 ⭐</option>
                        </select>

                        <input name="comment" placeholder="Review donor">

                        <button class="sm" name="rate_donor">
                            ★ Rate Donor
                        </button>

                    </form>

                <?php elseif ($r['status'] === 'delivered'): ?>

                    <small>⭐ Donor reviewed</small>

                <?php endif; ?>

                <!-- VOLUNTEER RATING -->

                <?php if (
                    $r['status'] === 'delivered'
                    && !empty($r['volunteer_id'])
                    && !$r['volunteer_rv']
                ): ?>

                    <form method="post"
                          style="display:flex;gap:6px;flex-wrap:wrap;align-items:center">

                        <input type="hidden" name="rid"
                               value="<?=(int)$r['id']?>">

                        <select name="rating" style="width:85px">
                            <option value="5">5 ⭐</option>
                            <option value="4">4 ⭐</option>
                            <option value="3">3 ⭐</option>
                            <option value="2">2 ⭐</option>
                            <option value="1">1 ⭐</option>
                        </select>

                        <input name="comment" placeholder="Review volunteer">

                        <button class="sm" name="rate_volunteer">
                            ★ Rate Volunteer
                        </button>

                    </form>

                <?php elseif (
                    $r['status'] === 'delivered'
                    && !empty($r['volunteer_id'])
                ): ?>

                    <small>⭐ Volunteer reviewed</small>

                <?php endif; ?>

            </div>

        <?php endforeach; ?>

    <?php else: ?>

        <p class="sub">No requests yet.</p>

        <a class="btn" href="find.php">
            Find available food
        </a>

    <?php endif; ?>

</div>

<?php bottom(); ?>
```