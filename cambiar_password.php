<?php
require_once('cnx_db.php');
require_once('globales.php'); 
require_once('validarloging.php');
if($_POST['cmd']==0){

?>
<div class="row justify-content-center">
	<div class="col-sm-12" align="center">
		<button type="button" class="btn btn-success" onClick="atcr('cambiar_password.php','',2,'<?php echo $_POST['cveusuario']; ?>');">Guardar</button>
	</div>
</div><br><br>
<div class="row justify-content-center">
	<div class="col-xl-6 col-lg-6 col-md-6">
		<div class="form-group">
			<label for="pass1">Contrase&ntilde;a anterior</label>
            <input type="password" class="form-control" id="pass1" name="pass1" value="">
        </div>
        <div class="form-group">
			<label for="pass2">Nueva contrase&ntilde;a</label>
            <input type="password" class="form-control" id="pass2" name="pass2" value="">
        </div>
        <div class="form-group">
			<label for="pass3">Confirmaci&oacute;n de contrase&ntilde;a</label>
            <input type="password" class="form-control" id="pass3" name="pass3" value="">
        </div>
    </div>
</div>
<?php
}

if($_POST['cmd']==2){
	$resultado = array('error' => 0, 'mensaje' => '');
	$res = mysql_query("SELECT * FROM usuarios WHERE cve='{$_POST['reg']}'");
	$row = mysql_fetch_array($res);
	if(trim($_POST['pass1'])==''){
		$resultado = array('error' => 1, 'mensaje' => utf8_encode('Necesita ingresar la contraseña anterior'));
	}
	elseif($_POST['pass1'] != $row['password']){
		$resultado = array('error' => 1, 'mensaje' => utf8_encode('La contraseña anterior es incorrecta'));
	}
	elseif(trim($_POST['pass2'])==''){
		$resultado = array('error' => 1, 'mensaje' => utf8_encode('Necesita ingresar la contraseña nueva'));
	}
	elseif(trim($_POST['pass3'])==''){
		$resultado = array('error' => 1, 'mensaje' => utf8_encode('Necesita ingresar la confirmación de contraseña'));
	}
	elseif(trim($_POST['pass2'])!=trim($_POST['pass3'])){
		$resultado = array('error' => 1, 'mensaje' => utf8_encode('No son iguales la nueva contraseña y la confirmación'));
	}
	if($resultado['error']==1){
		echo json_encode($resultado);
	}
	else{
		$mensaje = utf8_encode("Se actualizó de forma correcta la contraseña");
		mysql_query("UPDATE usuarios SET password='{$_POST['pass2']}' WHERE cve='{$_POST['reg']}'");
		echo '<script>sweetAlert("Existoso", "'.$mensaje.'", "success");atcr("cambiar_password.php","",0,"'.$_POST['cveusuario'].'");</script>';
	}
}
?>