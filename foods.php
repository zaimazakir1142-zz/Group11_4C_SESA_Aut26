
<?php
require_once __DIR__ . '/includes/layout.php';

$u = me();

/* =================================
   PUBLIC FOOD CATALOG
================================= */

top('Food Catalog', 'public');


/* =================================
   SEARCH & FILTER
================================= */

$cat = max(0, (int)($_GET['cat'] ?? 0));
$s = trim($_GET['s'] ?? '');
$pg = max(1, (int)($_GET['p'] ?? 1));

$per = 100;

$w = 'WHERE 1=1';
$params = [];

if ($cat > 0) {
    $w .= ' AND f.category_id = ?';
    $params[] = $cat;
}

if ($s !== '') {
    $w .= ' AND f.name LIKE ?';
    $params[] = '%' . $s . '%';
}


/* =================================
   TOTAL FOOD COUNT
================================= */

$countQuery = q(
    "SELECT COUNT(*)
     FROM foods f
     $w",
    $params
);

$total = (int)$countQuery->fetchColumn();

$pages = max(1, (int)ceil($total / $per));

$pg = min($pg, $pages);

$offset = ($pg - 1) * $per;


/* =================================
   FETCH FOOD ITEMS
================================= */

$rows = q(
    "SELECT
        f.*,
        c.name AS cname,
        c.emoji AS cemoji
     FROM foods f
     LEFT JOIN categories c
        ON c.id = f.category_id
     $w
     ORDER BY f.id ASC
     LIMIT $per OFFSET $offset",
    $params
)->fetchAll();


/* =================================
   FETCH CATEGORIES
================================= */

$cats = q(
    "SELECT *
     FROM categories
     ORDER BY id ASC"
)->fetchAll();


/* =================================
   PAGINATION QUERY
================================= */

$queryBase = http_build_query([
    'cat' => $cat,
    's' => $s
]);

?>

<style>

/* =================================
   MAIN CATALOG
================================= */

.food-catalog {
    width: 100%;
    max-width: 1250px;
    margin: 0 auto;
    padding: 35px 25px;
    box-sizing: border-box;
}


/* =================================
   CATALOG HEADER
================================= */

.catalog-heading {
    margin-bottom: 25px;
}

.catalog-eyebrow {
    color: #16a36a;
    font-size: 13px;
    font-weight: 800;
    letter-spacing: 1px;
    text-transform: uppercase;
    margin-bottom: 8px;
}

.catalog-title {
    color: #18352a;
    font-size: clamp(27px, 4vw, 38px);
    font-weight: 800;
    line-height: 1.3;
    margin-bottom: 10px;
}

.catalog-subtitle {
    color: #718078;
    font-size: 15px;
}


/* =================================
   SEARCH FORM
================================= */

.food-search {
    display: grid;
    grid-template-columns: 2fr 1.3fr auto;
    gap: 12px;
    width: 100%;
    margin: 28px 0;
}

.food-search input,
.food-search select {
    width: 100%;
    min-width: 0;
    height: 48px;
    padding: 0 15px;
    border: 1px solid #e0eae3;
    border-radius: 9px;
    background: white;
    color: #18352a;
    font-size: 14px;
    outline: none;
    box-sizing: border-box;
}

.food-search input:focus,
.food-search select:focus {
    border-color: #16a36a;
}

.food-search button {
    height: 48px;
    padding: 0 25px;
    border: none;
    border-radius: 9px;
    background: #16a36a;
    color: white;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    transition: .2s;
}

.food-search button:hover {
    background: #087d50;
}


/* =================================
   FOOD INFORMATION
================================= */

.food-count {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    margin: 22px 0;
    color: #718078;
    font-size: 14px;
}

.food-count strong {
    color: #18352a;
}


/* =================================
   FOOD GRID
================================= */

.food-catalog-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 22px;
    align-items: stretch;
}


/* =================================
   FOOD CARD
================================= */

.food-catalog-card {
    width: 100%;
    min-width: 0;
    min-height: 310px;
    display: flex;
    flex-direction: column;
    background: #fff;
    border: 1px solid #e7eee9;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 3px 12px rgba(0,0,0,.05);
    box-sizing: border-box;
    transition: transform .25s, box-shadow .25s;
}

.food-catalog-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 9px 24px rgba(0,0,0,.10);
}


/* =================================
   FOOD IMAGE
================================= */

.food-catalog-image {
    position: relative;
    display: block;
    width: 100%;
    height: 190px;
    min-height: 190px;
    flex: 0 0 190px;
    overflow: hidden;
    background: #f1f5f2;
}

.food-catalog-image img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    padding: 0;
    margin: 0;
    border: none;
}


/* =================================
   IMAGE BADGE
================================= */

.food-image-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    padding: 5px 11px;
    background: #e5f9ed;
    color: #087d50;
    font-size: 11px;
    font-weight: 700;
    border-radius: 20px;
}


/* =================================
   FOOD BODY
================================= */

.food-catalog-body {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-width: 0;
    padding: 14px;
    box-sizing: border-box;
}

.food-catalog-body small {
    display: block;
    color: #218653;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    margin-bottom: 7px;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.food-catalog-body h4 {
    color: #18352a;
    font-size: 16px;
    line-height: 1.4;
    min-height: 44px;
    margin: 0 0 10px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}


/* =================================
   DONATE BUTTON
================================= */

.food-donate {
    display: inline-flex;
    align-items: center;
    margin-top: auto;
    padding-top: 8px;
    color: #16a36a;
    font-size: 13px;
    font-weight: 800;
    text-decoration: none;
}

.food-donate:hover {
    color: #087d50;
}


/* =================================
   EMPTY RESULT
================================= */

.food-empty {
    grid-column: 1 / -1;
    padding: 55px 20px;
    background: white;
    border: 1px solid #e7eee9;
    border-radius: 15px;
    text-align: center;
}

.food-empty h3 {
    margin-bottom: 10px;
}

.food-empty p {
    color: #718078;
    margin-bottom: 20px;
}


/* =================================
   PAGINATION
================================= */

.food-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    margin: 35px 0;
}

.food-pagination a {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 38px;
    height: 38px;
    padding: 0 12px;
    background: white;
    border: 1px solid #e1ebe4;
    border-radius: 8px;
    color: #18352a;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
}

.food-pagination a:hover,
.food-pagination a.on {
    background: #16a36a;
    color: white;
    border-color: #16a36a;
}


/* =================================
   TABLET
================================= */

@media(max-width:1000px) {

    .food-catalog-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 18px;
    }

}


/* =================================
   SMALL TABLET
================================= */

@media(max-width:700px) {

    .food-catalog {
        padding: 25px 18px;
    }

    .food-catalog-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .food-search {
        grid-template-columns: 1fr 1fr;
    }

    .food-search button {
        grid-column: 1 / -1;
    }

}


/* =================================
   MOBILE
================================= */

@media(max-width:450px) {

    .food-catalog {
        padding: 20px 12px;
    }

    .food-catalog-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .food-catalog-card {
        min-height: 280px;
    }

    .food-catalog-image {
        height: 160px;
        min-height: 160px;
        flex-basis: 160px;
    }

    .food-catalog-body {
        padding: 10px;
    }

    .food-catalog-body h4 {
        font-size: 13px;
        min-height: 38px;
    }

    .food-catalog-body small {
        font-size: 10px;
    }

    .food-donate {
        font-size: 12px;
    }

    .food-search {
        grid-template-columns: 1fr;
    }

    .food-search button {
        grid-column: auto;
    }

    .food-count {
        font-size: 12px;
    }

}

</style>


<!-- =================================
     FOOD CATALOG HTML
================================= -->

<div class="food-catalog">


    <!-- HEADER -->

    <div class="catalog-heading">

        <div class="catalog-eyebrow">
            REPLATE FOOD CATALOG
        </div>

        <h1 class="catalog-title">
            <?= number_format($total) ?> Foods to Explore
        </h1>

        <p class="catalog-subtitle">
            Explore delicious food and discover opportunities
            to reduce food waste.
        </p>

    </div>


    <!-- SEARCH & FILTER -->

    <form method="GET" action="<?= BASE ?>/foods.php"
          class="food-search">

        <input
            type="search"
            name="s"
            value="<?= e($s) ?>"
            placeholder="Search your favourite food..."
        >

        <select name="cat">

            <option value="0">
                All Categories
            </option>

            <?php foreach ($cats as $c): ?>

                <option
                    value="<?= (int)$c['id'] ?>"
                    <?= $cat == (int)$c['id'] ? 'selected' : '' ?>
                >

                    <?= e($c['emoji'] ?? '') ?>
                    <?= e($c['name']) ?>

                </option>

            <?php endforeach; ?>

        </select>

        <button type="submit">
            Search Food
        </button>

    </form>


    <!-- FOOD COUNT -->

    <div class="food-count">

        <span>
            Showing
            <strong><?= count($rows) ?></strong>
            of
            <strong><?= number_format($total) ?></strong>
            food items
        </span>

        <span>
            <strong><?= count($cats) ?></strong>
            Categories
        </span>

    </div>


    <!-- FOOD GRID -->

    <div class="food-catalog-grid">

        <?php if (!empty($rows)): ?>

            <?php foreach ($rows as $f): ?>

                <div class="food-catalog-card">

                    <!-- IMAGE -->

                    <div class="food-catalog-image">

                        <img
                            src="<?= e(img($f['image'] ?? '')) ?>"
                            alt="<?= e($f['name'] ?? 'Food') ?>"
                            loading="lazy"
                            onerror="this.onerror=null;this.src='<?= e(BASE) ?>/assets/images/food-placeholder.webp';"
                        >

                    </div>


                    <!-- BODY -->

                    <div class="food-catalog-body">

                        <small>
                            <?= e($f['cname'] ?? 'General Food') ?>
                        </small>

                        <h4>
                            <?= e($f['name'] ?? 'Food Item') ?>
                        </h4>


                        <?php if ($u && $u['role'] === 'donor'): ?>

                            <a
                                class="food-donate"
                                href="<?= BASE ?>/donor/add.php?food=<?= (int)$f['id'] ?>"
                            >
                                Donate this →
                            </a>

                        <?php endif; ?>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="food-empty">

                <h3>No Food Found</h3>

                <p>
                    Try another food name or category.
                </p>

                <a class="btn" href="<?= BASE ?>/foods.php">
                    View All Foods
                </a>

            </div>

        <?php endif; ?>

    </div>


    <!-- PAGINATION -->

    <?php if ($pages > 1): ?>

        <div class="food-pagination">

            <?php if ($pg > 1): ?>

                <a href="?<?= e($queryBase) ?>&p=<?= $pg - 1 ?>">
                    ← Previous
                </a>

            <?php endif; ?>


            <?php

            $start = max(1, $pg - 3);
            $end = min($pages, $pg + 3);

            for ($i = $start; $i <= $end; $i++):

            ?>

                <a
                    class="<?= $i == $pg ? 'on' : '' ?>"
                    href="?<?= e($queryBase) ?>&p=<?= $i ?>"
                >
                    <?= $i ?>
                </a>

            <?php endfor; ?>


            <?php if ($pg < $pages): ?>

                <a href="?<?= e($queryBase) ?>&p=<?= $pg + 1 ?>">
                    Next →
                </a>

            <?php endif; ?>

        </div>

    <?php endif; ?>


</div>


<?php
bottom();
?>
