<?php
if (!isset($_SESSION)) {
  session_start();
}

if(!$_SESSION['CveUsuario'] && !$_SESSION['NomUsuario'] && !isset($_POST['loginUser']) && !isset($_POST['loginPassword']) && !isset($_POST['draw'])) {
	echo '<script>window.location="login.php";</script>';
	exit();
}


if (isset($_POST['loginUser']) && isset($_POST['loginPassword'])) {
	//Como se supone venimos de ventana de login o sesion expirada, eliminamos cualquier rastro de sesion anterior
	// Unset all of the session variables.
	$_SESSION = array();
	// Finally, destroy the session.
	session_destroy();
	$loginUsername=$_POST['loginUser'];
	$password=$_POST['loginPassword'];
	$redirectLoginSuccess = "principal.php";
	$redirectLoginFailed = "login.php?ErrLogUs=true";
	//Hacemos uso de la funcion GetSQLValueString para evitar la inyeccion de SQL
	$LoginRS_query = sprintf("SELECT * FROM usuarios WHERE usuario = %s AND password=%s AND estatus='A'",
			  GetSQLValueString($loginUsername, "text"), GetSQLValueString($password, "text"));

	$LoginRS = mysql_query($LoginRS_query);

	$loginFoundUser = mysql_num_rows($LoginRS);
	
	if ($loginFoundUser) {
		$Usuario=mysql_fetch_assoc($LoginRS);
		$ip=getRealIP();
		$fechahora=date( "Y-m-d H:i:s" , strtotime ( "0 hour" , strtotime(date("Y-m-d H:i:s")) ) );
		mysql_query("INSERT registros_sistema SET usuario='".$Usuario['cve']."',entrada=NOW(),ip='$ip'");
		$reg_sistema=mysql_insert_id($MySQL);

		//Creamos la sesion

		session_start();		

		

		//Creamos las variables de sesion del usuario en cuestion

		$_SESSION['CveUsuario'] = $Usuario['cve'];

		$_SESSION['NomUsuario'] = $Usuario['nombre'];
		
		$_SESSION['TipoUsuario'] = $Usuario['tipo'];

		$_SESSION['NickUsuario'] = $Usuario['usuario'];
				
		$_SESSION['reg_sistema'] = $reg_sistema;
				

		header("Location: " . $redirectLoginSuccess );

	} else {
	
		
			header("Location: " . $redirectLoginFailed);

	}

}
?>