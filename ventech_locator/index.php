<?php
$host = 'localhost';
$db   = 'ventech_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Fetch venues with status 'open' or 'closed'
    $stmt = $pdo->prepare("SELECT * FROM venue WHERE status IN ('open', 'closed') ORDER BY created_at DESC");
    $stmt->execute();
    $venues = $stmt->fetchAll();

} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage();
    die();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Ventech Locator</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet"/>
</head>
<body class="font-roboto">
<header class="relative">
    <img src="https://storage.googleapis.com/a1aa/image/K_EDQPTQu2U4iz3tp8MzfHSgPnSlvbtIy4OxGn8rihQ.jpg" alt="Hotel" class="w-full h-96 object-cover" />
    <div class="absolute top-0 left-0 w-full h-full bg-black bg-opacity-50 flex flex-col items-center justify-center">
        <div class="flex justify-between items-center w-full px-8 py-4">
            <img src="https://storage.googleapis.com/a1aa/image/hnN7Al3bjnkfO3IsgYjIgHrULVNdWiF-JXUCBNDe-sw.jpg" class="h-12" alt="Planyo Logo" />
            <nav class="text-white space-x-8 flex items-center">
                <a class="hover:underline" href="#">HOME</a>
                <a class="hover:underline" href="user_venue_list.php">VENUE LIST</a>
                <div class="relative">
                    <button class="hover:underline focus:outline-none" id="signInButton">SIGN IN</button>
                    <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-2 hidden" id="dropdownMenu">
                        <a href="/ventech_locator/users/user_login.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-200">User</a>
                        <a href="/ventech_locator/client/client_login.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-200">Client</a>
                        <a href="#" class="block px-4 py-2 text-gray-800 hover:bg-gray-200">Admin</a>
                    </div>
                </div>
            </nav>
        </div>
        <h1 class="text-4xl text-white mt-8">welcome to</h1>
        <h2 class="text-6xl text-yellow-500 font-bold">Ventech Locator</h2>
        <button class="mt-4 px-6 py-2 bg-yellow-500 text-white font-bold rounded">READ MORE</button>
    </div>
</header>

<main class="py-16 px-8">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <?php foreach ($venues as $venue): 
            $img = !empty($venue['image_path']) && file_exists($venue['image_path']) 
                ? htmlspecialchars($venue['image_path']) 
                : 'https://via.placeholder.com/400x250?text=No+Image';
        ?>
        <div class="border rounded-lg shadow-lg overflow-hidden bg-white">
            <img src="<?= $img ?>" alt="<?= htmlspecialchars($venue['title']) ?>" class="w-full h-48 object-cover" />
            <div class="p-4">
            <p class="mt-2 text-sm text-gray-600">
            Status: <span class="<?= $venue['status'] === 'open' ? 'text-green-500' : 'text-red-500' ?>">
           <?= ucfirst($venue['status']) ?>
          </span></p>
                <h3 class="text-yellow-500 text-xl font-bold"><?= htmlspecialchars($venue['title']) ?></h3>
                <p class="mt-2 text-sm text-gray-600">Price from</p>
                <p class="text-lg font-bold text-gray-800">₱ <?= number_format($venue['price'], 2) ?>/Hour</p>
                <div class="flex items-center mt-2">
                    <div class="flex text-yellow-500">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                    </div>
                    <p class="ml-2 text-sm text-gray-600"><?= $venue['reviews'] ?> Reviews</p>
                </div>
                <div class="mt-4 flex space-x-4">
             <a href="venue_details.php?id=<?= $venue['id'] ?>" class="px-4 py-2 bg-yellow-500 text-white text-sm font-bold rounded hover:bg-yellow-600 transition">DETAILS</a>
             <a href="booking_form.php?venue_id=<?= $venue['id'] ?>" class="px-4 py-2 bg-yellow-500 text-white text-sm font-bold rounded hover:bg-yellow-600 transition">MAKE RESERVATION</a>
            </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</main>

<script>
    document.getElementById('signInButton').addEventListener('click', function () {
        document.getElementById('dropdownMenu').classList.toggle('hidden');
    });
</script>
</body>
</html>
