<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($conn)) {
    include 'dbConnect.php';
}

$currentPage = basename($_SERVER['PHP_SELF']);

$user_id = $_SESSION['user_id'] ?? 0;

$user = [];

if ($user_id) {

    $sql =
        "SELECT * FROM users
         WHERE user_id = '$user_id'";

    $result =
        $conn->query($sql);

    $user =
        $result->fetch_assoc() ?? [];
}
?>

 <header id="header" class="fixed top-0 left-0 right-0 z-50 glass-panel border-b-0 border-white/5 py-4 px-6 lg:px-12 transition-all duration-300">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            
            <!-- Logo -->
            <a href="#" class="flex items-center gap-2 text-xl font-bold tracking-tight text-white">
                    <div class="w-8 h-8 bg-accent hover:bg-accentHover text-uber-white flex items-center justify-center rounded-lg">
                        <i class="fa-solid fa-car-side text-sm"></i>
                    </div>
                    CarPlus
                </a>

            <!-- Desktop Navigation -->
            <nav class="hidden md:flex items-center space-x-8">
               <a
href="homepage.php"
class="<?php echo ($currentPage == 'homepage.php')
? 'text-white relative after:absolute after:bottom-[-4px] after:left-0 after:w-full after:h-[2px] after:bg-accent'
: 'text-gray-400'; ?>">
Homepage
</a>

<a
href="rentalDetails.php"
class="<?php echo ($currentPage == 'rentalDetails.php')
? 'text-white relative after:absolute after:bottom-[-4px] after:left-0 after:w-full after:h-[2px] after:bg-accent'
: 'text-gray-400'; ?>">
Bookings
</a>
            </nav>

            <!-- Auth/User Area -->

            <div class="relative group cursor-pointer">
                    <div class="flex items-center gap-3 py-2 px-3 rounded-full hover:bg-uber-gray-100 transition-colors border border-uber-gray-200 shadow-sm">
                        <div class="w-8 h-8 rounded-full overflow-hidden bg-uber-gray-200">
                            <img
    src="uploads/<?php echo !empty($user['profile_image']) ? $user['profile_image'] : 'default-avatar.png'; ?>" alt="User Avatar" class="w-full h-full object-cover">
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-white">
    <?php echo $user['name'] ?? 'User'; ?>
</span>
                            <i class="fa-solid fa-chevron-down text-[10px] text-gray-400"></i>
                        </div>
                    </div>
                    
                    <!-- Dropdown (Hidden by default, shown on hover for demo) -->
                    <div class="absolute right-0 top-full mt-2 w-56 bg-[#121212]/80 backdrop-blur-xl border border-white/10 rounded-xl shadow-uber-modal border border-uber-gray-100 py-2 hidden group-hover:block z-50">
                        <div class="px-4 py-3 border-b border-uber-gray-100 mb-2">
                            <p class="text-sm font-semibold text-white">
                            <?php echo $user['name']; ?>
                            </p>
                            <p class="text-xs text-uber-gray-500 mt-0.5">
                                <?php echo $user['email'] ?? ''; ?>
                            </p>
                        </div>
                        <a href="userprofile.php" class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium text-white bg-[#0a0a0a]">
                            <i class="fa-regular fa-user w-5 text-center"></i> User Profile
                        </a>
                        
                        
                        <div class="h-px bg-uber-gray-100 my-2"></div>
                        <a href="logout.php" class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50 transition-colors">
                            <i class="fa-solid fa-arrow-right-from-bracket w-5 text-center"></i> Sign Out
                        </a>
                    </div>
                </div>

                    <!-- <a href="userprofile.php"
        class="flex items-center gap-3 border-r border-white/10 pr-6 hover:opacity-80 transition-opacity"> -->

            <!-- <img
                src="uploads/<?php echo isset($_SESSION['profile_image']) ? $_SESSION['profile_image'] : 'default-avatar.png'; ?>"
                alt="User Avatar"
                class="w-8 h-8 rounded-full border border-accent/50"> -->

            <!-- <span class="text-white text-sm font-medium">
                Welcome back,
                <?php echo $_SESSION['name']; ?>
            </span> -->



        </a>
                <!-- <a href="logout.php" class="text-gray-400 text-sm font-medium hover:text-white transition-colors flex items-center gap-2">
    <i class="fa-solid fa-arrow-right-from-bracket"></i>
    Sign Out
</a> -->
            </div>

            <!-- Mobile Menu Button -->
            <button class="md:hidden text-white text-2xl focus:outline-none">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>
    </header>