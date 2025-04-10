<?php  
include('../includes/db_connection.php'); // Ensure this path is correct
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: client_login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch the venues added by the logged-in user
try {
    // Ensure $pdo is used for the database connection (assuming db_connection.php uses $pdo)
    $stmt = $pdo->prepare("SELECT * FROM venue WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $venues = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch all venues as an associative array
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Venues</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet"/>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto py-8">
        <h2 class="text-2xl font-semibold text-center mb-4">Your Venues</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <?php foreach ($venues as $venue): 
            // Ensure the correct image path
            $img = !empty($venue['image_path']) && file_exists(__DIR__ . '/uploads/' . $venue['image_path']) 
                ? '/uploads/' . htmlspecialchars($venue['image_path']) 
                : 'https://via.placeholder.com/400x250?text=No+Image';
        ?>
        <div class="border rounded-lg shadow-lg overflow-hidden bg-white">
            <img src="<?= $img ?>" alt="<?= htmlspecialchars($venue['title']) ?>" class="w-full h-48 object-cover" />
            <div class="p-4">
                <h3 class="text-yellow-500 text-xl font-bold"><?= htmlspecialchars($venue['title']) ?></h3>
                <p class="mt-2 text-sm text-gray-600">Price from</p>
                <p class="text-lg font-bold text-gray-800">₱ <?= number_format($venue['price'], 2) ?>/Hour</p>
                <div class="flex items-center mt-2">
                    <div class="flex text-yellow-500">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                    </div>
                    <p class="ml-2 text-sm text-gray-600"><?= $venue['reviews'] ?> Reviews</p>
                </div>
                <div class="mt-4 flex justify-between">
                    <a href="venue_details.php?id=<?= $venue['id'] ?>" class="px-4 py-2 bg-yellow-500 text-white text-sm font-bold rounded hover:bg-yellow-600 transition">DETAILS</a>
                    <a href="booking_form.php?venue_id=<?= $venue['id'] ?>" class="px-4 py-2 bg-yellow-500 text-white text-sm font-bold rounded hover:bg-yellow-600 transition">MAKE RESERVATION</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
</body>
</html>


