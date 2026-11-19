
<?php
require '../includes/layout.php';

$u = need('receiver');
$msg = '';

/* =================================
   FOOD REQUEST SYSTEM
================================= */

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if ($u['status'] != 'verified') {

        $msg = 'Your NGO account is waiting for admin verification.';

    } else {

        $d = (int)($_POST['did'] ?? 0);

        if ($d <= 0) {

            $msg = 'Invalid food selection.';

        } else {

            try {

                // Start database transaction
                $pdo->beginTransaction();

                // Lock selected donation
                $food = q(
                    "SELECT id, status, deadline
                     FROM donations
                     WHERE id = ?
                     FOR UPDATE",
                    [$d]
                )->fetch();

                if (
                    !$food ||
                    $food['status'] != 'available' ||
                    ($food['deadline'] !== null &&
                     strtotime($food['deadline']) <= time())
                ) {

                    $pdo->rollBack();

                    $msg = 'Sorry, this food is no longer available.';

                } else {

                    // Reserve food only if still available
                    $update = q(
                        "UPDATE donations
                         SET status = 'reserved'
                         WHERE id = ?
                         AND status = 'available'",
                        [$d]
                    );

                    if ($update->rowCount() == 1) {

                        // Generate request code
                        $code = sprintf(
                            '%04d',
                            random_int(0, 9999)
                        );

                        // Create request
                        q(
                            "INSERT INTO requests
                            (donation_id, receiver_id, code)
                            VALUES (?, ?, ?)",
                            [
                                $d,
                                $u['id'],
                                $code
                            ]
                        );

                        // Save transaction
                        $pdo->commit();

                        header('Location: dashboard.php');
                        exit;

                    } else {

                        $pdo->rollBack();

                        $msg = 'Sorry, someone else just requested this food.';
                    }
                }

            } catch (Throwable $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                error_log($e->getMessage());

                $msg = 'Request could not be completed. Please try again.';
            }
        }
    }
}


/* =================================
   SEARCH AND CATEGORY FILTER
================================= */

$cat = (int)($_GET['cat'] ?? 0);

$s = trim($_GET['s'] ?? '');

$w = "
    WHERE d.status = 'available'
    AND (
        d.deadline IS NULL
        OR d.deadline > NOW()
    )
";

$p = [];

if ($cat > 0) {

    $w .= " AND d.category_id = ?";

    $p[] = $cat;
}

if ($s !== '') {

    $w .= " AND (
        d.title LIKE ?
        OR u.name LIKE ?
    )";

    $p[] = "%$s%";
    $p[] = "%$s%";
}


/* =================================
   GET AVAILABLE FOOD
================================= */

$rows = q(
    "SELECT
        d.*,
        u.name AS donor,
        f.image

     FROM donations AS d

     INNER JOIN users AS u
        ON u.id = d.donor_id

     LEFT JOIN foods AS f
        ON f.id = d.food_id

     $w

     ORDER BY d.id DESC",
    $p
)->fetchAll();


/* =================================
   GET FOOD CATEGORIES
================================= */

$cats = q(
    "SELECT *
     FROM categories
     ORDER BY name ASC"
)->fetchAll();


/* =================================
   PAGE DESIGN
================================= */

top('Find Food', 'Find Food');

?>

<div class="eyebrow">Food marketplace</div>

<h1 class="page">Find food near you</h1>

<p class="sub">
    Browse surplus food shared by verified donors in your community.
</p>

<?php if ($msg): ?>

    <div class="err">
        <?= e($msg) ?>
    </div>

<?php endif; ?>


<!-- SEARCH AND FILTER -->

<form
    class="two"
    method="get"
    style="grid-template-columns:2fr 1fr auto"
>

    <input
        type="text"
        name="s"
        value="<?= e($s) ?>"
        placeholder="Search food items or donors..."
    >

    <select
        name="cat"
        onchange="this.form.submit()"
    >

        <option value="0">
            All categories
        </option>

        <?php foreach ($cats as $c): ?>

            <option
                value="<?= (int)$c['id'] ?>"
                <?= $cat == $c['id'] ? 'selected' : '' ?>
            >

                <?= e($c['emoji']) ?>
                <?= e($c['name']) ?>

            </option>

        <?php endforeach; ?>

    </select>

    <button type="submit">
        Search
    </button>

</form>

<br>


<!-- AVAILABLE FOOD LIST -->

<div class="grid">

    <?php foreach ($rows as $d): ?>

        <?php

        $button = '

            <form method="post">

                <input
                    type="hidden"
                    name="did"
                    value="' . (int)$d['id'] . '"
                >

                <button
                    type="submit"
                    style="width:100%"
                >
                    Request this food
                </button>

            </form>

        ';

        card($d, $button);

        ?>

    <?php endforeach; ?>


    <?php if (!$rows): ?>

        <p class="sub">
            No food available right now.
        </p>

    <?php endif; ?>

</div>


<?php bottom(); ?>
