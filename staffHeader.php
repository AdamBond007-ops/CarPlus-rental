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

    $sql = "
    SELECT *
    FROM users
    WHERE user_id = '$user_id'
    ";

    $user_result = $conn->query($sql);

    $user = $user_result->fetch_assoc() ?? [];
}
?>

<style>
.glass-panel{
    background: rgba(18,18,18,0.75);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);

    border-bottom: 1px solid rgba(255,255,255,0.08);

    box-shadow:
        0 8px 32px rgba(0,0,0,0.35);
}
</style>

<header
id="header"
class="fixed top-0 left-0 right-0 z-50 glass-panel py-4 px-6 lg:px-12">

    <div class="max-w-7xl mx-auto flex items-center justify-between">

        <!-- Logo -->
        <a
        href="staffPortal.php"
        class="flex items-center gap-3 text-xl font-bold tracking-tight text-white">

            <div
            class="w-9 h-9
            bg-[#00FF66]
            rounded-lg
            flex items-center justify-center
            shadow-[0_0_15px_rgba(0,255,102,0.35)]">

                <i
                class="fa-solid fa-car-side text-black text-sm">
                </i>

            </div>

            <span>
                CarPlus Staff
            </span>

        </a>

        <!-- Navigation -->
        <nav class="hidden md:flex items-center space-x-8">

            <a
            href="staffPortal.php"
            class="<?php echo ($currentPage == 'staffPortal.php')
            ? 'text-white relative after:absolute after:bottom-[-4px] after:left-0 after:w-full after:h-[2px] after:bg-[#00FF66]'
            : 'text-gray-400 hover:text-white'; ?>">

                Rentals

            </a>

            <a
            href="carsStaff.php"
            class="<?php echo ($currentPage == 'carsStaff.php')
            ? 'text-white relative after:absolute after:bottom-[-4px] after:left-0 after:w-full after:h-[2px] after:bg-[#00FF66]'
            : 'text-gray-400 hover:text-white'; ?>">

                Cars

            </a>

        </nav>

        <!-- User Dropdown -->
        <div class="relative group cursor-pointer">

            <div
            class="flex items-center gap-3 py-2 px-3 rounded-full border border-white/10">

                <div
                class="w-8 h-8 rounded-full overflow-hidden">

                    <img
                    src="uploads/<?php echo !empty($user['profile_image']) ? $user['profile_image'] : 'default-avatar.png'; ?>"
                    class="w-full h-full object-cover">

                </div>

                <div
                class="flex items-center gap-2">

                    <span
                    class="text-sm font-medium text-white">

                        <?php echo $user['name'] ?? 'Staff'; ?>

                    </span>

                    <i
                    class="fa-solid fa-chevron-down text-[10px] text-gray-400">
                    </i>

                </div>

            </div>

            <div
            class="absolute right-0 top-full mt-2 w-56
            bg-[#121212]/90
            backdrop-blur-xl
            border border-white/10
            rounded-xl
            py-2
            hidden group-hover:block">

                <div
                class="px-4 py-3 border-b border-white/10 mb-2">

                    <p
                    class="text-sm font-semibold text-white">

                        <?php echo $user['name']; ?>

                    </p>

                    <p
                    class="text-xs text-gray-400">

                        <?php echo $user['email'] ?? ''; ?>

                    </p>

                </div>

                <a
                href="staffProfile.php"
                class="flex items-center gap-3 px-4 py-2.5 text-sm text-white hover:bg-black/30">

                    <i class="fa-regular fa-user w-5"></i>

                    Profile

                </a>

                <div
                class="h-px bg-white/10 my-2">
                </div>

                <a
                href="logout.php"
                class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-500 hover:bg-red-500/10">

                    <i class="fa-solid fa-arrow-right-from-bracket w-5"></i>

                    Sign Out

                </a>

            </div>

        </div>

    </div>

</header>