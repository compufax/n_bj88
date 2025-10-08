<?php
include("main2.php");


top($_SESSION);
echo '<script type="text/javascript" src="js/jquery-timepicker-master/jquery.timepicker.js"></script>';
echo '<link rel="stylesheet" type="text/css" href="js/jquery-timepicker-master/jquery.timepicker.css" />';

echo '<input type="text" id="hora" value="">';

echo '<script>
	$("#hora").timepicker({'step':15});
';

bottom();
?>