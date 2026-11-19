
<?php

require_once __DIR__ . '/includes/layout.php';

$u = me();

top('Ranking', $u ? 'Ranking' : 'public');

/* =====================================
   LEADERBOARD FUNCTION
===================================== */

function board($title, $sql) {

    $rows = q($sql)->fetchAll();

    echo '<div class="panel">';

    echo '<h3>' . e($title) . '</h3>';

    echo '<table>';

    echo '
        <tr>
            <th>#</th>
            <th>Name</th>
            <th>Meals / Trips</th>
            <th>Rating</th>
        </tr>
    ';

    if (!empty($rows)) {

        $i = 1;

        foreach ($rows as $r) {

            $rank = $i == 1 ? '🥇' :
                    ($i == 2 ? '🥈' :
                    ($i == 3 ? '🥉' : $i));

            $rating = $r['rating'];

            echo '<tr>';

            echo '<td>' . $rank . '</td>';

            echo '<td>' . e($r['name']) . '</td>';

            echo '<td>' . number_format((float)$r['score']) . '</td>';

            echo '<td>';

            if ($rating !== null) {

                echo '<span class="rating-star">★</span> ';
                echo '<span class="rating-number">'
                    . number_format((float)$rating, 1)
                    . '</span>';

            } else {

                echo '—';

            }

            echo '</td>';

            echo '</tr>';

            $i++;
        }

    } else {

        echo '
            <tr>
                <td colspan="4">
                    No ranking data available yet.
                </td>
            </tr>
        ';

    }

    echo '</table>';

    echo '</div>';
}

/* =====================================
   PAGE CONTENT
===================================== */

?>

<div style="padding:<?= $u ? '0' : '30px 8%' ?>">

    <div class="eyebrow">
        Leaderboard
    </div>

    <h1 class="page">
        Top rescuers
    </h1>

    <!-- TOP DONORS -->

    <?php

    board(
        'Top Donors (meals delivered)',

        "SELECT
            u.name,
            SUM(d.qty) AS score,

            (
                SELECT AVG(rt.rating)
                FROM ratings rt
                WHERE rt.reviewee_id = u.id
                AND rt.reviewee_type = 'donor'
            ) AS rating

         FROM requests r

         JOIN donations d ON d.id = r.donation_id
         JOIN users u ON u.id = d.donor_id

         WHERE r.status = 'delivered'

         GROUP BY u.id, u.name

         ORDER BY score DESC

         LIMIT 10"
    );

    ?>

    <!-- TOP VOLUNTEERS -->

    <?php

    board(
        'Top Volunteers (deliveries)',

        "SELECT
            u.name,
            COUNT(*) AS score,

            (
                SELECT AVG(rt.rating)
                FROM ratings rt
                WHERE rt.reviewee_id = u.id
                AND rt.reviewee_type = 'volunteer'
            ) AS rating

         FROM requests r

         JOIN users u ON u.id = r.volunteer_id

         WHERE r.status = 'delivered'
         AND r.volunteer_id IS NOT NULL

         GROUP BY u.id, u.name

         ORDER BY score DESC

         LIMIT 10"
    );

    ?>

    <!-- TOP RECEIVERS -->

    <?php

    board(
        'Top Receivers / NGOs (meals received)',

        "SELECT
            u.name,
            SUM(d.qty) AS score,

            (
                SELECT AVG(rt.rating)
                FROM ratings rt
                WHERE rt.reviewee_id = u.id
                AND rt.reviewee_type = 'receiver'
            ) AS rating

         FROM requests r

         JOIN donations d ON d.id = r.donation_id
         JOIN users u ON u.id = r.receiver_id

         WHERE r.status = 'delivered'

         GROUP BY u.id, u.name

         ORDER BY score DESC

         LIMIT 10"
    );

    ?>

</div>

<?php bottom(); ?>
