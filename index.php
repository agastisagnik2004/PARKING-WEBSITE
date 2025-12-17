<?php
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ParkingPro — Parking Subscription System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
</head>
<body>
    <header class="hero">
        <div class="hero-inner">
            <h1>ParkingPro</h1>
            <p>Smart parking subscriptions, QR payments, and SMS alerts.</p>
            <div class="hero-actions">
                <a class="btn" href="<?php echo BASE_URL; ?>user/login.php">User Login</a>
                <a class="btn ghost" href="<?php echo BASE_URL; ?>admin/login.php">Admin</a>
            </div>
        </div>
    </header>

    <main class="container">
        <section class="features">
            <article class="feature">
                <img src="https://images.unsplash.com/photo-1519741491669-6a1ef8f7f7c8?auto=format&fit=crop&w=800&q=60" alt="User dashboard">
                <h3>User Portal</h3>
                <p>Register, manage vehicles and purchase subscriptions using QR payments.</p>
                <a class="link" href="<?php echo BASE_URL; ?>user/login.php">Enter user panel →</a>
            </article>

            <article class="feature">
                <img src="https://images.unsplash.com/photo-1532298229144-0ec0c57515c7?auto=format&fit=crop&w=800&q=60" alt="Admin tools">
                <h3>Admin Tools</h3>
                <p>Manage plans, promotions, staff and view parking logs from one place.</p>
                <a class="link" href="<?php echo BASE_URL; ?>admin/login.php">Open admin →</a>
            </article>

            <article class="feature">
                <img src="https://images.unsplash.com/photo-1504215680853-026ed2a45def?auto=format&fit=crop&w=800&q=60" alt="Staff scanner">
                <h3>Staff Station</h3>
                <p>Validate subscriptions quickly at stations and send SMS alerts.</p>
                <a class="link" href="<?php echo BASE_URL; ?>staff/login.php">Staff login →</a>
            </article>
        </section>

        <section class="info">
            <h2>Getting started</h2>
            <p>Use the buttons above to log in. Replace sample images with your own by placing them under <strong>assets/images/</strong> and updating the <strong>src</strong> attributes.</p>
        </section>
    </main>

    <footer class="site-footer">
        <div>© <?php echo date('Y'); ?> ParkingPro.</div>
        <div class="credits">Sample photos from <a href="https://unsplash.com" target="_blank" rel="noopener">Unsplash</a>. Replace with licensed images for production.</div>
    </footer>
    <script src="<?php echo BASE_URL; ?>assets/js/app.js"></script>
</body>
</html>
