<?php
$host = "sql103.infinityfree.com";
$user = "if0_42143898";
$pass = "Umarjztr313";
$db = "if0_42143898_carplus";

$conn = new mysqli(
"sql103.infinityfree.com",
"if0_42143898",
"Umarjztr313",
"if0_42143898_carplus"
);

if(
$conn->connect_error
){

die(
"Connection failed: "
.
$conn->connect_error
);

}

/* FETCH RENTALS */

$sql = "

SELECT

rentals.*,

users.name AS customer_name,

users.profile_image,

cars.name AS car_name,

branches.branch_name

FROM rentals

JOIN users
ON rentals.user_id =
users.user_id

JOIN cars
ON rentals.car_id =
cars.car_id

JOIN branches
ON rentals.branch_id =
branches.branch_id

ORDER BY rentals.created_at DESC

";

$result =
$conn->query($sql);

/* ACTIVE RENTALS */

$active_sql =

"SELECT COUNT(*)
AS total

FROM rentals

WHERE status =
'Active'

OR status =
'Rented'
";

$active_result =
$conn
->query(
$active_sql
);

$active =
$active_result
->fetch_assoc();

/* AWAITING PICKUP */

$pickup_sql =

"SELECT COUNT(*)
AS total

FROM rentals

WHERE status =
'Awaiting Pickup'
OR status =
'Pending'
";

$pickup_result =
$conn
->query(
$pickup_sql
);

$pickup =
$pickup_result
->fetch_assoc();
?>