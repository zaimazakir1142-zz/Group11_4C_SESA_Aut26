
<?php
require '../includes/layout.php';

$u = need('receiver');
$id = $u['id'];

$msg = $_SESSION['m'] ?? '';
unset($_SESSION['m']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $rid = (int)($_POST['rid'] ?? 0);
    $type = $_POST['type'] ?? '';
    $rating = (int)($_POST['rating'] ?? 0);
    $review = trim($_POST['review'] ?? '');

    if (!in_array($type, ['donor', 'volunteer']) ||
        $rating < 1 || $rating > 5) {
        $_SESSION['m'] = 'Invalid rating.';
    } else {

        $r = q(
            "SELECT r.id, d.donor_id, r.volunteer_id
             FROM requests r
             JOIN donations d ON d.id = r.donation_id
             WHERE r.id = ? AND r.receiver_id = ?
             AND r.status = 'delivered'",
            [$rid, $id]
        )->fetch();

        if (!$r) {
            $_SESSION['m'] = 'Delivery not found.';
        } else {

            $person = $type === 'donor'
                ? (int)$r['donor_id']
                : (int)$r['volunteer_id'];

            if (!$person) {
                $_SESSION['m'] = 'Volunteer not assigned.';
            } else {
                try {
                    q(
                        "INSERT INTO ratings
                        (request_id, reviewer_id, reviewee_id,
                         reviewee_type, rating, review)
                        VALUES (?, ?, ?, ?, ?, ?)",
                        [$rid, $id, $person, $type, $rating, $review]
                    );

                    $_SESSION['m'] = 'Review submitted successfully!';
                } catch (PDOException $e) {
                    $_SESSION['m'] = 'You have already submitted this review.';
                }
            }
        }
    }

    header('Location: rate.php');
    exit;
}

$requests = q(
    "SELECT r.id, d.title, d.donor_id,
    du.name AS donor_name, r.volunteer_id,
    vu.name AS volunteer_name
    FROM requests r
    JOIN donations d ON d.id = r.donation_id
    JOIN users du ON du.id = d.donor_id
    LEFT JOIN users vu ON vu.id = r.volunteer_id
    WHERE r.receiver_id = ? AND r.status = 'delivered'
    ORDER BY r.id DESC",
    [$id]
)->fetchAll();

top('Rate & Review', 'Reviews');
?>

<div class="eyebrow">Receiver workspace</div>
<h1 class="page">Rate & Review</h1>
<p class="sub">Share your experience with our food donors and volunteers.</p>

<?php if ($msg): ?>
<div class="panel"><?= e($msg) ?></div>
<?php endif; ?>

<?php foreach ($requests as $r): ?>

<div class="panel">
    <h3><?= e($r['title']) ?></h3>

    <?php
    $people = [
        ['id'=>$r['donor_id'], 'name'=>$r['donor_name'], 'type'=>'donor']
    ];

    if ($r['volunteer_id']) {
        $people[] = [
            'id'=>$r['volunteer_id'],
            'name'=>$r['volunteer_name'],
            'type'=>'volunteer'
        ];
    }
    ?>

    <?php foreach ($people as $p): ?>

    <?php
    $old = q(
        "SELECT rating, review FROM ratings
         WHERE request_id=? AND reviewer_id=? AND reviewee_id=?",
        [$r['id'], $id, $p['id']]
    )->fetch();
    ?>

    <div class="row">
        <div>
            <b><?= e($p['name']) ?></b>
            <small><?= ucfirst($p['type']) ?></small>
        </div>

        <?php if ($old): ?>

            <b>★ <?= (int)$old['rating'] ?>/5</b>
            <small><?= e($old['review']) ?></small>

        <?php else: ?>

            <form method="post">
                <input type="hidden" name="rid" value="<?= (int)$r['id'] ?>">
                <input type="hidden" name="type" value="<?= e($p['type']) ?>">

                <label>Rating</label>
                <select name="rating" required>
                    <option value="">Select Stars</option>
                    <option value="5">★★★★★ 5</option>
                    <option value="4">★★★★ 4</option>
                    <option value="3">★★★ 3</option>
                    <option value="2">★★ 2</option>
                    <option value="1">★ 1</option>
                </select>

                <textarea name="review" placeholder="Write your review..." maxlength="1000"></textarea>

                <button class="sm" type="submit">Submit Review</button>
            </form>

        <?php endif; ?>
    </div>

    <?php endforeach; ?>
</div>

<?php endforeach; ?>

<?php if (!$requests): ?>
<div class="panel">
    <p class="sub">No delivered food available for review.</p>
</div>
<?php endif; ?>

<?php bottom(); ?>
