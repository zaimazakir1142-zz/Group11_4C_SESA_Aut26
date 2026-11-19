
<?php
require '../includes/layout.php';

$u = need('volunteer');
$id = (int)$u['id'];

$msg = '';

/* =====================================
   HANDLE VOLUNTEER ACTIONS
===================================== */

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $r = (int)($_POST['rid'] ?? 0);

    // ACCEPT TASK
    if (isset($_POST['accept'])) {

        q(
            "UPDATE requests
             SET volunteer_id = ?, status = 'assigned'
             WHERE id = ?
             AND status = 'approved'
             AND volunteer_id IS NULL",
            [$id, $r]
        );
    }

    // PICKUP CONFIRMATION
    if (isset($_POST['pick'])) {

        q(
            "UPDATE requests
             SET status = 'picked'
             WHERE id = ?
             AND volunteer_id = ?
             AND status = 'assigned'",
            [$r, $id]
        );
    }

    // DELIVERY CONFIRMATION
    if (isset($_POST['deliver'])) {

        $ok = q(
            "UPDATE requests
             SET status = 'delivered'
             WHERE id = ?
             AND volunteer_id = ?
             AND status = 'picked'
             AND code = ?",
            [
                $r,
                $id,
                trim($_POST['code'] ?? '')
            ]
        )->rowCount();

        if ($ok) {

            q(
                "UPDATE donations
                 SET status = 'completed'
                 WHERE id = (
                     SELECT donation_id
                     FROM requests
                     WHERE id = ?
                 )",
                [$r]
            );

        } else {

            $_SESSION['m'] = 'Wrong delivery code.';
        }
    }

    header('Location: dashboard.php');
    exit;
}

/* =====================================
   SUCCESS / ERROR MESSAGE
===================================== */

$msg = $_SESSION['m'] ?? '';
unset($_SESSION['m']);

/* =====================================
   AVAILABLE TASKS
===================================== */

$av = q(
    "SELECT
        r.id,
        d.title,
        d.qty,
        d.unit,
        d.pickup_location,
        du.name AS donor,
        ru.name AS rname,
        ru.location AS rloc

     FROM requests r

     JOIN donations d ON d.id = r.donation_id
     JOIN users du ON du.id = d.donor_id
     JOIN users ru ON ru.id = r.receiver_id

     WHERE r.status = 'approved'
     AND r.volunteer_id IS NULL

     ORDER BY r.id DESC"
)->fetchAll();

/* =====================================
   MY ACTIVE TASKS
===================================== */

$my = q(
    "SELECT
        r.id,
        r.status,
        d.title,
        d.pickup_location,
        ru.name AS rname,
        ru.location AS rloc

     FROM requests r

     JOIN donations d ON d.id = r.donation_id
     JOIN users ru ON ru.id = r.receiver_id

     WHERE r.volunteer_id = ?
     AND r.status IN ('assigned','picked')

     ORDER BY r.id DESC",
    [$id]
)->fetchAll();

/* =====================================
   VOLUNTEER STATISTICS
===================================== */

$st = q(
    "SELECT
        COUNT(*) AS c,
        COALESCE(SUM(d.qty),0) AS m

     FROM requests r

     JOIN donations d ON d.id = r.donation_id

     WHERE r.volunteer_id = ?
     AND r.status = 'delivered'",
    [$id]
)->fetch();

/* =====================================
   VOLUNTEER RATING
===================================== */

$rating = q(
    "SELECT
        COALESCE(AVG(rating),0) AS average_rating,
        COUNT(*) AS total_reviews

     FROM ratings

     WHERE reviewee_id = ?
     AND reviewee_type = 'volunteer'",
    [$id]
)->fetch();

/* =====================================
   VOLUNTEER REVIEWS
===================================== */

$reviews = q(
    "SELECT
        r.rating,
        r.review,
        r.created_at,
        u.name AS reviewer_name

     FROM ratings r

     JOIN users u ON u.id = r.reviewer_id

     WHERE r.reviewee_id = ?
     AND r.reviewee_type = 'volunteer'

     ORDER BY r.id DESC
     LIMIT 10",
    [$id]
)->fetchAll();

/* =====================================
   COMPLETED DELIVERY HISTORY
===================================== */

$history = q(
    "SELECT
        r.id,
        r.status,
        d.title,
        d.qty,
        d.unit,
        ru.name AS receiver,
        ru.location AS receiver_location

     FROM requests r

     JOIN donations d ON d.id = r.donation_id
     JOIN users ru ON ru.id = r.receiver_id

     WHERE r.volunteer_id = ?
     AND r.status = 'delivered'

     ORDER BY r.id DESC
     LIMIT 10",
    [$id]
)->fetchAll();

/* =====================================
   DASHBOARD
===================================== */

top('Volunteer Dashboard', 'Overview');

?>

<div class="eyebrow">
    Volunteer workspace
</div>

<h1 class="page">
    Ready to make a difference,
    <?=e(explode(' ', $u['name'])[0])?>?
</h1>

<p class="sub">
    Choose a nearby rescue task and deliver good food to the right table.
</p>

<?php if ($msg): ?>

    <div class="err">
        <?=e($msg)?>
    </div>

<?php endif; ?>


<!-- =====================================
     VOLUNTEER STATISTICS
===================================== -->

<div class="stats">

    <div class="stat">
        <span>Available tasks</span>
        <b><?=count($av)?></b>
    </div>

    <div class="stat">
        <span>Completed trips</span>
        <b><?=e($st['c'])?></b>
    </div>

    <div class="stat">
        <span>Meals delivered</span>
        <b><?=e($st['m'])?></b>
    </div>

    <div class="stat">
        <span>My active tasks</span>
        <b><?=count($my)?></b>
    </div>

</div>


<!-- =====================================
     RATING STATISTICS
===================================== -->

<div class="stats">

    <div class="stat">
        <span>Average Rating</span>
        <b>
            ⭐ <?=number_format((float)$rating['average_rating'], 1)?> / 5
        </b>
    </div>

    <div class="stat">
        <span>Total Reviews</span>
        <b><?=e($rating['total_reviews'])?></b>
    </div>

</div>


<!-- =====================================
     MY ACTIVE TASKS
===================================== -->

<div class="panel">

    <h3>
        My active tasks (live location is shared with receiver)
    </h3>

    <?php foreach ($my as $t): ?>

        <div class="row">

            <div>

                <b><?=e($t['title'])?></b>

                <small>
                    Pickup: <?=e($t['pickup_location'])?>
                    →
                    <?=e($t['rname'])?>,
                    <?=e($t['rloc'])?>
                </small>

            </div>

            <span class="pill o">
                <?=e($t['status'])?>
            </span>

            <form method="post">

                <input type="hidden"
                       name="rid"
                       value="<?=e($t['id'])?>">

                <?php if ($t['status'] == 'assigned'): ?>

                    <button class="sm" name="pick">
                        Picked up
                    </button>

                <?php else: ?>

                    <input
                        name="code"
                        maxlength="4"
                        placeholder="4-digit code"
                        style="width:110px"
                        required
                    >

                    <button class="sm" name="deliver">
                        Delivered
                    </button>

                <?php endif; ?>

            </form>

        </div>

    <?php endforeach; ?>


    <?php if (!$my): ?>

        <p class="sub">
            No active tasks.
        </p>

    <?php endif; ?>


    <!-- LIVE LOCATION CONTROL -->

    <?php if ($my): ?>

        <p>

            <button class="ghost" type="button" id="sh">
                📡 Start sharing live location
            </button>

            <button class="ghost" type="button" id="stop">
                Stop sharing
            </button>

            <span id="ss" class="sub"></span>

        </p>

        <small class="sub">
            Location sharing is optional. Share only during an active delivery.
        </small>

    <?php endif; ?>

</div>


<!-- =====================================
     AVAILABLE TASKS
===================================== -->

<div class="panel">

    <h3>
        Available pickup &amp; delivery tasks
    </h3>

    <?php foreach ($av as $t): ?>

        <div class="row">

            <div>

                <b><?=e($t['donor'])?></b>

                <small>
                    Pickup: <?=e($t['pickup_location'])?>
                </small>

            </div>

            <div>

                <b><?=e($t['rname'])?></b>

                <small>
                    Deliver: <?=e($t['rloc'])?>
                </small>

            </div>

            <div>

                <?=e($t['title'])?>

                <small>
                    <?=e($t['qty'].' '.$t['unit'])?>
                </small>

            </div>

            <form method="post">

                <input type="hidden"
                       name="rid"
                       value="<?=e($t['id'])?>">

                <button class="sm" name="accept">
                    Accept task
                </button>

            </form>

        </div>

    <?php endforeach; ?>


    <?php if (!$av): ?>

        <p class="sub">
            No tasks waiting. Check back soon.
        </p>

    <?php endif; ?>

</div>


<!-- =====================================
     COMPLETED DELIVERY HISTORY
===================================== -->

<div class="panel">

    <h3>Completed Delivery History</h3>

    <?php foreach ($history as $h): ?>

        <div class="row">

            <div>

                <b><?=e($h['title'])?></b>

                <small>
                    <?=e($h['qty'].' '.$h['unit'])?>
                    · Receiver: <?=e($h['receiver'])?>
                </small>

                <small>
                    Location: <?=e($h['receiver_location'])?>
                </small>

            </div>

            <span class="pill">
                Delivered ✔
            </span>

        </div>

    <?php endforeach; ?>


    <?php if (!$history): ?>

        <p class="sub">
            No completed deliveries yet.
        </p>

    <?php endif; ?>

</div>


<!-- =====================================
     VOLUNTEER REVIEWS
===================================== -->

<div class="panel">

    <h3>⭐ My Reviews</h3>

    <p class="sub">
        See feedback from receivers about your deliveries.
    </p>

    <?php foreach ($reviews as $review): ?>

        <div class="row">

            <div>

                <b><?=e($review['reviewer_name'])?></b>

                <small>
                    <?=str_repeat('⭐', max(0, min(5, (int)$review['rating'])))?>
                </small>

                <small>
                    <?=e($review['review'] ?? 'No written review.')?>
                </small>

                <small>
                    <?=e(date('d M Y', strtotime($review['created_at'])))?>
                </small>

            </div>

        </div>

    <?php endforeach; ?>


    <?php if (!$reviews): ?>

        <p class="sub">
            No reviews yet. Complete deliveries to receive feedback.
        </p>

    <?php endif; ?>

</div>


<!-- =====================================
     LIVE LOCATION JAVASCRIPT
===================================== -->

<script>

let watchId = null;

const sh = document.getElementById('sh');
const stop = document.getElementById('stop');
const ss = document.getElementById('ss');

if (sh) {

    sh.onclick = () => {

        if (!navigator.geolocation) {
            ss.textContent = 'Geolocation is not supported.';
            return;
        }

        if (watchId !== null) return;

        ss.textContent = 'Requesting location permission...';

        watchId = navigator.geolocation.watchPosition(

            position => {

                const lat = position.coords.latitude;
                const lng = position.coords.longitude;

                fetch('<?=BASE?>/api/location.php', {

                    method: 'POST',

                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },

                    body:
                        'lat=' + encodeURIComponent(lat) +
                        '&lng=' + encodeURIComponent(lng)

                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Location update failed');
                    }

                    ss.textContent =
                        'Live location shared ✔ ' +
                        new Date().toLocaleTimeString();
                })
                .catch(() => {
                    ss.textContent = 'Could not update location.';
                });

            },

            error => {

                if (watchId !== null) {
                    navigator.geolocation.clearWatch(watchId);
                    watchId = null;
                }

                ss.textContent = 'Location blocked: ' + error.message;

            },

            {
                enableHighAccuracy: true,
                maximumAge: 5000,
                timeout: 15000
            }

        );

        ss.textContent = 'Sharing started...';
    };

}

if (stop) {

    stop.onclick = () => {

        if (watchId !== null) {

            navigator.geolocation.clearWatch(watchId);
            watchId = null;

        }

        ss.textContent = 'Location sharing stopped.';

    };

}

window.addEventListener('beforeunload', () => {

    if (watchId !== null) {
        navigator.geolocation.clearWatch(watchId);
    }

});

</script>

<?php bottom(); ?>
