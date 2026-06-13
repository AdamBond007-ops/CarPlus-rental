<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$conn = new mysqli("sql103.infinityfree.com", "if0_42143898", "Umarjztr313", "if0_42143898_carplus");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_GET['car_id']) || $_GET['car_id'] === '') {
    die("Car ID not found.");
}

$car_id = (int) $_GET['car_id'];
$user_id = (int) $_SESSION['user_id'];

$car_sql = "
    SELECT cars.*, branches.branch_name
    FROM cars
    LEFT JOIN branches ON cars.branch_id = branches.branch_id
    WHERE cars.car_id = ?
";
$car_stmt = $conn->prepare($car_sql);
$car_stmt->bind_param("i", $car_id);
$car_stmt->execute();
$car_result = $car_stmt->get_result();

if ($car_result->num_rows === 0) {
    die("Car not found.");
}

$car = $car_result->fetch_assoc();

$user_stmt = $conn->prepare("SELECT name, email, phone, profile_image FROM users WHERE user_id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();
$full_name = $user['name'] ?? '';
$name_parts = preg_split('/\s+/', trim($full_name), 2);
$first_name = $name_parts[0] ?? '';
$last_name = $name_parts[1] ?? '';
$profile_image = !empty($user['profile_image']) ? $user['profile_image'] : 'default-avatar.png';

$pickup_date = $_POST['pickup_date'] ?? '';
$pickup_time = $_POST['pickup_time'] ?? '';

$dropoff_date = $_POST['dropoff_date'] ?? '';
$dropoff_time = $_POST['dropoff_time'] ?? '';

$rental_days = 1;
if ($pickup_date !== '' && $dropoff_date !== '') {
    $pickup = new DateTime($pickup_date);
    $dropoff = new DateTime($dropoff_date);
    $days = (int) $pickup->diff($dropoff)->format('%r%a');
    $rental_days = max(1, $days);
}

$price_per_day = (float) $car['price_per_day'];
$rental_fee = $rental_days * $price_per_day;
$insurance_fee = $rental_fee * 0.10;
$tax_fee = ($rental_fee + $insurance_fee) * 0.10;
$total_price = $rental_fee + $insurance_fee + $tax_fee;

            /* SAVE RENTAL */
            $rental_method = $_POST['rental_method'] ?? 'pickup';
            $delivery_address = trim($_POST['delivery_address'] ?? '');
            if (isset($_POST['confirm_payment'])) {

                $branch_id = $car['branch_id'];
                

                $status = "Pending";

                $pickup_date = $_POST['pickup_date'];
$pickup_time = $_POST['pickup_time'];

$dropoff_date = $_POST['dropoff_date'];
$dropoff_time = $_POST['dropoff_time'];

                $insert_sql = "

                INSERT INTO rentals (
                user_id,
                car_id,
                branch_id,
                pickup_date,
                pickup_time,
                dropoff_date,
                dropoff_time,
                rental_days,
                total_price,
                status,
                rental_method,
                delivery_address
                )

                VALUES (
                ?, ?, ?,
                ?, ?,
                ?, ?,
                ?,
                ?,
                ?,
                ?,
                ?
                )

                ";

                $stmt =
                $conn->prepare(
                    $insert_sql
                );

                $stmt->bind_param(

                    "iiissssidsss",

                    $user_id,
                    $car_id,
                    $branch_id,

                    $pickup_date,
                    $pickup_time,

                    $dropoff_date,
                    $dropoff_time,

                    $rental_days,

                    $total_price,

                    $status,

                    $rental_method,
                    $delivery_address

                );

                $stmt->execute();

                /* OPTIONAL */
                $conn->query(
                "
                UPDATE cars
                SET availability = 0
                WHERE car_id = $car_id
                "
                );

                header(
                "Location: successfulpage.php"
                );

                exit();

            }

            function e($value) {
                return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
            }

            function money($value) {
                return "RM" . number_format((float) $value, 2);
            }

            function bookingDateText($date, $time) {
                if ($date === '') {
                    return "Choose date at counter";
                }

                $text = date("M d, Y", strtotime($date));

                if ($time !== '') {
                    $text .= " • " . date("g:i A", strtotime($time));
                }

                return $text;
            }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - <?php echo e($car['name']); ?> | CarPlus</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script> window.FontAwesomeConfig = { autoReplaceSvg: 'nest'};</script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <script>

         tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        accent: '#16a34a',
    accentHover: '#15803d',
                        dark: {
                            900: '#0a0a0a',
                            800: '#121212',
                            700: '#1a1a1a',
                            600: '#262626',
                        },
                        neon: {
                            green: '#00FF66',
                            greenHover: '#00e65c',
                            greenMuted: 'rgba(0, 255, 102, 0.1)',
                        }
                    },
                    boxShadow: {
                        'neon-glow': '0 0 15px rgba(0, 255, 102, 0.15)',
                        'card-depth': '0 8px 30px rgba(0,0,0,0.4)',
                    }
                }
            }
        }

function toggleDelivery() {

    const selected =
        document.querySelector(
            'input[name="rental_method"]:checked'
        ).value;

    const deliverySection =
        document.getElementById("deliverySection");

    const pickupCard =
        document.getElementById("pickupCard");

    const deliveryCard =
        document.getElementById("deliveryCard");

    if(selected === "delivery") {

        deliverySection.classList.remove("hidden");

        pickupCard.classList.remove(
            "border-neon-green",
            "bg-neon-greenMuted"
        );

        pickupCard.classList.add(
            "border-dark-600",
            "bg-dark-700"
        );

        deliveryCard.classList.remove(
            "border-dark-600",
            "bg-dark-700"
        );

        deliveryCard.classList.add(
            "border-neon-green",
            "bg-neon-greenMuted"
        );

    } else {

        deliverySection.classList.add("hidden");

        deliveryCard.classList.remove(
            "border-neon-green",
            "bg-neon-greenMuted"
        );

        deliveryCard.classList.add(
            "border-dark-600",
            "bg-dark-700"
        );

        pickupCard.classList.remove(
            "border-dark-600",
            "bg-dark-700"
        );

        pickupCard.classList.add(
            "border-neon-green",
            "bg-neon-greenMuted"
        );
    }
}

document.addEventListener(
    "DOMContentLoaded",
    toggleDelivery
);

</script>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #0a0a0a;
            color: #ffffff;
            -webkit-font-smoothing: antialiased;
        }
        ::-webkit-scrollbar { display: none; }
        
        /* Custom Input styling for dark theme with green focus */
        .input-dark {
            background-color: #1a1a1a;
            border: 1px solid #262626;
            color: #ffffff;
            transition: all 0.3s ease;
        }
        .input-dark:focus {
            outline: none;
            border-color: #00FF66;
            box-shadow: 0 0 0 2px rgba(0, 255, 102, 0.2);
        }
        .input-dark::placeholder {
            color: #6b7280;
        }
        
        /* Custom Radio/Checkbox styling */
        .radio-custom:checked {
            background-color: #00FF66;
            border-color: #00FF66;
        }

        .header-scrolled {
    background: rgba(18, 18, 18, 0.8);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);

    border-bottom: 1px solid rgba(255,255,255,0.08);

    box-shadow:
        0 8px 32px rgba(0,0,0,0.25);
}
    </style>
</head>
<body class="min-h-screen text-gray-100 font-sans selection:bg-neon-green selection:text-black flex">

    
   

    <!-- Main Content Wrapper -->
    <main id="main-content" class="ml-[88px] w-[calc(100%-88px)] min-h-screen bg-dark-900 flex flex-col relative">
        
        <!-- Top Header -->
        <?php
        include 'header.php';
        ?>

        <!-- Checkout Content Grid -->
        <div class="flex-1 pt-28 p-10 max-w-[1600px] mx-auto w-full grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <!-- Left Column: Forms (Booking Details & Payment) -->
            <div class="lg:col-span-8 flex flex-col gap-8">
                
                <!-- Driver Details Section -->
                <section id="driver-details" class="bg-dark-800 rounded-[20px] p-8 shadow-card-depth border border-dark-700">
                    <div class="flex items-center justify-between mb-8 pb-6 border-b border-dark-700">
                        <h2 class="text-xl font-semibold text-white flex items-center gap-3">
                            <i class="fa-regular fa-user text-neon-green"></i>
                            Driver Details
                        </h2>
                        <button class="text-sm text-neon-green hover:underline">Auto-fill from profile</button>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-6">
                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-medium text-gray-400">First Name</label>
                            <input type="text" name="first_name" value="<?php echo e($first_name); ?>" class="input-dark h-12 rounded-xl px-4 w-full text-sm" placeholder="Enter first name">
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-medium text-gray-400">Last Name</label>
                            <input type="text" name="last_name" value="<?php echo e($last_name); ?>" class="input-dark h-12 rounded-xl px-4 w-full text-sm" placeholder="Enter last name">
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-medium text-gray-400">Email Address</label>
                            <input type="email" name="email" value="<?php echo e($user['email'] ?? ''); ?>" class="input-dark h-12 rounded-xl px-4 w-full text-sm" placeholder="Enter email">
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-medium text-gray-400">Phone Number</label>
                            <input type="tel" name="phone" value="<?php echo e($user['phone'] ?? ''); ?>" class="input-dark h-12 rounded-xl px-4 w-full text-sm" placeholder="Enter phone number">
                        </div>
                    </div>
                </section>
                <form method="POST" action="checkout.php?car_id=<?php echo $car_id; ?>">
                <!-- Rental Method Section -->
<section class="bg-dark-800 rounded-[20px] p-8 shadow-card-depth border border-dark-700 mb-8">

    <div class="flex items-center justify-between mb-8 pb-6 border-b border-dark-700">
        <h2 class="text-xl font-semibold text-white flex items-center gap-3">
            <i class="fa-solid fa-location-dot text-neon-green"></i>
            Rental Method
        </h2>
    </div>

    <div class="space-y-4">

        <!-- Pickup -->
        <label id="pickupCard" class="flex items-center justify-between p-5 rounded-xl border border-neon-green bg-neon-greenMuted cursor-pointer">
            <div class="flex items-center gap-4">
                <input
                    type="radio"
                    name="rental_method"
                    value="pickup"
                    checked
                    onchange="toggleDelivery()"
                    class="radio-custom">

                <div>
                    <p class="font-medium text-white">
                        Pickup at Branch
                    </p>
                    <p class="text-sm text-gray-400">
                        Collect vehicle from selected branch
                    </p>
                </div>
            </div>

            <i class="fa-solid fa-building text-neon-green"></i>
        </label>

        <!-- Delivery -->
        <label id="deliveryCard" class="flex items-center justify-between p-5 rounded-xl border border-dark-600 bg-dark-700 cursor-pointer">
            <div class="flex items-center gap-4">
                <input
                    type="radio"
                    name="rental_method"
                    value="delivery"
                    onchange="toggleDelivery()"
                    class="radio-custom">

                <div>
                    <p class="font-medium text-white">
                        Vehicle Delivery
                    </p>
                    <p class="text-sm text-gray-400">
                        We deliver the car to your location
                    </p>
                </div>
            </div>

            <i class="fa-solid fa-truck text-neon-green"></i>
        </label>

    </div>

    <!-- Delivery Address -->
    <div id="deliverySection" class="hidden mt-6">
        <label class="block text-sm text-gray-300 mb-2">
            Delivery Address
        </label>

        <textarea
            name="delivery_address"
            rows="4"
            class="w-full input-dark rounded-xl px-4 py-3"
            placeholder="Enter your full delivery address"></textarea>
    </div>

</section>


            <section class="bg-dark-800 rounded-[20px] p-8 shadow-card-depth border border-dark-700">

    <div class="flex items-center justify-between mb-8 pb-6 border-b border-dark-700">
        <h2 class="text-xl font-semibold text-white flex items-center gap-3">
            <i class="fa-solid fa-calendar-days text-neon-green"></i>
            Rental Schedule
        </h2>
    </div>

    <div class="grid grid-cols-2 gap-6">

        <div>
            <label class="text-sm text-gray-400 block mb-2">
                Pickup Date
            </label>

            <input
                type="date"
                name="pickup_date"
                required
                class="input-dark h-12 rounded-xl px-4 w-full">
        </div>

        <div>
            <label class="text-sm text-gray-400 block mb-2">
                Pickup Time
            </label>

            <input
                type="time"
                name="pickup_time"
                required
                class="input-dark h-12 rounded-xl px-4 w-full">
        </div>

        <div>
            <label class="text-sm text-gray-400 block mb-2">
                Dropoff Date
            </label>

            <input
                type="date"
                name="dropoff_date"
                required
                class="input-dark h-12 rounded-xl px-4 w-full">
        </div>

        <div>
            <label class="text-sm text-gray-400 block mb-2">
                Dropoff Time
            </label>

            <input
                type="time"
                name="dropoff_time"
                required
                class="input-dark h-12 rounded-xl px-4 w-full">
        </div>

    </div>

</section>
                <!-- Payment Method Section -->
                <section id="payment-method" class="bg-dark-800 rounded-[20px] p-8 shadow-card-depth border border-dark-700">
                    <div class="flex items-center justify-between mb-8 pb-6 border-b border-dark-700">
                        <h2 class="text-xl font-semibold text-white flex items-center gap-3">
                            <i class="fa-regular fa-credit-card text-neon-green"></i>
                            Payment Method
                        </h2>
                        <div class="flex gap-2">
                            <i class="fa-brands fa-cc-visa text-2xl text-gray-400"></i>
                            <i class="fa-brands fa-cc-mastercard text-2xl text-gray-400"></i>
                            <i class="fa-brands fa-cc-amex text-2xl text-gray-400"></i>
                        </div>
                    </div>

                    <!-- Payment Options -->
                    <div class="flex flex-col gap-4 mb-8">
                        <label class="flex items-center justify-between p-4 rounded-xl border border-neon-green bg-neon-greenMuted cursor-pointer transition-all">
                            <div class="flex items-center gap-4">
                                <input type="radio" name="payment" checked class="w-5 h-5 accent-neon-green radio-custom">
                                <span class="font-medium text-white">Credit / Debit Card</span>
                            </div>
                            <i class="fa-regular fa-credit-card text-neon-green"></i>
                        </label>
                        
                        
                    </div>

                    <!-- Card Details Form -->
                    <div class="bg-dark-900 rounded-xl p-6 border border-dark-700">
                        <div class="flex flex-col gap-6">
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-gray-400">Card Number</label>
                                <div class="relative">
                                    <input type="text" class="input-dark h-12 rounded-xl px-4 pl-12 w-full text-sm font-mono" placeholder="0000 0000 0000 0000">
                                    <i class="fa-regular fa-credit-card absolute left-4 top-1/2 -translate-y-1/2 text-gray-500"></i>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-6">
                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-medium text-gray-400">Expiry Date</label>
                                    <input type="text" class="input-dark h-12 rounded-xl px-4 w-full text-sm font-mono" placeholder="MM/YY">
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-medium text-gray-400">CVC</label>
                                    <div class="relative">
                                        <input type="text" class="input-dark h-12 rounded-xl px-4 w-full text-sm font-mono" placeholder="123">
                                        <i class="fa-regular fa-circle-question absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 cursor-pointer hover:text-white"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-gray-400">Cardholder Name</label>
                                <input type="text" class="input-dark h-12 rounded-xl px-4 w-full text-sm" placeholder="Name on card">
                            </div>
                        </div>
                    </div>
                </section>

            </div>

            <!-- Right Column: Sticky Order Summary -->
            <div class="lg:col-span-4 relative">
                <div class="sticky top-[120px] flex flex-col gap-6">
                    
                    <!-- Car Summary Card -->
                    <div class="bg-dark-800 rounded-[20px] p-6 shadow-card-depth border border-dark-700">
                        <h3 class="text-lg font-semibold text-white mb-6 pb-4 border-b border-dark-700">Rental Summary</h3>
                        
                        <div class="flex gap-4 mb-6">
                            <div class="w-24 h-16 bg-dark-700 rounded-lg overflow-hidden flex items-center justify-center p-2 border border-dark-600">
                                <img class="w-full h-full object-cover" src="<?php echo e($car['image_url']); ?>" alt="<?php echo e($car['name']); ?>" />
                            </div>
                            <div class="flex flex-col justify-center">
                                <h4 class="font-semibold text-white text-lg"><?php echo e($car['name']); ?></h4>
                                <p class="text-sm text-gray-400"><?php echo e($car['car_type']); ?> • Car #<?php echo e($car['car_id']); ?></p>
                            </div>
                        </div>

                        <div class="space-y-4 mb-6 bg-dark-900 rounded-xl p-4 border border-dark-700">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-full bg-dark-800 flex items-center justify-center border border-dark-600 mt-1">
                                    <i class="fa-solid fa-location-dot text-neon-green text-xs"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400 mb-1">Pick-up</p>
                                    <p class="text-sm text-white font-medium"><?php echo e($car['branch_name'] ?? 'Selected branch'); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo e(bookingDateText($pickup_date, $pickup_time)); ?></p>
                                </div>
                            </div>
                            
                            <div class="w-px h-6 bg-dark-600 ml-4"></div>
                            
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-full bg-dark-800 flex items-center justify-center border border-dark-600 mt-1">
                                    <i class="fa-solid fa-flag-checkered text-gray-400 text-xs"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400 mb-1">Drop-off</p>
                                    <p class="text-sm text-white font-medium"><?php echo e($car['branch_name'] ?? 'Selected branch'); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo e(bookingDateText($dropoff_date, $dropoff_time)); ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Pricing Breakdown -->
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between text-gray-400">
                                <span>Rental fee (<?php echo e($rental_days); ?> day<?php echo $rental_days > 1 ? 's' : ''; ?> x <?php echo e(money($price_per_day)); ?>)</span>
                                <span class="text-white"><?php echo e(money($rental_fee)); ?></span>
                            </div>
                            <div class="flex justify-between text-gray-400">
                                <span>Premium Insurance</span>
                                <span class="text-white"><?php echo e(money($insurance_fee)); ?></span>
                            </div>
                            <div class="flex justify-between text-gray-400">
                                <span>Taxes & Fees (10%)</span>
                                <span class="text-white"><?php echo e(money($tax_fee)); ?></span>
                            </div>
                        </div>

                        <div class="mt-6 pt-6 border-t border-dark-700">
                            <div class="flex justify-between items-end mb-8">
                                <span class="text-base text-gray-400 font-medium">Total Price</span>
                                <div class="text-right">
                                    <span class="text-3xl font-bold text-white tracking-tight"><?php echo e(money($total_price)); ?></span>
                                </div>
                            </div>

                           <form method="POST">

                                <button 

                                type="submit"

                                name="confirm_payment"

                                class="
                                w-full
                                py-4

                                bg-accent
                                hover:bg-accentHover

                                text-white

                                rounded-2xl

                                font-bold
                                text-lg

                                transition-all
                                duration-300

                                shadow-[0_0_20px_rgba(22,163,74,0.4)]

                                hover:shadow-[0_0_30px_rgba(22,163,74,0.6)]

                                "

                                >

                                Confirm & Pay

                                </button>

                            </form>
                            <p class="text-center text-xs text-gray-500 mt-4 flex items-center justify-center gap-1">
                                <i class="fa-solid fa-lock"></i> Secure 256-bit SSL encryption
                            </p>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </main>

       <script>
window.addEventListener('scroll', function() {

    const header =
        document.getElementById('header');

    if (window.scrollY > 50) {

        header.classList.add(
            'header-scrolled'
        );

    } else {

        header.classList.remove(
            'header-scrolled'
        );

    }

});
</script>

</body>
</html>
