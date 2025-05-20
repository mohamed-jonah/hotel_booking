<?php
include 'includes/config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$roomType = $_GET['room_type'] ?? '';
$checkInDate = $_GET['check_in_date'] ?? '';
$checkOutDate = $_GET['check_out_date'] ?? '';
$availabilityMessage = "";
$dateError = "";
$showCaptcha = false;

if (!$roomType) {
    echo "Room type not specified!";
    exit;
}

// Fetch room type details
$query = "SELECT * FROM room_types WHERE room_type = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $roomType);
$stmt->execute();
$roomTypeResult = $stmt->get_result();

if ($roomTypeResult->num_rows === 0) {
    echo "Invalid room type!";
    exit;
}

$roomTypeData = $roomTypeResult->fetch_assoc();

function validateDates($checkIn, $checkOut) {
    $today = new DateTime();
    $today->setTime(0, 0);
    $in = new DateTime($checkIn);
    $out = new DateTime($checkOut);

    if ($in < $today) return "Check-in date cannot be in the past.";
    if ($out <= $in) return "Check-out date must be after check-in date.";
    return "";
}

if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['check_availability'])) {
    $checkInDate = $_POST['check_in_date'];
    $checkOutDate = $_POST['check_out_date'];

    $dateError = validateDates($checkInDate, $checkOutDate);

    if (!$dateError) {
        $query = "SELECT COUNT(*) AS total_rooms FROM rooms WHERE room_type = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $roomType);
        $stmt->execute();
        $result = $stmt->get_result();
        $totalRooms = $result->fetch_assoc()['total_rooms'];

        $query = "SELECT COUNT(*) AS booked_rooms FROM bookings b 
                  JOIN rooms r ON b.room_id = r.room_id
                  WHERE r.room_type = ? AND (b.check_in_date < ? AND b.check_out_date > ?)
                  AND b.status = 'completed'";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sss", $roomType, $checkOutDate, $checkInDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $bookedRooms = $result->fetch_assoc()['booked_rooms'] ?? 0;

        $availableRooms = $totalRooms - $bookedRooms;

        if ($availableRooms > 0) {
            $availabilityMessage = "<p class='success-message'>$availableRooms rooms available. Please proceed to book.</p>";
            $showCaptcha = true;
            // Generate CAPTCHA
            $a = rand(1, 10);
            $b = rand(1, 10);
            $_SESSION['captcha_question'] = "What is $a + $b?";
            $_SESSION['captcha_answer'] = $a + $b;
        } else {
            $availabilityMessage = "<p class='error-message'>Sorry, no rooms available for those dates.</p>";
        }
    } else {
        $availabilityMessage = "<p class='error-message'>$dateError</p>";
    }
}

if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['confirm_booking'])) {
    $checkInDate = $_POST['check_in_date'];
    $checkOutDate = $_POST['check_out_date'];
    $user_id = $_SESSION['user_id'];

    if (!isset($_POST['captcha']) || (int)$_POST['captcha'] !== $_SESSION['captcha_answer']) {
        $availabilityMessage = "<p class='error-message'>Incorrect CAPTCHA. Please try again.</p>";
        $showCaptcha = true;
    } else {
        $dateError = validateDates($checkInDate, $checkOutDate);

        if ($dateError) {
            $availabilityMessage = "<p class='error-message'>$dateError</p>";
        } else {
            $breakfast = $_POST['breakfast'];
            $breakfast_time = $_POST['breakfast_time'] ?? null;
            $dinner = $_POST['dinner'];
            $dinner_time = $_POST['dinner_time'] ?? null;
            $additional_services = $_POST['additional_services'] ?? null;

            $query = "SELECT room_id FROM rooms r WHERE room_type = ? AND NOT EXISTS (
                          SELECT 1 FROM bookings b WHERE b.room_id = r.room_id
                          AND (b.check_in_date < ? AND b.check_out_date > ?)
                      ) LIMIT 1";

            $stmt = $conn->prepare($query);
            $stmt->bind_param("sss", $roomType, $checkOutDate, $checkInDate);
            $stmt->execute();
            $result = $stmt->get_result();
            $availableRoom = $result->fetch_assoc();

            if ($availableRoom) {
                $room_id = $availableRoom['room_id'];
                $nights = (new DateTime($checkInDate))->diff(new DateTime($checkOutDate))->days;
                $totalPrice = $nights * $roomTypeData['price_per_night'];

                $stmt = $conn->prepare("INSERT INTO bookings 
                    (user_id, room_id, check_in_date, check_out_date, total_price, breakfast, breakfast_time, dinner, dinner_time, additional_services, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
                $stmt->bind_param("iissdsssss", $user_id, $room_id, $checkInDate, $checkOutDate, $totalPrice, $breakfast, $breakfast_time, $dinner, $dinner_time, $additional_services);

                if ($stmt->execute()) {
                    header("Location: payment.php?booking_id=" . $conn->insert_id);
                    exit;
                } else {
                    $availabilityMessage = "<p class='error-message'>Booking failed: " . $stmt->error . "</p>";
                }
            } else {
                $availabilityMessage = "<p class='error-message'>No rooms available for those dates.</p>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="Cache-Control" content="no-store" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Book Room</title>
<link rel="icon" href="images/logo/hotellogo.png" type="image/x-icon">
<link rel="stylesheet" href="css/booking.css">
<link rel="stylesheet" href="css/nav.css">
<link rel="stylesheet" href="css/footer.css">
</head>
<body>

<?php include 'includes/header.php'; ?>

<main class="container">
    <h2>Book Your Room</h2>
    <div class="room-container">
        <div class="room-image">
            <img src="<?= $roomTypeData['image_url']; ?>" alt="Room Image">
        </div>
        <div class="room-info">
            <h3><?= htmlspecialchars($roomType); ?></h3>
            <p><?= htmlspecialchars($roomTypeData['description']); ?></p>
            <p>Price per night: €<?= $roomTypeData['price_per_night']; ?></p>

            <form method="POST">
                <label for="checkInDate">Check-in Date:</label>
                <input type="date" id="checkInDate" name="check_in_date" value="<?= $checkInDate ?>" min="<?= date('Y-m-d'); ?>" required>

                <label for="checkOutDate">Check-out Date:</label>
                <input type="date" id="checkOutDate" name="check_out_date" value="<?= $checkOutDate ?>" min="<?= date('Y-m-d', strtotime('+1 day')); ?>" required>

                <button type="submit" name="check_availability" class="button">Check Availability</button>

                <?= $availabilityMessage; ?>

                <?php if ($showCaptcha): ?>
                    <label>Breakfast:</label>
                    <select name="breakfast" id="breakfast">
                        <option value="No">No</option>
                        <option value="Yes">Yes</option>
                    </select>
                    <label>Time:</label>
                    <input type="time" name="breakfast_time" id="breakfast_time">

                    <label>Dinner:</label>
                    <select name="dinner" id="dinner">
                        <option value="No">No</option>
                        <option value="Yes">Yes</option>
                    </select>
                    <label>Time:</label>
                    <input type="time" name="dinner_time" id="dinner_time">

                    <label>Additional Services:</label>
                    <textarea name="additional_services" placeholder="Specify additional requests"></textarea>

                    <label for="captcha">CAPTCHA: <?= $_SESSION['captcha_question']; ?></label>
                    <input type="text" name="captcha" id="captcha" required>

                    <button type="submit" name="confirm_booking" class="button">Proceed to Payment</button>
                <?php endif; ?>
            </form>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

<script src="https://kit.fontawesome.com/2e5e758ab7.js" crossorigin="anonymous"></script>
<script src="js/navbar.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const breakfast = document.getElementById('breakfast');
    const breakfastTime = document.getElementById('breakfast_time');
    const dinner = document.getElementById('dinner');
    const dinnerTime = document.getElementById('dinner_time');

    breakfast?.addEventListener('change', function () {
        breakfastTime.required = (this.value === 'Yes');
    });

    dinner?.addEventListener('change', function () {
        dinnerTime.required = (this.value === 'Yes');
    });
});
</script>

</body>
</html>
