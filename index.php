
<?php

require 'includes/layout.php';

top('Home','public');


/* ===============================
   AVAILABLE FOOD
================================ */

$c = q("
    SELECT
        d.*,
        u.name AS donor,
        f.image
    FROM donations d
    JOIN users u ON u.id = d.donor_id
    LEFT JOIN foods f ON f.id = d.food_id
    WHERE d.status = 'available'
    ORDER BY d.id DESC
    LIMIT 3
")->fetchAll();


/* ===============================
   USER COUNTERS
================================ */

$n = function($role) {

    return (int) q(
        "SELECT COUNT(*) FROM users WHERE role = ?",
        [$role]
    )->fetchColumn();

};

$mealCount = meals();
$donorCount = $n('donor');
$volunteerCount = $n('volunteer');

?>

<!-- HERO SECTION -->

<section class="hero">

    <div>

        <span class="pill">
            ♡ Good food belongs on plates
        </span>

        <h1>
            Rescue food.<br>
            Reduce waste.<br>
            <span>Feed hope.</span>
        </h1>

        <p class="sub">
            RePlate connects surplus food from local businesses
            and homes with communities that need it most—quickly,
            safely, and with dignity.
        </p>

        <a class="btn" href="#available">
            Explore Food →
        </a>

        <a class="btn ghost"
           href="<?=BASE?>/login.php?mode=register">
            Donate Food
        </a>

    </div>

    <div>

        <img
            src="<?=BASE?>/assets/images/hero.svg"
            alt="RePlate Food Donation"
        >

    </div>

</section>


<!-- HOW IT WORKS -->

<section class="sec" id="how">

    <div style="text-align:center">

        <div class="eyebrow">
            Simple by design
        </div>

        <h2>From extra food to extra smiles</h2>

        <p class="sub">
            Three easy steps turn today's surplus into
            nourishment for a neighbor.
        </p>

    </div>


    <div class="steps">

        <div class="card step">

            <i>📦</i>

            <h4>1. Donate Food</h4>

            <p class="sub">
                List safe, surplus food in just a few clicks
                and choose a convenient pickup time.
            </p>

        </div>


        <div class="card step">

            <i>🔍</i>

            <h4>2. Find Food</h4>

            <p class="sub">
                Verified receivers discover nearby food
                that matches their community's needs.
            </p>

        </div>


        <div class="card step">

            <i>🚚</i>

            <h4>3. Deliver Food</h4>

            <p class="sub">
                Local volunteers collect and deliver
                donations safely and on time, with live tracking.
            </p>

        </div>

    </div>

</section>


<!-- AVAILABLE FOOD -->

<section class="sec"
         id="available"
         style="background:#eaf3e6">

    <div class="eyebrow">
        Available near you
    </div>

    <h2 style="text-align:left">
        Fresh food, ready to share
    </h2>

    <p class="sub">

        Explore quality surplus food offered by trusted donors
        in your community.

        <a href="<?=BASE?>/foods.php">
            Browse the full catalog →
        </a>

    </p>


    <div class="grid">

        <?php

        foreach($c as $d) {

            card(
                $d,
                '<a href="'.BASE.'/receiver/find.php">View Details →</a>'
            );

        }

        if(!$c) {

            echo '<p class="sub">No listings right now.</p>';

        }

        ?>

    </div>

</section>


<!-- IMPACT SECTION -->

<section class="impact" id="about">

    <div>

        <div class="eyebrow"
             style="color:#bfe3c2">

            Our collective impact

        </div>

        <h2>
            Small acts. A lasting difference.
        </h2>

        <p>
            Every listing, pickup, and delivery brings us
            closer to a community where good food is never wasted.
        </p>

    </div>


    <div class="impact-stats">


        <div class="impact-item">

            <strong>
                <?=number_format($mealCount)?>
            </strong>

            <span>
                Meals Rescued
            </span>

        </div>


        <div class="impact-item">

            <strong>
                <?=number_format($donorCount)?>
            </strong>

            <span>
                Food Donors
            </span>

        </div>


        <div class="impact-item">

            <strong>
                <?=number_format($volunteerCount)?>
            </strong>

            <span>
                Active Volunteers
            </span>

        </div>


    </div>

</section>


<!-- CALL TO ACTION -->

<section class="cta">

    <div class="eyebrow"
         style="color:#d7f0d9">

        Your next act can matter

    </div>

    <h2>
        Be part of the change
    </h2>

    <p>
        Whether you have food to share or time to give,
        there is a place for you in the RePlate community.
    </p>

    <a class="btn ghost"
       href="<?=BASE?>/login.php?mode=register">

        Become a Donor

    </a>

    <a class="btn ghost"
       href="<?=BASE?>/login.php?mode=register">

        Join as Volunteer

    </a>

</section>


<?php bottom(); ?>
