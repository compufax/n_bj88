<?php

if ($_SESSION['CveUsuario'] == '') {
	require_once('cnx_db.php');
	require_once('globales.php'); 
	require_once('validarloging.php');
	//$_SESSION['CveUsuario'] = $_POST['cveusuario'];
}
	if($_SESSION['CveUsuario']!=1){
		unset($array_modulos[3]);
		unset($array_modulos[99]);
	}
	foreach($array_modulos as $k=>$v){ 
		if($_SESSION['TipoUsuario']==1){
			$rs=mysql_query("SELECT * FROM menu a WHERE modulo='$k' ORDER BY orden");
		}
		else{
			$rs=mysql_query("SELECT a.cve, a.link, a.nombre FROM menu as a INNER JOIN usuario_accesos as b ON (b.menu=a.cve AND b.usuario='".$_SESSION['CveUsuario']."' AND b.acceso>0 AND b.usuario > 0) WHERE a.modulo='$k' AND a.solo_root!=1 AND (b.plaza='{$_POST['cveplaza']}' OR a.plazaobligatoria=0) GROUP BY a.cve ORDER BY a.orden");
		}
		if(mysql_num_rows($rs)>0){

?>
			<li class="nav-item menuprincipal">
				<span id="modulo_<?php echo $k;?>" style="cursor:pointer;" class="nav-link collapsed" data-toggle="collapse" data-target="#collapse_<?php echo $k;?>" aria-expanded="true" aria-controls="collapse_<?php echo $k;?>">
			          <span><?php echo $v;?></span>
			    </span>
			    <div id="collapse_<?php echo $k;?>" class="collapse" aria-labelledby="heading_<?php echo $k;?>" data-parent="#accordionSidebar">
			        <div class="bg-white py-2 collapse-inner rounded">
<?php
			while($ro=mysql_fetch_array($rs)) {
?>
				<span class="collapse-item" style="cursor:pointer;" onClick="$('.sidebar').find('.nav-item').removeClass('active');$(this).parents('li:first').addClass('active'); menu('<?php echo $k;?>', '<?php echo $ro['cve'];?>', '<?php echo $ro['link'];?>', '<?php echo $ro['nombre'];?>', '<?php echo $_SESSION['reg_sistema'];?>');"><?php echo $ro['nombre']; ?></span>
<?php
			}
?>
		        	</div>
		    	</div>
			</li>
<?php 
		} 
	}

?>