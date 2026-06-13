<?php

$plain =
"fahim";

$hash =
'$2y$10$kybqJxNS9niTMoPgWSWiiOzVbnbj4PCxbgPR9bebEzq2kaVzKm4O.';
if(
password_verify(
$plain,
$hash
)
){

echo
"MATCH";

}
else{

echo
"NOT MATCH";

}

?>