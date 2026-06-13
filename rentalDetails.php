<?php

session_start();

include 'dbConnect.php';

if(isset($_POST['cancel_rental_id'])){

    $rental_id =
        (int)$_POST['cancel_rental_id'];

    $user_id =
        (int)$_SESSION['user_id'];

    /* Cancel rental */
    $stmt =
        $conn->prepare(
        "UPDATE rentals
         SET status='Cancelled'
         WHERE rental_id=?
         AND user_id=?"
        );

    $stmt->bind_param(
        "ii",
        $rental_id,
        $user_id
    );

    $stmt->execute();

    /* Make car available again */
    $car_stmt =
        $conn->prepare(
        "UPDATE cars
         SET availability = 1
         WHERE car_id =
         (
            SELECT car_id
            FROM rentals
            WHERE rental_id = ?
         )"
        );

    $car_stmt->bind_param(
        "i",
        $rental_id
    );

    $car_stmt->execute();

    header("Location: rentalDetails.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$sql = "

SELECT

rentals.*,

cars.name AS car_name,
cars.car_type,
cars.image_url,

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

";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i",$user_id);

$stmt->execute();

$result = $stmt->get_result();

$upcoming = [];
$previous = [];

while($row = $result->fetch_assoc()){

$status = strtolower(trim($row['status']));

if(
in_array(
$status,
[
'completed',
'complete',
'returned',
'cancelled'
]
)
){

$previous[] = $row;

}
else{

$upcoming[] = $row;

}

}

function e($x){
return htmlspecialchars($x);
}

?>




<!DOCTYPE html>
<html lang="en">
<head>
    <script>
        window.FontAwesomeConfig = {
          autoReplaceSvg: 'nest'
        };

        
      </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarPlus - My Bookings</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                        uber: {
                            black: '#000000',
                            white: '#FFFFFF',
                            green: {
                                50: '#ECFDF5',
                                100: '#D1FAE5',
                                200: '#A7F3D0',
                                300: '#6EE7B7',
                                400: '#34D399',
                                500: '#10B981',
                                600: '#059669',
                                700: '#047857',
                                800: '#065F46',
                                900: '#064E3B',
                                neon: '#00FF88'
                            },
                            gray: {
                                50: '#0A0A0A',
                                100: '#141414',
                                200: '#1F1F1F',
                                300: '#2A2A2A',
                                400: '#3D3D3D',
                                500: '#757575',
                                600: '#AFAFAF',
                                700: '#CBCBCB',
                                800: '#E2E2E2',
                                900: '#F6F6F6',
                            }
                        }
                    },
                    boxShadow: {
                        'uber': '0 2px 12px rgba(0, 255, 136, 0.1)',
                        'uber-hover': '0 4px 16px rgba(0, 255, 136, 0.2)',
                        'uber-modal': '0 8px 24px rgba(0, 255, 136, 0.15)',
                        'uber-nav': '0 2px 8px rgba(0, 0, 0, 0.4)',
                        'uber-floating': '0 4px 20px rgba(0, 255, 136, 0.25)'
                    }
                }
            }
        }
    </script>
    <style>
        body { margin: 0; padding: 0; background-color: #000000; }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #141414; }
        ::-webkit-scrollbar-thumb { background: #2A2A2A; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #3D3D3D; }

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
<body class="text-uber-gray-900 font-sans antialiased bg-uber-black flex flex-col min-h-screen">

    <!-- Global Navigation Shell -->
    <?php
$currentPage = 'bookings';
include 'header.php';
?>

    <!-- Main Content Area -->
    <main class="flex-grow flex flex-col w-full max-w-[1024px] mx-auto px-6 pt-28 pb-12 w-full max-w-[1440px] mx-auto px-6 py-10">
        
        <!-- Page Header & Stats -->
        <div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-6">
            <div>
                <h1 class="text-[32px] font-bold text-uber-white tracking-tight mb-2">My Bookings</h1>
                <p class="text-uber-gray-700 text-base">Manage your upcoming trips and view rental history.</p>
            </div>
            
            <div class="flex gap-4">
                <div class="bg-uber-gray-100 px-5 py-3 rounded-[12px] border border-uber-green-neon shadow-uber flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-uber-green-900 flex items-center justify-center text-uber-green-neon">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                    <div>
                        <p class="text-xs text-uber-gray-700 font-medium uppercase tracking-wide">Upcoming</p>
                        <p class="text-xl font-bold text-uber-white"><?php echo count($upcoming); ?></p>
                    </div>
                </div>
                <div class="bg-uber-gray-100 px-5 py-3 rounded-[12px] border border-uber-gray-300 shadow-sm flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-uber-gray-200 flex items-center justify-center text-uber-gray-700">
                        <i class="fa-solid fa-route"></i>
                    </div>
                    <div>
                        <p class="text-xs text-uber-gray-700 font-medium uppercase tracking-wide">Completed</p>
                        <p class="text-xl font-bold text-uber-white"><?php echo count($previous); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upcoming Rentals Section -->
        <section id="upcoming-rentals" class="mb-12">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-uber-white flex items-center gap-2">
                    <i class="fa-solid fa-clock text-uber-green-neon text-lg"></i> Upcoming Rentals
                </h2>
                <a href="#" class="text-sm font-medium text-uber-green-neon hover:underline">View Calendar</a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <?php foreach( $upcoming as $rent ): ?>

<?php if(
strtolower($rent['status']) == 'pending'
){ ?>

<form method="POST" class="mt-3">

    <input
        type="hidden"
        name="cancel_rental_id"
        value="<?php echo $rent['rental_id']; ?>">

    <button
        type="submit"
        onclick="return confirm('Cancel this rental?')"
        class="px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white text-sm">

        Cancel Rental

    </button>

</form>

<?php } ?>

<div class="bg-uber-gray-100 rounded-[16px] overflow-hidden flex flex-col sm:flex-row border border-uber-green-neon">

<div class="w-full sm:w-[240px] h-[200px] bg-black">

<img

src="<?php echo e(
$rent['image_url']
); ?>"

class="w-full h-full object-cover">

</div>

<div class="p-6 flex-grow">

<div class="text-xs text-gray-400">

CP-<?php echo e(
$rent['rental_id']
); ?>

</div>

<h3 class="text-xl font-bold">

<?php echo e(
$rent['car_name']
); ?>

</h3>

<p class="text-gray-400">

<?php echo e(
$rent['car_type']
); ?>

</p>

<br>

Pickup:

<?php echo e(
$rent['pickup_date']
); ?>

<br>

Dropoff:

<?php echo e(
$rent['dropoff_date']
); ?>

<br>

Branch:

<?php echo e(
$rent['branch_name']
); ?>

<br><br>

RM<?php
echo number_format(
$rent['total_price'],
2
);
?>

</div>

</div>

<?php endforeach; ?>
</div>

</section>
        <!-- Previous Rentals Section -->
        <section id="previous-rentals" class="mb-16">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-uber-white flex items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left text-uber-gray-500 text-lg"></i> Previous Rentals
                </h2>
                <div class="flex items-center gap-2">
                    <button class="px-4 py-2 text-sm font-medium border border-uber-gray-300 rounded-lg bg-uber-white hover:bg-uber-gray-50 transition-colors flex items-center gap-2">
                        <i class="fa-solid fa-filter text-uber-gray-500"></i> Filter
                    </button>
                </div>
            </div>

            <div class="bg-uber-black    rounded-[16px] shadow-uber border border-uber-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-uber-gray-50 border-b border-uber-gray-200 text-xs uppercase tracking-wider text-uber-gray-500 font-semibold">
                                <th class="px-6 py-4">Vehicle</th>
                                <th class="px-6 py-4">Dates</th>
                                <th class="px-6 py-4">Location</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-right">Total</th>
                                <th class="px-6 py-4 text-center">Action</th>
                                <th class="px-6 py-4 text-center">Rebook</th>
                            </tr>
                        </thead>
                        
               
        
                        
<tbody>

<?php if(count($previous) > 0): ?>

<?php foreach($previous as $rent): ?>

<tr>

<td class="px-6 py-4">

<div class="flex gap-4">

<img
src="<?php echo e($rent['image_url']); ?>"
class="w-16 h-10 object-cover">

<div>

<?php echo e($rent['car_name']); ?>

<br>

CP-<?php echo e($rent['rental_id']); ?>

</div>

</div>

</td>

<td>

<?php echo e($rent['pickup_date']); ?>

-

<?php echo e($rent['dropoff_date']); ?>

</td>

<td>

<?php echo e($rent['branch_name']); ?>

</td>

<td>

Completed

</td>

<td>

RM<?php echo number_format($rent['total_price'],2); ?>

</td>

<td>

Done

</td>

<td class="px-6 py-4 text-center">
    <a href="checkout.php?car_id=<?php echo $rent['car_id']; ?>" 
       class="inline-flex items-center justify-center px-4 py-2 text-xs font-semibold text-white bg-green-600 hover:bg-green-500 rounded-xl transition-all shadow-md shadow-green-900/20 hover:shadow-green-500/20">
        <i class="fa-solid fa-rotate-left mr-1.5 text-[10px]"></i> Book Again
    </a>
</td>

</tr>

<?php endforeach; ?>

<?php else: ?>

<tr>

<td colspan="6">

No completed rentals

</td>

</tr>

<?php endif; ?>

</tbody>

