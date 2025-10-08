<?php
error_reporting(E_ERROR | E_PARSE);
require_once('cnx_db.php');
require_once('globales.php');

if($_GET['cmd']==101){
	require_once("numlet.php");
	$res=mysql_query("SELECT * FROM pagos_caja WHERE plaza='{$_GET['cveplaza']}' AND cve='{$_GET['cvepago']}'");
	$row=mysql_fetch_array($res);
	$texto=chr(27)."@";
	$texto.='|';
	$resPlaza = mysql_query("SELECT nombre, numero FROM plazas WHERE cve='{$row['plaza']}'");
	$rowPlaza = mysql_fetch_array($resPlaza);
	$resPlaza2 = mysql_query("SELECT rfc FROM datosempresas WHERE plaza='{$row['plaza']}'");
	$rowPlaza2 = mysql_fetch_array($resPlaza2);
	$rowFormaPago = mysql_fetch_assoc(mysql_query("SELECT nombre FROM formas_pago WHERE cve='{$row['forma_pago']}'"));
	$rowTipoPago = mysql_fetch_assoc(mysql_query("SELECT nombre FROM tipos_pago WHERE cve='{$row['tipo_pago']}'"));
	//$rowDepositante = mysql_fetch_assoc(mysql_query("SELECT nombre FROM depositantes WHERE cve='{$row['tipo_pago']}'"));
	$rowDepositante = mysql_fetch_assoc(mysql_query("SELECT nombre FROM depositantes WHERE cve='{$row['depositante']}'"));

	$textosimp=chr(27).'!'.chr(30)." ".$rowPlaza['numero']."|".$rowPlaza['nombre'];
	$textosimp.='| RFC: '.$rowPlaza2['rfc'];
	$textosimp.='||';
	$textosimp.=chr(27).'!'.chr(8)." FOLIO: ".$row['cve'];
	$textosimp.='|';
	$textosimp.=chr(27).'!'.chr(8)." PAGO";
	$textosimp.='|';
	$textosimp.=chr(27).'!'.chr(8)." FECHA: ".$row['fecha'].' '.$row['hora'].'|';
	$textosimp.='|';
	$textosimp.=chr(27).'!'.chr(8)." FORMA PAGO: ".$rowFormaPago['nombre'];
	if($row['forma_pago']>1){
		$textosimp.=chr(27).'!'.chr(8)." REFERENCIA: ".$row['referencia'];
		$textosimp.='|';
	}
	$textosimp.='|';
	$textosimp.=chr(27).'!'.chr(8)." TIPO PAGO: ".$rowTipoPago['nombre'];
	$textosimp.='|';
	$textosimp.=chr(27).'!'.chr(8)." DEPOSITANTE: ".$rowDepositante['nombre'];
	$textosimp.='|';
	if($_POST['tipo_pago']==6){
		$Engomado=mysql_fetch_assoc(mysql_query("SELECT nombre FROM engomados WHERE cve='{$row['engomado']}'"));
		$textosimp.=chr(27).'!'.chr(8)." TIPO CERT: ".$Engomado['nombre'];
		$textosimp.='|';
	}
	$textosimp.=chr(27).'!'.chr(8)." MONTO: ".number_format($row['monto'],2);
	$textosimp.='|';
	$textosimp.=chr(27).'!'.chr(8)." ".numlet($row['monto']);
	$textosimp.='|';
	$barcode = '1'.sprintf("%011s",(intval($row['cve'])));
	$texto=chr(27)."@";
	/*if(file_exists('img/logo.TMB')){
		$texto.=chr(27).'a'.chr(1);
		$texto.=file_get_contents('img/logo.TMB');
		$texto.=chr(10).chr(13);
		$texto.=chr(27).'a0';
	}*/
	$textoimp=explode("|",$textosimp);
	for($i=0;$i<count($textoimp);$i++){
		$texto.=$textoimp[$i].chr(10).chr(13);
	}
	if($barcode!="")$texto.=chr(29)."h".chr(80).chr(29)."H".chr(2).chr(29)."k".chr(2).$barcode.chr(0);
	$texto.=chr(10).chr(13).chr(29).chr(86).chr(66).chr(0);

	$texto.=chr(27)."@";
	/*if(file_exists('img/logo.TMB')){
		$texto.=chr(27).'a'.chr(1);
		$texto.=file_get_contents('img/logo.TMB');
		$texto.=chr(10).chr(13);
		$texto.=chr(27).'a0';
	}*/
	$texto .= "        COPIA".chr(10).chr(13);
	for($i=0;$i<count($textoimp);$i++){
		$texto.=$textoimp[$i].chr(10).chr(13);
	}
	if($barcode!="")$texto.=chr(29)."h".chr(80).chr(29)."H".chr(2).chr(29)."k".chr(2).$barcode.chr(0);
	$texto.=chr(10).chr(13).chr(29).chr(86).chr(66).chr(0);
	if($row['tipo_pago']==6 || $row['tipo_pago']==9){
		$res1=mysql_query("SELECT cve,tipo FROM vales_pago_anticipado WHERE plaza='{$row['plaza']}' AND pago='{$row['cve']}'");
		while($row1 = mysql_fetch_array($res1)){
			$textosimp=chr(27).'!'.chr(30)." ".$rowPlaza['numero']."|".$rowPlaza['nombre'];
			$textosimp.='| RFC: '.$rowPlaza2['rfc'];
			$textosimp.='||';
			$textosimp.=chr(27).'!'.chr(8)." FOLIO: ".$row1['cve'];
			$textosimp.='|';
			if($row1['tipo']==0)
				$textosimp.=chr(27).'!'.chr(8)." VALE PAGO ANTICIPADO";
			else
				$textosimp.=chr(27).'!'.chr(8)." VALE CORTESIA";
			$textosimp.='|';
			$textosimp.=chr(27).'!'.chr(8)." FECHA: ".$row['fecha'].'|';
			$textosimp.='|';
			$textosimp.=chr(27).'!'.chr(8)." DEPOSITANTE: ".$rowDepositante['nombre'];
			$textosimp.='|';
			$texto.=chr(27)."@";
			$textoimp=explode("|",$textosimp);
			for($i=0;$i<count($textoimp);$i++){
				$texto.=$textoimp[$i].chr(10).chr(13);
			}
			$barcode = '6'.sprintf("%011s",(intval($row1['cve'])));
			if($barcode!="")$texto.=chr(29)."h".chr(80).chr(29)."H".chr(2).chr(29)."k".chr(2).$barcode.chr(0);
			$texto.=chr(10).chr(13).chr(29).chr(86).chr(66).chr(0);
		}
	}
	echo $texto;
	exit();
}

if($_POST['ajax']==34){
	$resultado = array('mensaje' => '', 'error'=>0);
	$res = mysql_query("SELECT factura, tipo_pago FROM pagos_caja WHERE plaza='{$_POST['cveplaza']}' AND cve='{$_POST['venta']}'");
	$row = mysql_fetch_array($res);
	if ($row['factura'] > 0) {
		$resultado['mensaje'] = 'La venta ya esta facturada';
		$resultado['error'] = 1;
	}
	elseif($row['tipo_pago']==6) {
		$res1=mysql_query("SELECT COUNT(a.cve) FROM vales_pago_anticipado a INNER JOIN cobro_engomado b ON a.plaza = b.plaza AND b.estatus!='C' AND ((a.cve = b.vale_pago_anticipado AND a.tipo=0 AND b.tipo_venta=0 AND b.tipo_pago = 6) OR (a.cve = b.codigo_cortesia AND a.tipo=1 AND b.tipo_venta=2 AND b.tipo_cortesia=2)) WHERE a.plaza='{$row['cveplaza']}' AND a.pago = '{$_POST['venta']}'");
		if($row1=mysql_fetch_array($res1)){
			$resultado['mensaje'] = 'La venta ya tiene vales utilizados';
			$resultado['error'] = 1;
		}
	}
	echo json_encode($resultado);
	exit();
}

if($_POST['cmd']==33){
	$resultado = array('mensaje' => 'Se cancelo exitosamente', 'tipo'=>'success');

	mysql_query("UPDATE pagos_caja SET estatus='C', usucan='{$_POST['cveusuario']}', fechacan=NOW(), obscan='".addslashes($_POST['obscan'])."' WHERE plaza='{$_POST['cveplaza']}' AND cve='{$_POST['venta']}'");
	mysql_query("UPDATE vales_pago_anticipado SET estatus='C',usucan='{$_POST['cveusuario']}',fechacan=NOW() WHERE plaza='{$_POST['cveplaza']}' AND pago='{$_POST['venta']}'");


	echo json_encode($resultado);
	exit();
}
require_once('validarloging.php');

if($_POST['cmd']==0){
	$nivelUsuario = nivelUsuario();
?>
<input type="hidden" id="ventacancelar" value="">
<div id="modalCancelacion" class="modal fade" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="staticBackdropLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="staticBackdropLabel">Cancelación</h5>
		        <!--<button type="button" class="close" data-dismiss="modal" aria-label="Close">
		          <span aria-hidden="true">&times;</span>
		        </button>-->
			</div>
			<div class="modal-body" id="bodypago">
				<div class="row">
					<div class="col-xl-12 col-lg-12 col-md-12">
						<div class="form-row">
					        <div class="form-group col-sm-12">
								<label for="total">Motivo</label>
					            <textarea type="text" class="form-control" rows="3" id="motivocancelacion"></textarea>
					        </div>
					    </div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-danger" onClick="cancelarventa();">Cancelar</button>
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
		     </div>
		</div>
	</div>
</div>
<div class="row justify-content-center">
	<div class="col-xl-12 col-lg-12 col-md-12">
		<div class="form-group row">
			<label class="col-sm-2 col-form-label">Fecha Inicio</label>
			<div class="col-sm-4">
            	<input type="date" class="form-control" id="busquedafechaini" name="busquedafechaini" value="<?php echo date('Y-m');?>-01" placeholder="Fecha Inicio">
        	</div>
			<label class="col-sm-2 col-form-label">Fecha Fin</label>
			<div class="col-sm-4">
            	<input type="date" class="form-control" id="busquedafechafin" name="busquedafechafin" value="<?php echo date('Y-m-d');?>" placeholder="Fecha Fin">
        	</div>
        </div>

      <div class="form-group row">
			<label class="col-sm-2 col-form-label">Tipo de Pago</label>
			<div class="col-sm-4">
            	<select name="busquedatipopago" id="busquedatipopago" class="form-control"><option value="">Todos</option>
            	<?php
            	$res1 = mysql_query("SELECT cve, nombre FROM tipos_pago WHERE cve IN (2,6) ORDER BY nombre");
				while($row1=mysql_fetch_array($res1)){
					echo '<option value="'.$row1['cve'].'">'.$row1['nombre'].'</option>';
				}
				?>
            	</select>
        	</div>
        	<label class="col-sm-2 col-form-label">Depositante</label>
			<div class="col-sm-4">
            	<select name="busquedadepositante" id="busquedadepositante" class="form-control" data-container="body" data-live-search="true" title="Depositante" data-hide-disabled="true" data-actions-box="true" data-virtual-scroll="false"><option value="">Todos</option>
            	<?php
            	$res1 = mysql_query("SELECT cve, CONCAT(nombre,'(',IF(tipo_depositante=0,'Pago Anticipado', 'Credito'),')') as nombre FROM depositantes WHERE estatus!=1 ORDER BY nombre");
				while($row1=mysql_fetch_array($res1)){
					echo '<option value="'.$row1['cve'].'">'.$row1['nombre'].'</option>';
				}
				?>
            	</select>
            	<script>
					$("#busquedadepositante").selectpicker();	
				</script>
        	</div>
        </div>

        <div class="form-group row">
			
        	<label class="col-sm-2 col-form-label">Usuario</label>
			<div class="col-sm-4">
            	<select name="busquedausuario" id="busquedausuario" class="form-control" data-container="body" data-live-search="true" title="Usuario" data-hide-disabled="true" data-actions-box="true" data-virtual-scroll="false"><option value="">Todos</option>
            	<?php
            	$res1 = mysql_query("SELECT b.cve, b.usuario FROM (SELECT usuario FROM pagos_caja WHERE plaza='{$_POST['cveplaza']}' GROUP BY usuario) a INNER JOIN usuarios b ON b.cve = a.usuario ORDER BY b.usuario");
				while($row1=mysql_fetch_array($res1)){
					echo '<option value="'.$row1['cve'].'">'.$row1['usuario'].'</option>';
				}
				?>
            	</select>
            	<script>
					$("#busquedausuario").selectpicker();	
				</script>
        	</div>
        </div>
        
        <div class="form-group row">
        	<div class="col-sm-12" align="center">
        		<button type="button" class="btn btn-primary" onClick="buscar();">
	            	Buscar
	        	</button>&nbsp;
	        	<button type="button" class="btn btn-success" onClick="atcr('pagos_caja.php','',1,0);">
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
				<th>&nbsp;</th>
				<th>Folio</th>
				<th>Fecha</th>
				<th>Tipo de Pago</th>
				<th>Forma de Pago</th>
				<th>Referencia</th>
				<th>Cliente</th>
				<th>Monto</th>
				<th>Copias</th>
				<th>Total</th>
				<th>Vales</th>
				<th>Cortesias</th>
				<th>Observaciones</th>
				<th>Factura</th>
				<th>Usuario</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>&nbsp;</th>
				<th>Folio</th>
				<th>Fecha</th>
				<th>Tipo de Pago</th>
				<th>Forma de Pago</th>
				<th>Referencia</th>
				<th>Cliente</th>
				<th>Monto<br><span id="tmonto" style="text-align: right;"></span></th>
				<th>Copias<br><span id="tcopias" style="text-align: right;"></span></th>
				<th>Total<br><span id="ttotal" style="text-align: right;"></span></th>
				<th>Vales</th>
				<th>Cortesias</th>
				<th>Observaciones</th>
				<th>Factura</th>
				<th>Usuario</th>
				
			</tr>
		</tfoot>
	</table>
</div>
<script>
	var tablalistado = $('#dataTable').DataTable( {
        "ajax": {
        	url: 'pagos_caja.php',
        	type: "POST",
        	"data": {
        		"cmd": 10,
        		"busquedafechaini": $("#busquedafechaini").val(),
        		"busquedafechafin": $("#busquedafechafin").val(),
        		"busquedausuario": $("#busquedausuario").val(),
        		"busquedadepositante": $("#busquedadepositante").val(),
        		"busquedatipopago": $("#busquedatipopago").val(),
        		"cvemenu": $('#cvemenu').val(),
        		"cveplaza": $('#cveplaza').val(),
        		"cveusuario": $('#cveusuario').val()
        	},
        	fncallback: function(json){
        		$('#tmonto').html(json.monto);
        		$('#tcopias').html(json.copias);
        		$('#ttotal').html(json.total);
        	}
        },
        "processing": true,
        "serverSide": true,
        "bFilter": false,
        "order": [[1, "DESC"]],
        "columnDefs": [
        	{ className: "dt-head-center dt-body-center", "targets": 0 },
        	{ className: "dt-head-center dt-body-right", "targets": 1 },
        	{ className: "dt-head-center dt-body-center", "targets": 2 },
        	{ className: "dt-head-center dt-body-center", "targets": 3 },
        	{ className: "dt-head-center dt-body-center", "targets": 4 },
        	{ className: "dt-head-center dt-body-center", "targets": 5 },
        	{ className: "dt-head-center dt-body-left", "targets": 6 },
        	{ className: "dt-head-center dt-body-right", "targets": 7 },
        	{ className: "dt-head-center dt-body-right", "targets": 8 },
        	{ className: "dt-head-center dt-body-right", "targets": 9 },
        	{ className: "dt-head-center dt-body-center", "targets": 10 },
        	{ className: "dt-head-center dt-body-center", "targets": 11 },
        	{ className: "dt-head-center dt-body-left", "targets": 12 },
        	{ className: "dt-head-center dt-body-center", "targets": 13 },
        	{ className: "dt-head-center dt-body-left", "targets": 14 },
        	{ orderable: false, "targets": 0 },
        	{ orderable: false, "targets": 10 },
        	{ orderable: false, "targets": 11 }
		  ]
    } );
	function buscar(){
		tablalistado.ajax.data({
    		"cmd": 10,
    		"busquedafechaini": $("#busquedafechaini").val(),
    		"busquedafechafin": $("#busquedafechafin").val(),
    		"busquedausuario": $("#busquedausuario").val(),
    		"busquedadepositante": $("#busquedadepositante").val(),
    		"busquedatipopago": $("#busquedatipopago").val(),
    		"cvemenu": $('#cvemenu').val(),
    		"cveplaza": $('#cveplaza').val(),
    		"cveusuario": $('#cveusuario').val()
        });
        tablalistado.ajax.reload();
	}

	function cancelarventa(){
		if ($("#motivocancelacion").val() == ""){
			alert("Necesita seleccionar un motivo de cancelacion");
		}
		else{
			$('#modalCancelacion').modal('hide');
			waitingDialog.show();
			$.ajax({
				url: 'pagos_caja.php',
				type: "POST",
				dataType: 'json',
				data: {
					cmd: 33,
					venta: $('#ventacancelar').val(),
					obscan: $("#motivocancelacion").val(),
					cveplaza: $('#cveplaza').val(),
					cveusuario: $('#cveusuario').val()
				},
				success: function(data) {
					waitingDialog.hide();
					sweetAlert('', data.mensaje, data.tipo);
					buscar();
				}
			});
		}
	}

	function precancelarventa(venta){
		waitingDialog.show();
		$.ajax({
			url: 'cobro_engomado.php',
			type: "POST",
			dataType: 'json',
			data: {
				cmd: 34,
				venta: venta,
				cveplaza: $('#cveplaza').val(),
				cveusuario: $('#cveusuario').val()
			},
			success: function(data) {
				waitingDialog.hide();
				if (data.error == 1) {
					sweetAlert('', data.mensaje, 'warning');
				}
				else {
					$('#ventacancelar').val(venta);
					$("#motivocancelacion").val('');
					$('#modalCancelacion').modal('show');
				}
			}
		});
	}

	$("#modalCancelacion").modal({
		backdrop: false,
		keyboard: false,
		show: false
	});
</script>
<?php
}

if($_POST['cmd']==10){
	$columnas=array("", "a.cve", "a.fecha", "b.nombre", "c.nombre", "a.referencia", 'd.nombre', 'a.monto', 'a.obs', 'CONCAT(h.serie,h.folio)', 'f.usuario');

	$orderby = "";
	foreach($_POST['order'] as $dato){
		$orderby .= ",{$columnas[$dato['column']]} {$dato['dir']}";
	}

	if($orderby == ""){
		$orderby = " ORDER BY a.cve DESC";
	}
	else{
		$orderby = " ORDER BY ".substr($orderby, 1);
	}


	$where = " WHERE a.plaza='{$_POST['cveplaza']}'";
		if($_POST['busquedafechaini']!=''){
			$where .= " AND a.fecha >= '{$_POST['busquedafechaini']}'";
		}

		if($_POST['busquedafechafin']!=''){
			$where .= " AND a.fecha <= '{$_POST['busquedafechafin']}'";
		}

		if($_POST['busquedadepositante']!=''){
			$where .= " AND a.depositante = '{$_POST['busquedadepositante']}'";
		}

		if($_POST['busquedausuario']!=''){
			$where .= " AND a.usuario = '{$_POST['busquedausuario']}'";
		}

		if($_POST['busquedatipopago']!=''){
			$where .= " AND a.tipo_pago = '{$_POST['busquedatipopago']}'";
		}

	$res = mysql_query("SELECT COUNT(a.cve) as registros, SUM(IF(a.estatus!='C', a.monto, 0)) as monto, IF(a.estatus='C',0,a.copias*a.costo_copias) as copias, IF(a.estatus='C',0,a.monto+(a.copias*a.costo_copias)) as total FROM pagos_caja a {$where}");
	$registros = mysql_fetch_assoc($res);
	$resultado = array(
		'data' => array(),
		'draw'=> $_POST['draw'],
		'recordsTotal'=> $registros['registros'],
		'recordsFiltered'=> $registros['registros'],
		'monto' => $registros['monto'],
		'copias' => $registros['copias'],
		'total' => $registros['total']
	);
	$res = mysql_query("SELECT a.cve, a.fecha, b.nombre as nomtipopago, c.nombre as nomformapago, a.referencia, d.nombre as nomdepositante,
	IF(a.estatus='C',0,a.monto) as monto, IF(a.estatus='C',0,a.copias*a.costo_copias) as copias, IF(a.estatus='C',0,a.monto+(a.copias*a.costo_copias)) as total, a.obs, f.usuario, a.estatus, a.tipo_pago, CONCAT(h.serie,h.folio) as foliofactura 
	FROM pagos_caja a 
	INNER JOIN tipos_pago b ON b.cve = a.tipo_pago 
	INNER JOIN formas_pago c ON c.cve = a.forma_pago 
	INNER JOIN depositantes d ON d.cve = a.depositante 
	INNER JOIN usuarios f ON f.cve = a.usuario 
	LEFT JOIN facturas h ON h.plaza = a.plaza AND h.cve = a.factura{$where}{$orderby} LIMIT {$_POST['start']},{$_POST['length']}");
	$tmonto = 0;
	$nivelUsuario = nivelUsuario();
	while($row = mysql_fetch_assoc($res)){
		
		$extras2 = '';
		if ($row['estatus'] == 'A' && $nivelUsuario >= 3) {
			$extras2 .= '<a class="dropdown-item" href="#" onClick="precancelarventa('.$row['cve'].')">Cancelar</a>';
		}

		$dropmenu = '<button class="btn btn-info dropdown-toggle" type="button" id="dropdownMenuButton_'.$row['cve'].'" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                      Acci&oacute;n
                    </button><div class="dropdown-menu animated--fade-in" aria-labelledby="dropdownMenuButton_'.$row['cve'].'" x-placement="bottom-start" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(0px, 38px, 0px);">
                      <a class="dropdown-item" href="#" onClick="atcr(\'pagos_caja.php\',\'\',101,'.$row['cve'].')">Imprimir</a>
                      '.$extras2.'
                    </div>';
	    if($row['estatus']=='C'){
	    	$dropmenu='CANCELADO';
	    }
	    $vales = '';
	    $cortesias='';
	    if ($row['tipo_pago']==6){
	    	$row1 = mysql_fetch_assoc(mysql_query("SELECT CONCAT(MIN(cve),' - ',MAX(cve)) as vales, SUM(IF(tipo=1,1,0)) as cortesias FROM vales_pago_anticipado WHERE plaza='{$_POST['cveplaza']}' AND pago = '{$row['cve']}'"));
	    	$vales = $row1['vales'];
	    	$cortesias = $row1['cortesias'];
	    }
		$resultado['data'][] = array(
			$dropmenu,
			($row['cve']),
			mostrar_fechas($row['fecha']),
			utf8_encode($row['nomtipopago']),
			utf8_encode($row['nomformapago']),
			utf8_encode($row['referencia']),
			utf8_encode($row['nomdepositante']),
			number_format($row['monto'],2),
			number_format($row['copias'],2),
			number_format($row['total'],2),
			$vales,
			$cortesias,
			utf8_encode($row['obs']),
			utf8_encode($row['foliofactura']),
			utf8_encode($row['usuario']),
		);
	}
	echo json_encode($resultado);

}

if($_POST['cmd']==1){
	$res = mysql_query("SELECT * FROM costos_copias_impresiones ORDER BY cve DESC");
	$row = mysql_fetch_assoc($res);
	$costo_copias = $row['copias'];
	$res1 = mysql_query("SELECT b.precio FROM engomados a inner join engomados_plazas b on a.cve = b.engomado WHERE a.cve=20 AND b.plaza = '{$_POST['cveplaza']}' ORDER BY a.nombre");
	$row1 = mysql_fetch_assoc($res1);
?>
<div class="row justify-content-center">
	<div class="col-sm-12" align="center">
	<?php
		if(nivelUsuario() > 1){
	?>
		<button type="button" class="btn btn-success" onClick="
			var resp=true;
			if($('#cortesias').val()>0){
				resp=confirm('Se generaran '+$('#cortesias').val()+' cortesia(s), esta seguro de continuar?');
			} 
			if(resp){
				atcr('pagos_caja.php','',2,'0');
			}">Guardar</button>
	&nbsp;&nbsp;&nbsp;
	<?php
		}
	?>
		<button type="button" class="btn btn-primary" onClick="$('#contenedorprincipal').html('');atcr('pagos_caja.php','',0,0);">Volver</button>
	</div>
</div><br>
<input type="hidden" name="importe_verificacion" id="importe_verificacion" value="0">
<div class="row">
	<div class="col-xl-12 col-lg-12 col-md-12">
		<div class="card shadow">
			<div class="card-body">
				<div class="form-row">
	        <div class="form-group col-sm-3">
						<label for="fecha">Fecha</label>
	           <input type="date" class="form-control" id="fecha" value="<?php echo date('Y-m-d');?>" name="fecha">
	        </div>
	      </div>
	      <div class="form-row">
	        <div class="form-group col-sm-3">
						<label for="forma_pago">Forma de Pago</label>
	          <select name="forma_pago" id="forma_pago" class="form-control" onChange="muestra_referencia()"><option value="">Seleccione</option>
	           	<?php
	           		$res1 = mysql_query("SELECT cve, nombre FROM formas_pago ORDER BY cve");
								while($row1=mysql_fetch_array($res1)){
									echo '<option value="'.$row1['cve'].'"">'.utf8_encode($row1['nombre']).'</option>';
								}
							?>
						</select>
	        </div>
	        <div class="form-group col-sm-3" id="capareferencia" style="display: none;">
						<label for="referencia">Referencia</label>
	          <input type="text" class="form-control" id="referencia" value="" name="referencia">
	        </div>
	      </div>
	      <div class="form-row">
	        <div class="form-group col-sm-3">
						<label for="tipo_pago">Tipo de Pago</label>
	          <select name="tipo_pago" id="tipo_pago" class="form-control" onChange="mostrar_campos_tipo_pago()"><option value="">Seleccione</option>
	           	<?php
	           		$res1 = mysql_query("SELECT cve, nombre FROM tipos_pago WHERE cve IN (2,6) ORDER BY cve");
								while($row1=mysql_fetch_array($res1)){
									echo '<option value="'.$row1['cve'].'">'.utf8_encode($row1['nombre']).'</option>';
								}
							?>
						</select>
	        </div>
	      </div>
	      <div class="form-row">
	        <div class="form-group col-sm-7">
				<label for="depositante">Depositante</label>
	         	<select name="depositante" class="form-control" data-container="body" data-live-search="true" title="Depositante" data-hide-disabled="true" data-actions-box="true" data-virtual-scroll="false" id="depositante"><option value="">Seleccione</option>

	         	</select>
	         	<script>
					$("#depositante").selectpicker();	
				</script>
	        </div>
	      </div>
	      <div class="form-row" id="tipo_certificado" style="display: none;">
	        <div class="form-group col-sm-7">
				<label for="engomado">Tipo de Certificado</label>
	         	<select name="engomado" id="engomado" class="form-control" onChange="muestra_precio()">
	           	<?php
	           		$res1 = mysql_query("SELECT a.cve, a.nombre, b.precio FROM engomados a inner join engomados_plazas b on a.cve = b.engomado WHERE b.venta=1 AND b.plaza = '{$_POST['cveplaza']}' ORDER BY a.nombre");
	           		if(mysql_num_rows($res1) > 1){
	           			echo '<option value="0" precio="0">Seleccione</option>';
	           		}
								while($row1=mysql_fetch_array($res1)){
									echo '<option value="'.$row1['cve'].'" precio="'.$row1['precio'].'">'.utf8_encode($row1['nombre']).'</option>';
								}
							?>
						</select>
	         	</select>
	        </div>
	      </div>
	      <div class="form-row" id="numero_verificaciones" style="display: none;">
	      	<div class="form-group col-sm-2">
						<label for="monto">Cantidad</label>
	          <input type="number" class="form-control" id="verificaciones" value="" name="verificaciones" onKeyUp="calcular()">
	        </div>
	      </div>
	      <div class="form-row" id="numero_cortesias" style="display: none;">
	      	<div class="form-group col-sm-2">
						<label for="monto">Cantidad Cortesias</label>
	          <input type="number" class="form-control" id="cortesias" value="" name="cortesias">
	        </div>
	      </div>
	      <div class="form-row">
	      	<div class="form-group col-sm-2">
						<label for="monto">Monto</label>
	          <input type="number" class="form-control" id="monto" value="" name="monto" onKeyUp="calcular2()">
	        </div>
	        <div class="form-group col-sm-2">
						<label for="monto">Copias</label>
	          <input type="number" class="form-control" id="copias" value="" name="copias" onKeyUp="calcular2()">
	        </div>
	        <div class="form-group col-sm-2">
						<label for="monto">Total</label>
	          <input type="number" class="form-control" id="total" value="" name="total" readOnly>
	        </div>
	      </div>
	      <div class="form-row">
	        <div class="form-group col-sm-6">
	        	<label for="obs">Observaciones</label>
	        	<textarea rows="3" id="obs" name="obs" class="form-control"></textarea>
	        </div>
	      </div>
	    </div>
	  </div>
	</div>
</div>


<script>
function traeRegistro(){

}

function muestra_precio(){
	$('#importe_verificacion').val($('#engomado').find('option:selected').attr('precio'));
	calcular();
}

function muestra_referencia(){
	if($('#forma_pago').val() == "" || $('#forma_pago').val() == "1"){
		$('#capareferencia').hide();
		$('#referencia').val('');
	}
	else {
		$('#capareferencia').show();
	}
}


function mostrar_campos_tipo_pago(){
	$('#numero_verificaciones').hide();
	$('#numero_cortesias').hide();
	$('#tipo_certificado').hide();
	$('#verificaciones').val('');
	$('#cortesias').val('');
	$('#engomado').val('0');
	$('#importe_verificacion').val('0');
	$('#monto').val('');
	$('#monto').removeAttr('readOnly');
	if($('#tipo_pago').val() == 6){
		$('#numero_verificaciones').show();
		//$('#numero_cortesias').show();
		$('#tipo_certificado').show();
		$('#monto').attr('readOnly', 'readOnly');
	}
	traeDepositante();
}

function traeDepositante(){
	$.ajax({
		url: 'pagos_caja.php',
		type: "POST",
		dataType: 'json',
		data: {
			cmd: 20,
			cveplaza: $('#cveplaza').val(),
			tipo_pago: $('#tipo_pago').val()
		},
		success: function(data) {
			$('#depositante').html(data.html);
			$('#depositante').selectpicker('refresh');
		}
	});
}

function calcular(){
	var total = $('#importe_verificacion').val()*$('#verificaciones').val();
	$('#monto').val(total.toFixed(2));
	calcular2();
}

function calcular2(){
	var total = $('#monto').val()/1 + ($('#copias').val()/1*<?php echo $costo_copias;?>);
	$('#total').val(total.toFixed(2));
}

muestra_precio();
</script>

<?php
}


if($_POST['cmd']==20){
	$resultado=array('html' => '<option value="" selected>Seleccione</option>');
	if($_POST['tipo_pago'] == 6){
		$tipo_depositante=0;
	}
	elseif($_POST['tipo_pago']==2){
		$tipo_depositante=4;
	}
	else{
		$tipo_depositante=2;
	}
	$res = mysql_query("SELECT cve, nombre FROM depositantes WHERE plaza='{$_POST['cveplaza']}' AND tipo_depositante='{$tipo_depositante}' AND estatus=0 ORDER BY nombre");
	while($row = mysql_fetch_assoc($res)){
		$resultado['html'] .= '<option value="'.$row['cve'].'">'.utf8_encode($row['nombre']).'</option>';
		
	}

	echo json_encode($resultado);
}


if($_POST['cmd']==2){
	$resultado = array('error' => 0, 'mensaje' => '');
	if(trim($_POST['forma_pago'])==''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita seleccionar la forma de pago');
	}
	elseif(trim($_POST['forma_pago'])>1 && $_POST['referencia'] == ''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita ingresar la referencia');
	}
	elseif(trim($_POST['tipo_pago']) == ''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita seleccionar el tipo de pago');
	}
	elseif(trim($_POST['depositante']) == ''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita seleccionar el depositante');
	}
	elseif(trim($_POST['tipo_pago']) == 6 && $_POST['engomado']==0){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita seleccionar el tipo de certificado');
	}
	elseif($_POST['monto']==0 && $_POST['cveusuario']!=1){
		$resultado = array('error' => 1, 'mensaje' => 'El monto no debe de ser mayor a cero');
	}
	elseif($_POST['copias']==''){
		$resultado = array('error' => 1, 'mensaje' => 'La cantidad de copias no debe de ir vacia');
	}
	
	if($resultado['error']==1){
		echo json_encode($resultado);
	}
	else{
		$res = mysql_query("SELECT * FROM costos_copias_impresiones ORDER BY cve DESC");
		$row = mysql_fetch_assoc($res);
		$costo_copias = $row['copias'];
		$insert = " INSERT pagos_caja 
							SET 
							plaza = '{$_POST['cveplaza']}',fecha='".$_POST['fecha']."',fecha_creacion=CURDATE(),hora=CURTIME(),
							forma_pago='{$_POST['forma_pago']}',referencia='{$_POST['referencia']}',monto='{$_POST['monto']}',
							tipo_pago='{$_POST['tipo_pago']}',depositante='{$_POST['depositante']}',
							usuario='{$_POST['cveusuario']}',estatus='A',copias='{$_POST['copias']}',costo_copias='{$costo_copias}',
							obs='".addslashes($_POST['obs'])."',importe_verificacion='{$_POST['importe_verificacion']}',
							verificaciones='{$_POST['verificaciones']}', engomado='{$_POST['engomado']}'";
			mysql_query($insert);
			$cvecobro = mysql_insert_id();
			if($_POST['tipo_pago']==6){
				for($i=0;$i<$_POST['verificaciones'];$i++){
					mysql_query("INSERT vales_pago_anticipado SET 
						plaza = '{$_POST['cveplaza']}',fecha='{$_POST['fecha']}',fecha_creacion=CURDATE(),
						hora=CURTIME(),depositante='{$_POST['depositante']}',monto='{$_POST['importe_verificacion']}',tipo=0,
						estatus='A',usuario='{$_POST['cveusuario']}',pago={$cvecobro}");
				}

				$Depositante=mysql_fetch_assoc(mysql_query("SELECT n_cortesias FROM depositantes WHERE cve={$_POST['depositante']}"));
				if ($Depositante['n_cortesias'] > 0){
					$cortesias = intval($_POST['verificaciones']/$Depositante['n_cortesias']);
					//$cortesias = $_POST['cortesias'];
					for($i=0;$i<$cortesias;$i++){
						mysql_query("INSERT vales_pago_anticipado SET 
							plaza = '{$_POST['cveplaza']}',fecha='{$_POST['fecha']}',fecha_creacion=CURDATE(),
							hora=CURTIME(),depositante='{$_POST['depositante']}',monto='0',
							estatus='A',usuario='{$_POST['cveusuario']}',pago={$cvecobro},tipo=1");
					}
				}
			}
		echo '<script>$("#contenedorprincipal").html("");atcr("pagos_caja.php","",0,"");atcr("pagos_caja.php","_blank",101,"'.$cvecobro.'");</script>';
	}
}

if($_POST['cmd']==101){
	/*$variables = array(
		'server' => '',
		'printer' => 'impresoratermica',
		'url' => $url_impresion.'/pagos_caja.php?cmd=101&cveplaza='.$_POST['cveplaza'].'&cvepago='.$_POST['reg']
	);
	$impresion='<iframe src="http://localhost:8020/?'.http_build_query($variables).'" width=200 height=200></iframe>';*/
	require_once("numlet.php");
	$res=mysql_query("SELECT * FROM pagos_caja WHERE plaza='{$_POST['cveplaza']}' AND cve='{$_POST['reg']}'");
	$row=mysql_fetch_array($res);
	$texto=chr(27)."@";
	$texto.='|';
	$resPlaza = mysql_query("SELECT nombre, numero FROM plazas WHERE cve='{$row['plaza']}'");
	$rowPlaza = mysql_fetch_array($resPlaza);
	$resPlaza2 = mysql_query("SELECT rfc FROM datosempresas WHERE plaza='{$row['plaza']}'");
	$rowPlaza2 = mysql_fetch_array($resPlaza2);
	$rowFormaPago = mysql_fetch_assoc(mysql_query("SELECT nombre FROM formas_pago WHERE cve='{$row['forma_pago']}'"));
	$rowTipoPago = mysql_fetch_assoc(mysql_query("SELECT nombre FROM tipos_pago WHERE cve='{$row['tipo_pago']}'"));
	//$rowDepositante = mysql_fetch_assoc(mysql_query("SELECT nombre FROM depositantes WHERE cve='{$row['tipo_pago']}'"));
	$rowDepositante = mysql_fetch_assoc(mysql_query("SELECT nombre FROM depositantes WHERE cve='{$row['depositante']}'"));

	$variables='plaza='.$rowPlaza['numero'];
	$variables.='&nomplaza='.$rowPlaza['nombre'];
	$variables.='&rfc='.$rowPlaza2['rfc'];	
	$variables.='&folio='.$row['cve'];
	$variables.='&fecha='.$row['fecha'].' '.$row['hora'];
	$variables.='&formapago='.$rowFormaPago['nombre'];
	$variables.='&cveformapago='.$row['forma_pago'];
	$variables.='&referencia='.$row['referencia'];
	$variables.='&tipopago='.$rowTipoPago['tipo_pago'];
	$variables.='&depositante='.$rowDepositante['nombre'];
	$variables.='&monto='.$row['monto'];
	$variables.='&montoletra='.numlet($row['monto']);
	$vales = '';
	if($row['tipo_pago']==6 || $row['tipo_pago']==9){
		$res1=mysql_query("SELECT cve,tipo FROM vales_pago_anticipado WHERE plaza='".$row['plaza']."' AND pago='".$row['cve']."'");
		while($row1 = mysql_fetch_array($res1)){
			$vales .= ','.$row1['cve'].'|'.$row1['tipo'];
		}
		$vales = substr($vales, 1);
	}
	$variables.='&vales='.$vales;
	//$variables.='&montol='.numlet($row['monto']);
	$impresion='<iframe src="http://localhost/impresioncajaverificentros.php?'.$variables.'" width=200 height=200></iframe>';
	echo '<html><body>'.$impresion.'</body></html>';
	echo '<script>setTimeout("window.close()",5000);</script>';
}

?>