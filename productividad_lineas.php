<?php
error_reporting(E_ERROR | E_PARSE);
require_once('cnx_db.php');
require_once('globales.php');

function obtener_informacion($datos){
	$resultado = array('total' => 0, 'datos' => array());
	$select .= "SELECT a.cve, a.nombre, COUNT(b.cve) as aforo FROM cat_lineas a 
		INNER JOIN certificados b ON a.plaza = b.plaza AND a.cve = b.linea  
	WHERE a.plaza = {$datos['cveplaza']} AND b.fecha BETWEEN '{$datos['busquedafechaini']}' AND '{$datos['busquedafechafin']}'";
	if ($datos['busquedatipocertificado']!="") {
		$select.=" AND b.engomado='{$datos['busquedatipocertificado']}' "; 
	}	
	$select.=" GROUP BY a.cve, a.nombre ORDER BY a.nombre";
	$res = mysql_query($select);
	while($row = mysql_fetch_assoc($res)){
		$resultado['total']+=$row['aforo'];
		$resultado['datos'][] = $row;
	}
	return $resultado;
}
require_once('validarloging.php');


if($_POST['cmd']==0){
?>

<div class="row justify-content-center">
	<div class="col-xl-12 col-lg-12 col-md-12">
		<div class="form-group row">
			<label class="col-sm-2 col-form-label">Fecha Inicio</label>
			<div class="col-sm-4">
            	<input type="date" class="form-control" id="busquedafechaini" name="busquedafechaini" placeholder="Fecha Inicio" value="<?php echo date('Y-m-d');?>">
        	</div>
			<label class="col-sm-2 col-form-label">Fecha Fin</label>
			<div class="col-sm-4">
            	<input type="date" class="form-control" id="busquedafechafin" name="busquedafechafin" placeholder="Fecha Fin" value="<?php echo date('Y-m-d');?>">
        	</div>
        </div>
        
        <div class="form-group row">
			<label class="col-sm-2 col-form-label">Tipo de Certificado</label>
			<div class="col-sm-4">
            	<select name="busquedatipocertificado" id="busquedatipocertificado" class="form-control"><option value="">Todos</option>
            	<?php
            	$res1 = mysql_query("SELECT a.cve, a.nombre FROM engomados a inner join engomados_plazas b on a.cve = b.engomado WHERE b.venta=1 AND b.plaza = '{$_POST['cveplaza']}' ORDER BY a.nombre");
				while($row1=mysql_fetch_array($res1)){
					echo '<option value="'.$row1['cve'].'">'.$row1['nombre'].'</option>';
				}
				?>
            	</select>
        	</div>
        	
        </div>
        
        <div class="form-group row">
        	<div class="col-sm-12" align="center">
	        	<button type="button" class="btn btn-primary" onClick="buscar();">
	            	Buscar
	        	</button>
	        	&nbsp;&nbsp;
	        	<button type="button" class="btn btn-primary" onClick="atcr('productividad_lineas.php','_blank',100,0);">
	            	Excel
	        	</button>
        	</div>
        </div>
    </div>
</div>
<div class="row" id="resultadocorte">
	
</div>
<script>
	function buscar(){
		$.ajax({
		  url: 'productividad_lineas.php',
		  type: "POST",
		  data: {
			cmd: 10,
			busquedafechaini: $('#busquedafechaini').val(),
			busquedafechafin: $('#busquedafechafin').val(),
    		busquedatipocertificado: $("#busquedatipocertificado").val(),
    		cvemenu: $('#cvemenu').val(),
    		cveplaza: $('#cveplaza').val(),
    		cveusuario: $('#cveusuario').val()
		  },
			success: function(data) {
				$('#resultadocorte').html(data);
			}
		});
	}
</script>
<?php
}

if($_POST['cmd']==10){
	$res = obtener_informacion($_POST);
?>
	<table class="table">
	  <thead>
	    <tr>
	      <th scope="col" style="text-align: center;">Linea</th>
	      <th scope="col" style="text-align: center;">Aforo</th>
	      <th scope="col" style="text-align: center;">%</th>
	    </tr>
	  </thead>
	  <tbody>
	<?php
		$totales = array();
		$i = 0;
		foreach($res['datos'] as $row){
	?>
	    <tr>
	      <td align="left"><?php echo $row['nombre'];?></td>
	      <td align="center"><?php echo number_format($row['aforo'], 0);?></td>
	      <td align="center"><?php echo number_format($row['aforo']*100/$res['total'], 1);?></td>
	    </tr>
	<?php
		$i++;
		$totales[0]+=$row['aforo'];
		$totales[1]+=$row['aforo']*100/$res['total'];
	}
	?>
		<tr>
			<th style="text-align: left;"><?php echo $i;?> Registro(s)</th>
			<th style="text-align: center;"><?php echo number_format($totales[0],0);?></th>
			<th style="text-align: center;"><?php echo number_format($totales[1],1);?></th>
		</tr>
	  </tbody>
	</table>
	

<?php
}

if($_POST['cmd']==100){
	require_once('fpdf/fpdf.php');

	$res = obtener_informacion($_POST);
	$Plaza = mysql_fetch_assoc(mysql_query("SELECT numero FROM plazas WHERE cve='{$_POST['cveplaza']}'"));

	$pdf = new PDF_MC_Table('P','mm','LETTER');
	$pdf->AddPage();
	$pdf->SetFont("Arial","B",15);
	$pdf->MultiCell(190, 5, 'Productividad de Lineas del '.mostrar_fechas($_POST['busquedafechaini']).' al '.mostrar_fechas($_POST['busquedafechafin']).'
Plaza: '.$Plaza['numero'], 0, 'C');
	$pdf->Ln();
	$pdf->Ln();
	$pdf->SetFont('Arial','B',10);
	$pdf->Cell(60,4,"Linea",1,0,"C",0);
	$pdf->Cell(30,4,"Aforo",1,0,"C",0);
	$pdf->Cell(20,4,"%",1,0,"C",0);
	$pdf->Ln();
	$pdf->SetFont('Arial','',10);
	$totales = array();
	$i = 0;
	foreach($res['datos'] as $row){
		$pdf->Cell(60,4,$row['nombre'],1,0,"L",0);
		$pdf->Cell(30,4,number_format($row['aforo'], 0),1,0,"C",0);
		$pdf->Cell(20,4,number_format($row['aforo']*100/$res['total'], 1),1,0,"C",0);
		$pdf->Ln();
		$i++;
		$totales[0]+=$row['aforo'];
		$totales[1]+=$row['aforo']*100/$res['total'];
	}
	$pdf->SetFont('Arial','B',10);
	$pdf->Cell(60,4,"{$i} Registro(s)",1,0,"L",0);
	$pdf->Cell(30,4,number_format($totales[0],0),1,0,"C",0);
	$pdf->Cell(20,4,number_format($totales[1],1),1,0,"C",0);
	$pdf->Output();
	exit; 

}

?>