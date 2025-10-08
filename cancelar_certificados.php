<?php
error_reporting(E_ERROR | E_PARSE);
require_once('cnx_db.php');
require_once('globales.php');


if($_POST['cmd']==33){
	$resultado = array('mensaje' => 'Se cancelo exitosamente', 'tipo'=>'success');

	mysql_query("UPDATE certificados_cancelados SET estatus='C', usucan='{$_POST['cveusuario']}', fechacan=NOW(), obscan='{$_POST['motivocancelacion']}' WHERE plaza='{$_POST['cveplaza']}' AND cve='{$_POST['cancelacion']}'");

	$res = mysql_query("SELECT * FROM certificados_cancelados WHERE plaza='{$_POST['cveplaza']}' AND cve='{$_POST['cancelacion']}'");
	$row = mysql_fetch_array($res);
	mysql_query("UPDATE compra_certificados a INNER JOIN compra_certificados_detalle b ON a.plaza = b.plaza AND a.cve = b.cvecompra SET b.estatus=0 WHERE a.plaza='{$_POST['cveplaza']}' AND a.engomado = '{$row['engomado']}' AND a.estatus!='C' AND b.folio='".intval($row['certificado'])."' AND b.estatus=1");
	echo json_encode($resultado);
	exit();
}
require_once('validarloging.php');

if($_POST['cmd']==110){
		require_once('dompdf/dompdf_config.inc.php');
			$html='<html><head>
		  <style type="text/css">
							top  lado      ladoiz
			 @page{ margin: 5in 0.5in 1px 0.5in;}
			</style>
			 </head><body>';


$html.= '<table width="100%" border="0" cellpadding="4" cellspacing="1" class="" id="tabla1" >';
	$html.= '<tr style="font-size:32px"><td align="center" colspan="11">Puebla</td></tr><tr><td>&nbsp;</td></tr><tr><td>&nbsp;</td></tr>
	<tr style="font-size:24px">
			<td align="center" colspan="11" style="font-size:28px">Reporte Cancelados</td>
		 </tr>';
	$html.= '</table>';
	$html.= '<br><table width="100%" border="0" cellpadding="4" cellspacing="14" class="" id="tabla1" style="font-size:12px">';
	$html.='<tr>
	<th WIDTH="25">&nbsp;</th>
				<th WIDTH="20">Folio</th>
				<th WIDTH="75">Fecha</th>
				<th WIDTH="95">Motivo</th>
				<th WIDTH="130">Tipo de Certificado</th>
				<th WIDTH="70">Semestre</th>
				<th WIDTH="35">Certificado</th>
				<th  WIDTH="30">Placa</th>
				<th WIDTH="145">T&eacute;cnico</th>
				<th WIDTH="30">Linea</th>
				<!--<th>Observaciones</th>-->
				<th WIDTH="40">Usuario</th>
				
			</tr>';

	
	$columnas=array("a.cve", "CONCAT(a.fecha,' ',a.hora)", "b.nombre", "c.nombre", "d.nombre", 'a.certificado', 'a.placa', 'e.nombre', 'f.nombre', 'a.obs', 'g.usuario');

	$orderby = "";
	foreach($_POST['order'] as $dato){
		$orderby .= ",{$columnas[$dato['column']]} {$dato['dir']}";
	}

	if($orderby == ""){
		$orderby = " ORDER BY a.cve";
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

		if($_POST['busquedacertificado']!=''){
			$where .= " AND a.certificado = '{$_POST['busquedacertificado']}'";
		}

		if($_POST['busquedaanio']!=''){
			$where .= " AND a.anio = '{$_POST['busquedaanio']}'";
		}

		if($_POST['busquedamotivo']!=''){
			$where .= " AND a.motivo = '{$_POST['busquedamotivo']}'";
		}

		if(is_array($_POST['busquedatipocertificado']) && count($_POST['busquedatipocertificado']>0)){
			$busquedatipocertificado = implode(',', $_POST['busquedatipocertificado']);
			$where .= " AND a.engomado IN ({$busquedatipocertificado})";
		}

		if($_POST['busquedausuario']!=''){
			$where .= " AND a.usuario = '{$_POST['busquedausuario']}'";
		}



	$trt="SELECT a.cve, CONCAT(a.fecha,' ',a.hora) as fechahora, b.nombre as nommotivo, c.nombre as nomengomado, d.nombre as nomanio, a.certificado, a.placa, e.nombre as nomtecnico, f.nombre as nomlinea, a.obs, g.usuario, a.estatus FROM certificados_cancelados a INNER JOIN motivos_cancelacion_certificados b ON b.cve = a.motivo INNER JOIN engomados c ON c.cve = a.engomado INNER JOIN anios_certificados d ON d.cve = a.anio LEFT JOIN tecnicos e ON e.plaza=a.plaza AND e.cve = a.tecnico LEFT JOIN cat_lineas f ON f.cve = a.linea INNER JOIN usuarios g ON g.cve = a.usuario{$where}{$orderby}";
//	$html.= $trt;
	$res = mysql_query($trt);
	$tmonto = 0;
	$nivelUsuario = nivelUsuario();
	while($row = mysql_fetch_assoc($res)){
		
		$html.= '<tr><td>';
		
    if($row['estatus']=='C'){
    	$dropmenu='CANCELADO';
    }
	$html.= '</td>';
			$html.= '<td align="">'.utf8_encode($row['cve']).'</td>';
			$html.= '<td align="">'.substr($row['fechahora'],0,10).' '.substr($row['fechahora'],11).'</td>';
			$html.= '<td align="">'.utf8_encode($row['nommotivo']).'</td>';
			$html.= '<td align="">'.utf8_encode($row['nomengomado']).'</td>';
			$html.= '<td align="">'.utf8_encode($row['nomanio']).'</td>';
			$html.= '<td align="">'.utf8_encode($row['certificado']).'</td>';
			$html.= '<td align="">'.utf8_encode($row['placa']).'</td>';
			$html.= '<td align="">'.utf8_encode($row['nomtecnico']).'</td>';
			$html.= '<td align="">'.utf8_encode($row['nomlinea']).'</td>';
			//$html.= '<td align="">'.utf8_encode($row['obs']).'</td>';
			$html.= '<td align="">'.utf8_encode($row['usuario']).'</td>';
			$html.='</tr>';
		
	}
	$html.='<tr><td>&nbsp;</td></tr><tr><td colspan="11" align="left" >Registros'.mysql_num_rows($res).'</td></tr>';
	$html.='</table> </body></html>';
	
	$mipdf= new DOMPDF();
//	$mipdf->margin: "0";
	//$mipdf->set_paper("A4", "portrait");
//	$mipdf->set_paper("A4", "portrait");
    
//    $mipdf->set_margin("Legal", "landscape");
	$mipdf->set_paper("Legal", "landscape");
	$mipdf->load_html($html);
	$mipdf->render();
	$mipdf ->stream();
exit();
}



if($_POST['cmd']==0){
	$nivelUsuario = nivelUsuario();
?>
<input type="hidden" id="cvecancelar" value="">
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
				<button type="button" class="btn btn-danger" onClick="cancelarcve();">Cancelar</button>
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
			<label class="col-sm-2 col-form-label">Tipo de Certificado</label>
			<div class="col-sm-4">
            	<select multiple name="busquedatipocertificado[]" class="form-control" data-container="body" data-live-search="true" title="Tipo de Certificado" data-hide-disabled="true" data-actions-box="true" data-virtual-scroll="false" id="busquedatipocertificado">
            	<?php
            	$res1 = mysql_query("SELECT a.cve, a.nombre FROM engomados a inner join engomados_plazas b on a.cve = b.engomado WHERE b.entrega=1 AND b.plaza = '{$_POST['cveplaza']}' ORDER BY a.nombre");
				while($row1=mysql_fetch_array($res1)){
					echo '<option value="'.$row1['cve'].'">'.$row1['nombre'].'</option>';
				}
				?>
            	</select>
            	<script>
					$("#busquedatipocertificado").selectpicker();	
				</script>
        	</div>
        	<label class="col-sm-2 col-form-label">Usuario</label>
			<div class="col-sm-4">
            	<select name="busquedausuario" id="busquedausuario" class="form-control" data-container="body" data-live-search="true" title="Usuario" data-hide-disabled="true" data-actions-box="true" data-virtual-scroll="false"><option value="">Todos</option>
            	<?php
            	$res1 = mysql_query("SELECT b.cve, b.usuario FROM (SELECT usuario FROM certificados WHERE plaza='{$_POST['cveplaza']}' GROUP BY usuario) a INNER JOIN usuarios b ON b.cve = a.usuario ORDER BY b.usuario");
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
			<label class="col-sm-2 col-form-label">Motivo de Cancelaci&oacute;n</label>
			<div class="col-sm-4">
            	<select class="form-control" id="busquedamotivo" name="busquedamotivo"><option value="">Todos</option>
            	<?php
            	$res1 = mysql_query("SELECT a.cve, a.nombre FROM motivos_cancelacion_certificados a ORDER BY a.nombre");
				while($row1=mysql_fetch_array($res1)){
					echo '<option value="'.$row1['cve'].'">'.$row1['nombre'].'</option>';
				}
				?>
            	</select>
        	</div>
        	<label class="col-sm-2 col-form-label">Semestre</label>
			<div class="col-sm-4">
            	<select name="busquedaanio" id="busquedaanio" class="form-control"><option value="">Todos</option>
            	<?php
            	$res1 = mysql_query("SELECT * FROM anios_certificados  ORDER BY nombre DESC");
				while($row1=mysql_fetch_array($res1)){
					echo '<option value="'.$row1['cve'].'">'.$row1['nombre'].'</option>';
				}
				?>
            	</select>
        	</div>
        </div>
        <div class="form-group row">
        	<label class="col-sm-2 col-form-label">Certificado</label>
			<div class="col-sm-4">
            	<input type="number" class="form-control" id="busquedacertificado" name="busquedacertificado" placeholder="Certificado">
        	</div>
        </div>

        <div class="form-group row">
        	<div class="col-sm-12" align="center">
        	<?php if($_POST['cveusuario']!=1){ ?>
        		<button type="button" class="btn btn-primary" onClick="buscar();">
	            	Buscar
	        	</button>&nbsp;
	        	<button type="button" class="btn btn-success" onClick="atcr('cancelar_certificados.php','',1,0);">
	            	Nuevo
	        	</button>
	        <?php } else { ?>
	        	<div class="btn-group">
	        		<button type="button" class="btn btn-primary" onClick="buscar();">
		            	Buscar
		        	</button>&nbsp;&nbsp;
		        </div>
			    <div class="btn-group">
		      		<button class="btn btn-success dropdown-toggle" type="button" id="dropdownMenuButton3" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
						Nuevo
					</button>
					<div class="dropdown-menu" aria-labelledby="dropdownMenuButton3">
					    <a class="dropdown-item" href="#" onClick="atcr('cancelar_certificados.php','', 1, 0);">Individual</a>
					    <a class="dropdown-item" href="#" onClick="atcr('cancelar_certificados.php','', 31, 1);">Masivo</a>
					</div>
				</div>
				<div class="btn-group">
		      		<button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton3" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
						Imprimir
					</button>
					<div class="dropdown-menu" aria-labelledby="dropdownMenuButton4">
						 <a class="dropdown-item" href="#" onClick="atcr('cancelar_certificados.php','_blank', 110, 0);">Pdf</a>
					</div>
				</div>
	        <?php } ?>
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
				<th>Motivo</th>
				<th>Tipo de Certificado</th>
				<th>Semestre</th>
				<th>Certificado</th>
				<th>Placa</th>
				<th>T&eacute;cnico</th>
				<th>Linea</th>
				<th>Observaciones</th>
				<th>Usuario</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>&nbsp;</th>
				<th>Folio</th>
				<th>Fecha</th>
				<th>Motivo</th>
				<th>Tipo de Certificado</th>
				<th>Semestre</th>
				<th>Certificado</th>
				<th>Placa</th>
				<th>T&eacute;cnico</th>
				<th>Linea</th>
				<th>Observaciones</th>
				<th>Usuario</th>
			</tr>
		</tfoot>
	</table>
</div>
<script>
	var tablalistado = $('#dataTable').DataTable( {
        "ajax": {
        	url: 'cancelar_certificados.php',
        	type: "POST",
        	"data": {
        		"cmd": 10,
        		"busquedafechaini": $("#busquedafechaini").val(),
        		"busquedafechafin": $("#busquedafechafin").val(),
        		"busquedacertificado": $("#busquedacertificado").val(),
        		"busquedausuario": $("#busquedausuario").val(),
        		"busquedatipocertificado": $("#busquedatipocertificado").val(),
        		"busquedaanio": $("#busquedaanio").val(),
        		"busquedamotivo": $("#busquedamotivo").val(),
        		"cvemenu": $('#cvemenu').val(),
        		"cveplaza": $('#cveplaza').val(),
        		"cveusuario": $('#cveusuario').val()
        	},
        },
        "processing": true,
        "serverSide": true,
        "bFilter": false,
        "order": [[0, "DESC"]],
        "columnDefs": [
        	{ className: "dt-head-center dt-body-center", "targets": 0 },
        	{ className: "dt-head-center dt-body-right", "targets": 1 },
        	{ className: "dt-head-center dt-body-center", "targets": 2 },
        	{ className: "dt-head-center dt-body-left", "targets": 3 },
        	{ className: "dt-head-center dt-body-left", "targets": 4 },
        	{ className: "dt-head-center dt-body-left", "targets": 5 },
        	{ className: "dt-head-center dt-body-center", "targets": 6 },
        	{ className: "dt-head-center dt-body-left", "targets": 7 },
        	{ className: "dt-head-center dt-body-left", "targets": 8 },
        	{ className: "dt-head-center dt-body-left", "targets": 9 },
        	{ className: "dt-head-center dt-body-left", "targets": 10 },
        	{ orderable: false, "targets": 0 }
		  ]
    } );
	function buscar(){
		tablalistado.ajax.data({
    		"cmd": 10,
    		"busquedafechaini": $("#busquedafechaini").val(),
    		"busquedafechafin": $("#busquedafechafin").val(),
    		"busquedacertificado": $("#busquedacertificado").val(),
    		"busquedausuario": $("#busquedausuario").val(),
    		"busquedatipocertificado": $("#busquedatipocertificado").val(),
    		"busquedaanio": $("#busquedaanio").val(),
    		"busquedamotivo": $("#busquedamotivo").val(),
    		"cvemenu": $('#cvemenu').val(),
    		"cveplaza": $('#cveplaza').val(),
    		"cveusuario": $('#cveusuario').val()
        });
        tablalistado.ajax.reload();
	}

	function cancelarcve(){
		if ($("#motivocancelacion").val() == ""){
			alert("Necesita seleccionar un motivo de cancelacion");
		}
		else{
			$('#modalCancelacion').modal('hide');
			waitingDialog.show();
			$.ajax({
				url: 'cancelar_certificados.php',
				type: "POST",
				dataType: 'json',
				data: {
					cmd: 33,
					cancelacion: $('#cvecancelar').val(),
					motivocancelacion: $("#motivocancelacion").val(),
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

	function precancelarcve(cve){
		$('#cvecancelar').val(cve);
		$("#motivocancelacion").val('');
		$('#modalCancelacion').modal('show');
		
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
	$columnas=array("a.cve", "CONCAT(a.fecha,' ',a.hora)", "b.nombre", "c.nombre", "d.nombre", 'a.certificado', 'a.placa', 'e.nombre', 'f.nombre', 'a.obs', 'g.usuario');

	$orderby = "";
	foreach($_POST['order'] as $dato){
		$orderby .= ",{$columnas[$dato['column']]} {$dato['dir']}";
	}

	if($orderby == ""){
		$orderby = " ORDER BY a.cve";
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

		if($_POST['busquedacertificado']!=''){
			$where .= " AND a.certificado = '{$_POST['busquedacertificado']}'";
		}

		if($_POST['busquedaanio']!=''){
			$where .= " AND a.anio = '{$_POST['busquedaanio']}'";
		}

		if($_POST['busquedamotivo']!=''){
			$where .= " AND a.motivo = '{$_POST['busquedamotivo']}'";
		}

		if(is_array($_POST['busquedatipocertificado']) && count($_POST['busquedatipocertificado']>0)){
			$busquedatipocertificado = implode(',', $_POST['busquedatipocertificado']);
			$where .= " AND a.engomado IN ({$busquedatipocertificado})";
		}

		if($_POST['busquedausuario']!=''){
			$where .= " AND a.usuario = '{$_POST['busquedausuario']}'";
		}



	$res = mysql_query("SELECT COUNT(a.cve) as registros FROM certificados_cancelados a {$where}");
	$registros = mysql_fetch_assoc($res);
	$resultado = array(
		'data' => array(),
		'draw'=> $_POST['draw'],
		'recordsTotal'=> $registros['registros'],
		'recordsFiltered'=> $registros['registros'],
	);
	$res = mysql_query("SELECT a.cve, CONCAT(a.fecha,' ',a.hora) as fechahora, b.nombre as nommotivo, c.nombre as nomengomado, d.nombre as nomanio, a.certificado, a.placa, e.nombre as nomtecnico, f.nombre as nomlinea, a.obs, g.usuario, a.estatus FROM certificados_cancelados a INNER JOIN motivos_cancelacion_certificados b ON b.cve = a.motivo INNER JOIN engomados c ON c.cve = a.engomado INNER JOIN anios_certificados d ON d.cve = a.anio LEFT JOIN tecnicos e ON e.plaza=a.plaza AND e.cve = a.tecnico LEFT JOIN cat_lineas f ON f.cve = a.linea INNER JOIN usuarios g ON g.cve = a.usuario{$where}{$orderby} LIMIT {$_POST['start']},{$_POST['length']}");
	$tmonto = 0;
	$nivelUsuario = nivelUsuario();
	while($row = mysql_fetch_assoc($res)){
		
		$extras2 = '';
		if ($row['estatus'] == 'A' && $nivelUsuario >= 3) {
			$extras2 .= '<a class="dropdown-item" href="#" onClick="precancelarcve('.$row['cve'].')">Cancelar</a>';
		}

		$dropmenu = '<button class="btn btn-info dropdown-toggle" type="button" id="dropdownMenuButton_'.$row['cve'].'" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                      Acci&oacute;n
                    </button><div class="dropdown-menu animated--fade-in" aria-labelledby="dropdownMenuButton_'.$row['cve'].'" x-placement="bottom-start" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(0px, 38px, 0px);">
                      
                      '.$extras2.'
                    </div>';
    if($row['estatus']=='C'){
    	$dropmenu='CANCELADO';
    }
		$resultado['data'][] = array(
			$dropmenu,
			($row['cve']),
			mostrar_fechas(substr($row['fechahora'],0,10)).' '.substr($row['fechahora'],11),
			utf8_encode($row['nommotivo']),
			utf8_encode($row['nomengomado']),
			utf8_encode($row['nomanio']),
			utf8_encode($row['certificado']),
			utf8_encode($row['placa']),
			utf8_encode($row['nomtecnico']),
			utf8_encode($row['nomlinea']),
			utf8_encode($row['obs']),
			utf8_encode($row['usuario'])
		);
	}
	echo json_encode($resultado);

}


if($_POST['cmd']==1){


?>
<div class="row justify-content-center">
	<div class="col-sm-12" align="center">
	<?php
		if(nivelUsuario() > 1){
	?>
		<button type="button" class="btn btn-success" onClick="atcr('cancelar_certificados.php','',2,'0');">Guardar</button>
	&nbsp;&nbsp;&nbsp;
	<?php
		}
	?>
		<button type="button" class="btn btn-primary" onClick="$('#contenedorprincipal').html('');atcr('cancelar_certificados.php','',0,0);">Volver</button>
	</div>
</div><br>
<div class="row">
	<div class="col-xl-12 col-lg-12 col-md-12">
		<div class="card shadow">
			<div class="card-body">
				<div class="form-row">
			        <div class="form-group col-sm-3">
						<label for="motivo">Motivo de Cancelaci&oacute;n</label>
			          	<select name="motivo" id="motivo" class="form-control"><option value="0">Seleccione</option>
			           	<?php
			           		$res1 = mysql_query("SELECT cve, nombre FROM motivos_cancelacion_certificados ORDER BY nombre");
							while($row1=mysql_fetch_array($res1)){
								echo '<option value="'.$row1['cve'].'">'.utf8_encode($row1['nombre']).'</option>';
							}
						?>
						</select>
			        </div>
			        <div class="form-group col-sm-4">
						<label for="anio">Semetre</label>
			          	<select name="anio" id="anio" class="form-control"><option value="0">Seleccione</option>
			           	<?php
			           		$res1 = mysql_query("SELECT cve, nombre FROM anios_certificados ORDER BY nombre DESC LIMIT 3");
							while($row1=mysql_fetch_array($res1)){
								echo '<option value="'.$row1['cve'].'">'.utf8_encode($row1['nombre']).'</option>';
							}
						?>
						</select>
			        </div>
			    </div>
			    <div class="form-row">
			      	<div class="form-group col-sm-2">
						<label for="certificado">Certificado</label>
			          	<input type="number" class="form-control" id="certificado" value="" name="certificado" onChange="traeCertificado();" onKeyUp="if(event.keyCode==13){ traeCertificado();}">
			        </div>
			      	<div class="form-group col-sm-3">
						<label for="engomado">Tipo de Certificado</label>
			          	<select name="engomado" id="engomado" class="form-control"><option value="0">Seleccione</option></select>
			        </div>
			    </div>
				<div class="form-row">
			        <div class="form-group col-sm-3">
						<label for="placa">Placa</label>
			            <input type="text" class="form-control" id="placa" value="" autocomplete="off" onKeyUp="this.value = this.value.toUpperCase();" name="placa">
			        </div>
			        <div class="form-group col-sm-6">
						<label for="tecnico">T&eacute;cnico</label>
				        <select name="tecnico" id="tecnico" class="form-control"><option value="0">Seleccione</option>
				        <?php
				        	$res1 = mysql_query("SELECT cve, nombre FROM tecnicos WHERE plaza='{$_POST['cveplaza']}' ORDER BY nombre");
							while($row1=mysql_fetch_array($res1)){
								echo '<option value="'.$row1['cve'].'">'.utf8_encode($row1['nombre']).'</option>';
							}
						?>
						</select>
				    </div>
	        		<div class="form-group col-sm-3">
						<label for="linea">Linea</label>
				        <select name="linea" id="linea" class="form-control"><option value="0">Seleccione</option>
				        <?php
				        	$res1 = mysql_query("SELECT cve, nombre FROM cat_lineas WHERE plaza='{$_POST['cveplaza']}' ORDER BY nombre");
							while($row1=mysql_fetch_array($res1)){
								echo '<option value="'.$row1['cve'].'">'.utf8_encode($row1['nombre']).'</option>';
							}
						?>
						</select>
				    </div>
	      	</div>
	     		<div class="form-row">
	     			<div class="form-group col-sm-3">
								<label for="tipo_combustible">Tipo de Combustible</label>
			          <select name="tipo_combustible" id="tipo_combustible" class="form-control"><option value="0">Seleccione</option>
			           	<?php
			           		$res1 = mysql_query("SELECT cve, nombre FROM tipo_combustible ORDER BY cve");
										while($row1=mysql_fetch_array($res1)){
											echo '<option value="'.$row1['cve'].'">'.utf8_encode($row1['nombre']).'</option>';
										}
									?>
								</select>
			        </div>
	        		<div class="form-group col-sm-6">
									<label for="obs">Observaciones</label>
	           			<textarea class="form-control" rows="3" id="obs" name="obs"></textarea>
	        		</div>

		        </div>
	      
	    	</div>
	    </div>
	</div>
</div>


<script>

function traeCertificado(){
	if($('#certificado').val() == ''){
		$('#engomado').html('<option value="0">Seleccione</option>');
	}
	else{
		$.ajax({
			url: 'cancelar_certificados.php',
			type: "POST",
			dataType: 'json',
			data: {
				cmd: 22,
				certificado: $('#certificado').val(),
				anio: $('#anio').val(),
				cveplaza: $('#cveplaza').val(),
			},
			success: function(data) {
				$('#engomado').html(data.engomado);
				if(data.mensaje != ''){
					alert(data.mensaje);
				}
			}
		});
	}
}

</script>

<?php
}

if($_POST['cmd']==22){
	$resultado=array('mensaje' => '', 'engomado' => '<option value="0">Seleccione</option>');
	$res = mysql_query("SELECT cve FROM certificados_cancelados WHERE plaza='{$_POST['cveplaza']}' AND certificado='".intval($_POST['certificado'])."' AND estatus!='C' AND anio='{$_POST['anio']}'");
	if(mysql_num_rows($res)==0){
		$res = mysql_query("SELECT cve FROM certificados WHERE plaza='{$_POST['cveplaza']}' AND certificado='".intval($_POST['certificado'])."' AND estatus!='C' AND anio='{$_POST['anio']}'");
		if(mysql_num_rows($res)==0){
			$res = mysql_query("SELECT a.engomado, b.estatus, b.tipo, a.anio, c.nombre FROM compra_certificados a 
				INNER JOIN compra_certificados_detalle b ON a.plaza = b.plaza AND a.cve = b.cvecompra 
				INNER JOIN engomados c ON c.cve = a.engomado
				WHERE a.plaza='{$_POST['cveplaza']}' AND a.estatus!='C' AND a.anio='{$_POST['anio']}' AND b.folio='".intval($_POST['certificado'])."' ORDER BY b.cve DESC LIMIT 1");
				if($row = mysql_fetch_array($res)){
						$resultado['engomado'] = '<option value="'.$row['engomado'].'">'.$row['nombre'].'</option>';
				}
				else{
					$resultado['mensaje'] = utf8_encode('El certificado no existe!');
				}
		}
		else{
			$resultado['mensaje'] = utf8_encode('El certificado esta entregado');	
		}
	}
	else{
		$resultado['mensaje'] = utf8_encode('El certificado esta cancelado');
	}


	echo json_encode($resultado);
}


if ($_POST['cmd']==2) {

	$resultado = array('error' => 0, 'mensaje' => '');
	if(trim($_POST['motivo'])=='0'){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita seleccionar el motivo de cancelación');
	}
	elseif(trim($_POST['anio']) == ''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita seleccionar el semestre');
	}
	/*elseif(trim($_POST['tecnico']) == '0'){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita seleccionar el técnico');
	}
	elseif(trim($_POST['linea']) == '0'){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita seleccionar la linea');
	}*/
	elseif(trim($_POST['certificado']) == ''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita ingresar el certificado');
	}
	elseif(trim($_POST['engomado']) == '0'){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita cargar el certificado');
	}
	else{
		$costo=0;
		$res = mysql_query("SELECT cve, fecha FROM certificados_cancelados WHERE plaza='{$_POST['cveplaza']}' AND engomado='{$_POST['engomado']}' AND anio='{$_POST['anio']}' AND certificado='".intval($_POST['certificado'])."' AND estatus!='C'");
		if(mysql_num_rows($res)==0){
			$res = mysql_query("SELECT cve, fecha FROM certificados WHERE plaza='{$_POST['cveplaza']}' AND engomado='{$_POST['engomado']}' AND anio='{$_POST['anio']}' AND certificado='".intval($_POST['certificado'])."' AND estatus!='C'");
			if(mysql_num_rows($res)==0){
				if($_POST['engomado'] == 3 || $_POST['engomado'] == 19){
					$res = mysql_query("SELECT b.cve, a.costo FROM compra_certificados a INNER JOIN compra_certificados_detalle b ON a.plaza = b.plaza AND a.cve = b.cvecompra WHERE a.plaza='{$_POST['cveplaza']}' AND a.engomado = '{$_POST['engomado']}' AND b.folio='".intval($_POST['certificado'])."' AND a.estatus!='C'");
				}
				else{
					$res = mysql_query("SELECT b.cve, a.costo FROM compra_certificados a INNER JOIN compra_certificados_detalle b ON a.plaza = b.plaza AND a.cve = b.cvecompra WHERE a.plaza='{$_POST['cveplaza']}' AND a.engomado = '{$_POST['engomado']}' AND a.anio='{$_POST['anio']}' AND b.folio='".intval($_POST['certificado'])."' AND a.estatus!='C'");
				}
				if(mysql_num_rows($res)==0 ){
					$resultado = array('error' => 1, 'mensaje' => "El holograma no existe o no esta activo");
				}
				else{
					$row = mysql_fetch_assoc($res);
					$costo = $row['costo'];
				}
			}
			else{
				$row = mysql_fetch_array($res);
				$resultado = array('error' => 1, 'mensaje' => "El holograma ya esta entregado en el folio {$row['cve']} del dia {$row['fecha']}");
			}
		}
		else{
			$row = mysql_fetch_array($res);
			$resultado = array('error' => 1, 'mensaje' => "El holograma ya esta cancelado en el folio {$row['cve']} del dia {$row['fecha']}");
		}

	}
	if($resultado['error']==1){
		$resultado['mensaje'] = utf8_encode($resultado['mensaje']);
		echo json_encode($resultado);
	}
	else{
		$ticket = 0;
		$fechaticket='0000-00-00 00:00:00';
		$res = mysql_query("SELECT a.cve,a.fecha,a.hora FROM cobro_engomado a LEFT JOIN certificados b ON a.plaza = b.plaza AND a.cve = b.ticket AND b.estatus != 'C'
		WHERE a.plaza = '{$_POST['cveplaza']}' AND a.placa = '{$_POST['placa']}' AND a.anio='{$_POST['anio']}' AND a.estatus!='C' AND ISNULL(b.cve) ORDER BY a.cve DESC");
		if($row = mysql_fetch_array($res)){
			$ticket = $row['cve'];
			$fechaticket=$row['fecha'].' '.$row['hora'];
		}
		$insert = " INSERT certificados_cancelados
							SET 
							plaza = '{$_POST['cveplaza']}', fecha=CURDATE(), hora=CURTIME(), costo='$costo', 
							certificado='{$_POST['certificado']}', motivo='{$_POST['motivo']}', anio='{$_POST['anio']}',
							usuario='{$_POST['cveusuario']}', engomado='{$_POST['engomado']}', estatus='A',
							placa='{$_POST['placa']}', obs='".addslashes($_POST['obs'])."', tecnico='{$_POST['tecnico']}',
							linea='{$_POST['linea']}', ticket='{$ticket}', fechaticket='{$fechaticket}',
							cobro_tecnico='{$_POST['cobro_tecnico']}',cant_empleados='".count($_POST['personales'])."', tipo_combustible='{$_POST['tipo_combustible']}'";
		mysql_query($insert);
		$idcancelacion = mysql_insert_id();
		mysql_query("UPDATE compra_certificados a INNER JOIN compra_certificados_detalle b ON a.plaza = b.plaza AND a.cve = b.cvecompra SET b.estatus=1 WHERE a.plaza='{$_POST['cveplaza']}' AND a.engomado = '{$_POST['engomado']}' AND b.folio='".intval($_POST['certificado'])."' AND a.estatus!='C'");
		if($_POST['cobro_tecnico']==1){
			foreach($_POST['personales'] as $personal){
				mysql_query("INSERT certificados_cancelados_cobro SET plaza='{$_POST['cveplaza']}', cancelacion='{$idcancelacion}', personal='{$personal}'");
			}
		}
		echo '<script>$("#contenedorprincipal").html("");atcr("cancelar_certificados.php","",0,"");</script>';
	}
}

if($_POST['cmd']==31){


?>
<div class="row justify-content-center">
	<div class="col-sm-12" align="center">
	<?php
		if(nivelUsuario() > 1){
	?>
		<button type="button" class="btn btn-success" onClick="atcr('cancelar_certificados.php','',32,'0');">Guardar</button>
	&nbsp;&nbsp;&nbsp;
	<?php
		}
	?>
		<button type="button" class="btn btn-primary" onClick="$('#contenedorprincipal').html('');atcr('cancelar_certificados.php','',0,0);">Volver</button>
	</div>
</div><br>
<div class="row">
	<div class="col-xl-12 col-lg-12 col-md-12">
		<div class="card shadow">
			<div class="card-body">
				<div class="form-row">
			        <div class="form-group col-sm-3">
						<label for="motivo">Motivo de Cancelaci&oacute;n</label>
			          	<select name="motivo" id="motivo" class="form-control"><option value="0">Seleccione</option>
			           	<?php
			           		$res1 = mysql_query("SELECT cve, nombre FROM motivos_cancelacion_certificados ORDER BY nombre");
							while($row1=mysql_fetch_array($res1)){
								echo '<option value="'.$row1['cve'].'">'.utf8_encode($row1['nombre']).'</option>';
							}
						?>
						</select>
			        </div>
			        <div class="form-group col-sm-4">
						<label for="anio">Semetre</label>
			          	<select name="anio" id="anio" class="form-control"><option value="0">Seleccione</option>
			           	<?php
			           		$res1 = mysql_query("SELECT cve, nombre FROM anios_certificados ORDER BY nombre DESC LIMIT 3");
							while($row1=mysql_fetch_array($res1)){
								echo '<option value="'.$row1['cve'].'">'.utf8_encode($row1['nombre']).'</option>';
							}
						?>
						</select>
			        </div>
			    </div>
			    <div class="form-row">
			      	<div class="form-group col-sm-2">
						<label for="certificado1">Certificado Inicial</label>
			          	<input type="number" class="form-control" id="certificado1" value="" name="certificado1" onKeyUp="calcular()">
			        </div>
			        <div class="form-group col-sm-2">
						<label for="certificado2">Certificado Final</label>
			          	<input type="number" class="form-control" id="certificado2" value="" name="certificado2" onKeyUp="calcular()">
			        </div>
			      	<div class="form-group col-sm-3">
						<label for="engomado">Tipo de Certificado</label>
			          	<select name="engomado" id="engomado" class="form-control"><option value="0">Seleccione</option>
			          	<?php
		            	$res1 = mysql_query("SELECT a.cve, a.nombre FROM engomados a inner join engomados_plazas b on a.cve = b.engomado WHERE b.entrega=1 AND b.plaza = '{$_POST['cveplaza']}' ORDER BY a.nombre");
						while($row1=mysql_fetch_array($res1)){
							echo '<option value="'.$row1['cve'].'">'.$row1['nombre'].'</option>';
						}
						?>
						</select>
			        </div>
			    </div>
				<div class="form-row">
			        <div class="form-group col-sm-3">
						<label for="placa">Cantidad</label>
			            <input type="text" class="form-control" id="cantidad" value="" readOnly>
			        </div>
	        		<div class="form-group col-sm-6">
						<label for="obs">Observaciones</label>
	           			<textarea class="form-control" rows="3" id="obs" name="obs"></textarea>
	        		</div>

		        </div>
	      
	    	</div>
	    </div>
	</div>
</div>


<script>

function calcular(){
	var cantidad = 0;
		if(($('#certificado1').val()/1) > 0 && ($('#certificado2').val()/1) > 0 && ($('#certificado1').val()/1)<=($('#certificado2').val()/1)){
			cantidad = ($('#certificado2').val()/1) + 1 - ($('#certificado1').val()/1);
		}
		$('#cantidad').val(cantidad.toFixed(0));
}

</script>

<?php
}

if($_POST['cmd']==32){
	$resultado = array('error' => 0, 'mensaje' => '');
	if(trim($_POST['motivo'])=='0'){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita seleccionar el motivo de cancelación');
	}
	elseif(trim($_POST['anio']) == ''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita seleccionar el semestre');
	}
	elseif(trim($_POST['certificado1']) == ''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita ingresado el certificado inicial');
	}
	elseif(trim($_POST['certificado2']) == ''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita ingresar el certificado final');
	}
	elseif($_POST['certificado2'] < $_POST['certificado1']){
		$resultado = array('error' => 1, 'mensaje' => 'El certificado inicial no puede ser mayor al final');
	}
	elseif(trim($_POST['engomado']) == '0'){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita seleccionar el tipo de certificado');
	}
	if($resultado['error']==1){
		$resultado['mensaje'] = utf8_encode($resultado['mensaje']);
		echo json_encode($resultado);
	}
	else{
		for($holograma=$_POST['certificado']; $holograma<=$_POST['certificado2']; $holograma++){
			$res = mysql_query("SELECT cve, fecha FROM certificados_cancelados WHERE plaza='{$_POST['cveplaza']}' AND engomado='{$_POST['engomado']}' AND certificado ='".intval($holograma)."' AND estatus!='C'");
			if(mysql_num_rows($res)==0){
				$res = mysql_query("SELECT cve, fecha FROM certificados WHERE plaza='{$_POST['cveplaza']}' AND engomado='{$_POST['engomado']}' AND certificado='".intval($holograma)."' AND estatus!='C'");
				if(mysql_num_rows($res)==0){
					$res = mysql_query("SELECT b.cve, a.costo FROM compra_certificados a INNER JOIN compra_certificados_detalle b ON a.plaza = b.plaza AND a.cve = b.cvecompra WHERE a.plaza='{$_POST['cveplaza']}' AND a.engomado = '{$_POST['engomado']}' AND b.folio='".intval($holograma)."' AND a.estatus!='C'");
					if(mysql_num_rows($res)>0){
						$row=mysql_fetch_assoc($res);
						$costo = $row['costo'];
						$ticket = 0;
						$fechaticket='0000-00-00 00:00:00';
						
						$insert = " INSERT certificados_cancelados
											SET 
											plaza = '{$_POST['cveplaza']}',fecha='{$_POST['fecha']}',hora=CURTIME(), costo='{$row['costo']}',
											certificado='{$holograma}',motivo='{$_POST['motivo']}',anio='{$_POST['anio']}',
											usuario='{$_POST['cveusuario']}',engomado='{$_POST['engomado']}',estatus='A',
											placa='',obs='".addslashes($_POST['obs'])."',tecnico='',
											linea='',ticket='$ticket',fechaticket='$fechaticket',
											cobro_tecnico='0',cant_empleados='0'";
						mysql_query($insert);
						$idcancelacion = mysql_insert_id();
						mysql_query("UPDATE compra_certificados a INNER JOIN compra_certificados_detalle b ON a.plaza = b.plaza AND a.cve = b.cvecompra SET b.estatus=1 WHERE a.plaza='{$_POST['cveplaza']}' AND a.engomado = '{$_POST['engomado']}' AND b.folio='".intval($holograma)."' AND a.estatus!='C'");
						
					}
					
				}
				
			}
			
		}
		echo '<script>$("#contenedorprincipal").html("");atcr("cancelar_certificados.php","",0,"");</script>';
	}

}
?>