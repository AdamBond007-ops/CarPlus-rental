<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}


$user_id = (int) $_SESSION['user_id'];

/* DATABASE CONNECTION */
$conn = new mysqli("sql103.infinityfree.com", "if0_42143898", "Umarjztr313", "if0_42143898_carplus");

/* CHECK CONNECTION */
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/* FETCH USER */
$sql = "SELECT * FROM users WHERE user_id = '$user_id'";
$result = $conn->query($sql);

$user = $result->fetch_assoc() ?? [];


/* FETCH CARS */
/* DEFAULT QUERY */
$sql = "
SELECT cars.*, branches.branch_name
FROM cars
LEFT JOIN branches
ON cars.branch_id = branches.branch_id
WHERE cars.availability = 1
";

/* SEARCH FILTER */
$conditions = ["cars.availability = 1"];

/* BRANCH FILTER */
if (!empty($_GET['branch_id'])) {
    $branch_id = (int) $_GET['branch_id'];
    $conditions[] = "cars.branch_id = $branch_id";
}

/* CAR NAME FILTER */
if (!empty($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    $conditions[] = "cars.name LIKE '%$search%'";
}

/* PRICE MIN FILTER */
if (isset($_GET['price_min']) && $_GET['price_min'] !== '') {
    $price_min = (float) $_GET['price_min'];
    $conditions[] = "cars.price_per_day >= $price_min";
}

/* PRICE MAX FILTER */
if (isset($_GET['price_max']) && $_GET['price_max'] !== '') {
    $price_max = (float) $_GET['price_max'];
    $conditions[] = "cars.price_per_day <= $price_max";
}

/* BRAND FILTER — matches car name containing brand keyword */
if (!empty($_GET['brand'])) {
    $brand = $conn->real_escape_string($_GET['brand']);
    $conditions[] = "cars.name LIKE '%$brand%'";
}

/* CATEGORY / CAR TYPE FILTER */
if (!empty($_GET['car_type'])) {
    $car_types = array_map(fn($t) => "'" . $conn->real_escape_string(trim($t)) . "'", $_GET['car_type']);
    $conditions[] = "cars.car_type IN (" . implode(',', $car_types) . ")";
}

/* FINAL WHERE */
$where = implode(" AND ", $conditions);

/* FINAL QUERY */
$sql = "
SELECT cars.*, branches.branch_name
FROM cars
LEFT JOIN branches ON cars.branch_id = branches.branch_id
WHERE $where
ORDER BY cars.price_per_day ASC
";

$car_result = $conn->query($sql);

if (!$car_result) {
    die("SQL Error: " . $conn->error);
}

/* Preserve filter values for repopulating the form */
$filter_price_min  = isset($_GET['price_min'])  ? (float)$_GET['price_min']  : '';
$filter_price_max  = isset($_GET['price_max'])  ? (float)$_GET['price_max']  : '';
$filter_brand      = $_GET['brand']    ?? '';
$filter_car_types  = $_GET['car_type'] ?? [];
$filter_branch_id  = $_GET['branch_id'] ?? '';

/* FETCH BRANCHES */
$branch_sql = "SELECT * FROM branches";
$branch_result = $conn->query($branch_sql);
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
    <title>CarPlus - Homepage (Logged In)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Oswald:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
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
                        accent: '#16a34a', // Electric blue
                        accentHover: '#15803d',
                        surface: 'rgba(30, 33, 50, 0.6)',
                        surfaceLight: 'rgba(255, 255, 255, 0.05)',
                    },
                    backgroundImage: {
                        'gradient-radial': 'radial-gradient(var(--tw-gradient-stops))',
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
        .glass-panel {
            background: rgba(18, 18, 18, 0.6);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
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
        .input-glass {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        .input-glass:focus {
            background: rgba(255, 255, 255, 0.05);
            border-color: #16a34a;
            box-shadow: 0 0 15px rgba(22, 163, 74, 0.2);
            outline: none;
        }
        /* Custom scrollbar */
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
        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col relative overflow-x-hidden">

    <!-- Global Background -->
    <div class="fixed inset-0 z-0 pointer-events-none">
        <div class="absolute inset-0 bg-gradient-radial from-darker/60 via-darker/90 to-darker z-10"></div>
    </div>

        <?php
$currentPage = 'home';
include 'header.php';
?>

    <!-- Header / Navigation -->
    

    <!-- Main Content -->
    <main class="relative z-20 flex-grow pt-28 pb-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Welcome Hero & Search Panel -->
            <div class="glass-panel rounded-[32px] p-8 mb-12 bg-glow border-accent/10">
                <div class="flex flex-col lg:flex-row gap-8 items-center">
                    <!-- Left: Hero Text & Image -->
                    <div class="w-full lg:w-1/2 flex flex-col items-start">
                        <h1 class="text-4xl md:text-5xl font-display font-bold text-white tracking-wide mb-4">
                            ALL IN ONE <span class="text-accent text-glow">CAR PLATFORM</span>
                        </h1>
                        <p class="text-gray-400 text-lg mb-8 max-w-md">Renting a car gives you freedom, and we'll help you find the perfect match for your journey.</p>
                        
                        <div class="w-full h-48 relative rounded-2xl overflow-hidden glass-panel border-white/5">
                            <img class="w-full h-full object-cover opacity-80" src="https://storage.googleapis.com/uxpilot-auth.appspot.com/0a3d19eb67-587d1b069d99e2280f2b.png" alt="three luxury sports cars parked together, sleek modern design, dramatic studio lighting, dark background, blue red and green colors" />
                        </div>
                    </div>
                    
                    <!-- Right: Search Form -->
                    <div class="w-full lg:w-1/2">

                        <form method="GET" id="filter-form">
   
                            <!-- Search Name -->
                        <div class="mb-8">

                            <div class="relative">

                                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-500"></i>

                                <input
                                    type="text"
                                    name="search"
                                    class="w-full input-glass rounded-xl pl-11 pr-4 py-3 text-white placeholder-gray-500 text-sm"
                                    placeholder="Search car model...">

                            </div>

                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Location -->
                            <div class="col-span-1 md:col-span-2 flex flex-col gap-2">

    <label class="text-xs text-gray-400 font-medium uppercase tracking-wider ml-1">
        Branch Location
    </label>

    <div class="relative">

        <i class="fa-solid fa-location-dot absolute left-4 top-1/2 transform -translate-y-1/2 text-accent z-10"></i>

        <select
            name="branch_id"
            class="w-full input-glass rounded-xl pl-11 pr-4 py-3.5 text-white text-sm font-medium appearance-none bg-[#121212]">

            <option value="" class="bg-[#121212]">
                Select Branch
            </option>

            <?php
            while($branch = $branch_result->fetch_assoc()) {
            ?>

                <option
                    value="<?php echo $branch['branch_id']; ?>"
                    class="bg-[#121212]">

                    <?php echo $branch['branch_name']; ?>

                </option>

            <?php
            }
            ?>

        </select>

    </div>

</div>
                            
                            <!-- Dates & Times -->
                            <div class="flex flex-col gap-2">
                                <label class="text-xs text-gray-400 font-medium uppercase tracking-wider ml-1">Pick-up Date</label>
                                <div class="relative">
                                    <i class="fa-regular fa-calendar absolute left-4 top-1/2 transform -translate-y-1/2 text-accent"></i>
                                    <input
                                    type="date"
                                    name="pickup_date"
                                    
                                    min="<?php echo date('Y-m-d'); ?>"
                                    class="w-full input-glass rounded-xl pl-11 pr-4 py-3.5 text-white text-sm font-medium bg-[#121212] cursor-pointer"
                                    style="color-scheme: dark;"
                                    onclick="this.showPicker()">
                                </div>
                            </div>
                            <div class="flex flex-col gap-2">
                                <label class="text-xs text-gray-400 font-medium uppercase tracking-wider ml-1">Pick-up Time</label>
                                <div class="relative">
                                    <i class="fa-regular fa-clock absolute left-4 top-1/2 transform -translate-y-1/2 text-accent"></i>
                                    <input
                                        type="time"
                                        name="pickup_time"
                                        
                                        class="w-full input-glass rounded-xl pl-11 pr-4 py-3.5 text-white text-sm font-medium bg-[#121212]">
                                </div>
                            </div>
                            
                            <div class="flex flex-col gap-2">
                                <label class="text-xs text-gray-400 font-medium uppercase tracking-wider ml-1">Drop-off Date</label>
                                <div class="relative">
                                    <i class="fa-regular fa-calendar absolute left-4 top-1/2 transform -translate-y-1/2 text-accent"></i>
                                    <input
                                    type="date"
                                    name="dropoff_date"
                                    
                                    min="<?php echo date('Y-m-d'); ?>"
                                    class="w-full input-glass rounded-xl pl-11 pr-4 py-3.5 text-white text-sm font-medium bg-[#121212] cursor-pointer"
                                    style="color-scheme: dark;"
                                    onclick="this.showPicker()">
                                </div>
                            </div>
                            <div class="flex flex-col gap-2">
                                <label class="text-xs text-gray-400 font-medium uppercase tracking-wider ml-1">Drop-off Time</label>
                                <div class="relative">
                                    <i class="fa-regular fa-clock absolute left-4 top-1/2 transform -translate-y-1/2 text-accent"></i>
                                    <input
                                        type="time"
                                        name="dropoff_time"
                                        
                                        class="w-full input-glass rounded-xl pl-11 pr-4 py-3.5 text-white text-sm font-medium bg-[#121212]">
                                </div>
                            </div>
                            
                            <!-- Search Button -->
                            <div class="col-span-1 md:col-span-2 mt-2">
                                
                                <button
                                    type="submit"
                                    class="w-full py-4 bg-accent hover:bg-accentHover text-white rounded-xl font-bold text-lg transition-all duration-300 shadow-[0_0_20px_rgba(37,99,235,0.4)] hover:shadow-[0_0_30px_rgba(37,99,235,0.6)]">

                                    Search Vehicles

                                </button>

                            </div>
                        </div>
                        </div>
                        </div>
                        </div>

            <div class="flex flex-col lg:flex-row gap-8">
                <!-- Left Sidebar: Filters -->
                <div class="w-full lg:w-1/4 flex flex-col gap-6">
                    <div class="glass-panel rounded-[24px] p-6 sticky top-28">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-xl font-display font-bold text-white tracking-wide">Filters</h3>
                            <a href="homepage.php" class="text-sm text-gray-400 hover:text-accent transition-colors">Reset</a>
                        </div>

                        <!-- Price Range -->
                        <div class="mb-8 border-t border-white/5 pt-6">
                            <h4 class="text-sm font-medium text-white mb-2">Price & Budget</h4>
                            <p class="text-xs text-gray-400 mb-4">Filter by price per day (RM)</p>
                            <div class="flex gap-4">
                                <div class="w-1/2">
                                    <label class="text-[10px] text-gray-500 uppercase tracking-wider mb-1 block" for="price_min">Min Price</label>
                                    <input
                                        type="number"
                                        id="price_min"
                                        name="price_min"
                                        min="0"
                                        placeholder="e.g. 200"
                                        value="<?php echo htmlspecialchars($filter_price_min); ?>"
                                        class="input-glass rounded-lg px-3 py-2 text-white text-sm w-full focus:outline-none"
                                    >
                                </div>
                                <div class="w-1/2">
                                    <label class="text-[10px] text-gray-500 uppercase tracking-wider mb-1 block" for="price_max">Max Price</label>
                                    <input
                                        type="number"
                                        id="price_max"
                                        name="price_max"
                                        min="0"
                                        placeholder="e.g. 1000"
                                        value="<?php echo htmlspecialchars($filter_price_max); ?>"
                                        class="input-glass rounded-lg px-3 py-2 text-white text-sm w-full focus:outline-none"
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Brands -->
                        <div class="mb-8 border-t border-white/5 pt-6">
                            <h4 class="text-sm font-medium text-white mb-4">Brand</h4>
                            <div class="flex flex-wrap gap-2">
                                <?php
                                $brands = ['Porsche','BMW','Mercedes','Audi','Ferrari','Lamborghini','Mitsubishi','Volkswagen','Dodge'];
                                foreach ($brands as $b):
                                    $active = ($filter_brand === $b);
                                ?>
                                <button
                                    type="button"
                                    onclick="toggleBrand(this, '<?php echo $b; ?>')"
                                    data-brand="<?php echo $b; ?>"
                                    class="brand-btn px-4 py-2 rounded-lg text-xs font-medium transition-colors <?php echo $active ? 'bg-accent border border-accent text-white' : 'input-glass text-gray-300 hover:text-white border border-white/10'; ?>">
                                    <?php echo $b; ?>
                                </button>
                                <?php endforeach; ?>
                                <!-- Hidden input holds the selected brand value -->
                                <input type="hidden" id="brand-input" name="brand" value="<?php echo htmlspecialchars($filter_brand); ?>">
                            </div>
                        </div>

                        <!-- Categories / Car Type -->
                        <div class="border-t border-white/5 pt-6">
                            <h4 class="text-sm font-medium text-white mb-4">Category</h4>
                            <div class="space-y-3">
                                <?php
                                $car_type_options = [
                                    'coupe'      => 'Coupe',
                                    'supercar'   => 'Supercar',
                                    'hatchback'  => 'Hatchback',
                                    'sedan'      => 'Sedan',
                                    'suv'        => 'SUV',
                                    'Concept Car'=> 'Concept Car',
                                ];
                                foreach ($car_type_options as $val => $label):
                                    $checked = in_array($val, $filter_car_types);
                                ?>
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <div class="relative w-5 h-5">
                                        <input
                                            type="checkbox"
                                            name="car_type[]"
                                            value="<?php echo $val; ?>"
                                            <?php echo $checked ? 'checked' : ''; ?>
                                            class="peer absolute opacity-0 w-0 h-0"
                                            onchange="this.closest('form').submit()"
                                        >
                                        <div class="w-5 h-5 rounded border border-white/20 bg-white/5 flex items-center justify-center peer-checked:border-accent peer-checked:bg-accent/20 transition-colors group-hover:border-white/40">
                                            <i class="fa-solid fa-check text-xs text-white opacity-0 peer-checked:opacity-100 transition-opacity" style="display:none"></i>
                                        </div>
                                        <!-- visible checkmark overlay -->
                                    </div>
                                    <span class="text-sm <?php echo $checked ? 'text-white' : 'text-gray-300 group-hover:text-white'; ?> transition-colors"><?php echo $label; ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Apply Filters Button -->
                        <div class="mt-6 border-t border-white/5 pt-6">
                            <button type="submit" class="w-full py-3 bg-accent hover:bg-accentHover text-white rounded-xl font-bold text-sm transition-all duration-300">
                                Apply Filters
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Right Content: Car Grid & Recent -->
                <div class="w-full lg:w-3/4 flex flex-col gap-8">
                    
                    <!-- Recent Activity / Saved Searches Strip -->
                    <div class="glass-panel rounded-[20px] p-4 flex items-center justify-between overflow-x-auto">
                        <div class="flex items-center gap-4 min-w-max">
                            <span class="text-sm font-medium text-gray-400 mr-2">Recent Searches:</span>
                            <button class="flex items-center gap-2 px-4 py-2 bg-white/5 hover:bg-white/10 border border-white/10 rounded-full text-xs text-white transition-colors">
                                <span>Miami • Oct 24 - Oct 27</span>
                                <i class="fa-solid fa-xmark text-gray-500 hover:text-white"></i>
                            </button>
                            <button class="flex items-center gap-2 px-4 py-2 bg-white/5 hover:bg-white/10 border border-white/10 rounded-full text-xs text-white transition-colors">
                                <span>Los Angeles • Sports Cars</span>
                                <i class="fa-solid fa-xmark text-gray-500 hover:text-white"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Header for Grid -->
                    <div class="flex justify-between items-end">
                        <div>
                            <h2 class="text-2xl font-display font-bold text-white tracking-wide">RECOMMENDED <span class="text-accent text-glow">VEHICLES</span></h2>
                            <p class="text-sm text-gray-400 mt-1">Showing <?php echo $car_result->num_rows; ?> vehicle<?php echo $car_result->num_rows !== 1 ? 's' : ''; ?> matching your criteria</p>
                        </div>
                        <div class="flex gap-2">
                            <button class="w-10 h-10 rounded-lg glass-panel flex items-center justify-center text-accent bg-white/5">
                                <i class="fa-solid fa-grid-2"></i>
                            </button>
                            <button class="w-10 h-10 rounded-lg glass-panel flex items-center justify-center text-gray-400 hover:text-white transition-colors">
                                <i class="fa-solid fa-list"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Car Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">

                    <?php


if ($car_result->num_rows > 0) {

    while($car = $car_result->fetch_assoc()) {

     
        $detail_params = ["id" => $car['car_id']];

        foreach (["pickup_date", "pickup_time", "dropoff_date", "dropoff_time"] as $booking_field) {
            if (isset($_GET[$booking_field]) && $_GET[$booking_field] !== '') {
                $detail_params[$booking_field] = $_GET[$booking_field];
            }
        }

        $detail_url = "carDetail.php?" . http_build_query($detail_params);
?>

<div class="glass-panel rounded-[24px] p-5 flex flex-col group bg-glow-hover transition-all duration-300">

    <!-- Car Name -->
    <div class="flex justify-between items-start mb-4">
        <div>
            <h3 class="text-lg font-display font-bold text-white">
                <?php echo $car['name']; ?>
            </h3>

            <p class="text-xs text-gray-400">
                <?php echo $car['car_type']; ?>
            </p>
        </div>

        <div class="flex items-center gap-1 bg-[#0a0a0a]/80 px-2 py-1 rounded-md border border-white/10">
            <i class="fa-solid fa-star text-accent text-[10px]"></i>
            <span class="text-white text-xs font-bold">4.9</span>
        </div>
    </div>

    <!-- Car Image -->
    <a href="#" class="block h-40 mb-4 relative rounded-xl overflow-hidden bg-darker/50">

        <img 
            src="<?php echo $car['image_url']; ?>" 
            alt="<?php echo $car['name']; ?>"
            class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-500 opacity-90"
        >

    </a>

    <!-- Price -->
    <div class="flex justify-between items-end mb-4 border-b border-white/5 pb-4">

        <div class="flex items-center gap-2 text-xs text-gray-400">
    <i class="fa-solid fa-location-dot text-accent/70"></i>

    <span>
        <?php echo $car['branch_name']; ?>
    </span>
</div>

        <div class="text-right">
            <span class="text-xl font-display font-bold text-white">
                RM<?php echo $car['price_per_day']; ?>
            </span>

            <span class="text-[10px] text-gray-500 uppercase">
                /day
            </span>
        </div>

    </div>

    <!-- Description -->
    <div class="mb-5 text-xs text-gray-300 line-clamp-2">
        <?php echo $car['description']; ?>
    </div>

    <!-- Button -->
    <a href="<?php echo $detail_url; ?>"
   class="w-full py-3 bg-white/5 hover:bg-accent text-white text-center rounded-xl font-semibold text-sm transition-all duration-300 border border-white/10 hover:border-accent">

    View Details

</a>

</div>

<?php
    }
} else {
    echo "<p class='text-white'>No cars available.</p>";
}
?>

                    </div>
                    
                    <!-- Pagination -->
                    <div class="flex justify-center mt-8">
                        <div class="flex items-center gap-2 glass-panel rounded-full px-4 py-2 border-white/5">
                            <button class="w-8 h-8 rounded-full flex items-center justify-center text-gray-500 hover:text-white hover:bg-white/10 transition-colors">
                                <i class="fa-solid fa-chevron-left text-xs"></i>
                            </button>
                            <button class="w-8 h-8 rounded-full flex items-center justify-center bg-accent text-white font-medium text-sm shadow-[0_0_10px_rgba(37,99,235,0.4)]">1</button>
                            <button class="w-8 h-8 rounded-full flex items-center justify-center text-gray-400 hover:text-white hover:bg-white/10 transition-colors font-medium text-sm">2</button>
                            <button class="w-8 h-8 rounded-full flex items-center justify-center text-gray-400 hover:text-white hover:bg-white/10 transition-colors font-medium text-sm">3</button>
                            <span class="text-gray-500 mx-1">...</span>
                            <button class="w-8 h-8 rounded-full flex items-center justify-center text-gray-500 hover:text-white hover:bg-white/10 transition-colors">
                                <i class="fa-solid fa-chevron-right text-xs"></i>
                            </button>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </form>
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

    <script>
        /* ── BRAND TOGGLE ── */
        function toggleBrand(btn, brand) {
            const input = document.getElementById('brand-input');
            const allBtns = document.querySelectorAll('.brand-btn');

            if (input.value === brand) {
                // Deselect
                input.value = '';
                btn.classList.remove('bg-accent', 'border-accent', 'text-white');
                btn.classList.add('input-glass', 'text-gray-300', 'border-white/10');
            } else {
                // Deselect all first
                allBtns.forEach(b => {
                    b.classList.remove('bg-accent', 'border-accent', 'text-white');
                    b.classList.add('input-glass', 'text-gray-300', 'border-white/10');
                });
                // Select clicked
                input.value = brand;
                btn.classList.add('bg-accent', 'border-accent', 'text-white');
                btn.classList.remove('input-glass', 'text-gray-300', 'border-white/10');
            }
        }

        /* ── CHECKBOX VISUAL (peer trick fix for FA icons) ── */
        document.querySelectorAll('input[name="car_type[]"]').forEach(cb => {
            const icon = cb.closest('label').querySelector('.fa-check');
            const box  = cb.closest('label').querySelector('div > div');

            function syncVisual() {
                if (cb.checked) {
                    icon.style.display = '';
                    box.classList.add('border-accent', 'bg-accent/20');
                    box.classList.remove('border-white/20', 'bg-white/5');
                } else {
                    icon.style.display = 'none';
                    box.classList.remove('border-accent', 'bg-accent/20');
                    box.classList.add('border-white/20', 'bg-white/5');
                }
            }
            syncVisual(); // run on load
            cb.addEventListener('change', syncVisual);
        });

        /* ── PRESERVE SEARCH FORM VALUES ON LOAD ── */
        (function() {
            const params = new URLSearchParams(window.location.search);
            const searchEl = document.querySelector('input[name="search"]');
            if (searchEl && params.get('search')) searchEl.value = params.get('search');

            const branchEl = document.querySelector('select[name="branch_id"]');
            if (branchEl && params.get('branch_id')) branchEl.value = params.get('branch_id');

            const pickupDate = document.querySelector('input[name="pickup_date"]');
            if (pickupDate && params.get('pickup_date')) pickupDate.value = params.get('pickup_date');

            const pickupTime = document.querySelector('input[name="pickup_time"]');
            if (pickupTime && params.get('pickup_time')) pickupTime.value = params.get('pickup_time');

            const dropoffDate = document.querySelector('input[name="dropoff_date"]');
            if (dropoffDate && params.get('dropoff_date')) dropoffDate.value = params.get('dropoff_date');

            const dropoffTime = document.querySelector('input[name="dropoff_time"]');
            if (dropoffTime && params.get('dropoff_time')) dropoffTime.value = params.get('dropoff_time');
        })();
    </script>
</body>
</html>