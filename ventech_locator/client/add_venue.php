<?php 
include('../includes/db_connection.php'); // Ensure this path is correct
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: client_login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Initialize error and success messages
$errors = [];
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input data
    $title = trim($_POST['title']);
    $price = trim($_POST['price']);
    $description = trim($_POST['description']);
    $status = $_POST['status']; // Capture the status

    if (empty($title) || empty($price) || empty($description) || empty($status)) {
        $errors[] = "All fields are required.";
    }

    // Validate price format
    if (!is_numeric($price) || $price <= 0) {
        $errors[] = "Invalid price.";
    }

    // Additional price validation
    if (!preg_match("/^\d+(\.\d{2})?$/", $price)) {
        $errors[] = "Price must be a valid number with up to two decimal places.";
    }

    // Upload image with better validation and handling
    $upload_dir = "../uploads/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $image_path = "";
    if (isset($_FILES["image"]) && $_FILES["image"]["error"] == 0) {
        $image_name = time() . "_" . preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', basename($_FILES["image"]["name"]));
        $full_path = $upload_dir . $image_name;
        $relative_path = "uploads/" . $image_name;

        // Validate file type
        $file_ext = strtolower(pathinfo($full_path, PATHINFO_EXTENSION));
        $allowed = ["jpg", "jpeg", "png", "gif"];
        
        // Check MIME type as well
        $mime_type = mime_content_type($_FILES['image']['tmp_name']);
        $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif'];

        if (!in_array($file_ext, $allowed) || !in_array($mime_type, $allowed_mimes)) {
            $errors[] = "Invalid file type. Only JPG, JPEG, PNG, GIF allowed.";
        }

        if (in_array($file_ext, $allowed) && !file_exists($full_path)) {
            // Move the uploaded file
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $full_path)) {
                $image_path = $relative_path;
            } else {
                $errors[] = "Sorry, there was an error uploading your image.";
            }
        } else {
            $errors[] = "File already exists. Please upload a different image.";
        }
    } else {
        $errors[] = "Please select an image to upload.";
    }

    // If no errors, insert the venue into the database
    if (empty($errors)) {
        try {
            // Prepare the SQL query to insert the venue into the database
            $stmt = $pdo->prepare("INSERT INTO venue (user_id, title, price, image_path, description, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $title, $price, $image_path, $description, $status]);

            // Get the last inserted venue ID
            $venue_id = $pdo->lastInsertId(); 

            // Redirect to the user_venue_list.php page with success message
            $success = "Venue added successfully!";
            header("Location: /ventech_locator/client_dashboard.php?new_venue=true&id=$venue_id");
            exit();
        } catch (PDOException $e) {
            $errors[] = "Error: " . $e->getMessage();
        }
    }
}
?>

<!-- HTML remains the same -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Venue</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto py-8">
        <h2 class="text-2xl font-semibold text-center mb-4">Add a New Venue</h2>
        
        <!-- Display success or error messages -->
        <?php if ($success): ?>
            <div class="bg-green-500 text-white p-4 rounded mb-4">
                <?= htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="bg-red-500 text-white p-4 rounded mb-4">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="max-w-lg mx-auto bg-white p-6 rounded shadow">
            <div class="mb-4">
                <label for="title" class="block text-gray-700">Title</label>
                <input type="text" id="title" name="title" class="w-full border-gray-300 rounded-md p-2" value="<?= isset($title) ? htmlspecialchars($title) : ''; ?>" required>
            </div>
            <div class="mb-4">
                <label for="description" class="block text-gray-700">Description</label>
                <textarea id="description" name="description" class="w-full border-gray-300 rounded-md p-2" required><?= isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
            </div>
            <div class="mb-4">
                <label for="price" class="block text-gray-700">Price per Hour</label>
                <input type="number" id="price" name="price" class="w-full border-gray-300 rounded-md p-2" value="<?= isset($price) ? htmlspecialchars($price) : ''; ?>" required>
            </div>

            <!-- Venue Status Selection -->
            <div class="mb-4">
                <label for="status" class="block text-gray-700">Status</label>
                <select id="status" name="status" class="w-full border-gray-300 rounded-md p-2" required>
                    <option value="open" <?= isset($status) && $status == 'open' ? 'selected' : ''; ?>>Open</option>
                    <option value="closed" <?= isset($status) && $status == 'closed' ? 'selected' : ''; ?>>Closed</option>
                </select>
            </div>

            <!-- Display status highlight -->
            <?php if (isset($status) && $status == 'open'): ?>
                <div class="bg-green-500 text-white text-center py-2 mb-4">This venue is OPEN!</div>
            <?php endif; ?>

            <div class="mb-4">
                <label for="image" class="block text-gray-700">Venue Image</label>
                <input type="file" id="image" name="image" class="w-full border-gray-300 rounded-md p-2" required>
            </div>
            <button type="submit" class="w-full bg-blue-500 text-white py-2 rounded-md">Add Venue</button>
        </form>
    </div>
</body>
</html>



