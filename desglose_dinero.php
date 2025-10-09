<?php
error_reporting(E_ERROR | E_PARSE);
require_once('cnx_db.php');
require_once('globales.php');
$array_denominaciones=array('1000','500','200','100','50','20','10','5','2','1','0.50','0.20','0.10','0.05');

if($_GET['cmd']==101){
	require_once("numlet.php");
	$res=mysql_query("SELECT * FROM desglose_dinero WHERE plaza='{$_GET['cveplaza']}' AND cve='{$_GET['cvedesglose']}'");
	$row=mysql_fetch_array($res);
	$resPlaza = mysql_query("SELECT numero,nombre,bloqueada_sat FROM plazas WHERE cve='{$row['plaza']}'");
	$rowPlaza = mysql_fetch_array($resPlaza);
	$Usuario = mysql_fetch_assoc(mysql_query("SELECT nombre FROM usuarios WHERE cve='{$_GET['cveusuario']}'"));
	$texto=chr(27)."@";
	$texto.=''.chr(10).chr(13);
	$texto.=chr(27).'!'.chr(8)." ".$rowPlaza['numero'].' '.$rowPlaza['nombre'];
	$texto.=''.chr(10).chr(13);
	$texto.=''.chr(10).chr(13);
	$texto.=chr(27).'!'.chr(8)." FOLIO: ".$row['folio'];
	$texto.=''.chr(10).chr(13);
	$texto.=chr(27).'!'.chr(8)." DESGLOSE DE DINERO";
	$texto.=''.chr(10).chr(13);
	$texto.=chr(27).'!'.chr(8)." FECHA: ".$row['fecha']."   ".$row['hora'];
	$texto.=''.chr(10).chr(13);
	$texto.=chr(27).'!'.chr(8)." USUARIO: ".$Usuario['nombre'];
	$texto.=''.chr(10).chr(13);
	$res1=mysql_query("SELECT * FROM desglose_dineromov WHERE plaza='{$_GET['cveplaza']}' AND desglose='{$_GET['cvedesglose']}'");
	while($row1=mysql_fetch_array($res1)){
		$texto.=chr(27).'!'.chr(8)." ".sprintf("%-5s",$row1['denominacion'])." C: ".sprintf("%-3s",$row1['cantidad'])." I: ".sprintf("%10s",$row1['importe']);
		$texto.=''.chr(10).chr(13);
	}
	$texto.=''.chr(10).chr(13);
	$texto.=chr(27).'!'.chr(8)." TOTAL: ".$row['monto'];
	$texto.=''.chr(10).chr(13);
	$texto.=''.chr(10).chr(13);
	$texto.='USUARIO';
	$texto.=''.chr(10).chr(13);
	$texto.=''.chr(10).chr(13);
	$texto.='___________________________';
	$texto.=''.chr(10).chr(13);
	$texto.=' '.$Usuario['nombre'];
	$texto.=''.chr(10).chr(13);
	$texto.=''.chr(10).chr(13);
	$texto.=''.chr(10).chr(13);
	$texto.=''.chr(10).chr(13);
	$texto.=''.chr(10).chr(13);
	$texto.=''.chr(10).chr(13);
	$texto.=''.chr(10).chr(13);
	$texto.=''.chr(10).chr(13);
	$texto.=''.chr(10).chr(13);
	$texto.=''.chr(10).chr(13);
	$texto.=''.chr(10).chr(13);
	$texto.=''.chr(10).chr(13);
	$texto.=''.chr(10).chr(13);
	$texto.=''.chr(10).chr(13);
	$texto.=''.chr(10).chr(13);
	$texto.=''.chr(10).chr(13);
	$texto.=chr(10).chr(13).chr(29).chr(86).chr(66).chr(0);
	echo $texto;
	exit();
}

if($_POST['cmd']==33){
	$resultado = array('mensaje' => 'Se cancelo exitosamente', 'tipo'=>'success');
	mysql_query("UPDATE desglose_dinero SET estatus='C', usucan='{$_POST['cveusuario']}', fechacan=NOW() WHERE plaza='{$_POST['cveplaza']}' AND cve='{$_POST['desglose']}'");

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

        	<label class="col-sm-2 col-form-label">Usuario</label>
			<div class="col-sm-4">
            	<select name="busquedausuario" id="busquedausuario" class="form-control" data-container="body" data-live-search="true" title="Plantilla" data-hide-disabled="true" data-actions-box="true" data-virtual-scroll="false"><option value="">Todos</option>
            	<?php
            	$res1 = mysql_query("SELECT b.cve, b.usuario FROM (SELECT usuario FROM desglose_dinero WHERE plaza='{$_POST['cveplaza']}' GROUP BY usuario) a INNER JOIN usuarios b ON b.cve = a.usuario ORDER BY b.usuario");
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
	        	<button type="button" class="btn btn-success" onClick="atcr('desglose_dinero.php','',1,0);">
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
				<th>Usuario</th>
				<th>Monto</th>
				<th>Observaciones</th>
				<th>&nbsp;</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>Folio</th>
				<th>Fecha</th>
				<th>Usuario</th>
				<th>Monto<br><span id="tmonto" style="text-align: right;"></span></th>
				<th>Observaciones</th>
				<th>&nbsp;</th>
			</tr>
		</tfoot>
	</table>
</div>
<script>
	var tablalistado = $('#dataTable').DataTable( {
        "ajax": {
        	url: 'desglose_dinero.php',
        	type: "POST",
        	"data": {
        		"cmd": 10,
        		"busquedafechaini": $("#busquedafechaini").val(),
        		"busquedafechafin": $("#busquedafechafin").val(),
        		"busquedausuario": $("#busquedausuario").val(),
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
        "order": [[1, "DESC"]],
        "columnDefs": [
        	{ className: "dt-head-center dt-body-right", "targets": 0 },
        	{ className: "dt-head-center dt-body-center", "targets": 1 },
        	{ className: "dt-head-center dt-body-left", "targets": 2 },
        	{ className: "dt-head-center dt-body-right", "targets": 3 },
        	{ className: "dt-head-center dt-body-left", "targets": 4 },
        	{ className: "dt-head-center dt-body-center", "targets": 5 },
        	{ orderable: false, "targets": 5 }
		  ]
    } );
	function buscar(){
		tablalistado.ajax.data({
    		"cmd": 10,
    		"busquedafechaini": $("#busquedafechaini").val(),
    		"busquedafechafin": $("#busquedafechafin").val(),
    		"busquedausuario": $("#busquedausuario").val(),
    		"cvemenu": $('#cvemenu').val(),
    		"cveplaza": $('#cveplaza').val(),
    		"cveusuario": $('#cveusuario').val()
        });
        tablalistado.ajax.reload();
	}

	function cancelarcve(cvedesglose){
		if (confirm("Esta seguro de cancelar el desglose de dinero?")){
			waitingDialog.show();
			$.ajax({
				url: 'desglose_dinero.php',
				type: "POST",
				dataType: 'json',
				data: {
					cmd: 33,
					desglose: cvedesglose,
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
	$columnas=array("a.folio", "CONCAT(a.fecha,' ',a.hora)", "b.usuario", "IF(a.estatus='C',0,a.monto)", "a.obs");

	$orderby = "";
	foreach($_POST['order'] as $dato){
		$orderby .= ",{$columnas[$dato['column']]} {$dato['dir']}";
	}

	if($orderby == ""){
		$orderby = " ORDER BY CONCAT(a.fecha,' ',a.hora) DESC";
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

		if($_POST['busquedausuario']!=''){
			$where .= " AND a.usuario = '{$_POST['busquedausuario']}'";
		}



	$res = mysql_query("SELECT COUNT(a.cve) as registros, SUM(IF(a.estatus!='C',a.monto,0)) as monto FROM desglose_dinero a {$where}");
	$registros = mysql_fetch_assoc($res);
	$resultado = array(
		'data' => array(),
		'draw'=> $_POST['draw'],
		'recordsTotal'=> $registros['registros'],
		'recordsFiltered'=> $registros['registros'],
		'monto' => $registros['monto']
	);
	$res = mysql_query("SELECT a.cve, a.folio, CONCAT(a.fecha,' ',a.hora) as fechahora, b.usuario as nomusuario, IF(a.estatus!='C',a.monto,0) as monto, a.estatus, a.obs FROM desglose_dinero a INNER JOIN usuarios b ON b.cve = a.usuario{$where}{$orderby} LIMIT {$_POST['start']},{$_POST['length']}");
	$tmonto = 0;
	$nivelUsuario = nivelUsuario();
	while($row = mysql_fetch_assoc($res)){
		
		$extras2 = '';
		if ($row['estatus'] == 'A' && $nivelUsuario >= 3) {
			$extras2 .= '<a class="dropdown-item" href="#" onClick="cancelarcve('.$row['cve'].')">Cancelar</a>';
		}

		$dropmenu = '<button class="btn btn-info dropdown-toggle" type="button" id="dropdownMenuButton_'.$row['cve'].'" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                      Acci&oacute;n
                    </button><div class="dropdown-menu animated--fade-in" aria-labelledby="dropdownMenuButton_'.$row['cve'].'" x-placement="bottom-start" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(0px, 38px, 0px);">
                      <a class="dropdown-item" href="#" onClick="atcr(\'desglose_dinero.php\',\'_blank\',101,'.$row['cve'].')">Imprimir</a>
                      '.$extras2.'
                    </div>';
    if($row['estatus']=='C'){
    	$dropmenu='CANCELADO';
    }
		$resultado['data'][] = array(
			($row['folio']),
			mostrar_fechas(substr($row['fechahora'],0,10)).' '.substr($row['fechahora'],11),
			utf8_encode($row['nomusuario']),
			number_format($row['monto'],2),
			utf8_encode($row['obs']),
			$dropmenu
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
		<button type="button" class="btn btn-success" onClick="atcr('desglose_dinero.php','',2,'0');">Guardar</button>
	&nbsp;&nbsp;&nbsp;
	<?php
		}
	?>
		<button type="button" class="btn btn-primary" onClick="$('#contenedorprincipal').html('');atcr('desglose_dinero.php','',0,0);">Volver</button>
	</div>
</div><br>
<div class="row">
	<div class="col-xl-12 col-lg-12 col-md-12">
		<div class="card shadow">
			<div class="card-body">
				<div class="form-row">
			        <div class="form-group col-sm-3">
									<label for="fecha">Fecha</label>
			            <input type="date" class="form-control" id="fecha" name="fecha" value="<?php echo date('Y-m-d');?>">
			        </div>
				</div>
				<div class="form-row">
					<div class="col-sm-2" style="text-align: center;"><label></label></div>
					<div class="col-sm-2" style="text-align: center;"><label>Cantidad</label></div>
					<div class="col-sm-2" style="text-align: center;"><label>Importe</label></div>
				</div>
				<?php foreach($array_denominaciones as $k=>$v){ ?>
					<div class="form-row">
						<div class="col-sm-2" style="text-align: center;"><label><?php echo $v;?></label></div>
						<div class="col-sm-2" style="text-align: center;"><input type="number" class="form-control" name="cantidad[<?php echo $k;?>]" value="" id="cantidad_<?php echo $k; ?>" onKeyUp="calcular(<?php echo $k;?>)" value="" denominacion="<?php echo $v;?>"></div>
						<div class="col-sm-2" style="text-align: center;"><input type="number" class="form-control importes" name="importe[<?php echo $k;?>]" value="" id="importe_<?php echo $k; ?>" value="" readOnly></div>
					</div>
				<?php } ?>
				<div class="form-row">
					<div class="col-sm-2" style="text-align: center;"><label></label></div>
						<div class="col-sm-2" style="text-align: center;"><label>Total</label></div>
						<div class="col-sm-2" style="text-align: center;"><input type="number" class="form-control" name="monto" value="" id="monto" value="" readOnly></div>
					</div>
				<div class="form-row">
			        <div class="form-group col-sm-6">
									<label for="motivo">Observaciones</label>
			          	<textarea name="obs" id="obs" class="form-control" rows="3"></textarea>
			        </div>
			    </div>
	     	
	    	</div>
	  	</div>
	</div>
</div>


<script>

function calcular(renglon) {
	var campo = $('#cantidad_'+renglon);
	var monto = campo.val() * campo.attr('denominacion');
	$('#importe_'+renglon).val(monto.toFixed(2));
	var total = 0;
	$('.importes').each(function(){
		total += this.value/1;
	});
	$('#monto').val(total.toFixed(2));
}


</script>

<?php
}

if($_POST['cmd']==2){
	$resultado = array('error' => 0, 'mensaje' => '');
	if(trim($_POST['fecha'])==''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita ingresar la fecha');
	}
	elseif(($_POST['monto']/1) <= 0){
		$resultado = array('error' => 1, 'mensaje' => 'El total debe de ser mayor a cero');
	}
	
	
	if($resultado['error']==1){
		$resultado['mensaje'] = utf8_encode($resultado['mensaje']);
		echo json_encode($resultado);
	}
	else{
		$res=mysql_query("SELECT IFNULL(MAX(folio)+1,1) FROM desglose_dinero WHERE plaza='{$_POST['cveplaza']}' AND fecha='{$_POST['fecha']}'");
		$row=mysql_fetch_array($res);
		$folio=$row[0];
		$insert = " INSERT desglose_dinero 
						SET 
						plaza = '{$_POST['cveplaza']}',fecha='{$_POST['fecha']}', hora=CURTIME(),
						monto='{$_POST['monto']}', usuario='{$_POST['cveusuario']}', estatus='A', folio='$folio',
						obs='".addslashes($_POST['obs'])."', fecha_creacion=CURDATE()";
		mysql_query($insert);
		$cvedesglose = mysql_insert_id();
		foreach($array_denominaciones as $k=>$v){
			mysql_query("INSERT desglose_dineromov SET plaza='{$_POST['cveplaza']}',desglose='{$cvedesglose}',
			denominacion='{$v}',cantidad='{$_POST['cantidad'][$k]}',importe='{$_POST['importe'][$k]}'");
		}

		echo '<script>$("#contenedorprincipal").html("");atcr("desglose_dinero.php","_blank",101,"'.$cvedesglose.'");atcr("desglose_dinero.php","",0,"");</script>';
	}
}

if($_POST['cmd']==101){
	/*$variables = array(
		'server' => '',
		'printer' => 'impresoratermica',
		'url' => $url_impresion.'/desglose_dinero.php?cmd=101&cveplaza='.$_POST['cveplaza'].'&cvedesglose='.$_POST['reg'].'&cveusuario='.$_POST['cveusuario'].'&reimpresion='.$_GET['reimpresion']
	);
	$impresion='<iframe src="http://localhost:8020/?'.http_build_query($variables).'" width=200 height=200></iframe>';*/
	require_once("numlet.php");
	$res=mysql_query("SELECT * FROM desglose_dinero WHERE plaza='{$_POST['cveplaza']}' AND cve='{$_POST['reg']}'");
	$row=mysql_fetch_array($res);
	$resPlaza = mysql_query("SELECT numero,nombre,bloqueada_sat FROM plazas WHERE cve='{$row['plaza']}'");
	$rowPlaza = mysql_fetch_array($resPlaza);
	$Usuario = mysql_fetch_assoc(mysql_query("SELECT nombre FROM usuarios WHERE cve='{$_POST['cveusuario']}'"));
	$texto=chr(27)."@";
	$texto.='|';
	$texto.=chr(27).'!'.chr(8)." ".$rowPlaza['numero'].' '.$rowPlaza['nombre'];
	$texto.='|';
	$texto.='|';
	$texto.=chr(27).'!'.chr(8)." FOLIO: ".$row['folio'];
	$texto.='|';
	$texto.=chr(27).'!'.chr(8)." DESGLOSE DE DINERO";
	$texto.='|';
	$texto.=chr(27).'!'.chr(8)." FECHA: ".$row['fecha']."   ".$row['hora'];
	$texto.='|';
	$texto.=chr(27).'!'.chr(8)." USUARIO: ".$Usuario['nombre'];
	$texto.='|';
	$res1=mysql_query("SELECT * FROM desglose_dineromov WHERE plaza='{$_POST['cveplaza']}' AND desglose='{$_POST['reg']}'");
	while($row1=mysql_fetch_array($res1)){
		$texto.=chr(27).'!'.chr(8)." ".sprintf("%-5s",$row1['denominacion'])." C: ".sprintf("%-3s",$row1['cantidad'])." I: ".sprintf("%10s",$row1['importe']);
		$texto.='|';
	}
	$texto.='|';
	$texto.=chr(27).'!'.chr(8)." TOTAL: ".$row['monto'];
	$texto.='|';
	$texto.='|';
	$texto.='USUARIO';
	$texto.='|';
	$texto.='|';
	$texto.='___________________________';
	$texto.='|';
	$texto.=' '.$Usuario['nombre'];
	$texto.='||||||||||||||||';
	$impresion='<iframe src="http://localhost/impresiongenerallogo.php?textoimp='.$texto.'&logo='.str_replace(' ','',$rowPlaza['numero']).'" width=200 height=200></iframe>';
	echo '<html><body>'.$impresion.'</body></html>';
	echo '<script>setTimeout("window.close()",5000);</script>';
}

?>