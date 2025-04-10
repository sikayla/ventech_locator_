<?php 
// Database connection parameters
$host = 'localhost';
$db   = 'ventech_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// Create a PDO instance to handle the database connection
try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    header("Location: /ventech_locator/client_dashboard.php?error=db_connection");
    exit;
}

// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: client_login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user details from the database
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if user exists
    if (!$user) {
        throw new Exception("User not found.");
    }
} catch (PDOException $e) {
    error_log("Error fetching user: " . $e->getMessage());
    header("Location: /ventech_locator/client_dashboard.php?error=db_fetch_user");
    exit;
} catch (Exception $e) {
    error_log($e->getMessage());
    header("Location: /ventech_locator/client_dashboard.php?error=user_not_found");
    exit;
}

// Fetch venues posted by the client with optional status filtering
try {
    $status = $_GET['status'] ?? '';
    $allowed_status = ['open', 'closed'];

    if (in_array($status, $allowed_status)) {
        $stmt = $pdo->prepare("SELECT * FROM venue WHERE user_id = ? AND status = ? ORDER BY created_at DESC");
        $stmt->execute([$user_id, $status]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM venue WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
    }

    $venues = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching venues: " . $e->getMessage());
    $venues = [];
}

// Fetch reservations made by the client
try {
    $stmt = $pdo->prepare("SELECT * FROM reservations WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching reservations: " . $e->getMessage());
    $reservations = [];
}

// Show message after adding a new venue
$new_venue_message = "";
if (isset($_GET['new_venue']) && $_GET['new_venue'] == 'true') {
    $new_venue_message = "You’ve just added a venue! Please add the details for your venue.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Roboto', sans-serif; } </style>
</head>
<body class="bg-gray-100">

    <!-- Navbar (Optional) -->
    <div class="bg-orange-500 p-4 text-white text-center">
        <h1 class="text-xl font-bold">Client Dashboard</h1>
    </div>

    <!-- Sidebar Navigation -->
    <div class="flex min-h-screen">
        <div class="w-1/4 bg-white p-6 shadow-md">
            <h2 class="text-xl font-semibold mb-6">Welcome, <?= htmlspecialchars($user['username']) ?></h2>
            <ul class="space-y-4">
                <li><a href="client_dashboard.php" class="text-gray-700 hover:text-orange-500">Dashboard</a></li>
                <li><a href="add_venue.php" class="text-gray-700 hover:text-orange-500">Add New Venue</a></li>
                <li><a href="client_profile.php" class="text-gray-700 hover:text-orange-500">Profile</a></li>
                <li><a href="client_logout.php" class="text-gray-700 hover:text-orange-500">Logout</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-6">
            <!-- Display message for newly added venue -->
            <?php if (!empty($new_venue_message)): ?>
                <div class="bg-yellow-100 p-4 rounded-md mb-8 text-center text-yellow-700">
                    <p><?= $new_venue_message ?> <a href="/ventech_locator/venue_details.php?id=<?= isset($venues[0]['id']) ? $venues[0]['id'] : '' ?>" class="text-blue-500">Click here to add details</a>.</p>
                </div>
            <?php endif; ?>

            <!-- Venue Management Section -->
            <div class="mb-8">
                <h2 class="text-2xl font-bold mb-4">Your Venues</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php if (count($venues) > 0): ?>
                        <?php foreach ($venues as $venue): 
                            $img = !empty($venue['image_path']) && file_exists($venue['image_path']) 
                                ? htmlspecialchars($venue['image_path']) 
                                : 'https://via.placeholder.com/400x250?text=No+Image';
                        ?>
                        <div class="border rounded-lg shadow-lg overflow-hidden bg-white">
                        <img src="<?= $img ?>" alt="<?= htmlspecialchars($venue['title']) ?>" class="w-full h-48 object-cover" />
                        <div class="p-4">
                        <span class="inline-block px-2 py-1 text-sm rounded-full mt-2 
                            <?= $venue['status'] == 'open' ? 'bg-green-200 text-green-800' : 'bg-red-200 text-red-800'; ?>">
                            <?= ucfirst($venue['status']); ?>
                            </span>
                            <h3 class="text-yellow-500 text-xl font-bold"><?= htmlspecialchars($venue['title']) ?></h3>
                            <p class="mt-2 text-sm text-gray-600">Price from</p>
                            <p class="text-lg font-bold text-gray-800">₱ <?= number_format($venue['price'], 2) ?>/Hour</p>
                            <div class="flex items-center mt-2">
                                <div class="flex text-yellow-500">
                                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                                </div>
                                <p class="ml-2 text-sm text-gray-600"><?= $venue['reviews'] ?> Reviews</p>
                            </div>
                            <!-- Edit Venue Button -->
                            <div class="mt-4">
                                <a href="/ventech_venue.php/client/edit_venue.php?id=<?= $venue['id'] ?>" class="text-blue-500 hover:text-blue-700">Edit Venue</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No venues added yet. <a href="/ventech_locator/client/add_venue.php" class="text-blue-500">Add a new venue</a>.</p>
                <?php endif; ?>
            </div>

            <!-- Reservation Management Section -->
            <div class="mb-8">
                <h2 class="text-2xl font-bold mb-4">Your Reservations</h2>
                <div class="bg-white shadow-md rounded-lg p-4">
                    <?php if (count($reservations) > 0): ?>
                        <table class="w-full table-auto">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 border-b">Venue</th>
                                    <th class="px-4 py-2 border-b">Event Date</th>
                                    <th class="px-4 py-2 border-b">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reservations as $reservation): ?>
                                    <tr>
                                        <td class="px-4 py-2"><?= htmlspecialchars($reservation['venue_name']) ?></td>
                                        <td class="px-4 py-2"><?= htmlspecialchars($reservation['event_date']) ?></td>
                                        <td class="px-4 py-2"><?= htmlspecialchars($reservation['status']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No reservations yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</body>
</html>



