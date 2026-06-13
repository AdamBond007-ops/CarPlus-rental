<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

include 'dbConnect.php';

$user_id = $_SESSION['user_id'];

/* GET LATEST RENTAL */

$sql = "

SELECT

rentals.*,

cars.*,

users.name,
users.email,
users.profile_image,

branches.branch_name

FROM rentals

JOIN cars
ON rentals.car_id = cars.car_id

JOIN users
ON rentals.user_id = users.user_id

LEFT JOIN branches
ON rentals.branch_id = branches.branch_id

WHERE rentals.user_id = ?

ORDER BY rentals.created_at DESC

LIMIT 1

";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
"i",
$user_id
);

$stmt->execute();

$data =
$stmt
->get_result()
->fetch_assoc();

function e($x){
return htmlspecialchars($x);
}

function money($x){
return "RM".number_format($x,2);
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Successful - CarPlus</title>
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
    </style>
</head>
<body class="min-h-screen text-gray-100 font-sans selection:bg-neon-green selection:text-black flex m-0 p-0">

    <!-- Sidebar Navigation -->
    

    <!-- Main Content Wrapper -->
    <main id="main-content" class="w-full md:ml-[88px] md:w-[calc(100%-88px)] min-h-screen bg-dark-900 flex flex-col relative">
        
        <!-- Top Header -->
        <header id="header" class="h-24 w-full flex items-center justify-between px-6 md:px-10 border-b border-dark-800 bg-dark-900/80 backdrop-blur-md sticky top-0 z-40">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-white flex items-center gap-3 hidden md:flex">
                    Booking Complete
                </h1>
                <!-- Mobile Logo -->
                <div class="w-10 h-10 rounded-xl bg-dark-700 flex items-center justify-center md:hidden shadow-card-depth border border-dark-600">
                    <i class="fa-solid fa-car text-neon-green text-lg"></i>
                </div>
            </div>
            
            
            </div>
        </header>

        <!-- Success Content -->
        <div class="flex-1 p-6 md:p-10 max-w-[1440px] mx-auto w-full flex flex-col items-center justify-center py-12">
            
            <!-- Success Header Section -->
            <section id="success-header" class="text-center mb-12 max-w-2xl mx-auto flex flex-col items-center">
                <div class="w-24 h-24 rounded-full bg-neon-greenMuted border-2 border-neon-green flex items-center justify-center mb-6 shadow-neon-glow relative">
                    <i class="fa-solid fa-check text-4xl text-neon-green"></i>
                    <!-- Decorative particles -->
                    <div class="absolute -top-2 -right-2 w-3 h-3 rounded-full bg-neon-green shadow-neon-glow animate-pulse"></div>
                    <div class="absolute bottom-2 -left-3 w-2 h-2 rounded-full bg-neon-green shadow-neon-glow animate-pulse delay-100"></div>
                </div>
                
                <h2 class="text-4xl md:text-5xl font-bold text-white mb-4 tracking-tight">Thank You!</h2>
                <p class="text-gray-400 text-lg mb-6">Your booking has been successfully confirmed. A confirmation email has been sent to your registered email address.</p>
                
                <div class="inline-flex items-center gap-3 bg-dark-800 border border-dark-700 px-6 py-3 rounded-xl shadow-card-depth">
                    <span class="text-sm text-gray-400">Confirmation Number:</span>
                    <span class="text-lg font-mono font-bold text-neon-green tracking-wider">#CP-<?php echo e($data['rental_id']); ?></span>
                    <button class="ml-2 text-gray-500 hover:text-white transition-colors" title="Copy">
                        <i class="fa-regular fa-copy"></i>
                    </button>
                </div>
            </section>

            <!-- Booking Summary & Logistics Layout -->
            <div class="w-full max-w-5xl grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
                
               <section id="vehicle-details"
class="bg-dark-800 rounded-[20px] p-6 md:p-8 shadow-card-depth border border-dark-700">

<h3 class="text-xl font-semibold text-white mb-6 pb-4 border-b border-dark-700 flex items-center gap-3">

<i class="fa-solid fa-car-side text-neon-green"></i>

Vehicle Details

</h3>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">

    <!-- IMAGE -->

    <div class="bg-dark-900 rounded-xl overflow-hidden border border-dark-600 h-[300px]">

        <img

        class="w-full h-full object-cover"

        src="<?php echo e($data['image_url']); ?>"

        alt="<?php echo e($data['name']); ?>">

    </div>


    <!-- DETAILS -->

    <div class="flex flex-col justify-center">

        <div class="mb-6">

            <h4 class="text-3xl font-bold text-white mb-3">

                <?php echo e($data['name']); ?>

            </h4>

            <p class="text-gray-400 text-lg">

                <?php echo e($data['car_type']); ?>

            </p>

        </div>

        <div class="bg-dark-900 rounded-xl p-5 border border-dark-700 mb-6">

            <p class="text-sm text-gray-500 mb-2">

                Total Paid

            </p>

            <h3 class="text-4xl font-bold text-neon-green">

                <?php echo money($data['total_price']); ?>

            </h3>

        </div>

        <div class="grid grid-cols-2 gap-4">

            <div class="bg-dark-900 p-4 rounded-xl border border-dark-700">

                <p class="text-xs text-gray-500">

                    Rental Days

                </p>

                <p class="text-white font-semibold">

                    <?php echo e($data['rental_days']); ?>

                    Day(s)

                </p>

            </div>

            <div class="bg-dark-900 p-4 rounded-xl border border-dark-700">

                <p class="text-xs text-gray-500">

                    Branch

                </p>

                <p class="text-white font-semibold">

                    <?php echo e($data['branch_name']); ?>

                </p>

            </div>

        </div>

    </div>

</div>

</section>
                <!-- Rental Logistics Card -->
                <section id="rental-logistics" class="bg-dark-800 rounded-[20px] p-6 md:p-8 shadow-card-depth border border-dark-700 h-full flex flex-col">
                    <h3 class="text-xl font-semibold text-white mb-6 pb-4 border-b border-dark-700 flex items-center gap-3">
                        <i class="fa-regular fa-clock text-neon-green"></i>
                        Rental Timeline
                    </h3>
                    
                    <div class="flex-1 flex flex-col gap-8 relative">
                        <!-- Connecting Line -->
                        <div class="absolute left-[23px] top-10 bottom-10 w-[2px] bg-dark-600 z-0"></div>
                        
                        <!-- Pick-up -->
                        <div class="flex gap-6 relative z-10">
                            <div class="w-12 h-12 rounded-full bg-dark-900 border-2 border-neon-green flex items-center justify-center shrink-0 shadow-neon-glow">
                                <i class="fa-solid fa-location-dot text-neon-green"></i>
                            </div>
                            <div class="flex-1 bg-dark-900 rounded-xl p-5 border border-dark-700">
                                <span class="inline-block px-2 py-1 bg-dark-800 text-gray-400 text-xs rounded mb-2 border border-dark-600">Pick-Up</span>
                                <h4 class="text-lg font-medium text-white mb-1"><?php echo e($data['branch_name']); ?></h4>
                                <p class="text-sm text-gray-400 mb-3"></p>
                                <div class="flex items-center gap-4 text-sm">
                                    <div class="flex items-center gap-2 text-white">
                                        <i class="fa-regular fa-calendar text-neon-green"></i> <?php
                                                                echo date(
                                                                "d M Y",
                                                                strtotime(
                                                                $data['pickup_date']
                                                                )
                                                                );
                                                                ?>
                                    </div>
                                    <div class="flex items-center gap-2 text-white">
                                        <i class="fa-regular fa-clock text-neon-green"></i> <?php
                                                echo date(
                                                "g:i A",
                                                strtotime(
                                                $data['pickup_time']
                                                )
                                                );
                                                ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Drop-off -->
                        <div class="flex gap-6 relative z-10">
                            <div class="w-12 h-12 rounded-full bg-dark-900 border-2 border-dark-600 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-flag-checkered text-gray-400"></i>
                            </div>
                            <div class="flex-1 bg-dark-900 rounded-xl p-5 border border-dark-700">
                                <span class="inline-block px-2 py-1 bg-dark-800 text-gray-400 text-xs rounded mb-2 border border-dark-600">Drop-Off</span>
                                <h4 class="text-lg font-medium text-white mb-1"><?php echo e($data['branch_name']); ?></h4>
                                <p class="text-sm text-gray-400 mb-3"></p>
                                <div class="flex items-center gap-4 text-sm">
                                    <div class="flex items-center gap-2 text-white">
                                        <i class="fa-regular fa-calendar text-gray-400"></i> <?php
                                                    echo date(
                                                    "d M Y",
                                                    strtotime(
                                                    $data['dropoff_date']
                                                    )
                                                    );
                                                    ?>
                                    </div>
                                    <div class="flex items-center gap-2 text-white">
                                        <i class="fa-regular fa-clock text-gray-400"></i> <?php
                                                        echo date(
                                                        "g:i A",
                                                        strtotime(
                                                        $data['dropoff_time']
                                                        )
                                                        );
                                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

            </div>

            <!-- Action Footer -->
            <section id="action-footer" class="w-full max-w-2xl mx-auto flex flex-col sm:flex-row gap-4 justify-center">

                <a href="rentalDetails.php"

                        class="flex-1 h-14 bg-neon-green hover:bg-neon-greenHover text-dark-900 font-bold text-lg rounded-xl transition-all shadow-neon-glow flex items-center justify-center gap-2 hover:scale-[1.02] active:scale-[0.98]">

                        <i class="fa-solid fa-list-ul"></i>

                        View My Bookings

                        </a>


                    <a href="homepage.php"

                    class="flex-1 h-14 bg-transparent border-2 border-neon-green text-neon-green hover:bg-neon-greenMuted font-bold text-lg rounded-xl transition-all flex items-center justify-center gap-2 hover:scale-[1.02] active:scale-[0.98]">

                    <i class="fa-solid fa-house"></i>

                    Return to Home

                    </a>
            </section>

        </div>
    </main>

</body>
</html>