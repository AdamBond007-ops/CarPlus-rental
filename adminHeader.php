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

    $user_result =
$conn->query($sql);

$user =
$user_result->fetch_assoc() ?? [];
}
?>

<header id="header"
class="fixed top-0 left-0 right-0 z-50 glass-panel border-b-0 border-white/5 py-4 px-6 lg:px-12 transition-all duration-300">

    <div class="max-w-7xl mx-auto flex items-center justify-between">

        <!-- Logo -->
        <a href="adminDashboard.php"
        class="flex items-center gap-2 text-xl font-bold tracking-tight text-white">

<div
class="w-8 h-8
bg-[#00FF66]
flex items-center justify-center
rounded-lg
shadow-[0_0_15px_rgba(0,255,102,0.35)]">                
<i class="fa-solid fa-car-side text-sm text-black"></i>
            </div>

            CarPlus Admin

        </a>

        <!-- Navigation -->
        <nav class="hidden md:flex items-center space-x-8">

            <a
            href="adminDashboard.php"
            class="<?php echo ($currentPage == 'adminDashboard.php')
            ? 'text-white relative after:absolute after:bottom-[-4px] after:left-0 after:w-full after:h-[2px] after:bg-accent'
            : 'text-gray-400'; ?>">
                Rentals
            </a>

            <a
            href="carsAdmin.php"
            class="<?php echo ($currentPage == 'carsAdmin.php')
            ? 'text-white relative after:absolute after:bottom-[-4px] after:left-0 after:w-full after:h-[2px] after:bg-accent'
            : 'text-gray-400'; ?>">
                Cars
            </a>

            <a
            href="staffAdmin.php"
            class="<?php echo ($currentPage == 'staffAdmin.php')
            ? 'text-white relative after:absolute after:bottom-[-4px] after:left-0 after:w-full after:h-[2px] after:bg-accent'
            : 'text-gray-400'; ?>">
                Staff
            </a>

        </nav>

        <!-- User Menu -->
        <div class="relative group cursor-pointer">

            <div class="flex items-center gap-3 py-2 px-3 rounded-full hover:bg-uber-gray-100 transition-colors border border-uber-gray-200 shadow-sm">

                <div class="w-8 h-8 rounded-full overflow-hidden bg-uber-gray-200">
                    <img
                    src="uploads/<?php echo !empty($user['profile_image']) ? $user['profile_image'] : 'default-avatar.png'; ?>"
                    class="w-full h-full object-cover">
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium text-white">
                        <?php echo $user['name'] ?? 'Admin'; ?>
                    </span>

                    <i class="fa-solid fa-chevron-down text-[10px] text-gray-400"></i>
                </div>

            </div>

            <div class="absolute right-0 top-full mt-2 w-56 bg-[#121212]/80 backdrop-blur-xl border border-white/10 rounded-xl py-2 hidden group-hover:block z-50">

                <div class="px-4 py-3 border-b border-white/10 mb-2">
                    <p class="text-sm font-semibold text-white">
                        <?php echo $user['name']; ?>
                    </p>

                    <p class="text-xs text-gray-400">
                        <?php echo $user['email']; ?>
                    </p>
                </div>

                <a href="adminProfile.php"
                class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium text-white hover:bg-black/30">
                    <i class="fa-regular fa-user w-5 text-center"></i>
                    Profile
                </a>

                <div class="h-px bg-white/10 my-2"></div>

                <a href="logout.php"
                class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium text-red-500 hover:bg-red-500/10">
                    <i class="fa-solid fa-arrow-right-from-bracket w-5 text-center"></i>
                    Sign Out
                </a>

            </div>

        </div>

    </div>

</header>