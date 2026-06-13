<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'dbConnect.php';

// --- AJAX: Toggle Availability ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    header('Content-Type: application/json');

    if ($_POST['action'] === 'toggle_availability') {
        $car_id      = (int) ($_POST['car_id'] ?? 0);
        $new_status  = (int) ($_POST['availability'] ?? 0);

        $stmt = $conn->prepare("UPDATE cars SET availability = ? WHERE car_id = ?");
        $stmt->bind_param("ii", $new_status, $car_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'availability' => $new_status]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
        exit;
    }

    // --- AJAX: Delete Car ---
    if ($_POST['action'] === 'delete_car') {
        $car_id = (int) ($_POST['car_id'] ?? 0);

        $stmt = $conn->prepare("DELETE FROM cars WHERE car_id = ?");
        $stmt->bind_param("i", $car_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
        exit;
    }

    // --- AJAX: Add / Edit Car ---
    if ($_POST['action'] === 'save_car') {
        $car_id       = (int) ($_POST['car_id'] ?? 0);
        $name         = trim($_POST['name']          ?? '');
        $car_type     = trim($_POST['car_type']       ?? '');
        $price        = (float) ($_POST['price_per_day'] ?? 0);
        $description  = trim($_POST['description']   ?? '');
        $image_url    = trim($_POST['image_url']      ?? '');
        $availability = (int) ($_POST['availability'] ?? 1);
        $branch_id    = (int) ($_POST['branch_id']    ?? 0);

        if ($car_id) {
            // Edit
            $stmt = $conn->prepare("
                UPDATE cars
                SET name=?, car_type=?, price_per_day=?, description=?,
                    image_url=?, availability=?, branch_id=?
                WHERE car_id=?
            ");
            $stmt->bind_param("ssdssiii", $name, $car_type, $price, $description,
                                          $image_url, $availability, $branch_id, $car_id);
        } else {
            // Add
            $stmt = $conn->prepare("
                INSERT INTO cars (name, car_type, price_per_day, description, image_url, availability, branch_id)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("ssdssiи", $name, $car_type, $price, $description,
                                          $image_url, $availability, $branch_id);
            // fix bind_param typo
            $stmt = $conn->prepare("
                INSERT INTO cars (name, car_type, price_per_day, description, image_url, availability, branch_id)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("ssdssii", $name, $car_type, $price, $description,
                                          $image_url, $availability, $branch_id);
        }

        if ($stmt->execute()) {
            $new_id = $car_id ?: $conn->insert_id;
            echo json_encode(['success' => true, 'car_id' => $new_id]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Unknown action']);
    exit;
}

// --- Fetch stats ---
$total_cars       = $conn->query("SELECT COUNT(*) AS c FROM cars")->fetch_assoc()['c'];
$available_cars   = $conn->query("SELECT COUNT(*) AS c FROM cars WHERE availability = 1")->fetch_assoc()['c'];
$unavailable_cars = $conn->query("SELECT COUNT(*) AS c FROM cars WHERE availability = 0")->fetch_assoc()['c'];

// --- Fetch all cars with branch ---
$cars_result = $conn->query("
    SELECT c.*, b.branch_name, b.location AS branch_location
    FROM cars c
    LEFT JOIN branches b ON c.branch_id = b.branch_id
    ORDER BY c.car_id ASC
");
$cars = $cars_result->fetch_all(MYSQLI_ASSOC);

// --- Fetch branches for form ---
$branches_result = $conn->query("SELECT * FROM branches ORDER BY branch_name");
$branches = $branches_result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cars – CarPlus Staff</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { background: #0a0a0a; }

        .glass-panel {
            background: rgba(18,18,18,0.75);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255,255,255,0.08);
            box-shadow: 0 8px 32px rgba(0,0,0,0.35);
        }

        .card {
            background: #111111;
            border: 1px solid rgba(255,255,255,0.07);
            transition: transform .2s, box-shadow .2s;
        }
        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.5);
        }

        .stat-card {
            background: #111111;
            border: 1px solid rgba(255,255,255,0.07);
        }

        /* Toggle switch */
        .toggle-checkbox:checked { right: 0; border-color: #00FF66; }
        .toggle-checkbox:checked + .toggle-label { background: #00FF66; }
        .toggle-checkbox { right: 0.25rem; transition: right .2s; }

        /* Modal backdrop */
        .modal-backdrop {
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(4px);
        }

        .modal-box {
            background: #141414;
            border: 1px solid rgba(255,255,255,0.1);
        }

        input, select, textarea {
            background: #1a1a1a !important;
            border: 1px solid rgba(255,255,255,0.12) !important;
            color: #fff !important;
        }
        input:focus, select:focus, textarea:focus {
            outline: none !important;
            border-color: #00FF66 !important;
            box-shadow: 0 0 0 2px rgba(0,255,102,0.15) !important;
        }
        select option { background: #1a1a1a; }

        .badge-available   { background: rgba(0,255,102,0.15); color: #00FF66; }
        .badge-unavailable { background: rgba(255,60,60,0.15);  color: #ff6b6b; }

        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #111; }
        ::-webkit-scrollbar-thumb { background: #333; border-radius: 3px; }
    </style>
</head>
<body class="min-h-screen text-white">

    <?php include 'staffHeader.php'; ?>

    <main class="pt-24 pb-16 px-6 lg:px-12 max-w-7xl mx-auto">

        <!-- Page Title -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-white">Fleet Management</h1>
                <p class="text-gray-400 text-sm mt-1">Manage all cars across branches</p>
            </div>
            <button
                onclick="openAddModal()"
                class="flex items-center gap-2 bg-[#00FF66] hover:bg-[#00e05a] text-black font-semibold px-5 py-2.5 rounded-xl transition-colors">
                <i class="fa-solid fa-plus text-sm"></i>
                Add Car
            </button>
        </div>

        <!-- Stat Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-10">
            <div class="stat-card rounded-2xl p-5 flex items-center gap-4">
                <div class="w-11 h-11 rounded-xl bg-white/10 flex items-center justify-center">
                    <i class="fa-solid fa-car text-white"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-white"><?php echo $total_cars; ?></p>
                    <p class="text-gray-400 text-sm">Total Cars</p>
                </div>
            </div>
            <div class="stat-card rounded-2xl p-5 flex items-center gap-4">
                <div class="w-11 h-11 rounded-xl bg-[#00FF66]/15 flex items-center justify-center">
                    <i class="fa-solid fa-circle-check text-[#00FF66]"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-white"><?php echo $available_cars; ?></p>
                    <p class="text-gray-400 text-sm">Available</p>
                </div>
            </div>
            <div class="stat-card rounded-2xl p-5 flex items-center gap-4">
                <div class="w-11 h-11 rounded-xl bg-red-500/15 flex items-center justify-center">
                    <i class="fa-solid fa-circle-xmark text-red-400"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-white"><?php echo $unavailable_cars; ?></p>
                    <p class="text-gray-400 text-sm">Unavailable</p>
                </div>
            </div>
        </div>

        <!-- Cars Grid -->
        <div id="carsGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($cars as $car): ?>
            <div
                class="card rounded-2xl overflow-hidden"
                id="car-card-<?php echo $car['car_id']; ?>"
                data-car='<?php echo htmlspecialchars(json_encode($car), ENT_QUOTES); ?>'>

                <!-- Image -->
                <div class="relative h-48 overflow-hidden bg-[#1a1a1a]">
                    <img
                        src="<?php echo htmlspecialchars($car['image_url'] ?? ''); ?>"
                        alt="<?php echo htmlspecialchars($car['name']); ?>"
                        class="w-full h-full object-cover"
                        onerror="this.src='https://via.placeholder.com/400x200/1a1a1a/555?text=No+Image'">

                    <!-- Availability badge -->
                    <span
                        id="badge-<?php echo $car['car_id']; ?>"
                        class="absolute top-3 right-3 text-xs font-semibold px-2.5 py-1 rounded-full
                        <?php echo $car['availability'] ? 'badge-available' : 'badge-unavailable'; ?>">
                        <?php echo $car['availability'] ? 'Available' : 'Unavailable'; ?>
                    </span>
                </div>

                <!-- Body -->
                <div class="p-5">
                    <!-- Title row -->
                    <div class="flex items-start justify-between mb-1">
                        <div>
                            <h3 class="font-bold text-white text-base leading-tight">
                                <?php echo htmlspecialchars($car['name']); ?>
                            </h3>
                            <p class="text-gray-500 text-xs capitalize mt-0.5">
                                <?php echo htmlspecialchars($car['car_type'] ?? ''); ?>
                            </p>
                        </div>
                        <div class="flex items-center gap-1 text-yellow-400 text-xs font-semibold shrink-0 mt-0.5">
                            <i class="fa-solid fa-star text-[10px]"></i>
                            4.9
                        </div>
                    </div>

                    <!-- Branch -->
                    <p class="flex items-center gap-1.5 text-gray-400 text-xs mt-2">
                        <i class="fa-solid fa-location-dot text-[#00FF66] text-[10px]"></i>
                        <?php echo htmlspecialchars($car['branch_name'] ?? 'N/A'); ?>
                    </p>

                    <!-- Price -->
                    <p class="mt-3 text-white font-bold text-lg">
                        RM<?php echo number_format($car['price_per_day'], 0); ?>
                        <span class="text-gray-500 text-xs font-normal">/DAY</span>
                    </p>

                    <!-- Description -->
                    <?php if (!empty($car['description'])): ?>
                    <p class="text-gray-500 text-xs mt-2 line-clamp-2 leading-relaxed">
                        <?php echo htmlspecialchars($car['description']); ?>
                    </p>
                    <?php endif; ?>

                    <!-- Availability Toggle -->
                    <div class="flex items-center justify-between mt-4 pt-4 border-t border-white/5">
                        <span class="text-gray-400 text-xs">Availability</span>
                        <label class="flex items-center cursor-pointer select-none">
                            <div class="relative">
                                <input
                                    type="checkbox"
                                    class="sr-only availability-toggle"
                                    data-car-id="<?php echo $car['car_id']; ?>"
                                    <?php echo $car['availability'] ? 'checked' : ''; ?>>
                                <div class="w-10 h-5 bg-gray-700 rounded-full shadow-inner toggle-track
                                    <?php echo $car['availability'] ? '!bg-[#00FF66]' : ''; ?>
                                    transition-colors duration-200">
                                </div>
                                <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow
                                    transition-transform duration-200
                                    <?php echo $car['availability'] ? 'translate-x-5' : ''; ?>
                                    toggle-thumb">
                                </div>
                            </div>
                        </label>
                    </div>

                    <!-- Action Buttons -->
                    <div class="grid grid-cols-2 gap-3 mt-4">
                        <button
                            onclick='openEditModal(<?php echo htmlspecialchars(json_encode($car), ENT_QUOTES); ?>)'
                            class="flex items-center justify-center gap-2 bg-white/5 hover:bg-white/10
                                   border border-white/10 text-white text-sm font-medium
                                   py-2.5 rounded-xl transition-colors">
                            <i class="fa-regular fa-pen-to-square text-xs"></i>
                            Edit
                        </button>
                        <button
                            onclick="confirmDelete(<?php echo $car['car_id']; ?>, '<?php echo htmlspecialchars(addslashes($car['name'])); ?>')"
                            class="flex items-center justify-center gap-2 bg-red-500/10 hover:bg-red-500/20
                                   border border-red-500/20 text-red-400 text-sm font-medium
                                   py-2.5 rounded-xl transition-colors">
                            <i class="fa-regular fa-trash-can text-xs"></i>
                            Delete
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- ===================== ADD / EDIT MODAL ===================== -->
    <div id="carModal" class="fixed inset-0 z-[100] hidden flex items-center justify-center p-4">
        <div class="modal-backdrop absolute inset-0" onclick="closeModal()"></div>
        <div class="modal-box relative w-full max-w-lg rounded-2xl p-6 z-10 max-h-[90vh] overflow-y-auto">

            <div class="flex items-center justify-between mb-6">
                <h2 id="modalTitle" class="text-lg font-bold text-white">Add Car</h2>
                <button onclick="closeModal()" class="text-gray-400 hover:text-white transition-colors">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>

            <form id="carForm" onsubmit="submitCarForm(event)">
                <input type="hidden" id="formCarId" name="car_id" value="0">
                <input type="hidden" name="action" value="save_car">

                <div class="space-y-4">

                    <div>
                        <label class="block text-xs text-gray-400 mb-1.5">Car Name</label>
                        <input type="text" id="formName" name="name" required
                            class="w-full rounded-xl px-4 py-2.5 text-sm"
                            placeholder="e.g. Porsche 911">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-gray-400 mb-1.5">Car Type</label>
                            <input type="text" id="formType" name="car_type"
                                class="w-full rounded-xl px-4 py-2.5 text-sm"
                                placeholder="e.g. sedan, SUV">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 mb-1.5">Price / Day (RM)</label>
                            <input type="number" id="formPrice" name="price_per_day" required min="0" step="0.01"
                                class="w-full rounded-xl px-4 py-2.5 text-sm"
                                placeholder="600.00">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs text-gray-400 mb-1.5">Image URL</label>
                        <input type="url" id="formImage" name="image_url"
                            class="w-full rounded-xl px-4 py-2.5 text-sm"
                            placeholder="https://...">
                    </div>

                    <div>
                        <label class="block text-xs text-gray-400 mb-1.5">Branch</label>
                        <select id="formBranch" name="branch_id" class="w-full rounded-xl px-4 py-2.5 text-sm">
                            <option value="">— Select Branch —</option>
                            <?php foreach ($branches as $b): ?>
                            <option value="<?php echo $b['branch_id']; ?>">
                                <?php echo htmlspecialchars($b['branch_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs text-gray-400 mb-1.5">Description</label>
                        <textarea id="formDesc" name="description" rows="3"
                            class="w-full rounded-xl px-4 py-2.5 text-sm resize-none"
                            placeholder="Short description..."></textarea>
                    </div>

                    <div class="flex items-center justify-between bg-white/5 rounded-xl px-4 py-3">
                        <span class="text-sm text-gray-300">Available for rent</span>
                        <label class="flex items-center cursor-pointer">
                            <div class="relative">
                                <input type="checkbox" id="formAvailability" name="availability" value="1" checked class="sr-only">
                                <div id="formToggleTrack"
                                    class="w-10 h-5 bg-[#00FF66] rounded-full shadow-inner transition-colors duration-200">
                                </div>
                                <div id="formToggleThumb"
                                    class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow
                                    translate-x-5 transition-transform duration-200">
                                </div>
                            </div>
                        </label>
                    </div>

                </div>

                <div class="flex gap-3 mt-6">
                    <button type="button" onclick="closeModal()"
                        class="flex-1 bg-white/5 hover:bg-white/10 border border-white/10
                               text-white text-sm font-medium py-2.5 rounded-xl transition-colors">
                        Cancel
                    </button>
                    <button type="submit" id="submitBtn"
                        class="flex-1 bg-[#00FF66] hover:bg-[#00e05a] text-black font-semibold
                               text-sm py-2.5 rounded-xl transition-colors">
                        Save Car
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================== DELETE CONFIRM MODAL ===================== -->
    <div id="deleteModal" class="fixed inset-0 z-[100] hidden flex items-center justify-center p-4">
        <div class="modal-backdrop absolute inset-0" onclick="closeDeleteModal()"></div>
        <div class="modal-box relative w-full max-w-sm rounded-2xl p-6 z-10 text-center">
            <div class="w-14 h-14 bg-red-500/15 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fa-regular fa-trash-can text-red-400 text-2xl"></i>
            </div>
            <h3 class="text-white font-bold text-lg mb-2">Delete Car?</h3>
            <p class="text-gray-400 text-sm mb-6">
                Are you sure you want to delete <span id="deleteCarName" class="text-white font-medium"></span>?
                This action cannot be undone.
            </p>
            <input type="hidden" id="deleteCarId">
            <div class="flex gap-3">
                <button onclick="closeDeleteModal()"
                    class="flex-1 bg-white/5 hover:bg-white/10 border border-white/10
                           text-white text-sm font-medium py-2.5 rounded-xl transition-colors">
                    Cancel
                </button>
                <button onclick="executeDeletion()"
                    class="flex-1 bg-red-500 hover:bg-red-600 text-white font-semibold
                           text-sm py-2.5 rounded-xl transition-colors">
                    Delete
                </button>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast"
        class="fixed bottom-6 right-6 z-[200] hidden items-center gap-3
               bg-[#1a1a1a] border border-white/10 text-white text-sm
               px-4 py-3 rounded-xl shadow-2xl transition-all">
        <i id="toastIcon" class="fa-solid fa-circle-check text-[#00FF66]"></i>
        <span id="toastMsg"></span>
    </div>

    <script>
    // ============================================================
    //  AVAILABILITY TOGGLE
    // ============================================================
    document.querySelectorAll('.availability-toggle').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const carId = this.dataset.carId;
            const newVal = this.checked ? 1 : 0;
            const track  = this.parentElement.querySelector('.toggle-track');
            const thumb  = this.parentElement.querySelector('.toggle-thumb');
            const badge  = document.getElementById('badge-' + carId);

            // Optimistic UI update
            if (this.checked) {
                track.classList.add('!bg-[#00FF66]');
                thumb.classList.add('translate-x-5');
                badge.textContent = 'Available';
                badge.className = badge.className.replace('badge-unavailable', 'badge-available');
            } else {
                track.classList.remove('!bg-[#00FF66]');
                thumb.classList.remove('translate-x-5');
                badge.textContent = 'Unavailable';
                badge.className = badge.className.replace('badge-available', 'badge-unavailable');
            }

            fetch('carsStaff.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=toggle_availability&car_id=' + carId + '&availability=' + newVal
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast(newVal ? 'Car set to Available' : 'Car set to Unavailable', newVal ? 'success' : 'warning');
                } else {
                    showToast('Failed to update availability', 'error');
                    this.checked = !this.checked; // revert
                }
            })
            .catch(() => { showToast('Network error', 'error'); });
        });
    });

    // ============================================================
    //  MODAL HELPERS
    // ============================================================
    function openAddModal() {
        document.getElementById('modalTitle').textContent = 'Add Car';
        document.getElementById('submitBtn').textContent  = 'Add Car';
        document.getElementById('carForm').reset();
        document.getElementById('formCarId').value = '0';

        // Reset form toggle to checked/available
        setFormToggle(true);

        document.getElementById('carModal').classList.remove('hidden');
        document.getElementById('carModal').classList.add('flex');
    }

    function openEditModal(car) {
        document.getElementById('modalTitle').textContent = 'Edit Car';
        document.getElementById('submitBtn').textContent  = 'Save Changes';
        document.getElementById('formCarId').value        = car.car_id;
        document.getElementById('formName').value         = car.name         || '';
        document.getElementById('formType').value         = car.car_type     || '';
        document.getElementById('formPrice').value        = car.price_per_day|| '';
        document.getElementById('formImage').value        = car.image_url    || '';
        document.getElementById('formDesc').value         = car.description  || '';
        document.getElementById('formBranch').value       = car.branch_id    || '';

        setFormToggle(car.availability == 1);

        document.getElementById('carModal').classList.remove('hidden');
        document.getElementById('carModal').classList.add('flex');
    }

    function setFormToggle(checked) {
        const cb    = document.getElementById('formAvailability');
        const track = document.getElementById('formToggleTrack');
        const thumb = document.getElementById('formToggleThumb');
        cb.checked = checked;
        if (checked) {
            track.classList.add('bg-[#00FF66]');
            track.classList.remove('bg-gray-700');
            thumb.classList.add('translate-x-5');
        } else {
            track.classList.remove('bg-[#00FF66]');
            track.classList.add('bg-gray-700');
            thumb.classList.remove('translate-x-5');
        }
    }

    // Wire up the form toggle in modal
    document.getElementById('formAvailability').addEventListener('change', function() {
        setFormToggle(this.checked);
    });

    function closeModal() {
        document.getElementById('carModal').classList.add('hidden');
        document.getElementById('carModal').classList.remove('flex');
    }

    // ============================================================
    //  SUBMIT FORM (ADD / EDIT)
    // ============================================================
    function submitCarForm(e) {
        e.preventDefault();
        const form = document.getElementById('carForm');
        const data = new FormData(form);

        // Ensure availability value
        if (!document.getElementById('formAvailability').checked) {
            data.set('availability', '0');
        } else {
            data.set('availability', '1');
        }

        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.textContent = 'Saving…';

        fetch('carsStaff.php', { method: 'POST', body: data })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                showToast('Car saved successfully', 'success');
                closeModal();
                setTimeout(() => location.reload(), 700);
            } else {
                showToast('Error: ' + (res.error || 'Unknown'), 'error');
                btn.disabled = false;
                btn.textContent = 'Save Car';
            }
        })
        .catch(() => {
            showToast('Network error', 'error');
            btn.disabled = false;
        });
    }

    // ============================================================
    //  DELETE
    // ============================================================
    function confirmDelete(carId, carName) {
        document.getElementById('deleteCarId').value  = carId;
        document.getElementById('deleteCarName').textContent = carName;
        document.getElementById('deleteModal').classList.remove('hidden');
        document.getElementById('deleteModal').classList.add('flex');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
        document.getElementById('deleteModal').classList.remove('flex');
    }

    function executeDeletion() {
        const carId = document.getElementById('deleteCarId').value;

        fetch('carsStaff.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=delete_car&car_id=' + carId
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                showToast('Car deleted', 'success');
                closeDeleteModal();
                const card = document.getElementById('car-card-' + carId);
                if (card) {
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.95)';
                    card.style.transition = 'all .3s';
                    setTimeout(() => card.remove(), 300);
                }
            } else {
                showToast('Delete failed: ' + (res.error || ''), 'error');
            }
        })
        .catch(() => showToast('Network error', 'error'));
    }

    // ============================================================
    //  TOAST
    // ============================================================
    let toastTimer;
    function showToast(msg, type = 'success') {
        const toast = document.getElementById('toast');
        const icon  = document.getElementById('toastIcon');
        document.getElementById('toastMsg').textContent = msg;

        icon.className = 'fa-solid ';
        if (type === 'success') {
            icon.className += 'fa-circle-check text-[#00FF66]';
        } else if (type === 'warning') {
            icon.className += 'fa-triangle-exclamation text-yellow-400';
        } else {
            icon.className += 'fa-circle-xmark text-red-400';
        }

        toast.classList.remove('hidden');
        toast.classList.add('flex');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => {
            toast.classList.add('hidden');
            toast.classList.remove('flex');
        }, 3000);
    }
    </script>
</body>
</html>