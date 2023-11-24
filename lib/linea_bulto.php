<?

//Se utilizará para controlar las linea bulto

class linea_bulto
{
    //FUNCION PARA SABER SI SE PUEDE PREPARAR UNA LINEA MODIFICANDO OTRA
    function poneBandera($idMaterial, $cantidadDisponible, $cantidadPendiente, $idOrden)
    {
        global $bd;

        $bandera            = false;
        $menoresADisponible = 0;
        $sumatorio          = 0;

        $sql = "SELECT SUM(CANTIDAD) AS CANTIDAD_TOTAL
						FROM BULTO_LINEA AS BL 
						INNER JOIN BULTO AS B ON B.ID_BULTO = BL.ID_BULTO
						WHERE BL.ID_MATERIAL = $idMaterial 
						AND B.ID_ORDEN_PREPARACION = $idOrden
						GROUP BY BL.ID_BULTO_LINEA";

        $result = $bd->ExecSQL($sql);
        while ($row = $bd->SigReg($result)):
            if ($row->CANTIDAD_TOTAL < $cantidadDisponible):
                $menoresADisponible++;
            endif;
            $sumatorio = $sumatorio + $row->CANTIDAD_TOTAL;
        endwhile;

        $restante = $cantidadPendiente - $sumatorio;
        if ($cantidadDisponible > $restante):
            $menoresADisponible++;
        endif;

        if ($menoresADisponible > 1): //SI AL MENOS HAY 2 POSIBLES DE SERVIR
            $bandera = true;
        endif;

        return $bandera;
    }

    //FUNCION PARA SABER SI UNA LINEA DE UN BULTO ESTANDAR ESTA PREPARADA O NO
    function lineaBultoPreparada($idBulto, $idMaterial)
    {
        global $bd;
        global $NotificaErrorPorEmail;

        $NotificaErrorPorEmail = "No";
        $sqlRest               = "ID_BULTO = $idBulto AND ID_MATERIAL = $idMaterial";
        $reg                   = $bd->VerRegRest("MOVIMIENTO_SALIDA_LINEA", $sqlRest, "No");
        if ($reg == false):
            return false;
        endif;

        $NotificaErrorPorEmail = "No";
        $rowBulto              = $bd->VerReg("BULTO", "ID_BULTO", $idBulto, "No");
        if ($rowBulto->MATERIALES_SIN_ASIGNAR == 1):
            return false;
        endif;

        $sql    = "SELECT COUNT(DISTINCT(ID_MOVIMIENTO_SALIDA_LINEA)) AS NUM
						FROM MOVIMIENTO_SALIDA_LINEA 
						WHERE ID_BULTO = $idBulto 
						AND ID_MATERIAL = $idMaterial 
						AND ID_UBICACION = 0";
        $result = $bd->ExecSQL($sql);
        $row    = $bd->SigReg($result);

        if ($row->NUM > 0):
            return false;
        else:
            return true;
        endif;
    }

    //FUNCION PARA SABER SI UNA LINEA DE UN BULTO A GRANEL ESTA PREPARADA O NO
    function lineaBultoGranelPreparada($idBulto, $idMaterial)
    {
        global $bd;
        global $NotificaErrorPorEmail;

        $NotificaErrorPorEmail = "No";
        $sqlRest               = "ID_BULTO = $idBulto AND ID_MATERIAL = $idMaterial";
        $reg                   = $bd->VerRegRest("BULTO_GRANEL", $sqlRest, "No");
        if ($reg == false):
            return false;
        endif;

        $sql    = "SELECT COUNT(DISTINCT(ID_BULTO_GRANEL)) AS NUM
						FROM BULTO_GRANEL AS BG
						INNER JOIN BULTO AS B ON B.ID_BULTO = BG.ID_BULTO
						WHERE BG.ID_BULTO = $idBulto 
						AND BG.ID_MATERIAL = $idMaterial
						AND (B.ESTADO = 'Cerrado' OR B.ESTADO = 'Embarcado')";
        $result = $bd->ExecSQL($sql);
        $row    = $bd->SigReg($result);

        if ($row->NUM > 0):
            return true;
        else:
            return false;
        endif;
    }

    //FUNCION QUE DEVUELVE LA CANTIDAD PREPARADA DE UNA LINEA DE UN BULTO NO ESTANDAR
    function cantidadLineaBultoGranelPreparada($idOrden, $idMaterial)
    {
        global $bd;

        $sqlBultosNoEstandar    = "SELECT ID_BULTO FROM BULTO WHERE BULTO_NO_ESTANDAR = 1 AND ID_ORDEN_PREPARACION = $idOrden";
        $resultBultosNoEstandar = $bd->ExecSQL($sqlBultosNoEstandar);

        if ($bd->NumRegs($resultBultosNoEstandar) == 0):
            return 0;
        endif;

        $listaBultosNoEstandar = "";
        while ($rowBultoNoEstandar = $bd->SigReg($resultBultosNoEstandar)):
            if ($listaBultosNoEstandar == ""):
                $listaBultosNoEstandar = $rowBultoNoEstandar->ID_BULTO;
            else:
                $listaBultosNoEstandar = $listaBultosNoEstandar . "," . $rowBultoNoEstandar->ID_BULTO;
            endif;
        endwhile;

        $sqlCantidad    = "SELECT IF(SUM(CANTIDAD) IS NULL,0, SUM(CANTIDAD)) AS CANTIDAD_TOTAL
										FROM BULTO_GRANEL 
										WHERE ID_MATERIAL = $idMaterial 
										AND ID_ORDEN_PREPARACION = $idOrden 
										AND ID_BULTO IN ($listaBultosNoEstandar)";
        $resultCantidad = $bd->ExecSQL($sqlCantidad);
        $rowCantidad    = $bd->SigReg($resultCantidad);

        return $rowCantidad->CANTIDAD_TOTAL;
    }

    //FUNCION QUE DEVUELVE EL NUMERO DE BULTOS DE UNA ORDEN
    //OPCIONALMENTE SE LE PUEDE PASAR EL ESTADO DE BULTO
    function bultosOrden($idOrden, $estado = "")
    {
        global $bd;

        //BUSCO LA ORDEN
        $rowOrden = $bd->VerReg("ORDEN_PREPARACION", "ID_ORDEN_PREPARACION", $idOrden);

        if ($estado != ""):
            $sqlWhere = "AND B.ESTADO = '$estado'";
        endif;
        $sqlWhere = "AND B.MATERIALES_SIN_ASIGNAR = 0 " . $sqlWhere;

        //OBTENGO LA CANTIDAD DE BULTOS ESTANDAR Y NO ESTANDAR
        $clausulaWhere = "B.ID_ORDEN_PREPARACION = $rowOrden->ID_ORDEN_PREPARACION $sqlWhere";
        $numBultos     = $bd->NumRegsTabla("BULTO AS B", $clausulaWhere);

        return $numBultos;
    }

    //FUNCION QUE DEVUELVE EL NUMERO DE LINEAS DE UNA ORDEN
    function lineasOrden($idOrden)
    {
        global $bd;
        //MAL - NO SE PUEDE BASAR EN MOVIMIENTOS YA QUE SOLO SIRVEN PARA DESUBICAR, NO AGRUPAR
//		$sql = "SELECT COUNT(DISTINCT ID_MATERIAL, MSL.ID_BULTO) AS NUM
//						FROM MOVIMIENTO_SALIDA_LINEA AS MSL 
//						INNER JOIN MOVIMIENTO_SALIDA AS MS ON MS.ID_MOVIMIENTO_SALIDA = MSL.ID_MOVIMIENTO_SALIDA
//						WHERE ID_ORDEN_PREPARACION = $idOrden ";
//		$result = $bd->ExecSQL($sql);
//		$row = $bd->SigReg($result);
//		return $row->NUM;
        //MAL

        //ANTIGUO

        //BUSCO LA ORDEN
        $rowOrden = $bd->VerReg("ORDEN_PREPARACION", "ID_ORDEN_PREPARACION", $idOrden);

        //EN FUNCION DEL ESTADO DE LA ORDEN SE APLICA UN FILTRO O NO
        if (($rowOrden->ESTADO == 'Preparado') || ($rowOrden->ESTADO == 'En Embarque') || ($rowOrden->ESTADO == 'Embarcado') || ($rowOrden->ESTADO == 'Expedido') || ($rowOrden->ESTADO == 'Contabilizado')):
            $sqlBultosWhere = "AND B.BULTO_NO_ESTANDAR = 0 AND B.MATERIALES_SIN_ASIGNAR = 0";
        endif;

        //BUSCO LOS BULTOS ESTANDAR QUE NO ESTAN ASOCIADOS A BULTOS GRANEL CON CANTIDAD POSITIVA
        $listaIdBultosEstandar = "";
        $sql                   = "SELECT ID_BULTO FROM BULTO AS B WHERE ID_ORDEN_PREPARACION = $rowOrden->ID_ORDEN_PREPARACION AND B.MATERIALES_SIN_ASIGNAR = 0 $sqlBultosWhere";
        $result                = $bd->ExecSQL($sql);
        while ($row = $bd->SigReg($result)):
            if ($listaIdBultosEstandar == ""):
                $listaIdBultosEstandar = $row->ID_BULTO;
            else:
                $listaIdBultosEstandar = $listaIdBultosEstandar . "," . $row->ID_BULTO;
            endif;
        endwhile;

        //CUENTO LAS LINEAS DE LOS BULTOS ESTANDAR
        if ($listaIdBultosEstandar != ""):
            $sqlRest                 = "ID_ORDEN_PREPARACION = $idOrden AND BL.ID_BULTO IN ($listaIdBultosEstandar) AND CANTIDAD > 0";
            $numLineasBultosEstandar = $bd->NumRegsTabla("BULTO_LINEA AS BL INNER JOIN BULTO AS B ON B.ID_BULTO = BL.ID_BULTO", $sqlRest);
        else:
            $numLineasBultosEstandar = 0;
        endif;

        //BUSCO LAS POSIBLES LINEAS QUE NO SE ENCUENTREN EL BULTOS NUEVOS
        $numLineasNoEnBultos = 0;
        $sql                 = "SELECT * FROM BULTO_LINEA AS BL INNER JOIN BULTO AS B ON B.ID_BULTO = BL.ID_BULTO WHERE B.ID_ORDEN_PREPARACION = $idOrden AND B.MATERIALES_SIN_ASIGNAR = 1 $sqlBultosWhere";
        $result              = $bd->ExecSQL($sql);
        while ($rowLinea = $bd->SigReg($result)):
            $sqlCantidadEnBultosNuevos    = "SELECT IF(SUM(CANTIDAD) IS NULL, 0, SUM(CANTIDAD)) AS CANTIDAD_TOTAL FROM BULTO_GRANEL WHERE ID_ORDEN_PREPARACION = $idOrden AND ID_MATERIAL = $rowLinea->ID_MATERIAL";
            $resultCantidadEnBultosNuevos = $bd->ExecSQL($sqlCantidadEnBultosNuevos);
            $rowCantidadEnBultosNuevos    = $bd->SigReg($resultCantidadEnBultosNuevos);
            if ($rowCantidadEnBultosNuevos->CANTIDAD_TOTAL < $rowLinea->CANTIDAD):
                $numLineasNoEnBultos = $numLineasNoEnBultos + 1;
            endif;
        endwhile;

        //BUSCO LOS BULTOS NO ESTANDAR
        $listaIdBultosNoEstandar = "";
        $sql                     = "SELECT ID_BULTO FROM BULTO_GRANEL WHERE ID_ORDEN_PREPARACION = $idOrden";
        $result                  = $bd->ExecSQL($sql);
        while ($row = $bd->SigReg($result)):
            if ($listaIdBultosNoEstandar == ""):
                $listaIdBultosNoEstandar = $row->ID_BULTO;
            else:
                $listaIdBultosNoEstandar = $listaIdBultosNoEstandar . "," . $row->ID_BULTO;
            endif;
        endwhile;

        //CUENTO LAS LINEAS DE LOS BULTOS NO ESTANDAR
        if ($listaIdBultosNoEstandar != ""):
            $sqlRest                   = "BG.ID_ORDEN_PREPARACION = $idOrden AND BG.ID_BULTO IN ($listaIdBultosNoEstandar)";
            $numLineasBultosNoEstandar = $bd->NumRegsTabla("BULTO_GRANEL AS BG INNER JOIN BULTO AS B ON B.ID_BULTO = BG.ID_BULTO", $sqlRest);
        else:
            $numLineasBultosNoEstandar = 0;
        endif;

        return $numLineasBultosEstandar + $numLineasNoEnBultos + $numLineasBultosNoEstandar;

        //FIN ANTIGUO
    }

    //FUNCION QUE DEVUELVE EL NUMERO DE LINEAS PREPARADAS DE UNA ORDEN
    function lineasPreparadasOrden($idOrden)
    {
        global $bd;

        //BUSCO LA ORDEN
        $rowOrden = $bd->VerReg("ORDEN_PREPARACION", "ID_ORDEN_PREPARACION", $idOrden);

        //EN FUNCION DEL ESTADO DE LA ORDEN SE APLICA UN FILTRO O NO
        if (($rowOrden->ESTADO == 'Preparado') || ($rowOrden->ESTADO == 'En Embarque') || ($rowOrden->ESTADO == 'Embarcado') || ($rowOrden->ESTADO == 'Expedido') || ($rowOrden->ESTADO == 'Contabilizado')):
            $sqlBultosWhere = "AND B.MATERIALES_SIN_ASIGNAR = 0 AND B.BULTO_NO_ESTANDAR = 0";
        endif;

        //BUSCO LOS BULTOS ESTANDAR
        $listaIdBultosEstandar = "";
        $sql                   = "SELECT ID_BULTO FROM BULTO AS B WHERE ID_ORDEN_PREPARACION = $rowOrden->ID_ORDEN_PREPARACION $sqlBultosWhere";
        $result                = $bd->ExecSQL($sql);
        while ($row = $bd->SigReg($result)):
            if ($listaIdBultosEstandar == ""):
                $listaIdBultosEstandar = $row->ID_BULTO;
            else:
                $listaIdBultosEstandar = $listaIdBultosEstandar . "," . $row->ID_BULTO;
            endif;
        endwhile;

        $numLineasPreparadas = 0;

        //BUSCO Y CUENTO LAS LINEAS DE LOS BULTOS ESTANDAR
        if ($listaIdBultosEstandar != ""):
            $sqlLinea    = "SELECT * FROM BULTO_LINEA AS BL INNER JOIN BULTO AS B ON B.ID_BULTO = BL.ID_BULTO WHERE ID_ORDEN_PREPARACION = $idOrden AND BL.ID_BULTO IN ($listaIdBultosEstandar)";
            $resultLinea = $bd->ExecSQL($sqlLinea);
            while ($rowLinea = $bd->SigReg($resultLinea)):
                if ($this->lineaBultoPreparada($rowLinea->ID_BULTO, $rowLinea->ID_MATERIAL)):
                    $numLineasPreparadas = $numLineasPreparadas + 1;
                endif;
            endwhile;
        endif;

        //BUSCO LOS BULTOS NO ESTANDAR
        $listaIdBultosNoEstandar = "";
        $sql                     = "SELECT ID_BULTO FROM BULTO_GRANEL WHERE ID_ORDEN_PREPARACION = $idOrden";
        $result                  = $bd->ExecSQL($sql);
        while ($row = $bd->SigReg($result)):
            if ($listaIdBultosNoEstandar == ""):
                $listaIdBultosNoEstandar = $row->ID_BULTO;
            else:
                $listaIdBultosNoEstandar = $listaIdBultosNoEstandar . "," . $row->ID_BULTO;
            endif;
        endwhile;

        //BUSCO Y CUENTO LAS LINEAS DE LOS BULTOS ESTANDAR
        if ($listaIdBultosNoEstandar != ""):
            $sqlLinea    = "SELECT * FROM BULTO_GRANEL AS BG INNER JOIN BULTO AS B ON B.ID_BULTO = BG.ID_BULTO WHERE BG.ID_ORDEN_PREPARACION = $idOrden AND BG.ID_BULTO IN ($listaIdBultosNoEstandar)";
            $resultLinea = $bd->ExecSQL($sqlLinea);
            while ($rowLinea = $bd->SigReg($resultLinea)):
                if ($this->lineaBultoGranelPreparada($rowLinea->ID_BULTO, $rowLinea->ID_MATERIAL)):
                    $numLineasPreparadas = $numLineasPreparadas + 1;
                endif;
            endwhile;
        endif;

        return $numLineasPreparadas;
    }

    function MoverBultoEntreOrdenes($idBulto, $idOrdenOrigen, $idOrdenDestino, &$arrIdMovs, &$ultimoMovimientoGenerado, $conTransaccion = false)
    {
        global $bd;
        global $administrador;

        if ($conTransaccion):
            $bd->begin_transaction(); //COMIENZO LA TRANSACCION
        endif;

        $NotificaErrorPorEmail = "No";
        $rowBulto              = $bd->VerReg("BULTO", "ID_BULTO", $idBulto, "No");

        if ($rowBulto->BULTO_NO_ESTANDAR == 0): //BULTO ESTANDAR
            //ACTUALIZO LA ORDEN DE CARGA DEL BULTO
            $sqlUpdate = "UPDATE BULTO SET
										ID_ORDEN_PREPARACION = $idOrdenDestino 
										, IMPRESO = 0 
										, POSICION = -1 
										WHERE ID_BULTO = $idBulto";
            $bd->ExecSQL($sqlUpdate);

            //ACTUALIZO LA ORDEN DE CARGA DEL MOVIMIENTO DE SALIDA
            $NotificaErrorPorEmail = "No";
            $clausulaWhere         = "ID_ORDEN_PREPARACION = $idOrdenOrigen AND ID_BULTO = $idBulto";
            $rowMovSal             = $bd->VerRegRest("MOVIMIENTO_SALIDA", $clausulaWhere);

            $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA SET ID_ORDEN_PREPARACION = $idOrdenDestino WHERE ID_MOVIMIENTO_SALIDA = $rowMovSal->ID_MOVIMIENTO_SALIDA";
            $bd->ExecSQL($sqlUpdate);

        else: //BULTO NO ESTANDAR

            //BUSCO LA ORDEN DE DESTINO
            $NotificaErrorPorEmail = "No";
            $rowOrdenDestino       = $bd->VerReg("ORDEN_PREPARACION", "ID_ORDEN_PREPARACION", $idOrdenDestino, "No");

            //COMPRUEBO SI EXISTE UN BULTO GRANEL EN LA ORDEN DE DESTINO
            /*			$NotificaErrorPorEmail = "No";
                        $clausulaWhere = "ID_ORDEN_PREPARACION = $idOrdenDestino AND MATERIALES_SIN_ASIGNAR = 1";
                        $rowBultoGranelEnDestino = $bd->VerRegRest("BULTO", $clausulaWhere, "No");	*/
            $sqlBultoGranelEnDestino    = "SELECT * FROM BULTO WHERE ID_ORDEN_PREPARACION = $idOrdenDestino AND MATERIALES_SIN_ASIGNAR = 1";
            $resultBultoGranelEnDestino = $bd->ExecSQL($sqlBultoGranelEnDestino);
            $rowBultoGranelEnDestino    = $bd->SigReg($resultBultoGranelEnDestino);

            //BUSCO EL ID_BULTO DEL BULTO A GRANEL EN LA ORDEN DE DESTINO
            $idBultoGranelDestino = "";
            if ($rowBultoGranelEnDestino == false):
                //SI NO EXISTE EL BULTO A GRANEL LO CREO
                $sqlInsert = "INSERT INTO BULTO SET
											ID_ORDEN_PREPARACION = $idOrdenDestino
											, ESTADO = 'Cerrado'
											, MATERIALES_SIN_ASIGNAR = 1";
                $bd->ExecSQL($sqlInsert);
                $idBultoGranelDestino = $bd->IdAsignado();
            else:
                $idBultoGranelDestino = $rowBultoGranelEnDestino->ID_BULTO;
            endif;

            //BUSCO EL ID_BULTO DEL BULTO A GRANEL EN LA ORDEN DE ORIGEN
            $NotificaErrorPorEmail = "No";
            $clausulaWhere         = "ID_ORDEN_PREPARACION = $idOrdenOrigen AND MATERIALES_SIN_ASIGNAR = 1";
            $rowBultoGranelOrigen  = $bd->VerRegRest("BULTO", $clausulaWhere, "No");
            $idBultoGranelOrigen   = $rowBultoGranelOrigen->ID_BULTO;

            //$arrIdMovs = array(); //ARRAY PARA GUARDAR LAS CABECERAS DE MOVIMIENTOS GENERADAS
            //$ultimoMovimientoGenerado = "";

            //INCREMENTO O GENERO LAS CANTIDADES EN LAS LINEAS DE LOS BULTOS DEL DESTINO, Y DECREMENTO EN LAS LINEAS DE LOS BULTOS DEL ORIGEN
            //TAMBIEN GENERO LAS CABECERAS DE NUEVOS MOVIMIENTOS Y LINEAS NECESARIAS DESTINO
            //DECREMENTO LAS CANTIDADES DE LAS LINEAS DE MOVIMIENTO ORIGEN
            $sqlBultoGranel    = "SELECT * FROM BULTO_GRANEL WHERE ID_ORDEN_PREPARACION = $idOrdenOrigen AND ID_BULTO = $idBulto ORDER BY ID_MOVIMIENTO_SALIDA"; // EL ORDEN IMPORTA A LA HORA DE GENERAR LOS NUEVOS MOVIMIENTOS
            $resultBultoGranel = $bd->ExecSQL($sqlBultoGranel);
            while ($rowBultoGranel = $bd->SigReg($resultBultoGranel)):
                //GUARDO LA CANTIDAD DEL BULTO GRANEL
                $cantidadBultoGranel = $rowBultoGranel->CANTIDAD;

                //BUSCO EL BULTO_LINEA DE DESTINO
                /*				$NotificaErrorPorEmail = "No";
                                $clausulaWhere = "ID_BULTO = $idBultoGranelDestino AND ID_MATERIAL = $rowBultoGranel->ID_MATERIAL";
                                $rowBultoLineaDestino = $bd->VerRegRest("BULTO_LINEA", $clausulaWhere, "No");*/

                $sqlBultoLineaDestino    = "SELECT * FROM BULTO_LINEA WHERE ID_BULTO = $idBultoGranelDestino AND ID_MATERIAL = $rowBultoGranel->ID_MATERIAL";
                $resultBultoLineaDestino = $bd->ExecSQL($sqlBultoLineaDestino);
                $rowBultoLineaDestino    = $bd->SigReg($resultBultoLineaDestino);

                if ($rowBultoLineaDestino == false):
                    //GENERO EL BULTO_LINEA DEL MATERIAL CORRESPONDIENTE DESTINO
                    $sqlInsert = "INSERT INTO BULTO_LINEA SET
												ID_BULTO = $idBultoGranelDestino
												, ID_MATERIAL = $rowBultoGranel->ID_MATERIAL
												, CANTIDAD_DEFECTO = $rowBultoGranel->CANTIDAD
												, CANTIDAD = $rowBultoGranel->CANTIDAD";
                    $bd->ExecSQL($sqlInsert);
                else:
                    //INCREMENTO EL BULTO_LINEA DEL MATERIAL CORRESPONDIENTE DESTINO
                    $sqlUpdate = "UPDATE BULTO_LINEA SET
												CANTIDAD_DEFECTO = CANTIDAD_DEFECTO + $rowBultoGranel->CANTIDAD
												, CANTIDAD = CANTIDAD + $rowBultoGranel->CANTIDAD
												WHERE ID_BULTO_LINEA = $rowBultoLineaDestino->ID_BULTO_LINEA";//echo($sqlUpdate);exit();
                    $bd->ExecSQL($sqlUpdate);
                endif;

                //BUSCO EL BULTO A GRANEL DE LA ORDEN ORIGEN
                $NotificaErrorPorEmail = "No";
                $clausulaWhere         = "ID_ORDEN_PREPARACION = $idOrdenOrigen AND MATERIALES_SIN_ASIGNAR = 1";
                $rowBultoGranelOrigen  = $bd->VerRegRest("BULTO", $clausulaWhere, "No");

                //DECREMENTO EL BULTO_LINEA DEL MATERIAL GRANEL CORRESPONDIENTE ORIGEN
                $NotificaErrorPorEmail = "No";
                $clausulaWhere         = "ID_BULTO = $rowBultoGranelOrigen->ID_BULTO AND ID_MATERIAL = $rowBultoGranel->ID_MATERIAL";
                $rowBultoLineaOrigen   = $bd->VerRegRest("BULTO_LINEA", $clausulaWhere, "No");
                $sqlUpdate             = "UPDATE BULTO_LINEA SET
											CANTIDAD_DEFECTO = CANTIDAD_DEFECTO - $rowBultoGranel->CANTIDAD 
											, CANTIDAD = CANTIDAD - $rowBultoGranel->CANTIDAD 
											WHERE ID_BULTO_LINEA = $rowBultoLineaOrigen->ID_BULTO_LINEA";
                $bd->ExecSQL($sqlUpdate);

                //GENERO LAS NUEVAS CABECERAS DEL MOVIMIENTO DESTINO SI TODAVIA NO LAS HE CREADO
                if (!(in_array($rowBultoGranel->ID_MOVIMIENTO_SALIDA, (array) $arrIdMovs))):
                    $NotificaErrorPorEmail = "No";
                    $rowMovOrigen          = $bd->VerReg("MOVIMIENTO_SALIDA", "ID_MOVIMIENTO_SALIDA", $rowBultoGranel->ID_MOVIMIENTO_SALIDA, "No");

                    if ($rowOrdenDestino->ID_EXPEDICION == 0):
                        $idExpedicion = 'NULL';
                    else:
                        $idExpedicion = $rowOrdenDestino->ID_EXPEDICION;
                    endif;

                    $sqlInsert = "INSERT INTO MOVIMIENTO_SALIDA SET
												ID_PEDIDO_SALIDA = $rowMovOrigen->ID_PEDIDO_SALIDA
												, ID_ORDEN_PREPARACION = $idOrdenDestino
												, ID_EXPEDICION = $idExpedicion
												, ESTADO = '$rowMovOrigen->ESTADO'
												, FECHA = '$rowMovOrigen->FECHA'
												, MODULO_ENVIO_BULTOS = ''
												, ID_BULTO = $idBultoGranelDestino
												, BULTOS_OFICINA = '$rowMovOrigen->BULTOS_OFICINA'
												, ALBARAN_IMPRESO = '$rowMovOrigen->ALBARAN_IMPRESO'
												, FECHA_IMPRESION = '$rowMovOrigen->FECHA_IMPRESION'
												, ETIQUETAS_IMPRESAS = '$rowMovOrigen->ETIQUETAS_IMPRESAS'
												, PESO = '$rowMovOrigen->PESO'
												, FECHA_EXPEDICION = '$rowMovOrigen->FECHA_EXPEDICION'";
                    $bd->ExecSQL($sqlInsert);
                    $ultimoMovimientoGenerado = $bd->IdAsignado(); //ME GUARDO EL IDENTIFICADOR DEL MOVIMIENTO GENERADO


                    //NUEVO PARA MOVER BULTOS DEL BULTO A GRANEL*****************************************************************************************************************
                    //SI EL ESTADO DE LA ORDEN DE DESTINO ES 'PREPARADO', EL ESTADO DEL MOVIMIENTO DEBERA SER 'Expedido' Y ALBARAN IMPRESO = 1
                    $rowOrdenOrigen = $bd->VerReg("ORDEN_PREPARACION", "ID_ORDEN_PREPARACION", $idOrdenOrigen, "No");
                    if (($rowOrdenDestino->ESTADO == 'Preparado') && ($rowOrdenOrigen->ESTADO == 'En Preparacion')):
                        $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA SET
													ESTADO = 'Expedido' 
													, ALBARAN_IMPRESO = 1 
													WHERE ID_MOVIMIENTO_SALIDA = $ultimoMovimientoGenerado";
                        $bd->ExecSQL($sqlUpdate);
                    endif;
                    //NUEVO PARA MOVER BULTOS DEL BULTO A GRANEL*****************************************************************************************************************


                    //AÑADO AL ARRAY DE MOVIMIENTOS PASADOS EL QUE ACABAMOS DE LEER
                    $arrIdMovs[] = $rowBultoGranel->ID_MOVIMIENTO_SALIDA;
                endif;

                //GENERO LAS NUEVAS LINEAS DEL MOVIMIENTO DE DESTINO
                $NotificaErrorPorEmail = "No";
                $sqlMovSalLin          = "SELECT * FROM MOVIMIENTO_SALIDA_LINEA WHERE ID_MOVIMIENTO_SALIDA = $rowBultoGranel->ID_MOVIMIENTO_SALIDA AND ID_MATERIAL = $rowBultoGranel->ID_MATERIAL AND ID_BULTO = $idBultoGranelOrigen AND CANTIDAD > 0";
                $resultMovSalLin       = $bd->ExecSQL($sqlMovSalLin, "No");
                while ($rowMovLinOrigen = $bd->SigReg($resultMovSalLin)):
                    //CALCULAMOS LA CANTIDAD
                    if ($rowMovLinOrigen->CANTIDAD < $cantidadBultoGranel):
                        $cantidad            = $rowMovLinOrigen->CANTIDAD;
                        $cantidadBultoGranel = $cantidadBultoGranel - $cantidad;
                    else:
                        $cantidad            = $cantidadBultoGranel;
                        $cantidadBultoGranel = 0;
                    endif;

                    /*

                    //JUNTO******************************************************************************
                    //$NotificaErrorPorEmail = "No";
                    //$clausulaWhere = "ID_MOVIMIENTO_SALIDA = $ultimoMovimientoGenerado AND ID_UBICACION = $rowMovLinOrigen->ID_UBICACION AND ID_MATERIAL = $rowMovLinOrigen->ID_MATERIAL";
                    //$rowMovLinOrigenExiste = $bd->VerRegRest("MOVIMIENTO_SALIDA_LINEA", $clausulaWhere, "No");

                    // Carlos
                    $sqlMovLinOrigenExiste="SELECT * FROM MOVIMIENTO_SALIDA_LINEA WHERE ID_MOVIMIENTO_SALIDA = $ultimoMovimientoGenerado AND ID_UBICACION = $rowMovLinOrigen->ID_UBICACION AND ID_MATERIAL = $rowMovLinOrigen->ID_MATERIAL";
                    $resultMovLinOrigenExiste=$bd->ExecSQL($sqlMovLinOrigenExiste);
                    $rowMovLinOrigenExiste=$bd->SigReg($resultMovLinOrigenExiste);
                    // Fin Carlos
                    //FIN JUNTO**************************************************************************

                    if ($rowMovLinOrigenExiste == false):*/
                    $sqlInsert = "INSERT INTO MOVIMIENTO_SALIDA_LINEA SET
												ID_MOVIMIENTO_SALIDA = $ultimoMovimientoGenerado 
												, ID_UBICACION = $rowMovLinOrigen->ID_UBICACION 
												, ID_MATERIAL = $rowMovLinOrigen->ID_MATERIAL 
												, ID_PEDIDO_SALIDA = $rowMovLinOrigen->ID_PEDIDO_SALIDA 
												, CANTIDAD = $cantidad 
												, CANTIDAD_PEDIDO = $rowBultoGranel->CANTIDAD 
												, ID_BULTO = $idBultoGranelDestino";
                    //, CANTIDAD_PEDIDO = $rowMovLinOrigen->CANTIDAD_PEDIDO
                    $bd->ExecSQL($sqlInsert);
                    $idMovimientoSalidaLinea = $bd->IdAsignado();


                    if ($rowBulto->ESTADO == 'Cerrado'):
                        //BUSCO LA TRANSAFERENCIA INICIAL RELACIONADA
                        $clausulaWhere    = "TIPO = 'Automatico' AND ID_MATERIAL = $rowMovLinOrigen->ID_MATERIAL AND ID_MOVIMIENTO_SALIDA_LINEA = $rowMovLinOrigen->ID_MOVIMIENTO_SALIDA_LINEA";
                        $rowTransferencia = $bd->VerRegRest("MOVIMIENTO_TRANSFERENCIA", $clausulaWhere);

                        //AÑADIMOS LOS MOVIMIENTOS_TRANSFERENCIA NECESARIOS
                        $sqlInsert = "INSERT INTO MOVIMIENTO_TRANSFERENCIA SET
													FECHA = '$rowTransferencia->FECHA' 
													, ID_MATERIAL = $rowMovLinOrigen->ID_MATERIAL 
													, ID_UBICACION_ORIGEN = $rowTransferencia->ID_UBICACION_ORIGEN 
													, ID_UBICACION_DESTINO = $rowTransferencia->ID_UBICACION_DESTINO  
													, CANTIDAD = $cantidad 
													, ID_MOVIMIENTO_SALIDA = $ultimoMovimientoGenerado 
													, ID_MOVIMIENTO_SALIDA_LINEA = $idMovimientoSalidaLinea 
													, TIPO = 'Automatico'";
                        $bd->ExecSQL($sqlInsert);
                        $idMovimientoTransferencia = $bd->IdAsignado();


                        //NUEVO PARA MOVER BULTOS DEL BULTO A GRANEL*****************************************************************************************************************
                        $rowOrdenOrigen = $bd->VerReg("ORDEN_PREPARACION", "ID_ORDEN_PREPARACION", $idOrdenOrigen, "No");
                        if (($rowOrdenDestino->ESTADO == 'Preparado') && ($rowOrdenOrigen->ESTADO == 'En Preparacion')):
                            $sqlUpdate = "UPDATE MATERIAL SET
														STOCK_TOTAL = STOCK_TOTAL - $cantidad 
														, STOCK_RESERVADO = STOCK_RESERVADO - $cantidad 
														WHERE ID_MATERIAL = $rowMovLinOrigen->ID_MATERIAL";
                            $bd->ExecSQL($sqlUpdate);

                            $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
														STOCK_TOTAL = STOCK_TOTAL - $cantidad 
														WHERE ID_MATERIAL = $rowMovLinOrigen->ID_MATERIAL AND ID_UBICACION = $rowTransferencia->ID_UBICACION_DESTINO";
                            $bd->ExecSQL($sqlUpdate);
                        endif;
                        //NUEVO PARA MOVER BULTOS DEL BULTO A GRANEL*****************************************************************************************************************


                        //ACTUALIZAMOS EL MOVIMIENTO_TRANSFERENCIA ORIGINAL
                        $sqlUpdate = "UPDATE MOVIMIENTO_TRANSFERENCIA SET
													CANTIDAD = CANTIDAD - $cantidad 
													WHERE ID_MOVIMIENTO_TRANSFERENCIA = $rowTransferencia->ID_MOVIMIENTO_TRANSFERENCIA";
                        $bd->ExecSQL($sqlUpdate);

                        //PARA MATERIAL SERIABLE/LOTABLE
                        $rowMat = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowMovLinOrigen->ID_MATERIAL, "No");
                        if (($rowMat->SERIABLE) || ($rowMat->LOTABLE)): //MATERIAL SERIABLE

                            $sqlMatFisBultoGranel    = "SELECT * FROM MATERIAL_FISICO_BULTO_GRANEL WHERE ID_BULTO = $idBulto";
                            $resultMatFisBultoGranel = $bd->ExecSQL($sqlMatFisBultoGranel);
                            while ($rowMatFisBultoGranel = $bd->SigReg($resultMatFisBultoGranel)):
                                //ACTUALIZAMOS MOVIMIENTO_TRANSFERENCIA
                                $NotificaErrorPorEmail = "No";
                                $sqlMatFisTrans        = "SELECT * FROM MATERIAL_FISICO_TRANSFERENCIA WHERE ID_MATERIAL_FISICO = $rowMatFisBultoGranel->ID_MATERIAL_FISICO AND ID_MOVIMIENTO_TRANSFERENCIA = $rowTransferencia->ID_MOVIMIENTO_TRANSFERENCIA";
                                $resultMatFisTrans     = $bd->ExecSQL($sqlMatFisTrans);
                                $rowMatFisTrans        = $bd->SigReg($resultMatFisTrans);

                                if ($rowMatFisTrans != false): //CONTROLAMOS QUE SOLO HAGA LOS CAMBIOS CUANDO LO ENCUENTRE


                                    //NUEVO PARA MOVER BULTOS DEL BULTO A GRANEL*****************************************************************************************************************
                                    $rowOrdenOrigen = $bd->VerReg("ORDEN_PREPARACION", "ID_ORDEN_PREPARACION", $idOrdenOrigen, "No");
                                    if (($rowOrdenDestino->ESTADO == 'Preparado') && ($rowOrdenOrigen->ESTADO == 'En Preparacion')):
                                        $sqlUpdate = "UPDATE MATERIAL_FISICO SET
																	CANTIDAD = CANTIDAD - $rowMatFisTrans->CANTIDAD 
																	WHERE ID_MATERIAL_FISICO = $rowMatFisBultoGranel->ID_MATERIAL_FISICO";
                                        $bd->ExecSQL($sqlUpdate);

                                        $sqlUpdate = "UPDATE MATERIAL_FISICO_UBICACION SET
																	STOCK_TOTAL = STOCK_TOTAL - $rowMatFisTrans->CANTIDAD 
																	WHERE ID_MATERIAL_FISICO = $rowMatFisBultoGranel->ID_MATERIAL_FISICO AND ID_UBICACION = $rowTransferencia->ID_UBICACION_DESTINO";
                                        $bd->ExecSQL($sqlUpdate);

                                        //BUSCAMOS SI HAY UNA LINEA QUE COINCIDA CON LA DE DESTINO
                                        $sqlMatFisSal    = "SELECT * FROM MATERIAL_FISICO_SALIDA WHERE ID_MOVIMIENTO_SALIDA_LINEA = $idMovimientoSalidaLinea AND ID_MATERIAL_FISICO = $rowMatFisBultoGranel->ID_MATERIAL_FISICO";
                                        $resultMatFisSal = $bd->ExecSQL($sqlMatFisSal);
                                        $rowMatFisSal    = $bd->SigReg($resultMatFisSal);
                                        if ($rowMatFisSal != false):
                                            // ACTUALIZAMOS LA RELACION EN TABLA MATERIAL_FISICO_SALIDA DESTINO
                                            $sqlUpdate = "UPDATE MATERIAL_FISICO_SALIDA SET
																		CANTIDAD = CANTIDAD + $rowMatFisTrans->CANTIDAD 
																		WHERE ID_MATERIAL_FISICO_SALIDA = $rowMatFisSal->ID_MATERIAL_FISICO_SALIDA";
                                            $bd->ExecSQL($sqlUpdate);
                                        else:
                                            // CREAMOS LA RELACION EN TABLA MATERIAL_FISICO_SALIDA DESTINO
                                            $sqlInsert = "INSERT INTO MATERIAL_FISICO_SALIDA SET
																		ID_MATERIAL_FISICO = $rowMatFisBultoGranel->ID_MATERIAL_FISICO
																		, ID_MOVIMIENTO_SALIDA_LINEA = $idMovimientoSalidaLinea
																		, CANTIDAD = $rowMatFisTrans->CANTIDAD";
                                            $bd->ExecSQL($sqlInsert);
                                        endif;

                                        //BUSCAMOS SI HAY UNA LINEA QUE COINCIDA CON LA DE ORIGEN
                                        $sqlMatFisSal    = "SELECT * FROM MATERIAL_FISICO_SALIDA WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowMovLinOrigen->ID_MOVIMIENTO_SALIDA_LINEA AND ID_MATERIAL_FISICO = $rowMatFisBultoGranel->ID_MATERIAL_FISICO";
                                        $resultMatFisSal = $bd->ExecSQL($sqlMatFisSal);
                                        $rowMatFisSal    = $bd->SigReg($resultMatFisSal);
                                        if ($rowMatFisSal != false):
                                            // DECREMENTAMOS LA CANTIDAD EN LA RELACION EN TABLA MATERIAL_FISICO_SALIDA ORIGEN
                                            $sqlUpdate = "UPDATE MATERIAL_FISICO_SALIDA SET
																		CANTIDAD = CANTIDAD - $rowMatFisTrans->CANTIDAD 
																		WHERE ID_MATERIAL_FISICO_SALIDA = $rowMatFisSal->ID_MATERIAL_FISICO_SALIDA";
                                            $bd->ExecSQL($sqlUpdate);
                                        else:
                                            exit("Error Grave");
                                        endif;
                                    endif;
                                    //NUEVO PARA MOVER BULTOS DEL BULTO A GRANEL*****************************************************************************************************************


                                    //ACTUALIZAMOS EL MATERIAL FISICO TRANSFERENCIA
                                    $sqlUpdate = "UPDATE MATERIAL_FISICO_TRANSFERENCIA SET
																ID_MOVIMIENTO_TRANSFERENCIA = $idMovimientoTransferencia 
																WHERE ID_MATERIAL_FISICO_TRANSFERENCIA = $rowMatFisTrans->ID_MATERIAL_FISICO_TRANSFERENCIA";
                                    $bd->ExecSQL($sqlUpdate);

                                    //ACTUALIZAMOS BULTO_GRANEL
                                    $clausulaWhere  = "ID_BULTO = $idBulto AND ID_MATERIAL = $rowMovLinOrigen->ID_MATERIAL";
                                    $rowBultoGranel = $bd->VerRegRest("BULTO_GRANEL", $clausulaWhere, "NO");

                                    $sqlUpdate = "UPDATE BULTO_GRANEL SET
																ID_MOVIMIENTO_SALIDA = $ultimoMovimientoGenerado 
																, ID_ORDEN_PREPARACION = $idOrdenDestino 
																WHERE ID_BULTO_GRANEL = $rowBultoGranel->ID_BULTO_GRANEL";
                                    $bd->ExecSQL($sqlUpdate);

                                    //ACTUALIZAMOS MATERIAL_FISICO_BULTO_GRANEL
                                    $sqlUpdate = "UPDATE MATERIAL_FISICO_BULTO_GRANEL SET
																ID_MOVIMIENTO_SALIDA = $ultimoMovimientoGenerado
																, ID_ORDEN_PREPARACION = $idOrdenDestino 
																WHERE ID_MATERIAL_FISICO_BULTO_GRANEL = $rowMatFisBultoGranel->ID_MATERIAL_FISICO_BULTO_GRANEL";
                                    $bd->ExecSQL($sqlUpdate);

                                endif;
                            endwhile;    //FIN MATERIAL FISICO BULTO GRANEL
                        endif;
                        //FIN PARA MATERIAL SERIABLE/LOTABLE

                        //BORRAMOS LAS TRANSFERENCIAS CON CANTIDAD CERO
                        $sqlDelete = "DELETE FROM MOVIMIENTO_TRANSFERENCIA WHERE CANTIDAD = 0";
                        $bd->ExecSQL($sqlDelete);

                        //ACTUALIZAMOS LA CANTIDAD PEDIDO, YA QUE SI VIENE EN VARIOS BULTOS NO ES CORRECTA Y DA FALLO DE INTEGRIDAD CUANDO NO ES CIERTO
                        $sqlMovSalLinAgrupado    = "SELECT ID_MATERIAL, SUM(CANTIDAD) AS CANTIDAD_TOTAL FROM MOVIMIENTO_SALIDA_LINEA WHERE ID_MOVIMIENTO_SALIDA = $ultimoMovimientoGenerado GROUP BY ID_MATERIAL";
                        $resultMovSalLinAgrupado = $bd->ExecSQL($sqlMovSalLinAgrupado);
                        while ($rowMovSalLinAgrupado = $bd->SigReg($resultMovSalLinAgrupado)):
                            $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
														CANTIDAD_PEDIDO = $rowMovSalLinAgrupado->CANTIDAD_TOTAL 
														WHERE ID_MATERIAL = $rowMovSalLinAgrupado->ID_MATERIAL AND ID_MOVIMIENTO_SALIDA = $ultimoMovimientoGenerado";
                            $bd->ExecSQL($sqlUpdate);
                        endwhile;

                    endif; //FIN BULTO CERRADO

                    /*else:
                        $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                                                    CANTIDAD = CANTIDAD + $cantidad
                                                    , CANTIDAD_PEDIDO = CANTIDAD_PEDIDO + $cantidad
                                                    WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowMovLinOrigenExiste->ID_MOVIMIENTO_SALIDA_LINEA";
                        $bd->ExecSQL($sqlUpdate);
                    endif;*/

                    //DECREMENTO LA CANTIDAD EN LA LINEA DEL MOVIMIENTO DE ORIGEN
                    //NUEVO PARA MOVER BULTOS DEL BULTO A GRANEL*****************************************************************************************************************
                    $rowOrdenOrigen = $bd->VerReg("ORDEN_PREPARACION", "ID_ORDEN_PREPARACION", $idOrdenOrigen, "No");
                    if (($rowOrdenDestino->ESTADO == 'Preparado') && ($rowOrdenOrigen->ESTADO == 'En Preparacion')):
                        $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                                                CANTIDAD = CANTIDAD - $cantidad 
                                                WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowMovLinOrigen->ID_MOVIMIENTO_SALIDA_LINEA";//, CANTIDAD_PEDIDO = CANTIDAD_PEDIDO - $cantidad
                        $bd->ExecSQL($sqlUpdate);
                    //NUEVO PARA MOVER BULTOS DEL BULTO A GRANEL*****************************************************************************************************************
                    else:
                        $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                                                    CANTIDAD = CANTIDAD - $cantidad 
                                                    , CANTIDAD_PEDIDO = CANTIDAD_PEDIDO - $rowBultoGranel->CANTIDAD 
                                                    WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowMovLinOrigen->ID_MOVIMIENTO_SALIDA_LINEA";
                        $bd->ExecSQL($sqlUpdate);
                    endif;
                endwhile;    //WHILE LINEA MOVIMIENTO

                //MODIFICO LA LINEA BULTO GRANEL IMPLICADA
                $sqlUpdate = "UPDATE BULTO_GRANEL SET
											ID_ORDEN_PREPARACION = $idOrdenDestino 
											, ID_MOVIMIENTO_SALIDA = $ultimoMovimientoGenerado 
											WHERE ID_BULTO_GRANEL = $rowBultoGranel->ID_BULTO_GRANEL";
                $bd->ExecSQL($sqlUpdate);

                //MODIFICO LA LINEA DEL MATERIAL FISICO BULTO GRANEL
                $sqlUpdate = "UPDATE MATERIAL_FISICO_BULTO_GRANEL
											SET ID_ORDEN_PREPARACION = $idOrdenDestino 
											WHERE ID_BULTO_GRANEL = $rowBultoGranel->ID_BULTO_GRANEL 
											AND ID_ORDEN_PREPARACION = $idOrdenOrigen 
											AND ID_BULTO = $idBulto";
                $bd->ExecSQL($sqlUpdate);
            endwhile; //WHILE BULTO GRANEL

            //BORRO LAS LINEAS DE BULTO QUE SE HAYAN QUEDADO CON CANTIDAD CERO
            $sqlLineasBulto    = "SELECT *
												 FROM BULTO_LINEA AS BL 
												 INNER JOIN BULTO AS B ON B.ID_BULTO = BL.ID_BULTO 
												 WHERE B.ID_ORDEN_PREPARACION = $idOrdenOrigen AND BL.CANTIDAD = 0";
            $resultLineasBulto = $bd->ExecSQL($sqlLineasBulto);
            while ($rowLineasBulto = $bd->SigReg($resultLineasBulto)):
                $sqlDelete = "DELETE FROM BULTO_LINEA WHERE ID_BULTO_LINEA = $rowLineasBulto->ID_BULTO_LINEA";
                $bd->ExecSQL($sqlDelete);
            endwhile;

            //BORRO EL BULTO QUE SE HAYA QUEDADO SIN LINEAS
            $sqlBulto    = "SELECT COUNT(DISTINCT ID_BULTO_LINEA) AS NUM
									 FROM BULTO AS B
									 INNER JOIN BULTO_LINEA AS BL ON B.ID_BULTO = BL.ID_BULTO
									 WHERE B.ID_BULTO = $idBulto";
            $resultBulto = $bd->ExecSQL($sqlBulto);
            $rowBulto    = $bd->SigReg($resultBulto);
            if ($rowBulto->NUM > 0):
                $sqlDelete = "DELETE FROM BULTO WHERE ID_BULTO = $idBulto";
                $bd->ExecSQL($sqlDelete);
            endif;

            //BORRO LAS LINEAS DE MOVIMIENTO DESTINO QUE SE HAYAN QUEDADO CON CANTIDAD CERO, ES POSIBLE SI LA CANTIDAD MAYOR A LA DEL BULTO
            $sqlLineasMovimiento    = "SELECT *
															FROM MOVIMIENTO_SALIDA AS MS 
															INNER JOIN MOVIMIENTO_SALIDA_LINEA AS MSL ON MS.ID_MOVIMIENTO_SALIDA = MSL.ID_MOVIMIENTO_SALIDA 
															WHERE ID_ORDEN_PREPARACION = $idOrdenDestino AND CANTIDAD = 0";
            $resultLineasMovimiento = $bd->ExecSQL($sqlLineasMovimiento);
            while ($rowLineasMovimiento = $bd->SigReg($resultLineasMovimiento)):
                $sqlDelete = "DELETE FROM MOVIMIENTO_SALIDA_LINEA WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowLineasMovimiento->ID_MOVIMIENTO_SALIDA_LINEA";
                $bd->ExecSQL($sqlDelete);
            endwhile;

            //BORRO LAS LINEAS DE MOVIMIENTO ORIGEN QUE SE HAYAN QUEDADO CON CANTIDAD CERO
            $sqlLineasMovimiento    = "SELECT *
															FROM MOVIMIENTO_SALIDA AS MS 
															INNER JOIN MOVIMIENTO_SALIDA_LINEA AS MSL ON MS.ID_MOVIMIENTO_SALIDA = MSL.ID_MOVIMIENTO_SALIDA 
															WHERE ID_ORDEN_PREPARACION = $idOrdenOrigen AND CANTIDAD = 0 AND ID_UBICACION <> 0";
            $resultLineasMovimiento = $bd->ExecSQL($sqlLineasMovimiento);
            while ($rowLineasMovimiento = $bd->SigReg($resultLineasMovimiento)):
                $sqlDelete = "DELETE FROM MOVIMIENTO_SALIDA_LINEA WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowLineasMovimiento->ID_MOVIMIENTO_SALIDA_LINEA";
                $bd->ExecSQL($sqlDelete);
            endwhile;

            //BORRO EL MOVIMIENTO ORIGEN QUE SE HAYA QUEDADO SIN LINEAS
            $sqlMovimiento    = "SELECT *
												FROM MOVIMIENTO_SALIDA
												WHERE ID_ORDEN_PREPARACION = $idOrdenOrigen";
            $resultMovimiento = $bd->ExecSQL($sqlMovimiento);
            while ($rowMovimiento = $bd->SigReg($resultMovimiento)):
                $sqlMovimientoLinea    = "SELECT COUNT(*) AS NUM FROM MOVIMIENTO_SALIDA_LINEA WHERE ID_MOVIMIENTO_SALIDA = $rowMovimiento->ID_MOVIMIENTO_SALIDA";
                $resultMovimientoLinea = $bd->ExecSQL($sqlMovimientoLinea);
                $rowMovimientoLinea    = $bd->SigReg($resultMovimientoLinea);
                if ($rowMovimientoLinea->NUM == 0):
                    $sqlDelete = "DELETE FROM MOVIMIENTO_SALIDA WHERE ID_MOVIMIENTO_SALIDA = $rowMovimiento->ID_MOVIMIENTO_SALIDA";
                    $bd->ExecSQL($sqlDelete);
                endif;
            endwhile;


            //NUEVO PARA MOVER BULTOS DEL BULTO A GRANEL*****************************************************************************************************************
            $rowOrdenOrigen = $bd->VerReg("ORDEN_PREPARACION", "ID_ORDEN_PREPARACION", $idOrdenOrigen, "No");
            if (($rowOrdenDestino->ESTADO == 'Preparado') && ($rowOrdenOrigen->ESTADO == 'En Preparacion')):
                //BORRO EL MATERIAL_FISICO_SALIDA QUE SE HAYA QUEDADO A CERO
                $sqlDelete = "DELETE FROM MATERIAL_FISICO_SALIDA WHERE CANTIDAD = 0";
                $bd->ExecSQL($sqlDelete);
            endif;
            //NUEVO PARA MOVER BULTOS DEL BULTO A GRANEL*****************************************************************************************************************


            //ACTUALIZO EL ID_ORDEN_PREPARACION DEL BULTO
            $sqlUpdate = "UPDATE BULTO SET
										ID_ORDEN_PREPARACION = $idOrdenDestino 
										, IMPRESO = 0 
										, POSICION = -1 
										WHERE ID_BULTO = $idBulto";
            $bd->ExecSQL($sqlUpdate);

        endif; //FIN BULTOS ESTANDAR O NO ESTANDAR

        //CAMBIAMOS LA ORDEN DE PREPARACION DE LAS ETIQUETAS PENDIENTES DE SER IMPRESAS
        $NotificaErrorPorEmail = "No";
        $sqlEtiqPdte           = "SELECT * FROM ETIQUETA_PDTE WHERE ID_BULTO = $idBulto AND ID_ORDEN_PREPARACION = $idOrdenOrigen";
        $resultEtiqPdte        = $bd->ExecSQL($sqlEtiqPdte);
        $rowEtiqPdte           = $bd->SigReg($resultEtiqPdte);
        if ($rowEtiqPdte != false):

            //COMENTADO PORQUE ENVIA EL EMAIL
            //$clausulaWhere = "ID_BULTO = $idBulto AND ID_ORDEN_PREPARACION = $idOrdenOrigen";
            //$rowEtiqPdte = $bd->VerRegRest("ETIQUETA_PDTE", $clausulaWhere, "No");
            //if ($rowEtiqPdte != false):
            $sqlUpdate = "UPDATE ETIQUETA_PDTE SET
										ID_ORDEN_PREPARACION = $idOrdenDestino 
										, ETIQUETA_IMPRESA = 0 
										WHERE ID_ETIQUETA_PDTE = $rowEtiqPdte->ID_ETIQUETA_PDTE";
            $bd->ExecSQL($sqlUpdate);
        endif;
        //FIN CAMBIAMOS LA ORDEN DE PREPARACION DE LAS ETIQUETAS PENDIENTES DE SER IMPRESAS


        //NUEVO PARA MOVER BULTOS DEL BULTO A GRANEL*****************************************************************************************************************
        $rowOrdenOrigen = $bd->VerReg("ORDEN_PREPARACION", "ID_ORDEN_PREPARACION", $idOrdenOrigen, "No");
        if (($rowOrdenDestino->ESTADO == 'Preparado') && ($rowOrdenOrigen->ESTADO == 'En Preparacion')):
            $rowAdministrador = $bd->VerReg("ADMINISTRADOR", "ID_ADMINISTRADOR", $administrador->ID_ADMINISTRADOR);
            $sqlInsert        = "INSERT INTO ETIQUETA_PDTE SET
										ID_ADMINISTRADOR = '$rowAdministrador->ID_ADMINISTRADOR' 
										, NOMBRE_ADMINISTRADOR = '$rowAdministrador->NOMBRE' 
										, ID_ORDEN_PREPARACION = $idOrdenOrigen 
										, ID_BULTO = $idBulto 
										, ETIQUETA_IMPRESA = 0";
            $bd->ExecSQL($sqlInsert);
        endif;
        //NUEVO PARA MOVER BULTOS DEL BULTO A GRANEL*****************************************************************************************************************


        $fechaActual = date("Y-m-d");
        $admin       = $administrador->ID_ADMINISTRADOR;
        //GRABO EN LA TABLA BULTO_MOVIDO EL CAMBIO DE ORDEN
        $sqlInsert = "INSERT INTO BULTO_MOVIDO SET
									ID_BULTO = $idBulto, 
									ID_ORDEN_ORIGEN = $idOrdenOrigen, 
									ID_ORDEN_DESTINO = $idOrdenDestino, 
									FECHA = '$fechaActual', 
									ID_ADMIN = $admin";
        $bd->ExecSQL($sqlInsert);
        //FIN GRABO EN LA TABLA BULTO_MOVIDO EL CAMBIO DE ORDEN


        //exit;

        //REORGANIZAR LAS REFERENCIAS DE LA ORDEN ORIGEN
        $this->ReorganizarPosicionesOrden($idOrdenOrigen);

        //REORGANIZAR LAS REFERENCIAS DE LA ORDEN DESTINO
        $this->ReorganizarPosicionesOrden($idOrdenDestino);

        if ($conTransaccion):
            $bd->commit_transaction(); //CONFIRMO LA TRANSACCION
        endif;
    }

    function ReorganizarPosicionesOrden($idOrden)
    {
        global $bd;

        $sqlBultos    = "SELECT * FROM BULTO WHERE ID_ORDEN_PREPARACION = $idOrden AND MATERIALES_SIN_ASIGNAR = 0 ORDER BY POSICION";
        $resultBultos = $bd->ExecSQL($sqlBultos);
        $i            = 1; //INICIADO A 1 PORQUE ES EL MINIMO DE POSICION
        while ($rowBulto = $bd->SigReg($resultBultos)):
            if ($rowBulto->POSICION == -1): //BULTO MOVIDO
                $num       = $bd->NumRegs($resultBultos);
                $sqlUpdate = "UPDATE BULTO SET POSICION = " . $num . " WHERE ID_BULTO = $rowBulto->ID_BULTO";
                $bd->ExecSQL($sqlUpdate);
                break;
            else: //BULTOS NO MOVIDOS
                if ($rowBulto->POSICION == $i):
                    //NO HACER NADA
                else:
                    $sqlUpdate = "UPDATE BULTO SET POSICION = " . $i . " WHERE ID_BULTO = $rowBulto->ID_BULTO";
                    $bd->ExecSQL($sqlUpdate);
                endif;
                $i++; //INCREMENTAMOS LA POSICION
            endif;
        endwhile;
    }

    function CalculaOrdenFinal($idBulto, $idOrden)
    {
        global $bd;

        $sqlOrden      = "SELECT * FROM BULTO_MOVIDO WHERE ID_BULTO = $idBulto ORDER BY ID_BULTO_MOVIDO";
        $resultOrden   = $bd->ExecSQL($sqlOrden);
        $numeroCambios = $bd->NumRegs($resultOrden);
        if ($numeroCambios > 0): //SE HA MOVIDO EL BULTO
            if ($numeroCambios == 1):
                $rowOrden = $bd->SigReg($resultOrden);

                return $rowOrden->ID_ORDEN_DESTINO;
            else:
                while ($rowBultoMovido = $bd->SigReg($resultOrden)):
                    if ($rowBultoMovido->ID_ORDEN_ORIGEN == $idOrden):
                        $idOrden = $rowBultoMovido->ID_ORDEN_DESTINO;
                    endif;
                endwhile;

                return $idOrden;
            endif;
        else:
            return 0;
        endif;
    }

    function CalcularOrdenInicial($idBulto)
    {
        global $bd;

        $ordenPrimera = false;

        $sqlBultoMovido    = "SELECT MIN(ID_BULTO_MOVIDO),ID_ORDEN_ORIGEN FROM BULTO_MOVIDO WHERE ID_BULTO=$idBulto";
        $resultBultoMOvido = $bd->ExecSQL($sqlBultoMovido);
        if ($resultBultoMovido != false):
            $rowBultoMovido = $bd->SigReg($resultBultoMovido);
            $ordenPrimera   = $rowBultoMovido->ORDEN_ORIGEN;
        endif;

        return $ordenPrimera;
    }
}

?>