<?php
// Include your database connection
include_once('includes/db_connection.php');
include_once('includes/config.php');

// Start session for user login
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php"); // Redirect to login page if not logged in
    exit;
}

// Fetch user details for dashboard
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>

<body class="bg-gray-100">

    <div class="flex">
        <!-- Sidebar -->
        <div class="w-64 bg-gray-800 text-white h-screen">
            <div class="flex justify-center items-center h-16 bg-gray-900">
                <h1 class="text-xl font-semibold">COTW Dashboard</h1>
            </div>
            <nav class="mt-6">
                <ul>
                    <li>
                        <a href="user_dashboard.php" class="block py-2 px-4 text-gray-300 hover:bg-gray-700 hover:text-white">
                            <i class="fas fa-tachometer-alt mr-3"></i> Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="user_profile.php" class="block py-2 px-4 text-gray-300 hover:bg-gray-700 hover:text-white">
                            <i class="fas fa-user mr-3"></i> Profile
                        </a>
                    </li>
                    <li>
                        <a href="user_settings.php" class="block py-2 px-4 text-gray-300 hover:bg-gray-700 hover:text-white">
                            <i class="fas fa-cogs mr-3"></i> Settings
                        </a>
                    </li>
                    <li>
                        <a href="logout.php" class="block py-2 px-4 text-gray-300 hover:bg-gray-700 hover:text-white">
                            <i class="fas fa-sign-out-alt mr-3"></i> Logout
                        </a>
                    </li>
                </ul>
            </nav>
        </div>

        <!-- Main Content Area -->
        <div class="flex-1 p-6">
            <h2 class="text-3xl font-semibold text-gray-800">Welcome, <?php echo htmlspecialchars($user['username']); ?>!</h2>
            <p class="text-gray-600 mt-2">Here’s an overview of your account and activities.</p>

            <!-- Overview Section -->
            <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Profile Overview Card -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h3 class="text-lg font-semibold text-gray-800">Your Profile</h3>
                    <p class="text-gray-600 mt-2">Manage your personal details, change password, and more.</p>
                    <a href="user_profile.php" class="text-blue-500 mt-4 block">Go to Profile</a>
                </div>

                <!-- Activity Overview Card -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h3 class="text-lg font-semibold text-gray-800">Recent Activity</h3>
                    <p class="text-gray-600 mt-2">View your recent activities and updates related to your profile.</p>
                    <a href="user_activity.php" class="text-blue-500 mt-4 block">View Activity</a>
                </div>

                <!-- Settings Overview Card -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h3 class="text-lg font-semibold text-gray-800">Settings</h3>
                    <p class="text-gray-600 mt-2">Update your account settings, including email and preferences.</p>
                    <a href="user_settings.php" class="text-blue-500 mt-4 block">Go to Settings</a>
                </div>
            </div>

            <!-- Stats Section -->
            <div class="mt-8 bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-lg font-semibold text-gray-800">Dashboard Stats</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-4">
                    <!-- Stats Card 1 -->
                    <div class="bg-blue-100 p-4 rounded-lg shadow-md">
                        <p class="text-gray-700">Total Bookings</p>
                        <h4 class="text-2xl font-bold text-blue-600">5</h4>
                    </div>
                    <!-- Stats Card 2 -->
                    <div class="bg-green-100 p-4 rounded-lg shadow-md">
                        <p class="text-gray-700">Upcoming Events</p>
                        <h4 class="text-2xl font-bold text-green-600">2</h4>
                    </div>
                    <!-- Stats Card 3 -->
                    <div class="bg-yellow-100 p-4 rounded-lg shadow-md">
                        <p class="text-gray-700">Recent Reviews</p>
                        <h4 class="text-2xl font-bold text-yellow-600">8</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>

</html>
