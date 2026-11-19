
<?php
require_once __DIR__ . '/../includes/layout.php';

$u = need('admin');

/* =====================================
   SAFE COUNT HELPER
===================================== */

function adminCount($sql) {
    try {
        return (int) q($sql)->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

/* =====================================
   DASHBOARD STATISTICS
===================================== */

$totalUsers = adminCount("SELECT COUNT(*) FROM users");

$totalDonors = adminCount(
    "SELECT COUNT(*) FROM users WHERE role='donor'"
);

$totalReceivers = adminCount(
    "SELECT COUNT(*) FROM users WHERE role='receiver'"
);

$totalVolunteers = adminCount(
    "SELECT COUNT(*) FROM users WHERE role='volunteer'"
);

$totalFoods = adminCount("SELECT COUNT(*) FROM foods");

$totalCategories = adminCount("SELECT COUNT(*) FROM categories");

$totalDonations = adminCount("SELECT COUNT(*) FROM donations");

$totalRequests = adminCount("SELECT COUNT(*) FROM requests");

$pendingDonations = adminCount(
    "SELECT COUNT(*) FROM donations
     WHERE status IN ('draft','scheduled')"
);

$availableDonations = adminCount(
    "SELECT COUNT(*) FROM donations
     WHERE status='available'"
);

$completedDeliveries = adminCount(
    "SELECT COUNT(*) FROM requests
     WHERE status='delivered'"
);

$totalRatings = adminCount("SELECT COUNT(*) FROM ratings");

$totalReviews = adminCount("SELECT COUNT(*) FROM reviews");

/* =====================================
   MEALS RESCUED
===================================== */

$mealsRescued = adminCount(
    "SELECT COALESCE(SUM(d.qty),0)
     FROM requests r
     JOIN donations d ON d.id=r.donation_id
     WHERE r.status='delivered'"
);

/* =====================================
   RECENT DONATIONS
===================================== */

$recentDonations = [];

try {
    $recentDonations = q("
        SELECT
            d.id,
            d.title,
            d.qty,
            d.unit,
            d.pickup_location,
            d.status,
            d.created,
            u.name AS donor_name
        FROM donations d
        LEFT JOIN users u ON u.id=d.donor_id
        ORDER BY d.id DESC
        LIMIT 8
    ")->fetchAll();

} catch (Throwable $e) {
    $recentDonations = [];
}

/* =====================================
   PAGE HEADER
===================================== */

top('Admin Dashboard', 'Dashboard');
?>

<style>

.admin-wrap {
    max-width:1200px;
    margin:auto;
    padding:25px 20px;
}

.admin-heading {
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:15px;
    flex-wrap:wrap;
    margin-bottom:25px;
}

.admin-heading h1 {
    margin:5px 0;
    font-size:27px;
}

.admin-heading p {
    color:#718078;
    margin:5px 0;
}

.admin-label {
    color:#198754;
    font-weight:700;
    font-size:12px;
    letter-spacing:1px;
}

.admin-grid {
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(185px,1fr));
    gap:16px;
    margin:22px 0;
}

.admin-stat {
    background:#fff;
    border:1px solid #e6eee8;
    border-radius:15px;
    padding:20px;
    box-shadow:0 3px 12px #183b2510;
}

.admin-stat .stat-icon {
    font-size:25px;
}

.admin-stat h3 {
    font-size:27px;
    margin:12px 0 4px;
}

.admin-stat p {
    color:#718078;
    margin:0;
    font-size:13px;
}

.admin-section {
    background:#fff;
    border:1px solid #e6eee8;
    border-radius:15px;
    padding:20px;
    margin-top:22px;
    overflow-x:auto;
}

.admin-section h2 {
    font-size:19px;
    margin:0 0 17px;
}

.admin-table {
    width:100%;
    border-collapse:collapse;
    min-width:650px;
}

.admin-table th,
.admin-table td {
    padding:13px 12px;
    text-align:left;
    border-bottom:1px solid #edf1ed;
    font-size:13px;
}

.admin-table th {
    color:#718078;
    background:#f8faf8;
}

.admin-status {
    display:inline-block;
    padding:5px 10px;
    border-radius:20px;
    background:#e9f7ef;
    color:#198754;
    font-size:11px;
    font-weight:700;
}

.admin-links {
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    margin-top:15px;
}

.admin-links a {
    padding:10px 15px;
    border-radius:9px;
    background:#edf7f0;
    color:#167344;
    text-decoration:none;
    font-weight:600;
    font-size:13px;
    transition:.2s;
}

.admin-links a:hover {
    background:#d9efdf;
}

.admin-empty {
    padding:25px;
    text-align:center;
    color:#718078;
}

@media(max-width:600px) {

    .admin-wrap {
        padding:15px 10px;
    }

    .admin-heading h1 {
        font-size:22px;
    }

    .admin-section {
        padding:14px;
    }

    .admin-grid {
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:10px;
    }

    .admin-stat {
        padding:14px;
    }

    .admin-stat h3 {
        font-size:23px;
    }

}

</style>


<div class="admin-wrap">

    <!-- HEADER -->

    <div class="admin-heading">

        <div>

            <span class="admin-label">
                ADMIN WORKSPACE
            </span>

            <h1>
    Welcome, <?= e($u['name'] ?? 'Admin') ?>! 👋
</h1>

            <p>
                Manage RePlate and monitor food rescue activities.
            </p>

        </div>

        <span class="admin-status">
            ● System Overview
        </span>

    </div>


    <!-- PLATFORM OVER