
<?php
require_once __DIR__ . '/../includes/layout.php';

need('donor');
$u = me();
$donorId = (int)$u['id'];

$errors = [];
$success = '';

/* =========================
   LOAD CATEGORIES & FOODS
========================= */

$categories = q("SELECT id, emoji, name FROM categories ORDER BY name")->fetchAll();

$foods = q("
    SELECT id, category_id, name, image
    FROM foods
    ORDER BY name
")->fetchAll();

/* =========================
   DEFAULT VALUES
========================= */

$old = [
    'category' => '',
    'food' => '',
    'title' => '',
    'qty' => '',
    'unit' => 'meals',
    'description' => '',
    'pickup_location' => '',
    'available_from' => '',
    'deadline' => '',
    'instructions' => '',
    'status' => 'available'
];

/* =========================
   HANDLE FORM SUBMISSION
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    foreach ($old as $key => $value) {
        if (isset($_POST[$key])) {
            $old[$key] = trim((string)$_POST[$key]);
        }
    }

    $categoryId = (int)$old['category'];
    $foodId = (int)$old['food'];
    $qty = (float)$old['qty'];

    $allowedUnits = [
        'meals',
        'meal boxes',
        'packs',
        'kg',
        'pieces',
        'liters'
    ];

    $allowedStatuses = [
        'available',
        'scheduled',
        'draft'
    ];

    /* Check category */
    $category = q(
        "SELECT id FROM categories WHERE id = ?",
        [$categoryId]
    )->fetch();

    if (!$category) {
        $errors[] = "Please select a valid category.";
    }

    /* Check food and category */
    $food = q(
        "SELECT id, category_id, name, image
         FROM foods
         WHERE id = ? AND category_id = ?",
        [$foodId, $categoryId]
    )->fetch();

    if (!$food) {
        $errors[] = "Please select a valid food item.";
    }

    if ($qty <= 0) {
        $errors[] = "Quantity must be greater than zero.";
    }

    if (!in_array($old['unit'], $allowedUnits, true)) {
        $errors[] = "Please select a valid unit.";
    }

    if ($old['pickup_location'] === '') {
        $errors[] = "Pickup location is required.";
    }

    if (!in_array($old['status'], $allowedStatuses, true)) {
        $errors[] = "Invalid donation status.";
    }

    /* Validate dates */
    $availableFrom = $old['available_from'];
    $deadline = $old['deadline'];

    if ($availableFrom !== '') {
        $date = DateTime::createFromFormat('Y-m-d\TH:i', $availableFrom);

        if (!$date || $date->format('Y-m-d\TH:i') !== $availableFrom) {
            $errors[] = "Invalid availability date.";
        }
    } else {
        $availableFrom = date('Y-m-d H:i:s');
    }

    if ($deadline !== '') {
        $date = DateTime::createFromFormat('Y-m-d\TH:i', $deadline);

        if (!$date || $date->format('Y-m-d\TH:i') !== $deadline) {
            $errors[] = "Invalid pickup deadline.";
        } else {
            $deadline = $date->format('Y-m-d H:i:s');
        }
    } else {
        $deadline = null;
    }

    if (
        $deadline !== null &&
        strtotime($deadline) <= strtotime($availableFrom)
    ) {
        $errors[] = "Pickup deadline must be later than availability time.";
    }

    /* Scheduled donation validation */
    if (
        $old['status'] === 'scheduled' &&
        strtotime($availableFrom) <= time()
    ) {
        $errors[] = "Scheduled donations must have a future availability time.";
    }

    /* =========================
       SAVE DONATION
    ========================= */

    if (empty($errors)) {

        $title = $old['title'] !== ''
            ? $old['title']
            : $food['name'];

        q("
            INSERT INTO donations
            (
                donor_id,
                food_id,
                category_id,
                title,
                qty,
                unit,
                description,
                pickup_location,
                available_from,
                deadline,
                instructions,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            $donorId,
            $foodId,
            $categoryId,
            $title,
            $qty,
            $old['unit'],
            $old['description'],
            $old['pickup_location'],
            $availableFrom,
            $deadline,
            $old['instructions'],
            $old['status']
        ]);

        header("Location: " . BASE . "/donor/dashboard.php?success=donation");
        exit;
    }
}

/* =========================
   IMAGE DATA FOR JAVASCRIPT
========================= */

$foodJson = json_encode(
    $foods,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
);

top('Add Donation', 'Add Donation');
?>

<style>
.add-donation-page {
    max-width: 1050px;
    margin: 25px auto;
    padding: 20px;
}

.add-donation-page .page-heading {
    margin-bottom: 25px;
}

.add-donation-page .page-heading h1 {
    font-size: 30px;
    margin: 12px 0;
}

.add-donation-page .form-section {
    background: var(--card, #fff);
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 14px;
    padding: 24px;
    margin-bottom: 22px;
}

.add-donation-page .form-section h3 {
    margin: 0 0 20px;
}

.add-donation-page .form-group {
    margin-bottom: 18px;
}

.add-donation-page label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
}

.add-donation-page input,
.add-donation-page select,
.add-donation-page textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #dce3df;
    border-radius: 9px;
    font: inherit;
    background: var(--card, #fff);
    color: inherit;
}

.add-donation-page textarea {
    min-height: 100px;
    resize: vertical;
}

.add-donation-page .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}

.add-donation-page .food-preview {
    width: 190px;
    height: 145px;
    object-fit: cover;
    border-radius: 12px;
    border: 1px solid #ddd;
}

.add-donation-page .status-options {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
}

.add-donation-page .status-options label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
}

.add-donation-page .status-options input {
    width: auto;
}

.add-donation-page .form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 20px;
}

.add-donation-page .form-actions button,
.add-donation-page .form-actions a {
    padding: 12px 22px;
    border-radius: 9px;
    border: 0;
    cursor: pointer;
    text-decoration: none;
    font-weight: 600;
}

.add-donation-page .publish-btn {
    background: #168653;
    color: white;
}

.add-donation-page .draft-btn {
    background: #edf2ef;
    color: #245c40;
}

.add-donation-page .cancel-btn {
    background: #f3f4f6;
    color: #333;
}

.add-donation-page .error-box {
    padding: 15px;
    background: #fff0f0;
    color: #a52222;
    border-radius: 10px;
    margin-bottom: 20px;
}

@media(max-width:650px) {
    .add-donation-page .form-row {
        grid-template-columns: 1fr;
    }

    .add-donation-page .form-actions {
        flex-wrap: wrap;
    }
}
</style>

<div class="add-donation-page">

    <div class="page-heading">

        <a href="<?= BASE ?>/donor/dashboard.php">
            ← Back to donor dashboard
        </a>

        <p>Food donation</p>

        <h1>Add surplus food</h1>

        <p>
            Share safe, quality food with verified receivers
            in your community.
        </p>

    </div>

    <?php if (!empty($errors)): ?>
        <div class="error-box">
            <strong>Please fix the following:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" id="donationForm">

        <!-- FOOD INFORMATION -->

        <section class="form-section">

            <h3>Food information</h3>

            <div class="form-group">

                <label>Category *</label>

                <select name="category" id="category" required>

                    <option value="">Select category</option>

                    <?php foreach ($categories as $cat): ?>

                        <option
                            value="<?= (int)$cat['id'] ?>"
                            <?= $old['category'] == $cat['id'] ? 'selected' : '' ?>
                        >
                            <?= e($cat['emoji'] . ' ' . $cat['name']) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="form-group">

                <label>Food item * (500+ items)</label>

                <select name="food" id="food" required>

                    <option value="">Select food item</option>

                </select>

            </div>

            <div class="form-group">

                <label>Listing title (optional)</label>

                <input
                    type="text"
                    name="title"
                    maxlength="200"
                    value="<?= e($old['title']) ?>"
                    placeholder="e.g. Fresh Chicken Biryani"
                >

            </div>

            <div class="form-row">

                <div class="form-group">

                    <label>Quantity *</label>

                    <input
                        type="number"
                        name="qty"
                        min="0.01"
                        step="0.01"
                        value="<?= e($old['qty']) ?>"
                        placeholder="Enter quantity"
                        required
                    >

                </div>

                <div class="form-group">

                    <label>Unit</label>

                    <select name="unit">

                        <?php foreach (
                            ['meals', 'meal boxes', 'packs', 'kg', 'pieces', 'liters']
                            as $unit
                        ): ?>

                            <option
                                value="<?= e($unit) ?>"
                                <?= $old['unit'] === $unit ? 'selected' : '' ?>
                            >
                                <?= e(ucfirst($unit)) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

            </div>

            <div class="form-group">

                <label>Description</label>

                <textarea
                    name="description"
                    placeholder="Describe food quality, preparation time and other details..."
                ><?= e($old['description']) ?></textarea>

            </div>

        </section>

        <!-- PICKUP INFORMATION -->

        <section class="form-section">

            <h3>Pickup information</h3>

            <div class="form-group">

                <label>Pickup location *</label>

                <input
                    type="text"
                    name="pickup_location"
                    value="<?= e($old['pickup_location']) ?>"
                    placeholder="Enter pickup address"
                    required
                >

            </div>

            <div class="form-row">

                <div class="form-group">

                    <label>Available from</label>

                    <input
                        type="datetime-local"
                        name="available_from"
                        id="available_from"
                        value="<?= e($old['available_from']) ?>"
                    >

                </div>

                <div class="form-group">

                    <label>Pickup deadline</label>

                    <input
                        type="datetime-local"
                        name="deadline"
                        value="<?= e($old['deadline']) ?>"
                    >

                </div>

            </div>

            <div class="form-group">

                <label>Pickup instructions</label>

                <textarea
                    name="instructions"
                    placeholder="Enter pickup instructions..."
                ><?= e($old['instructions']) ?></textarea>

            </div>

        </section>

        <!-- FOOD IMAGE -->

        <section class="form-section">

            <h3>Food image</h3>

            <img
                id="foodImage"
                class="food-preview"
                src="<?= BASE ?>/assets/images/foods/plain-steamed-rice.webp"
                alt="Selected food"
            >

            <p id="imageName">Select a food to preview its image.</p>

        </section>

        <!-- DONATION STATUS -->

        <section class="form-section">

            <h3>Donation status</h3>

            <div class="status-options">

                <label>
                    <input
                        type="radio"
                        name="status"
                        value="available"
                        <?= $old['status'] === 'available' ? 'checked' : '' ?>
                    >
                    Available now
                </label>

                <label>
                    <input
                        type="radio"
                        name="status"
                        value="scheduled"
                        <?= $old['status'] === 'scheduled' ? 'checked' : '' ?>
                    >
                    Scheduled
                </label>

                <label>
                    <input
                        type="radio"
                        name="status"
                        value="draft"
                        <?= $old['status'] === 'draft' ? 'checked' : '' ?>
                    >
                    Save as draft
                </label>

            </div>

        </section>

        <div class="form-actions">

            <a
                href="<?= BASE ?>/donor/dashboard.php"
                class="cancel-btn"
            >
                Cancel
            </a>

            <button
                type="submit"
                class="draft-btn"
                name="status"
                value="draft"
            >
                Save as draft
            </button>

            <button
                type="submit"
                class="publish-btn"
                id="publishButton"
            >
                ＋ Publish donation
            </button>

        </div>

    </form>

</div>

<script>
/* =========================
   FOOD CATALOG
========================= */

const allFoods = <?= $foodJson ?: '[]' ?>;

const categorySelect = document.getElementById('category');
const foodSelect = document.getElementById('food');
const foodImage = document.getElementById('foodImage');
const imageName = document.getElementById('imageName');

const BASE = <?= json_encode(BASE) ?>;

const oldFood = <?= json_encode($old['food']) ?>;

function updateFoods(selectedFood = '') {

    const categoryId = categorySelect.value;

    foodSelect.innerHTML =
        '<option value="">Select food item</option>';

    const filteredFoods = allFoods.filter(food =>
        String(food.category_id) === String(categoryId)
    );

    filteredFoods.forEach(food => {

        const option = document.createElement('option');

        option.value = food.id;
        option.textContent = food.name;

        if (String(food.id) === String(selectedFood)) {
            option.selected = true;
        }

        foodSelect.appendChild(option);

    });

    updateImage();
}

function updateImage() {

    const food = allFoods.find(item =>
        String(item.id) === String(foodSelect.value)
    );

    if (food) {

        const filename = String(food.image || '').split('/').pop();

        if (
            filename &&
            /^[a-zA-Z0-9._-]+$/.test(filename)
        ) {
            foodImage.src =
                BASE + '/assets/images/foods/' + filename;
        } else {
            foodImage.src =
                BASE + '/assets/images/foods/plain-steamed-rice.webp';
        }

        imageName.textContent = food.name;

    } else {

        foodImage.src =
            BASE + '/assets/images/foods/plain-steamed-rice.webp';

        imageName.textContent = 'Select a food to preview its image.';

    }
}

categorySelect.addEventListener('change', () => {
    updateFoods();
});

foodSelect.addEventListener('change', updateImage);

/* Restore selected food after validation error */
updateFoods(oldFood);

/* Available Now uses current date/time */
document.getElementById('donationForm')
    .addEventListener('submit', function(event) {

        const submitter = event.submitter;

        if (submitter && submitter.value === 'draft') {
            // Draft is saved using the submit button's value.
            // PHP receives the posted status.
        }

    });

</script>

<?php bottom(); ?>
