<?php
include("cnx_db.php");
include("globales.php");
$res = mysql_query("SELECT correotimbres FROM usuarios WHERE cve=1");
$row = mysql_fetch_array($res);
$emailenvio = $row[0];
if($emailenvio!=""){
	require_once('fpdf/fpdf.php');
	class FPDF2 extends PDF_MC_Table {
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
	$pdf->AddPage();
	$pdf->SetFont('Arial','B',16);
	$pdf->SetY(23);
	$pdf->Cell(190,5,$NOMBRE,0,0,'C');
	$pdf->Ln();
	$tit='';
	$pdf->MultiCell(200,5,'REPORTE DE EXISTENCIA DE TIMBRES',0,'C');
	$pdf->Ln();
	$pdf->Ln();
	$pdf->SetFont('Arial','B',10);
	$pdf->Cell(150,4,'Centro',0,0,'C',0);
	$pdf->Cell(30,4,'Timbres',0,0,'C',0);
	$pdf->Ln();		
	$pdf->SetFont('Arial','',10);
	$pdf->SetWidths(array(150,30));
	$res = mysql_query("SELECT * FROM plazas where estatus='A'  ORDER BY orden_reporte");
	while($row=mysql_fetch_array($res)){
		$renglon=array();
		$renglon[] = $row['numero'].' '.$row['nombre'];
		$renglon[] = existencia_timbres($row['cve']);
		$pdf->Row($renglon);
	}
	$nombre = "cfdi/rep_existencia".date('Y_m_d_H_i_s');
	$pdf->Output($nombre.".pdf","F");	

	$mail = obtener_mail();					
	$mail->FromName = $NOMBRE;
	$mail->Subject = "Reporte de Existencia de Timbres";
	$mail->Body = "Reporte";
	$correos = explode(",",trim($emailenvio));
	foreach($correos as $correo)
		$mail->AddAddress(trim($correo));
	$mail->AddAttachment($nombre.".pdf", "Reporte.pdf");
	$mail->Send();
	@unlink($nombre.".pdf");
}	