<?php 
// venue_display.php

$host = 'localhost';
$db   = 'ventech_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// Set up PDO connection
try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit;
}

// Fetch venue details
$venue_id = $_GET['id'] ?? null;
if (!$venue_id) {
    echo "No venue selected.";
    exit;
}

// Get venue info using prepared statement
$stmt = $pdo->prepare("SELECT * FROM venue WHERE id = :venue_id");
$stmt->bindParam(':venue_id', $venue_id, PDO::PARAM_INT);
$stmt->execute();
$venue = $stmt->fetch(PDO::FETCH_ASSOC);

// Get venue media (images/videos)
$media_stmt = $pdo->prepare("SELECT * FROM venue_media WHERE venue_id = :venue_id");
$media_stmt->bindParam(':venue_id', $venue_id, PDO::PARAM_INT);
$media_stmt->execute();
$media = $media_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unavailable dates
$unavailableDatesStmt = $pdo->prepare("SELECT unavailable_date FROM unavailable_dates WHERE venue_id = ?");
$unavailableDatesStmt->execute([$venue_id]);
$unavailableDates = $unavailableDatesStmt->fetchAll(PDO::FETCH_COLUMN);

// Convert unavailable dates into a JavaScript-friendly format
$unavailableDatesJson = json_encode($unavailableDates);

// Get client info
$client_stmt = $pdo->prepare("SELECT client_name, client_email, client_phone, client_address FROM users WHERE id = :user_id");
$client_stmt->bindParam(':user_id', $venue['user_id'], PDO::PARAM_INT); // venue.user_id must exist
$client_stmt->execute();
$client = $client_stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($venue['venue_name']) ?> - Venue Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.4/index.global.min.css" rel="stylesheet">
    <style>
        .fc-day.fc-disabled {
            background-color: #f87171 !important; /* Red highlight for disabled days */
            color: white !important;
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-800">

<!-- Main Container -->
<div class="max-w-6xl mx-auto p-6 bg-white shadow-lg rounded-lg mt-10">

    <!-- Venue Title -->
    <h1 class="text-3xl font-bold mb-4"><?= htmlspecialchars($venue['title']) ?></h1>

    <!-- Venue Media Slider -->
    <div class="relative w-full overflow-hidden rounded-lg mb-6">
        <div class="flex gap-4 overflow-x-auto scrollbar-hide">
            <?php foreach ($media as $item): ?>
                <div class="swiper-slide">
                    <?php if ($item['media_type'] === 'image' && file_exists($item['media_path'])): ?>
                        <img src="<?= htmlspecialchars($item['media_path']) ?>" alt="Venue image" class="h-60 object-cover rounded-lg" />
                    <?php elseif ($item['media_type'] === 'video' && file_exists($item['media_path'])): ?>
                        <video controls class="h-60 object-cover rounded-lg">
                            <source src="<?= htmlspecialchars($item['media_path']) ?>" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    <?php else: ?>
                        <p>Media not available.</p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Venue Description -->
    <div class="mb-6">
        <h2 class="text-xl font-semibold mb-2">About the Venue</h2>
        <p class="text-gray-700"><?= nl2br(htmlspecialchars($venue['description'])) ?></p>
    </div>

    <!-- Client Info -->
    <?php if ($client): ?>
        <div class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Client Info</h2>
            <div class="bg-gray-100 p-4 rounded-lg shadow-sm">
                <p><strong>Name:</strong> <?= htmlspecialchars($client['client_name']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($client['client_email']) ?></p>
                <p><strong>Phone:</strong> <?= htmlspecialchars($client['client_phone']) ?></p>
                <p><strong>Address:</strong> <?= htmlspecialchars($client['client_address']) ?></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Amenities -->
    <?php
    $amenities_stmt = $pdo->prepare("SELECT amenity_name FROM venue_amenities WHERE venue_id = :venue_id");
    $amenities_stmt->bindParam(':venue_id', $venue_id, PDO::PARAM_INT);
    $amenities_stmt->execute();
    $amenities = $amenities_stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <div class="mb-6">
        <h2 class="text-xl font-semibold mb-2">Amenities</h2>
        <ul class="list-disc list-inside text-gray-700">
            <?php foreach ($amenities as $amenity): ?>
                <li><?= htmlspecialchars($amenity['amenity_name']) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- Availability Calendar -->
    <div class="mb-6">
        <h2 class="text-xl font-semibold mb-4">Availability Calendar</h2>
        <div id="calendar"></div>
    </div>

    <!-- Location -->
    <div class="mb-6">
        <h2 class="text-xl font-semibold mb-2">Location</h2>
        <p class="text-gray-700"><?= htmlspecialchars($venue['location']) ?></p>
    </div>

    <!-- Google Map -->
    <div class="mb-6">
        <iframe
            src="https://maps.google.com/maps?q=<?= urlencode($venue['location']) ?>&output=embed"
            width="100%"
            height="300"
            class="rounded-lg border"
            allowfullscreen
            loading="lazy">
        </iframe>
    </div>

</div>

<!-- FullCalendar Script -->
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.4/main.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid@6.1.4/main.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/interaction@6.1.4/main.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const unavailableDates = <?php echo $unavailableDatesJson; ?>;
        
        const calendarEl = document.getElementById('calendar');
        
        const calendar = new FullCalendar.Calendar(calendarEl, {
            plugins: ['dayGrid', 'interaction'],
            initialView: 'dayGridMonth',
            events: unavailableDates.map(date => ({
                title: 'Unavailable',
                start: date,
                rendering: 'background',
                color: '#f87171', // Red background for unavailable dates
            }))
        });
        
        calendar.render();
    });
</script>

</body>
</html>
