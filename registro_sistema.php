<?php
require_once('cnx_db.php');
require_once('globales.php'); 
require_once('validarloging.php');

if($_POST['cmd']==0){
?>
<div class="row justify-content-center">
	<div class="col-xl-12 col-lg-12 col-md-12">
		<div class="form-group row">
			<label class="col-sm-2 col-form-label">Fecha Inicial</label>
			<div class="col-sm-4">
            	<input type="date" class="form-control" id="busquedafechaini" placeholder="Fecha Inicial" value="<?php echo date('Y-m-d');?>">
        	</div>
			<label class="col-sm-2 col-form-label">Fecha Final</label>
			<div class="col-sm-4">
            	<input type="date" class="form-control" id="busquedafechafin" placeholder="Fecha Final" value="<?php echo date('Y-m-d');?>">
        	</div>
        </div>
        <div class="form-group row">
			<label class="col-sm-2 col-form-label">Usuario</label>
			<div class="col-sm-4">
            	<input type="text" class="form-control" id="busquedausuario" placeholder="Usuario">
        	</div>
        </div>
        <div class="form-group row">
        	<div class="col-sm-12" align="center">
	        	<button type="button" class="btn btn-primary" onClick="buscar();">
	            	Buscar
	        	</button>
        	</div>
        </div>
    </div>
</div>
<div class="table-responsive">
	<table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
    	<thead>
			<tr>
				<th>Usuario</th>
				<th>Fecha</th>
				<th>IP</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>Usuario</th>
				<th>Fecha</th>
				<th>IP</th>
			</tr>
		</tfoot>
	</table>
</div>
<script>

	var tablalistado = $('#dataTable').DataTable( {
        "ajax": {
        	url: 'registro_sistema.php',
        	type: "POST",
        	"data": {
        		"cmd": 10,
        		"busquedafechaini": $("#busquedafechaini").val(),
        		"busquedafechafin": $("#busquedafechafin").val(),
        		'usuario': $('#busquedausuario').val(),
        		'cvemenu': $('#cvemenu').val()
        	}
        },
        "processing": true,
        "serverSide": true,
        "bFilter": false,
        "order": [[1, "DESC"]],
        "bPaginate": true,
        "columnDefs": [
        	{ className: "dt-head-center dt-body-left", "targets": 0 },
        	{ className: "dt-head-center dt-body-center", "targets": 1 },
        	{ className: "dt-head-center dt-body-center", "targets": 2 }
		  ]
    } );
	function buscar(){
		tablalistado.ajax.data({
    		"cmd": 10,
    		"busquedafechaini": $("#busquedafechaini").val(),
    		"busquedafechafin": $("#busquedafechafin").val(),
    		'usuario': $('#busquedausuario').val(),
        	'cvemenu': $('#cvemenu').val()
        });
        tablalistado.ajax.reload();
	}
</script>
<?php
}

if($_POST['cmd']==10){

	$columnas=array('b.usuario', "a.entrada", "a.ip");

	$orderby = "";
	foreach($_POST['order'] as $dato){
		$orderby .= ",{$columnas[$dato['column']]} {$dato['dir']}";
	}

	if($orderby == ""){
		$orderby = " ORDER BY a.entrada";
	}
	else{
		$orderby = " ORDER BY ".substr($orderby, 1);
	}

	$where = "";

	if($_POST['busquedausuario'] != 0){
		$where .= " AND b.usuario = '{$_POST['busquedausuario']}'";
	}

	if($_POST['busquedafechaini'] != ''){
		$where .= " AND a.entrada >= '{$_POST['busquedafechaini']} 00:00:00'";
	}

	if($_POST['busquedafechafin'] != ''){
		$where .= " AND a.entrada <= '{$_POST['busquedafechafin']} 23:59:59'";
	}

	if($where != ""){
		$where = " WHERE ".substr($where, 5);
	} 
	$res = mysql_query("SELECT COUNT(a.cve) as registros FROM registros_sistema a INNER JOIN usuarios b ON b.cve = a.usuario{$where}");
	echo $MySQL->error;
	$registros = mysql_fetch_assoc($res);
	$resultado = array(
		'data' => array(),
		'draw'=> $_POST['draw'],
		'recordsTotal'=> $registros['registros'],
		'recordsFiltered'=> $registros['registros']
	);
	$res = mysql_query("SELECT b.usuario, a.entrada, a.ip FROM registros_sistema a INNER JOIN usuarios b ON b.cve = a.usuario{$where}{$orderby} LIMIT {$_POST['start']},{$_POST['length']}");
	$tmonto = 0;
	while($row = mysql_fetch_assoc($res)){

		$resultado['data'][] = array(
			$row['usuario'],
			mostrar_fechas(substr($row['entrada'],0,10)).' '.substr($row['entrada'],11),
			$row['ip']
		);
	}
	echo json_encode($resultado);

}
?>