<?php

include 'dbConnect.php';

/* UPDATE STATUS */
if(isset($_POST['updateStatus'])){

$rental_id =
$_POST['rental_id'];

$status =
$_POST['status'];

$sql =
"UPDATE rentals
SET status='$status'
WHERE rental_id='$rental_id'";

$conn->query($sql);

}

/* FETCH RENTALS */

$sql =
"SELECT rentals.*,
users.name,
cars.name AS car_name

FROM rentals

JOIN users
ON rentals.user_id =
users.user_id

JOIN cars
ON rentals.car_id =
cars.car_id";

$result =
$conn->query($sql);

?>

<table border="1">

<tr>

<th>User</th>

<th>Car</th>

<th>Status</th>

<th>Action</th>

</tr>

<?php while(
$row =
$result->fetch_assoc()
){ ?>

<tr>

<td>
<?php echo $row['name']; ?>
</td>

<td>
<?php echo $row['car_name']; ?>
</td>

<td>
<?php echo $row['status']; ?>
</td>

<td>

<form method="POST">

<input
type="hidden"
name="rental_id"

value="<?php
echo
$row['rental_id'];
?>">

<select name="status">

<option value="Pending">
Pending
</option>

<option value="Approved">
Approved
</option>

<option value="Ready for Pickup">
Ready for Pickup
</option>

<option value="Rented">
Rented
</option>

<option value="Returned">
Returned
</option>

<option value="Cancelled">
Cancelled
</option>

</select>

<button
name="updateStatus">

Update

</button>

</form>

</td>

</tr>

<?php } ?>

</table>