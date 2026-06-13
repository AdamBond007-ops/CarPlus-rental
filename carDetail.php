<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

/* DATABASE CONNECTION */
$conn = new mysqli("sql103.infinityfree.com", "if0_42143898", "Umarjztr313", "if0_42143898_carplus");

/* CHECK CONNECTION */
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/* GET CAR ID */
if (!isset($_GET['id'])) {
    die("Car ID not found.");
}

$car_id = (int) $_GET['id'];

/* FETCH CAR */
$sql = "
SELECT cars.*, branches.branch_name
FROM cars
LEFT JOIN branches
ON cars.branch_id = branches.branch_id
WHERE cars.car_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $car_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Car not found.");
}

$car = $result->fetch_assoc();

$checkout_params = ["car_id" => $car['car_id']];

foreach (["pickup_date", "pickup_time", "dropoff_date", "dropoff_time"] as $booking_field) {
    if (isset($_GET[$booking_field]) && $_GET[$booking_field] !== '') {
        $checkout_params[$booking_field] = $_GET[$booking_field];
    }
}

$checkout_url = "checkout.php?" . http_build_query($checkout_params);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo $car['name']; ?> - Car Details</title>

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Oswald:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        display: ['Oswald', 'sans-serif'],
                    },
                    colors: {
                        dark: '#121212',
                        darker: '#0a0a0a',
                        accent: '#16a34a',
                        accentHover: '#15803d',
                    }
                }
            }
        }
    </script>

    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #0a0a0a;
            color: white;
            font-family: 'Inter', sans-serif;
        }

        .glass-panel {
            background: rgba(18, 18, 18, 0.6);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .text-glow {
            text-shadow: 0 0 20px rgba(22, 163, 74, 0.5);
        }

        .bg-glow {
            box-shadow: 0 0 40px rgba(22, 163, 74, 0.2);
        }

        .bg-glow-hover:hover {
            box-shadow: 0 0 40px rgba(22, 163, 74, 0.4);
        }

        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #0a0a0a;
        }

        ::-webkit-scrollbar-thumb {
            background: #333;
            border-radius: 4px;
        }
    </style>
</head>

<body class="min-h-screen">

    <!-- HEADER -->
    <?php
    include 'header.php';
    ?>

    <!-- MAIN -->
    <main class="relative z-20 flex-grow pt-28 pb-20">

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">

            <!-- IMAGE -->
            <div class="glass-panel rounded-[30px] overflow-hidden bg-glow">

                <img
                    src="<?php echo $car['image_url']; ?>"
                    alt="<?php echo $car['name']; ?>"
                    class="w-full h-full object-cover">

            </div>

            <!-- DETAILS -->
            <div class="glass-panel rounded-[30px] p-8 flex flex-col justify-between">

                <div>

                    <!-- TYPE -->
                    <div class="mb-4">
                        <span class="px-4 py-2 rounded-full bg-accent/20 border border-accent/40 text-sm text-white">
                            <?php echo $car['car_type']; ?>
                        </span>
                    </div>

                    <!-- NAME -->
                    <h1 class="text-5xl font-display font-bold mb-4">
                        <?php echo $car['name']; ?>
                    </h1>

                    <!-- BRANCH -->
                    <div class="flex items-center gap-2 text-gray-400 mb-6">
                        <i class="fa-solid fa-location-dot text-accent"></i>

                        <span>
                            <?php echo $car['branch_name']; ?>
                        </span>
                    </div>

                    <!-- PRICE -->
                    <div class="mb-8">
                        <span class="text-4xl font-bold text-accent">
                            RM<?php echo $car['price_per_day']; ?>
                        </span>

                        <span class="text-gray-400 text-lg">
                            /day
                        </span>
                    </div>

                    <!-- DESCRIPTION -->
                    <div class="mb-8">

                        <h2 class="text-xl font-semibold mb-3">
                            Description
                        </h2>

                        <p class="text-gray-300 leading-8">
                            <?php echo $car['description']; ?>
                        </p>

                    </div>

                    <!-- EXTRA INFO -->
                    <div class="grid grid-cols-2 gap-4 mb-10">

                        <div class="glass-panel rounded-2xl p-5">
                            <p class="text-gray-400 text-sm mb-2">
                                Car ID
                            </p>

                            <p class="text-xl font-bold">
                                #<?php echo $car['car_id']; ?>
                            </p>
                        </div>

                        <div class="glass-panel rounded-2xl p-5">
                            <p class="text-gray-400 text-sm mb-2">
                                Availability
                            </p>

                            <p class="text-xl font-bold text-accent">
                                <?php
                                echo ($car['availability'] == 1)
                                    ? "Available"
                                    : "Not Available";
                                ?>
                            </p>
                        </div>

                    </div>

                </div>

                <!-- BUTTONS -->
                <div class="flex gap-4">

            <a
                href="<?php echo $checkout_url; ?>"
                class="w-full py-4 bg-accent hover:bg-accentHover rounded-2xl text-white text-center font-bold text-lg transition-all">

                Rent Now

                </a>

                    <a href="homepage.php"
                        class="px-6 py-4 glass-panel rounded-2xl text-white hover:bg-white/10 transition flex items-center justify-center">

                        <i class="fa-solid fa-arrow-left"></i>

                    </a>

                </div>

            </div>

        </div>

    </main>

       <!-- Footer -->
    <footer id="footer" class="border-t border-white/5 bg-[#0a0a0a] pt-16 pb-8 relative z-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
                
                <!-- Brand Col -->
                <div class="col-span-1 md:col-span-1">
                    <a href="#" class="font-display text-2xl font-bold tracking-widest text-white inline-block mb-4">
                        CAR<span class="text-accent text-glow">PLUS</span>
                    </a>
                    <p class="text-gray-400 text-sm mb-6 max-w-xs font-light">
                        The world's premier platform for exotic and luxury vehicle rentals. Experience the drive of your dreams today.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="w-10 h-10 rounded-full glass-panel flex items-center justify-center text-gray-400 hover:text-accent hover:bg-white/5 transition-all"><i class="fa-brands fa-twitter"></i></a>
                        <a href="#" class="w-10 h-10 rounded-full glass-panel flex items-center justify-center text-gray-400 hover:text-accent hover:bg-white/5 transition-all"><i class="fa-brands fa-instagram"></i></a>
                        <a href="#" class="w-10 h-10 rounded-full glass-panel flex items-center justify-center text-gray-400 hover:text-accent hover:bg-white/5 transition-all"><i class="fa-brands fa-linkedin-in"></i></a>
                    </div>
                </div>

                <!-- Links Col 1 -->
                <div>
                    <h4 class="text-white font-display font-bold mb-6 tracking-wide">VEHICLES</h4>
                    <ul class="space-y-3">
                        <li><a href="#" class="text-gray-400 hover:text-accent text-sm transition-colors">Sports & Exotic</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-accent text-sm transition-colors">Luxury Sedans</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-accent text-sm transition-colors">Premium SUVs</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-accent text-sm transition-colors">Electric Fleet</a></li>
                    </ul>
                </div>

                <!-- Links Col 2 -->
                <div>
                    <h4 class="text-white font-display font-bold mb-6 tracking-wide">COMPANY</h4>
                    <ul class="space-y-3">
                        <li><a href="#" class="text-gray-400 hover:text-accent text-sm transition-colors">About Us</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-accent text-sm transition-colors">Careers</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-accent text-sm transition-colors">Elite Membership</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-accent text-sm transition-colors">Contact Support</a></li>
                    </ul>
                </div>

                <!-- Newsletter Col -->
                <div>
                    <h4 class="text-white font-display font-bold mb-6 tracking-wide">NEWSLETTER</h4>
                    <p class="text-gray-400 text-sm mb-4 font-light">Subscribe to get special offers, free giveaways, and once-in-a-lifetime deals.</p>
                    <form class="relative">
                        <input type="email" class="w-full input-glass rounded-xl pl-4 pr-12 py-3 text-white placeholder-gray-500 text-sm" placeholder="Enter your email">
                        <button type="submit" class="absolute right-2 top-1/2 transform -translate-y-1/2 w-8 h-8 rounded-lg bg-accent text-white flex items-center justify-center hover:bg-accentHover transition-colors">
                            <i class="fa-solid fa-paper-plane text-xs"></i>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Bottom Footer -->
            <div class="border-t border-white/5 pt-8 flex flex-col md:flex-row items-center justify-between gap-4">
                <p class="text-gray-500 text-xs font-light">&copy; 2026 CarPlus. All rights reserved.</p>
                <div class="flex space-x-6 text-xs text-gray-500">
                    <a href="#" class="hover:text-white transition-colors">Privacy Policy</a>
                    <a href="#" class="hover:text-white transition-colors">Terms of Service</a>
                    <a href="#" class="hover:text-white transition-colors">Cookie Policy</a>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>
