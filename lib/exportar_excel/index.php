<?php

	include "../elementos/registro_dentro.php";

	require_once('OLEwriter.php');
	require_once('BIFFwriter.php');
	require_once('Worksheet.php');
	require_once('Workbook.php');
	
	// HTTP headers
	HeaderingExcel('catalogoDRACMA.xls');                                                                              
                          
	// Creating a workbook
	$workbook = new Workbook("-");	

	function HeaderingExcel($filename){
		header("Content-type: application/vnd.ms-excel");
		header("Content-Disposition: attachment; filename=$filename" );
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
		header("Pragma: public");
	}

	function TratarFamilia($rowFamilia){
	
		global $basedatos;	
		global $workbook;
		global $articulo;
		
		$fil=3;
		$col=0;
		
		//Crea la página
		$worksheet1 =& $workbook->add_worksheet($rowFamilia->NOMBRE);

		$sql="SELECT a.* FROM ARTICULO a";
		
		$sql="$sql LEFT JOIN FAMILIA f ON (a.ID_FAMILIA=f.ID_FAMILIA)";
		$sql="$sql LEFT JOIN SUBFAMILIA sf ON (a.ID_SUBFAMILIA=sf.ID_SUBFAMILIA)";
		$sql="$sql LEFT JOIN PAIS p ON (a.ID_PAIS=p.ID_PAIS)";
		$sql="$sql LEFT JOIN MONEDA m ON (a.ID_MONEDA=m.ID_MONEDA)";
		$sql="$sql LEFT JOIN METAL mt ON (a.ID_METAL=mt.ID_METAL)";										

		$sql="$sql WHERE a.ESTADO='A'";		
		$sql="$sql AND a.ID_FAMILIA=$rowFamilia->ID_FAMILIA";
		$sql="$sql ORDER BY f.NOMBRE,sf.ANO_DESDE,sf.NOMBRE,p.NOMBRE ASC,a.LOTE DESC,mt.ORDEN,m.NOMBRE_ORDEN,a.VALOR*m.FACTOR,a.ANO,a.MES_EMISION,a.DIA_EMISION,a.ANO_CAL,a.ENSAYADOR,a.SERIE,a.PRECIO ASC";		
		
		$resArticulos = $basedatos->ejecutarSQL($sql);
		$worksheet1->set_column(1, 1, 40);
		$worksheet1->set_row(1, 20);				

		while($rowArticulo = $basedatos->siguiente_reg($resArticulos)){
			//NOMBRE FAMILIA
			$worksheet1->write_string(1, 1, $rowFamilia->NOMBRE);
			//REFERENCIA
			$worksheet1->write_NUMBER($fil, $col, $rowArticulo->ID_ARTICULO);
			$col++;			
			//SUBFAMILIA
			if ($rowArticulo->ID_SUBFAMILIA<>null){
				$rowSubfamilia = $basedatos->mostrar_reg_exist("SUBFAMILIA","ID_SUBFAMILIA",$rowArticulo->ID_SUBFAMILIA);
				$worksheet1->write_string($fil, $col, $rowSubfamilia->NOMBRE);
			}elseif($rowFamilia->ID_FAMILIA==8 || $rowFamilia->ID_FAMILIA==10){
					if ($rowArticulo->ID_PAIS<>""){
						$rowPais=$basedatos->mostrar_reg_exist("PAIS","ID_PAIS",$rowArticulo->ID_PAIS);
						$worksheet1->write_string($fil, $col, $rowPais->NOMBRE);
					}			
			}elseif($rowFamilia->ID_FAMILIA==9){
				$mndTmp = $articulo->mostrar_moneda_valor($rowArticulo);
				$worksheet1->write_string($fil, $col, $mndTmp);			
			}
			$col++;
			//DESCRIPCION
			$myDesc = $articulo->mostrar_descripcion($rowArticulo,true);		
			$worksheet1->write_string($fil, $col, $myDesc);
			$col++;
			//CALIDAD
			if($rowArticulo->ID_CALIDAD<>""):
				$regCalidad = $basedatos->mostrar_reg_exist('CALIDAD',ID_CALIDAD,$rowArticulo->ID_CALIDAD);
				$myCal="$regCalidad->NOMBRE$rowArticulo->GRADO";
			endif;
			if($rowArticulo->ID_CALIDAD2<>""):
				if($rowArticulo->ID_CALIDAD<>"") $myCal="$myCal a ";
				$regCalidad = $basedatos->mostrar_reg_exist('CALIDAD',ID_CALIDAD,$rowArticulo->ID_CALIDAD2);
				$myCal=$myCal."$regCalidad->NOMBRE$rowArticulo->GRADO2";
			endif;			
			$worksheet1->write_string($fil, $col, $myCal);
			$col++;
			//METAL
			if($rowArticulo->ID_METAL<>""):
				$rowMetal=$basedatos->mostrar_reg_exist("METAL","ID_METAL",$rowArticulo->ID_METAL);
				$worksheet1->write_string($fil, $col, $rowMetal->ABREV);
			endif;
			$col++;
			//PRECIO
			$worksheet1->write_string($fil, $col, number_format($rowArticulo->PRECIO,2,",",".")."?");
			$col=0;
			$fil++;
		}
	}

	// HAYO LOS NOMBRES DE LAS FAMILIAS		
	$sql="SELECT * FROM FAMILIA ORDER BY ORDEN";
	$resFamilias = $basedatos->ejecutarSQL($sql);
	
	if ($basedatos->num_regs_select($resFamilias) == 0){
		include "error.php";
		exit;
	}
	
	while($rowFamilia = $basedatos->siguiente_reg($resFamilias)){
		TratarFamilia($rowFamilia);
	}

	//CIERRA HOJA EXCEL Y FIN
	$workbook->close();

?>