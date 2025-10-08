<?php

# Rutina de conversión de numeros a letras.
# Soporta valores de 0 a 999,999.99
function numlet($v) {
	$d=trim(sprintf("%9.2f",abs($v)));
	$t=' PESOS '.substr($d,-2).'/100 M.N.)';
	$d=substr($d,0,-3);
	if (abs($d)>999999) {
		$v=substr($d,0,strlen($d)-6);
		$y=numlet1($v);
		if ($v>1) $y.=' MILLONES ';
		else $y.=' MILLON ';
		$d=substr($d,-6);
	}
	if (abs($d)>999) {
		$y.=numlet1(substr($d,0,strlen($d)-3));
		$y.=' MIL ';
		$d=substr($d,-3);
	}	
	$y.=numlet1($d);
	return '('.$y.$t;
}

function numlet1($d) {
	static $l= array ('CERO','UN','DOS','TRES','CUATRO','CINCO','SEIS','SIETE','OCHO','NUEVE','DIEZ','ONCE','DOCE','TRECE','CATORCE','QUINCE');
	static $ld= array ('','DIEZ','VEINTE','TREINTA','CUARENTA','CINCUENTA','SESENTA','SETENTA','OCHENTA','NOVENTA');
	static $lc= array ('','CIENTO','DOSCIENTOS','TRESCIENTOS','CUATROCIENTOS','QUINIENTOS','SEISCIENTOS','SETECIENTOS','OCHOCIENTOS','NOVECIENTOS');
	
	$x='';
	$d1=abs($d);

	if ($d1==100) {
		$x.='CIEN ';
	} else {
		if ($d1>99) {
			$d2=floor($d1/100);
			$x.=$lc[$d2].' ';
			$d1=$d1-$d2*100;
		}
		if ($d1<16 && $d1>0) {
			$x.=$l[$d1];
		} else {
			$d2=floor($d1/10);
			$d1=$d1-$d2*10;
			if ($d2==1 && $d1>0) {
				$x.='DIECI'.$l[$d1].' ';
			} else {
				if ($d2==2 && $d1>0) {
					$x.='VEINTI'.$l[$d1].' ';
				} else {
					$x.=$ld[$d2].' ';
					if ($d1>0) $x.=' Y '.$l[$d1].' ';
				}
			}
		}
	}
	return $x;
}

?>
