
<?php

require_once __DIR__.'/db.php';

/* =================================
   ROLE BASED NAVIGATION
================================= */

function menu($r){

    $m = [

        'donor' => [
            'Overview' => ['/donor/dashboard.php','▦'],
            'Add Donation' => ['/donor/add.php','＋'],
            'Food Catalog' => ['/foods.php','🍽']
        ],

        'receiver' => [
            'Overview' => ['/receiver/dashboard.php','▦'],
            'Find Food' => ['/receiver/find.php','🔍'],
            'Food Catalog' => ['/foods.php','🍽']
        ],

        'volunteer' => [
            'Overview' => ['/volunteer/dashboard.php','▦'],
            'Food Catalog' => ['/foods.php','🍽']
        ],

        'admin' => [
            'Dashboard' => ['/admin/dashboard.php','▦'],
            'Manage Users' => ['/admin/manage-users.php','👥'],
            'Manage Donations' => ['/admin/donations.php','🍱'],
            'Manage Requests' => ['/admin/requests.php','📋'],
            'Food Catalog' => ['/foods.php','🍽']
        ]

    ];

    if(!isset($m[$r])){
        return [];
    }

    $m[$r]['Ranking'] = ['/ranking.php','🏆'];

    return $m[$r];
}


/* =================================
   ROLE DASHBOARD URL
================================= */

function dashboardUrl($role){

    $urls = [

        'donor' => '/donor/dashboard.php',
        'receiver' => '/receiver/dashboard.php',
        'volunteer' => '/volunteer/dashboard.php',
        'admin' => '/admin/dashboard.php'

    ];

    return $urls[$role] ?? '/login.php';
}


/* =================================
   TOP LAYOUT
================================= */

function top($t,$active=''){

    global $layoutApp;

    $u = me();

    $layoutApp = ($u && $active !== 'public');

    $role = $u['role'] ?? '';
    $name = $u['name'] ?? 'User';

?>

<!doctype html>
<html lang="en">

<head>

<meta charset="utf-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title><?=e($t)?> · RePlate</title>

<link rel="stylesheet"
href="<?=BASE?>/assets/css/style.css?v=7">

<link rel="stylesheet"
href="<?=BASE?>/assets/css/dashboard.css?v=7">

<link rel="stylesheet"
href="<?=BASE?>/assets/css/responsive.css?v=7">

</head>

<body>

<?php if($layoutApp): ?>

<!-- =================================
     DASHBOARD LAYOUT
================================= -->

<div class="app">

    <aside>

        <a class="logo" href="<?=BASE?>/">
            <i>🍃</i> RePlate
        </a>

        <small>WORKSPACE</small>

        <?php foreach(menu($role) as $l=>$a): ?>

            <a
                class="nav <?=($active === $l || ($active === 'Dashboard' && $l === 'Overview')) ? 'on' : ''?>"
                href="<?=BASE.$a[0]?>"
            >

                <span><?=e($a[1])?></span>

                <?=e($l)?>

            </a>

        <?php endforeach; ?>


        <div class="sidefoot">

            <div class="mini">

                💚

                <b>

                    <?=number_format((int)meals())?>

                    meals rescued

                </b>

                <br>

                <span>
                    Together every plate makes an impact
                </span>

            </div>


            <a class="nav" href="<?=BASE?>/logout.php">

                ⎋ Logout

            </a>

        </div>

    </aside>


    <main>

        <div class="topbar">

            <span>

                <?=e($name)?>

                ·

                <?=e(ucfirst($role))?>

                <?=($u['status'] ?? '') === 'verified'
                    ? ' ✔'
                    : ' (pending verification)'?>

            </span>

        </div>


<?php else: ?>


<!-- =================================
     PUBLIC NAVIGATION
================================= -->

<header class="pub">

    <a class="logo" href="<?=BASE?>/">

        <i>🍃</i>

        RePlate

    </a>


    <nav>

        <a href="<?=BASE?>/">
            Home
        </a>

        <a href="<?=BASE?>/#available">
            Available Food
        </a>

        <a href="<?=BASE?>/#how">
            How It Works
        </a>

        <a href="<?=BASE?>/#about">
            About Us
        </a>

        <a href="<?=BASE?>/foods.php">
            Food Catalog
        </a>

        <a href="<?=BASE?>/ranking.php">
            Ranking
        </a>

    </nav>


    <div>

        <?php if($u): ?>

            <a
                class="btn ghost"
                href="<?=BASE.dashboardUrl($role)?>"
            >

                Dashboard

            </a>

        <?php else: ?>

            <a
                class="btn ghost"
                href="<?=BASE?>/login.php"
            >

                Log in

            </a>


            <a
                class="btn"
                href="<?=BASE?>/login.php?mode=register"
            >

                Join Us

            </a>

        <?php endif; ?>

    </div>

</header>


<main class="wide">


<?php endif; ?>

<?php
}


/* =================================
   BOTTOM LAYOUT
================================= */

function bottom(){

    global $layoutApp;

    echo '</main>';

    if(!empty($layoutApp)){

        echo '</div>';

    }

    echo '</body></html>';

}


/* =================================
   FOOD CARD
================================= */

function card($d,$btn=''){

?>

<div class="card food">

    <div class="im">

        <img

            src="<?=img($d['image'] ?? '')?>"

            alt="<?=e($d['title'] ?? 'Food')?>"

            loading="lazy"

        >

        <span class="tag">

            ● Available now

        </span>

    </div>


    <div class="bd">

        <small>

            <?=e($d['donor'] ?? 'RePlate Donor')?>

        </small>


        <h4>

            <?=e($d['title'] ?? 'Food Item')?>

        </h4>


        <p class="meta">

            📦

            <?=e(($d['qty'] ?? 0).' '.($d['unit'] ?? ''))?>

            &nbsp;

            📍

            <?=e($d['pickup_location'] ?? 'Location unavailable')?>

            <br>

            🕒 Pickup by

            <?=!empty($d['deadline'])
                ? e(date('d M, g:i A',strtotime($d['deadline'])))
                : '—'?>

        </p>


        <?=$btn?>

    </div>

</div>

<?php

}

?>
