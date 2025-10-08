<?php

include("cnx_db.php");

if(date('w') != 0){
	mysql_query("insert into asistencia (plaza, fecha, personal, estatus) select a.plaza, CURDATE(), a.cve, IF(IFNULL(b.motivo,0) > 0, 2, 0) from personal a left join dias_justificados b on a.cve = b.personal and b.fecha = CURDATE() where a.estatus=1");
}
else{
	mysql_query("insert into asistencia (plaza, fecha, personal, estatus, domingo) select a.plaza, CURDATE(), a.cve, IF(IFNULL(b.motivo,0) > 0, 2, 0), 1 from personal a left join dias_justificados b on a.cve = b.personal and b.fecha = CURDATE() where a.estatus=1");	
}

?>