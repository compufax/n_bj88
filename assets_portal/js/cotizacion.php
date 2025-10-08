<?php
require_once('subs/cnx_db.php');
global $base,$PHP_SELF;

if($_GET['tipo']>0){
	$_POST['tipo'] = $_GET['tipo'];
}
if($_GET['usuario']>0){
	$_POST['usuario'] = $_GET['usuario'];
}

$res = mysql_db_query($base,"SELECT * FROM cat_kyocera ORDER BY descripcion,modelo");
while($row = mysql_fetch_array($res)){
	$array_productos[$row['cve']] = $row['descripcion'];
	$array_modelos[$row['cve']] = $row['modelo'];
	$array_precios[$row['cve']] = $row['precio_final'];
}

function imagen_funcion($valor){
	if($valor == 0)
		return '&nbsp;';
	elseif($valor == 1)
		return '<img src="images/bcircle.gif">';
	else
		return '<img src="images/wcircle.gif">';
}

function rowc() {

		echo '<tr bgcolor="#ffffff" onmouseover="sc(this, 1, 0);" onmouseout="sc(this, 0, 0);" onmousedown="sc(this, 2, 0);">';

	}



	// Renglones que cambian el color de fondo

	function rowb() {

		global $rc;

		if ($rc) {

			echo '<tr bgcolor="#d5d5d5" onmouseover="sc(this, 1, 1);" onmouseout="sc(this, 0, 1);" onmousedown="sc(this, 2, 1);">';

			$rc=FALSE;

		}

		else {

			echo '<tr bgcolor="#e5e5e5" onmouseover="sc(this, 1, 2);" onmouseout="sc(this, 0, 2);" onmousedown="sc(this, 2, 2);">';

			$rc=TRUE;

		}

	}

if($_POST['cmd']=='imprimir'){
	include('fpdf153/fpdf.php');
	include("numlet.php");	
	$res = mysql_db_query($base,"SELECT * FROM cotizacion WHERE cve='".$_POST['reg']."'");
	$row = mysql_fetch_array($res);
	$suma=0;
	class FPDF2 extends PDF_MC_Table {
		function Header(){
			global $array_plaza,$row,$totalRegistros;
			//$this->Image('images/membrete.JPG',107,3,120,10);
			$this->SetFont('Arial','B',15);
			//$this->SetY(17);
			$this->MultiCell(190,5,'COTIZACION',0,'C');
			$this->SetFont('Arial','B',12);
			$this->MultiCell(190,5,'DOCUMENT PLUS S.A. DE C.V.
RFC  DCPL050114QRS
DIRECCION PATRIOTISMO 365
SAN PEDRO DE LOS PINOS MEXICO DF 
CP  03800',0,'C');
			$this->Ln();
			$this->Cell(95,5,"Folio: ".$row['cve']);
			$this->Cell(95,5,"Fecha: ".$row['fecha'],0,0,'R');
			$this->Ln();
			$this->Cell(190,5,"Nombre: ".$row['nombre']);
			$this->Ln();
			$this->MultiCell(190,5,"Observaciones: ".$row['obs']);
			$this->Ln();
			$this->SetFont('Arial','B',12);
			$this->SetFont('Arial','B',9);
			$this->Cell(100,4,'Producto',0,0,'C',0);
			$this->Cell(50,4,'Modelo',0,0,'C',0);
			$this->Cell(35,4,'Importe',0,0,'C',0);
			$this->Ln();		
		}
		
		//Pie de página
		function Footer(){
			//Posición: a 1,5 cm del final
			$this->SetY(-15);
			//Arial bold 12
			$this->SetFont('Arial','B',11);
			//Número de página
			$this->Cell(0,10,'Página '.$this->PageNo().' de {nb}',0,0,'C');
		}
	}
	$pdf=new FPDF2('P','mm','LETTER');
	$pdf->AliasNbPages();
	$pdf->AddPage('P');
	$pdf->SetFont('Arial','',9);
	$pdf->SetWidths(array(100,50,35));
	$pdf->SetAligns(array('L','L','R'));
	$res1=mysql_db_query($base,"SELECT * FROM cotizacion_mov WHERE cvecoti='".$_POST['reg']."'") or die(mysql_error());
	while($row1=mysql_fetch_array($res1)) {
		$renglon=array();
		$renglon[]=$array_productos[$row1['cveproducto']];
		$renglon[]=$array_modelos[$row1['cveproducto']];
		$renglon[]=number_format($row1['cant']*$row1['precio'],2);
		$suma+=round($row1['cant']*$row1['precio'],2);
		$pdf->Row($renglon);
	}
	$pdf->SetFont('Arial','B',9);
	$pdf->Cell(150,8,'Total: ',0,0,'R');
	$pdf->Cell(35,8,number_format($suma,2),0,0,'R');
	$pdf->Output();
	exit();
}

if($_POST['ajax']=="traer_archivos"){
	$res1=mysql_db_query($base,"SELECT * FROM cat_kyocera_archivos WHERE cveproducto='".$_POST['cveproducto']."'");
	if(mysql_num_rows($res1)>0){
		if(mysql_num_rows($res1)==1){
			echo '1|';
			$row1=mysql_fetch_array($res1);
			$dat=explode(".",$row1['archivo']);
			$extension=end($dat);
			echo 'pdfskyocera/archivo'.$row1['cve'].'.'.$extension;
		}
		else{
			$html='2|<table width="100%">';
			while($row1 = mysql_fetch_array($res1)){
				$dat=explode(".",$row1['archivo']);
				$extension=end($dat);
				$html.='<tr><td><a href="#" onClick=atcr("pdfskyocera/archivo'.$row1['cve'].'.'.$extension.'","_blank","","");>'.$row1['archivo'].'</a>';
			}
			$html.='</table>';
			echo $html;
		}
	}
	else{
		echo '0|';
	}
	exit();
}

$id=0;

if($_POST['cmd']=="cotizar"){
	mysql_db_query($base,"INSERT cotizacion SET plaza=0,fecha='".date("Y-m-d")."',hora='".date("H:i:s")."',nombre='".$_POST['nombre']."',correo='".$_POST['correo']."',estatus='A',obs='".$_POST['obs']."'");
	$id = mysql_insert_id();
	foreach($_POST['prod'] as $k=>$v){
		if($v!="")
			mysql_db_query($base,"INSERT cotizacion_mov SET plaza=0,cvecoti='$id',cveproducto='$v',cant=1,precio='".$_POST['precio'][$k]."'");
	}	
	require_once("phpmailer/class.phpmailer.php");
	$mail = new PHPMailer();
	$mail->Host = "localhost";
	$mail->From = "foldio@foldio.mx";
	$mail->FromName = "Foldio";
	$mail->Subject = "Cotizacion";
	$mail->AddAddress(trim($_POST['correo']));
	$mail->IsHTML(true);
	$html='<table align="center"><img src="images/folidio.JPG" /></table><br>
	<h2>Cotizacion</h2><br>
	<table align="left">
	<tr><th>Folio</th><td>'.$id.'</td></tr>
	<tr><th>Nombre</th><td>'.$_POST['nombre'].'</td></tr>
	<tr><td colspan="2">
	<table border="1" align="center"><tr><th>Produto</th><th>Modelo</th><th>Precio</th></tr>';
	$suma=0;
	foreach($_POST['prod'] as $k=>$v){
		if($v!=""){
			$html.= '<tr>';
			$html.= '<td>'.$array_productos[$v].'</td>';
			$html.= '<td>'.$array_modelos[$v].'</td>';
			$html.= '<td align="right">'.number_format($_POST['precio'][$k],2).'</td>';
			$html.= '</tr>';
			$suma+=$_POST['precio'][$k];
			$res1=mysql_db_query($base,"SELECT * FROM cat_kyocera_archivos WHERE cveproducto='".$v."'");
			while($row1=mysql_fetch_array($res1)){
				$dat=explode(".",$row1['archivo']);
				$extension=end($dat);
				$mail->AddAttachment("pdfskyocera/archivo".$row1['cve'].".".$extension, $row1['archivo']);
			}
		}
	}
	$html.= '<tr id="idtotal"><th align="right" colspan="2">Total&nbsp;&nbsp;<td align="right">'.number_format($suma,2).'</td></tr>';
	$html.= '</table></td></tr>';		
	$html.= '<tr><th>Observaciones</th><td>'.$_POST['obs'].'</td></tr>
	</table>';
	$mail->Body = $html;
	$mail->Send();
}

echo '
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">



	<html xmlns="http://www.w3.org/1999/xhtml">

	<head>

	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />

	<title>:: DOCUMENT PLUS S.A. DE C.V ::</title>

	<link rel="stylesheet" type="text/css" href="css/style2.css" />

	<link rel="stylesheet" type="text/css" href="calendar/dhtmlgoodies_calendar.css" />
	<style>
		.colorrojo { color: #FF0000 } 
		.panel {
            background:#DFE6EF;
            top:0px;
            left:0px;
            display:none;
            position:absolute;
            filter:alpha(opacity=40);
            opacity:.4;
        }
	</style>
	<script src="js/rutinas.js"></script>
	<link rel="stylesheet" type="text/css" href="css/ui.css" />
	<script src="js/jquery-1.8.0.min.js" type="text/javascript"></script>
	<script src="js/jquery-ui-1.8.23.custom.min.js" type="text/javascript"></script>
	<script src="http://cdn.kyostatics.net/js/jquery/jquery.easing.1.3.js" type="text/javascript">
	<script src="http://cdn.kyostatics.net/js/jquery/jquery.galleryview-1.1-min.js" type="text/javascript">
	<script src="http://cdn.kyostatics.net/js/jquery/jquery.timers-1.1.2.js" type="text/javascript">
	<script src="calendar/dhtmlgoodies_calendar.js"></script>
	
	<script>
	
	function pulsar(e) {
		tecla=(document.all) ? e.keyCode : e.which;
		if(tecla==13) return false;
	}
	
	function ver_comparar(){
		var ids="";
		$(".checks").each(function(){
			if($(this).is(":checked"))
				ids += ","+$(this).val();
		});
		if(ids == "")
			alert("Necesita seleccionar al menos un producto");
		else
			popUpWindow("comparar.php?claves="+ids, 50, 50, 600, 500);
	}
	
	</script>
	
	</head>



	<form name="forma" id="forma" method="POST" enctype="multipart/form-data">
	<body align="center" onkeypress="return pulsar(event)">
	<input type="hidden" name="cmd" id="cmd" value="">
	<input type="hidden" name="reg" id="reg" value="">';
	if($_POST['tipo']>0) echo '<input type="hidden" name="tipo" id="tipo" value="'.$_POST['tipo'].'">';
	if($_POST['usuario']>0) echo '<input type="hidden" name="usuario" id="usuario" value="'.$_POST['usuario'].'">';
	echo '
	<div id="panel" class="panel"></div>
	<div id="dialog" style="display:none"></div>
	<div align="center">
	<table align="center"><img src="images/folidio.JPG" /></table><br>';
	if($_POST['cmd']!="ver_producto"){
		echo '
		<h2>Cotizacion</h2><br>
		<table align="center" width="80%">
		<tr bgcolor="#E9F2F8"><th rowspan="2">Producto</th><th colspan="2">Velocidad impresión (BN/Color)</th><th rowspan="2">(BN/Color)</th><th colspan="4">Funciones</th>
		<th rowspan="2">Precio</th>';
		if($_POST['tipo']>0)
			echo '<th rowspan="2">Precio Final</th>';
		echo '
		<th rowspan="2"><span style="color: blue;cursor: pointer;" onClick="ver_comparar()">Comparar</span></th></tr>
		<tr bgcolor="#E9F2F8"><th>A4</th><th>A3</th><th>Impresora</th><th>Copiadora</th><th>Escaner</th><th>Fax</th></tr>';
		$res = mysql_db_query($base,"SELECT * FROM cat_kyocera ORDER BY modelo");
		while($row=mysql_fetch_array($res)){
			rowb();
			echo '<td><span style="color: blue;cursor: pointer;" onClick="atcr(\'cotizacion.php\',\'\',\'ver_producto\',\''.$row['cve'].'\');">'.$row['modelo'].'</span></td>';
			echo '<td align="center">'.$row['velocidad_impresion_a4'].'</td>';
			echo '<td align="center">'.$row['velocidad_impresion_a3'].'</td>';
			echo '<td align="center">';
			if($row['tipo_imp'] == 0)
				echo '<img src="images/icon_bw.jpg">';
			else
				echo '<img src="images/icon_color.jpg">';
			echo '</td>';
			echo '<td align="center">'.imagen_funcion($row['es_impresora']).'</td>';
			echo '<td align="center">'.imagen_funcion($row['es_copiadora']).'</td>';
			echo '<td align="center">'.imagen_funcion($row['es_escaner']).'</td>';
			echo '<td align="center">'.imagen_funcion($row['es_fax']).'</td>';
			if($_POST['tipo']==1){
				echo '<td align="right">'.number_format($row['costo'],2).'</td>';
				echo '<td align="center"><input type="text" class="textField" value="'.$row['costo'].'" size="15" name="precio['.$row['cve'].']"></td>';
			}
			elseif($_POST['tipo']==2){
				echo '<td align="right">'.number_format($row['precio_distribuidor'],2).'</td>';
				echo '<td align="center"><input type="text" class="textField" value="'.$row['precio_distribuidor'].'" size="15" name="precio['.$row['cve'].']"></td>';
			}
			else{
				echo '<td align="right">'.number_format($row['precio_final'],2).'</td>';
			}
			echo '<td align="center"><input type="checkbox" class="checks" name="imp[]" value="'.$row['cve'].'"></td>';
			echo '</tr>';
		}	
		echo '<tr><td bgcolor="#E9F2F8" colspan="9"><img src="images/wcircle.gif"><small>= opcional, funcionalidad no estandar</small></td></table>';
	}
	else{
		echo '
		<h2>'.$array_modelos[$_POST['reg']].'</h2><br><span style="color: blue;cursor: pointer;" onClick="atcr(\'cotizacion.php\',\'\',\'\',\'\');">Volver</span><br><br>
		<div id="tabs">
			<ul>
				<li><a hrer="#tabs-1">Información del producto</a></li>
				<li><a hrer="#tabs-2">Vistas del producto</a></li>
				<li><a hrer="#tabs-4">Especificación técnica</a></li>
			</ul>
			<div id="tabs-1">
			<table width="100%"><tr><td colspan="2"><b>';
			$res1=mysql_db_query($base,"SELECT * FROM cat_kyocera_especificaciones WHERE cveproducto='".$_POST['reg']."' AND tipo=0");
			$row1=mysql_fetch_array($res1);
			echo $row1['texto'];
			echo '</b><td></tr>';
			echo '<tr><td><ul>';
			while($row1 = mysql_fetch_array($res1)){
				echo '<li>'.$row1['texto'].'</li>';
			}
			echo '</ul></td><td>';
			$res = mysql_db_query($base,"SELECT * FROM cat_kyocera_archivos WHERE cveproducto='".$_POST['reg']."' AND tipo=1 LIMIT 1");
			if($row = mysql_fetch_array($res)){
				$dat=explode(".",$row['archivo']);
				$extension=end($dat);
				echo '<img src="pdfskyocera/imagen'.$row['cve'].'.'.$extension.'" width="50px" heigth="50px">';
			}
			else{
				echo '&nbsp;';
			}	
		echo '</div>
			<div id="tabs-2">
			<ul id="myGallery">';
			$res = mysql_db_query($base,"SELECT * FROM cat_kyocera_archivos WHERE cveproducto='".$_POST['reg']."' AND tipo=1");
			if($row = mysql_fetch_array($res)){
				$dat=explode(".",$row['archivo']);
				$extension=end($dat);
				echo '<li><img src="pdfskyocera/imagen'.$row['cve'].'.'.$extension.'" width="50px" heigth="50px"></li>';
			}
			else{
				echo '<li>&nbsp;</li>';
			}	
		echo '</ul></div>
			<div id="tabs-4">';
			$res = mysql_db_query($base,"SELECT * FROM cat_kyocera WHERE cve='".$_POST['reg']."'");
			$row = mysql_fetch_array($res);
			echo '<table>
			<tr><td class="tableEnc">General</td></tr>
			</table><br><table width="100%" border="1">';
			$rc=TRUE;
			rowb(); echo '<th align="left" valign="top" width="200">Tipo</th><td>'.$row['tipo'].'</td></tr>';
			rowb(); echo '<th align="left" valign="top" width="200">Tecnologia</th><td>'.$row['tecnologia'].'</td></tr>';
			rowb(); echo '<th align="left" valign="top" width="200">Velocidad (ppm)</th><td>'.$row['ppm'].'</td></tr>';
			rowb(); echo '<th align="left" valign="top" width="200">Resolucion (ppp)</th><td>'.$row['resolucion_ppp'].'</td></tr>';
			rowb(); echo '<th align="left" valign="top" width="200">Tiempo de Calentamiento (seg.)</th><td>'.$row['tiempo_calentamiento'].'</td></tr>';
			rowb(); echo '<th align="left" valign="top" width="200">Tiempo para la primer pagina/fotocopia (seg.)</th><td>'.$row['tiempo_primerapag'].'</td></tr>';
			rowb(); echo '<th align="left" valign="top" width="200">Dimensiones An x P x Al (mm)</th><td>'.$row['dimensiones_an_p_al'].'</td></tr>';
			rowb(); echo '<th align="left" valign="top" width="200">Peso (kg)</th><td>'.$row['peso'].'</td></tr>';
			rowb(); echo '<th align="left" valign="top" width="200">Ruido</th><td>'.$row['ruido'].'</td></tr>';
			rowb(); echo '<th align="left" valign="top" width="200">Capacidad max. entrada (hojas)</th><td>'.$row['capacidad_max_entrada'].'</td></tr>';
			rowb(); echo '<th align="left" valign="top" width="200">Capacidad de salida (hojas)</th><td>'.$row['capacidad_salida'].'</td></tr>';
			rowb(); echo '<th align="left" valign="top" width="200">Interfaz</th><td>'.$row['interfaz'].'</td></tr>';
			rowb(); echo '<th align="left" valign="top" width="200">Idioma del Controlador</th><td>'.$row['idioma_controlador'].'</td></tr>';
			echo '</table>';
			if($row['es_impresora']==1){
				echo '<br><br><table>
				<tr><td class="tableEnc">Imprimir</td></tr>
				</table><br><table width="100%" border="1">';
				$rc=TRUE;
				rowb(); echo '<th align="left" valign="top" width="200">Procesador</th><td>'.$row['procesador'].'</td></tr>';
				rowb(); echo '<th align="left" valign="top" width="200">Memoria</th><td>'.$row['memoria'].'</td></tr>';
				rowb(); echo '<th align="left" valign="top" width="200">Emulaciones</th><td>'.$row['emulaciones'].'</td></tr>';
				rowb(); echo '<th align="left" valign="top" width="200">Sistemas Operativos</th><td>'.$row['sistemas_operativos'].'</td></tr>';
				echo '</table>';
			}
			if($row['es_copiadora']==1){
				echo '<br><br><table>
				<tr><td class="tableEnc">Copiar</td></tr>
				</table><br><table width="100%" border="1">';
				$rc=TRUE;
				rowb(); echo '<th align="left" valign="top" width="200">Tamaño máx. de original</th><td>'.$row['copia_tam_max'].'</td></tr>';
				rowb(); echo '<th align="left" valign="top" width="200">Funciones de copia digital</th><td>'.$row['copia_func_copia_dig'].'</td></tr>';
				rowb(); echo '<th align="left" valign="top" width="200">Modo de exposición</th><td>'.$row['copia_modo_exp'].'</td></tr>';
				rowb(); echo '<th align="left" valign="top" width="200">Porcentajes de ampliación/reducción</th><td>'.$row['copia_porc_amp_redu'].'</td></tr>';
				rowb(); echo '<th align="left" valign="top" width="200">Zoom</th><td>'.$row['copia_zoom'].'</td></tr>';
				rowb(); echo '<th align="left" valign="top" width="200">Copia continua</th><td>'.$row['copia_copia_continua'].'</td></tr>';
				rowb(); echo '<th align="left" valign="top" width="200">Capacidad de memoria</th><td>'.$row['copia_capacidad_memoria'].'</td></tr>';
				rowb(); echo '<th align="left" valign="top" width="200">Ajustes de la imagen</th><td>'.$row['copia_ajuste_img'].'</td></tr>';
				echo '</table>';
			}
			if($row['es_escaner']==1){
				echo '<br><br><table>
				<tr><td class="tableEnc">Escanear</td></tr>
				</table><br><table width="100%" border="1">';
				$rc=TRUE;
				rowb(); echo '<th align="left" valign="top" width="200">Sistemas Operativos</th><td>'.$row['scan_sistema_op'].'</td></tr>';
				rowb(); echo '<th align="left" valign="top" width="200">Software incluido</th><td>'.$row['scan_software'].'</td></tr>';
				rowb(); echo '<th align="left" valign="top" width="200">Tipo de archivo</th><td>'.$row['scan_tipo_arch'].'</td></tr>';
				rowb(); echo '<th align="left" valign="top" width="200">Reconocimiento de originales</th><td>'.$row['scan_reconocimiento_ori'].'</td></tr>';
				rowb(); echo '<th align="left" valign="top" width="200">Tamaño máx. de escaneo</th><td>'.$row['scan_tamano_max'].'</td></tr>';
				rowb(); echo '<th align="left" valign="top" width="200">Funcionalidad</th><td>'.$row['scan_funcionalidad'].'</td></tr>';
				rowb(); echo '<th align="left" valign="top" width="200">Resolución de escaneo</th><td>'.$row['scan_resolucion'].'</td></tr>';
				rowb(); echo '<th align="left" valign="top" width="200">Velocidad de escaneo</th><td>'.$row['scan_velocidad'].'</td></tr>';
				echo '</table>';
			}
			if($row['es_fax']==1){
				echo '<br><br><table>
				<tr><td class="tableEnc">Fax</td></tr>
				</table><br><table width="100%" border="1">';
				$rc=TRUE;
				rowb(); echo '<th align="left" valign="top" width="200">Compatibilidad</th><td>'.$row['fax_compatibilidad'].'</td></tr>';
				rowb(); echo '<th align="left" valign="top" width="200">Velocidad de modem</th><td>'.$row['fax_velocidad_modem'].'</td></tr>';
				rowb(); echo '<th align="left" valign="top" width="200">Velocidad de escaneo</th><td>'.$row['fax_velocidad_escaneo'].'</td></tr>';
				rowb(); echo '<th align="left" valign="top" width="200">Velocidad de transmision</th><td>'.$row['fax_velocidad_transmision'].'</td></tr>';
				rowb(); echo '<th align="left" valign="top" width="200">Densidad de escaneo</th><td>'.$row['fax_densidad_escaneo'].'</td></tr>';
				rowb(); echo '<th align="left" valign="top" width="200">Tamaño máx. de original</th><td>'.$row['fax_tam_original'].'</td></tr>';
				rowb(); echo '<th align="left" valign="top" width="200">Método de compresión</th><td>'.$row['fax_metodo_compresion'].'</td></tr>';
				rowb(); echo '<th align="left" valign="top" width="200">Funciones de fax</th><td>'.$row['fax_funciones'].'</td></tr>';
				echo '</table>';
			}
		echo '</div>
		</div>';
		
		echo '<script>
			$("#tabs").tabs();
			$(function(){
			$("#myGallery").galleryView({
				nav_theme: "dark",
				border: "1px solid #cccccc",
				overlay_height: 0,
				background_color: "#cccccc",
				overlay_text_color: "white",
				caption_text_color: "black",
				panel_width: 605,
				panel_height: 560,
				frame_width: 137,
				frame_height: 190 });
			});
			</script>';
	}
	echo '
	</body>
	</form>

	</html>';
	

?>