<?php
error_reporting(E_ERROR | E_PARSE);
require_once('cnx_db.php');
require_once('globales.php');

if($_POST['cmd']==101){
	$res=mysql_db_query($base,"SELECT * FROM plazas");
	while($Plaza=mysql_fetch_array($res)){
		$array_plaza[$row['cve']]=$row['nombre'];
	}

	$rsPuesto=mysql_db_query($base,"SELECT * FROM puestos");
	while($Puesto=mysql_fetch_array($rsPuesto)){
		$array_puesto[$Puesto['cve']]=$Puesto['nombre'];
	}
	include('fpdf/fpdf.php');
	include("numlet.php");
	$res=mysql_query("SELECT * FROM recibos_salida WHERE plaza='{$_POST['cveplaza']}' AND cve='{$_POST['reg']}'");
	$row=mysql_fetch_assoc($res);
	$pdf=new FPDF('P','mm','LETTER');
	$pdf->AddPage();
	$pdf->SetFont('Arial','B',16);
	$Plaza = mysql_fetch_assoc(mysql_query("SELECT numero, nombre FROM plazas WHERE cve='{$_POST['cveplaza']}'"));
	$Motivo = mysql_fetch_assoc(mysql_query("SELECT nombre FROM motivos WHERE cve='{$row['motivo']}'"));
	$Beneficiario = mysql_fetch_assoc(mysql_query("SELECT nombre FROM beneficiarios WHERE cve='{$row['beneficiario']}'"));
	$UsuarioImp = mysql_fetch_assoc(mysql_query("SELECT usuario FROM usuarios WHERE cve='{$_POST['cveusuario']}'"));
	$Usuario = mysql_fetch_assoc(mysql_query("SELECT usuario FROM usuarios WHERE cve='{$row['usuario']}'"));

	$pdf->Cell(190,10,$Plaza['numero'].' '.$Plaza['nombre'],0,0,'C');
	$pdf->Ln();
	$pdf->Cell(95,10,'Recibo de Salida',0,0,'L');
	$pdf->Cell(95,10,'Folio: '.$row['cve'],0,0,'R');
	$pdf->Ln();
	$pdf->SetFont('Arial','B',10);
	$pdf->Cell(95,5,'',0,0,'L');
	$pdf->Cell(95,5,'Bueno por: $ '.number_format($row['monto'],2),0,0,'R');
	$pdf->Ln();
	$y=$pdf->GetY();
	$pdf->MultiCell(95,5,'Motivo: '.$Motivo['nombre'],0,'L');
	$pdf->SetXY(105,$y);
	$pdf->Cell(95,5,'Fecha: '.fecha_letra($row['fecha']),0,0,'R');
	$pdf->Ln();
	$pdf->Ln();
	$pdf->SetFont('Arial','',10);
	$pdf->MultiCell(190,5,"Recibi la cantidad de ".numlet($row['monto']),0,"R");
	$pdf->Ln();
	$pdf->MultiCell(190,5,"Por Concepto de: ".$row['concepto'],0,"R");
	$pdf->Ln();
	$pdf->Ln();
	$pdf->Ln();
	$pdf->SetFont('Arial','U',12);
	$pdf->Cell(60,5,'');
	$pdf->MultiCell(70,5,$Beneficiario['nombre'],0,'C');
	$pdf->Ln();
	$pdf->SetFont('Arial','',12);
	$pdf->Cell(190,5,"Recibi",0,0,'C');
	$pdf->Ln();
	$pdf->Ln();
	$pdf->Ln();
	$pdf->Ln();

	$pdf->SetFont('Arial','',10);
	$pdf->Cell(95,5,'Impreso por: '.$UsuarioImp['usuario'],0,0,'L');
	$pdf->Cell(95,5,'Creado por: '.$Usuario['usuario'],0,0,'R');
	$pdf->Output();	
	exit();	
}

if($_POST['cmd']==33){
	$resultado = array('mensaje' => 'Se cancelo exitosamente', 'tipo'=>'success');

	mysql_query("UPDATE recibos_salida SET estatus='C', usucan='{$_POST['cveusuario']}', fechacan=NOW() WHERE plaza='{$_POST['cveplaza']}' AND cve='{$_POST['recibo']}'");

	echo json_encode($resultado);
	exit();
}
require_once('validarloging.php');

if($_POST['cmd']==0){
	$nivelUsuario = nivelUsuario();
?>

<div class="row justify-content-center">
	<div class="col-xl-12 col-lg-12 col-md-12">
		<div class="form-group row">
			<label class="col-sm-2 col-form-label">Fecha Inicio</label>
			<div class="col-sm-4">
            	<input type="date" class="form-control" id="busquedafechaini" name="busquedafechaini" placeholder="Fecha Inicio">
        	</div>
			<label class="col-sm-2 col-form-label">Fecha Fin</label>
			<div class="col-sm-4">
            	<input type="date" class="form-control" id="busquedafechafin" name="busquedafechafin" placeholder="Fecha Fin">
        	</div>
        </div>

      <div class="form-group row">
			<label class="col-sm-2 col-form-label">Motivo</label>
			<div class="col-sm-4">
            	<select name="busquedamotivo" id="busquedamotivo" class="form-control"><option value="">Todos</option>
            	<?php
            	$res1 = mysql_query("SELECT cve, nombre FROM motivos ORDER BY nombre");
				while($row1=mysql_fetch_array($res1)){
					echo '<option value="'.$row1['cve'].'">'.$row1['nombre'].'</option>';
				}
				?>
            	</select>
        	</div>
        	<label class="col-sm-2 col-form-label">Beneficiario</label>
			<div class="col-sm-4">
            	<select name="busquedabeneficiario" id="busquedabeneficiario" class="form-control"><option value="">Todos</option>
            	<?php
            	$res1 = mysql_query("SELECT cve, nombre FROM beneficiarios ORDER BY nombre");
				while($row1=mysql_fetch_array($res1)){
					echo '<option value="'.$row1['cve'].'">'.$row1['nombre'].'</option>';
				}
				?>
            	</select>
        	</div>
        </div>

        <div class="form-group row">
			
        	<label class="col-sm-2 col-form-label">Usuario</label>
			<div class="col-sm-4">
            	<select name="busquedausuario" id="busquedausuario" class="form-control" data-container="body" data-live-search="true" title="Plantilla" data-hide-disabled="true" data-actions-box="true" data-virtual-scroll="false">
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
	        	<button type="button" class="btn btn-success" onClick="atcr('recibos_salida.php','',1,0);">
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
				<th>Folio</th>
				<th>Fecha</th>
				<th>Beneficiario</th>
				<th>Motivo</th>
				<th>Monto</th>
				<th>Concepto</th>
				<th>Usuario</th>
				<th>&nbsp;</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>Folio</th>
				<th>Fecha</th>
				<th>Beneficiario</th>
				<th>Motivo</th>
				<th>Monto<br><span id="tmonto" style="text-align: right;"></span></th>
				<th>Concepto</th>
				<th>Usuario</th>
				<th>&nbsp;</th>
			</tr>
		</tfoot>
	</table>
</div>
<script>
	var tablalistado = $('#dataTable').DataTable( {
        "ajax": {
        	url: 'recibos_salida.php',
        	type: "POST",
        	"data": {
        		"cmd": 10,
        		"busquedafechaini": $("#busquedafechaini").val(),
        		"busquedafechafin": $("#busquedafechafin").val(),
        		"busquedausuario": $("#busquedausuario").val(),
        		"busquedamotivo": $("#busquedamotivo").val(),
        		"busquedabeneficiario": $("#busquedabeneficiario").val(),
        		"cvemenu": $('#cvemenu').val(),
        		"cveplaza": $('#cveplaza').val(),
        		"cveusuario": $('#cveusuario').val()
        	},
        	fncallback: function(json){
        		$('#tmonto').html(json.monto);
        	}
        },
        "processing": true,
        "serverSide": true,
        "bFilter": false,
        "order": [[0, "DESC"]],
        "columnDefs": [
        	{ className: "dt-head-center dt-body-right", "targets": 0 },
        	{ className: "dt-head-center dt-body-center", "targets": 1 },
        	{ className: "dt-head-center dt-body-left", "targets": 2 },
        	{ className: "dt-head-center dt-body-left", "targets": 3 },
        	{ className: "dt-head-center dt-body-right", "targets": 4 },
        	{ className: "dt-head-center dt-body-left", "targets": 5 },
        	{ className: "dt-head-center dt-body-left", "targets": 6 },
        	{ className: "dt-head-center dt-body-center", "targets": 7 },
        	{ orderable: false, "targets": 7 }
		  ]
    } );
	function buscar(){
		tablalistado.ajax.data({
    		"cmd": 10,
    		"busquedafechaini": $("#busquedafechaini").val(),
    		"busquedafechafin": $("#busquedafechafin").val(),
    		"busquedausuario": $("#busquedausuario").val(),
    		"busquedamotivo": $("#busquedamotivo").val(),
        	"busquedabeneficiario": $("#busquedabeneficiario").val(),
    		"cvemenu": $('#cvemenu').val(),
    		"cveplaza": $('#cveplaza').val(),
    		"cveusuario": $('#cveusuario').val()
        });
        tablalistado.ajax.reload();
	}

	function cancelarsalida(salida){
		if (confirm("Esta seguro de cancelar la salida?")){
			$('#modalCancelacion').modal('hide');
			waitingDialog.show();
			$.ajax({
				url: 'recibos_salida.php',
				type: "POST",
				dataType: 'json',
				data: {
					cmd: 33,
					salida: salida,
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

	
</script>
<?php
}

if($_POST['cmd']==10){
	$columnas=array("a.cve", "a.fecha", "b.nombre", "c.nombre", 'a.monto', 'a.concepto', 'd.usuario');

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

		if($_POST['busquedabeneficiario']!=''){
			$where .= " AND a.beneficiario = '{$_POST['busquedabeneficiario']}'";
		}

		if($_POST['busquedamotivo']!=''){
			$where .= " AND a.motivo = '{$_POST['busquedamotivo']}'";
		}

		if($_POST['busquedausuario']!=''){
			$where .= " AND a.usuario = '{$_POST['busquedausuario']}'";
		}

	$res = mysql_query("SELECT COUNT(a.cve) as registros, SUM(IF(a.estatus!='C', a.monto, 0)) as monto FROM recibos_salida a {$where}");
	$registros = mysql_fetch_assoc($res);
	$resultado = array(
		'data' => array(),
		'draw'=> $_POST['draw'],
		'recordsTotal'=> $registros['registros'],
		'recordsFiltered'=> $registros['registros'],
		'monto' => $registros['monto'],
	);
	$res = mysql_query("SELECT a.cve, a.fecha, b.nombre as nombeneficiario, c.nombre as nommotivo, IF(a.estatus='C',0,a.monto) as monto, a.concepto, d.usuario, a.estatus FROM recibos_salida a INNER JOIN beneficiarios b ON b.cve = a.beneficiario INNER JOIN motivos c ON c.cve = a.motivo INNER JOIN usuarios d ON d.cve = a.usuario{$where}{$orderby} LIMIT {$_POST['start']},{$_POST['length']}");
	$tmonto = 0;
	$nivelUsuario = nivelUsuario();
	while($row = mysql_fetch_assoc($res)){
		
		$extras2 = '';
		if ($row['estatus'] == 'A' && $nivelUsuario >= 3) {
			$extras2 .= '<a class="dropdown-item" href="#" onClick="cancelarsalida('.$row['cve'].')">Cancelar</a>';
		}

		$dropmenu = '<button class="btn btn-info dropdown-toggle" type="button" id="dropdownMenuButton_'.$row['cve'].'" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                      Acci&oacute;n
                    </button><div class="dropdown-menu animated--fade-in" aria-labelledby="dropdownMenuButton_'.$row['cve'].'" x-placement="bottom-start" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(0px, 38px, 0px);">
                      <a class="dropdown-item" href="#" onClick="atcr(\'recibos_salida.php\',\'\',101,'.$row['cve'].')">Imprimir</a>
                      '.$extras2.'
                    </div>';
    if($row['estatus']=='C'){
    	$dropmenu='CANCELADO';
    }
    
		$resultado['data'][] = array(
			($row['cve']),
			mostrar_fechas($row['fecha']),
			utf8_encode($row['nombeneficiario']),
			utf8_encode($row['nommotivo']),
			number_format($row['monto'],2),
			utf8_encode($row['concepto']),
			utf8_encode($row['usuario']),
			$dropmenu,
		);
	}
	echo json_encode($resultado);

}

if($_POST['cmd']==1){

	function saldo_gastos($plaza){
		$saldo = 0;
		$row = mysql_fetch_assoc(mysql_query("SELECT SUM(monto) as monto FROM reembolsos WHERE plaza={$plaza} AND estatus!='C'"));
		$saldo+=$row['monto'];
		$row = mysql_fetch_assoc(mysql_query("SELECT SUM(monto) as monto FROM recibos_salida WHERE plaza={$plaza} AND estatus!='C'"));
		$saldo-=$row['monto'];
		return $saldo;
	}

?>
<div class="row justify-content-center">
	<div class="col-sm-12" align="center">
	<?php
		if(nivelUsuario() > 1){
	?>
		<button type="button" class="btn btn-success" onClick="atcr('recibos_salida.php','',2,'0');">Guardar</button>
	&nbsp;&nbsp;&nbsp;
	<?php
		}
	?>
		<button type="button" class="btn btn-primary" onClick="$('#contenedorprincipal').html('');atcr('recibos_salida.php','',0,0);">Volver</button>
	</div>
</div><br>
<div class="row">
	<div class="col-xl-12 col-lg-12 col-md-12">
		<div class="card shadow">
			<div class="card-body">
				<div class="form-row">
			        <div class="form-group col-sm-3">
						<label for="fecha">Fecha</label>
			           <input type="date" class="form-control" id="fecha" value="<?php echo date('Y-m-d');?>" name="fecha" readOnly>
			        </div>
			        <div class="form-group col-sm-3">
						<label for="fecha">Saldo</label>
			           <input type="number" class="form-control" id="saldo" value="<?php echo saldo_gastos($_POST['cveplaza']);?>" name="saldo" readOnly>
			        </div>
			      </div>
			      <div class="form-row">
			      	<div class="form-group col-sm-2">
						<label for="monto">Monto</label>
			          	<input type="number" class="form-control" id="monto" value="" name="monto">
			        </div>
			      </div>
			      <div class="form-row">
			        <div class="form-group col-sm-6">
								<label for="motivo">Motivo</label>
			          <select name="motivo" id="motivo" class="form-control"><option value="">Seleccione</option>
			           	<?php
			           		$res1 = mysql_query("SELECT cve, nombre FROM motivos ORDER BY nombre");
										while($row1=mysql_fetch_array($res1)){
									echo '<option value="'.$row1['cve'].'"">'.utf8_encode($row1['nombre']).'</option>';
								}
							?>
						</select>
			        </div>
			      </div>
			      <div class="form-row">
			        <div class="form-group col-sm-6">
								<label for="beneficiario">Beneficiario</label>
			          <select name="beneficiario" id="beneficiario" class="form-control"><option value="">Seleccione</option>
			           	<?php
			           		$res1 = mysql_query("SELECT cve, nombre FROM beneficiarios ORDER BY nombre");
										while($row1=mysql_fetch_array($res1)){
											echo '<option value="'.$row1['cve'].'">'.utf8_encode($row1['nombre']).'</option>';
										}
									?>
								</select>
			        </div>
			      </div>
			      <div class="form-row">
			        <div class="form-group col-sm-6">
			        	<label for="concepto">Concepto</label>
			        	<textarea rows="3" id="concepto" name="concepto" class="form-control"></textarea>
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
	if(trim($_POST['motivo'])==''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita seleccionar el motivo');
	}
	elseif(trim($_POST['beneficiario']) == ''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita seleccionar el beneficiario');
	}
	elseif(trim($_POST['concepto']) == ''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita ingresar el concepto');
	}
	elseif($_POST['monto']==0){
		$resultado = array('error' => 1, 'mensaje' => 'El monto no debe de ser mayor a cero');
	}
	
	if($resultado['error']==1){
		echo json_encode($resultado);
	}
	else{
		
		$insert = " INSERT recibos_salida 
					SET 
					plaza = '{$_POST['cveplaza']}',fecha=CURDATE(),hora=CURTIME(),
					motivo='{$_POST['motivo']}',beneficiario='{$_POST['beneficiario']}',monto='{$_POST['monto']}',
					usuario='{$_POST['cveusuario']}',estatus='A',concepto='".addslashes($_POST['concepto'])."'";
			mysql_query($insert);
			$cvecobro = mysql_insert_id();
			
		echo '<script>$("#contenedorprincipal").html("");atcr("recibos_salida.php","",0,"");atcr("recibos_salida.php","_blank",101,"'.$cvecobro.'");</script>';
	}
}


?>