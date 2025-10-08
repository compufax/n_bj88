<?php
include("cnx_db.php");
include("globales.php");


$res = mysql_query("SELECT * FROM menu WHERE cve='{$_POST['menu']}'");
$row = mysql_fetch_assoc($res);

if($row['plazaobligatoria']==1 && ($_POST['cveplaza']==0 || $_POST['cveplaza']=='')){
	echo 'error';
}
else{
	mysql_query("INSERT registros_sistemamov SET cveacceso='".$_POST['registro']."',usuario='".$_POST['cveusuario']."',menu='".$_POST['menu']."'");
}
?>