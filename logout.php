<?php 
include ("cnx_db.php"); 
// Unset all of the session variables.
$_SESSION = array();

// Finally, destroy the session.
session_destroy();

header("Location: login.php");
	
?>
