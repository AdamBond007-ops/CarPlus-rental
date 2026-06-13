<?php

session_start();

if(
$_SESSION['role']
!= 'staff'
){

header(
"Location:login.html"
);

exit();

}

include 'dbConnect.php';

$branch_result = $conn->query(
"SELECT branch_id, branch_name
FROM branches
ORDER BY branch_name"
);

$branch_filter =
$_GET['branch_id']
?? '';

$where = [];
$params = [];
$types = '';
/* FETCH RENTALS */

$search = trim($_GET['search'] ?? '');

$sql = "

SELECT

rentals.*,

users.name AS customer_name,

users.profile_image,

cars.name AS car_name,

branches.branch_name

FROM rentals

JOIN users
ON rentals.user_id = users.user_id

JOIN cars
ON rentals.car_id = cars.car_id

JOIN branches
ON rentals.branch_id = branches.branch_id

";

if($branch_filter != ''){

    $where[] =
    "branches.branch_id = ?";

    $params[] =
    $branch_filter;

    $types .= 'i';
}

if($search !== ''){

    $where[] =
    "(users.name LIKE ?
    OR branches.branch_name LIKE ?
    OR cars.name LIKE ?)";

    $searchTerm =
    "%{$search}%";

    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;

    $types .= 'sss';
}

if(!empty($where)){

    $sql .=
    " WHERE "
    . implode(
    " AND ",
    $where
    );
}


$stmt = $conn->prepare($sql);

if(!empty($params)){

    $stmt->bind_param(
        $types,
        ...$params
    );
}

$stmt->execute();

$result = $stmt->get_result();



/* ACTIVE RENTALS */

$active_sql =

"SELECT COUNT(*)
AS total

FROM rentals

WHERE LOWER(status) =
'confirmed'
";

$active =
$conn
->query(
$active_sql
)
->fetch_assoc();

/* PENDING RENTALS */

$pickup_sql =

"SELECT COUNT(*)
AS total

FROM rentals

WHERE LOWER(status) =
'pending'
";

$pickup =
$conn
->query(
$pickup_sql
)
->fetch_assoc();

/* UPDATE RENTAL STATUS */

if(
isset(
$_POST['updateStatus']
)
){

$rental_id =
$_POST['rental_id'];

$status =
$_POST['status'];

$update_sql =

"UPDATE rentals

SET status = ?

WHERE rental_id = ?";

$stmt =
$conn->prepare(
$update_sql
);

$stmt->bind_param(

"si",

$status,

$rental_id

);

$stmt->execute();

/* REFRESH PAGE */

header(
"Location: staffPortal.php"
);

exit();

}

$user_id = $_SESSION['user_id'];

$sql = "
SELECT profile_image, name
FROM users
WHERE user_id = '$user_id'
";


$user = $result->fetch_assoc();
?>







<html lang="en">

  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Rental Management Dashboard - CarPlus</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="">
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap"
      rel="stylesheet">
    <script>
      window.FontAwesomeConfig = {
        autoReplaceSvg: 'nest'
      };
    </script>
    <script
      src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"
      crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
      integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A=="
      crossorigin="anonymous" referrerpolicy="no-referrer">

    <script>
      tailwind.config = {
        theme:
        {
          extend:
          {
            fontFamily:
            {
              sans: ['Inter', 'sans-serif'],
            },
            colors:
            {
              dark:
              {
                900: '#0a0a0a',
                800: '#121212',
                700: '#1a1a1a',
                600: '#262626',
                500: '#404040',
              },
              neon:
              {
                green: '#00FF66',
                greenHover: '#00e65c',
                greenMuted: 'rgba(0, 255, 102, 0.1)',
              }
            },
            boxShadow:
            {
              'neon-glow': '0 0 15px rgba(0, 255, 102, 0.15)',
              'card-depth': '0 8px 30px rgba(0,0,0,0.4)',
            }
          }
        }
      }
    </script>
    <style>
      body
      {
        margin: 0;
        padding: 0;
        background-color: #0a0a0a;
        color: #ffffff;
        -webkit-font-smoothing: antialiased;
      }

      ::-webkit-scrollbar
      {
        display: none;
      }

      .input-dark
      {
        background-color: #1a1a1a;
        border: 1px solid #262626;
        color: #ffffff;
        transition: all 0.3s ease;
      }

      .input-dark:focus
      {
        outline: none;
        border-color: #00FF66;
        box-shadow: 0 0 0 2px rgba(0, 255, 102, 0.2);
      }

      .input-dark::placeholder
      {
        color: #6b7280;
      }

      .radio-custom:checked
      {
        background-color: #00FF66;
        border-color: #00FF66;
      }

      /* Custom dropdown styling */
      select.input-dark
      {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 1rem center;
        background-size: 1.2em 1.2em;
        padding-right: 2.5rem;
      }

      /* Table row hover effect */
      .table-row-hover:hover
      {
        background-color: rgba(0, 255, 102, 0.03);
        border-color: rgba(0, 255, 102, 0.2);
      }
    </style>
  </head>

  <body
    class="min-h-screen text-gray-100 font-sans selection:bg-neon-green selection:text-black flex">
    <!-- Sidebar Navigation -->
    <!-- Main Content Wrapper -->

    <?php 
      include 'staffHeader.php';
      ?>

    <main class="pt-28 px-10">

    <div class="max-w-xl mb-6">
            <form method="GET" class="flex-1 max-w-md">
              <div class="relative">
    
    <i class="fa-solid fa-magnifying-glass
              absolute left-4 top-1/2 -translate-y-1/2
              text-gray-400"></i>

    <input
        type="text"
        name="search"
        value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
        placeholder="Search by name, cars, branch..."
        class="input-dark w-full pl-11 pr-4 py-3 rounded-xl">

</div>
    
</form></div>
      <!-- Dashboard Content -->
      <div
        class="flex-1 p-10 max-w-[1600px] mx-auto w-full flex flex-col gap-8">
        <!-- Overview Tiles -->
        <div
          class="bg-dark-800 rounded-[20px] p-6 shadow-card-depth border border-dark-700 flex items-center gap-6">
          <div
            class="w-14 h-14 rounded-full bg-neon-greenMuted flex items-center justify-center border border-neon-green/30 shadow-neon-glow">
            <i class="fa-solid fa-car-on text-neon-green text-xl"></i></div>
          <div>
            <p class="text-sm text-gray-400 mb-1">Active Rentals</p>
            <h3 class="text-2xl font-bold text-white">

                <?php
                echo
                $active['total'];
                ?>

                </h3>
          </div>
        </div>
        <section id="overview-tiles"
          class="grid grid-cols-1 md:grid-cols-4 gap-6"></section>
        <!-- Main Data Table Section -->
        <div
          class="bg-dark-800 rounded-[20px] p-6 shadow-card-depth border border-dark-700 flex items-center gap-6 flex-row left-0"
          id="ifhdp">
          <div
            class="w-14 h-14 rounded-full bg-dark-700 flex items-center justify-center border border-dark-600">
            <i class="fa-solid fa-clock text-gray-300 text-xl"></i></div>
          <div>
            <p class="text-sm text-gray-400 mb-1">Awaiting Pickup</p>
            <h3 class="text-2xl font-bold text-white">

            <?php
            echo
            $pickup['total'];
            ?>

            </h3>
          </div>
        </div>
        <section id="rental-management"
          class="bg-dark-800 rounded-[20px] shadow-card-depth border border-dark-700 flex flex-col flex-1 overflow-hidden">
          <!-- Table Header & Filters -->
          <div
            class="p-6 border-b border-dark-700 flex flex-col sm:flex-row justify-between items-center gap-4">
            
            <div class="flex items-center gap-3"><form method="GET">

<select
name="branch_id"
onchange="this.form.submit()"
class="input-dark h-10 px-4 rounded-xl">

<option value="">
All Branches
</option>

<?php while(
$branch =
$branch_result->fetch_assoc()
){ ?>

<option
value="<?php echo $branch['branch_id']; ?>"

<?php
if(
$branch_filter ==
$branch['branch_id']
)
echo 'selected';
?>>

<?php
echo
$branch['branch_name'];
?>

</option>

<?php } ?>

</select>

</form></div>
          </div><!-- Table Content -->
          <div class="overflow-x-auto w-full">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr
                  class="bg-dark-900/50 border-b border-dark-700 text-xs uppercase tracking-wider text-gray-400 font-medium">
                  <th class="px-6 py-4">Customer</th>
                  <th class="px-6 py-4">Vehicle</th>
                  <th class="px-6 py-4">Rental Period</th>
                  <th class="px-6 py-4">Location</th>
                  <th class="px-6 py-4">Method</th>
                  <th class="px-6 py-4">Status</th>
                  <th class="px-6 py-4 text-right">Action</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-dark-700">

                    <?php

                    while(
                    $row =
                    $result->fetch_assoc()
                    ){

                    ?>

                    <tr
                    class="table-row-hover transition-colors group">

                    <td class="px-6 py-4">

                    <div
                    class="flex items-center gap-3">

                    <div
                    class="w-10 h-10 rounded-full overflow-hidden border border-dark-600">

                    <img

                    src="uploads/<?php

                    echo
                    !empty(
                    $row[
                    'profile_image'
                    ]
                    )

                    ?

                    $row[
                    'profile_image'
                    ]

                    :

                    'default-avatar.png';

                    ?>"

                    class="w-full h-full object-cover">

                    </div>

                    <div>

                    <p
                    class="text-sm font-medium text-white">

                    <?php

                    echo
                    $row[
                    'customer_name'
                    ];

                    ?>

                    </p>

                    <p
                    class="text-xs text-gray-500">

                    ID:
                    #<?php
                    echo
                    $row[
                    'rental_id'
                    ];
                    ?>

                    </p>

                    </div>

                    </div>

                    </td>

                    <td
                    class="px-6 py-4">

                    <?php

                    echo
                    $row[
                    'car_name'
                    ];

                    ?>

                    </td>

                    <td
                    class="px-6 py-4">

                    <?php

                    echo
                    $row[
                    'pickup_date'
                    ];

                    ?>

                    -

                    <?php

                    echo
                    $row[
                    'dropoff_date'
                    ];

                    ?>

                    </td>

                    <td class="px-6 py-4">

<?php
echo
$row['branch_name'];
?>

</td>

<td class="px-6 py-4">

<?php if(strtolower($row['rental_method']) == 'delivery'): ?>

<span class="
px-3
py-1
rounded-full
text-xs
font-medium
bg-blue-500/20
text-blue-400
border
border-blue-500/30">

🚚 Delivery

</span>

<?php else: ?>

<span class="
px-3
py-1
rounded-full
text-xs
font-medium
bg-neon-greenMuted
text-neon-green
border
border-neon-green/30">

🏢 Pickup

</span>

<?php endif; ?>

</td>

<td class="px-6 py-4">

<span>

<?php
echo
$row['status'];
?>

</span>

                   
<td
class="px-6 py-4">

<form
method="POST"

class="flex gap-2">

<input

type="hidden"

name="rental_id"

value="<?php
echo
$row['rental_id'];
?>">

<select

name="status"

class="input-dark h-9 rounded-lg">

  <option
value="pending"

<?php
if(
$row['status']
==
'Pending'
)
echo
'selected';
?>

>

Pending

</option>

<option
value="confirmed"

<?php
if(
strtolower(
$row['status']
)
==
'pending'
)
echo 'selected';

?>
>

Confirmed


</option>


<option
value="cancelled"

<?php
if(
strtolower(
$row['status']
)
==
'confirmed'
)
echo 'selected';
?>

>

Cancelled

</option>

<option
value="completed"

<?php
if(
strtolower(
$row['status']
)
==
'cancelled'
)
echo 'selected';
?>

>

Complete

</option>

</select>

<button

type="submit"

name="updateStatus"

class="
px-3
py-2
bg-green-500
rounded-lg
text-black">

Update

</button>

</form>

</td>
                    </td>

                    
                    </tr>

                    <?php

                    }

                    ?>

</tbody>
            
            </table>
          </div><!-- Pagination -->
          <div
            class="p-4 border-t border-dark-700 flex items-center justify-between bg-dark-800 rounded-b-[20px]">
            <p class="text-sm text-gray-500">Showing <span
                class="font-medium text-white">1</span> to <span
                class="font-medium text-white">10</span> of <span
                class="font-medium text-white">124</span> entries</p>
            <div class="flex gap-2"><button
                class="w-8 h-8 rounded-lg bg-dark-900 border border-dark-600 flex items-center justify-center text-gray-400 hover:text-white disabled:opacity-50"><i
                  class="fa-solid fa-chevron-left text-xs"></i></button><button
                class="w-8 h-8 rounded-lg bg-neon-green text-dark-900 font-medium text-sm border border-neon-green">1</button><button
                class="w-8 h-8 rounded-lg bg-dark-900 border border-dark-600 flex items-center justify-center text-gray-400 hover:text-white text-sm">2</button><button
                class="w-8 h-8 rounded-lg bg-dark-900 border border-dark-600 flex items-center justify-center text-gray-400 hover:text-white text-sm">3</button><button
                class="w-8 h-8 rounded-lg bg-dark-900 border border-dark-600 flex items-center justify-center text-gray-400 hover:text-white"><i
                  class="fa-solid fa-chevron-right text-xs"></i></button></div>
          </div>
        </section>
      </div>
    </main>
  </body>

</html>