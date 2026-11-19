
<?php

require_once '../includes/layout.php';

/* =========================
   DONOR AUTHENTICATION
========================= */

need('donor');

$u = me();
$id = (int)$u['id'];

/* =========================
   HANDLE POST ACTIONS
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $r = (int)($_POST['rid'] ?? 0);
    $d = (int)($_POST['did'] ?? 0);

    // APPROVE REQUEST
    if (isset($_POST['approve']) && $r > 0) {

        q(
            "UPDATE requests r
             JOIN donations d ON d.id = r.donation_id
             SET r.status = 'approved'
             WHERE r.id = ?
             AND d.donor_id = ?
             AND r.status = 'requested'",
            [$r, $id]
        );
    }

    // DECLINE REQUEST
    elseif (isset($_POST['decline']) && $r > 0) {

        q(
            "UPDATE requests r
             JOIN donations d ON d.id = r.donation_id
             SET r.status = 'declined',
                 d.status = 'available'
             WHERE r.id = ?
             AND d.donor_id = ?
             AND r.status = 'requested'",
            [$r, $id]
        );
    }

    // PUBLISH DONATION
    elseif (isset($_POST['publish']) && $d > 0) {

        q(
            "UPDATE donations
             SET status = 'available'
             WHERE id = ?
             AND donor_id = ?
             AND status IN ('draft','scheduled')",
            [$d, $id]
        );
    }

    // REMOVE DONATION
    elseif (isset($_POST['remove']) && $d > 0) {

        q(
            "UPDATE donations
             SET status = 'cancelled'
             WHERE id = ?
             AND donor_id = ?
             AND status IN ('available','draft','scheduled')",
            [$d, $id]
        );
    }

    header('Location: dashboard.php');
    exit;
}

/* =========================
   DONOR STATISTICS
========================= */

$st = q(
    "SELECT

        (
            SELECT COALESCE(SUM(d.qty),0)
            FROM requests r
            JOIN donations d ON d.id = r.donation_id
            WHERE r.status = 'delivered'
            AND d.donor_id = ?
        ) AS m,

        (
            SELECT COUNT(*)
            FROM donations
            WHERE donor_id = ?
            AND status = 'available'
        ) AS a,

        (
            SELECT COUNT(*)
            FROM requests r
            JOIN donations d ON d.id = r.donation_id
            WHERE r.status = 'delivered'
            AND d.donor_id = ?
        ) AS p",

    [$id, $id, $id]

)->fetch();

$st = $st ?: [
    'm' => 0,
    'a' => 0,
    'p' => 0
];

/* =========================
   GLOBAL MEALS
========================= */

$allMeals = meals();

/* =========================
   RATING STATISTICS
========================= */

$rating = q(
    "SELECT
        COALESCE(AVG(rating), 0) AS average_rating,
        COUNT(*) AS total_reviews
     FROM ratings
     WHERE reviewee_id = ?
     AND reviewee_type = 'donor'",
    [$id]
)->fetch();

$rating = $rating ?: [
    'average_rating' => 0,
    'total_reviews' => 0
];

/* =========================
   DONOR REVIEWS
========================= */

$reviews = q(
    "SELECT
        r.rating,
        r.review,
        r.created_at,
        u.name AS reviewer_name
     FROM ratings r
     JOIN users u ON u.id = r.reviewer_id
     WHERE r.reviewee_id = ?
     AND r.reviewee_type = 'donor'
     ORDER BY r.id DESC
     LIMIT 5",
    [$id]
)->fetchAll();

/* =========================
   DONATIONS LIST
========================= */

$list = q(
    "SELECT *
     FROM donations
     WHERE donor_id = ?
     AND status <> 'cancelled'
     ORDER BY id DESC",
    [$id]
)->fetchAll();

/* =========================
   REQUESTS LIST
========================= */

$req = q(
    "SELECT
        r.*,
        d.title,
        u.name AS rname
     FROM requests r
     JOIN donations d ON d.id = r.donation_id
     JOIN users u ON u.id = r.receiver_id
     WHERE d.donor_id = ?
     AND r.status <> 'declined'
     ORDER BY r.id DESC",
    [$id]
)->fetchAll();

/* =========================
   DASHBOARD
========================= */

top('Donor Dashboard', 'Overview');

?>

<!-- ADD DONATION -->

<a class="btn" style="float:right" href="add.php">
    ＋ Add food donation
</a>

<!-- WELCOME -->

<div class="eyebrow">
    Food donor workspace
</div>

<h1 class="page">
    Welcome back, <?=e($u['name'] ?? 'Donor')?>
</h1>

<p class="sub">
    Manage your surplus food and see the impact of every donation.
</p>

<!-- STATISTICS -->

<div class="stats">

    <div class="stat">
        <span>Meals donated</span>
        <b><?=number_format((float)$st['m'])?></b>
    </div>

    <div class="stat">
        <span>Active listings</span>
        <b><?=number_format((int)$st['a'])?></b>
    </div>

    <div class="stat">
        <span>Successful pickups</span>
        <b><?=number_format((int)$st['p'])?></b>
    </div>

    <div class="stat">
        <span>Meals rescued (all)</span>
        <b><?=number_format((int)$allMeals)?></b>
    </div>

</div>

<!-- RATINGS -->

<div class="stats">

    <div class="stat">
        <span>Average Rating</span>
        <b>
            ⭐ <?=number_format(
                (float)$rating['average_rating'], 1
            )?> / 5
        </b>
    </div>

    <div class="stat">
        <span>Total Reviews</span>
        <b>
            <?=number_format((int)$rating['total_reviews'])?>
        </b>
    </div>

</div>

<!-- REQUESTS -->

<div class="panel">

    <h3>Requests on your food</h3>

    <?php if (!empty($req)): ?>

        <?php foreach ($req as $r): ?>

            <div class="row">

                <div>
                    <b><?=e($r['title'])?></b>
                    <small>by <?=e($r['rname'])?></small>
                </div>

                <span class="pill">
                    <?=e($r['status'])?>
                </span>

                <?php if ($r['status'] === 'requested'): ?>

                    <form method="post">

                        <input type="hidden"
                               name="rid"
                               value="<?=(int)$r['id']?>">

                        <button class="sm"
                                name="approve"
                                type="submit">
                            Approve
                        </button>

                        <button class="sm red"
                                name="decline"
                                type="submit">
                            Decline
                        </button>

                    </form>

                <?php elseif (
                    in_array($r['status'], ['assigned','picked'])
                ): ?>

                    <a href="<?=BASE?>/track.php?id=<?=(int)$r['id']?>">
                        📍 Track
                    </a>

                <?php endif; ?>

            </div>

        <?php endforeach; ?>

    <?php else: ?>

        <p class="sub">No requests yet.</p>

    <?php endif; ?>

</div>

<!-- DONATIONS -->

<div class="panel">

    <h3>Manage food donations</h3>

    <?php if (!empty($list)): ?>

        <?php foreach ($list as $d): ?>

            <div class="row">

                <div>

                    <b><?=e($d['title'])?></b>

                    <small>
                        <?=e($d['qty'].' '.$d['unit'])?>
                        · Pickup by

                        <?=!empty($d['deadline'])
                            ? e(date(
                                'd M g:i A',
                                strtotime($d['deadline'])
                            ))
                            : '—'?>
                    </small>

                </div>

                <span class="pill <?=$d['status'] === 'reserved' ? 'o' : ''?>">
                    <?=e($d['status'])?>
                </span>

                <form method="post">

                    <input type="hidden"
                           name="did"
                           value="<?=(int)$d['id']?>">

                    <?php if (
                        in_array($d['status'], ['draft','scheduled'])
                    ): ?>

                        <button class="sm"
                                name="publish"
                                type="submit">
                            Publish
                        </button>

                    <?php endif; ?>

                    <?php if (
                        in_array(
                            $d['status'],
                            ['available','draft','scheduled']
                        )
                    ): ?>

                        <button class="sm red"
                                name="remove"
                                type="submit">
                            Remove
                        </button>

                    <?php endif; ?>

                </form>

            </div>

        <?php endforeach; ?>

    <?php else: ?>

        <p class="sub">No donations yet.</p>

    <?php endif; ?>

</div>

<!-- DONOR REVIEWS -->

<div class="panel">

    <h3>⭐ Donor Reviews</h3>

    <p class="sub">
        See what receivers think about your food donations.
    </p>

    <?php if (!empty($reviews)): ?>

        <?php foreach ($reviews as $review): ?>

            <div class="row">

                <div>

                    <b><?=e($review['reviewer_name'])?></b>

                    <small>
                        <?php
                        $stars = (int)$review['rating'];

                        echo str_repeat(
                            '⭐',
                            max(0, min(5, $stars))
                        );
                        ?>
                    </small>

                    <small>
                        <?=e(
                            $review['review']
                            ?? 'No written review.'
                        )?>
                    </small>

                    <small>
                        <?=!empty($review['created_at'])
                            ? e(date(
                                'd M Y',
                                strtotime($review['created_at'])
                            ))
                            : 'Date unavailable'?>
                    </small>

                </div>

            </div>

        <?php endforeach; ?>

    <?php else: ?>

        <p class="sub">
            No reviews yet. Reviews will appear here after receivers rate your donations.
        </p>

    <?php endif; ?>

</div>

<?php bottom(); ?>
