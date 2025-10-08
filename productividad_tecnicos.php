<?php
error_reporting(E_ERROR | E_PARSE);
require_once('cnx_db.php');
require_once('globales.php');

function obtener_informacion($datos){
	$resultado = array('total' => 0, 'datos' => array());
	$select .= "SELECT a.cve, a.nombre, COUNT(b.cve) as cantidad FROM tecnicos a 
		INNER JOIN certificados b ON a.plaza = b.plaza AND a.cve = b.tecnico  
	WHERE a.plaza = {$datos['cveplaza']} AND b.fecha BETWEEN '{$datos['busquedafechaini']}' AND '{$datos['busquedafechafin']}' GROUP BY a.cve, a.nombre ORDER BY a.nombre";
	$res = mysql_query($select);
	
	return $res;
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
        	<div class="col-sm-12" align="center">
	        	<button type="button" class="btn btn-primary" onClick="buscar();">
	            	Buscar
	        	</button>
	        	&nbsp;&nbsp;
	        	<button type="button" class="btn btn-primary" onClick="atcr('productividad_tecnicos.php','_blank',100,0);">
	            	Excel
	        	</button>
        	</div>
        </div>
    </div>
</div>
<div class="row" id="resultadocorte">
	
</div>
<div id="piechart" style="width: 900px; height: 500px;"></div>
<script>
	google.charts.load('current', {'packages':['corechart']});
    //google.charts.setOnLoadCallback(drawChart);
    var chart = new google.visualization.PieChart(document.getElementById('piechart'));
    function drawChart2() {

    	var informacion = [];
    	informacion.push(['Task', 'Productividad Tecnicos']);
    	var informacion_reporte = JSON.parse($('#informacionreporte').val());
    	$.each(informacion_reporte, function(i, item) {
		    informacion.push([item.nombre, parseFloat(item.cantidad)]);
		});

    	var data = google.visualization.arrayToDataTable(informacion);

        var options = {
          title: 'Productividad Tecnicos'
        };

        

        chart.draw(data, options);
    }

	function buscar(){
		$.ajax({
		  url: 'productividad_tecnicos.php',
		  type: "POST",
		  data: {
			cmd: 10,
			busquedafechaini: $('#busquedafechaini').val(),
			busquedafechafin: $('#busquedafechafin').val(),
    		cvemenu: $('#cvemenu').val(),
    		cveplaza: $('#cveplaza').val(),
    		cveusuario: $('#cveusuario').val()
		  },
			success: function(data) {
				$('#resultadocorte').html(data);
				
				drawChart2();
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
	      <th scope="col" style="text-align: center;">Tecnico</th>
	      <th scope="col" style="text-align: center;">Cantidad</th>
	    </tr>
	  </thead>
	  <tbody>
	<?php
		$totales = 0;
		$informacionreporte = array();
		$i = 0;
		while($row = mysql_fetch_assoc($res)){
			$row['nombre'] = utf8_encode($row['nombre']);
			$informacionreporte[] = $row;
	?>
	    <tr>
	      <td align="left"><?php echo $row['nombre'];?></td>
	      <td align="center"><?php echo number_format($row['cantidad'], 0);?></td>
	    </tr>
	<?php
		$i++;
		$totales+=$row['cantidad'];
	}
	?>
		<tr>
			<th style="text-align: left;"><?php echo $i;?> Registro(s)</th>
			<th style="text-align: center;"><?php echo number_format($totales,0);?></th>
		</tr>
	  </tbody>
	</table>
	<textarea id="informacionreporte" style="display: none;"><?php echo json_encode($informacionreporte);?></textarea>

<?php
}

if($_POST['cmd']==100){
	require_once('fpdf/fpdf.php');

	$res = obtener_informacion($_POST);
	$Plaza = mysql_fetch_assoc(mysql_query("SELECT numero FROM plazas WHERE cve='{$_POST['cveplaza']}'"));

	$pdf = new PDF_MC_Table('P','mm','LETTER');
	$pdf->AddPage();
	$pdf->SetFont("Arial","B",15);
	$pdf->MultiCell(190, 5, 'Productividad de Tecnicos del '.mostrar_fechas($_POST['busquedafechaini']).' al '.mostrar_fechas($_POST['busquedafechafin']).'
Plaza: '.$Plaza['numero'], 0, 'C');
	$pdf->Ln();
	$pdf->Ln();
	$pdf->SetFont('Arial','B',10);
	$pdf->Cell(120,4,"Tecnico",1,0,"C",0);
	$pdf->Cell(30,4,"Cantidad",1,0,"C",0);
	$pdf->Ln();
	$pdf->SetFont('Arial','',10);
	$totales = array();
	$i = 0;
	while($row = mysql_fetch_assoc($res)){
		$pdf->Cell(120,4,$row['nombre'],1,0,"L",0);
		$pdf->Cell(30,4,number_format($row['cantidad'], 0),1,0,"C",0);
		$pdf->Ln();
		$i++;
		$totales+=$row['cantidad'];
	}
	$pdf->SetFont('Arial','B',10);
	$pdf->Cell(120,4,"{$i} Registro(s)",1,0,"L",0);
	$pdf->Cell(30,4,number_format($totales,0),1,0,"C",0);
	$pdf->Output();
	exit; 

}

?>