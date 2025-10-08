<?php
require_once('cnx_db.php');
require_once('globales.php'); 

if($_POST['cmd']==20){
	require_once("funciones_timbrado.php");
	$resultado = timbrar($_POST['plaza'], $_POST['factura']);
	$resultado['mensaje'] = utf8_encode($resultado['mensaje']);
	echo $resultado['mensaje'];
	exit();
}
require_once('validarloging.php');
if($_POST['cmd']==0){
?>


<div class="row justify-content-center">
	<div class="col-xl-12 col-lg-12 col-md-12">
		
        <div class="form-group row">
        	<div class="col-sm-12" align="center">
        		<div class="btn-group">
		        	<button type="button" class="btn btn-primary" onClick="buscar();">
		            	Buscar
		        	</button>
		        </div>
        	</div>
        </div>
    </div>
</div>
<div class="table-responsive">
	<table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
    	<thead>
			<tr>
				<th>Plaza</th>
				<th>Folio</th>
				<th>Fecha</th>
				<th>Tipo Factura</th>
				<th>Cliente</th>
				<th>RFC Cliente</th>
				<th>Total</th>
				<th>Usuario</th>
				<th>&nbsp;</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>Plaza</th>
				<th>Folio</th>
				<th>Fecha</th>
				<th>Tipo Factura</th>
				<th>Cliente</th>
				<th>RFC Cliente</th>
				<th>Total<br><span id="ttotal" style="text-align: right;"></span></th>
				<th>Usuario</th>
				<th>&nbsp;</th>
			</tr>
		</tfoot>
	</table>
</div>
<script>

	function descargar(tipo){
		var error = 0;
		if(tipo==1){
			if(!$('.chks').is(':checked')){
				sweetAlert('', 'Necesita seleccionar al menos una factura', 'warning');
				error=1;
			}
		}
		if(error == 0){
			atcr("facturas.php", "_blank", 200, tipo);
		}
	}

	var tablalistado = $('#dataTable').DataTable( {
        "ajax": {
        	url: 'facturas_sintimbrar.php',
        	type: "POST",
        	"data": {
        		"cmd": 10,
        		'cveusuario': $('#cveusuario').val(),
        		'cveplaza': $('#cveplaza').val(),
        		'cvemenu': $('#cvemenu').val()
        	},
        	fncallback: function(json){
        		$('#ttotal').html(json.total);
        	}
        },
        "processing": true,
        "serverSide": true,
        "bFilter": false,
        "order": [[2, "DESC"]],
        "bPaginate": true,
        "columnDefs": [
        	{ className: "dt-head-center dt-body-left", "targets": 0 },
        	{ className: "dt-head-center dt-body-left", "targets": 1 },
        	{ className: "dt-head-center dt-body-center", "targets": 2 },
        	{ className: "dt-head-center dt-body-left", "targets": 3 },
        	{ className: "dt-head-center dt-body-left", "targets": 4 },
        	{ className: "dt-head-center dt-body-left", "targets": 5 },
        	{ className: "dt-head-center dt-body-right", "targets": 6 },
        	{ className: "dt-head-center dt-body-left", "targets": 7 },
        	{ className: "dt-head-center dt-body-center", "targets": 8 },
        	{ orderable: false, "targets": 8 }
		  ]
    } );
	function buscar(){
		tablalistado.ajax.data({
    		"cmd": 10,
    		'cveusuario': $('#cveusuario').val(),
    		'cveplaza': $('#cveplaza').val(),
    		'cvemenu': $('#cvemenu').val()
        });
        tablalistado.ajax.reload();
	}

	function timbrar(placa, factura){
		waitingDialog.show();
		$.ajax({
			url: 'facturas_sintimbrar.php',
			type: "POST",
			data: {
				cmd: 20,
				cveplaza: $('#cveplaza').val(),
				plaza: plaza,
				factura: factura
			},
			success: function(data) {
				waitingDialog.hide();
				sweetAlert('', data, 'success');
				buscar();
			}
		});
	}

	
</script>
<?php
}

if($_POST['cmd']==10){
	$columnas=array("e.numero", "CONCAT(a.folio, ' ', a.serie)", "CONCAT(a.fecha, ' ', a.hora)", 'a.tipo_pag', "b.nombre", 'b.rfc', "IF(a.estatus='C', 0, a.total)", "IF(a.estatus='C', 'Cancelado', IF(a.respuesta1='', 'Pendiente de Timbrar', 'Timbrado'))");

	$orderby = "";
	foreach($_POST['order'] as $dato){
		$orderby .= ",{$columnas[$dato['column']]} {$dato['dir']}";
	}

	if($orderby == ""){
		$orderby = " ORDER BY CONCAT(a.serie,' ',a.cve)";
	}
	else{
		$orderby = " ORDER BY ".substr($orderby, 1);
	}

	$where = " WHERE a.respuesta1='' AND a.estatus!='C'";
	

	$nivelUsuario = nivelUsuario();
	$res = mysql_query("SELECT COUNT(a.cve) as registros, SUM(IF(a.estatus!='C', a.total, 0)) as total FROM facturas a INNER JOIN clientes b ON b.cve = a.cliente{$where}");
	$registros = mysql_fetch_assoc($res);
	$resultado = array(
		'data' => array(),
		'draw'=> $_POST['draw'],
		'recordsTotal'=> $registros['registros'],
		'recordsFiltered'=> $registros['registros'],
		'total' => $registros['total']
	);
	$res = mysql_query("SELECT e.numero, a.plaza, a.cve, a.serie, a.folio, a.fecha, a.hora, IF(a.tipo_pag=0, 'Contado', 'Credito') as nomtipopag, b.nombre as nomcliente, b.rfc, IF(a.estatus='C', 0, a.total) as total, d.usuario, a.estatus, a.respuesta1 FROM facturas a INNER JOIN clientes b ON b.cve = a.cliente INNER JOIN plazas e ON e.cve = a.plaza LEFT JOIN usuarios d ON d.cve = a.usuario{$where}{$orderby} LIMIT {$_POST['start']},{$_POST['length']}");
	$tmonto = 0;
	while($row = mysql_fetch_assoc($res)){
		$extras = '<i class="fas fa-cloud-upload-alt fa-sm fa-fw mr-2 text-primary" style="cursor:pointer;" onClick="timbrar('.$row['cve'].')" title="Timbrar"></i>';
		
		

		$resultado['data'][] = array(
			utf8_encode($row['numero']),
			$row['serie'].' '.$row['folio'],
			mostrar_fechas($row['fecha']).' '.$row['hora'],
			utf8_encode($row['nomtipopag']),
			utf8_encode($row['nomcliente']),
			utf8_encode($row['rfc']),
			number_format($row['total'],2),
			 $row['usuario'],
			$extras,
		);
	}
	echo json_encode($resultado);

}

?>