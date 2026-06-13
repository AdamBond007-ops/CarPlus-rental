<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'dbConnect.php';

// ============================================================
//  AJAX HANDLERS
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    // ── ADD STAFF ──────────────────────────────────────────
    if ($_POST['action'] === 'add_staff') {
        $name      = trim($_POST['name']      ?? '');
        $email     = trim($_POST['email']     ?? '');
        $phone     = trim($_POST['phone']     ?? '');
        $password  = trim($_POST['password']  ?? '');
        $branch_id = (int)($_POST['branch_id'] ?? 0);
        $position  = trim($_POST['position']  ?? 'Staff');
        $hire_date = trim($_POST['hire_date'] ?? date('Y-m-d'));

        if (!$name || !$email || !$password || !$branch_id) {
            echo json_encode(['success' => false, 'error' => 'Required fields missing.']);
            exit;
        }

        // Check duplicate email
        $check = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            echo json_encode(['success' => false, 'error' => 'Email already in use.']);
            exit;
        }

        $hashed = password_hash($password, PASSWORD_BCRYPT);

        // Insert into users
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, 'staff')");
        $stmt->bind_param("ssss", $name, $email, $hashed, $phone);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'error' => 'Failed to create user: ' . $conn->error]);
            exit;
        }
        $user_id = $conn->insert_id;

        // Insert into staff
        $stmt2 = $conn->prepare("INSERT INTO staff (user_id, branch_id, position, hire_date) VALUES (?, ?, ?, ?)");
        $stmt2->bind_param("iiss", $user_id, $branch_id, $position, $hire_date);
        if (!$stmt2->execute()) {
            // Rollback user insert
            $conn->query("DELETE FROM users WHERE user_id = $user_id");
            echo json_encode(['success' => false, 'error' => 'Failed to create staff record: ' . $conn->error]);
            exit;
        }

        echo json_encode(['success' => true, 'user_id' => $user_id]);
        exit;
    }

    // ── EDIT STAFF ─────────────────────────────────────────
    if ($_POST['action'] === 'edit_staff') {
        $staff_id  = (int)($_POST['staff_id']  ?? 0);
        $user_id   = (int)($_POST['user_id']   ?? 0);
        $name      = trim($_POST['name']       ?? '');
        $email     = trim($_POST['email']      ?? '');
        $phone     = trim($_POST['phone']      ?? '');
        $branch_id = (int)($_POST['branch_id'] ?? 0);
        $position  = trim($_POST['position']   ?? 'Staff');
        $hire_date = trim($_POST['hire_date']  ?? '');
        $password  = trim($_POST['password']   ?? '');

        // Check email uniqueness (exclude current user)
        $check = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $check->bind_param("si", $email, $user_id);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            echo json_encode(['success' => false, 'error' => 'Email already in use by another account.']);
            exit;
        }

        // Update users table
        if ($password) {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE users SET name=?, email=?, phone=?, password=? WHERE user_id=?");
            $stmt->bind_param("ssssi", $name, $email, $phone, $hashed, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET name=?, email=?, phone=? WHERE user_id=?");
            $stmt->bind_param("sssi", $name, $email, $phone, $user_id);
        }
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'error' => 'Failed to update user: ' . $conn->error]);
            exit;
        }

        // Update staff table
        $stmt2 = $conn->prepare("UPDATE staff SET branch_id=?, position=?, hire_date=? WHERE staff_id=?");
        $stmt2->bind_param("issi", $branch_id, $position, $hire_date, $staff_id);
        if (!$stmt2->execute()) {
            echo json_encode(['success' => false, 'error' => 'Failed to update staff: ' . $conn->error]);
            exit;
        }

        echo json_encode(['success' => true]);
        exit;
    }

    // ── DELETE STAFF ───────────────────────────────────────
    if ($_POST['action'] === 'delete_staff') {
        $staff_id = (int)($_POST['staff_id'] ?? 0);
        $user_id  = (int)($_POST['user_id']  ?? 0);

        // Delete staff first (FK), then user
        $stmt = $conn->prepare("DELETE FROM staff WHERE staff_id = ?");
        $stmt->bind_param("i", $staff_id);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'error' => $conn->error]);
            exit;
        }

        $stmt2 = $conn->prepare("UPDATE users SET role = 'customer' WHERE user_id = ?");
        $stmt2->bind_param("i", $user_id);
        $stmt2->execute();

        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Unknown action.']);
    exit;
}

// ============================================================
//  PAGE DATA
// ============================================================
$staff_list = $conn->query("
    SELECT
        s.staff_id, s.user_id, s.branch_id, s.position, s.hire_date, s.created_at,
        u.name, u.email, u.phone, u.profile_image,
        b.branch_name, b.location AS branch_location
    FROM staff s
    JOIN users u ON s.user_id = u.user_id
    JOIN branches b ON s.branch_id = b.branch_id
    ORDER BY s.staff_id DESC
")->fetch_all(MYSQLI_ASSOC);

$branches = $conn->query("SELECT * FROM branches ORDER BY branch_name")->fetch_all(MYSQLI_ASSOC);

$total_staff    = count($staff_list);
$branch_counts  = [];
foreach ($staff_list as $s) {
    $branch_counts[$s['branch_name']] = ($branch_counts[$s['branch_name']] ?? 0) + 1;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management – CarPlus Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root { --accent: #00FF66; }
        body  { background: #0a0a0a; }

        .glass-panel {
            background: rgba(18,18,18,0.75);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255,255,255,0.08);
            box-shadow: 0 8px 32px rgba(0,0,0,0.35);
        }

        .card-dark {
            background: #111;
            border: 1px solid rgba(255,255,255,0.07);
        }

        .table-row {
            border-bottom: 1px solid rgba(255,255,255,0.05);
            transition: background .15s;
        }
        .table-row:hover { background: rgba(255,255,255,0.03); }
        .table-row:last-child { border-bottom: none; }

        /* Modal */
        .modal-backdrop { background: rgba(0,0,0,0.75); backdrop-filter: blur(6px); }
        .modal-box {
            background: #141414;
            border: 1px solid rgba(255,255,255,0.1);
        }

        /* Inputs */
        .field {
            background: #1a1a1a;
            border: 1px solid rgba(255,255,255,0.1);
            color: #fff;
            transition: border-color .2s, box-shadow .2s;
        }
        .field:focus {
            outline: none;
            border-color: #00FF66;
            box-shadow: 0 0 0 3px rgba(0,255,102,0.12);
        }
        .field::placeholder { color: #4b5563; }
        select.field option { background: #1a1a1a; }

        /* Badge */
        .badge-branch {
            background: rgba(0,255,102,0.1);
            color: #00FF66;
            border: 1px solid rgba(0,255,102,0.2);
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: #111; }
        ::-webkit-scrollbar-thumb { background: #333; border-radius: 3px; }

        /* Password toggle */
        .pw-wrap { position: relative; }
        .pw-eye  {
            position: absolute; right: 12px; top: 50%;
            transform: translateY(-50%);
            color: #6b7280; cursor: pointer;
        }
        .pw-eye:hover { color: #fff; }
    </style>
</head>
<body class="min-h-screen text-white">

<?php include 'adminHeader.php'; ?>

<main class="pt-24 pb-16 px-6 lg:px-12 max-w-7xl mx-auto">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-white">Staff Management</h1>
            <p class="text-gray-400 text-sm mt-1">Manage staff accounts and branch assignments</p>
        </div>
        <button onclick="openAddModal()"
            class="inline-flex items-center gap-2 bg-[#00FF66] hover:bg-[#00e05a]
                   text-black font-semibold px-5 py-2.5 rounded-xl transition-colors text-sm">
            <i class="fa-solid fa-user-plus"></i>
            Add Staff
        </button>
    </div>

    <!-- Stat Cards -->
    <?php $grid_cols = min(1 + count($branches), 4); ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-<?php echo $grid_cols; ?> gap-4 mb-8">
        <div class="card-dark rounded-2xl p-5 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-white/10 flex items-center justify-center shrink-0">
                <i class="fa-solid fa-users text-white"></i>
            </div>
            <div>
                <p class="text-2xl font-bold"><?php echo $total_staff; ?></p>
                <p class="text-gray-400 text-sm">Total Staff</p>
            </div>
        </div>
        <?php foreach ($branches as $br): ?>
        <?php $count = $branch_counts[$br['branch_name']] ?? 0; ?>
        <div class="card-dark rounded-2xl p-5 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-[#00FF66]/10 flex items-center justify-center shrink-0">
                <i class="fa-solid fa-building text-[#00FF66]"></i>
            </div>
            <div>
                <p class="text-2xl font-bold"><?php echo $count; ?></p>
                <p class="text-gray-400 text-sm"><?php echo htmlspecialchars($br['branch_name']); ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Staff Table -->
    <div class="card-dark rounded-2xl overflow-hidden">

        <!-- Table Header -->
        <div class="px-6 py-4 border-b border-white/5 flex items-center justify-between">
            <h2 class="font-semibold text-white">All Staff</h2>
            <span class="text-xs text-gray-500"><?php echo $total_staff; ?> member<?php echo $total_staff != 1 ? 's' : ''; ?></span>
        </div>

        <!-- Scrollable Table -->
        <div class="overflow-x-auto">
            <table class="w-full min-w-[700px]">
                <thead>
                    <tr class="border-b border-white/5">
                        <th class="text-left text-xs text-gray-500 font-medium px-6 py-3 uppercase tracking-wider">Staff</th>
                        <th class="text-left text-xs text-gray-500 font-medium px-4 py-3 uppercase tracking-wider">Staff ID</th>
                        <th class="text-left text-xs text-gray-500 font-medium px-4 py-3 uppercase tracking-wider">Branch</th>
                        <th class="text-left text-xs text-gray-500 font-medium px-4 py-3 uppercase tracking-wider">Position</th>
                        <th class="text-left text-xs text-gray-500 font-medium px-4 py-3 uppercase tracking-wider">Hire Date</th>
                        <th class="text-left text-xs text-gray-500 font-medium px-4 py-3 uppercase tracking-wider">Contact</th>
                        <th class="text-right text-xs text-gray-500 font-medium px-6 py-3 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="staffTableBody">
                    <?php if (empty($staff_list)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-gray-500 py-16 text-sm">
                            <i class="fa-solid fa-users-slash text-3xl mb-3 block text-gray-700"></i>
                            No staff members found. Add one to get started.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($staff_list as $s): ?>
                    <tr class="table-row" id="row-<?php echo $s['staff_id']; ?>">
                        <!-- Staff info -->
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full overflow-hidden bg-[#1e1e1e] shrink-0">
                                    <img src="uploads/<?php echo htmlspecialchars(!empty($s['profile_image']) ? $s['profile_image'] : 'default-avatar.png'); ?>"
                                         class="w-full h-full object-cover"
                                         onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($s['name']); ?>&background=1e1e1e&color=00FF66&size=36'">
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-white"><?php echo htmlspecialchars($s['name']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($s['email']); ?></p>
                                </div>
                            </div>
                        </td>
                        <!-- Staff ID -->
                        <td class="px-4 py-4">
                            <span class="text-xs font-mono text-gray-400">#<?php echo $s['staff_id']; ?></span>
                        </td>
                        <!-- Branch -->
                        <td class="px-4 py-4">
                            <span class="badge-branch text-xs px-2.5 py-1 rounded-full font-medium">
                                <?php echo htmlspecialchars($s['branch_name']); ?>
                            </span>
                        </td>
                        <!-- Position -->
                        <td class="px-4 py-4 text-sm text-gray-300">
                            <?php echo htmlspecialchars($s['position']); ?>
                        </td>
                        <!-- Hire Date -->
                        <td class="px-4 py-4 text-sm text-gray-400">
                            <?php echo $s['hire_date'] ? date('d M Y', strtotime($s['hire_date'])) : '—'; ?>
                        </td>
                        <!-- Contact -->
                        <td class="px-4 py-4 text-sm text-gray-400">
                            <?php echo htmlspecialchars($s['phone'] ?? '—'); ?>
                        </td>
                        <!-- Actions -->
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <button
                                    onclick='openEditModal(<?php echo htmlspecialchars(json_encode($s), ENT_QUOTES); ?>)'
                                    class="flex items-center gap-1.5 bg-white/5 hover:bg-white/10
                                           border border-white/10 text-white text-xs font-medium
                                           px-3 py-1.5 rounded-lg transition-colors">
                                    <i class="fa-regular fa-pen-to-square text-[10px]"></i>
                                    Edit
                                </button>
                                <button
                                    onclick="confirmDelete(<?php echo $s['staff_id']; ?>, <?php echo $s['user_id']; ?>, '<?php echo htmlspecialchars(addslashes($s['name'])); ?>')"
                                    class="flex items-center gap-1.5 bg-red-500/10 hover:bg-red-500/20
                                           border border-red-500/20 text-red-400 text-xs font-medium
                                           px-3 py-1.5 rounded-lg transition-colors">
                                    <i class="fa-regular fa-trash-can text-[10px]"></i>
                                    Remove
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- ============================================================
     ADD / EDIT MODAL
     ============================================================ -->
<div id="staffModal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4">
    <div class="modal-backdrop absolute inset-0" onclick="closeModal()"></div>
    <div class="modal-box relative w-full max-w-xl rounded-2xl p-6 z-10 max-h-[90vh] overflow-y-auto">

        <!-- Modal Header -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-[#00FF66]/15 flex items-center justify-center">
                    <i id="modalIcon" class="fa-solid fa-user-plus text-[#00FF66] text-sm"></i>
                </div>
                <h2 id="modalTitle" class="text-lg font-bold text-white">Add Staff</h2>
            </div>
            <button onclick="closeModal()" class="text-gray-500 hover:text-white transition-colors">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>

        <form id="staffForm" onsubmit="submitForm(event)" autocomplete="off">
            <input type="hidden" id="fStaffId" name="staff_id" value="0">
            <input type="hidden" id="fUserId"  name="user_id"  value="0">
            <input type="hidden" id="fAction"  name="action"   value="add_staff">

            <!-- Section: Account Info -->
            <p class="text-xs text-[#00FF66] font-semibold uppercase tracking-widest mb-3">Account Information</p>
            <div class="space-y-3 mb-5">

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-gray-400 mb-1.5">Full Name <span class="text-red-400">*</span></label>
                        <input type="text" id="fName" name="name" required
                            class="field w-full rounded-xl px-4 py-2.5 text-sm"
                            placeholder="e.g. Ahmad Faris">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 mb-1.5">Phone Number</label>
                        <input type="tel" id="fPhone" name="phone"
                            class="field w-full rounded-xl px-4 py-2.5 text-sm"
                            placeholder="e.g. 0123456789">
                    </div>
                </div>

                <div>
                    <label class="block text-xs text-gray-400 mb-1.5">Email Address <span class="text-red-400">*</span></label>
                    <input type="email" id="fEmail" name="email" required
                        class="field w-full rounded-xl px-4 py-2.5 text-sm"
                        placeholder="staff@carplus.com">
                </div>

                <div>
                    <label class="block text-xs text-gray-400 mb-1.5">
                        Password <span class="text-red-400" id="pwRequired">*</span>
                        <span id="pwHint" class="text-gray-600 normal-case tracking-normal font-normal hidden"> — leave blank to keep current</span>
                    </label>
                    <div class="pw-wrap">
                        <input type="password" id="fPassword" name="password"
                            class="field w-full rounded-xl px-4 pr-10 py-2.5 text-sm"
                            placeholder="Min. 8 characters">
                        <i class="pw-eye fa-regular fa-eye" onclick="togglePw()"></i>
                    </div>
                </div>

            </div>

            <!-- Section: Staff Details -->
            <p class="text-xs text-[#00FF66] font-semibold uppercase tracking-widest mb-3">Staff Details</p>
            <div class="space-y-3">

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-gray-400 mb-1.5">Branch <span class="text-red-400">*</span></label>
                        <select id="fBranch" name="branch_id" required class="field w-full rounded-xl px-4 py-2.5 text-sm">
                            <option value="">— Select Branch —</option>
                            <?php foreach ($branches as $b): ?>
                            <option value="<?php echo $b['branch_id']; ?>">
                                <?php echo htmlspecialchars($b['branch_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 mb-1.5">Position</label>
                        <input type="text" id="fPosition" name="position" value="Staff"
                            class="field w-full rounded-xl px-4 py-2.5 text-sm"
                            placeholder="e.g. Staff, Manager">
                    </div>
                </div>

                <div>
                    <label class="block text-xs text-gray-400 mb-1.5">Hire Date</label>
                    <input type="date" id="fHireDate" name="hire_date"
                        class="field w-full rounded-xl px-4 py-2.5 text-sm"
                        value="<?php echo date('Y-m-d'); ?>">
                </div>

            </div>

            <!-- Error message -->
            <div id="formError" class="hidden mt-4 flex items-center gap-2 bg-red-500/10 border border-red-500/20 text-red-400 text-sm px-4 py-3 rounded-xl">
                <i class="fa-solid fa-circle-exclamation shrink-0"></i>
                <span id="formErrorMsg"></span>
            </div>

            <!-- Buttons -->
            <div class="flex gap-3 mt-6">
                <button type="button" onclick="closeModal()"
                    class="flex-1 bg-white/5 hover:bg-white/10 border border-white/10
                           text-white text-sm font-medium py-2.5 rounded-xl transition-colors">
                    Cancel
                </button>
                <button type="submit" id="submitBtn"
                    class="flex-1 bg-[#00FF66] hover:bg-[#00e05a] text-black font-semibold
                           text-sm py-2.5 rounded-xl transition-colors flex items-center justify-center gap-2">
                    <i class="fa-solid fa-user-plus text-xs"></i>
                    <span id="submitLabel">Add Staff</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================
     DELETE CONFIRM MODAL
     ============================================================ -->
<div id="deleteModal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4">
    <div class="modal-backdrop absolute inset-0" onclick="closeDeleteModal()"></div>
    <div class="modal-box relative w-full max-w-sm rounded-2xl p-6 z-10 text-center">
        <div class="w-14 h-14 bg-red-500/15 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fa-solid fa-user-minus text-red-400 text-xl"></i>
        </div>
        <h3 class="text-white font-bold text-lg mb-2">Remove Staff Member?</h3>
        <p class="text-gray-400 text-sm mb-1">
            You are about to remove <span id="delName" class="text-white font-semibold"></span>.
        </p>
        <p class="text-gray-600 text-xs mb-6">Their user account will be kept but their staff role will be revoked.</p>
        <input type="hidden" id="delStaffId">
        <input type="hidden" id="delUserId">
        <div class="flex gap-3">
            <button onclick="closeDeleteModal()"
                class="flex-1 bg-white/5 hover:bg-white/10 border border-white/10
                       text-white text-sm font-medium py-2.5 rounded-xl transition-colors">
                Cancel
            </button>
            <button onclick="executeDelete()"
                class="flex-1 bg-red-500 hover:bg-red-600 text-white font-semibold
                       text-sm py-2.5 rounded-xl transition-colors">
                Remove
            </button>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="toast"
    class="fixed bottom-6 right-6 z-[200] hidden items-center gap-3
           bg-[#1a1a1a] border border-white/10 text-white text-sm
           px-4 py-3 rounded-xl shadow-2xl min-w-[220px]">
    <i id="toastIcon" class="fa-solid fa-circle-check text-[#00FF66] shrink-0"></i>
    <span id="toastMsg"></span>
</div>

<script>
// ============================================================
//  MODAL — ADD
// ============================================================
function openAddModal() {
    document.getElementById('modalTitle').textContent  = 'Add Staff Member';
    document.getElementById('modalIcon').className     = 'fa-solid fa-user-plus text-[#00FF66] text-sm';
    document.getElementById('submitLabel').textContent = 'Add Staff';
    document.getElementById('fAction').value           = 'add_staff';
    document.getElementById('fStaffId').value          = '0';
    document.getElementById('fUserId').value           = '0';
    document.getElementById('pwRequired').classList.remove('hidden');
    document.getElementById('pwHint').classList.add('hidden');
    document.getElementById('fPassword').required = true;
    document.getElementById('staffForm').reset();
    document.getElementById('fHireDate').value = new Date().toISOString().split('T')[0];
    document.getElementById('fPosition').value = 'Staff';
    hideError();
    showModal('staffModal');
}

// ============================================================
//  MODAL — EDIT
// ============================================================
function openEditModal(s) {
    document.getElementById('modalTitle').textContent  = 'Edit Staff Member';
    document.getElementById('modalIcon').className     = 'fa-solid fa-user-pen text-[#00FF66] text-sm';
    document.getElementById('submitLabel').textContent = 'Save Changes';
    document.getElementById('fAction').value           = 'edit_staff';
    document.getElementById('fStaffId').value          = s.staff_id;
    document.getElementById('fUserId').value           = s.user_id;
    document.getElementById('fName').value             = s.name        || '';
    document.getElementById('fEmail').value            = s.email       || '';
    document.getElementById('fPhone').value            = s.phone       || '';
    document.getElementById('fBranch').value           = s.branch_id   || '';
    document.getElementById('fPosition').value         = s.position    || 'Staff';
    document.getElementById('fHireDate').value         = s.hire_date   || '';
    document.getElementById('fPassword').value         = '';
    document.getElementById('fPassword').required      = false;
    document.getElementById('pwRequired').classList.add('hidden');
    document.getElementById('pwHint').classList.remove('hidden');
    hideError();
    showModal('staffModal');
}

function closeModal() {
    hideModal('staffModal');
}

// ============================================================
//  FORM SUBMIT
// ============================================================
function submitForm(e) {
    e.preventDefault();
    hideError();

    const btn   = document.getElementById('submitBtn');
    const label = document.getElementById('submitLabel');
    btn.disabled = true;
    label.textContent = 'Saving…';

    const data = new FormData(document.getElementById('staffForm'));

    fetch('staffAdmin.php', { method: 'POST', body: data })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            showToast('Staff saved successfully!', 'success');
            closeModal();
            setTimeout(() => location.reload(), 700);
        } else {
            showError(res.error || 'Something went wrong.');
            btn.disabled = false;
            label.textContent = document.getElementById('fAction').value === 'add_staff' ? 'Add Staff' : 'Save Changes';
        }
    })
    .catch(() => {
        showError('Network error. Please try again.');
        btn.disabled = false;
    });
}

// ============================================================
//  DELETE
// ============================================================
function confirmDelete(staffId, userId, name) {
    document.getElementById('delStaffId').value  = staffId;
    document.getElementById('delUserId').value   = userId;
    document.getElementById('delName').textContent = name;
    showModal('deleteModal');
}

function closeDeleteModal() { hideModal('deleteModal'); }

function executeDelete() {
    const staffId = document.getElementById('delStaffId').value;
    const userId  = document.getElementById('delUserId').value;

    fetch('staffAdmin.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=delete_staff&staff_id=${staffId}&user_id=${userId}`
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            showToast('Staff member removed.', 'success');
            closeDeleteModal();
            const row = document.getElementById('row-' + staffId);
            if (row) {
                row.style.opacity = '0';
                row.style.transition = 'opacity .3s';
                setTimeout(() => row.remove(), 300);
            }
        } else {
            showToast('Error: ' + (res.error || 'Failed'), 'error');
        }
    })
    .catch(() => showToast('Network error.', 'error'));
}

// ============================================================
//  PASSWORD TOGGLE
// ============================================================
function togglePw() {
    const input = document.getElementById('fPassword');
    const icon  = document.querySelector('.pw-eye');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

// ============================================================
//  HELPERS
// ============================================================
function showModal(id) {
    const el = document.getElementById(id);
    el.classList.remove('hidden');
    el.classList.add('flex');
}
function hideModal(id) {
    const el = document.getElementById(id);
    el.classList.add('hidden');
    el.classList.remove('flex');
}
function showError(msg) {
    document.getElementById('formError').classList.remove('hidden');
    document.getElementById('formErrorMsg').textContent = msg;
}
function hideError() {
    document.getElementById('formError').classList.add('hidden');
}

let toastTimer;
function showToast(msg, type = 'success') {
    const toast = document.getElementById('toast');
    const icon  = document.getElementById('toastIcon');
    document.getElementById('toastMsg').textContent = msg;

    icon.className = 'fa-solid shrink-0 ';
    if (type === 'success') icon.className += 'fa-circle-check text-[#00FF66]';
    else if (type === 'error') icon.className += 'fa-circle-xmark text-red-400';
    else icon.className += 'fa-triangle-exclamation text-yellow-400';

    toast.classList.remove('hidden');
    toast.classList.add('flex');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
        toast.classList.add('hidden');
        toast.classList.remove('flex');
    }, 3500);
}
</script>
</body>
</html>