<?php
error_reporting(E_ERROR | E_PARSE);
require_once('cnx_db.php');
require_once('globales.php');


function obtener_informacion($datos){
	$resultado = array('informacion' => array(), 'totales' => array());
	$totales = array();
	$array_motivos_intento = array();
	$res = mysql_query("SELECT * FROM motivos_intento WHERE 1 ORDER BY nombre");
	while($row=mysql_fetch_array($res)){
		$array_motivos_intento[$row['cve']]=$row['nombre'];
	}
	$c=1;
	$array_engomados = array();
	$resE = mysql_query("SELECT a.numero, a.nombre, b.precio, GROUP_CONCAT(a.cve) as cves FROM engomados a inner join engomados_plazas b on a.cve = b.engomado WHERE b.plaza = '{$_POST['cveplaza']}' AND (b.entrega=1 OR b.venta=1) GROUP BY a.numero ORDER BY a.numero");
	while($rowE = mysql_fetch_assoc($resE)){
		$array_engomados[] = $rowE;
		$select = "SELECT a.* FROM (
		SELECT a.placa,a.ticket,b.fecha as fechaticket,a.cve, IF(b.fecha!=a.fecha,6,b.tipo_pago) as tipo_pago, 0 as cancelado, a.engomado as engomadoentrega, 
		if(b.tipo_pago=6 OR b.tipo_pago=12 OR b.fecha!=a.fecha,0,b.monto) as monto, a.certificado as certificado, IF(a.fecha!=b.fecha,1,0) as diffechas, b.motivo_intento, b.engomado as engomadoventa, b.anio,
			b.tipo_venta
		FROM certificados a 
		INNER JOIN cobro_engomado b ON b.plaza = a.plaza AND b.cve = a.ticket 
		WHERE a.plaza = '{$datos['cveplaza']}' AND a.fecha='{$datos['busquedafecha']}' AND a.estatus != 'C' AND a.engomado IN ({$rowE['cves']})
		UNION ALL 
		SELECT '' as placa,'' as ticket,'' as fechaticket,'' as cve, 0 as tipo_pago, 1 as cancelado, engomado as engomadoentrega,  0 as monto, certificado, 0 as diffechas, 0 as motivo_intento, 0 as engomadoventa, anio, 0 as tipo_venta FROM certificados_cancelados 
		WHERE plaza='{$datos['cveplaza']}' AND fecha='{$datos['busquedafecha']}' AND engomado IN ({$rowE['cves']}) AND estatus!='C') as a ORDER BY a.certificado";
		$res = mysql_query($select);
		$fcertificado=-1;
		while($row = mysql_fetch_assoc($res)) {
			if($fcertificado<0) $fcertificado = $row['certificado'];
			if($fcertificado!=$row['certificado']){
				$resOmiso = mysql_query("SELECT b.folio FROM compra_certificados a INNER JOIN compra_certificados_detalle b ON a.plaza = b.plaza AND a.cve = b.cvecompra WHERE a.plaza={$datos['cveplaza']} AND a.estatus!='C' AND a.engomado = {$row['engomadoentrega']} AND b.folio>={$fcertificado} AND b.folio<{$row['certificado']}' AND b.tipo=1 ORDER BY b.folio");
				while($rowOmiso = mysql_fetch_array($resOmiso)){
					$resultado['informacion'][] = array(
						'tipo' => 'OMISO',
						'certificado' => $rowOmiso['folio'],
						'color' => 'RED'
					);
					$fcertificado=$rowOmiso['folio']+1;
				}
			}
			$renglon = array();
			if($row['tipo_venta']==3 && $row['monto']>0){
				$tipo='R';
			}
			elseif($row['tipo_venta'] == 4)
			{
				$tipo='PI';
				$row['monto'] = 0;
			}
			elseif($row['tipo_pago']==1 && $row['monto']>0){
				$tipo='E';
			}
			elseif(($row['tipo_pago']==2) && $row['monto']>0){
				$tipo='C';
			}
			elseif(($row['tipo_pago']==5) && $row['monto']>0){
				$tipo='TC';
			}
			elseif(($row['tipo_pago']==7) && $row['monto']>0){
				$tipo='TD';
			}
			else{
				$tipo='';
			}
			$res2 = mysql_query("SELECT a.fecha,a.cve,a.monto FROM cobro_engomado a INNER JOIN certificados b ON a.plaza = b.plaza AND a.cve = b.ticket WHERE a.plaza = {$datos['cveplaza']}' AND a.cve>{$row['ticket']} AND a.placa = '{$row['placa']}' AND a.estatus!='C' AND b.estatus!='C' ORDER BY a.cve LIMIT 1");
			$row2 = mysql_fetch_array($res2);
			$res3 = mysql_query("SELECT b.fecha,a.cve,b.cve FROM cobro_engomado a INNER JOIN certificados b ON a.plaza = b.plaza AND a.cve = b.ticket WHERE a.plaza = {$datos['cveplaza']} AND a.cve<{$row['ticket']} AND a.placa = '{$row['placa']}' AND a.estatus!='C' AND b.estatus!='C' AND b.engomado NOT IN (9,19) ORDER BY a.cve DESC LIMIT 1");
			$row3 = mysql_fetch_array($res3);
			$res1 = mysql_query("SELECT fecha,cve,monto FROM cobro_engomado WHERE plaza = {$datos['cveplaza']} AND cve<{$row['ticket']} AND placa = '{$row['placa']}' AND anio={$row['anio']}' AND estatus!='C' ORDER BY cve DESC LIMIT 1");
			$row1 = mysql_fetch_array($res1);
			$renglon['tipo'] = $tipo;
			$renglon['consecutivo'] = $c;
			$renglon['certificado'] = $row['certificado'];
			if($fcertificado!=$row['certificado']){
				$renglon['color'] = 'RED';
				$fcertificado=$row['certificado'];
			}
			$renglon['ticket'] = $row['ticket'];
			$renglon['placa'] = $row['placa'];

			if($row['monto']==0 && $row['fechaticket']>$row1['fecha']){
				if($row['tipo_pago']==6){
					$renglon['fecha_venta'] = 'DEPOSITO';
				}
				elseif($row1['fecha']==''){
					$renglon['fecha_venta'] = '<font color="RED">'.$array_motivos_intento[$row['motivo_intento']].'</font></td>';
				}
				else{
					$renglon['fecha_venta'] = $row1['fecha'];
				}
				
			}
			elseif($row['diffechas']!=1){
				$renglon['fecha_venta'] = $row['fechaticket'];
			}
			else{
				$renglon['fecha_venta'] = '<font color="RED">'.$row['fechaticket'].'</font></td>';
			}

			if($row['cancelado']==1){
				$renglon['engomado_'.$rowE['numero']] = 'CA';
			}
			elseif($row['tipo_venta']==2){
				$renglon['engomado_'.$rowE['numero']] = 'CT';
			}
			elseif($row['tipo_pago']==6){
				$renglon['engomado_'.$rowE['numero']] = 'PA';
			}
			elseif($row['tipo_pago']==12){
				$renglon['engomado_'.$rowE['numero']] = 'VE';
			}
			elseif($row2['monto']==0 && $row['fechaticket']==$row2['fecha'] && $row3['fecha']!=$row['fechaticket']){
				$renglon['engomado_'.$rowE['numero']] = 'RV';
			}
			elseif($row['monto']==0 && $row['fechaticket']>$row1['fecha']){
				$renglon['engomado_'.$rowE['numero']] = 'PA';
			}
			else{
				$renglon['engomado_'.$rowE['numero']] = $rowE['numero'];
			}
			if($tipo == 'C') $row['monto'] = 0;
			$iva=round($row['monto']*16/116,2);
			$subtotal=round($row['monto']-$iva,2);
			$renglon['subtotal'] = $subtotal;
			$renglon['iva'] = $iva;
			$renglon['total'] = $row['monto'];
			$renglon['folio_entrega'] = $row['cve'];
			if($tipo == '') $tipo = 'P';
			$totales[$tipo]+=$row['monto'];
			$fcertificado+=1;

			$resultado['informacion'][] = $renglon;
			$c++;

		}
	}

	foreach($array_engomados as $rowE){
		$select = "SELECT a.cve,a.fecha,a.placa, a.monto, IF(a.tipo_pago=6 OR a.tipo_pago=12,0,a.monto) as monto,a.tipo_pago,a.tipo_venta FROM cobro_engomado a  
		LEFT JOIN certificados b ON a.plaza = b.plaza AND a.cve = b.ticket AND a.fecha=b.fecha AND b.estatus!='C'
		WHERE a.plaza={$datos['cveplaza']}' AND a.fecha='{$datos['busquedafecha']}' AND a.engomado IN ({$rowE['cves']}) AND a.estatus!='C' AND a.monto > 0 AND ISNULL(b.cve) ORDER BY a.cve";
		$res = mysql_query($select);
		while($row = mysql_fetch_array($res)){
			$renglon = array();
			if($row['tipo_venta'] == 3) $tipo = 'R';
			else $tipo='NV';
			$renglon['tipo'] = $tipo;
			$renglon['consecutivo'] = $c;
			if($row['tipo_pago']==7){
				$renglon['certificado'] = 'TD';
			}
			elseif($row['tipo_pago']==5){
				$renglon['certificado'] = 'TC';
			}
			$renglon['ticket'] = $row['cve'];
			$renglon['placa'] = $row['placa'];
			$renglon['fecha_venta'] = $row['fecha'];
			if($row['tipo_pago']==6){
				$renglon['engomado_'.$rowE['numero']] = 'PA';
			}
			elseif($row['tipo_pago']==12){
				$renglon['engomado_'.$rowE['numero']] = 'VE';
			}
			elseif($row['tipo_venta'] == 4)
			{
				$renglon['engomado_'.$rowE['numero']] = 'PI';
				$row['monto'] = 0;
			}
			else{
				$renglon['engomado_'.$rowE['numero']] = 'Sin Verificar';
			}
			if($row['tipo_pago'] == 2) $row['monto'] = 0;	
			$iva=round($row['monto']*16/116,2);
			$subtotal=round($row['monto']-$iva,2);
			$renglon['subtotal'] = $subtotal;
			$renglon['iva'] = $iva;
			$renglon['total'] = $row['monto'];
			$renglon['folio_entrega'] = '';
			$totales['NV']+=$row['monto'];
			$resultado['informacion'][] = $renglon;
			$c++;
		}
	}
	$tpa=0;
	$res = mysql_query("SELECT * FROM pagos_caja WHERE plaza={$datos['cveplaza']} AND estatus!='C' AND fecha='{$_POST['busquedafecha']}' ORDER BY cve");
	while($row = mysql_fetch_array($res)){
		$renglon = array();
		$tipo='PA';
		$renglon['tipo'] = $tipo;
		$renglon['consecutivo'] = $c;
		$renglon['ticket'] = $row['cve'];
		$renglon['fecha_venta'] = $row['fecha'];
		if($row['forma_pago'] != 1 && $row['forma_pago'] != 3) $row['monto']=0;
		$iva=round($row['monto']*16/116,2);
		$iva=0;
		$subtotal=round($row['monto']-$iva,2);
		$renglon['subtotal'] = $subtotal;
		$renglon['iva'] = $iva;
		$renglon['total'] = $row['monto'];
		$renglon['folio_entrega'] = '';
		$resultado['informacion'][] = $renglon;
		$totales[$tipo]+=$row['monto'];
		$c++;
	}

	$tve=0;
	$res = mysql_query("SELECT * FROM vales_externos WHERE plaza={$datos['cveplaza']} AND estatus!='C' AND fecha='{$_POST['busquedafecha']}' ORDER BY cve");
	while($row = mysql_fetch_array($res)){
		$tipo='VE';
		$renglon['tipo'] = $tipo;
		$renglon['consecutivo'] = $c;
		$renglon['ticket'] = $row['folio'];
		$renglon['fecha_venta'] = $row['fecha'];
		if($row['formapago'] != 1 && $row['formapago'] != 3) $row['total']=0;
		$iva=round($row['total']*16/116,2);
		$iva=0;
		$subtotal=round($row['total']-$iva,2);
		$renglon['subtotal'] = $subtotal;
		$renglon['iva'] = $iva;
		$renglon['total'] = $row['total'];
		$renglon['folio_entrega'] = '';
		$resultado['informacion'][] = $renglon;
		$totales[$tipo]+=$row['total'];
		$c++;
	}
	
	$tdv=0;
	$res = mysql_query("SELECT * FROM devolucion_certificado WHERE plaza={$datos['cveplaza']} AND estatus!='C' AND fecha='{$_POST['busquedafecha']}' ORDER BY cve");
	while($row = mysql_fetch_array($res)){
		$tipo='DV';
		
		$renglon['tipo'] = $tipo;
		$renglon['consecutivo'] = $c;
		$renglon['ticket'] = $row['cve'];
		$renglon['placa'] = $row['placa'];
		$renglon['fecha_venta'] = $row['fecha'];
		$row['devolucion'] = $row['devolucion']*-1;
		$iva=round($row['devolucion']*16/116,2);
		$iva=0;
		$subtotal=round($row['devolucion']-$iva,2);
		$renglon['subtotal'] = $subtotal;
		$renglon['iva'] = $iva;
		$renglon['total'] = $row['devolucion'];
		$renglon['folio_entrega'] = '';
		$resultado['informacion'][] = $renglon;
		$totales[$tipo]+=abs($row['devolucion']);
		$c++;
		
	}

	$res = mysql_query("SELECT * FROM devolucion_ajuste WHERE plaza={$datos['cveplaza']} AND estatus!='C' AND fecha='{$_POST['busquedafecha']}' ORDER BY cve");
	while($row = mysql_fetch_array($res)){
		$tipo='DRV';
		$renglon['tipo'] = $tipo;
		$renglon['consecutivo'] = $c;
		$renglon['ticket'] = $row['cve'];
		$renglon['fecha_venta'] = $row['fecha'];
		$row['monto'] = $row['monto']*-1;
		$iva=round($row['monto']*16/116,2);
		$iva=0;
		$subtotal=round($row['monto']-$iva,2);
		$renglon['subtotal'] = $subtotal;
		$renglon['iva'] = $iva;
		$renglon['total'] = $row['monto'];
		$renglon['folio_entrega'] = '';
		$resultado['informacion'][] = $renglon;
		$totales[$tipo]+=abs($row['monto']);
		$c++;
	}
	
	$tre=0;
	$res = mysql_query("SELECT * FROM recuperacion_certificado WHERE plaza={$datos['cveplaza']} AND estatus!='C' AND fecha='{$_POST['busquedafecha']}'  ORDER BY cve");
	while($row = mysql_fetch_array($res)){
		$tipo='RE';
		
		$renglon['tipo'] = $tipo;
		$renglon['consecutivo'] = $c;
		$renglon['ticket'] = $row['cve'];
		$renglon['placa'] = $row['placa'];
		$renglon['fecha_venta'] = $row['fecha'];
		$iva=round($row['recuperacion']*16/116,2);
		$iva=0;
		$subtotal=round($row['recuperacion']-$iva,2);
		$renglon['subtotal'] = $subtotal;
		$renglon['iva'] = $iva;
		$renglon['total'] = $row['recuperacion'];
		$renglon['folio_entrega'] = '';
		$resultado['informacion'][] = $renglon;
		$totales[$tipo]+=abs($row['recuperacion']);
		$c++;
		
	}

	$tb=0;
	$res = mysql_query("SELECT * FROM bonos WHERE plaza={$datos['cveplaza']} AND estatus!='C' AND fecha='{$_POST['busquedafecha']}'  ORDER BY cve");
	while($row = mysql_fetch_array($res)){
		$tipo='B';

		$renglon['tipo'] = $tipo;
		$renglon['consecutivo'] = $c;
		$renglon['ticket'] = $row['cve'];
		$renglon['placa'] = $row['placa'];
		$renglon['fecha_venta'] = $row['fecha'];
		$iva=round($row['monto']*16/116,2);
		$iva=0;
		$subtotal=round($row['monto']-$iva,2);
		$renglon['subtotal'] = $subtotal;
		$renglon['iva'] = $iva;
		$renglon['total'] = $row['monto'];
		$renglon['folio_entrega'] = '';
		$resultado['informacion'][] = $renglon;
		$totales[$tipo]+=abs($row['monto']);
		$c++;
	}
	foreach($totales as $tipo => $monto){
		$resultado['totales'][] = array('clase' => $tipo, 'importe' => number_format($monto,2));
	}
	return $resultado;
}

require_once('validarloging.php');

if($_POST['cmd']==0){

?>

<div id="modalTiposCertificados" class="modal fade" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="staticBackdropLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="staticBackdropLabel">Tipos Certificados</h5>
		        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
		          <span aria-hidden="true">&times;</span>
		        </button>
			</div>
			<div class="modal-body" id="bodytiposcertificados">
				<div class="row">
					<div class="col-xl-12 col-lg-12 col-md-12">
						<table class="table">
						  <thead>
						    <tr>
						      <th scope="col" style="text-align: center;">N&uacute;mero</th>
						      <th scope="col" style="text-align: center;">Nombre</th>
						      <th scope="col" style="text-align: center;">Precio</th>
						    </tr>
						  </thead>
						  <tbody>
						  	<?php
						  	$res = mysql_query("SELECT a.numero, a.nombre, b.precio FROM engomados a inner join engomados_plazas b on a.cve = b.engomado WHERE b.plaza = '{$_POST['cveplaza']}' AND (b.entrega=1 OR b.venta=1) GROUP BY a.numero ORDER BY a.numero");
						  	while($row = mysql_fetch_assoc($res)) {
						  	?>
						  		<tr>
						  			<td style="text-align: center;"><?php echo $row['numero']; ?></td>
						  			<td style="text-align: left;"><?php echo utf8_encode($row['nombre']); ?></td>
						  			<td style="text-align: right;"><?php echo $row['precio']; ?></td>
						  	<?php
						  	}
						  	?>
						  </tbody>
						</table>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
		     </div>
		</div>
	</div>
</div>

<div id="modalTipos" class="modal fade" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="staticBackdropLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="staticBackdropLabel">Tipos</h5>
		        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
		          <span aria-hidden="true">&times;</span>
		        </button>
			</div>
			<div class="modal-body" id="bodytipos">
				<div class="row">
					<div class="col-xl-12 col-lg-12 col-md-12">
						<table class="table">
						  <tbody>
						  	<tr><td align="center">C</td><td>Venta Credito</td></tr>
							<tr><td align="center">TC</td><td>Tarjeta Credito</td></tr>
							<tr><td align="center">TD</td><td>Tarjeta Debito</td></tr>
							<tr><td align="center">R</td><td>Reposici&oacute;n</td></tr>
							<tr><td align="center">NV</td><td>No Verificado</td></tr>
							<tr><td align="center">PA</td><td>Pago Anticipado</td></tr>
							<tr><td align="center">RV</td><td>Reverificacion</td></tr>
							<tr><td align="center">CT</td><td>Cortesia</td></tr>
							<tr><td align="center">DV</td><td>Devolucion</td></tr>
							<tr><td align="center">RE</td><td>Recuperacion</td></tr>
							<tr><td align="center">DRV</td><td>Descuentos y Rebajas de Venta</td></tr>
							<tr><td align="center">B</td><td>Bonos</td></tr>
							<tr><td align="center">PI</td><td>Pago Intento</td></tr>
						  </tbody>
						</table>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
		     </div>
		</div>
	</div>
</div>



<div id="modalDepositos" class="modal fade" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="staticBackdropLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="staticBackdropLabel">Depositos</h5>
		        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
		          <span aria-hidden="true">&times;</span>
		        </button>
			</div>
			<div class="modal-body" id="bodydepositos">
				<div class="row">
					<div class="col-xl-12 col-lg-12 col-md-12">
						<table class="table">
						  <thead>
						    <tr>
						      <th scope="col" style="text-align: center;">Tipo</th>
						      <th scope="col" style="text-align: center;">Importe</th>
						    </tr>
						  </thead>
						  <tbody>
						  	<tr><td align="center">P</td><td class="class_P ctotales" style="text-align:right;"></td></tr>
							<tr><td align="center">C</td><td class="class_C ctotales" style="text-align:right;"></td></tr>
							<tr><td align="center">TC</td><td class="class_TC ctotales" style="text-align:right;"></td></tr>
							<tr><td align="center">TD</td><td class="class_TD ctotales" style="text-align:right;"></td></tr>
							<tr><td align="center">R</td><td class="class_R ctotales" style="text-align:right;"></td></tr>
							<tr><td align="center">NV</td><td class="class_NV ctotales" style="text-align:right;"></td></tr>
							<tr><td align="center">PA</td><td class="class_PA ctotales" style="text-align:right;"></td></tr>
							<tr><td align="center">DV</td><td class="class_DV ctotales" style="text-align:right;"></td></tr>
							<tr><td align="center">DRV</td><td class="class_DRV ctotales" style="text-align:right;"></td></tr>
							<tr><td align="center">RE</td><td class="class_RE ctotales" style="text-align:right;"></td></tr>
							<tr><td align="center">B</td><td class="class_B ctotales" style="text-align:right;"></td></tr>
							<tr><td align="center">Total</td><td class="class_Total ctotales" style="text-align:right;"></td></tr>

						  </tbody>
						</table>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
		     </div>
		</div>
	</div>
</div>

<div class="row justify-content-center">
	<div class="col-xl-3 col-lg-3 col-md-3">
		<div class="form-group row">
			<label class="col-sm-4 col-form-label">Fecha</label>
			<div class="col-sm-8">
            	<input type="date" class="form-control" id="busquedafecha" name="busquedafecha" placeholder="Fecha Inicio" value="<?php echo date('Y-m-d');?>">
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
    <div class="col-xl-3 col-lg-3 col-md-3">
    	<h3><span style="cursor: pointer;" onClick="mostrar_tipos_certificados()">Tipo de Certificado</span></h3>
		
    </div>
    <div class="col-xl-2 col-lg-2 col-md-2">
    	<h3><span style="cursor: pointer;" onClick="mostrar_tipos()">Tipos</span></h3>
		
    </div>
    <div class="col-xl-2 col-lg-2 col-md-2">
    	<h3><span style="cursor: pointer;" onClick="mostrar_depositos()">Depositos</span></h3>
		
    </div>
</div>
<div class="row" id="resultadocorte">
	
</div>
<script>
	function buscar(){
		$.ajax({
		  url: 'poliza_certificado.php',
		  type: "POST",
		  data: {
			menu: $('#cvemenu').val(),
			cmd: 10,
			cveusuario: $('#cveusuario').val(),
			busquedafecha: $('#busquedafecha').val(),
    		cvemenu: $('#cvemenu').val(),
    		cveplaza: $('#cveplaza').val(),
    		cveusuario: $('#cveusuario').val()
		  },
			success: function(data) {
				$('#resultadocorte').html(data);
				var totales = JSON.parse($('#totales').val());
				$('.ctotales').each(function(){
					$(this).html('');
				});
				$.each(totales, function(i, item) {
				    $('.class_'+item.clase).html(item.valor);
				});
			}
		});
	}

	function mostrar_tipos_certificados(){
		$('#modalTiposCertificados').modal('show');
	}

	function mostrar_tipos(){
		$('#modalTipos').modal('show');
	}

	function mostrar_depositos(){
		$('#modalDepositos').modal('show');
	}

	$("#modalTiposCertificados").modal({
		backdrop: false,
		keyboard: false,
		show: false
	});

	$("#modalTipos").modal({
		backdrop: false,
		keyboard: false,
		show: false
	});

	$("#modalDepositos").modal({
		backdrop: false,
		keyboard: false,
		show: false
	});
</script>
<?php
}


if($_POST['cmd']==10){
	$resultado = obtener_informacion($_POST);
?>
	<table class="table">
	  <thead>
	    <tr>
	      <th scope="col" style="text-align: center;">Tipo</th>
	      <th scope="col" style="text-align: center;">&nbsp;</th>
	      <th scope="col" style="text-align: center;">Certificado</th>
	      <th scope="col" style="text-align: center;">Ticket</th>
	      <th scope="col" style="text-align: center;">Placa</th>
		  <th scope="col" style="text-align: center;">Fecha Venta</th>
<?php
		$totales = array(0, 0, 0);
		$array_engomados = array();
		$res = mysql_query("SELECT a.numero, a.nombre, b.precio FROM engomados a inner join engomados_plazas b on a.cve = b.engomado WHERE b.plaza = '{$_POST['cveplaza']}' AND (b.entrega=1 OR b.venta=1) GROUP BY a.numero ORDER BY a.numero");
		  while($row = mysql_fetch_assoc($res)) {
		  	$array_engomados[] = $row['numero'];
		  	$totales[] = 0;
?>
		  <th scope="col" style="text-align: center;"><?php echo $row['nombre'];?></th> 
<?php
		}
?>

	      <th scope="col" style="text-align: center;">Monto</th> 
	      <th scope="col" style="text-align: center;">IVA</th> 
	      <th scope="col" style="text-align: center;">Total</th> 
	      <th scope="col" style="text-align: center;">Folio de Entrega</th> 

	    </tr>
	  </thead>
	  <tbody>
<?php
	foreach($resultado['informacion'] as $renglon) {
?>
		<tr>
		  <?php
		  	if($renglon['tipo'] == 'OMISO'){
		  ?>
		  	<td align="center" colspan="2"><?php echo $renglon['tipo'];?></td>
		  <?php
		  	}
		  	else{
		  ?>
		  	<td align="center"><?php echo $renglon['tipo'];?></td>
	      	<td align="center"><?php echo $renglon['consecutivo'];?></td>
		  <?php
		  	}
		  ?>
	      <td align="center" <?php if($renglon['color']!=''){ echo 'style="color: '.$renglon['color'].';"'; }?>><?php echo $renglon['certificado'];?></td>
	      <td align="center"><?php echo $renglon['ticket'];?></td>
	      <td align="center"><?php echo $renglon['placa'];?></td>
	      <td align="center"><?php echo $renglon['fecha_venta'];?></td>
	      <?php 
	      $c = 0;
	      foreach($array_engomados as $numero) { 
	      	if ($renglon['engomado_'.$numero] != '' && $renglon['engomado_'.$numero] != 'CA'){
	      		$totales[$c]++;
	      		$c++;
	      	}
	      ?>
	      	<td align="center"><?php echo $renglon['engomado_'.$numero];?></td>
	      <?php 
	  	  } 
	  	  ?>
	      <td align="right"><?php echo number_format($renglon['subtotal'],2);?></td>
	      <td align="right"><?php echo number_format($renglon['iva'],2);?></td>
	      <td align="right"><?php echo number_format($renglon['total'],2);?></td>
	      <td align="center"><?php echo $renglon['folio_entrega'];?></td>
	      <?php
	      $totales[$c]+=$renglon['subtotal'];$c++;
	      $totales[$c]+=$renglon['iva'];$c++;
	      $totales[$c]+=$renglon['total'];$c++;
	      ?>
<?php		
	}
?>
		<tr>
			<th colspan="6" style="text-align: left;">Totales</th>
			<?php
				$importes = count($totales)-3;
				foreach($totales as $k => $t){
					$decimales = ($k>$importes) ? 2:0;
			?>
				<th style="text-align: right;"><?php echo number_format($t,$decimales);?></th>
			<?php	
				}
			?>
			<th style="text-align: right;">&nbsp;</th>
		</tr>
	  </tbody>
	</table>
	<textarea id="totales" style="display:none;"><?php echo json_encode($resultado['totales']);?></textarea>
<?php
}
?>