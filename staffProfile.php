<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

include 'dbConnect.php';

$user_id = $_SESSION['user_id'];

/* FETCH USER DATA */
$sql = "SELECT * FROM users WHERE user_id = '$user_id'";
$result = $conn->query($sql);

$user = $result->fetch_assoc();

/* UPDATE PROFILE */
$pw_error   = '';   // error message shown in form
$pw_success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $profile_image = $user['profile_image'];

    /* IMAGE UPLOAD */
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $image_name    = time() . '_' . $_FILES['profile_image']['name'];
        $target        = "uploads/" . $image_name;
        move_uploaded_file($_FILES['profile_image']['tmp_name'], $target);
        $profile_image = $image_name;
    }

    $name  = $_POST['name'];
    $phone = $_POST['phone'];

    /* UPDATE BASIC INFO */
    $update_sql = "
        UPDATE users
        SET name = '$name', phone = '$phone', profile_image = '$profile_image'
        WHERE user_id = '$user_id'
    ";
    $conn->query($update_sql);

    /* PASSWORD CHANGE */
    $cur_pw  = $_POST['current_password'] ?? '';
    $new_pw  = $_POST['new_password']     ?? '';
    $conf_pw = $_POST['confirm_password'] ?? '';

    if ($cur_pw !== '' || $new_pw !== '' || $conf_pw !== '') {

        /* 1. Current password must not be empty */
        if ($cur_pw === '') {
            $pw_error = 'Please enter your current password.';

        } else {
            /* Fetch hash from DB */
            $hash_res  = $conn->query("SELECT password FROM users WHERE user_id = '$user_id'");
            $hash_row  = $hash_res->fetch_assoc();

            /* 2. Verify current password against DB hash */
            if (!password_verify($cur_pw, $hash_row['password'])) {
                $pw_error = 'Current password is incorrect. Please try again.';

            } else {
                /* 3. Strength checks on new password */
                if ($new_pw === '') {
                    $pw_error = 'New password cannot be empty.';
                } elseif (strlen($new_pw) < 8) {
                    $pw_error = 'New password must be at least 8 characters.';
                } elseif (!preg_match('/[A-Z]/', $new_pw)) {
                    $pw_error = 'New password must contain at least one uppercase letter.';
                } elseif (!preg_match('/[0-9!@#$%^&*()\-_=+\[\]{};|,.<>\/?`~@]/', $new_pw)) {
                    $pw_error = 'New password must contain at least one number or symbol.';
                } elseif (password_verify($new_pw, $hash_row['password'])) {
                    $pw_error = 'New password cannot be the same as your current password.';
                } elseif ($new_pw !== $conf_pw) {
                    $pw_error = 'New password and confirmation do not match.';
                } else {
                    /* ALL CHECKS PASSED — update password */
                    $hashed = password_hash($new_pw, PASSWORD_DEFAULT);
                    $conn->query("UPDATE users SET password = '$hashed' WHERE user_id = '$user_id'");
                    $pw_success = true;
                }
            }
        }
    }

    /* Only redirect (profile saved) when there is NO password error */
    if ($pw_error === '') {
        $_SESSION['profile_image'] = $profile_image;
        $_SESSION['name']          = $name;
        $redirect_flag = $pw_success ? 'pw_updated' : '1';
        header("Location: " . basename(__FILE__) . "?updated=" . $redirect_flag);
        exit();
    }

    /* Password error: re-fetch user so the form repopulates correctly */
    $result = $conn->query("SELECT * FROM users WHERE user_id = '$user_id'");
    $user   = $result->fetch_assoc();
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
    <title>CarPlus - User Profile</title>
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
                                dark: '#121212',
                                darker: '#0a0a0a',
                        uber: {
                            black: '#000000',
                            white: '#FFFFFF',
                            gray: {
                                50: '#F6F6F6',
                                100: '#EEEEEE',
                                200: '#E2E2E2',
                                300: '#CBCBCB',
                                400: '#AFAFAF',
                                500: '#757575',
                                600: '#545454',
                                700: '#333333',
                                800: '#222222',
                                900: '#111111',
                             
                            },
                            blue: '#276EF1'
                        }
                    },
                    boxShadow: {
                        'uber': '0 2px 12px rgba(0, 0, 0, 0.08)',
                        'uber-hover': '0 4px 16px rgba(0, 0, 0, 0.12)',
                        'uber-modal': '0 8px 24px rgba(0, 0, 0, 0.16)',
                        'uber-nav': '0 2px 8px rgba(0, 0, 0, 0.04)',
                        'uber-floating': '0 4px 20px rgba(0, 0, 0, 0.15)'
                    }
                }
            }
        }
    </script>
    <style>
        body { margin: 0; padding: 0; background-color: #F6F6F6; }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #E2E2E2; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #CBCBCB; }
        
        .uber-input:focus-within {
            border-color: #000000;
            box-shadow: inset 0 0 0 1px #000000;
        }
        
        .toggle-password {
            cursor: pointer;
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
<body class="text-uber-gray-900 font-sans antialiased bg-[#0a0a0a] flex flex-col min-h-screen">

    <!-- Global Navigation Shell (Consistent with Car Discovery) -->
    <?php
include 'staffHeader.php';
?>

    <!-- Main Content Area -->
    <main class="flex-grow flex flex-col w-full max-w-[1024px] mx-auto px-6 pt-28 pb-12 w-full max-w-[1024px] mx-auto px-6 py-12">
        
        <!-- Page Header -->
        <div class="mb-10">
            <h1 class="text-[32px] font-bold text-white tracking-tight mb-2">Account Management</h1>
            <p class="text-gray-400 text-base">Manage your personal information, security, and preferences.</p>
        </div>

        <!-- Two Column Layout -->
        <div class="flex flex-col lg:flex-row gap-12 items-start relative">
            
            <!-- Left Column: Avatar Management -->
            <div id="avatar-section" class="w-full lg:w-[280px] flex-shrink-0">
                <div class="bg-[#121212]/80 backdrop-blur-xl border border-white/10 rounded-[16px] p-8 shadow-uber border border-uber-gray-100 flex flex-col items-center text-center">
                    <div class="relative mb-6 group">
                        <div class="w-32 h-32 rounded-full overflow-hidden border-4 border-uber-white shadow-md bg-uber-gray-100 relative">
                            <img
    src="uploads/<?php echo !empty($user['profile_image']) ? $user['profile_image'] : 'default-avatar.png'; ?>" alt="Profile Picture" class="w-full h-full object-cover">
                            
                            <!-- Hover Overlay -->
                            <div class="absolute inset-0 bg-accent hover:bg-accentHover/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer">
                                <i class="fa-solid fa-camera text-uber-white text-xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <h3 class="text-lg font-semibold text-white mb-1">Profile Picture</h3>
                    <p class="text-xs text-uber-gray-500 mb-6 px-4">Supported formats: JPEG, PNG. Max size: 5MB.</p>
                    
                    <div class="flex flex-col gap-3 w-full">
                       
                        <button class="w-full text-gray-400 font-medium py-2 px-4 rounded-xl hover:bg-[#0a0a0a] transition-colors text-sm">
                            Remove Picture
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right Column: Form Fields -->
            <form method="POST" enctype="multipart/form-data" class="w-full flex-grow flex flex-col gap-8 pb-32">

                        <label
                for="profileUpload"
                class="w-full bg-accent text-white font-medium py-3 px-4 rounded-xl hover:bg-accentHover transition-colors text-sm text-center cursor-pointer block">

                Upload New

            </label>

            <input
                type="file"
                id="profileUpload"
                name="profile_image"
                accept="image/*"
                class="hidden">
                
                <!-- Personal Info Card -->
                <div class="bg-[#121212]/80 backdrop-blur-xl border border-white/10 rounded-[16px] shadow-uber border border-uber-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-uber-gray-100">
                        <h2 class="text-xl font-bold text-white">Personal Information</h2>
                    </div>
                    
                    <div class="p-6 flex flex-col gap-6">
                        <!-- Full Name -->
                        <div class="flex flex-col gap-2">
                            <label for="fullName" class="text-sm font-medium text-uber-gray-900">Full Name</label>
                            <div class="relative uber-input rounded-xl border border-white/10 bg-[#121212]/80 backdrop-blur-xl border border-white/10 transition-all">
                                <input type="text" id="fullName" name="name" value="<?php echo $user['name']; ?>" class="w-full py-3 px-4 rounded-xl bg-transparent text-white text-base placeholder-uber-gray-400 focus:outline-none" placeholder="Enter your full name">
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="flex flex-col gap-2">
                            <label for="email" class="text-sm font-medium text-uber-gray-900">Email Address</label>
                            <div class="relative uber-input rounded-xl border border-white/10 bg-[#0a0a0a] transition-all">
                                <input type="email" id="email" value="<?php echo $user['email']; ?>" disabled class="w-full py-3 px-4 rounded-xl bg-transparent text-gray-400 text-base focus:outline-none cursor-not-allowed">
                                <div class="absolute right-4 top-1/2 -translate-y-1/2">
                                    <i class="fa-solid fa-lock text-uber-gray-400 text-sm" title="Email cannot be changed directly"></i>
                                </div>
                            </div>
                            <p class="text-xs text-uber-gray-500 mt-1">To change your email, please contact support.</p>
                        </div>

                        <!-- Phone Number -->
                        <div class="flex flex-col gap-2">
                            <label for="phone" class="text-sm font-medium text-uber-gray-900">Phone Number</label>
                            <div class="flex gap-3">
                                <!-- Country Code -->
                                <div class="relative uber-input rounded-xl border border-white/10 bg-[#121212]/80 backdrop-blur-xl border border-white/10 transition-all w-[120px] flex-shrink-0">
                                    <select class="w-full h-full py-3 pl-4 pr-8 rounded-xl bg-transparent text-white text-base appearance-none focus:outline-none cursor-pointer">
                                        <option value="+1">🇺🇸 +1</option>
                                        <option value="+44">🇬🇧 +44</option>
                                        <option value="+91">🇮🇳 +91</option>
                                        <option value="+61">🇦🇺 +61</option>
                                    </select>
                                    <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                                        <i class="fa-solid fa-chevron-down text-xs text-uber-gray-500"></i>
                                    </div>
                                </div>
                                <!-- Number Input -->
                                <div class="relative flex-grow uber-input rounded-xl border border-white/10 bg-[#121212]/80 backdrop-blur-xl border border-white/10 transition-all">
                                    <input type="tel" id="phone" name="phone" value="<?php echo $user['phone']; ?>" class="w-full py-3 px-4 rounded-xl bg-transparent text-white text-base placeholder-uber-gray-400 focus:outline-none" placeholder="Enter phone number">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Password & Security Card -->
                <div class="bg-[#121212]/80 backdrop-blur-xl border border-white/10 rounded-[16px] shadow-uber border border-uber-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-uber-gray-100 flex justify-between items-center">
                        <h2 class="text-xl font-bold text-white">Password & Security</h2>
                        <span class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-1 rounded-md">Protected</span>
                    </div>
                    
                    <div class="p-6 flex flex-col gap-6">
                        <!-- Password feedback banner -->
                        <?php if ($pw_error !== ''): ?>
                        <div id="pw-error-banner" class="flex items-center gap-3 bg-red-900/40 border border-red-500/50 text-red-300 text-sm px-4 py-3 rounded-xl">
                            <i class="fa-solid fa-circle-exclamation text-red-400 flex-shrink-0"></i>
                            <span><?php echo htmlspecialchars($pw_error); ?></span>
                        </div>
                        <?php endif; ?>

                        <!-- Current Password -->
                        <div class="flex flex-col gap-2">
                            <label for="currentPassword" class="text-sm font-medium text-uber-gray-900">Current Password</label>
                            <div class="relative uber-input rounded-xl border border-white/10 bg-[#121212]/80 backdrop-blur-xl border border-white/10 transition-all">
                                <input
                                type="password"
                                id="currentPassword"
                                name="current_password" class="w-full py-3 pl-4 pr-12 rounded-xl bg-transparent text-white text-base placeholder-uber-gray-400 focus:outline-none" placeholder="Enter current password">
                                <button type="button" class="absolute right-4 top-1/2 -translate-y-1/2 text-uber-gray-500 hover:text-white toggle-password" onclick="togglePassword('currentPassword', this)">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="h-px w-full bg-uber-gray-100 my-2"></div>

                        <!-- New Password -->
                        <div class="flex flex-col gap-2">
                            <label for="newPassword" class="text-sm font-medium text-uber-gray-900">New Password</label>
                            <div class="relative uber-input rounded-xl border border-white/10 bg-[#121212]/80 backdrop-blur-xl border border-white/10 transition-all">
                                <input
                                type="password"
                                id="newPassword"
                                name="new_password" class="w-full py-3 pl-4 pr-12 rounded-xl bg-transparent text-white text-base placeholder-uber-gray-400 focus:outline-none" placeholder="Enter new password">
                                <button type="button" class="absolute right-4 top-1/2 -translate-y-1/2 text-uber-gray-500 hover:text-white toggle-password" onclick="togglePassword('newPassword', this)">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Confirm New Password -->
                        <div class="flex flex-col gap-2">
                            <label for="confirmPassword" class="text-sm font-medium text-uber-gray-900">Confirm New Password</label>
                            <div class="relative uber-input rounded-xl border border-white/10 bg-[#121212]/80 backdrop-blur-xl border border-white/10 transition-all">
                                <input type="password" id="confirmPassword" name="confirm_password" class="w-full py-3 pl-4 pr-12 rounded-xl bg-transparent text-white text-base placeholder-uber-gray-400 focus:outline-none" placeholder="Confirm new password">
                                <button type="button" class="absolute right-4 top-1/2 -translate-y-1/2 text-uber-gray-500 hover:text-white toggle-password" onclick="togglePassword('confirmPassword', this)">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Password Rules (Helper Text) -->
                        <div class="bg-[#0a0a0a] rounded-xl p-4 mt-2">
                            <p class="text-xs font-semibold text-uber-gray-700 mb-2 uppercase tracking-wide">Password Requirements:</p>
                            <ul class="text-sm text-gray-400 flex flex-col gap-1.5">
                                <li id="req-length" class="flex items-center gap-2 transition-colors duration-200">
                                    <i class="req-icon fa-solid fa-circle text-[6px] text-uber-gray-400 w-3 text-center"></i> Minimum 8 characters long
                                </li>
                                <li id="req-upper" class="flex items-center gap-2 transition-colors duration-200">
                                    <i class="req-icon fa-solid fa-circle text-[6px] text-uber-gray-400 w-3 text-center"></i> At least one uppercase letter
                                </li>
                                <li id="req-symbol" class="flex items-center gap-2 transition-colors duration-200">
                                    <i class="req-icon fa-solid fa-circle text-[6px] text-uber-gray-400 w-3 text-center"></i> At least one number or symbol
                                </li>
                                <li id="req-different" class="flex items-center gap-2 transition-colors duration-200">
                                    <i class="req-icon fa-solid fa-circle text-[6px] text-uber-gray-400 w-3 text-center"></i> Cannot be the same as current password
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
        
    </main>

    <!-- Sticky Footer Action Bar -->
    <div class="fixed bottom-0 left-0 w-full bg-[#121212]/80 backdrop-blur-xl border border-white/10 border-t border-uber-gray-200 shadow-[0_-4px_20px_rgba(0,0,0,0.05)] z-40">
        <div class="max-w-[1440px] mx-auto px-6 h-[88px] flex items-center justify-between">
            <div class="flex items-center gap-2 text-sm text-uber-gray-500 hidden sm:flex">
                <i class="fa-regular fa-clock"></i>
                <span>Last updated: Oct 24, 2023 at 14:32 PST</span>
            </div>
            
            <div class="flex items-center gap-4 w-full sm:w-auto justify-end">
                <button class="px-6 py-3.5 rounded-xl font-medium text-black bg-uber-gray-100 hover:bg-uber-gray-200 transition-colors text-base flex-grow sm:flex-grow-0 text-center">
                    Cancel
                </button>
              <button
                type="submit"
                class="px-8 py-3.5 rounded-xl font-medium text-uber-white bg-accent hover:bg-accentHover hover:bg-uber-gray-800 shadow-md transition-all text-base flex-grow sm:flex-grow-0 text-center flex items-center justify-center gap-2">

                Save Changes

            </button>
            </div>
        </div>
    </div>
    </form>

    <!-- Inline Toast Notification (Hidden by default, structure provided) -->
    <div class="fixed top-24 left-1/2 -translate-x-1/2 z-50 transition-all transform duration-300 opacity-0 pointer-events-none translate-y-[-20px]" id="toast-success">
        <div class="bg-accent hover:bg-accentHover text-uber-white px-6 py-4 rounded-xl shadow-uber-floating flex items-center gap-3">
            <div class="w-6 h-6 rounded-full bg-green-500 flex items-center justify-center flex-shrink-0">
                <i class="fa-solid fa-check text-uber-white text-xs"></i>
            </div>
            <p class="text-sm font-medium">Profile updated successfully.</p>
        </div>
    </div>

    <script>
        /* ── PASSWORD TOGGLE ── */
        function togglePassword(inputId, btnElement) {
            const input = document.getElementById(inputId);
            const icon = btnElement.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        /* ── PASSWORD REQUIREMENT CHECKER ── */
        function setReq(id, passed) {
            const li   = document.getElementById(id);
            const icon = li.querySelector('.req-icon');
            if (passed) {
                li.classList.add('text-green-400');
                li.classList.remove('text-gray-400');
                icon.className = 'req-icon fa-solid fa-check text-green-500 text-xs w-3';
            } else {
                li.classList.remove('text-green-400');
                li.classList.add('text-gray-400');
                icon.className = 'req-icon fa-solid fa-circle text-[6px] text-uber-gray-400 w-3 text-center';
            }
        }

        function checkPasswordStrength() {
            const newPw  = document.getElementById('newPassword').value;
            const curPw  = document.getElementById('currentPassword').value;

            setReq('req-length',    newPw.length >= 8);
            setReq('req-upper',     /[A-Z]/.test(newPw));
            setReq('req-symbol',    /[0-9!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?`~]/.test(newPw));
            setReq('req-different', newPw.length > 0 && newPw !== curPw);
        }

        /* attach live listeners */
        document.getElementById('newPassword').addEventListener('input', checkPasswordStrength);
        document.getElementById('currentPassword').addEventListener('input', checkPasswordStrength);

        /* ── FORM SUBMIT VALIDATION ── */
        document.querySelector('form').addEventListener('submit', function(e) {
            const newPw     = document.getElementById('newPassword').value;
            const confirmPw = document.getElementById('confirmPassword').value;
            const curPw     = document.getElementById('currentPassword').value;

            /* only validate when user is trying to change password */
            if (newPw === '' && confirmPw === '' && curPw === '') return;

            const errors = [];
            if (newPw.length < 8)                          errors.push('Password must be at least 8 characters.');
            if (!/[A-Z]/.test(newPw))                      errors.push('Password must contain at least one uppercase letter.');
            if (!/[0-9!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?`~]/.test(newPw))
                                                           errors.push('Password must contain at least one number or symbol.');
            if (newPw === curPw && curPw !== '')           errors.push('New password cannot be the same as the current password.');
            if (newPw !== confirmPw)                       errors.push('New password and confirmation do not match.');
            if (curPw === '')                              errors.push('Please enter your current password to change it.');

            if (errors.length > 0) {
                e.preventDefault();
                alert(errors.join('\n'));
            }
        });

        /* ── SCROLL HEADER ── */
        window.addEventListener('scroll', function() {
            const header = document.getElementById('header');
            if (!header) return;
            if (window.scrollY > 50) {
                header.classList.add('header-scrolled');
            } else {
                header.classList.remove('header-scrolled');
            }
        });

        /* ── TOAST ON LOAD ── */
        <?php if (isset($_GET['updated'])): ?>
        (function() {
            const isPwUpdate = <?php echo $_GET['updated'] === 'pw_updated' ? 'true' : 'false'; ?>;
            const msgEl = document.querySelector('#toast-success p');
            if (isPwUpdate) msgEl.textContent = 'Password changed successfully.';
            const toast = document.getElementById('toast-success');
            toast.classList.remove('opacity-0', 'pointer-events-none', 'translate-y-[-20px]');
            toast.classList.add('opacity-100', 'translate-y-0');
            setTimeout(() => {
                toast.classList.add('opacity-0', 'translate-y-[-20px]');
                toast.classList.remove('opacity-100', 'translate-y-0');
            }, 3500);
        })();
        <?php endif; ?>
    </script>
</body>
</html>