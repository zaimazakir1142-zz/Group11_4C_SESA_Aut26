```php
<?php
require_once __DIR__ . '/includes/layout.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    $location = trim($_POST['location'] ?? '');

    if (!$name || !$email || !$password || !$role) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif (!in_array($role, ['donor', 'receiver', 'volunteer'])) {
        $error = 'Invalid account role.';
    } else {

        $exists = q(
            "SELECT id FROM users WHERE email = ?",
            [$email]
        )->fetch();

        if ($exists) {
            $error = 'This email is already registered.';
        } else {

            $hash = password_hash($password, PASSWORD_DEFAULT);

            q(
                "INSERT INTO users
                (name, email, password, role, status, location)
                VALUES (?, ?, ?, ?, 'pending', ?)",
                [$name, $email, $hash, $role, $location]
            );

            header('Location: login.php?mode=login&registered=1');
            exit;
        }
    }
}

top('Create Account', 'Register');
?>

<div class="panel" style="max-width:600px;margin:40px auto">

    <div class="eyebrow">Rescue with RePlate</div>

    <h1 class="page">Where To Rescue</h1>

    <p class="sub">
        Confirm your account and choose where you want to deliver food.
    </p>

    <?php if ($error): ?>
        <div class="err"><?=e($error)?></div>
    <?php endif; ?>

    <form method="post">

        <label>Select your role</label>

        <select name="role" required>
            <option value="">Choose your role</option>
            <option value="donor">Donor</option>
            <option value="receiver">Receiver / NGO</option>
            <option value="volunteer">Volunteer</option>
        </select>

        <br><br>

        <label>Name / Organization</label>

        <input
            type="text"
            name="name"
            placeholder="Enter your name"
            required
        >

        <br><br>

        <label>Email Address</label>

        <input
            type="email"
            name="email"
            placeholder="Enter your email"
            required
        >

        <br><br>

        <label>Password</label>

        <input
            type="password"
            name="password"
            placeholder="Minimum 8 characters"
            minlength="8"
            required
        >

        <br><br>

        <label>Delivery Location</label>

        <input
            type="text"
            name="location"
            id="location"
            placeholder="Enter your location"
        >

        <br><br>

        <button type="button" class="sm"
                onclick="getLocation()">
            📍 Get Current Location
        </button>

        <br><br>

        <button type="submit" class="btn">
            Create Account →
        </button>

    </form>

    <hr>

    <p class="sub">
        Already have an account?
        <a href="login.php?mode=login">LOGIN</a>
    </p>

</div>

<script>
function getLocation() {

    if (!navigator.geolocation) {
        alert('Location is not supported by this browser.');
        return;
    }

    navigator.geolocation.getCurrentPosition(
        function(position) {

            document.getElementById('location').value =
                position.coords.latitude + ', ' +
                position.coords.longitude;

        },
        function() {
            alert('Unable to get location. Please enter it manually.');
        }
    );
}
</script>

<?php bottom(); ?>
```
