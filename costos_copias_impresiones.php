<?php
require_once('cnx_db.php');
require_once('globales.php'); 
require_once('validarloging.php');
if($_POST['cmd']==0){
	$res = mysql_query("SELECT * FROM costos_copias_impresiones");
	$row = mysql_fetch_assoc($res);
?>
<div class="row justify-content-center">
	<div class="col-sm-12" align="center">
	<?php if (nivelUsuario() > 1) { ?>
		<button type="button" class="btn btn-success" onClick="atcr('costos_copias_impresiones.php','',2, '');">Guardar</button>
	<?php } ?>
	</div>
</div><br><br>
<div class="row justify-content-center">
	<div class="col-xl-6 col-lg-6 col-md-6">
		<div class="form-group">
			<label for="costo">Costo Copias</label>
            <input type="number" class="form-control" id="costo" name="costo" value="<?php echo $row['copias'];?>">
        </div>
    </div>
</div>
<?php
}

if($_POST['cmd']==2){
	$resultado = array('error' => 0, 'mensaje' => '');
	$res = mysql_query("SELECT cve FROM costos_copias_impresiones");
	if($row = mysql_fetch_array($res)){
		mysql_query("UPDATE costos_copias_impresiones SET copias = '{$_POST['costo']}' WHERE cve='{$row['cve']}'");
	}
	else{
		mysql_query("INSERT costos_copias_impresiones SET copias = '{$_POST['costo']}'");
	}
	echo '<script>sweetAlert("Existoso","Se actualizó de forma correcta el costo", "success");atcr("costos_copias_impresiones.php","",0,"");</script>';
}
?>