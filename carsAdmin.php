<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'dbConnect.php';

// ─── CRUD Operations ──────────────────────────────────────────────────────────

$message = '';
$messageType = '';

// ADD Car
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'add') {
        $name         = $conn->real_escape_string(trim($_POST['name']));
        $car_type     = $conn->real_escape_string(trim($_POST['car_type']));
        $price        = floatval($_POST['price_per_day']);
        $description  = $conn->real_escape_string(trim($_POST['description']));
        $image_url    = $conn->real_escape_string(trim($_POST['image_url']));
        $availability = isset($_POST['availability']) ? 1 : 0;
        $branch_id    = intval($_POST['branch_id']);

        $sql = "INSERT INTO cars (name, car_type, price_per_day, description, image_url, availability, branch_id)
                VALUES ('$name', '$car_type', $price, '$description', '$image_url', $availability, $branch_id)";

        if ($conn->query($sql)) {
            $message = "Car added successfully.";
            $messageType = "success";
        } else {
            $message = "Error adding car: " . $conn->error;
            $messageType = "error";
        }
    }

    // EDIT Car
    if ($_POST['action'] === 'edit') {
        $car_id       = intval($_POST['car_id']);
        $name         = $conn->real_escape_string(trim($_POST['name']));
        $car_type     = $conn->real_escape_string(trim($_POST['car_type']));
        $price        = floatval($_POST['price_per_day']);
        $description  = $conn->real_escape_string(trim($_POST['description']));
        $image_url    = $conn->real_escape_string(trim($_POST['image_url']));
        $availability = isset($_POST['availability']) ? 1 : 0;
        $branch_id    = intval($_POST['branch_id']);

        $sql = "UPDATE cars SET
                    name='$name',
                    car_type='$car_type',
                    price_per_day=$price,
                    description='$description',
                    image_url='$image_url',
                    availability=$availability,
                    branch_id=$branch_id
                WHERE car_id=$car_id";

        if ($conn->query($sql)) {
            $message = "Car updated successfully.";
            $messageType = "success";
        } else {
            $message = "Error updating car: " . $conn->error;
            $messageType = "error";
        }
    }
}

// DELETE Car
if (isset($_GET['delete'])) {
    $car_id = intval($_GET['delete']);
    $sql = "DELETE FROM cars WHERE car_id=$car_id";
    if ($conn->query($sql)) {
        $message = "Car deleted successfully.";
        $messageType = "success";
    } else {
        $message = "Error deleting car: " . $conn->error;
        $messageType = "error";
    }
}

// ─── Fetch branches for dropdowns ────────────────────────────────────────────
$branches = $conn->query("SELECT * FROM branches ORDER BY branch_name")->fetch_all(MYSQLI_ASSOC);

// ─── Search + Filter ─────────────────────────────────────────────────────────
$search      = $conn->real_escape_string(trim($_GET['search'] ?? ''));
$filterType  = $conn->real_escape_string(trim($_GET['type'] ?? ''));
$filterAvail = $_GET['availability'] ?? '';

$where = "WHERE 1=1";
if ($search)      $where .= " AND (c.name LIKE '%$search%' OR c.description LIKE '%$search%')";
if ($filterType)  $where .= " AND c.car_type = '$filterType'";
if ($filterAvail !== '') $where .= " AND c.availability = " . intval($filterAvail);

$cars = $conn->query(
    "SELECT c.*, b.branch_name, b.location
     FROM cars c
     LEFT JOIN branches b ON c.branch_id = b.branch_id
     $where
     ORDER BY c.car_id DESC"
)->fetch_all(MYSQLI_ASSOC);

// Distinct car types for filter dropdown
$types = $conn->query("SELECT DISTINCT car_type FROM cars ORDER BY car_type")->fetch_all(MYSQLI_ASSOC);

// ─── Fetch single car for edit modal ─────────────────────────────────────────
$editCar = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $res = $conn->query("SELECT * FROM cars WHERE car_id=$edit_id");
    if ($res) $editCar = $res->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cars Management — CarPlus Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --accent: #00FF66;
            --accent-dim: rgba(0,255,102,0.15);
            --accent-glow: 0 0 18px rgba(0,255,102,0.35);
            --glass: rgba(255,255,255,0.04);
            --glass-border: rgba(255,255,255,0.08);
            --card-bg: #111111;
            --surface: #0d0d0d;
        }

        * { box-sizing: border-box; }

        body {
            background-color: #080808;
            color: #e5e5e5;
            font-family: 'Inter', system-ui, sans-serif;
            min-height: 100vh;
        }

        /* Subtle grid texture */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.015) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.015) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
            z-index: 0;
        }

        .glass-panel {
            background: rgba(255,255,255,0.03);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
        }

        /* Car card */
        .car-card {
            background: var(--card-bg);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 16px;
            overflow: hidden;
            transition: transform 0.2s, border-color 0.2s, box-shadow 0.2s;
        }
        .car-card:hover {
            transform: translateY(-3px);
            border-color: rgba(0,255,102,0.25);
            box-shadow: 0 8px 32px rgba(0,0,0,0.5), var(--accent-glow);
        }

        .car-card-img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            display: block;
            background: #1a1a1a;
        }

        /* Accent badge */
        .badge-available {
            background: rgba(0,255,102,0.15);
            color: #00FF66;
            border: 1px solid rgba(0,255,102,0.3);
        }
        .badge-unavailable {
            background: rgba(255,80,80,0.12);
            color: #ff5050;
            border: 1px solid rgba(255,80,80,0.25);
        }

        /* Accent green */
        .text-accent   { color: var(--accent); }
        .bg-accent     { background-color: var(--accent); }
        .border-accent { border-color: var(--accent); }

        /* Inputs */
        .form-input {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: #e5e5e5;
            border-radius: 8px;
            padding: 10px 14px;
            width: 100%;
            transition: border-color 0.2s, box-shadow 0.2s;
            font-size: 14px;
        }
        .form-input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(0,255,102,0.1);
        }
        .form-input option { background: #1a1a1a; }

        /* Buttons */
        .btn-accent {
            background: var(--accent);
            color: #000;
            font-weight: 600;
            border-radius: 8px;
            padding: 10px 20px;
            cursor: pointer;
            transition: opacity 0.2s, box-shadow 0.2s;
            font-size: 14px;
        }
        .btn-accent:hover { opacity: 0.88; box-shadow: var(--accent-glow); }

        .btn-ghost {
            background: rgba(255,255,255,0.06);
            color: #ccc;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            padding: 10px 20px;
            cursor: pointer;
            transition: background 0.2s;
            font-size: 14px;
        }
        .btn-ghost:hover { background: rgba(255,255,255,0.1); }

        .btn-danger {
            background: rgba(255,80,80,0.12);
            color: #ff5050;
            border: 1px solid rgba(255,80,80,0.25);
            border-radius: 8px;
            padding: 7px 14px;
            cursor: pointer;
            transition: background 0.2s;
            font-size: 13px;
        }
        .btn-danger:hover { background: rgba(255,80,80,0.22); }

        .btn-edit {
            background: rgba(100,160,255,0.1);
            color: #64a0ff;
            border: 1px solid rgba(100,160,255,0.25);
            border-radius: 8px;
            padding: 7px 14px;
            cursor: pointer;
            transition: background 0.2s;
            font-size: 13px;
        }
        .btn-edit:hover { background: rgba(100,160,255,0.2); }

        /* Modal */
        .modal-overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.75);
            backdrop-filter: blur(6px);
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .modal-box {
            background: #111;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 32px;
            width: 100%;
            max-width: 560px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
        }
        .modal-box::-webkit-scrollbar { width: 4px; }
        .modal-box::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 4px; }

        /* Toast */
        #toast {
            position: fixed;
            bottom: 28px; right: 28px;
            z-index: 200;
            min-width: 260px;
            border-radius: 12px;
            padding: 14px 20px;
            font-size: 14px;
            font-weight: 500;
            transition: opacity 0.4s;
        }

        /* Scrollbar global */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 6px; }

        /* Rating star row */
        .star-accent { color: var(--accent); font-size: 11px; }

        /* placeholder for missing images */
        .img-fallback { background: #1c1c1c; display: flex; align-items: center; justify-content: center; }
    </style>
</head>
<body class="relative">

<?php include 'adminHeader.php'; ?>

<!-- ─── Page wrapper ──────────────────────────────────────────────────────── -->
<main class="relative z-10 pt-28 pb-16 px-6 lg:px-12 max-w-7xl mx-auto">

    <!-- Page title + Add button -->
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8">
        <div>
            <p class="text-xs font-semibold tracking-widest text-accent uppercase mb-1">Fleet Management</p>
            <h1 class="text-3xl font-bold text-white tracking-tight">Cars</h1>
        </div>
        <button onclick="openAddModal()" class="btn-accent flex items-center gap-2 self-start sm:self-auto">
            <i class="fa-solid fa-plus text-xs"></i>
            Add New Car
        </button>
    </div>

    <!-- ─── Search & Filter bar ──────────────────────────────────────────── -->
    <form method="GET" class="glass-panel rounded-2xl p-4 mb-8 flex flex-col sm:flex-row gap-3 items-stretch sm:items-center">
        <!-- Search -->
        <div class="relative flex-1">
            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-500 text-sm"></i>
            <input
                type="text"
                name="search"
                placeholder="Search cars…"
                value="<?php echo htmlspecialchars($search); ?>"
                class="form-input pl-9">
        </div>

        <!-- Type filter -->
        <select name="type" class="form-input sm:w-44">
            <option value="">All Types</option>
            <?php foreach ($types as $t): ?>
                <option value="<?php echo htmlspecialchars($t['car_type']); ?>"
                    <?php echo ($filterType === $t['car_type']) ? 'selected' : ''; ?>>
                    <?php echo ucfirst(htmlspecialchars($t['car_type'])); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <!-- Availability filter -->
        <select name="availability" class="form-input sm:w-44">
            <option value="">All Status</option>
            <option value="1" <?php echo ($filterAvail === '1') ? 'selected' : ''; ?>>Available</option>
            <option value="0" <?php echo ($filterAvail === '0') ? 'selected' : ''; ?>>Unavailable</option>
        </select>

        <button type="submit" class="btn-accent whitespace-nowrap">
            <i class="fa-solid fa-filter mr-1.5 text-xs"></i>Filter
        </button>

        <?php if ($search || $filterType || $filterAvail !== ''): ?>
            <a href="carsAdmin.php" class="btn-ghost whitespace-nowrap text-center">Clear</a>
        <?php endif; ?>
    </form>

    <!-- ─── Stats row ─────────────────────────────────────────────────────── -->
    <?php
        $total     = count($cars);
        $available = count(array_filter($cars, fn($c) => $c['availability'] == 1));
        $unavail   = $total - $available;
    ?>
    <div class="grid grid-cols-3 gap-4 mb-8">
        <?php foreach ([
            ['Total Cars',   $total,     'fa-car',           'text-white'],
            ['Available',    $available, 'fa-circle-check',  'text-accent'],
            ['Unavailable',  $unavail,   'fa-circle-xmark',  'text-red-400'],
        ] as [$label, $val, $icon, $col]): ?>
        <div class="glass-panel rounded-xl p-4 flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-white/5 flex items-center justify-center flex-shrink-0">
                <i class="fa-solid <?php echo $icon; ?> <?php echo $col; ?> text-sm"></i>
            </div>
            <div>
                <p class="text-lg font-bold text-white leading-none"><?php echo $val; ?></p>
                <p class="text-xs text-gray-400 mt-0.5"><?php echo $label; ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ─── Car Grid ──────────────────────────────────────────────────────── -->
    <?php if (empty($cars)): ?>
        <div class="text-center py-24 text-gray-500">
            <i class="fa-solid fa-car-side text-5xl mb-4 opacity-20"></i>
            <p class="text-lg font-medium">No cars found</p>
            <p class="text-sm mt-1">Try adjusting your search or filters.</p>
        </div>
    <?php else: ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($cars as $car): ?>
        <div class="car-card flex flex-col">

            <!-- Image -->
            <div class="relative overflow-hidden" style="height:180px">
                <?php if (!empty($car['image_url'])): ?>
                <img
                    src="<?php echo htmlspecialchars($car['image_url']); ?>"
                    alt="<?php echo htmlspecialchars($car['name']); ?>"
                    class="car-card-img"
                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="img-fallback" style="display:none; height:180px;">
                    <i class="fa-solid fa-car-side text-4xl text-gray-600"></i>
                </div>
                <?php else: ?>
                <div class="img-fallback" style="height:180px;">
                    <i class="fa-solid fa-car-side text-4xl text-gray-600"></i>
                </div>
                <?php endif; ?>

                <!-- Availability badge overlay -->
                <span class="absolute top-3 right-3 text-[11px] font-semibold px-2.5 py-1 rounded-full
                    <?php echo $car['availability'] ? 'badge-available' : 'badge-unavailable'; ?>">
                    <?php echo $car['availability'] ? 'Available' : 'Unavailable'; ?>
                </span>
            </div>

            <!-- Body -->
            <div class="flex flex-col flex-1 p-4 gap-3">

                <!-- Name + type + rating -->
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <h2 class="text-white font-bold text-base leading-tight">
                            <?php echo htmlspecialchars($car['name']); ?>
                        </h2>
                        <p class="text-gray-500 text-xs mt-0.5 capitalize">
                            <?php echo htmlspecialchars($car['car_type'] ?? '—'); ?>
                        </p>
                    </div>
                    <div class="flex items-center gap-1 flex-shrink-0 mt-0.5">
                        <i class="fa-solid fa-star star-accent"></i>
                        <span class="text-white font-semibold text-sm">4.9</span>
                    </div>
                </div>

                <!-- Branch -->
                <div class="flex items-center gap-1.5 text-xs text-gray-400">
                    <i class="fa-solid fa-location-dot text-accent text-xs"></i>
                    <?php echo htmlspecialchars($car['branch_name'] ?? 'No Branch'); ?>
                </div>

                <!-- Price -->
                <div class="flex items-baseline gap-1">
                    <span class="text-white font-extrabold text-xl tracking-tight">
                        RM<?php echo number_format($car['price_per_day'], 2); ?>
                    </span>
                    <span class="text-gray-500 text-xs">/DAY</span>
                </div>

                <!-- Description -->
                <?php if (!empty($car['description'])): ?>
                <p class="text-gray-400 text-xs leading-relaxed line-clamp-2">
                    <?php echo htmlspecialchars($car['description']); ?>
                </p>
                <?php endif; ?>

                <!-- Actions -->
                <div class="flex gap-2 pt-1 mt-auto">
                    <button
                        onclick='openEditModal(<?php echo htmlspecialchars(json_encode($car)); ?>)'
                        class="btn-edit flex-1 flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-pen-to-square text-xs"></i> Edit
                    </button>
                    <a href="carsAdmin.php?delete=<?php echo $car['car_id']; ?>"
                       onclick="return confirm('Delete <?php echo htmlspecialchars(addslashes($car['name'])); ?>? This cannot be undone.')"
                       class="btn-danger flex items-center justify-center gap-1.5 no-underline">
                        <i class="fa-solid fa-trash text-xs"></i> Delete
                    </a>
                </div>

            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</main>

<!-- ─────────────────────────────────────────────────────────────────────────
     ADD MODAL
────────────────────────────────────────────────────────────────────────── -->
<div id="addModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <button onclick="closeModal('addModal')" class="absolute top-4 right-4 text-gray-500 hover:text-white text-lg">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <h2 class="text-white font-bold text-xl mb-6">Add New Car</h2>

        <form method="POST" action="carsAdmin.php">
            <input type="hidden" name="action" value="add">
            <?php echo carFormFields(null, $branches); ?>
            <div class="flex gap-3 mt-6">
                <button type="submit" class="btn-accent flex-1">Add Car</button>
                <button type="button" onclick="closeModal('addModal')" class="btn-ghost flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- ─────────────────────────────────────────────────────────────────────────
     EDIT MODAL
────────────────────────────────────────────────────────────────────────── -->
<div id="editModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <button onclick="closeModal('editModal')" class="absolute top-4 right-4 text-gray-500 hover:text-white text-lg">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <h2 class="text-white font-bold text-xl mb-6">Edit Car</h2>

        <form method="POST" action="carsAdmin.php" id="editForm">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="car_id" id="edit_car_id">
            <?php echo carFormFields(null, $branches, 'edit_'); ?>
            <div class="flex gap-3 mt-6">
                <button type="submit" class="btn-accent flex-1">Save Changes</button>
                <button type="button" onclick="closeModal('editModal')" class="btn-ghost flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- ─── Toast notification ───────────────────────────────────────────────── -->
<?php if ($message): ?>
<div id="toast" class="<?php echo $messageType === 'success'
    ? 'bg-[#001a0d] border border-[#00FF66]/30 text-[#00FF66]'
    : 'bg-[#1a0000] border border-red-500/30 text-red-400'; ?>">
    <i class="fa-solid <?php echo $messageType === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?> mr-2"></i>
    <?php echo htmlspecialchars($message); ?>
</div>
<script>
    setTimeout(() => {
        const t = document.getElementById('toast');
        if (t) { t.style.opacity = '0'; setTimeout(() => t.remove(), 400); }
    }, 3500);
</script>
<?php endif; ?>

<!-- ─── Open edit modal if URL has ?edit= ────────────────────────────────── -->
<?php if ($editCar): ?>
<script>
    window.addEventListener('DOMContentLoaded', () => {
        openEditModal(<?php echo json_encode($editCar); ?>);
    });
</script>
<?php endif; ?>

<script>
function openAddModal() {
    document.getElementById('addModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function openEditModal(car) {
    // Populate edit form fields
    document.getElementById('edit_car_id').value  = car.car_id;
    document.getElementById('edit_name').value     = car.name;
    document.getElementById('edit_car_type').value = car.car_type || '';
    document.getElementById('edit_price_per_day').value = car.price_per_day;
    document.getElementById('edit_description').value   = car.description || '';
    document.getElementById('edit_image_url').value     = car.image_url || '';
    document.getElementById('edit_availability').checked = car.availability == 1;
    document.getElementById('edit_branch_id').value = car.branch_id || '';

    document.getElementById('editModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
    document.body.style.overflow = '';
}

// Close on backdrop click
['addModal','editModal'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) closeModal(id);
    });
});

// Escape key
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { closeModal('addModal'); closeModal('editModal'); }
});
</script>
</body>
</html>

<?php
// ─── Reusable form fields function ───────────────────────────────────────────
function carFormFields($car, $branches, $prefix = '') {
    $val = fn($k) => htmlspecialchars($car[$k] ?? '');
    ob_start();
    ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

        <div class="sm:col-span-2">
            <label class="block text-xs font-semibold text-gray-400 mb-1.5 uppercase tracking-wide">Car Name</label>
            <input type="text" name="name" id="<?php echo $prefix; ?>name"
                value="<?php echo $val('name'); ?>"
                placeholder="e.g. Porsche 911"
                required class="form-input">
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-400 mb-1.5 uppercase tracking-wide">Type</label>
            <input type="text" name="car_type" id="<?php echo $prefix; ?>car_type"
                value="<?php echo $val('car_type'); ?>"
                placeholder="e.g. coupe, hatchback"
                class="form-input">
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-400 mb-1.5 uppercase tracking-wide">Price / Day (RM)</label>
            <input type="number" name="price_per_day" id="<?php echo $prefix; ?>price_per_day"
                value="<?php echo $val('price_per_day'); ?>"
                placeholder="0.00" min="0" step="0.01"
                required class="form-input">
        </div>

        <div class="sm:col-span-2">
            <label class="block text-xs font-semibold text-gray-400 mb-1.5 uppercase tracking-wide">Image URL</label>
            <input type="url" name="image_url" id="<?php echo $prefix; ?>image_url"
                value="<?php echo $val('image_url'); ?>"
                placeholder="https://…"
                class="form-input">
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-400 mb-1.5 uppercase tracking-wide">Branch</label>
            <select name="branch_id" id="<?php echo $prefix; ?>branch_id" class="form-input">
                <option value="">— Select Branch —</option>
                <?php foreach ($branches as $b): ?>
                <option value="<?php echo $b['branch_id']; ?>"
                    <?php echo ($car && $car['branch_id'] == $b['branch_id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($b['branch_name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="flex items-center gap-3 pt-5">
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="availability" id="<?php echo $prefix; ?>availability"
                    value="1"
                    <?php echo (!$car || $car['availability']) ? 'checked' : ''; ?>
                    class="sr-only peer">
                <div class="w-11 h-6 bg-gray-700 rounded-full peer peer-checked:bg-[#00FF66]
                    after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                    after:bg-white after:rounded-full after:h-5 after:w-5
                    after:transition-all peer-checked:after:translate-x-5"></div>
                <span class="ml-3 text-sm text-gray-300">Available</span>
            </label>
        </div>

        <div class="sm:col-span-2">
            <label class="block text-xs font-semibold text-gray-400 mb-1.5 uppercase tracking-wide">Description</label>
            <textarea name="description" id="<?php echo $prefix; ?>description"
                rows="3" placeholder="Short description…"
                class="form-input resize-none"><?php echo $val('description'); ?></textarea>
        </div>

    </div>
    <?php
    return ob_get_clean();
}
?>