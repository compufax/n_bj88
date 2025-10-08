<?php
require_once('cnx_db.php');
require_once('globales.php'); 
require_once('validarloging.php');

if($_POST['cmd']==0){
?>

<div class="row">
	<div class="col-xl-12 col-lg-12 col-md-12">
		<div class="form-group row">
			<label class="col-sm-1 col-form-label">Nombre</label>
			<div class="col-sm-5">
            	<input type="text" class="form-control" id="busquedanombre" placeholder="Nombre">
        	</div>
        	<label class="col-sm-1 col-form-label">RFC</label>
			<div class="col-sm-5">
            	<input type="text" class="form-control" id="busquedarfc" placeholder="RFC">
        	</div>
        </div>
        <div class="form-group row">
        	<div class="col-sm-12" align="center">
	        	<button type="button" class="btn btn-primary" onClick="buscar();">
	            	Buscar
	        	</button>
	        	&nbsp;
	        	<button type="button" class="btn btn-primary" onClick="atcr('clientes.php','',1,0);">
	            	Nuevo
	        	</button>
        	</div>
        </div>
    </div>
</div>
<div class="card shadow mb-4">
	<div class="card-body">
        <div class="table-responsive">
        	<table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
	        	<thead>
					<tr>
						<th>Nombre</th>
						<th>RFC</th>
						<th>C&oacute;digo Postal</th>
						<th>E-mail</th>
						<th>Uso de CFDI</th>
						<th>R&eacute;gimen Fiscal</th>
						<th>&nbsp;</th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<th>Nombre</th>
						<th>RFC</th>
						<th>C&oacute;digo Postal</th>
						<th>E-mail</th>
						<th>Uso de CFDI</th>
						<th>R&eacute;gimen Fiscal</th>
						<th>&nbsp;</th>
					</tr>
				</tfoot>
			</table>
        </div>
    </div>
</div>
<script>
	var tablalistado = $('#dataTable').DataTable( {
        "ajax": {
        	url: 'clientes.php',
        	type: "POST",
        	"data": {
        		"cmd": 10,
        		"busquedanombre": $("#busquedanombre").val(),
        		"busquedarfc": $("#busquedarfc").val(),
        		"cveusuario": $('#cveusuario').val(),
        		'cveplaza': '<?php echo $_POST['cveplaza'];?>'
        	}
        },
        "processing": true,
        "serverSide": true,
        "bFilter": false,
        "order": [[0, "ASC"]],
        "columnDefs": [
        	{ className: "dt-head-center dt-body-left", "targets": 0 },
        	{ className: "dt-head-center dt-body-left", "targets": 1 },
        	{ className: "dt-head-center dt-body-left", "targets": 2 },
        	{ className: "dt-head-center dt-body-left", "targets": 3 },
        	{ className: "dt-head-center dt-body-left", "targets": 4 },
        	{ className: "dt-head-center dt-body-left", "targets": 5 },
        	{ className: "dt-head-center dt-body-center", "targets": 6 },
        	{ orderable: false, "targets": 6 }
		  ]
    } );
	function buscar(){
		tablalistado.ajax.data({
    		"cmd": 10,
    		"busquedanombre": $("#busquedanombre").val(),
        	"busquedarfc": $("#busquedarfc").val(),
        	"cveusuario": $('#cveusuario').val(),
        	'cveplaza': '<?php echo $_POST['cveplaza'];?>'
        });
        tablalistado.ajax.reload();
	}
</script>
<?php
}

if($_POST['cmd']==10){

	$columnas=array('a.nombre', 'a.rfc', 'a.codigopostal', 'a.email', 'b.nombre', 'c.nombre');

	$orderby = "";
	foreach($_POST['order'] as $dato){
		$orderby .= ",{$columnas[$dato['column']]} {$dato['dir']}";
	}

	if($orderby == ""){
		$orderby = " ORDER BY a.nombre";
	}
	else{
		$orderby = " ORDER BY ".substr($orderby, 1);
	}

	$where = " WHERE a.plaza='{$_POST['cveplaza']}'";

	if($_POST['busquedanombre'] != ''){
		$where .= " AND a.nombre LIKE '%{$_POST['busquedanombre']}%'";
	}

	if($_POST['busquedarfc']!=''){
		$where .= " AND a.rfc='{$_POST['busquedarfc']}'";
	}

	$res = mysql_query("SELECT COUNT(cve) as registros FROM clientes a{$where}");
	$registros = mysql_fetch_assoc($res);
	$resultado = array(
		'data' => array(),
		'draw'=> $_POST['draw'],
		'recordsTotal'=> $registros['registros'],
		'recordsFiltered'=> $registros['registros'],
	);
	$res = mysql_query("SELECT a.cve, a.nombre, a.rfc, a.codigopostal, a.email, b.nombre as nomusocfdi, c.nombre as nomregimen FROM clientes a LEFT JOIN usocfdi_sat b ON b.cve = a.usocfdi LEFT JOIN regimen_sat c ON c.clave = a.regimensat{$where}{$orderby} LIMIT {$_POST['start']},{$_POST['length']}");
	$tmonto = 0;
	while($row = mysql_fetch_assoc($res)){

		$resultado['data'][] = array(
			utf8_encode($row['nombre']),
			utf8_encode($row['rfc']),
			utf8_encode($row['codigopostal']),
			utf8_encode($row['email']),
			utf8_encode($row['nomusocfdi']),
			utf8_encode($row['nomregimen']),
			'<span class="btn btn-circle btn-info" style="cursor:pointer;"><i class="fas fa-edit" onClick="atcr(\'clientes.php\',\'\',1,'.$row['cve'].')" title="Editar"></i></span>'
		);
	}
	echo json_encode($resultado);

}


if($_POST['cmd']==1){

	$res = mysql_query("SELECT * FROM clientes WHERE cve='{$_POST['reg']}'");
	$row = mysql_fetch_array($res);
	$row = convertir_a_utf8($row);
?>
<div class="row justify-content-center">
	<div class="col-sm-12" align="center">
		<button type="button" class="btn btn-success" onClick="atcr('clientes.php','',2,'<?php echo $_POST['reg']; ?>');">Guardar</button>
	&nbsp;&nbsp;&nbsp;
		<button type="button" class="btn btn-primary" onClick="atcr('clientes.php','',0,0);">Volver</button>
	</div>
</div><br>
<div class="row">
	<div class="col-xl-12 col-lg-12 col-md-12">
		<div class="card shadow">
			<div class="card-header">
				<h6 class="m-0 font-weight-bold text-secondary">Datos</h6>
			</div>
			<div class="card-body">
				<div class="form-row">
					<div class="form-group col-sm-9">
						<label for="nombre">Nombre</label>
			            <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo $row['nombre'];?>" >
			        </div>
			        <div class="form-group col-sm-3">
						<label for="rfc">RFC</label>
			            <input type="text" class="form-control" id="rfc" name="rfc" value="<?php echo $row['rfc'];?>" >
			        </div>
			    </div>		        
		        <div class="form-row">
			        
			        <div class="form-group col-sm-6">
						<label for="email">E-mail</label>
			            <input type="email" class="form-control" id="email" name="camposi[email]" value="<?php echo $row['email'];?>">
			        </div>
			   
			        <div class="form-group col-sm-2">
						<label for="codigo_postal">C&oacute;digo Postal</label>
			            <input type="text" class="form-control" id="codigo_postal" name="camposi[codigopostal]" value="<?php echo $row['codigopostal'];?>">
			        </div>
			        <div class="form-group col-sm-5">
						<label for="usocfdi">Uso del CFDI</label>
			            <select name="camposi[usocfdi]" id="usocfdi" class="form-control">
			            <?php
			            $res1 = mysql_query("SELECT cve, nombre FROM usocfdi_sat ORDER BY nombre");
			            while($row1 = mysql_fetch_assoc($res1)){
			            	echo '<option value="'.$row1['cve'].'"';
			            	if($row['usocfdi'] == $row1['cve']) echo ' selected';
			            	echo '>'.utf8_encode($row1['nombre']).'</option>';
			            }
			            ?>
			            </select>
			        </div>
			    
			    </div>
			    <div class="form-row">
			    	<div class="form-group col-sm-5">
						<label for="regimensat">R&eacute;gimen Fiscal</label>
			            <select name="camposi[regimensat]" id="regimensat" class="form-control">
			            <?php
			            $res1 = mysql_query("SELECT clave, nombre FROM regimen_sat ORDER BY nombre");
			            while($row1 = mysql_fetch_assoc($res1)){
			            	echo '<option value="'.$row1['clave'].'"';
			            	if($row['regimensat'] == $row1['clave']) echo ' selected';
			            	echo '>'.utf8_encode($row1['nombre']).'</option>';
			            }
			            ?>
			            </select>
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
	if(trim($_POST['rfc'])==''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita ingresar el RFC');
	}
	elseif(trim($_POST['nombre'])==''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita ingresar el nombre');
	}
	elseif(trim($_POST['camposi']['email'])==''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita ingresar el email');
	}
	elseif(trim($_POST['camposi']['codigopostal'])==''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita ingresar el código postal');
	}
	elseif(trim($_POST['camposi']['usocfdi'])==''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita ingresar el uso del cfdi');
	}
	elseif(trim($_POST['camposi']['regimensat'])==''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita ingresar el régimen fiscal');
	}
	elseif($_POST['rfc']!=''){
		$res = mysql_query("SELECT cve FROM clientes WHERE plaza='{$_POST['cveplaza']}' AND rfc='{$_POST['rfc']}' AND cve != '{$_POST['reg']}'");
		if ($row = mysql_fetch_assoc($res)) {
			$resultado = array('error' => 1, 'mensaje' => 'El RFC ya esta registrado');
		}
	}
	if($resultado['error']==1){
		$resultado['mensaje'] = utf8_encode($resultado['mensaje']);
		echo json_encode($resultado);
	}
	else{
	
		$campos="";
		foreach($_POST['camposi'] as $k=>$v){
			$campos.=",{$k}='{$v}'";
		}	
		
		if($_POST['reg']>0){
			mysql_query("UPDATE clientes SET nombre='".addslashes($_POST['nombre'])."', rfc='{$_POST['rfc']}'{$campos} WHERE cve = '{$_POST['reg']}'");
		}
		else{
			mysql_query("INSERT clientes SET plaza='{$_POST['cveplaza']}', usuario='{$_POST['cveusuario']}', fechayhora=NOW(), nombre='".addslashes($_POST['nombre'])."', rfc='{$_POST['rfc']}'{$campos}");
			$proveedor_id = mysql_insert_id($MySQL);
		}

	

		echo '<script>atcr("clientes.php","",0,0);</script>';
	}
}
?>