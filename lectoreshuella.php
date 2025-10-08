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
			<label class="col-sm-2 col-form-label">Serie</label>
			<div class="col-sm-4">
            	<input type="text" class="form-control" id="busquedaserie" placeholder="Serie">
        	</div>
        	<label class="col-sm-2 col-form-label">Plaza</label>
			<div class="col-sm-4">
            	<select name="plaza" class="form-control" data-container="body" data-live-search="true" title="Plaza" data-hide-disabled="true" data-actions-box="true" data-virtual-scroll="false" id="plaza"><option value="">Todas</option>
				<?php
				$remolque = false;
				$res2 = mysql_query("SELECT cve, numero, nombre FROM plazas ORDER BY numero, nombre");
				while($row2 = mysql_fetch_assoc($res2)){
	            	echo '<option value="'.$row2['cve'].'">'.$row2['numero'].' '.utf8_encode($row2['nombre']).'</option>';
	            }
				?>
				</select>
				<script>
					$("#plaza").selectpicker();	
				</script>
        	</div>
        </div>
        <div class="form-group row">
        	<div class="col-sm-12" align="center">
        		<button type="button" class="btn btn-primary" onClick="buscar();">
	            	Buscar
	        	</button>&nbsp;
	        	<button type="button" class="btn btn-success" onClick="atcr('lectoreshuella.php','',1,0);">
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
				<th>Serie</th>
				<th>Plaza</th>
				<th>&nbsp;</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>Serie</th>
				<th>Plaza</th>
				<th>&nbsp;</th>
			</tr>
		</tfoot>
	</table>
</div>
<script>
	var tablalistado = $('#dataTable').DataTable( {
        "ajax": {
        	url: 'lectoreshuella.php',
        	type: "POST",
        	"data": {
        		"cmd": 10,
        		"cveusuario": $('#cveusuario').val(),
        		"cveempresa": $('#cveempresa').val(),
        		'busquedaserie': $('#busquedaserie').val(),
        		'busquedaplaza': $('#busquedaplaza').val()
        	}
        },
        "processing": true,
        "serverSide": true,
        "bFilter": false,
        "order": [[0, "ASC"]],
        "columnDefs": [
        	{ className: "dt-head-center dt-body-left", "targets": 0 },
        	{ className: "dt-head-center dt-body-left", "targets": 1 },
        	{ className: "dt-head-center dt-body-center", "targets": 2 },
        	{ orderable: false, "targets": 2 }
		  ]
    } );


	function buscar(){
		tablalistado.ajax.data({
    		"cmd": 10,
    		"cveusuario": $('#cveusuario').val(),
        	"cveempresa": $('#cveempresa').val(),
        	'busquedaserie': $('#busquedaserie').val(),
        	'busquedaplaza': $('#busquedaplaza').val()
        });
        tablalistado.ajax.reload();
	}

</script>
<?php
}


if($_POST['cmd']==10){
	$columnas=array("a.serie", "CONCAT(b.numero,' ',b.nombre)");

	$orderby = "";
	foreach($_POST['order'] as $dato){
		$orderby .= ",{$columnas[$dato['column']]} {$dato['dir']}";
	}

	if($orderby == ""){
		$orderby = " ORDER BY a.serie";
	}
	else{
		$orderby = " ORDER BY ".substr($orderby, 1);
	}

	$where = "";

	if($_POST['busquedaserie']!=''){
		$where .= " AND a.serie = '{$_POST['busquedaserie']}'";
	}
	if($_POST['busquedaplaza']!=''){
		$where .= " AND a.plaza = '{$_POST['busquedaplaza']}'";
	}

	if($where != '') {
		$where = " WHERE ".substr($where, 5);
	}

	$res = mysql_query("SELECT COUNT(a.cve) as registros FROM series a{$where}");
	$registros = mysql_fetch_assoc($res);
	$resultado = array(
		'data' => array(),
		'draw'=> $_POST['draw'],
		'recordsTotal'=> $registros['registros'],
		'recordsFiltered'=> $registros['registros'],
	);
	$res = mysql_query("SELECT a.cve, a.serie, CONCAT(b.numero, ' ', b.nombre) as nomplaza FROM series a INNER JOIN plazas b ON b.cve = a.plaza{$where}{$orderby} LIMIT {$_POST['start']},{$_POST['length']}");
	$tmonto = 0;
	while($row = mysql_fetch_assoc($res)){
		$row = convertir_a_utf8($row);
		$resultado['data'][] = array(
			$row['serie'],
			utf8_encode($row['nomplaza']),
			'<span class="btn btn-circle btn-info" style="cursor:pointer;"><i class="fas fa-edit" onClick="atcr(\'lectoreshuella.php\',\'\',1,'.$row['cve'].')" title="Editar"></i></span>'.$extras,
		);
	}
	echo json_encode($resultado);

}

if($_POST['cmd']==1){
	$res = mysql_query("SELECT * FROM series WHERE cve='{$_POST['reg']}'");
	$row = mysql_fetch_assoc($res);
	$row = convertir_a_utf8($row);

?>
<div class="row justify-content-center">
	<div class="col-sm-12" align="center">
		<?php if (nivelUsuario() > 1) { ?>
		<button type="button" class="btn btn-success" onClick="atcr('lectoreshuella.php','',2,'<?php echo $_POST['reg']; ?>');">Guardar</button>
	&nbsp;&nbsp;&nbsp;
		<?php } ?>
		<button type="button" class="btn btn-primary" onClick="$('#contenedorprincipal').html('');atcr('lectoreshuella.php','',0,0);">Volver</button>
	</div>
</div><br>
<div class="row">
	<div class="col-xl-12 col-lg-12 col-md-12">
		<div class="card shadow">
			<div class="card-body">
				<div class="form-row">
			        <div class="form-group col-sm-9">
						<label for="serie">Serie</label>
			            <input type="text" class="form-control" name="serie" id="serie" value="<?php echo $row['serie'];?>">
			        </div>
			    </div>
			    <div class="form-row">
			        <div class="form-group col-sm-9">
						<label for="plaza">Plaza</label>
			            <select name="plaza" class="form-control" data-container="body" data-live-search="true" title="Plaza" data-hide-disabled="true" data-actions-box="true" data-virtual-scroll="false" id="plaza"><option value="">Seleccione</option>
						<?php
						$remolque = false;
						$res2 = mysql_query("SELECT cve, numero, nombre FROM plazas ORDER BY numero, nombre");
						while($row2 = mysql_fetch_assoc($res2)){
			            	echo '<option value="'.$row2['cve'].'"';
			            	if ($row['plaza'] == $row2['cve']){ 
			            		echo ' selected';
			            	}
			            	echo '>'.$row2['numero'].' '.utf8_encode($row2['nombre']).'</option>';
			            }
						?>
						</select>
						<script>
							$("#plaza").selectpicker();	
						</script>
			        </div>
			    </div>
			</div>
		</div>
	</div>
</div>

<?php
}

if($_POST['cmd']==2){
	$resultado = array('error' => 0, 'mensaje' => '');
	if(trim($_POST['serie'])==''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita ingresar la serie');
	}
	elseif(trim($_POST['plaza'])==''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita seleccionar la plaza');
	}
	if($resultado['error']==1){
		echo json_encode($resultado);
	}
	else{

		
		$campos = substr($campos,1);
		if($_POST['reg']>0){
			
			mysql_query("UPDATE series SET serie='{$_POST['serie']}', plaza='{$_POST['plaza']}' WHERE cve='{$_POST['reg']}'");
			$mensaje = 'Se actualizo exitosamente';
			$id = $_POST['reg'];
		}
		else{

			mysql_query("INSERT series SET serie='{$_POST['serie']}', plaza='{$_POST['plaza']}'");
			$mensaje = 'Se registro exitosamente';
		}
		
		

		echo '<script>sweetAlert("Existoso","'.$mensaje.'", "success");$("#contenedorprincipal").html("");atcr("lectoreshuella.php","",0,"0");</script>';
	}
	
}
?>