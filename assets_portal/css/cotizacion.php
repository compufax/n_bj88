<?php
require_once('subs/cnx_db.php');
global $base,$PHP_SELF;

$res = mysql_db_query($base,"SELECT * FROM cat_kyocera ORDER BY descripcion,modelo");
while($row = mysql_fetch_array($res)){
	$array_productos[$row['cve']] = $row['descripcion'];
	$array_modelos[$row['cve']] = $row['modelo'];
	$array_precios[$row['cve']] = $row['precio_final'];
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

if($_POST['cmd']=="cotizar"){

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

	<script src="calendar/dhtmlgoodies_calendar.js"></script>
	
	<script>
	
	function pulsar(e) {
		tecla=(document.all) ? e.keyCode : e.which;
		if(tecla==13) return false;
	}
	
	</script>
	
	</head>



	<form name="forma" id="forma" method="POST" enctype="multipart/form-data">
	<body onkeypress="return pulsar(event)">
	<div id="panel" class="panel"></div>
	<div id="dialog" style="display:none"></div>
	<table align="center"><img src="images/folidio.JPG" /></table><br>
	<h2>Cotizacion</h2>
	<table align="center">
	<tr><th>Nombre</th><td><input type="text" name="nombre" id="nombre" size="50"></td></tr>
	<tr><th>Email</th><td><input type="text" name="correo" id="correo" size="30"></td></tr>
	</table><br>
	<table id="tablaproductos"><tr><th>&nbsp;</th><th>Produto</th><th>Precio</th><th>&nbsp;</th></tr>';
	$i=0;
	echo '<tr><td id="det'.$i.'">&nbsp;</td>';
	echo '<td><select id="prod'.$i.'" name="prod['.$i.']" onChange="actualizaprod('.$i.')"><option value="">Seleccione</option>';
	foreach($array_productos as $k=>$v){
		echo '<option value="'.$k.'">'.$v.'</option>';
	}
	echo '</select></td>';
	echo '<td align="center"><input type="text" class="readOnly suma" size="10" name="precio['.$i.']" id="precio'.$i.'" value="" readOnly></td>';
	echo '<td><input type="button" onClick="$(this).parent().parent().remove();" value="Quitar"></td>';
	echo '</tr>';
	$i++;
	echo '<tr id="idtotal"><th align="right" colspan="2">Total&nbsp;&nbsp;<td align="center"><input type="text" class="readOnly" size="10" name="monto" id="monto" value="" readOnly></td></tr>';
	echo '</table>';		
	echo '<input type="button" value="Agregar" onClick="agregarproducto()" class="textField">';
	echo '<input type="hidden" name="cantprod" value="'.$i.'">';
	echo '<input type="hidden" name="cmd" value="">';
	echo '<input type="hidden" name="reg" value="">';
	echo '
		<script type="text/javascript">
			var precios = new Array();
			';
		foreach($array_precios as $k=>$v){
			echo 'precios['.$k.']='.$v.';';
		}
		echo '
			
			function validar(){
				if(document.forma.nombre.value==""){
					$(\'#panel\').hide();
					alert("Necesita ingresar el nombre");
				}
				else if(document.forma.correo.value==""){
					$(\'#panel\').hide();
					alert("Necesita ingresar el email");
				}
				else if((document.forma.monto.value/1)<=0){
					$(\'#panel\').hide();
					alert("El total no puede ser cero");
				}
				else{
					document.forma.cmd.value="cotizar";
					document.forma.submit();
				}
			}
			
			function actualizaprod(ren){
				if($("#prod"+ren).val()==""){
					document.getElementById("importe"+ren).value="";
					$("#det"+ren).html(\'&nbsp;\');
				}
				else{
					$("#precio"+ren).val(precios[$("#prod"+ren).val()]);
					$("#det"+ren).html(\'<a href="#" onClick="mostrarPdfs(\'+$("#prod"+ren).val()+\')">Detalle</a>\');
				}
				sumarproductos();
			}
			
			function agregarproducto(){
				tot=$("#monto").val();
				$("#idtotal").remove();
				num=document.forma.cantprod.value;
				$("#tablaproductos").append(\'<tr><td>&nbsp;</td>\
				<td><select id="prod\'+num+\'" name="prod[\'num\']" onChange="actualizaprod(\'+num+\')"><option value="">Seleccione</option>';
			foreach($array_productos as $k=>$v){
				echo '<option value="'.$k.'">'.$v.'</option>';
			}
			echo '</select></td>\
				<td align="center"><input type="text" class="readOnly" size="10" name="precio[\'+num+\']" id="precio\'+num+\'" value="" readOnly></td>\
				<td><input type="button" onClick="$(this).parent().parent().remove();" value="Quitar"></td>\
				</tr>\
				<tr id="idtotal"><th align="right" colspan="2">Total&nbsp;&nbsp;<td align="center"><input type="text" class="readOnly" size="10" name="monto" id="monto" value="\'+tot+\'" readOnly></td></tr>\');
				num++;
				document.forma.cantprod.value=num;
			}
			
			function sumarproductos(){
				var sumar=0;
				$(".suma").each(function(){
					sumar += $(this).val()/1;
				});
				document.forma.monto.value=sumar.toFixed(2);
			}
			//configura_busqueda_producto(0);
						
			function validar_email(valor)
			{
				// creamos nuestra regla con expresiones regulares.
				var filter = /[\w-\.]{3,}@([\w-]{2,}\.)*([\w-]{2,}\.)[\w-]{2,4}/;
				// utilizamos test para comprobar si el parametro valor cumple la regla
				if(filter.test(valor))
					return true;
				else
					return false;
			}
			
			$("#dialog").dialog({ 
				bgiframe: true,
				autoOpen: false,
				modal: true,
				width: 400,
				height: 200,
				autoResize: true,
				position: "center",
				buttons: {
					"Cerrar": function(){ 
						$(this).dialog("close"); 
					}
				},
			}); 
			
			function mostrarPdfs(ren){
				$.ajax({
				  url: "cotizacion.php",
				  type: "POST",
				  async: false,
				  data: {
					ajax: "traer_archivos",
					cveproducto: $("#prod"+ren).val()
				},
				  success: function(data) {
					datos=data.split("|");
					if(datos[0] == "1"){
						atcr(datos[1],"_blank","","");
					}
					else if(datos[0] == "2"){
						$("#dialog").html(datos[1]);$("#dialog").dialog("open");
					}
				  }
				});
			}
			
	</script>';
	echo '
	</body>
	</form>

	</html>';
	

?>