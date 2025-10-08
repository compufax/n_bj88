<?php
include("cnx_db.php")
mysql_query("INSERT registros_sistemamov SET cveacceso='".$_POST['registro']."',usuario='".$_POST['cveusuario']."',menu='".$_POST['menu']."',fechahora=NOW()");
?>