<?php
// Database connection parameters
$host = 'localhost';
$db   = 'ventech_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// Include the PDO database connection
try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to the database: " . $e->getMessage());
}

// Get the venue ID from the URL
$venue_id = $_GET['id'] ?? null;

if (!$venue_id) {
    die("No venue ID provided.");
}

// Fetch venue data
$stmt = $pdo->prepare("SELECT * FROM venue WHERE id = ?");
$stmt->execute([$venue_id]);
$venue = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch media (images/videos)
$mediaStmt = $pdo->prepare("SELECT * FROM venue_media WHERE venue_id = ?");
$mediaStmt->execute([$venue_id]);
$media = $mediaStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch unavailable dates
$unavailableDatesStmt = $pdo->prepare("SELECT unavailable_date FROM unavailable_dates WHERE venue_id = ?");
$unavailableDatesStmt->execute([$venue_id]);
$unavailableDates = $unavailableDatesStmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch client information
$user_id = $venue['user_id']; // Assuming 'user_id' links to the users table

// Fetch user information from the 'users' table where the role is 'client'
$userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'client'");
$userStmt->execute([$user_id]);
$client_info = $userStmt->fetch(PDO::FETCH_ASSOC);

// Handle the case if no client data is found
if (!$client_info) {
    echo "Client information not available.";
}

// Check if venue was found
if (!$venue) {
    die("Venue not found.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate form inputs
    $amenities = trim($_POST['amenities'] ?? '');
    $reviews = filter_var($_POST['reviews'] ?? 0, FILTER_VALIDATE_INT);
    $additional_info = trim($_POST['additional_info'] ?? '');
    $unavailable_dates = $_POST['unavailable_dates'] ?? [];
    $num_persons = filter_var($_POST['num-persons'] ?? 1, FILTER_VALIDATE_INT);
    $wifi = $_POST['wifi'] ?? 'no';
    $parking = $_POST['parking'] ?? 'no';
    $event_date = $_POST['event_date'] ?? null;
    $client_name = trim($_POST['client-name'] ?? '');
    $client_email = trim($_POST['client-email'] ?? '');
    $client_phone = trim($_POST['client-phone'] ?? '');
    $client_address = trim($_POST['client-address'] ?? '');

    // Check for valid reviews
    if ($reviews === false || $reviews < 0) {
        die("Invalid reviews count.");
    }

    // Handle image upload
    if (isset($_FILES['venue_image']) && $_FILES['venue_image']['error'] === 0) {
        $image_tmp = $_FILES['venue_image']['tmp_name'];
        $image_name = time() . '_' . basename($_FILES['venue_image']['name']);
        $upload_dir = __DIR__ . '/../uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $image_path = 'uploads/' . $image_name;
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $image_extension = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
        if (!in_array($image_extension, $allowed_extensions)) {
            die("Invalid image format. Only JPG, JPEG, PNG, and GIF are allowed.");
        }
        if ($_FILES['venue_image']['size'] > 5 * 1024 * 1024) {
            die("File size exceeds the 5MB limit.");
        }
        if (!move_uploaded_file($image_tmp, $upload_dir . $image_name)) {
            die("Failed to upload image.");
        }
        $insert_media = $pdo->prepare("INSERT INTO venue_media (venue_id, media_type, media_url) VALUES (?, ?, ?)");
        $insert_media->execute([$venue_id, 'image', $image_path]);

    }
    // Handle video upload
    if (isset($_FILES['venue_video']) && $_FILES['venue_video']['error'] === 0) {
        $video_tmp = $_FILES['venue_video']['tmp_name'];
        $video_name = time() . '_' . basename($_FILES['venue_video']['name']);
        $upload_dir = __DIR__ . '/../uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $video_path = 'uploads/' . $video_name;
        $allowed_extensions = ['mp4'];
        $video_extension = strtolower(pathinfo($video_name, PATHINFO_EXTENSION));
        if (!in_array($video_extension, $allowed_extensions)) {
            die("Invalid video format. Only MP4 videos are allowed.");
        }
        if ($_FILES['venue_video']['size'] > 20 * 1024 * 1024) {
            die("File size exceeds the 20MB limit.");
        }
        if (!move_uploaded_file($video_tmp, $upload_dir . $video_name)) {
            die("Failed to upload video.");
        }
        $insert_media = $pdo->prepare("INSERT INTO venue_media (venue_id, media_type, media_url) VALUES (?, ?, ?)");
        $insert_media->execute([$venue_id, 'image', $video_path]);
    }

    // Update venue details
    if (isset($_POST['venue_id']) && $_POST['venue_id'] == $venue_id) { 
        $update = $pdo->prepare("UPDATE venue SET amenities = ?, reviews = ?, additional_info = ?, wifi = ?, parking = ? WHERE id = ?");
        $update_success = $update->execute([$amenities, $reviews, $additional_info, $wifi, $parking, $venue_id]);
        if ($update_success) {
            echo "Venue details updated successfully.";
        } else {
            echo "Failed to update venue details.";
        }
    }
    // Insert unavailable dates
    if (!empty($unavailable_dates)) {
        $insert = $pdo->prepare("INSERT IGNORE INTO unavailable_dates (venue_id, unavailable_date) VALUES (?, ?)");
        foreach ($unavailable_dates as $date) {
            $insert->execute([$venue_id, $date]);
        }
    }

    // Update client information
    if ($client_info) {
        $update_client = $pdo->prepare("UPDATE users SET client_name = ?, client_email = ?, client_phone = ?, client_address = ? WHERE id = ? AND role = 'client'");
        $update_client_success = $update_client->execute([$client_name, $client_email, $client_phone, $client_address, $user_id]);
        if ($update_client_success) {
            echo "Client information updated successfully.";
        } else {
            echo "Failed to update client information.";
        }
    }

    // Redirect after successful update
    header("Location: venue_display.php?id=$venue_id");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($venue['title']); ?> - Venue Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;700&display=swap" rel="stylesheet"/>
    <link href="https://unpkg.com/leaflet/dist/leaflet.css" rel="stylesheet"/>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
        }
        .hidden {
            display: none;
        }
    
        #map {
            height: 450px;
            width: 100%;
        }
        .flatpickr-calendar {
            font-family: 'Montserrat', sans-serif;
        }
        .unavailable {
    background-color: #f87171 !important; /* red-400 */
    color: white !important;
    border-radius: 4px;
}
    </style>
</head>
<body class="bg-white text-gray-700">
    <form action="venue_details.php?id=<?php echo $venue_id; ?>" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="venue_id" value="<?php echo $venue_id; ?>">
        <div class="max-w-5xl mx-auto p-4">

            <div class="text-center text-gray-600 mb-4">
                <span>Status:</span>
                <span id="status"><?php echo $venue['status'] == 'open' ? 'Open' : 'Closed'; ?></span>
            </div>

            <h1 class="text-center text-4xl font-light text-yellow-600 mb-8">
                <?php echo htmlspecialchars($venue['title']); ?>
            </h1>

            <div id="preview-section" class="mt-6">
                <h2 class="text-xl font-semibold text-center mb-4 text-gray-700">Preview Upload</h2>
                <div class="flex flex-col md:flex-row justify-center gap-6">
                    <div class="flex flex-col items-center">
                        <p class="mb-2 text-sm text-gray-600">Image Preview:</p>
                        <img id="imagePreview" src="#" alt="Image Preview" class="w-64 h-40 object-cover border border-gray-300 rounded hidden" />
                    </div>

                    <div class="flex flex-col items-center">
                        <p class="mb-2 text-sm text-gray-600">Video Preview:</p>
                        <video id="videoPreview" class="w-64 h-40 border border-gray-300 rounded hidden" controls>
                            <source src="#" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    </div>
                </div>
            </div>

            <div class="flex flex-col md:flex-row items-center justify-between">
                <div class="w-full md:w-1/2 mb-4 md:mb-0 relative">
                    <div class="swiper" id="media-slider">
                        <div class="swiper-wrapper">
                            <?php foreach ($media as $item): ?>
                                <div class="swiper-slide">
                                    <?php if ($item['media_type'] === 'image'): ?>
                                        <img src="<?php echo htmlspecialchars($item['media_path']); ?>" alt="Venue Image" class="w-full" />
                                    <?php elseif ($item['media_type'] === 'video'): ?>
                                        <video controls class="w-full">
                                            <source src="<?php echo htmlspecialchars($item['media_path']); ?>" type="video/mp4">
                                            Your browser does not support the video tag.
                                        </video>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="swiper-button-next"></div>
                        <div class="swiper-button-prev"></div>
                        <div class="swiper-pagination"></div>
                    </div>
                </div>

                <div class="w-full md:w-1/2 md:pl-8">
    <div class="text-yellow-600 text-right mb-2">April 2025</div>
    <div class="mb-4 text-center">
        <label for="availability-calendar" class="block mb-2">Select Event Date:</label>

        <!-- This is the calendar input -->
        <input 
            type="text" 
            id="availability-calendar" 
            class="border rounded px-4 py-2 w-full text-center shadow-sm focus:ring focus:ring-yellow-300 focus:outline-none"
            placeholder="Click to select a date"
            readonly
        >

        <!-- This hidden input stores the selected unavailable dates -->
        <input type="hidden" name="unavailable_dates" id="unavailable-dates" value="<?php echo implode(',', $unavailableDates); ?>">

    </div>
</div>
</div>

            <p class="mt-8 text-center text-gray-600">
                <?php echo !empty($venue['description']) ? htmlspecialchars($venue['description']) : "No description available."; ?>
            </p>

            <div class="flex flex-col md:flex-row justify-center space-y-4 md:space-y-0 md:space-x-4">
                <div class="mb-4">
                    <label for="venue_image" class="block text-gray-700">Upload Image (JPG, PNG, GIF):</label>
                    <input type="file" id="venue_image" name="venue_image" class="border p-2 w-full" />
                </div>
                <div class="mb-4">
                    <label for="venue_video" class="block text-gray-700">Upload Video (MP4):</label>
                    <input type="file" id="venue_video" name="venue_video" class="border p-2 w-full" />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="border p-4 text-center">
                    <label for="num-persons">Number of persons:</label>
                    <input class="border p-2 w-full" id="num-persons" name="num-persons" type="number" value="<?php echo htmlspecialchars($venue['num_persons'] ?? 1); ?>" />
                </div>
                <div class="border p-4 text-center">
                    <label for="amenities">Amenities:</label>
                    <input class="border p-2 w-full" id="amenities" name="amenities" type="text" value="<?php echo htmlspecialchars($venue['amenities'] ?? ''); ?>" />
                </div>
                <div class="border p-4 text-center">
                    <label for="reviews">Reviews:</label>
                    <input class="border p-2 w-full" id="reviews" name="reviews" type="number" value="<?php echo htmlspecialchars($venue['reviews'] ?? 0); ?>" />
                </div>
                <div class="border p-4 text-center">
                    <label for="additional_info">Additional Info:</label>
                    <textarea class="border p-2 w-full" id="additional_info" name="additional_info"><?php echo htmlspecialchars($venue['additional_info'] ?? ''); ?></textarea>
                </div>
                <div class="border p-4 text-center">
                    <label for="wifi">Free wifi:</label>
                    <select class="border p-2 w-full" id="wifi" name="wifi">
                        <option value="yes" <?php echo (isset($venue['wifi']) && $venue['wifi'] == 'yes') ? 'selected' : ''; ?>>yes</option>
                        <option value="no" <?php echo (isset($venue['wifi']) && $venue['wifi'] == 'no') ? 'selected' : ''; ?>>no</option>
                    </select>
                </div>
                <div class="border p-4 text-center">
                    <label for="parking">Covered parking garage:</label>
                    <select class="border p-2 w-full" id="parking" name="parking">
                        <option value="yes" <?php echo (isset($venue['parking']) && $venue['parking'] == 'yes') ? 'selected' : ''; ?>>yes</option>
                        <option value="no" <?php echo (isset($venue['parking']) && $venue['parking'] == 'no') ? 'selected' : ''; ?>>no</option>
                    </select>
                </div>
            </div>

            <input type="hidden" id="unavailable-dates" name="unavailable_dates" value=""/>

            <div class="flex flex-col md:flex-row justify-between mt-8">
                <div class="text-center md:w-1/2">
                    <h2 class="text-2xl font-light text-yellow-600 mb-4">Client Information</h2>
                    <div class="mb-4">
                        <label class="block text-gray-700" for="client-name">Full Name:</label>
                        <input class="border p-2 w-64" id="client-name" name="client-name" type="text"
                               value="<?php echo isset($client_info['client_name']) ? htmlspecialchars($client_info['client_name']) : ''; ?>" />
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700" for="client-email">Email:</label>
                        <input class="border p-2 w-64" id="client-email" name="client-email" type="email"
                               value="<?php echo isset($client_info['client_email']) ? htmlspecialchars($client_info['client_email']) : ''; ?>" />
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700" for="client-phone">Phone:</label>
                        <input class="border p-2 w-64" id="client-phone" name="client-phone" type="tel"
                               value="<?php echo isset($client_info['client_phone']) ? htmlspecialchars($client_info['client_phone']) : ''; ?>" />
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700" for="client-address">Address:</label>
                        <input class="border p-2 w-64" id="client-address" name="client-address" type="text"
                               value="<?php echo isset($client_info['client_address']) ? htmlspecialchars($client_info['client_address']) : ''; ?>" />
                    </div>
                </div>

                <div class="md:w-1/2 mt-8 md:mt-0">
                    <h2 class="text-2xl font-light text-yellow-600 mb-4">Location</h2>
                    <div class="relative">
                        <div id="map" class="h-64 rounded shadow"></div>
                    </div>
                    <div class="mt-4 text-center">
                        <button type="button" class="bg-yellow-600 text-white py-2 px-4 rounded" onclick="pinLocation()">Pin Your Location</button>
                    </div>
                </div>
            </div>

            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded mt-8 w-full">Submit</button>
        </div>
    </form>

    <script>
    const venueLat = <?php echo json_encode($venue['latitude']); ?>;
    const venueLon = <?php echo json_encode($venue['longitude']); ?>;

    // Initialize Leaflet map
    const map = L.map('map').setView([venueLat, venueLon], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

    let venueMarker = L.marker([venueLat, venueLon]).addTo(map)
        .bindPopup('This is your venue location').openPopup();

    // Setup unavailable dates on calendar
    const unavailableDates = <?php echo json_encode($unavailableDates); ?>;

    flatpickr("#availability-calendar", {
        minDate: "today",
        mode: "multiple", // enable multi-date selection
        dateFormat: "Y-m-d",
        disable: [], // allow all dates
        defaultDate: unavailableDates, // show existing unavailable dates as selected
        onDayCreate: function(dObj, dStr, fp, dayElem) {
            const dateStr = dayElem.dateObj.toISOString().split('T')[0];
            if (unavailableDates.includes(dateStr)) {
                dayElem.classList.add("unavailable");
                dayElem.style.backgroundColor = "#f87171"; // Tailwind red-400
                dayElem.style.color = "#fff";
            }
        },
        onChange: function(selectedDates, dateStr, instance) {
            const formattedDates = selectedDates.map(date =>
                date.toISOString().split('T')[0]
            );

            // Update hidden input with selected dates
            const unavailableDatesInput = document.getElementById("unavailable-dates");
            unavailableDatesInput.value = formattedDates.join(',');

            // Highlight selected dates in red
            setTimeout(() => {
                const allDays = instance.calendarContainer.querySelectorAll('.flatpickr-day');
                allDays.forEach(dayElem => {
                    const dateStr = dayElem.dateObj.toISOString().split('T')[0];
                    if (formattedDates.includes(dateStr)) {
                        dayElem.style.backgroundColor = "#f87171";
                        dayElem.style.color = "#fff";
                    } else {
                        dayElem.style.backgroundColor = "";
                        dayElem.style.color = "";
                    }
                });
            }, 1);
        }
    });

    // Swiper media slider
    const swiper = new Swiper('#media-slider', {
        slidesPerView: 1,
        spaceBetween: 10,
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev'
        },
        pagination: {
            el: '.swiper-pagination',
            clickable: true
        }
    });

    // Pin venue location on map
    function pinLocation() {
        map.once('click', function(e) {
            const { lat, lng } = e.latlng;

            // Update marker
            if (venueMarker) {
                map.removeLayer(venueMarker);
            }

            venueMarker = L.marker([lat, lng]).addTo(map)
                .bindPopup('New pinned venue location').openPopup();

            // Update form inputs (if present)
            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lng;
        });

        alert('Click on the map to pin your venue location.');
    }

    // Preview image before upload
    document.getElementById('venue_image').addEventListener('change', function (event) {
        const image = event.target.files[0];
        const preview = document.getElementById('imagePreview');
        if (image && image.type.match('image.*')) {
            const reader = new FileReader();
            reader.onload = function (e) {
                preview.src = e.target.result;
                preview.classList.remove('hidden');
            };
            reader.readAsDataURL(image);
        } else {
            preview.src = "#";
            preview.classList.add('hidden');
        }
    });

    // Preview video before upload
    document.getElementById('venue_video').addEventListener('change', function (event) {
        const video = event.target.files[0];
        const preview = document.getElementById('videoPreview');
        const source = preview.querySelector('source');
        if (video && video.type === 'video/mp4') {
            const videoURL = URL.createObjectURL(video);
            source.src = videoURL;
            preview.load();
            preview.classList.remove('hidden');
        } else {
            source.src = "#";
            preview.classList.add('hidden');
        }
    });
</script>

</body>
</html>
