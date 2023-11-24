<?php

# html
# Clase auxiliar contiene todas las funciones necesarias para
# la interaccion auxiliar
# Se incluira en las sesiones
# Octubre 2005 Ruben Alutiz Duarte

class html
{

    function __construct()
    {

    }

// MUESTRA PAGINA DE ERROR DEPENDIENDO DEL TIPO DE ERROR
    function PagError($TipoError)
    {
        global $Pagina_Error;
        global $msjeError;
        global $ventanaOrigen; // PARA PODER DIFERENCIAR ENTRE DISTINTOS TIPOS DE VENTANAS

        if (!isset($Pagina_Error)) $Pagina_Error = "error.php";

        include $Pagina_Error;
        exit;
    } // FIN PagError

// EXAMINA EL VALOR Y MUESTRA PAGINA DE ERROR SI COINCIDE
    function PagErrorCond($ValorComprobar, $ValorError, $TipoError, $EnviaMail = "No")
    {
        global $bd;
        global $Pagina_Error;
        global $administrador;
        global $auxiliar;

        if (!isset($Pagina_Error)) $Pagina_Error = "error.php";

        if ($EnviaMail == "Si"):
            $Asunto = $auxiliar->traduce("Error realizando acción", $administrador->ID_IDIOMA);
            $Cuerpo = $auxiliar->traduce("Descripción", $administrador->ID_IDIOMA) . ":\n$ValorComprobar = $ValorError";
            $bd->EnviarEmailErr($Asunto, $Cuerpo);
        endif;

        if ($ValorComprobar == $ValorError):
            include $Pagina_Error;
            exit;
        endif;
    } // Fin ver_error

    function PagErrorCondicionado($ValorComprobar, $Condicion, $ValorError, $TipoError, $EnviaMail = "No")
    {
        global $bd;
        global $Pagina_Error;
        global $CausaError;    // VIAJARA A LA PAGINA DE ERROR, POR EJ. UN Nº PEDIDO CONCRETO...
        global $administrador;
        global $auxiliar;

        // CONDICION SGTES VALORES, "==", "<=",">=","!="

        if (!isset($Pagina_Error)) $Pagina_Error = "error.php";

        $cumpleCondicion = "No";
        if ($Condicion == "=="):
            if ($ValorComprobar == $ValorError): $cumpleCondicion = "Si"; endif;
        elseif ($Condicion == "<="):
            if ($ValorComprobar <= $ValorError): $cumpleCondicion = "Si"; endif;
        elseif ($Condicion == "<"):
            if ($ValorComprobar < $ValorError): $cumpleCondicion = "Si"; endif;
        elseif ($Condicion == ">="):
            if ($ValorComprobar >= $ValorError): $cumpleCondicion = "Si"; endif;
        elseif ($Condicion == ">"):
            if ($ValorComprobar > $ValorError): $cumpleCondicion = "Si"; endif;
        elseif ($Condicion == "!="):
            if ($ValorComprobar != $ValorError): $cumpleCondicion = "Si"; endif;
        endif;

        if ($cumpleCondicion == "Si" && $EnviaMail == "Si"):
            $Asunto = $auxiliar->traduce("Error realizando acción", $administrador->ID_IDIOMA);
            $Cuerpo = $auxiliar->traduce("Descripción", $administrador->ID_IDIOMA) . ":\n$ValorComprobar = $ValorError";
            $bd->EnviarEmailErr($Asunto, $Cuerpo);
        endif;
        if ($cumpleCondicion == "Si"):
            include $Pagina_Error;
            exit;
        endif;

    } // Fin ver_error


    function SelectArrV($Nombre, $Elementos, $Valor, $Text = "Si")
    {
        // ["text"] DEBERA ESTAR RELLENADA SIEMPRE
        // Elementos[0]["text"]  CONTIENE EL TEXTO INICIAL
        // Elementos[0]["valor"] CONTIENE EL TEXTO INICIAL
        global $Estilo;
        global $Tamano;
        global $onChange;
        global $Multiple;
        global $disabled;
        global $jscript;

        if (isset($Tamano)) $textoTamano = " style='width: $Tamano'";
        if (isset($Estilo)) $textoEstilo = " class='$Estilo'";

        echo "<select name='$Nombre' $textoTamano $textoEstilo $disabled $onChange $Multiple $jscript>";
        for ($i = 0; $i < count( (array)$Elementos); $i++):
            if ($Text == "Si" && $i == 0):
                $value = "";
                $text  = $Elementos[$i]["text"];
            else:
                $value = $Elementos[$i]["valor"];
                $text  = $Elementos[$i]["text"];
            endif;

            if ($Valor == $value):
                echo "<option $textoEstilo value='" . $value . "' selected>" . $text . "</option>";
            else:
                echo "<option $textoEstilo value='" . $value . "'>" . $text . "</option>";
            endif;
        endfor;
        echo "</select>";

    } // Fin SelectArr

    function SelectArrHora($Nombre, $Valor, $mostrarHorasMedias = true)
    {
        global $ClassText;
        global $Tamano;
        global $onChange;
        global $Multiple;
        global $disabled;
        global $readonly;
        global $jscript;
        global $placeHolder;

        if (isset($Tamano)) $textoTamano = " style='width: $Tamano'";
        if (isset($ClassText)) $textoEstilo = " class='$ClassText'";
        $tamanoEntero = floatval(str_replace( "px", "",(string) $Tamano));
        $widthTextBox = "width:" . ($tamanoEntero - 22.75) . "px;";
        $margiLeft    = "margin-left:-" . ($tamanoEntero - 2) . "px;";

        $NombreSelect = $Nombre . "select";
        $jscript2     = $jscript;
        $jscript      = "onChange=\"jQuery('#$Nombre').val(jQuery('#$NombreSelect option:selected').html());jQuery('#$Nombre').focus();$jscript2 return false;\"";
        echo "<select id='$NombreSelect' name='$NombreSelect' class=\"editableBox $ClassText\" $textoTamano  $readonly $disabled $onChange $Multiple $jscript>";
        for ($i = 0; $i < 24; $i++):

            if ($i < 10):
                $hora      = "0$i:00";
                $horaMedia = "0$i:30";
            else:
                $hora      = "$i:00";
                $horaMedia = "$i:30";
            endif;

            echo "<option $textoEstilo value='" . $hora . "' selected>" . $hora . "</option>";

            if ($mostrarHorasMedias):
                echo "<option $textoEstilo value='" . $horaMedia . "' selected>" . $horaMedia . "</option>";
            endif;
        endfor;
        echo "</select>";
        if ($jscript2 != ""):
            $jscript2 = "onChange=\"$jscript2 return false;\"";
        endif;
        echo "<input class=\"timeTextBox $ClassText\"  style=\"vertical-align: top;$widthTextBox $margiLeft\" name=\"$Nombre\" $placeHolder $disabled $jscript2 $readonly id=\"$Nombre\" maxlength=\"5\" value=\"" . htmlentities( (string)$Valor) . "\"/>";

        // DEJO LA VARIABLE DE JS A VACÍO PARA QUE NO ENTRE EN CONFLICTO CON EL CÓDIGO DE OTROS CAMPOS
        $jscript = '';
    } // Fin SelectArr

    function SelectArr($Nombre, $Elementos, $Valor, $Text = "Si")
    {
        // ["text"] DEBERA ESTAR RELLENADA SIEMPRE
        // Elementos[0]["text"]  CONTIENE EL TEXTO INICIAL
        // Elementos[0]["valor"] CONTIENE EL TEXTO INICIAL
        global $Estilo;
        global $Tamano;
        global $Title;
        global $onChange;
        global $Multiple;
        global $disabled;
        global $jscript;
        global $NoSeleccionarTodas;
        global $NoMostrarVacio;
        global $obtenerTodosValores;
        global $SeleccionMultiple;
        global $ElementoVacio;
        global $administrador;
        global $auxiliar;

        //CUANDO SE PASA EL ID A TRAVES DE LA VARIABLE NOMBRE, ELIMINAR ID
        if (strpos( (string)$Nombre, "'") !== false):
            $Nombre = substr( (string) $Nombre, 0, strpos( (string)$Nombre, "'"));
        endif;
        if (isset($Title)) $Title = " title='$Title'";
        if (isset($Tamano)) $textoTamano = " style='width: $Tamano'";
        if (isset($Estilo)) $textoEstilo = " class='$Estilo'";
        $textoEstilo        = str_replace( "ObligatorioRellenar", "",(string) $textoEstilo);
        $seleccionarPrimero = false;

        // ASIGNAMOS NUEVO NOMBRE AL SELECT Y CREAMOS INPUTO PARA RECOGER LOS VALORES MULTIPLES
        $NombreMultiple = $Nombre . "select";
        echo "<select id ='" . ($Multiple != "No" ? $NombreMultiple : $Nombre) . "' name='" . ($Multiple != "No" ? $NombreMultiple : $Nombre) . "' $textoTamano  $Title $disabled  " . ($Multiple != "No" ? "multiple ='multiple'" : "") . " $textoEstilo>";

        //VALORES BUSCADOS
        $arrValor = explode(SEPARADOR_BUSQUEDA_MULTIPLE, (string)$Valor);

        //ELEMENTO PARA BUSQUEDAS VACIAS
//        if ($ElementoVacio == "Si"):
        if ((($SeleccionMultiple == "Si") || ($ElementoVacio == "Si")) && ($NoMostrarVacio != "Si")):
            $textoEstiloVacio = " class='$Estilo sombraFiltroDesplegable' ";
            if (in_array(ELEMENTO_BUSQUEDA_VACIO_VALUE, (array) $arrValor)):
                echo "<option  $textoEstiloVacio value='" . ELEMENTO_BUSQUEDA_VACIO_VALUE . "' selected >" . $auxiliar->traduce("Vacio", $administrador->ID_IDIOMA) . "</option>";
            else:
                $seleccionarPrimero = true;
                echo "<option  $textoEstiloVacio value='" . ELEMENTO_BUSQUEDA_VACIO_VALUE . "' >" . $auxiliar->traduce("Vacio", $administrador->ID_IDIOMA) . "</option>";
            endif;
        endif;

        //RECORRER Y CREAR ELEMENTOS DEL SELECT
        for ($i = 0; $i < count( (array)$Elementos); $i++):
            if ($Text == "Si" && $i == 0):
                $value = "";
                $text  = $Elementos[$i]["text"];
            else:
                $value = $Elementos[$i]["valor"];
                $text  = $Elementos[$i]["text"];
            endif;

            //CASOS PARA SELECCIONAR O NO ELEMENTOS DEL SELECT
            if (($value == "" || strtolower((string) $value) == "todos") && $SeleccionMultiple != "Si"):
                if ((in_array($value, (array) $arrValor)) && ($Valor != "") && (strtolower((string) $Valor) != "todos")):
                    $seleccionarPrimero = true;
                    echo "<option  value='' selected>" . $text . "</option>";
                elseif (($Valor == "") && (strtolower((string)$value) == "todos") || (strtolower((string)$value) == "todas")):
                    $seleccionarPrimero = true;
                    echo "<option $textoEstilo value='' selected>" . $text . "</option>";
                elseif (($Valor == "") && (strtolower((string)$value) == "todos" || strtolower((string)$value) == "todas")):
                    $seleccionarPrimero = true;
                    echo "<option $textoEstilo value='' selected>" . $text . "</option>";
                elseif (($Valor == "") && (!$seleccionarPrimero)):
                    $seleccionarPrimero = true;
                    echo "<option $textoEstilo value='$value' selected>" . $text . "</option>";
                else:
                    $seleccionarPrimero = true;
                    echo "<option $textoEstilo value='$value' >" . $text . "</option>";
                endif;
            elseif ($Valor == "" && $SeleccionMultiple != "Si" && !$seleccionarPrimero):
                $seleccionarPrimero = true;
                echo "<option $textoEstilo value='$value' selected>" . $text . "</option>";
                $Valor = $value;
                continue;
            endif;
            if ($value != "" && strtolower((string) $value) != "todos"):
                if (in_array($value, (array) $arrValor)):
                    $textInput          = $text;
                    $seleccionarPrimero = true;
                    echo "<option  $textoEstilo value='" . $value . "' selected>" . $text . "</option>";
                else:
                    echo "<option  $textoEstilo value='" . $value . "' >" . $text . "</option>";
                endif;
            endif;
        endfor;
        echo "</select>";

        /**
         * PARAMTROS PARA CREAR EL NUEVO OBJETO DE DESPLEGABLE
         */

        //TAMAÑO MINIMO CUANDO SE DESPIEGA (IGUAL QUE EL TAMAÑO DE LA CAJA
        $minWidth = " minWidth: " . str_replace( "px", "",(string) $Tamano) . ", ";

        //SI ES UN CAMPO DE SELECCION
        if ($obtenerTodosValores == "Si"):
            $placeHolder = "placeholder: '" . $auxiliar->traduce("Selecciona alguna", $administrador->ID_IDIOMA) . "', ";
        else://SI ES UN FILTRO
            $placeHolder = "placeholder: '" . $auxiliar->traduce("Todos", $administrador->ID_IDIOMA) . "', ";
        endif;

        $selectAllText = "selectAllText: '" . $auxiliar->traduce("Seleccionar/Deseleccionar Todos", $administrador->ID_IDIOMA) . "',";

        //DESHABILITAR OPCION SELECCIONAR TODAS
        //HABILITAMOS SELECCION multiple
        if ($SeleccionMultiple == "Si"):
            $single = "single: false, filter : true, ";
            $todas  = "selectAll: true, ";
            if ($obtenerTodosValores == "Si")://MARCANDO ESTA VARIABLE, NOS DEVUELVE TODOS LOS VALORES AL SELECCIONAR TODO
                $todas .= "obtenerTodos: true, ";
            endif;
        else:
            $single = "single: true, allSelected: false, filter : true,  ";
            $todas  = "selectAll: false, ";
        endif;

        //OBLIGATORIO RELLENAR
        if (strpos(strtolower((string)$Estilo), "obligatoriorellenar") !== false):
            $obligatorioRellenar = "obligatorioRellenar:true,";
        else:
            $obligatorioRellenar = "obligatorioRellenar:false,";
        endif;

        //ARREGLOS PARA CUANDO, A LA ANTERIOR VERSION, SE LE PASABA CODIGO JS
        //FUNCIONAL EN ESTA VERSIÓN
        $arrOnchange = array("onchange=", "onChange=", "onChange =", "onclick=", "onClick=", "onClick =");
        $arrComillas = array("'");
        $jscript     = str_replace( $arrOnchange, ";",(string) $jscript);
        $jscript     = str_replace( $arrComillas, "",(string) $jscript);
        $onChange    = str_replace( $arrOnchange, ";", (string)$onChange);
        $onChange    = str_replace( $arrComillas, "",(string) $onChange);

        $jscript  = str_replace( "this.value", "seleccionadas",(string) $jscript);
        $onChange = str_replace( "this.value", "seleccionadas",(string) $onChange);
        $jscript  = str_replace( "this", "seleccionadasThis",(string) $jscript);
        $onChange = str_replace( "this", "seleccionadasThis",(string) $onChange);
        if (strpos( (string)$jscript, "id=") !== false || strpos( (string)$jscript, "id =") !== false):
            $jscript = str_replace( "id=", ";id=",(string) $jscript);
            $jscript = str_replace( "id =", ";id=", (string)$jscript);
        endif;
        if (strpos( (string)$onChange, "id=") !== false || strpos( (string)$onChange, "id =") !== false):
            $onChange = str_replace( "id=", ";id=", (string)$onChange);
            $onChange = str_replace( "id =", ";id=",(string) $onChange);
        endif;
        if (strpos( (string)$jscript, "=Si") !== false):
            $jscript = str_replace( "Si", "'Si'", (string)$jscript);
        endif;
        if (strpos( (string)$onChange, "=Si") !== false):
            $onChange = str_replace( "Si", "'Si'",(string) $onChange);
        endif;

        //PINTAMOS EL OBJETO
        echo "<script type= \"text/javascript\">
                    jQuery('#$NombreMultiple').multipleSelect(
                        {
                            $minWidth
                            $placeHolder
                            $selectAllText
                            $todas
                            $single
                            $obligatorioRellenar
                            onClick: function(){
                                var seleccionadas = jQuery('#$NombreMultiple').multipleSelect('getSelects').toString();
                                //SI SE PASA THIS(CREAMOS OBJETO HTML)
                                var option = '<option value=\"'+seleccionadas+'\" selected> '+seleccionadas+'</option>';
                                var objHTMlTemp = document.createElement('div');
                                objHTMlTemp.innerHTML = option;
                                var seleccionadasThis =objHTMlTemp.firstChild;

                                seleccionadas = seleccionadas.replace(/,/g,'|');
                                jQuery('#$Nombre').val(seleccionadas);
                                $jscript
                                $onChange
                                return false;

                            },
                            onBlur: function(){
                                var seleccionadas = jQuery('#$NombreMultiple').multipleSelect('getSelects').toString();
                                //SI SE PASA THIS(CREAMOS OBJETO HTML)
                                var option = '<option value=\"'+seleccionadas+'\" selected> '+seleccionadas+'</option>';
                                var objHTMlTemp = document.createElement('div');
                                objHTMlTemp.innerHTML = option;
                                var seleccionadasThis =objHTMlTemp.firstChild;

                                seleccionadas = seleccionadas.replace(/,/g,'|');
                                jQuery('#$Nombre').val(seleccionadas);
                                return false;

                            },
                            onClose: function(){
                                var seleccionadas = jQuery('#$NombreMultiple').multipleSelect('getSelects').toString();
                                //SI SE PASA THIS(CREAMOS OBJETO HTML)
                                var option = '<option value=\"'+seleccionadas+'\" selected> '+seleccionadas+'</option>';
                                var objHTMlTemp = document.createElement('div');
                                objHTMlTemp.innerHTML = option;
                                var seleccionadasThis =objHTMlTemp.firstChild;

                                seleccionadas = seleccionadas.replace(/,/g,'|');
                                jQuery('#$Nombre').val(seleccionadas);
                                return false;
                            }

                        });

                </script > ";
        //PINTAMOS INPUT HIDDEN PARA RECOGER LOS VALORES SELECCIONADOS
        echo "<INPUT $textoEstilo TYPE = \"HIDDEN\" ID = \"$Nombre\" NAME=\"$Nombre\" VALUE=\"$Valor\" $disabled>";

    } // Fin SelectArr


    function SelectBD($Nombre, $result, $CampoClave, $CampoLabel, $Valor)
    {
        global $Estilo;
        global $Tamano;
        global $onChange;
        global $bd;
        global $jscript;
        global $TextoInicial;
        global $Valor1;

        if (isset($Tamano)) $textoTamano = " style='width: $Tamano'";
        if (isset($Estilo)) $textoEstilo = " class='$Estilo'";

        echo "<select name='$Nombre' $textoTamano $textoEstilo $onChange $jscript>";
        if (isset($TextoInicial)) echo "<option value=''>$TextoInicial</option>";
        if (isset($Valor1)):
            if ($Valor == -9) $Selec = " selected ";
            echo "<option value='-9' $Selec>$Valor1</option>";
        endif;
        while ($fila = mysqli_fetch_assoc($result)):
            $Selec = "";
            if ($Valor == $fila[$CampoClave]):
                $Selec = " selected ";
            endif;
            echo "<option value='$fila[$CampoClave]' $Selec>" . $fila[$CampoLabel] . "</option>";
        endwhile;
        echo "</select>";

    } // Fin SelectBD

    function SelectBDGroup($Nombre, $result, $CampoClave, $CampoLabel, $Valor)
    {
        global $Estilo;
        global $Tamano;
        global $onChange;
        global $bd;
        global $TextoInicial;
        global $disabled;

        if (isset($Tamano)) $textoTamano = " style='width: $Tamano'";
        if (isset($Estilo)) $textoEstilo = " class='$Estilo'";

        echo "<select $disabled name='$Nombre' $textoTamano $textoEstilo $onChange>";
        if (isset($TextoInicial)):
            echo "<option value=''>$TextoInicial</option>";
        endif;
        while ($fila = mysqli_fetch_assoc($result)):
            $Selec = "";
            if ($Valor == $fila[$CampoClave]):
                $Selec = " selected ";
            endif;
            if (substr( (string) $fila[$CampoLabel], 0, 1) == "#"):
                echo "<OPTGROUP LABEL=\"" . substr( (string) $fila[$CampoLabel], 1) . "\">";
            else:
                echo "<option value='$fila[$CampoClave]' $Selec>" . $fila[$CampoLabel] . "</option>";
            endif;
        endwhile;
        echo "</select>";

    } // Fin SelectBD

    function SelectBDArr($Nombre, $arr, $Valor)
    {
        global $Estilo;
        global $Tamano;
        global $onChange;
        global $bd;
        global $TextoInicial;
        global $disabled;

        if (isset($Tamano)) $textoTamano = " style='width: $Tamano'";
        if (isset($Estilo)) $textoEstilo = " class='$Estilo'";

        //echo "<select $disabled name='$Nombre' $textoTamano $textoEstilo $onChange readonly>";
        echo "<select $disabled name='$Nombre' $textoTamano $textoEstilo $onChange>";
        if (isset($TextoInicial)):
            echo "<option value=''>$TextoInicial</option>";
        endif;
        foreach ($arr as $value):
            $Selec = "";
            if ($Valor == $value):
                $Selec = " selected ";
            endif;
            echo "<option value='$value' $Selec>" . $value . "</option>";

        endforeach;
        echo "</select>";

    } // Fin SelectBD

    function SelectBDArrEt($Nombre, $arr, $Valor, $arr2)
    {
        global $Estilo;
        global $Tamano;
        global $onChange;
        global $bd;
        global $TextoInicial;
        global $disabled;
        global $jscript;

        if (isset($Tamano)) $textoTamano = " style='width: $Tamano'";
        if (isset($Estilo)) $textoEstilo = " class='$Estilo'";

        echo "<select name='$Nombre' $textoTamano $textoEstilo $onChange $disabled $jscript>";
        if (isset($TextoInicial)):
            echo "<option value=''>$TextoInicial</option>";
        endif;
        $conteo = count( (array)$arr);
        for ($i = 0; $i < $conteo; $i++):
            $Selec = "";
            if ($Valor == $arr2[$i]):
                $Selec = " selected ";
            endif;
            echo "<option value='$arr2[$i]' $Selec>" . $arr[$i] . "</option>";

        endfor;
        echo "</select>";

    } // Fin SelectBD

    function SelectArrValorTodosDiferenciado($Nombre, $Elementos, $Valor, $Text = "Si", $ValorTodos = "", $ClaseColorValorTodos = "textorojo", $ClaseColorRestoValores = "copyright")
    {
        // ["text"] DEBERA ESTAR RELLENADA SIEMPRE
        // Elementos[0]["text"]  CONTIENE EL TEXTO INICIAL
        // Elementos[0]["valor"] CONTIENE EL TEXTO INICIAL
        global $Estilo;
        global $Tamano;
        global $onChange;
        global $Multiple;
        global $disabled;
        global $jscript;

        if (isset($Tamano)) $textoTamano = " style='width: $Tamano'";
        if (isset($Estilo)) $textoEstilo = " class='$Estilo" . ($ValorTodos == $Valor ? " $ClaseColorValorTodos" : '') . "'";

        echo "<select name='$Nombre' id='$Nombre' $textoTamano $textoEstilo $disabled $onChange $Multiple $jscript>";
        for ($i = 0; $i < count( (array)$Elementos); $i++):
            if ($Text == "Si" && $i == 0):
                $value = "";
                $text  = $Elementos[$i]["text"];
            else:
                $value = $Elementos[$i]["valor"];
                $text  = $Elementos[$i]["text"];
            endif;

            //PINTO EL VALOR DEL DESPLEGABLE
            echo "<option " . ($ValorTodos == $value ? "class='" . $ClaseColorValorTodos . "'" : "class='" . $ClaseColorRestoValores . "'") . " value='" . $value . "' " . ($Valor == $value ? 'selected' : '') . ">" . $text . "</option>";

        endfor;
        echo "</select>";

    } // Fin SelectArrValorTodosDiferenciado


    function SelectBDespecial($Nombre, $result, $CampoClave, $CampoLabel, $CampoLabel2, $Valor)
    {
        global $Estilo;
        global $Tamano;
        global $onChange;
        global $bd;
        global $TextoInicial;

        if (isset($Tamano)) $textoTamano = " style='width: $Tamano'";
        if (isset($Estilo)) $textoEstilo = " class='$Estilo'";

        echo "<select name='$Nombre' $textoTamano $textoEstilo $onChange readonly>";
        if (isset($TextoInicial)):
            echo "<option value=''>$TextoInicial</option>";
        endif;
        while ($fila = mysqli_fetch_assoc($result)):
            $Selec = "";
            if ($Valor == $fila[$CampoClave]):
                $Selec = " selected ";
            endif;
            $descrip = strtolower((string)$fila[$CampoLabel2]);
            echo "<option value='$fila[$CampoClave]' $Selec>" . $fila[$CampoLabel] . " - " . ucfirst((string)$descrip) . "</option>";
        endwhile;
        echo "</select>";

    } // Fin SelectBD

    function SelectBD3campos($Nombre, $result, $CampoClave, $CampoClave2, $CampoLabel, $CampoLabel2, $CampoLabel3, $Valor, $Valor2)
    {
        global $Estilo;
        global $Tamano;
        global $onChange;
        global $bd;
        global $TextoInicial;

        if (isset($Tamano)) $textoTamano = " style='width: $Tamano'";
        if (isset($Estilo)) $textoEstilo = " class='$Estilo'";

        echo "<select name='$Nombre' $textoTamano $textoEstilo $onChange readonly>";
        if (isset($TextoInicial)):
            echo "<option value=''>$TextoInicial</option>";
        endif;
        while ($fila = mysqli_fetch_assoc($result)):
            $Selec = "";
            if ($Valor == $fila[$CampoClave] && $Valor2 == $fila[$CampoClave2]):
                $Selec = " selected ";
            endif;
            $nivelRev = strtolower((string)$fila[$CampoLabel2]);
            $descrip  = strtolower((string)$fila[$CampoLabel3]);
            echo "<option value='$fila[$CampoClave]#$fila[$CampoClave2]' $Selec>" . $fila[$CampoLabel] . "-$nivelRev - " . ucfirst((string)$descrip) . "</option>";
        endwhile;
        echo "</select>";

    } // Fin SelectBD


    function TextBox($Nombre, $Valor)
    {
        global $TamanoText;
        global $ClassText;
        global $MaxLength;
        global $readonly;
        global $p4ssw0d;
        global $title;
        global $jscript;
        global $idTextBox;
        global $auxiliar;
        global $administrador;

        $tipoText = "text";
        if (isset($TamanoText)) $textoTamano = " style='width: $TamanoText'";
        if (isset($ClassText)):
            $textoClass = " class='$ClassText' ";
        endif;
        if ($Valor == "Seleccion Multiple" || $Valor == $auxiliar->traduce("Seleccion Multiple", $administrador->ID_IDIOMA)):
            $textoClass = "class='$ClassText textoazulElectrico'";
        endif;
        if (isset($MaxLength)) $textoMax = " maxlength=$MaxLength ";
        if (isset($title)) $textoTitle = " title=\"$title\" ";
        if (isset($idTextBox)) $textoId = " id=\"$idTextBox\" ";
        if ($p4ssw0d != "") $tipoText = "password";

        echo "<input type='$tipoText' name='$Nombre' $textoId $textoMax $textoTamano $textoClass value=\"" . htmlentities( (string)$Valor) . "\" $readonly $textoTitle $jscript>";

    }// FIN TextBox


    function TextArea($Nombre, $Valor)
    {
        global $TamanoText;
        global $ClassText;
        global $MaxLength;
        global $Filas;
        global $disabled;
        global $tabindex;
        global $idTextArea;

        if (isset($TamanoText)) $textoTamano = " style='width: $TamanoText'";
        if (isset($ClassText)) $textoClass = " class='$ClassText' ";
        if (isset($Filas)) $textoFilas = " rows=$Filas ";
        if (isset($MaxLength)) $textoMax = " maxlength=$MaxLength ";

        echo "<textarea name='$Nombre' $textoMax $textoTamano $textoClass $textoFilas $idTextArea $disabled $tabindex>";
        echo $Valor;
        echo "</textarea>";

        unset($GLOBALS["disabled"]);

    }// FIN TextArea


    function ListaBD($Nombre, $result, $CampoClave, $CampoLabel)
    {
        global $bd;
        global $Estilo;
        global $Tamano;
        global $Alto;

        if (isset($Tamano)) $textoTamano = " style='width: $Tamano'";
        if (isset($Estilo)) $textoEstilo = " class='$Estilo'";
        if (isset($Alto)) $textoAlto = " size='$Alto' ";

        echo "<select name='$Nombre' $textoTamano $textoEstilo multiple $textoAlto>";
        while ($fila = mysqli_fetch_assoc($result)):
            echo "<option value='$fila[$CampoClave]'>" . $fila[$CampoLabel] . "</option>";
        endwhile;
        echo "</select>";

    } // Fin ListaBD

    function Option($Nombre, $Tipo, $Valor, $Checked)
    {
        global $jscript;
        global $disabled;
        global $Estilo;

        if ($Valor == $Checked) $Chequeado = "Checked";
        if (isset($Estilo)) $textoEstilo = " class='$Estilo'";

        if ($Tipo == "Option"):
            echo "<input type='radio' name=$Nombre value=\"$Valor\" $Chequeado $disabled $jscript $textoEstilo>";
        endif;
        if ($Tipo == "Check"):
            echo "<input type='checkbox' name=$Nombre value=\"$Valor\" $Chequeado $disabled $jscript $textoEstilo>";
        endif;
    }// FIN DE Option

    function CopiarAdjunto($Adjunto, $RutayFich)
    {
        if ($Adjunto != 'none'):
            // COPIO EL ADJUNTO
            //@system("cp -f \"$Adjunto\" \"$RutayFich\"",$result);
            mkdir(dirname($RutayFich), 0777, true);

            $result = copy($Adjunto, $RutayFich);

            if ($result === false): // ERROR
                return "Error";
            endif;
        endif;

        return "Correcto";

    } // Fin CopiarAdjunto

    function BorrarAdjunto($RutayFich)
    {
        @system("rm \"$RutayFich\"");
        if ($result != 0): // ERROR
            return "Error";
        endif;

        return "Correcto";

    } // Fin BorrarAdjunto

//
    function TextBoxEstilos($Nombre, $Valor)
    {
        global $TamanoText;
        global $ClassText;
        global $MaxLength;
        global $readonly;
        global $p4ssw0d;
        global $title;
        global $jscript;
        global $idTextBox;
        global $estilos;
        global $vAlign;

        $tipoText = "text";
        if (isset($TamanoText)):
            $estilos     = true;
            $textoTamano = " width:$TamanoText;";
        endif;
        if (isset($ClassText)) $textoClass = " class='$ClassText' ";
        if (isset($MaxLength)) $textoMax = " maxlength=$MaxLength ";
        if (isset($title)) $textoTitle = " title=\"$title\" ";
        if (isset($idTextBox)) $textoId = " id=\"$idTextBox\" ";
        if ($p4ssw0d != "") $tipoText = "password";
        if (isset($vAlign)):
            $estilos            = true;
            $alineacionVertical = " vertical-align:$vAlign;";
        endif;
        if ($estilos == true) $estilo = " style='$textoTamano $alineacionVertical'";

        echo "<input type='$tipoText' name='$Nombre' $textoId $textoMax $estilo $textoClass value=\"" . htmlentities( (string)$Valor) . "\" $readonly $textoTitle $jscript>";

    }// FIN TextBox

//FUNCION PARA VER HISTORIAL DE UN OBJETO
    function VerHistorial($tipoObjeto, $idObjeto, $descripcion = "", $nombreTabla = "", $widthImagen = 30, $heightImagen = 30)
    {
        global $administrador;
        global $auxiliar;
        global $pathRaiz;
        global $jscript;

        echo "<a href='" . $pathRaiz . "administracion/accesos/index.php?selObjeto=" . $tipoObjeto . "&txIdObjeto=" . $idObjeto . "&txDescripcion=" . $descripcion . "&nombreTabla=" . $nombreTabla . "&Buscar=Si&txFechaInicio=' target='_blank'><img height=$heightImagen width=$widthImagen src='" . $pathRaiz . "imagenes/botones/history.png' title='" . $auxiliar->traduce("Ver historial documento", $administrador->ID_IDIOMA) . "' $jscript></a>";
    }//FIN FUNCION PARA VER HISTORIAL DE UN OBJETO

    //FUNCION PARA VER CORREOS
    function VerCorreos($tipoObjeto, $idObjeto)
    {
        global $administrador;
        global $auxiliar;
        global $pathRaiz;
        global $jscript;
        echo "<a href='" . $pathRaiz . "ficha_correos_electronicos.php?idObjeto=$idObjeto&tipoObjeto=$tipoObjeto'
                   class='fancyboxCorreos'
                   title='" . $auxiliar->traduce("Ver Emails", $administrador->ID_IDIOMA) . "'>
                        <img style='margin-right:3px;'
                            src='" . $pathRaiz . "imagenes/email_verde.png'
                            width='30'
                            height='30'/>
                </a>";


    }//FIN FUNCION PARA VER CORREOS


    //FUNCION PINTA IMAGEN SEGUN EXTENSION FICHERO
    function imgExtension($fichero)
    {
        global $pathRaiz;
        $arrAuxiliar = explode(".", (string)$fichero);
        $extension = end($arrAuxiliar);

        if (strtoupper( (string)$extension) == "PDF"):
            echo "<img src='" . $pathRaiz . "imagenes/pdf.png'  width='15' height='15' border='0' align='absbottom'>";
        elseif (strtoupper( (string)$extension) == "DOC" || strtoupper( (string)$extension) == "DOCX"):
            echo "<img src='" . $pathRaiz . "imagenes/word.gif'  width='15' height='15' border='0' align='absbottom'>";
        elseif (strtoupper( (string)$extension) == "MSG" || strtoupper( (string)$extension) == "TXT"):
            echo "<img src='" . $pathRaiz . "imagenes/text.gif'  width='15' height='15' border='0' align='absbottom'>";

        endif;

    }//FUNCION PINTA IMAGEN SEGUN EXTENSION FICHERO


    //FUNCION PARA VER HISTORIAL DE UN OBJETO
    function VerHistorialTipoObjetoFecha($tipoObjeto, $txFechaInicio, $nombreTabla = "")
    {
        global $administrador;
        global $auxiliar;
        global $pathRaiz;
        global $jscript;

        echo "<a href='" . $pathRaiz . "administracion/accesos/index.php?selObjeto=" . $tipoObjeto . "&txFechaInicio=" . $txFechaInicio . "&nombreTabla=" . $nombreTabla . "&Buscar=Si' target='_blank'><img height=30 width=30 src='" . $pathRaiz . "imagenes/botones/history.png' title='" . $auxiliar->traduce("Ver historial documento", $administrador->ID_IDIOMA) . "' $jscript></a>";
    }//FIN FUNCION PARA VER HISTORIAL DE UN OBJETO

}// Fin de la Clase

?>
