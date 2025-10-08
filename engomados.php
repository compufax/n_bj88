<?php
error_reporting(E_ERROR | E_PARSE);
require_once('cnx_db.php');
require_once('globales.php');
require_once('validarloging.php');

if($_POST['cmd']==0){
?>

<div class="row justify-content-center">
	<div class="col-xl-12 col-lg-12 col-md-12">
		<div class="form-group row">
			<label class="col-sm-2 col-form-label">Nombre</label>
			<div class="col-sm-4">
            	<input type="text" class="form-control" id="busquedanombre" placeholder="Nombre">
        	</div>
        </div>
        <div class="form-group row">
        	<div class="col-sm-12" align="center">
        		<button type="button" class="btn btn-primary" onClick="buscar();">
	            	Buscar
	        	</button>&nbsp;
	        	<button type="button" class="btn btn-success" onClick="atcr('engomados.php','',1,0);">
	            	Nuevo
	        	</button>
        	</div>
        </div>
    </div>
</div>
<div class="table-responsive">
	<table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
    	<thead>
			<tr>
				<th>Nombre</th>
				<th>&nbsp;</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>Nombre</th>
				<th>&nbsp;</th>
			</tr>
		</tfoot>
	</table>
</div>
<script>
	var tablalistado = $('#dataTable').DataTable( {
        "ajax": {
        	url: 'engomados.php',
        	type: "POST",
        	"data": {
        		"cmd": 10,
        		"cveusuario": $('#cveusuario').val(),
        		"cveempresa": $('#cveempresa').val(),
        		'busquedanombre': $('#busquedanombre').val()
        	}
        },
        "processing": true,
        "serverSide": true,
        "bFilter": false,
        "order": [[0, "ASC"]],
        "columnDefs": [
        	{ className: "dt-head-center dt-body-left", "targets": 0 },
        	{ className: "dt-head-center dt-body-center", "targets": 1 },
        	{ orderable: false, "targets": 1 }
		  ]
    } );


	function buscar(){
		tablalistado.ajax.data({
    		"cmd": 10,
    		"cveusuario": $('#cveusuario').val(),
        	"cveempresa": $('#cveempresa').val(),
        	'busquedanombre': $('#busquedanombre').val()
        });
        tablalistado.ajax.reload();
	}

</script>
<?php
}


if($_POST['cmd']==10){
	$columnas=array("nombre");

	$orderby = "";
	foreach($_POST['order'] as $dato){
		$orderby .= ",{$columnas[$dato['column']]} {$dato['dir']}";
	}

	if($orderby == ""){
		$orderby = " ORDER BY nombre";
	}
	else{
		$orderby = " ORDER BY ".substr($orderby, 1);
	}

	$where = "";

	if($_POST['busquedanombre']!=''){
		$where .= " WHERE nombre LIKE '%{$_POST['busquedanombre']}%'";
	}

	$res = mysql_query("SELECT COUNT(cve) as registros FROM engomados{$where}");
	$registros = mysql_fetch_assoc($res);
	$resultado = array(
		'data' => array(),
		'draw'=> $_POST['draw'],
		'recordsTotal'=> $registros['registros'],
		'recordsFiltered'=> $registros['registros'],
	);
	$res = mysql_query("SELECT cve, nombre FROM engomados{$where}{$orderby} LIMIT {$_POST['start']},{$_POST['length']}");
	$tmonto = 0;
	while($row = mysql_fetch_assoc($res)){
		$row = convertir_a_utf8($row);
		$resultado['data'][] = array(
			utf8_encode($row['nombre']),
			'<span class="btn btn-circle btn-info" style="cursor:pointer;"><i class="fas fa-edit" onClick="atcr(\'engomados.php\',\'\',1,'.$row['cve'].')" title="Editar"></i></span>'.$extras,
		);
	}
	echo json_encode($resultado);

}

if($_POST['cmd']==1){
	$res = mysql_query("SELECT * FROM engomados WHERE cve='{$_POST['reg']}'");
	$row = mysql_fetch_assoc($res);
	$row = convertir_a_utf8($row);

?>
<div class="row justify-content-center">
	<div class="col-sm-12" align="center">
		<?php if (nivelUsuario() > 1) { ?>
		<button type="button" class="btn btn-success" onClick="atcr('engomados.php','',2,'<?php echo $_POST['reg']; ?>');">Guardar</button>
	&nbsp;&nbsp;&nbsp;
		<?php } ?>
		<button type="button" class="btn btn-primary" onClick="$('#contenedorprincipal').html('');atcr('engomados.php','',0,0);">Volver</button>
	</div>
</div><br>
<div class="row">
	<div class="col-xl-12 col-lg-12 col-md-12">
		<div class="card shadow">
			<div class="card-body">
				<div class="form-row">
			        <div class="form-group col-sm-9">
						<label for="nombre">Nombre</label>
			            <input type="text" class="form-control" name="nombre" id="nombre" value="<?php echo $row['nombre'];?>">
			        </div>
			    </div>
			</div>
		</div>
		<div class="card shadow" id="divpermisos">
			<div class="card-header">
				<h6 class="m-0 font-weight-bold text-secondary">Configuraci&oacute;n Plazas</h6>
			</div>
			<div class="card-body">
				<div class="row">
					<div class="col-sm-6 text-primary">Plaza</div>
					<div class="col-sm-1 text-primary" align="center">Venta</div>
					<div class="col-sm-1 text-primary" align="center">Entrega</div>
					<div class="col-sm-2 text-primary" align="center">Precio Venta</div>
					<div class="col-sm-2 text-primary" align="center">Precio Compra</div>
				</div>
				<?php 
				$res1 = mysql_query("SELECT a.cve, a.numero, a.nombre, c.precio_compra, c.precio, c.venta, c.entrega FROM plazas a LEFT JOIN engomados_plazas c ON a.cve = c.plaza AND c.engomado='{$_POST['reg']}' WHERE a.estatus != 'I'");
				while($row1 = mysql_fetch_array($res1)){
				?>
				<div class="form-row">
					<div class="col-sm-6"><?php echo utf8_encode($row1['numero']);?></div>
					<div class="col-sm-1" align="center"><input type="checkbox" id="cventa_<?php echo $row1['cve'];?>" class="form-control" onClick="cambiar_check('cventa_<?php echo $row1['cve'];?>')" onChange="cambiar_check('cventa_<?php echo $row1['cve'];?>')"<?php if($row1['venta']==1){ ?> checked<?php } ?>>
						<input type="hidden" class="form-control" id="cventa_<?php echo $row1['cve'];?>_h" name="plazas[<?php echo $row1['cve'];?>][venta]" value="<?php echo $row1['venta'];?>"></div>
					<div class="col-sm-1" align="center"><input type="checkbox" id="centrega_<?php echo $row1['cve'];?>" class="form-control" onClick="cambiar_check('centrega_<?php echo $row1['cve'];?>')" onChange="cambiar_check('centrega_<?php echo $row1['cve'];?>')"<?php if($row1['entrega']==1){ ?> checked<?php } ?>>
						<input type="hidden" class="form-control" id="centrega_<?php echo $row1['cve'];?>_h" name="plazas[<?php echo $row1['cve'];?>][entrega]" value="<?php echo $row1['entrega'];?>"></div>
					<div class="col-sm-2" align="center"><input type="text" class="form-control" name="plazas[<?php echo $row1['cve'];?>][precio]" value="<?php echo $row1['precio']; ?>"></div>
					<div class="col-sm-2" align="center"><input type="text" class="form-control" name="plazas[<?php echo $row1['cve'];?>][precio_compra]" value="<?php echo $row1['precio_compra']; ?>"></div>
				</div>
				<?php
				}
				?>
			</div>
		</div>
	</div>
</div>

<?php
}

if($_POST['cmd']==2){
	$resultado = array('error' => 0, 'mensaje' => '');
	if(trim($_POST['nombre'])==''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita ingresar el nombre');
	}
	if($resultado['error']==1){
		echo json_encode($resultado);
	}
	else{

		
		$campos = substr($campos,1);
		if($_POST['reg']>0){
			
			mysql_query("UPDATE engomados SET nombre='{$_POST['nombre']}' WHERE cve='{$_POST['reg']}'");
			$mensaje = 'Se actualizo exitosamente';
			$id = $_POST['reg'];
		}
		else{

			mysql_query("INSERT engomados SET nombre='{$_POST['nombre']}'");
			$id = mysql_insert_id();
			$mensaje = 'Se registro exitosamente';
		}

		if ($id>0){
			foreach ($_POST['plazas'] as $cveplaza => $datos) {
				$res1 = mysql_query("SELECT cve FROM engomados_plazas WHERE engomado='{$id}' AND plaza='{$cveplaza}'");
				if($row1 = mysql_fetch_assoc($res1)){
					mysql_query("UPDATE engomados_plazas SET venta='{$datos['venta']}', entrega='{$datos['entrega']}', precio='{$datos['precio']}', precio_compra = '{$datos['precio_compra']}' WHERE cve = '{$row1['cve']}'");
				}
				else{
					mysql_query("INSERT engomados_plazas SET engomado='{$id}', plaza='{$cveplaza}', venta='{$datos['venta']}', entrega='{$datos['entrega']}', precio='{$datos['precio']}', precio_compra = '{$datos['precio_compra']}'");
				}
			}
		}
		
		

		echo '<script>sweetAlert("Existoso","'.$mensaje.'", "success");$("#contenedorprincipal").html("");atcr("engomados.php","",0,"0");</script>';
	}
	
}
?>