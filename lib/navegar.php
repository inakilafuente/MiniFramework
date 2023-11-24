<?php
# navegar
# Clase navegar contiene todas las funciones necesarias para
# la interaccion navegar con un NECESARIO FORMULARIO en la pagina
# Se incluira en las sesiones
# Octubre 2005 Ruben Alutiz Duarte

class navegar
{
    var $limite;
    var $mostradas;
    var $numerofilas;
    var $maxfilas;


    var $maxfilasAlmacenCrearTransferencias;

    // MAX FILAS GENERAL
    var $MAX_FILAS_GENERAL;
    // EXPORTACION EXCEL
    var $copiaExport;
    //	ADMINISTRADOR >> ARTICULOS >> Familias
    var $sqlAdminArticFa;
    var $numerofilasAdminArticFa;
    var $maxfilasAdminArticFa;
    // MAESTRO >> ESTRUCTURA
    var $sqlAdminMaestroEstructuraOrganizativa;
    var $numerofilasMaestroEstructuraOrganizativa;
    var $maxfilasMaestroEstructuraOrganizativa;
    //	MAESTRO >> SOCIEDAD
    var $sqlAdminMaestroSociedad;
    var $numerofilasMaestroSociedad;
    var $maxfilasMaestroSociedad;
    //	MAESTRO >> CENTRO
    var $sqlAdminMaestroCentro;
    var $numerofilasMaestroCentro;
    var $maxfilasMaestroCentro;
    //	MAESTRO >> CENTRO FÍSICO
    var $sqlAdminMaestroCentroFisico;
    var $numerofilasMaestroCentroFisico;
    var $maxfilasMaestroCentroFisico;
    //	MAESTRO >> CENTRO FÍSICO CATEGORIA
    var $sqlAdminMaestroCentroFisicoCategoria;
    var $numerofilasMaestroCentroFisicoCategoria;
    var $maxfilasMaestroCentroFisicoCategoria;
    //	MAESTRO >> ALMACÉN
    var $sqlAdminMaestroAlmacen;
    var $numerofilasMaestroAlmacen;
    var $maxfilasMaestroAlmacen;
    //	MAESTRO >> INSTALACION
    var $sqlAdminMaestroInstalacion;
    var $numerofilasMaestroInstalacion;
    var $maxfilasMaestroInstalacion;
    //	MAESTRO >> LINEA CONTROL
    var $sqlAdminMaestroLineaControl;
    var $numerofilasMaestroLineaControl;
    var $maxfilasMaestroLineaControl;
    //	MAESTRO >> MAQUINA
    var $sqlAdminMaestroMaquina;
    var $numerofilasMaestroMaquina;
    var $maxfilasMaestroMaquina;
    //	MAESTRO >> CLIENTE
    var $sqlAdminMaestroCliente;
    var $numerofilasMaestroCliente;
    var $maxfilasMaestroCliente;
    //	MAESTRO >> PROVEEDOR
    var $sqlAdminMaestroProv;
    var $numerofilasMaestroProv;
    var $maxfilasMaestroProv;
    //	MAESTRO >> MATERIALES
    var $sqlAdminMaestroMaterial;
    var $numerofilasMaestroMaterial;
    var $maxfilasMaestroMaterial;
    //	MATERIALES >> FAMILIA MATERIAL
    var $sqlAdminFamiliaMaterial;
    var $numerofilasFamiliaMaterial;
    var $maxfilasFamiliaMaterial;
    //	MATERIALES >> SOLICITUDES COINCICENCIAS
    var $sqlAdminMaestroMaterialSolicitudCoincidencias;
    var $numerofilasMaestroMaterialSolicitudCoincidencias;
    var $maxfilasMaestroMaterialSolicitudCoincidencias;
    //	MAESTRO >> MATERIALES ALMACEN
    var $sqlAdminMaestroMaterialAlmacen;
    var $numerofilasMaestroMaterialAlmacen;
    var $maxfilasMaestroMaterialAlmacen;
    //	MAESTRO >> MATERIALES CENTRO
    var $sqlAdminMaestroMaterialCentro;
    var $numerofilasMaestroMaterialCentro;
    var $maxfilasMaestroMaterialCentro;

    //	MAESTRO >> UBICACIONES CENTRO FISICO
    var $sqlAdminMaestroUbicacionesCentroFisico;

    var $numerofilasMaestroUbicacionesCentroFisico;

    var $maxfilasMaestroUbicacionesCentroFisico;

    //	MAESTRO >> MATERIALES CENTRO FISICO
    var $sqlAdminMaestroMaterialCentroFisico;

    var $numerofilasMaestroMaterialCentroFisico;

    var $maxfilasMaestroMaterialCentroFisico;
    //	MAESTRO >> FAMILIAS REPRO
    var $sqlAdminMaestroFamiliaRepro;
    var $numerofilasMaestroFamiliaRepro;
    var $maxfilasMaestroFamiliaRepro;
    //	MAESTRO >> FAMILIAS MATERIAL
    var $sqlAdminMaestroFamiliaMaterial;
    var $numerofilasMaestroFamiliaMaterial;
    var $maxfilasMaestroFamiliaMaterial;
    //	MAESTRO >> FAMILIAS MATERIAL ASIGNAR
    var $sqlAdminMaestroFamiliaMaterialAsignar;
    var $numerofilasMaestroFamiliaMaterialAsignar;
    var $maxfilasMaestroFamiliaMaterialAsignar;
    //	MAESTRO >> FAMILIAS MATERIAL IC
    var $sqlAdminMaestroFamiliaMaterialIC;
    var $numerofilasMaestroFamiliaMaterialIC;
    var $maxfilasMaestroFamiliaMaterialIC;
    //	MAESTRO >> CODIGOS HS
    var $sqlAdminMaestroCodigoHS;
    var $numerofilasMaestroCodigoHS;
    var $maxfilasMaestroCodigoHS;
    //	MAESTRO >> CATEGORIAS IC
    var $sqlAdminMaestroCategoriaIC;
    var $numerofilasMaestroCategoriaIC;
    var $maxfilasMaestroCategoriaIC;
    //	MAESTRO >> TIPOS DE BLOQUEO
    var $sqlAdminMaestroTiposBloqueo;
    var $numerofilasMaestroTiposBloqueo;
    var $maxfilasMaestroTiposBloqueo;
    //	MAESTRO >> MATERIALES SUSTITUTIVOS
    var $sqlAdminMaestroMaterialesSustitutivos;
    var $numerofilasMaestroMaterialesSustitutivos;
    var $maxfilasMaestroMaterialesSustitutivos;
    //	MAESTRO >> DIRECCIONES ENTREGA PROVEEDORES
    var $sqlAdminMaestroDirsEntrProveedores;
    var $numerofilasMaestroDirsEntrProveedores;
    var $maxfilasMaestroDirsEntrProveedores;
    //	MAESTRO >> UNIDADES
    var $sqlAdminMaestroUnidades;
    var $numerofilasMaestroUnidades;
    var $maxfilasMaestroUnidades;
    //	MAESTRO >> REFERENCIAS CRUZADAS
    var $sqlAdminMaestroReferenciasCruzadas;
    var $numerofilasMaestroReferenciasCruzadas;
    var $maxfilasMaestroReferenciasCruzadas;
    //	MAESTRO >> UBICACIONES
    var $sqlAdminMaestroUbicaciones;
    var $numerofilasMaestroUbicaciones;
    var $maxfilasMaestroUbicaciones;
    //	MAESTRO >> PLANIFICADORES
    var $sqlAdminMaestroPlanificador;
    var $numerofilasMaestroPlanificador;
    var $maxfilasMaestroPlanificador;
    //	MAESTRO >> VEHICULOS
    var $sqlAdminMaestroVehiculos;
    var $numerofilasMaestroVehiculos;
    var $maxfilasMaestroVehiculos;
    //	MAESTRO >> VEHICULOS STOCK COMPARTIDO
    var $sqlAdminMaestroVehiculosStockCompartido;
    var $numerofilasMaestroVehiculosStockCompartido;
    var $maxfilasMaestroVehiculosStockCompartido;
    //	MAESTRO >> VEHICULOS TRANSPORTE
    var $sqlAdminMaestroVehiculosTransporte;
    var $numerofilasMaestroVehiculosTransporte;
    var $maxfilasMaestroVehiculosTransporte;
    //	MAESTRO >> ZONAS
    var $sqlAdminMaestroZonas;
    var $numerofilasMaestroZonas;
    var $maxfilasMaestroZonas;
    //	MAESTRO >> SUBZONAS
    var $sqlAdminMaestroSubzonas;
    var $numerofilasMaestroSubzonas;
    var $maxfilasMaestroSubzonas;
    //	MAESTRO >> RUTAS
    var $sqlAdminMaestroRutas;
    var $numerofilasMaestroRutas;
    var $maxfilasMaestroRutas;
    //	MAESTRO >> SUBRUTAS
    var $sqlAdminMaestroSubrutas;
    var $numerofilasMaestroSubrutas;
    var $maxfilasMaestroSubrutas;
    //	MAESTRO >> CATEGORIA UBICACION
    var $sqlAdminMaestroCategoriaUbicacion;
    var $numerofilasMaestroCategoriaUbicacion;
    var $maxfilasMaestroCategoriaUbicacion;
    //	MAESTRO >> CATEGORIA ALMACEN
    var $sqlAdminMaestroCategoriaAlmacen;
    var $numerofilasMaestroCategoriaAlmacen;
    var $maxfilasMaestroCategoriaAlmacen;
    //	MAESTRO >> CATEGORIA FACTURACION GRAN COMPONENTE
    var $sqlAdminMaestroCategoriaFacturacionGranComponente;
    var $numerofilasMaestroCategoriaFacturacionGranComponente;
    var $maxfilasMaestroCategoriaFacturacionGranComponente;
    //	MAESTRO >> CATEGORIA CENTRO FISICO
    var $sqlAdminMaestroCategoriaCentroFisico;
    var $numerofilasMaestroCategoriaCentroFisico;
    var $maxfilasMaestroCategoriaCentroFisico;
    //	MAESTRO >> PAISES
    var $sqlAdminMaestroPais;
    var $numerofilasMaestroPais;
    var $maxfilasMaestroPais;
    //	MAESTRO >> NUMEROS SERIE
    var $sqlAdminMaestroNumeroSerie;
    var $numerofilasMaestroNumeroSerie;
    var $maxfilasMaestroNumeroSerie;
    //	MAESTRO >> LISTADO TAREAS MANTENIMIENTO
    var $sqlAdminMaestroListadoTareasMantenimiento;
    var $numerofilasMaestroListadoTareasMantenimiento;
    var $maxfilasMaestroListadoTareasMantenimiento;
    // MAESTRO >> FRASES H
    var $sqlAdminMaestroFrasesH;
    var $numerofilasMaestroFrasesH;
    var $maxfilasMaestroFrasesH;
    // MAESTRO >> APLICACION >> DICCIONARIO
    var $sqlAdminDiccionario;
    var $numerofilasAdminDiccionario;
    var $maxfilasAdminDiccionario;

    // MAESTRO >> APQ CLASES PELIGRO
    var $sqlAdminMaestroApqClasesPeligro;
    var $numerofilasMaestroApqClasesPeligro;
    var $maxfilasMaestroApqClasesPeligro;
    // MAESTRO >> APQ ANEXOS CLP
    var $sqlAdminMaestroApqAnexosClp;
    var $numerofilasMaestroApqAnexosClp;
    var $maxfilasMaestroApqAnexosClp;
    // MAESTRO >> APQ CATEGORÍAS
    var $sqlAdminMaestroApqCategorias;
    var $numerofilasMaestroApqCategorias;
    var $maxfilasMaestroApqCategorias;
    // MAESTRO >> APQ FRASES H CARACTERÍSTICAS
    var $sqlAdminMaestroApqFrasesHCaracteristicas;
    var $numerofilasMaestroApqFrasesHCaracteristicas;
    var $maxfilasMaestroApqFrasesHCaracteristicas;
    // MAESTRO >> APQ APLICACION REGLAMENTO CF
    var $sqlAdminMaestroApqAplicacionReglamentoCF;
    var $numerofilasMaestroApqAplicacionReglamentoCF;
    var $maxfilasMaestroApqAplicacionReglamentoCF;
    //	MAESTRO >> ADUANAS
    var $sqlAdminMaestroAduanas;
    var $numerofilasMaestroAduanas;
    var $maxfilasMaestroAduanas;
    //	MAESTRO >> PATENTES
    var $sqlAdminMaestroPatentes;
    var $numerofilasMaestroPatentes;
    var $maxfilasMaestroPatentes;
    //	MAESTRO >> EMPRESAS
    var $sqlAdminMaestroEmpresas;
    var $numerofilasMaestroEmpresas;
    var $maxfilasMaestroEmpresas;
    //	MAESTRO >> ENTIDADES EXPEDIDORAS
    var $sqlAdminMaestroEntidadesExpedidoras;
    var $numerofilasMaestroEntidadesExpedidoras;
    var $maxfilasMaestroEntidadesExpedidoras;
    //	MAESTRO >> GRUPOS_APQ
    var $sqlAdminMaestroGruposAPQ;
    var $numerofilasMaestroGruposAPQ;
    var $maxfilasMaestroGruposAPQ;
    //	MAESTRO >> CLASES_ADR
    var $sqlAdminMaestroClaseADR;
    var $numerofilasMaestroClaseADR;
    var $maxfilasMaestroClaseADR;
    //	MAESTRO >> CLASES_RG
    var $sqlAdminMaestroClaseRG;
    var $numerofilasMaestroClaseRG;
    var $maxfilasMaestroClaseRG;
    //  MAESTRO >> ONU
    var $sqlAdminMaestroONU;
    var $numerofilasMaestroONU;
    var $maxfilasMaestroONU;
    //  MAESTRO >> CLAVE APROVISIONAMIENTO ESPECIAL
    var $sqlAdminMaestroClaveAprovisionamientoEspecial;
    var $numerofilasMaestroClaveAprovisionamientoEspecial;
    var $maxfilasMaestroClaveAprovisionamientoEspecial;
    //  MAESTRO >> TECNOLOGIA
    var $sqlAdminMaestroTecnologia;
    var $numerofilasMaestroTecnologia;
    var $maxfilasMaestroTecnologia;
    //  MAESTRO >> TECNOLOGIA_GENERICA
    var $sqlAdminMaestroTecnologiaGenerica;
    var $numerofilasMaestroTecnologiaGenerica;
    var $maxfilasMaestroTecnologiaGenerica;
    //  MAESTRO >> POTENCIA
    var $sqlAdminMaestroPotencia;
    var $numerofilasMaestroPotencia;
    var $maxfilasMaestroPotencia;
    //  MAESTRO >> ALMACEN_INSTALACION
    var $sqlAdminMaestroAlmacenInstalacion;
    var $numerofilasMaestroAlmacenInstalacion;
    var $maxfilasMaestroAlmacenInstalacion;
    //  MAESTRO >> INSTALACION_CLIENTE
    var $sqlAdminMaestroInstalacionCliente;
    var $numerofilasMaestroInstalacionCliente;
    var $maxfilasMaestroInstalacionCliente;
    //  MAESTRO >> MEDIOS_DE_DESCARGA
    var $sqlAdminMaestroMediosDeDescarga;
    var $numerofilasMaestroMediosDeDescarga;
    var $maxfilasMaestroMediosDeDescarga;
    //  MAESTRO >> ACCESIBILIDAD
    var $sqlAdminMaestroAccesibilidad;
    var $numerofilasMaestroAccesibilidad;
    var $maxfilasMaestroAccesibilidad;
    // MAESTROS >> PARAMETROS LOGISTICOS
    var $sqlAdminMaestroParametrosLogisticos;
    var $numerofilasMaestroParametrosLogisticos;
    var $maxfilasMaestroParametrosLogisticos;
    // MAESTROS >> MOTIVOS AJUSTE
    var $sqlAdminMaestroMotivosAjuste;
    var $numerofilasMaestroMotivosAjuste;
    var $maxfilasMaestroMotivosAjuste;
    //  MAESTRO >> DICCIONARIO
    var $sqlAdminMaestroDiccionario;
    var $numerofilasMaestroDiccionario;
    var $maxfilasMaestroDiccionario;
    //  MAESTRO >> CHOFERES
    var $sqlAdminMaestroChofer;
    var $numerofilasMaestroChofer;
    var $maxfilasMaestroChofer;
    //  MAESTRO >> CONTENEDORES
    var $sqlAdminMaestroContenedores;
    var $numerofilasMaestroContenedores;
    var $maxfilasMaestroContenedores;
    //  MAESTRO >> PUERTOS
    var $sqlAdminMaestroPuertos;
    var $numerofilasMaestroPuertos;
    var $maxfilasMaestroPuertos;
    //  MAESTRO >> ENTIDAD BL
    var $sqlAdminMaestroEntidadBL;
    var $numerofilasMaestroEntidadBL;
    var $maxfilasMaestroEntidadBL;
    //  MAESTRO >> TIPO EXTRACOSTE
    var $sqlAdminMaestroTipoExtracoste;
    var $numerofilasMaestroTipoExtracoste;
    var $maxfilasMaestroTipoExtracoste;
    //  MAESTRO >> TIPO SECTOR
    var $sqlAdminMaestroTipoSector;
    var $numerofilasMaestroTipoSector;
    var $maxfilasMaestroTipoSector;
    //  MAESTRO >> EXCEPCIONES DISTRIBUCION
    var $sqlAdminMaestroExcepcionesDistribucion;
    var $numerofilasMaestroExcepcionesDistribucion;
    var $maxfilasMaestroExcepcionesDistribucion;
    //  MAESTRO >> EXCEPCIONES DISTRIBUCION TIPO
    var $sqlAdminMaestroExcepcionesDistribucionTipo;
    var $numerofilasMaestroExcepcionesDistribucionTipo;
    var $maxfilasMaestroExcepcionesDistribucionTipo;
    //  MAESTRO >> INCOTERMS
    var $sqlAdminMaestroIncoterms;
    var $numerofilasMaestroIncoterms;
    var $maxfilasMaestroIncoterms;
    //  MAESTRO >> MONEDAS
    var $sqlAdminMaestroMonedas;
    var $numerofilasMaestroMonedas;
    var $maxfilasMaestroMonedas;
    //  MAESTRO >> DIRECCIONES
    var $sqlAdminMaestroDireccion;
    var $numerofilasMaestroDireccion;
    var $maxfilasMaestroDireccion;
    //  MAESTRO >> TIPOS PEDIDO SAP
    var $sqlAdminMaestroTiposPedidoSAP;
    var $numerofilasMaestroTiposPedidoSAP;
    var $maxfilasMaestroTiposPedidoSAP;
    //  MAESTRO >> ELEMENTOS INPUTACION
    var $sqlAdminMaestroElementosImputacion;
    var $numerofilasMaestroElementosImputacion;
    var $maxfilasMaestroElementosImputacion;
    //  MAESTRO >> REFERENCIAS FACTURACION
    var $sqlAdminMaestroReferenciasFacturacion;
    var $numerofilasMaestroReferenciasFacturacion;
    var $maxfilasMaestroReferenciasFacturacion;
    //  MAESTRO >> INTRODUCCION PESOS
    var $sqlAdminMaestroIntroduccionPesos;
    var $numerofilasMaestroIntroduccionPesos;
    var $maxfilasMaestroIntroduccionPesos;
    //  MAESTRO >> PARAMETROS REVISION PANELES
    var $sqlAdminMaestroParametrosRevisionPaneles;
    var $numerofilasMaestroParametrosRevisionPaneles;
    var $maxfilasMaestroParametrosRevisionPaneles;
    //	MAESTRO >> REGIONES
    var $sqlAdminMaestroRegiones;
    var $numerofilasMaestroRegiones;
    var $maxfilasMaestroRegiones;
    //	MAESTRO >> TARIFAS
    var $sqlAdminMaestroTarifas;
    var $numerofilasMaestroTarifas;
    var $maxfilasMaestroTarifas;
    //	MAESTRO >> REGLAS
    var $sqlAdminMaestroReglasDisponibilidadFechaPlanificadaOTs;
    var $numerofilasMaestroReglasDisponibilidadFechaPlanificadaOTs;
    var $maxfilasMaestroReglasDisponibilidadFechaPlanificadaOTs;
    //	MAESTRO >> CONTRATOS
    var $sqlAdminMaestroContratos;
    var $numerofilasMaestroContratos;
    var $maxfilasMaestroContratos;
    //	MAESTRO >> CLAUSULAS
    var $sqlAdminMaestroClausulas;
    var $numerofilasMaestroClausulas;
    var $maxfilasMaestroClausulas;
    //	MAESTRO >> SERVICIOS
    var $sqlAdminMaestroServicios;
    var $numerofilasMaestroServicios;
    var $maxfilasMaestroServicios;
    //	MAESTRO >> CARGAS MASIVAS
    var $sqlAdminMaestroCargasMasivas;
    var $numerofilasMaestroCargasMasivas;
    var $maxfilasMaestroCargasMasivas;
    //	MAESTRO >> REUBICACION
    var $sqlAdminMaestroReubicacion;
    var $numerofilasMaestroReubicacion;
    var $maxfilasMaestroReubicacion;
    //	MAESTRO >> TIPOLOGIAS_INCIDENCIAS
    var $sqlAdminMaestroTipologiaIncidencia;
    var $numerofilasMaestroTipologiaIncidencia;
    var $maxfilasMaestroTipologiaIncidencia;
    //	MAESTRO >> CORREOS
    var $sqlAdminMaestroCorreos;
    var $numerofilasMaestroCorreos;
    var $maxfilasMaestroCorreos;
    //	MAESTRO >> FichasSeguridad
    var $sqlAdminMaestroFichasSeguridad;
    var $numerofilasMaestroFichasSeguridad;
    var $maxfilasMaestroFichasSeguridad;
    //	MAESTRO >> IDIOMAS
    var $sqlAdminMaestroIdiomas;
    var $numerofilasMaestroIdiomas;
    var $maxfilasMaestroIdiomas;

    //	MAESTRO >> FALLOS
    var $sqlAdminMaestroFallos;
    var $numerofilasMaestroFallos;
    var $maxfilasMaestroFallos;

    //	MAESTRO >> CAUSAS
    var $sqlAdminMaestroCausas;
    var $numerofilasMaestroCausas;
    var $maxfilasMaestroCausas;

    //	MAESTRO >> SOLUCIONES
    var $sqlAdminMaestroSoluciones;
    var $numerofilasMaestroSoluciones;
    var $maxfilasMaestroSoluciones;

    //	MAESTRO >> CAMPOS
    var $sqlAdminMaestroCampos;
    var $numerofilasMaestroCampos;
    var $maxfilasMaestroCampos;

    //	MAESTRO >> CAMPO OBJETO
    var $sqlAdminMaestroCampoObjeto;
    var $numerofilasMaestroCampoObjeto;
    var $maxfilasMaestroCampoObjeto;

    //	MAESTRO >> OBJETOS BLOCKCHAIN
    var $sqlAdminMaestroObjetosBlockchain;
    var $numerofilasMaestroObjetosBlockchain;
    var $maxfilasMaestroObjetosBlockchain;

    //	MAESTRO >> INCIDENCIA SISTEMA TIPO
    var $sqlAdminMaestroIncidenciaSistemaTipo;
    var $numerofilasMaestroIncidenciaSistemaTipo;
    var $maxfilasMaestroIncidenciaSistemaTipo;

    //	MAESTRO >> INCIDENCIA SISTEMA SUBTIPO
    var $sqlAdminMaestroIncidenciaSistemaSubtipo;
    var $numerofilasMaestroIncidenciaSistemaSubtipo;
    var $maxfilasMaestroIncidenciaSistemaSubtipo;

    // MAESTRO >> TAREAS MANTENIMIENTO
    var $sqlAdminMaestroTareasMantenimiento;
    var $numerofilasMaestroTareasMantenimiento;
    var $maxfilasMaestroTareasMantenimiento;

    // MAESTRO >> TIPO TAREAS MANTENIMIENTO
    var $sqlAdminMaestroTipoTareasMantenimiento;
    var $numerofilasMaestroTipoTareasMantenimiento;
    var $maxfilasMaestroTipoTareasMantenimiento;

    //	MAESTRO >> TIPO ALERTA EXPEDITING
    var $sqlAdminMaestroTipoAlertaExpediting;
    var $numerofilasMaestroTipoAlertaExpediting;
    var $maxfilasMaestroTipoAlertaExpediting;

    //	MAESTRO >> TIPO MATERIAL
    var $sqlAdminMaestroTipoMaterial;
    var $numerofilasMaestroTipoMaterial;
    var $maxfilasMaestroTipoMaterial;
    //	MAESTRO >> TIPO MATERIAL SAP
    var $sqlAdminMaestroTipoMaterialSap;
    var $numerofilasMaestroTipoMaterialSap;
    var $maxfilasMaestroTipoMaterialSap;
    //	MAESTRO >> TIPO VEHICULO
    var $sqlAdminMaestroTipoVehiculo;
    var $numerofilasMaestroTipoVehiculo;
    var $maxfilasMaestroTipoVehiculo;
    //	MAESTRO >> LEAD TIMES
    var $sqlAdminMaestroLeadTimes;
    var $numerofilasMaestroLeadTimes;
    var $maxfilasMaestroLeadTimes;
    //	MAESTRO >> TIPO INCIDENCIA SISTEMA
    var $sqlAdminMaestroTipoIncidenciaSistema;
    var $numerofilasMaestrotTipoIncidenciaSistema;
    var $maxfilasMaestroTipoIncidenciaSistema;
    //	MAESTRO >> TIPO INCIDENCIA BLOCKCHAIN
    var $sqlAdminMaestroTipoIncidenciaBlockchain;
    var $numerofilasMaestrotTipoIncidenciaBlockchain;
    var $maxfilasMaestroTipoIncidenciaBlockchain;
    //	MAESTRO >> SUBTIPO INCIDENCIA SISTEMA
    var $sqlAdminMaestroSubTipoIncidenciaSistema;
    var $numerofilasMaestroSubTipoIncidenciaSistema;
    var $maxfilasMaestroSubTipoIncidenciaSistema;
    //	MAESTRO >> NAVIERA
    var $sqlAdminMaestroNaviera;
    var $numerofilasMaestroNaviera;
    var $maxfilasMaestroNaviera;
    //	MAESTRO >> BARCO
    var $sqlAdminMaestroBarco;
    var $numerofilasMaestroBarco;
    var $maxfilasMaestroBarco;
    //	MAESTRO >> ORDEN CONTRATACION
    var $sqlAdminMaestroContratacion;
    var $numerofilasMaestroContratacion;
    var $maxfilasMaestroContratacion;
    //	MAESTRO >> PLANTILLA CALENDARIO DE FESTIVOS
    var $sqlAdminMaestroPlantillaCalendarioFestivos;
    var $numerofilasMaestroPlantillaCalendarioFestivos;
    var $maxfilasMaestroPlantillaCalendarioFestivos;
    //	MAESTRO >>  CALENDARIO DE FESTIVOS
    var $sqlAdminMaestroCalendarioFestivos;
    var $numerofilasMaestroCalendarioFestivos;
    var $maxfilasMaestroCalendarioFestivos;
    //	MAESTRO >> VIAJES
    var $sqlAdminMaestroViajes;
    var $numerofilasMaestroViajes;
    var $maxfilasMaestroViajes;
    //	MAESTRO >> VIAJES DETALLE
    var $sqlAdminMaestroViajesDetalle;
    var $numerofilasMaestroViajesDetalle;
    var $maxfilasMaestroViajesDetalle;
    //	MAESTRO >> TIPOS DE WEB SERVICE
    var $sqlAdminMaestroTiposDeWebService;
    var $numerofilasMaestroTiposDeWebService;
    var $maxfilasMaestroTiposDeWebService;
    // MAESTRO >> GRUPO DE DEMANDAS
    var $sqlMaestroGruposDemanda;
    var $numerofilasMaestroGruposDemanda;
    var $maxfilasMaestroGruposDemanda;
    // MAESTRO >> LANZAMIENTO DE INTERFACES
    var $sqlMaestroLanzamientoInterfaces;
    var $numerofilasMaestroLanzamientoInterfaces;
    var $maxfilasMaestroLanzamientoInterfaces;
    //	MAESTRO >> GRUPO_COMPRA
    var $sqlAdminMaestroGrupoCompra;
    var $numerofilasMaestroGrupoCompra;
    var $maxfilasMaestroGrupoCompra;
    //	MAESTRO >> PRIORIDAD_INENTARIO
    var $sqlMaestroPrioridadInventario;
    var $numerofilasMaestroPrioridadInventario;
    var $maxfilasMaestroPrioridadInventario;
    // MAESTRO >> SOLICITUDES_MATERIAL
    var $sqlAdminMaestroSolicitudMaterial;
    var $numerofilasMaestroSolicitudMaterial;
    var $maxfilasMaestroSolicitudMaterial;
    // MAESTRO >> SOLICITUDES_MATERIAL_IC
    var $sqlAdminMaestroSolicitudMaterialIC;
    var $numerofilasMaestroSolicitudMaterialIC;
    var $maxfilasMaestroSolicitudMaterialIC;
    // MAESTRO >> SOLICITUDES_MATERIAL_SERVICIOS
    var $sqlAdminMaestroSolicitudMaterialServicios;
    var $numerofilasMaestroSolicitudMaterialServicios;
    var $maxfilasMaestroSolicitudMaterialServicios;
    // MAESTRO >> SOLICITUDES SUSTITUTIVOS
    var $sqlAdminMaestroSolicitudSustitutivo;
    var $numerofilasMaestroSolicitudSustitutivo;
    var $maxfilasMaestroSolicitudSustitutivo;

    //CODIFICACION >> INCIDENCIAS CODIFICACION
    var $sqlAdminCodificacionInformesIncidencias;
    var $numerofilasCodificacionInformesIncidencias;
    var $maxfilasCodificacionInformesIncidencias;
    //CODIFICACION >> ACCIONES
    var $sqlAdminCodificacionOperacionesAcciones;
    var $numerofilasCodificacionOperacionesAcciones;
    var $maxfilasCodificacionOperacionesAcciones;
    //MAESTRO >> APROBADORES
    var $sqlMaestroAprobadores;
    var $numerofilasMaestroAprobadores;
    var $maxfilasMaestroAprobadores;

    // BLOCKCHAIN
    var $sqlBlockchainBuscadorCampos;
    var $numerofilasBlockchainBuscadorCampos;
    var $maxfilasBlockchainBuscadorCampos;
    var $sqlBlockchainBuscadorDocumentos;
    var $numerofilasBlockchainBuscadorDocumentos;
    var $maxfilasBlockchainBuscadorDocumentos;

    //	ENTRADAS >> RECEPCIONES
    var $sqlEntradasRecepciones;
    var $numerofilasEntradasRecepciones;
    var $maxfilasEntradasRecepciones;
    //	ENTRADAS >> RECEPCIONES CONTAINERS
    var $sqlEntradasRecepcionesContainers;
    var $numerofilasEntradasRecepcionesContainers;
    var $maxfilasEntradasRecepcionesContainers;
    //	ENTRADAS >> CONTENEDORES_ENTRANTES
    var $sqlEntradasContenedoresEntrantes;
    var $numerofilasEntradasContenedoresEntrantes;
    var $maxfilasEntradasContenedoresEntrantes;
    //	ENTRADAS >> MOVIMIENTOS
    var $sqlEntradasMovimientos;
    var $numerofilasEntradasMovimientos;
    var $maxfilasEntradasMovimientos;
    //	ENTRADAS >> PEDIDOS
    var $sqlEntradasPedidos;
    var $numerofilasEntradasPedidos;
    var $maxfilasEntradasPedidos;
    //	ENTRADAS >> RECEPCION PEDIDOS TRASLADO
    var $sqlEntradasRecepcionPedidosTraslado;
    var $numerofilasEntradasRecepcionPedidosTraslado;
    var $maxfilasEntradasRecepcionPedidosTraslado;
    //	ENTRADAS >> CONTROL RECEPCION
    var $sqlEntradasControlRecepcion;
    var $numerofilasEntradasControlRecepcion;
    var $maxfilasEntradasControlRecepcion;
    //	ENTRADAS >> CONTROL GRAN COMPONENTE
    var $sqlEntradasControlGranComponente;
    var $numerofilasEntradasControlGranComponente;
    var $maxfilasEntradasControlGranComponente;
    //	ENTRADAS >> ANULACIONES
    var $sqlEntradasAnulaciones;
    var $numerofilasEntradasAnulaciones;
    var $maxfilasEntradasAnulaciones;
    //	ENTRADAS >> CONTROL CODIFICACION
    var $sqlEntradasControlCodificacion;
    var $numerofilasEntradasControlCodificacion;
    var $maxfilasEntradasControlCodificacion;
    //	ENTRADAS >> SIN PEDIDO
    var $sqlEntradasSinCompra;
    var $numerofilasEntradasSinCompra;
    var $maxfilasEntradasSinCompra;
    //	ENTRADAS >> CARROS
    var $sqlEntradasCarros;
    var $numerofilasEntradasCarros;
    var $maxfilasEntradasCarros;

    //CONSTRUCCION  >> ORDEN MONTAJE
    var $sqlConstruccionOrdenMontaje;
    var $numerofilasConstruccionOrdenMontaje;
    var $maxfilasConstruccionOrdenMontaje;

    //	CONSTRUCCION >> UOP
    var $sqlConstruccionUnidadOrganizativaProceso;
    var $numerofilasConstruccionUnidadOrganizativaProceso;
    var $maxfilasConstruccionUnidadOrganizativaProceso;

    //CONSTRUCCION >> TIPO_SECTOR
    var $sqlConstruccionTipoSector;
    var $numerofilasConstruccionTipoSector;
    var $maxfilasConstruccionTipoSector;

    //	CONSTRUCCION >> PARAMETROS
    var $sqlConstruccionParametros;
    var $numerofilasConstruccionParametros;
    var $maxfilasConstruccionParametros;

    //	CONSTRUCCION >> PARAMETROS
    var $sqlConstruccionFaltantes;
    var $numerofilasConstruccionFaltantes;
    var $maxfilasConstruccionFaltantes;

    //CONSTRUCCION  >> ORDEN MONTAJE AVISOS
    var $sqlConstruccionOrdenMontajeAvisos;
    var $numerofilasConstruccionOrdenMontajeAvisos;
    var $maxfilasConstruccionOrdenMontajeAvisos;

    //CONSTRUCCION  >> OPERACIONES
    var $sqlContruccionOperaciones;
    var $numerofilasContruccionOperaciones;
    var $maxfilasContruccionOperaciones;
    //CONSTRUCCION  >> ACTIVIDADES
    var $sqlContruccionActividades;
    var $numerofilasContruccionActividades;
    var $maxfilasContruccionActividades;
    //CONSTRUCCION  >> FASES
    var $sqlContruccionFases;
    var $numerofilasContruccionFases;
    var $maxfilasContruccionFases;
    //CONSTRUCCION  >> SUBFASES
    var $sqlConstruccionSubFases;
    var $numerofilasConstruccionSubFases;
    var $maxfilasConstruccionSubFases;
    //CONSTRUCCION  >> FASES INSTALACION
    var $sqlContruccionFasesInstalacion;
    var $numerofilasContruccionFasesInstalacion;
    var $maxfilasContruccionFasesInstalacion;
    //CONSTRUCCION  >> SUBFASES INSTALACION
    var $sqlConstruccionSubFasesInstalacion;
    var $numerofilasConstruccionSubFasesInstalacion;
    var $maxfilasConstruccionSubFasesInstalacion;
    //CONSTRUCCION  >> OPERACION INSTALACION
    var $sqlContruccionOperacionesInstalacion;
    var $numerofilasContruccionOperacionesInstalacion;
    var $maxfilasContruccionOperacionesInstalacion;
    //CONSTRUCCION  >> INFORME SITUACION
    var $sqlContruccionInformeSituacion;
    var $numerofilasContruccionInformeSituacion;
    var $maxfilasContruccionInformeSituacion;
    //CONSTRUCCION  >> INFORME LAYOUT MAQUINA
    var $sqlContruccionLayoutMaquinas;
    var $numerofilasContruccionLayoutMaquinas;
    var $maxfilasContruccionLayoutMaquinas;

    //TRANSPORTE CONSTRUCCION  >> UNIDAD TRANSPORTE
    var $sqlTransporteConstruccionUnidadTransporte;
    var $numerofilasTransporteConstruccionUnidadTransporte;
    var $maxfilasTransporteConstruccionUnidadTransporte;

    //TRANSPORTE CONSTRUCCION  >> RETENCION
    var $sqlTransporteConstruccioRetenciones;
    var $numerofilasTransporteConstruccionRetenciones;
    var $maxfilasTransporteConstruccionRetenciones;

    //TRANSPORTE CONSTRUCCION  >> HITOS
    var $sqlTransporteConstruccionHitos;
    var $numerofilasTransporteConstruccionHitos;
    var $maxfilasTransporteConstruccionHitos;
    //CONSTRUCCION  >> ETAPAS
    var $sqlConstruccionEtapas;
    var $numerofilasConstruccionEtapas;
    var $maxfilasConstruccionEtapas;
    //CONSTRUCCION  >> SUBETAPAS
    var $sqlConstruccionSubEtapas;
    var $numerofilasConstruccionSubEtapas;
    var $maxfilasConstruccionSubEtapas;
    //CONSTRUCCION  >> ETAPAS INSTALACION
    var $sqlConstruccionEtapasInstalacion;
    var $numerofilasConstruccionEtapasInstalacion;
    var $maxfilasConstruccionEtapasInstalacion;
    //CONSTRUCCION  >> ETAPAS INSTALACION
    var $sqlConstruccionSubEtapasInstalacion;
    var $numerofilasConstruccionSubEtapasInstalacion;
    var $maxfilasConstruccionSubEtapasInstalacion;
    //CONSTRUCCION  >> ACTIVIDADES INSTALACION
    var $sqlConstruccionActividadesInstalacion;
    var $numerofilasConstruccionActividadesInstalacion;
    var $maxfilasConstruccionActividadesInstalacion;
    //CONSTRUCCION  >> ESTRUCTURA
    var $sqlConstruccionEstructuraOrganizativa;
    var $numerofilasConstruccionEstructuraOrganizativa;
    var $maxfilasConstruccionEstructuraOrganizativa;

    //TRANSPORTE CONSTRUCCION  >> AVISOS
    var $sqlTransporteConstruccionAvisos;
    var $numerofilasTransporteConstruccionAvisos;
    var $maxfilasTransporteConstruccionAvisos;

    //TRANSPORTE CONSTRUCCION  >> EMBARQUES
    var $sqlTransporteConstruccionEmbarques;
    var $numerofilasTransporteConstruccionEmbarques;
    var $maxfilasTransporteConstruccionEmbarques;

    //TRANSPORTE CONSTRUCCION  >> INTEGRIDAD OTS
    var $sqlTransporteConstruccionIntegridadOTs;
    var $numerofilasTransporteConstruccionIntegridadOTs;
    var $maxfilasTransporteConstruccionIntegridadOTs;

    //TRANSPORTE CONSTRUCCION  >> INTEGRIDAD CONTRATACIONES
    var $sqlTransporteConstruccionIntegridadContrataciones;
    var $numerofilasTransporteConstruccionIntegridadContrataciones;
    var $maxfilasTransporteConstruccionIntegridadContrataciones;

    //TRANSPORTE CONSTRUCCION  >> ESTADO OTS
    var $sqlTransporteConstruccionEstadoOTs;
    var $numerofilasTransporteConstruccionEstadoOTs;
    var $maxfilasTransporteConstruccionEstadoOTs;

    //TRANSPORTE CONSTRUCCION  >> DESVIOS PLANIFICACION
    var $sqlTransporteConstruccionDesviosPlanificacion;
    var $numerofilasTransporteConstruccionDesviosPlanificacion;
    var $maxfilasTransporteConstruccionDesviosPlanificacion;

    //TRANSPORTE CONSTRUCCION  >> SDPS
    var $sqlTransporteConstruccionSdps;
    var $numerofilasTransporteConstruccionSdps;
    var $maxfilasTransporteConstruccionSdps;

    //TRANSPORTE CONSTRUCCION  >> NCS
    var $sqlTransporteConstruccionNCs;
    var $numerofilasTransporteConstruccionNCs;
    var $maxfilasTransporteConstruccionNCs;

    //TRANSPORTE CONSTRUCCION  >> AVISOS MASIVOS
    var $sqlTransporteConstruccionAvisosMasivos;
    var $numerofilasTransporteConstruccionAvisosMasivos;
    var $maxfilasTransporteConstruccionAvisosMasivos;

    //TRANSPORTE CONSTRUCCION  >> CRONOGRAMA
    var $sqlTransporteConstruccionCronograma;
    var $numerofilasTransporteConstruccionCronograma;
    var $maxfilasTransporteConstruccionCronograma;

    //TRANSPORTE CONSTRUCCION  >> CRONOGRAMA EMBARQUES
    var $sqlTransporteConstruccionCronogramaEmbarques;
    var $numerofilasTransporteConstruccionCronogramaEmbarques;
    var $maxfilasTransporteConstruccionCronogramaEmbarques;

    //TRANSPORTE CONSTRUCCION  >> PLAN PROYECTO
    var $sqlTransporteConstruccionPlanProyecto;
    var $numerofilasTransporteConstruccionPlanProyecto;
    var $maxfilasTransporteConstruccionPlanProyecto;

    //TRANSPORTE CONSTRUCCIÓN >> MÓDULOS
    var $sqlTransporteConstruccionModulo;
    var $numerofilasTransporteConstruccionModulo;
    var $maxfilasTransporteConstruccionModulo;

    //TRANSPORTE CONSTRUCCIÓN >> PEDIDOS CONTRATO LINEA
    var $sqlTransporteConstruccionLineaPedido;
    var $numerofilasTransporteConstruccionLineaPedido;
    var $maxfilasTransporteConstruccionLineaPedido;

    //TRANSPORTE CONSTRUCCIÓN >> PEDIDOS CONTRATO
    var $sqlTransporteConstruccionPedido;
    var $numerofilasTransporteConstruccionPedido;
    var $maxfilasTransporteConstruccionPedido;

    //TRANSPORTE CONSTRUCCIÓN >> CONFIGURACION PERIODICIDAD
    var $sqlRegistroBlockchainConfiguracionPeriodicidad;
    var $numerofilasRegistroBlockchainConfiguracionPeriodicidad;
    var $maxfilasRegistroBlockchainConfiguracionPeriodicidad;

    //TRANSPORTE CONSTRUCCIÓN >> INFORMES BLOCKCHAIN
    var $sqlRegistroBlockchainInformesBlockchain;
    var $numerofilasRegistroBlockchainInformesBlockchain;
    var $maxfilasRegistroBlockchainInformesBlockchain;

    //TRANSPORTE CONSTRUCCIÓN >> LOG ACCIONES
    var $sqlRegistroBlockchainLogAcciones;
    var $numerofilasRegistroBlockchainLogAcciones;
    var $maxfilasRegistroBlockchainLogAcciones;

    //TRANSPORTE CONSTRUCCIÓN >> REGISTRO CHATS
    var $sqlRegistroBlockchainRegistroChats;
    var $numerofilasRegistroBlockchainRegistroChats;
    var $maxfilasRegistroBlockchainRegistroChats;

    //CONSTRUCCION SOLAR >> CONTROL DE CALIDAD
    var $sqlConstruccionSolarControlCalidad;
    var $numerofilasConstruccionSolarControlCalidad;
    var $maxfilasConstruccionSolarControlCalidad;
    //CONSTRUCCION SOLAR >> LOTES FABRICACION
    var $sqlConstruccionSolarLotesFabricacion;
    var $numerofilasConstruccionSolarLotesFabricacion;
    var $maxfilasConstruccionSolarLotesFabricacion;
    //CONSTRUCCION SOLAR >> PERMANENCIA STOCK
    var $sqlConstruccionSolarPermanenciaStock;
    var $numerofilasConstruccionSolarPermanenciaStock;
    var $maxfilasConstruccionSolarPermanenciaStock;
    //CONSTRUCCION SOLAR >> LOTES FABRICACION
    var $sqlConstruccionSolarNumeroLote;
    var $numerofilasConstruccionSolarNumeroLote;
    var $maxfilasConstruccionSolarNumeroLote;

    //	SALIDAS >> PEDIDOS
    var $sqlSalidasPedidos;
    var $numerofilasSalidasPedidos;
    var $maxfilasSalidasPedidos;
    //	SALIDAS >> MOVIMIENTOS
    var $sqlSalidasMovimientos;
    var $numerofilasSalidasMovimientos;
    var $maxfilasSalidasMovimientos;
    //	SALIDAS >> ORDENES PREPARACION
    var $sqlSalidasOrdenesPreparacion;
    var $numerofilasSalidasOrdenesPreparacion;
    var $maxfilasSalidasOrdenesPreparacion;
    //	SALIDAS >> ORDENES CARGA
    var $sqlSalidasOrdenesCarga;
    var $numerofilasSalidasOrdenesCarga;
    var $maxfilasSalidasOrdenesCarga;
    //	SALIDAS >> EXPEDICIONES
    var $sqlSalidasExpediciones;
    var $numerofilasSalidasExpediciones;
    var $maxfilasSalidasExpediciones;
    //	SALIDAS >> GESTION_FACTURAS
    var $sqlSalidasGestionFacturas;
    var $numerofilasSalidasGestionFacturas;
    var $maxfilasSalidasGestionFacturas;
    //	SALIDAS >> DECISION CANAL DE ENTREGA
    var $sqlDecisionCanalEntrega;
    var $numerofilasDecisionCanalEntrega;
    var $maxfilasDecisionCanalEntrega;
    //	TRANSPORTE >> ORDENES TRASPORTE
    var $sqlTransporteOrdenesTransporte;
    var $numerofilasTransporteOrdenesTransporte;
    var $maxfilasTransporteOrdenesTransporte;
    //	TRANSPORTE >> ORDENES CONTRATACION
    var $sqlTransporteOrdenesContratacion;
    var $numerofilasTransporteOrdenesContratacion;
    var $maxfilasTransporteOrdenesContratacion;
    //	TRANSPORTE >> AUTOFACTURAS
    var $sqlTransporteAutofacturas;
    var $numerofilasTransporteAutofacturas;
    var $maxfilasTransporteAutofacturas;
    //	TRANSPORTE >> SOLICITUDES
    var $sqlTransporteSolicitudes;
    var $numerofilasTransporteSolicitudes;
    var $maxfilasTransporteSolicitudes;
    //	TRANSPORTE >> SOLICITUDES PROVEEDOR
    var $sqlTransporteSolicitudesProveedor;
    var $numerofilasTransporteSolicitudesProveedor;
    var $maxfilasTransporteSolicitudesProveedor;
    //	TRANSPORTE >> SOLICITUDES TERCEROS
    var $sqlTransporteSolicitudesTerceros;
    var $numerofilasTransporteSolicitudesTerceros;
    var $maxfilasTransporteSolicitudesTerceros;
    //	SALIDAS >> ETIQUETAS PENDIENTES
    var $sqlAlmacenEtiquetasPendientes;
    var $numerofilasAlmacenEtiquetasPendientes;
    var $maxfilasAlmacenEtiquetasPendientes;
    //	SALIDAS >> CONSOLIDACION AGM
    var $sqlAlmacenConsolidacionAGM;
    var $numerofilasAlmacenConsolidacionAGM;
    var $maxfilasAlmacenConsolidacionAGM;
    //	SALIDAS >> BULTOS
    var $sqlSalidasBultos;
    var $numerofilasSalidasBultos;
    var $maxfilasSalidasBultos;
    //	SALIDAS >> CONTROL FACTURAS
    var $sqlSalidasControlFacturas;
    var $numerofilasSalidasControlFacturas;
    var $maxfilasSalidasControlFacturas;
    //	ADMINISTRADOR >> ADMINISTRACIÓN >> Perfiles
    var $sqlAdminAdmiPerf;
    var $numerofilasAdminAdmiPerf;
    var $maxfilasAdminAdmiPerf;
    //	ADMINISTRADOR >> ADMINISTRACIÓN >> Usuarios
    var $sqlAdminAdmiUsuarios;
    var $numerofilasAdminAdmiUsuarios;
    var $maxfilasAdminAdmiUsuarios;
    //	ADMINISTRADOR >> ADMINISTRACIÓN >> Bloqueos
    var $sqlAdminAdmiBloqueos;
    var $numerofilasAdminAdmiBloqueos;
    var $maxfilasAdminAdmiBloqueos;
    //	ADMINISTRADOR >> ADMINISTRACIÓN >> Tecnicos Mantenimiento
    var $sqlAdminAdmiTecnicosMantenimiento;
    var $numerofilasAdminAdmiTecnicosMantenimiento;
    var $maxfilasAdminAdmiTecnicosMantenimiento;
    //	ADMINISTRADOR >> ADMINISTRACIÓN >> Accesos
    var $sqlAdminAdmiAccesos;
    var $numerofilasAdminAdmiAccesos;
    var $maxfilasAdminAdmiAccesos;
    //	ADMINISTRADOR >> ADMINISTRACIÓN >> WS Log
    var $sqlAdminAdmiWSLog;
    var $numerofilasAdminAdmiWSLog;
    var $maxfilasAdminAdmiWSLog;
    //	ADMINISTRADOR >> ADMINISTRACIÓN >> WS Log
    var $sqlAdminAdmiLogBlockchain;
    var $numerofilasAdminAdmiLogBlockchain;
    var $maxfilasAdminAdmiLogBlockchain;
    //	ADMINISTRADOR >> ADMINISTRACIÓN >> Trazabilidad
    var $sqlAlmacenTrazabilidad;
    var $numerofilasAlmacenTrazabilidad;
    var $maxfilasAlmacenTrazabilidad;
    //	ADMINISTRADOR >> ADMINISTRACIÓN >> Menu
    var $sqlAdminMenu;
    var $numerofilasAdminMenu;
    var $maxfilasAdminMenu;
    //	ALMACEN >> STOCK POR UBICACION
    var $sqlAlmacenStockPorUbicacion;
    var $numerofilasAlmacenStockPorUbicacion;
    var $maxfilasAlmacenStockPorUbicacion;
    //	ALMACEN >> STOCK DISPONIBLE
    var $sqlAlmacenStockDisponible;
    var $numerofilasAlmacenStockDisponible;
    var $maxfilasAlmacenStockDisponible;
    //	ALMACEN >> STOCK EXTERNALIZADO
    var $sqlAlmacenStockExternalizado;
    var $numerofilasAlmacenStockExternalizado;
    var $maxfilasAlmacenStockExternalizado;
    //	ALMACEN >> ASIENTOS
    var $sqlAlmacenAsientos;
    var $numerofilasAlmacenAsientos;
    var $maxfilasAlmacenAsientos;
    //	ALMACEN >> AJUSTES DE INVENTARIOS
    var $sqlAlmacenAjusteInventarios;
    var $numerofilasAlmacenAjusteInventarios;
    var $maxfilasAlmacenAjusteInventarios;
    //	ALMACEN >> TRANSFERENCIAS
    var $sqlAlmacenTransferencias;
    var $numerofilasAlmacenTransferencias;
    var $maxfilasAlmacenTransferencias;
    //	ALMACEN >> TRASLADO DIRECTO
    var $sqlAlmacenTrasladosDirectos;
    var $numerofilasAlmacenTrasladosDirectos;
    var $maxfilasAlmacenTrasladosDirectos;
    //	ALMACEN >> TAREAS PENDIENTES
    var $sqlAlmacenTareasPendientes;
    var $numerofilasAlmacenTareasPendientes;
    var $maxfilasAlmacenTareasPendientes;
    //	ALMACEN >> CAMBIO ESTADO
    var $sqlAlmacenCambioEstado;
    var $numerofilasAlmacenCambioEstado;
    var $maxfilasAlmacenCambioEstado;
    //	ALMACEN >> ORDENES CONTEO
    var $sqlAlmacenOrdenConteo;
    var $numerofilasAlmacenOrdenConteo;
    var $maxfilasAlmacenOrdenConteo;
    //	ALMACEN >> ORDENES TRANSFERENCIA
    var $sqlAlmacenOrdenTransferencia;
    var $numerofilasAlmacenOrdenTransferencia;
    var $maxfilasAlmacenOrdenTransferencia;
    //	ALMACEN >> ORDENES TRANSFERENCIA
    var $sqlAlmacenPropuestaReubicacion;
    var $numerofilasAlmacenPropuestaReubicacion;
    var $maxfilasAlmacenPropuestaReubicacion;

    //	OPERACIONES ESPECIALES >> CAMBIO NUMERO SERIE
    var $sqlOperacionesEspecialesCambioNumeroSerie;
    var $numerofilasOperacionesEspecialesCambioNumeroSerie;
    var $maxfilasOperacionesEspecialesCambioNumeroSerie;

    //	OPERACIONES ESPECIALES >> CAMBIO REFERENCIA
    var $sqlOperacionesEspecialesCambioReferencia;
    var $numerofilasOperacionesEspecialesCambioReferencia;
    var $maxfilasOperacionesEspecialesCambioReferencia;

    //	ORDENES TRABAJO >> ORDENES TRABAJO
    var $sqlOrdenesTrabajoOrdenesTrabajo;
    var $numerofilasOrdenesTrabajoOrdenesTrabajo;
    var $maxfilasOrdenesTrabajoOrdenesTrabajo;
    //	ORDENES TRABAJO >> OT´s DE PLANIFICADOS
    var $sqlOrdenesTrabajoOTsDePlanificados;
    var $numerofilasOrdenesTrabajoOTsDePlanificados;
    var $maxfilasOrdenesTrabajoOTsDePlanificados;
    //	ORDENES TRABAJO >> OT´s CON PENDIENTES
    var $sqlOrdenesTrabajoOTsConPendientes;
    var $numerofilasOrdenesTrabajoOTsConPendientes;
    var $maxfilasOrdenesTrabajoOTsConPendientes;
    //	ORDENES TRABAJO >> PENDIENTES
    var $sqlOrdenesTrabajoPendientes;
    var $numerofilasOrdenesTrabajoPendientes;
    var $maxfilasOrdenesTrabajoPendientes;
    //	ORDENES TRABAJO >> TRANSFERENCIAS DE PENDIENTES
    var $sqlOrdenesTrabajoTransferenciasDePendientes;
    var $numerofilasOrdenesTrabajoTransferenciasDePendientes;
    var $maxfilasOrdenesTrabajoTransferenciasDePendientes;
    //	ORDENES TRABAJO >> JOB PLAN
    var $sqlOrdenesTrabajoJobPlan;
    var $numerofilasOrdenesTrabajoJobPlan;
    var $maxfilasOrdenesTrabajoJobPlan;
    //	ORDENES TRABAJO >> GAMAS
    var $sqlOrdenesTrabajoGamas;
    var $numerofilasOrdenesTrabajoGamas;
    var $maxfilasOrdenesTrabajoGamas;
    //	ORDENES TRABAJO >> ORDENES TRABAJO MATERIAL NUMEROS DE SERIE
    var $sqlOrdenesTrabajoOrdenesTrabajoMaterialNumeroSerie;
    var $numerofilasOrdenesTrabajoOrdenesTrabajoMaterialNumeroSerie;
    var $maxfilasOrdenesTrabajoOrdenesTrabajoMaterialNumeroSerie;
    //	ORDENES TRABAJO >> ORDENES TRABAJO SGA
    var $sqlOrdenesTrabajoOrdenesTrabajoSGA;
    var $numerofilasOrdenesTrabajoOrdenesTrabajoSGA;
    var $maxfilasOrdenesTrabajoOrdenesTrabajoSGA;
    //	ORDENES TRABAJO >> MOVIMIENTOS VEHICULOS
    var $sqlOrdenesTrabajoMovimientosVehiculos;
    var $numerofilasOrdenesTrabajoMovimientosVehiculos;
    var $maxfilasOrdenesTrabajoMovimientosVehiculos;
    //	ORDENES TRABAJO >> OPERACIONES MASIVAS
    var $sqlOrdenesTrabajoOperacionesMasivas;
    var $numerofilasOrdenesTrabajoOperacionesMasivas;
    var $maxfilasOrdenesTrabajoOperacionesMasivas;

    //	LOGISTICA INVERSA >> MATERIAL ESTROPEADO
    var $sqlLogisticaInversaMaterialEstropeado;
    var $numerofilasLogisticaInversaMaterialEstropeado;
    var $maxfilasLogisticaInversaMaterialEstropeado;
    //	LOGISTICA INVERSA >> ENVIOS MATERIAL A PROVEEDOR
    var $sqlLogisticaInversaEnvioMaterialAProveedor;
    var $numerofilasLogisticaInversaEnvioMaterialAProveedor;
    var $maxfilasLogisticaInversaEnvioMaterialAProveedor;
    //	LOGISTICA INVERSA >> ENVIOS MATERIAL A ALMACEN PRINCIPAL
    var $sqlLogisticaInversaEnvioMaterialAAlmacenPrincipal;
    var $numerofilasLogisticaInversaEnvioMaterialAAlmacenPrincipal;
    var $maxfilasLogisticaInversaEnvioMaterialAAlmacenPrincipal;
    //	LOGISTICA INVERSA >> MATERIAL PENDIENTE RECEPCIONAR
    var $sqlLogisticaInversaMaterialPendienteRecepcionar;
    var $numerofilasLogisticaInversaMaterialPendienteRecepcionar;
    var $maxfilasLogisticaInversaMaterialPendienteRecepcionar;
    //	LOGISTICA INVERSA >> MATERIAL RECEPCIONADO
    var $sqlLogisticaInversaMaterialRecepcionado;
    var $numerofilasLogisticaInversaMaterialRecepcionado;
    var $maxfilasLogisticaInversaMaterialRecepcionado;
    //	LOGISTICA INVERSA >> REPARACIONES INTERNAS
    var $sqlLogisticaInversaReparacionesInternas;
    var $numerofilasLogisticaInversaReparacionesInternas;
    var $maxfilasLogisticaInversaReparacionesInternas;
    //	LOGISTICA INVERSA >> ROADMAP REPARACIONES MULTIPROVEEDOR
    var $sqlLogisticaInversaRoadmapReparacionesMultiproveedor;
    var $numerofilasLogisticaInversaRoadmapReparacionesMultiproveedor;
    var $maxfilasLogisticaInversaRoadmapReparacionesMultiproveedor;
    //	LOGISTICA INVERSA >> ROADMAP REPARACIONES
    var $sqlLogisticaInversaReparaciones;
    var $numerofilasLogisticaInversaReparaciones;
    var $maxfilasLogisticaInversaReparaciones;

    //	INVENTARIOS >> INVENTARIOS
    var $sqlInventariosInventarios;
    var $numerofilasInventariosInventarios;
    var $maxfilasInventariosInventarios;
    //	INVENTARIOS >> ORDENES DE CONTEO
    var $sqlInventariosOrdenesConteo;
    var $numerofilasInventariosOrdenesConteo;
    var $maxfilasInventariosOrdenesConteo;

    //	CALIDAD >> CONTROL CALIDAD
    var $sqlCalidadControlCalidad;
    var $numerofilasCalidadControlCalidad;
    var $maxfilasCalidadControlCalidad;
    //	CALIDAD >> INCIDENCIAS
    var $sqlCalidadIncidencias;
    var $numerofilasCalidadIncidencias;
    var $maxfilasCalidadIncidencias;
    //	CALIDAD >> NCS
    var $sqlAdminCalidadNCs;
    var $numerofilasCalidadNCs;
    var $maxfilasCalidadNCs;
    //	CALIDAD >> MATERIAL NO CONFORME
    var $sqlCalidadMaterialNoConforme;
    var $numerofilasCalidadMaterialNoConforme;
    var $maxfilasCalidadMaterialNoConforme;
    //	CALIDAD >> MATERIAL NO CONFORME PENDIENTE RECEPCIONAR
    var $sqlCalidadMaterialNoConformePendienteRecepcionar;
    var $numerofilasCalidadMaterialNoConformePendienteRecepcionar;
    var $maxfilasCalidadMaterialNoConformePendienteRecepcionar;
    //	CALIDAD >> RECEPCION MATERIAL NO CONFORME
    var $sqlCalidadRecepcionMaterialNoConforme;
    var $numerofilasCalidadRecepcionMaterialNoConforme;
    var $maxfilasCalidadRecepcionMaterialNoConforme;
    //	CALIDAD >> CONSULTA ENVIOS MATERIAL NO CONFORME
    var $sqlCalidadConsultaEnviosMaterialNoConforme;
    var $numerofilasCalidadConsultaEnviosMaterialNoConforme;
    var $maxfilasCalidadConsultaEnviosMaterialNoConforme;
    //	CALIDAD >> CONSULTA GARANTIA MATERIAL
    var $sqlCalidadGarantiaMaterial;
    var $numerofilasCalidadGarantiaMaterial;
    var $maxfilasCalidadGarantiaMaterial;

    //	INFORMES >> INFORME CALIDAD SERVICIO
    var $sqlInformesInformeCalidadServicio;
    var $numerofilasInformesInformeCalidadServicio;
    var $maxfilasInformesInformeCalidadServicio;
    //	INFORMES >> TRATAMIENTO AUTOMATICO OTS
    var $sqlInformesTratamientoAutomaticoOTs;
    var $numerofilasInformesTratamientoAutomaticoOTs;
    var $maxfilasInformesTratamientoAutomaticoOTs;
    //	INFORMES >> COLA INTERFACES CODIFICACION
    var $sqlInformesColaInterfacesCodificacion;
    var $numerofilasInformesColaInterfacesCodificacion;
    var $maxfilasInformesColaInterfacesCodificacion;
    //	INFORMES >> RECEPCIONES CON PROBLEMAS
    var $sqlInformesRecepcionesConProblemas;
    var $numerofilasInformesRecepcionesConProblemas;
    var $maxfilasInformesRecepcionesConProblemas;
    //	INFORMES >> ALERTAS APQ
    var $sqlInformesAlertasAPQ;
    var $numerofilasInformesAlertasAPQ;
    var $maxfilasInformesAlertasAPQ;
    //	INFORMES >> INFORME ADR
    var $sqlInformesInformeADR;
    var $numerofilasInformesInformeADR;
    var $maxfilasInformesInformeADR;
    //	INFORMES >> REPOSICIONES SPV
    var $sqlInformesReposicionesSPV;
    var $numerofilasInformesReposicionesSPV;
    var $maxfilasInformesReposicionesSPV;
    //	INFORMES >> CONTROL TIEMPO REVISION
    var $sqlInformesControlTiempoRevision;
    var $numerofilasInformesControlTiempoRevision;
    var $maxfilasInformesControlTiempoRevision;
    //	INFORMES >> TRANSACCIONES
    var $sqlInformesTransacciones;
    var $numerofilasInformesTransacciones;
    var $maxfilasInformesTransacciones;
    //	INFORMES >> REPARACIÓN
    var $sqlInformesInformeReparacion;
    var $numerofilasInformesInformeReparacion;
    var $maxfilasInformesInformeReparacion;

    //	INFORMES >> CONSTRUCCION
    var $sqlInformesConstruccion;
    var $numerofilasInformesConstruccion;
    var $maxfilasInformesConstruccion;

    //	PROBLEMAS >> MATERIALES ALMACEN
    var $sqlAdminProblemasMaterialAlmacen;
    var $numerofilasProblemasMaterialAlmacen;
    var $maxfilasProblemasMaterialAlmacen;
    //	PROBLEMAS >> PEDIDOS COMPRA
    var $sqlAdminProblemasPedidosCompra;
    var $numerofilasProblemasPedidosCompra;
    var $maxfilasProblemasPedidosCompra;
    //	PROBLEMAS >> RELEVANCIA PEDIDOS
    var $sqlAdminProblemasRelevanciaPedidos;
    var $numerofilasProblemasRelevanciaPedidos;
    var $maxfilasProblemasRelevanciaPedidos;
    //	PROBLEMAS >> INCIDENCIAS SISTEMA
    var $sqlAdminProblemasIncidenciasSistema;
    var $numerofilasProblemasIncidenciasSistema;
    var $maxfilasProblemasIncidenciasSistema;
    //	PROBLEMAS >> INCIDENCIAS BLOCKCHAIN
    var $sqlAdminProblemasIncidenciasBlockchain;
    var $numerofilasProblemasIncidenciasBlockchain;
    var $maxfilasProblemasIncidenciasBlockchain;

    //	NECESIDADES >> NECESIDADES
    var $sqlNecesidadesNecesidades;
    var $numerofilasNecesidadesNecesidades;
    var $maxfilasNecesidadesNecesidades;

    //	PROVEEDORES >> CONTRATACIONES
    var $sqlProveedoresContrataciones;
    var $numerofilasProveedoresContrataciones;
    var $maxfilasProveedoresContrataciones;

    //	PROVEEDORES >> SOLICITUDES
    var $sqlProveedoresSolicitudes;
    var $numerofilasProveedoresSolicitudes;
    var $maxfilasProveedoresSolicitudes;

    //  BUSCADORES >> MATERIALES
    var $sqlBuscadorMateriales;
    var $numerofilasBuscadorMateriales;
    var $maxfilasBuscadorMateriales;
    //  BUSCADORES >> HUSOS HORARIOS
    var $sqlBuscadorHusosHorarios;
    var $numerofilasBuscadorHusosHorarios;
    var $maxfilasBuscadorHusosHorarios;

    //  BUSCADORES >> FRASES H
    var $sqlBuscadorFrasesH;
    var $numerofilasBuscadorFrasesH;
    var $maxfilasBuscadorFrasesH;

    //  BUSCADORES >> APQ CLASES PELIGRO
    var $sqlBuscadorApqClasesPeligro;
    var $numerofilasBuscadorApqClasesPeligro;
    var $maxfilasBuscadorApqClasesPeligro;

    //BUSCADORES >> TERMINALES
    var $sqlTerminales;
    var $numerofilasTerminales;
    var $maxfilasTerminales;

    //  BUSCADORES >> APQ ANEXOS CLP
    var $sqlBuscadorApqAnexosClp;
    var $numerofilasBuscadorApqAnexosClp;
    var $maxfilasBuscadorApqAnexosClp;

    //  BUSCADORES >> APQ CATEGORÍAS
    var $sqlBuscadorApqCategorias;
    var $numerofilasBuscadorApqCategorias;
    var $maxfilasBuscadorApqCategorias;

    //  BUSCADORES >> APQ FRASES H CARACTERÍSTICAS
    var $sqlBuscadorApqFrasesHCaracteristicas;
    var $numerofilasBuscadorApqFrasesHCaracteristicas;
    var $maxfilasBuscadorApqFrasesHCaracteristicas;

    //  BUSCADORES >> APQ APLICACION REGLAMENTO CF
    var $sqlBuscadorApqAplicacionReglamentoCF;
    var $numerofilasBuscadorApqAplicacionReglamentoCF;
    var $maxfilasBuscadorAplicacionReglamentoCF;

    //	PEP CONSTRUCCION
    var $sqlAdminPepConstruccion;
    var $numerofilasPepConstruccion;
    var $maxfilasPepConstruccion;

    //	SECTORES
    var $sqlAdminPanelSolar;
    var $numerofilasPanelSolar;
    var $maxfilasPanelSolar;

    // RESERVAS
    var $sqlReservasReservas;
    var $numerofilasReservasReservas;
    var $maxfilasReservasReservas;

    //	DEMANDAS
    var $sqlReservasDemandas;
    var $numerofilasReservasDemandas;
    var $maxfilasReservasDemandas;

    // COLAs DE RESERVAS
    var $sqlReservasColas;
    var $numerofilasReservasColas;
    var $maxfilasReservasColas;

    /************************************** PDA **************************************/
    var $sqlPDA_Materiales;
    var $numerofilasPDA_Materiales;
    var $maxfilasPDA_Materiales;

    var $sqlPDA_MaterialesCentroFisico;
    var $numerofilasPDA_MaterialesCentroFisico;
    var $maxfilasPDA_MaterialesCentroFisico;

    /************************************ FIN PDA ************************************/


    function __construct()
    {
        $NumeroFilas       = 25;
        $this->limite      = "";
        $this->mostradas   = "";
        $this->numerofilas = "";
        $this->maxfilas    = $NumeroFilas;
        $this->Actualizar_Max_Filas();
    }

    function Actualizar_Max_Filas()
    {

        global $bd;

        $this->MAX_FILAS_GENERAL = $this->maxfilas;

        // ARTICULOS FAMILIAS
        $this->maxfilasAdminArticFa = $this->MAX_FILAS_GENERAL;
        // MAESTRO SOCIEDADES
        $this->maxfilasMaestroSociedad = $this->MAX_FILAS_GENERAL;
        // MAESTRO CENTROS
        $this->maxfilasMaestroCentro = $this->MAX_FILAS_GENERAL;
        // MAESTRO CENTROS FISICOS
        $this->maxfilasMaestroCentroFisico = $this->MAX_FILAS_GENERAL;
        // MAESTRO CENTROS FISICOS CATEGORIA
        $this->maxfilasMaestroCentroFisicoCategoria = $this->MAX_FILAS_GENERAL;
        // MAESTRO ALMACENES
        $this->maxfilasMaestroAlmacen = $this->MAX_FILAS_GENERAL;
        // MAESTRO INSTALACIONES
        $this->maxfilasMaestroInstalacion = $this->MAX_FILAS_GENERAL;
        // MAESTRO LINEAS CONTROL
        $this->maxfilasMaestroLineaControl = $this->MAX_FILAS_GENERAL;
        // MAESTRO MAQUINAS
        $this->maxfilasMaestroMaquina = $this->MAX_FILAS_GENERAL;
        // MAESTRO CLIENTES
        $this->maxfilasMaestroCliente = $this->MAX_FILAS_GENERAL;
        // MAESTRO PROVEEDORES
        $this->maxfilasMaestroProv = $this->MAX_FILAS_GENERAL;
        // MAESTRO MATERIALES
        $this->maxfilasMaestroMaterial = $this->MAX_FILAS_GENERAL;
        // MATERIALES FAMILIA MATERIAL
        $this->maxfilasFamiliaMaterial = 5; //CASO ESPECIFICO PARA ESTE BUSCADOR
        //	MATERIALES SOLICITUDES COINCICENCIAS
        $this->maxfilasMaestroMaterialSolicitudCoincidencias = $this->MAX_FILAS_GENERAL;
        // MAESTRO MATERIALES ALMACEN
        $this->maxfilasMaestroMaterialAlmacen = $this->MAX_FILAS_GENERAL;
        // MAESTRO MATERIALES CENTRO
        $this->maxfilasMaestroMaterialCentro = $this->MAX_FILAS_GENERAL;
        // MAESTRO UBICACIONES CENTRO FISICO
        $this->maxfilasMaestroUbicacionesCentroFisico = $this->MAX_FILAS_GENERAL;
        // MAESTRO MATERIALES CENTRO FISICO
        $this->maxfilasMaestroMaterialCentroFisico = $this->MAX_FILAS_GENERAL;
        // MAESTRO FAMILIAS REPRO
        $this->maxfilasMaestroFamiliaRepro = $this->MAX_FILAS_GENERAL;
        //MAESTRO FAMILIAS MATERIAL
        $this->maxfilasMaestroFamiliaMaterial = $this->MAX_FILAS_GENERAL;
        //MAESTRO FAMILIAS MATERIAL ASIGNAR
        $this->maxfilasMaestroFamiliaMaterialAsignar = $this->MAX_FILAS_GENERAL;
        //MAESTRO FAMILIAS MATERIAL IC
        $this->maxfilasMaestroFamiliaMaterialIC = $this->MAX_FILAS_GENERAL;
        //MAESTRO CODIGOS HS
        $this->maxfilasMaestroCodigoHS = $this->MAX_FILAS_GENERAL;
        //MAESTRO CATEGORIAS IC
        $this->maxfilasMaestroCategoriaIC = $this->MAX_FILAS_GENERAL;
        // MAESTRO TIPOS BLOQUEO
        $this->maxfilasMaestroTiposBloqueo = $this->MAX_FILAS_GENERAL;
        // MAESTRO MATERIALES SUSTITUTITVOS
        $this->maxfilasMaestroMaterialesSustitutivos = $this->MAX_FILAS_GENERAL;
        // MAESTRO DIRECCIONES ENTREGA PROVEEDORES
        $this->maxfilasMaestroDirsEntrProveedores = $this->MAX_FILAS_GENERAL;
        // MAESTRO UNIDADES
        $this->maxfilasMaestroUnidades = $this->MAX_FILAS_GENERAL;
        // MAESTRO UNIDADES
        $this->maxfilasMaestroReferenciasCruzadas = $this->MAX_FILAS_GENERAL;
        // MAESTRO UBICACIONES
        $this->maxfilasMaestroUbicaciones = $this->MAX_FILAS_GENERAL;
        // MAESTRO PLANIFICADORES
        $this->maxfilasMaestroPlanificador = $this->MAX_FILAS_GENERAL;
        // MAESTRO VEHICULOS
        $this->maxfilasMaestroVehiculos = $this->MAX_FILAS_GENERAL;
        // MAESTRO VEHICULOS STOCK COMPARTIDO
        $this->maxfilasMaestroVehiculosStockCompartido = $this->MAX_FILAS_GENERAL;
        // MAESTRO VEHICULOS TRANSPORTE
        $this->maxfilasMaestroVehiculosTransporte = $this->MAX_FILAS_GENERAL;
        // MAESTRO ZONAS
        $this->maxfilasMaestroZonas = $this->MAX_FILAS_GENERAL;
        // MAESTRO SUBZONAS
        $this->maxfilasMaestroSubzonas = $this->MAX_FILAS_GENERAL;
        // MAESTRO RUTAS
        $this->maxfilasMaestroRutas = $this->MAX_FILAS_GENERAL;
        // MAESTRO SUBRUTAS
        $this->maxfilasMaestroSubrutas = $this->MAX_FILAS_GENERAL;
        // MAESTRO CATEGORIA UBICACION
        $this->maxfilasMaestroCategoriaUbicacion = $this->MAX_FILAS_GENERAL;
        // MAESTRO CATEGORIA ALMACEN
        $this->maxfilasMaestroCategoriaAlmacen = $this->MAX_FILAS_GENERAL;
        // MAESTRO CATEGORIA FACTURACION GRAN COMPONENTE
        $this->maxfilasMaestroCategoriaFacturacionGranComponente = $this->MAX_FILAS_GENERAL;
        // MAESTRO CATEGORIA CENTRO FISICO
        $this->maxfilasMaestroCategoriaCentroFisico = $this->MAX_FILAS_GENERAL;
        // MAESTRO PAIS
        $this->maxfilasMaestroPais = $this->MAX_FILAS_GENERAL;
        // MAESTRO NUMEROS SERIE
        $this->maxfilasMaestroNumeroSerie = $this->MAX_FILAS_GENERAL;
        // MAESTRO LISTADO TAREAS MANTENIMIENTO
        $this->maxfilasMaestroListadoTareasMantenimiento = $this->MAX_FILAS_GENERAL;
        // MAESTRO FRASES H
        $this->maxfilasMaestroFrasesH = $this->MAX_FILAS_GENERAL;
        // MAESTRO APQ CLASES PELIGRO
        $this->maxfilasMaestroApqClasesPeligro = $this->MAX_FILAS_GENERAL;
        // MAESTRO APQ ANEXOS CLP
        $this->maxfilasMaestroApqAnexosClp = $this->MAX_FILAS_GENERAL;
        // MAESTRO APQ CATEGORÍAS
        $this->maxfilasMaestroApqCategorias = $this->MAX_FILAS_GENERAL;
        // MAESTRO APQ FRASES H CARACTERÍSTICAS
        $this->maxfilasMaestroApqFrasesHCaracteristicas = $this->MAX_FILAS_GENERAL;
        // MAESTRO APLICACION REGLAMENTO CF
        $this->maxfilasMaestroApqAplicacionReglamentoCF = $this->MAX_FILAS_GENERAL;
        // MAESTRO ADUANAS
        $this->maxfilasMaestroAduanas = $this->MAX_FILAS_GENERAL;
        // MAESTRO PATENTES
        $this->maxfilasMaestroPatentes = $this->MAX_FILAS_GENERAL;
        // MAESTRO EMPRESAS
        $this->maxfilasMaestroEmpresas = $this->MAX_FILAS_GENERAL;
        // MAESTRO ENTIDADES EXPEDIDORAS
        $this->maxfilasMaestroEntidadesExpedidoras = $this->MAX_FILAS_GENERAL;
        //MAESTRO GRUPOS_APQ
        $this->maxfilasMaestroGruposAPQ = $this->MAX_FILAS_GENERAL;
        // MAESTRO CLASES_ADR
        $this->maxfilasMaestroClaseADR = $this->MAX_FILAS_GENERAL;
        // MAESTRO CLASES_RG
        $this->maxfilasMaestroClaseRG = $this->MAX_FILAS_GENERAL;
        // MAESTRO ONU
        $this->maxfilasMaestroONU = $this->MAX_FILAS_GENERAL;
        // MAESTRO CLAVE APROVISIONAMIENTO ESPECIAL
        $this->maxfilasMaestroClaveAprovisionamientoEspecial = $this->MAX_FILAS_GENERAL;
        // MAESTRO ESTRUCTURA ORGANIZATIVA
        $this->maxfilasMaestroEstructuraOrganizativa = $this->MAX_FILAS_GENERAL;
        // MAESTRO TECNOLOGIA
        $this->maxfilasMaestroTecnologia = $this->MAX_FILAS_GENERAL;
        // MAESTRO TECNOLOGIA
        $this->maxfilasMaestroTecnologiaGenerica = $this->MAX_FILAS_GENERAL;
        // MAESTRO POTENCIA
        $this->maxfilasMaestroPotencia = $this->MAX_FILAS_GENERAL;
        // MAESTRO ALMACEN_INSTALACION
        $this->maxfilasMaestroAlmacenInstalacion = $this->MAX_FILAS_GENERAL;
        // MAESTRO INSTALACION_CLIENTE
        $this->maxfilasMaestroInstalacionCliente = $this->MAX_FILAS_GENERAL;
        // MAESTRO MEDIOS_DE_DESCARGA
        $this->maxfilasMaestroMediosDeDescarga = $this->MAX_FILAS_GENERAL;
        // MAESTRO ACCESIBILIDAD
        $this->maxfilasMaestroAccesibilidad = $this->MAX_FILAS_GENERAL;
        // MAESTROS PARAMETROS LOGISTICOS
        $this->maxfilasMaestroParametrosLogisticos = $this->MAX_FILAS_GENERAL;
        // MAESTROS MOTIVOS AJUSTE
        $this->maxfilasMaestroMotivosAjuste = $this->MAX_FILAS_GENERAL;
        // MAESTRO DICCIONARIO
        $this->maxfilasMaestroDiccionario = $this->MAX_FILAS_GENERAL;
        // MAESTRO CHOFERES
        $this->maxfilasMaestroChofer = $this->MAX_FILAS_GENERAL;
        // MAESTRO CONTENEDORES
        $this->maxfilasMaestroContenedores = $this->MAX_FILAS_GENERAL;
        // MAESTRO PUERTOS
        $this->maxfilasMaestroPuertos = $this->MAX_FILAS_GENERAL;
        // MAESTRO ENTIDAD BL
        $this->maxfilasMaestroEntidadBL = $this->MAX_FILAS_GENERAL;
        // MAESTRO TIPO EXTRACOSTE
        $this->maxfilasMaestroTipoExtracoste = $this->MAX_FILAS_GENERAL;
        // MAESTRO TIPO SECTOR
        $this->maxfilasMaestroTipoSector = $this->MAX_FILAS_GENERAL;
        // MAESTRO EXCEPCIONES DISTRIBUCION
        $this->maxfilasMaestroExcepcionesDistribucion = $this->MAX_FILAS_GENERAL;
        // MAESTRO EXCEPCIONES DISTRIBUCION TIPO
        $this->maxfilasMaestroExcepcionesDistribucionTipo = $this->MAX_FILAS_GENERAL;
        // MAESTRO INCOTERMS
        $this->maxfilasMaestroIncoterms = $this->MAX_FILAS_GENERAL;
        // MAESTRO MONEDAS
        $this->maxfilasMaestroMonedas = $this->MAX_FILAS_GENERAL;
        // MAESTRO INCIDENCIA SISTEMA TIPO
        $this->maxfilasMaestroIncidenciaSistemaTipo = $this->MAX_FILAS_GENERAL;
        // MAESTRO INCIDENCIA SISTEMA SUBTIPO
        $this->maxfilasMaestroIncidenciaSistemaSubtipo = $this->MAX_FILAS_GENERAL;
        // MAESTRO TAREA MANTENIMIENTO
        $this->maxfilasMaestroTareasMantenimiento = $this->MAX_FILAS_GENERAL;
        // MAESTRO TIPO TAREA MANTENIMIENTO
        $this->maxfilasMaestroTipoTareasMantenimiento = $this->MAX_FILAS_GENERAL;
        // MAESTRO DIRECCIONES
        $this->maxfilasMaestroDireccion = $this->MAX_FILAS_GENERAL;
        // MAESTRO TIPOS PEDIDO SAP
        $this->maxfilasMaestroTiposPedidoSAP = $this->MAX_FILAS_GENERAL;
        // MAESTRO ELEMENTOS INPUTACION
        $this->maxfilasMaestroElementosImputacion = $this->MAX_FILAS_GENERAL;
        // MAESTRO REFERENCIAS FACTURACION
        $this->maxfilasMaestroReferenciasFacturacion = $this->MAX_FILAS_GENERAL;
        // MAESTRO INTRODUCCION PESOS
        $this->maxfilasMaestroIntroduccionPesos = $this->MAX_FILAS_GENERAL;
        // MAESTRO INTRODUCCION PESOS
        $this->maxfilasMaestroParametrosRevisionPaneles = $this->MAX_FILAS_GENERAL;
        //MAESTRO REGLAS
        $this->maxfilasMaestroReglasDisponibilidadFechaPlanificadaOTs = $this->MAX_FILAS_GENERAL;
        //MAESTRO REGIONES
        $this->maxfilasMaestroRegiones = $this->MAX_FILAS_GENERAL;
        // MAESTRO TARIFAS
        $this->maxfilasMaestroTarifas = $this->MAX_FILAS_GENERAL;
        // MAESTRO CONTRATOS
        $this->maxfilasMaestroContratos = $this->MAX_FILAS_GENERAL;
        // MAESTRO CLAUSULAS
        $this->maxfilasMaestroClausulas = $this->MAX_FILAS_GENERAL;
        // MAESTRO SERVICIOS
        $this->maxfilasMaestroServicios = $this->MAX_FILAS_GENERAL;
        // MAESTRO CARGAS MASIVAS
        $this->maxfilasMaestroCargasMasivas = $this->MAX_FILAS_GENERAL;
        // MAESTRO REUBICACION
        $this->maxfilasMaestroReubicacion = $this->MAX_FILAS_GENERAL;
        //MAESTRO TIPOLOGIA_INCIDENCIA
        $this->maxfilasMaestroTipologiaIncidencia = $this->MAX_FILAS_GENERAL;
        //MAESTRO CORREOS
        $this->maxfilasMaestroCorreos = $this->MAX_FILAS_GENERAL;
        //MAESTRO FICHAS SEGURIDAD
        $this->maxfilasMaestroFichasSeguridad = $this->MAX_FILAS_GENERAL;
        //MAESTRO IDIOMAS
        $this->maxfilasMaestroIdiomas = $this->MAX_FILAS_GENERAL;
        //MAESTRO FALLOS
        $this->maxfilasMaestroFallos = $this->MAX_FILAS_GENERAL;
        //MAESTRO CAUSAS
        $this->maxfilasMaestroCausas = $this->MAX_FILAS_GENERAL;
        //MAESTRO SOLUCIONES
        $this->maxfilasMaestroSoluciones = $this->MAX_FILAS_GENERAL;
        //MAESTRO CAMPOS
        $this->maxfilasMaestroCampos = $this->MAX_FILAS_GENERAL;
        //MAESTRO CAMPOS
        $this->maxfilasMaestroCampoObjeto = $this->MAX_FILAS_GENERAL;
        //MAESTRO OBJETOS BLOCKCHAIN
        $this->maxfilasMaestroObjetosBlockchain = $this->MAX_FILAS_GENERAL;
        //MAESTRO TIPO MATERIAL
        $this->maxfilasMaestroTipoMaterial = $this->MAX_FILAS_GENERAL;
        //MAESTRO TIPO MATERIAL SAP
        $this->maxfilasMaestroTipoMaterialSap = $this->MAX_FILAS_GENERAL;
        //MAESTRO TIPO VEHICULO
        $this->maxfilasMaestroTipoVehiculo = $this->MAX_FILAS_GENERAL;
        //MAESTRO LEAD TIMES
        $this->maxfilasMaestroLeadTimes = $this->MAX_FILAS_GENERAL;
        //MAESTRO TIPO INCIDENCIA SISTEMA
        $this->maxfilasMaestroTipoIncidenciaSistema = $this->MAX_FILAS_GENERAL;
        //MAESTRO TIPO INCIDENCIA BLOCKCHAIN
        $this->maxfilasMaestroTipoIncidenciaBlockchain = $this->MAX_FILAS_GENERAL;
        //MAESTRO SUBTIPO INCIDENCIA SISTEMA
        $this->maxfilasMaestroSubTipoIncidenciaSistema = $this->MAX_FILAS_GENERAL;
        //MAESTRO TIPO ALERTA EXPEDITING
        $this->maxfilasMaestroTipoAlertaExpediting = $this->MAX_FILAS_GENERAL;
        //MAESTRO NAVIERA
        $this->maxfilasMaestroNaviera = $this->MAX_FILAS_GENERAL;
        //MAESTRO BARCO
        $this->maxfilasMaestroBarco = $this->MAX_FILAS_GENERAL;
        //MAESTRO ORDEN CONTRATACION
        $this->maxfilasMaestroContratacion = $this->MAX_FILAS_GENERAL;
        //MAESTRO PLANTILLA CALENDARIO DE FESTIVOS
        $this->maxfilasMaestroPlantillaCalendarioFestivos = $this->MAX_FILAS_GENERAL;
        //MAESTRO CALENDARIO DE FESTIVOS
        $this->maxfilasMaestroCalendarioFestivos = $this->MAX_FILAS_GENERAL;
        //MAESTRO VIAJES
        $this->maxfilasMaestroViajes = $this->MAX_FILAS_GENERAL;
        //MAESTRO VIAJES DETALLE
        $this->maxfilasMaestroViajesDetalle = $this->MAX_FILAS_GENERAL;
        //MAESTRO TIPOS DE WEB SERVICE
        $this->maxfilasMaestroTiposDeWebService = $this->MAX_FILAS_GENERAL;
        //MAESTROS >> TIPO_SECTOR
        $this->maxfilasConstruccionTipoSector = $this->MAX_FILAS_GENERAL;
        // MAESTROS >> GRUPOS DE DEMANDAS
        $this->maxfilasMaestroGruposDemanda = $this->MAX_FILAS_GENERAL;
        // MAESTROS >> GRUPOS DE DEMANDAS
        $this->maxfilasMaestroLanzamientoInterfaces = $this->MAX_FILAS_GENERAL;
        // MAESTRO GRUPO COMPRA
        $this->maxfilasMaestroGrupoCompra = $this->MAX_FILAS_GENERAL;
        //MAESTRO PRIORIDAD INVENTARIO
        $this->maxfilasMaestroPrioridadInventario = $this->MAX_FILAS_GENERAL;
        // MAESTRO >> SOLICITUD_MATERIAL
        $this->maxfilasMaestroSolicitudMaterial = $this->MAX_FILAS_GENERAL;
        // MAESTRO >> SOLICITUD_MATERIAL_IC
        $this->maxfilasMaestroSolicitudMaterialIC = $this->MAX_FILAS_GENERAL;
        // MAESTRO >> SOLICITUD_MATERIAL_SERVICIOS
        $this->maxfilasMaestroSolicitudMaterialServicios = $this->MAX_FILAS_GENERAL;
        // MAESTRO >> SOLICITUD SUSTITUTIVO
        $this->maxfilasMaestroSolicitudSustitutivo = $this->MAX_FILAS_GENERAL;

        //CODIFICACION >> INCIDENCIAS CODIFICACION
        $this->maxfilasCodificacionInformesIncidencias = $this->MAX_FILAS_GENERAL;
        //CODIFICACION >> INCIDENCIAS CODIFICACION
        $this->maxfilasCodificacionOperacionesAcciones = $this->MAX_FILAS_GENERAL;
        //MAESTROS >> APROBADORES
        $this->maxfilasMaestroAprobadores = $this->MAX_FILAS_GENERAL;
        //MAESTRO DICCIONARIO
        $this->maxfilasAdminDiccionario = $this->MAX_FILAS_GENERAL;

        //BLOCKCHAIN
        $this->maxfilasBlockchainBuscadorCampos = $this->MAX_FILAS_GENERAL;
        $this->maxfilasBlockchainBuscadorDocumentos = $this->MAX_FILAS_GENERAL;

        //BLOCKCHAIN
        $this->maxfilasBlockchainBuscadorCampos = $this->MAX_FILAS_GENERAL;
        $this->maxfilasBlockchainBuscadorDocumentos = $this->MAX_FILAS_GENERAL;

        //BLOCKCHAIN
        $this->maxfilasBlockchainBuscadorCampos = $this->MAX_FILAS_GENERAL;
        $this->maxfilasBlockchainBuscadorDocumentos = $this->MAX_FILAS_GENERAL;

        // ENTRADAS RECECPCIONES
        $this->maxfilasEntradasRecepciones = $this->MAX_FILAS_GENERAL;
        // ENTRADAS RECECPCIONES CONTAINERS
        $this->maxfilasEntradasRecepcionesContainers = $this->MAX_FILAS_GENERAL;
        // ENTRADAS RECECPCIONES CONTAINERS
        $this->maxfilasEntradasContenedoresEntrantes = $this->MAX_FILAS_GENERAL;
        // ENTRADAS MOVIMIENTOS
        $this->maxfilasEntradasMovimientos = $this->MAX_FILAS_GENERAL;
        // ENTRADAS PEDIDOS
        $this->maxfilasEntradasPedidos = $this->MAX_FILAS_GENERAL;
        // ENTRADAS RECEPCION PEDIDOS TRASLADO
        $this->maxfilasEntradasRecepcionPedidosTraslado = $this->MAX_FILAS_GENERAL;
        // ENTRADAS CONTROL RECEPCION
        $this->maxfilasEntradasControlRecepcion = $this->MAX_FILAS_GENERAL;
        // ENTRADAS CONTROL GRAN COMPONENTE
        $this->maxfilasEntradasControlGranComponente = $this->MAX_FILAS_GENERAL;
        // ENTRADAS ANULACIONES
        $this->maxfilasEntradasAnulaciones = $this->MAX_FILAS_GENERAL;
        // ENTRADAS CONTROL CODIFICACION
        $this->maxfilasEntradasControlCodificacion = $this->MAX_FILAS_GENERAL;
        // ENTRADAS SIN PEDIDO
        $this->maxfilasEntradasSinCompra = $this->MAX_FILAS_GENERAL;
        // ENTRADAS CARROS
        $this->maxfilasEntradasCarros = $this->MAX_FILAS_GENERAL;
        //CONSTRUCCION  ORDEN MONTAJE
        $this->maxfilasConstruccionOrdenMontaje = $this->MAX_FILAS_GENERAL;
        //CONSTRUCCION  UOP
        $this->maxfilasConstruccionUnidadOrganizativaProceso = $this->MAX_FILAS_GENERAL;
        //CONSTRUCCION  PARAMETROS
        $this->maxfilasConstruccionParametros = $this->MAX_FILAS_GENERAL;
        //CONSTRUCCION  FALTANTES
        $this->maxfilasConstruccionFaltantes = $this->MAX_FILAS_GENERAL;
        //CONSTRUCCION  ORDEN MONTAJE AVISOS
        $this->maxfilasConstruccionOrdenMontajeAvisos = $this->MAX_FILAS_GENERAL;
        //CONSTRUCCION  OPERACIONES
        $this->maxfilasContruccionOperaciones = $this->MAX_FILAS_GENERAL;
        //CONSTRUCCION  ACTIVIDADES
        $this->maxfilasContruccionActividades = $this->MAX_FILAS_GENERAL;
        //CONSTRUCCION  FASES
        $this->maxfilasContruccionFases = $this->MAX_FILAS_GENERAL;
        //CONSTRUCCION  SUBFASES
        $this->maxfilasConstruccionSubFases = $this->MAX_FILAS_GENERAL;
        //CONSTRUCCION  FASES INSTALACION
        $this->maxfilasContruccionFasesInstalacion = $this->MAX_FILAS_GENERAL;
        //CONSTRUCCION  SUBFASES INSTALACION
        $this->maxfilasConstruccionSubFasesInstalacion = $this->MAX_FILAS_GENERAL;
        //CONSTRUCCION  ETAPAS
        $this->maxfilasConstruccionEtapas = $this->MAX_FILAS_GENERAL;
        //CONSTRUCCION SUBETAPAS
        $this->maxfilasConstruccionSubEtapas = $this->MAX_FILAS_GENERAL;
        //CONSTRUCCION  ETAPAS INSTALACION
        $this->maxfilasConstruccionEtapasInstalacion = $this->MAX_FILAS_GENERAL;
        //CONSTRUCCION  ETAPAS INSTALACION
        $this->maxfilasConstruccionSubEtapasInstalacion = $this->MAX_FILAS_GENERAL;
        //CONSTRUCCION  ACTIVIDADES INSTALACION
        $this->maxfilasConstruccionActividadesInstalacion = $this->MAX_FILAS_GENERAL;
        //CONSTRUCCION ESTRUCTURA
        $this->maxfilasConstruccionEstructuraOrganizativa = $this->MAX_FILAS_GENERAL;

        //CONSTRUCCION  OPERACION INSTALACION
        $this->maxfilasContruccionOperacionesInstalacion = $this->MAX_FILAS_GENERAL;
        //CONSTRUCCION  INFORME SITUACION
        $this->maxfilasContruccionInformeSituacion = 500;//CASO ESPECIAL PARA QUE SE MUESTREN TODAS LAS MAQUINAS
        //CONSTRUCCION  LAYOUT MAQUINAS
        $this->maxfilasLayoutMaquinas = $this->MAX_FILAS_GENERAL;
        //TRANSPORTE CONSTRUCCION  UNIDAD TRANSPORTE
        $this->maxfilasTransporteConstruccionUnidadTransporte = $this->MAX_FILAS_GENERAL;
        //TRANSPORTE CONSTRUCCION  RETENCION
        $this->maxfilasTransporteConstruccionRetenciones = $this->MAX_FILAS_GENERAL;
        //TRANSPORTE CONSTRUCCION  HITOS
        $this->maxfilasTransporteConstruccionHitos = $this->MAX_FILAS_GENERAL;
        //TRANSPORTE CONSTRUCCIï¿½N EMBARQUE
        $this->maxfilasTransporteConstruccionEmbarques = $this->MAX_FILAS_GENERAL;
        //TRANSPORTE CONSTRUCCION  AVISOS
        $this->maxfilasTransporteConstruccionAvisos = $this->MAX_FILAS_GENERAL;
        //TRANSPORTE CONSTRUCCION  AVISOS MASIVOS
        $this->maxfilasTransporteConstruccionAvisosMasivos = $this->MAX_FILAS_GENERAL;
        //TRANSPORTE CONSTRUCCION CRONOGRAMA
        $this->maxfilasTransporteConstruccionCronograma = $this->MAX_FILAS_GENERAL;
        //TRANSPORTE CONSTRUCCION CRONOGRAMA EMBARQUES
        $this->maxfilasTransporteConstruccionCronogramaEmbarques = $this->MAX_FILAS_GENERAL;
        //TRANSPORTE CONSTRUCCION PLAN PROYECTO
        $this->maxfilasTransporteConstruccionPlanProyecto = $this->MAX_FILAS_GENERAL;
        //TRANSPORTE CONSTRUCCION MÓDULOS
        $this->maxfilasTransporteConstruccionModulo = $this->MAX_FILAS_GENERAL;
        //TRANSPORTE CONSTRUCCION PEDIDOS CONTRATO LÍNEA
        $this->maxfilasTransporteConstruccionLineaPedido = $this->MAX_FILAS_GENERAL;
        //TRANSPORTE CONSTRUCCION  SDPS
        $this->maxfilasTransporteConstruccionSdps = $this->MAX_FILAS_GENERAL;
        //TRANSPORTE CONSTRUCCION  NCS
        $this->maxfilasTransporteConstruccionNCs = $this->MAX_FILAS_GENERAL;
        //TRANSPORTE CONSTRUCCION PEDIDOS CONTRATO
        $this->maxfilasTransporteConstruccionPedido = $this->MAX_FILAS_GENERAL;
        //TRANSPORTE CONSTRUCCION CONFIGURACION PERIODICIDAD
        $this->maxfilasRegistroBlockchainConfiguracionPeriodicidad = $this->MAX_FILAS_GENERAL;
        //TRANSPORTE CONSTRUCCION INFORMES BLOCKCHAIN
        $this->maxfilasRegistroBlockchainInformesBlockchain = $this->MAX_FILAS_GENERAL;
        //TRANSPORTE CONSTRUCCION LOG ACCIONES
        $this->maxfilasRegistroBlockchainLogAcciones = $this->MAX_FILAS_GENERAL;
        //TRANSPORTE CONSTRUCCION REGISTRO CHATS
        $this->maxfilasRegistroBlockchainRegistroChats = $this->MAX_FILAS_GENERAL;
        //TRANSPORTE CONSTRUCCION  EMBARQUES GC
        $this->maxfilasTransporteConstruccionEmbarques = $this->MAX_FILAS_GENERAL;
        //TRANSPORTE CONSTRUCCION  INTEGRIDAD OTS
        $this->maxfilasTransporteConstruccionIntegridadOTs = $this->MAX_FILAS_GENERAL;
        //TRANSPORTE CONSTRUCCION  INTEGRIDAD CONTRATACIONES
        $this->maxfilasTransporteConstruccionIntegridadContrataciones = $this->MAX_FILAS_GENERAL;
        //TRANSPORTE CONSTRUCCION  ESTADO OTS
        $this->maxfilasTransporteConstruccionEstadoOTs = $this->MAX_FILAS_GENERAL;
        //TRANSPORTE CONSTRUCCION  DESVIOS PLANIFICACION
        $this->maxfilasTransporteConstruccionDesviosPlanificacion = $this->MAX_FILAS_GENERAL;
        //CONSTRUCCION SOLAR CONTROL DE CALIDAD
        $this->maxfilasConstruccionSolarControlCalidad = $this->MAX_FILAS_GENERAL;
        //CONSTRUCCION SOLAR LOTES FABRICACION
        $this->maxfilasConstruccionSolarLotesFabricacion = $this->MAX_FILAS_GENERAL;
        //CONSTRUCCION SOLAR PERMANENCIA STOCK
        $this->maxfilasConstruccionSolarPermanenciaStock = $this->MAX_FILAS_GENERAL;
        //CONSTRUCCION SOLAR NUMERO LOTE
        $this->maxfilasConstruccionSolarNumeroLote = $this->MAX_FILAS_GENERAL;

        // SALIDAS PEDIDOS
        $this->maxfilasSalidasPedidos = $this->MAX_FILAS_GENERAL;
        // SALIDAS MOVIMIENTOS
        $this->maxfilasSalidasMovimientos = $this->MAX_FILAS_GENERAL;
        // SALIDAS ORDENES PREPARACION
        $this->maxfilasSalidasOrdenesPreparacion = $this->MAX_FILAS_GENERAL;
        // SALIDAS ORDENES CARGA
        $this->maxfilasSalidasOrdenesCarga = $this->MAX_FILAS_GENERAL;
        // SALIDAS EXPEDICIONES
        $this->maxfilasSalidasExpediciones = $this->MAX_FILAS_GENERAL;
        // SALIDAS GESTION_FACTURAS
        $this->maxfilasSalidasGestionFacturas = $this->MAX_FILAS_GENERAL;
        // SALIDAS DECISION CANAL ENTREGA
        $this->maxfilasDecisionCanalEntrega = $this->MAX_FILAS_GENERAL;
        // TRANSPORTE ORDENES TRANSPORTE
        $this->maxfilasTransporteOrdenesTransporte = $this->MAX_FILAS_GENERAL;
        // TRANSPORTE ORDENES CONTRATACION
        $this->maxfilasTransporteOrdenesContratacion = $this->MAX_FILAS_GENERAL;
        // TRANSPORTE AUTOFACTURAS
        $this->maxfilasTransporteAutofacturas = $this->MAX_FILAS_GENERAL;
        // TRANSPORTE SOLICITUDES
        $this->maxfilasTransporteSolicitudes = $this->MAX_FILAS_GENERAL;
        // TRANSPORTE SOLICITUDES PROVEEDOR
        $this->maxfilasTransporteSolicitudesProveedor = $this->MAX_FILAS_GENERAL;
        // TRANSPORTE SOLICITUDES TERCEROS
        $this->maxfilasTransporteSolicitudesTerceros = $this->MAX_FILAS_GENERAL;
        // ALMACEN ETIQUETAS PENDIENTES
        $this->maxfilasAlmacenEtiquetasPendientes = $this->MAX_FILAS_GENERAL;
        // ALMACEN CONSOLIDACION AGM
        $this->maxfilasAlmacenConsolidacionAGM = $this->MAX_FILAS_GENERAL;
        // SALIDAS BULTOS
        $this->maxfilasSalidasBultos = $this->MAX_FILAS_GENERAL;
        // SALIDAS CONTROL FACTURAS
        $this->maxfilasSalidasControlFacturas = $this->MAX_FILAS_GENERAL;
        // ADMINISTRACION PERFILES
        $this->maxfilasAdminAdmiPerf = $this->MAX_FILAS_GENERAL;
        // ADMINISTRACION USUARIOS
        $this->maxfilasAdminAdmiUsuarios = $this->MAX_FILAS_GENERAL;
        // TERMINALES
        $this->maxfilasTerminales = $this->MAX_FILAS_GENERAL;
        // ADMINISTRACION BLOQUEOS
        $this->maxfilasAdminAdmiBloqueos = $this->MAX_FILAS_GENERAL;
        // ADMINISTRACION TECNICOS MANTENIMIENTO
        $this->maxfilasAdminAdmiTecnicosMantenimiento = $this->MAX_FILAS_GENERAL;
        // ADMINISTRACION ACCESOS
        $this->maxfilasAdminAdmiAccesos = $this->MAX_FILAS_GENERAL;
        // ADMINISTRACION ACCESOS
        $this->maxfilasAdminAdmiWSLog = $this->MAX_FILAS_GENERAL;
        // ADMINISTRACION ACCESOS
        $this->maxfilasAdminAdmiLogBlockchain = $this->MAX_FILAS_GENERAL;
        // ADMINISTRACION TRAZABILIDAD
        $this->maxfilasAlmacenTrazabilidad = $this->MAX_FILAS_GENERAL;
        // ADMINISTRACION MENU
        $this->maxfilasAdminMenu = $this->MAX_FILAS_GENERAL;
        // ALMACEN STOCK POR UBICACION
        $this->maxfilasAlmacenStockPorUbicacion = $this->MAX_FILAS_GENERAL;
        // ALMACEN STOCK DISPONIBLE
        $this->maxfilasAlmacenStockDisponible = $this->MAX_FILAS_GENERAL;
        // ALMACEN STOCK EXTERNALIZADO
        $this->maxfilasAlmacenStockExternalizado = $this->MAX_FILAS_GENERAL;
        // ALMACEN ASIENTOS
        $this->maxfilasAlmacenAsientos = $this->MAX_FILAS_GENERAL;
        // ALMACEN >> AJUSTES DE INVENTARIOS
        $this->maxfilasAlmacenAjusteInventarios = $this->MAX_FILAS_GENERAL;
        // ALMACEN TRANSFERENCIAS
        $this->maxfilasAlmacenTransferencias = $this->MAX_FILAS_GENERAL;
        // ALMACEN TRASLADO DIRECTO
        $this->maxfilasAlmacenTrasladosDirectos = $this->MAX_FILAS_GENERAL;
        // ALMACEN TAREAS PENDIENTES
        $this->maxfilasAlmacenTareasPendientes = $this->MAX_FILAS_GENERAL;
        // ALMACEN OPERACIONES ESPECIALES
        $this->maxfilasAlmacenCambioEstado = $this->MAX_FILAS_GENERAL;
        //	ALMACEN >> ORDENES CONTEO
        $this->maxfilasAlmacenOrdenConteo = $this->MAX_FILAS_GENERAL;
        //	ALMACEN >> ORDENES TRANSFERENCIA
        $this->maxfilasAlmacenOrdenTransferencia = $this->MAX_FILAS_GENERAL;
        // ALMACEN >> PROPUESTA REUBICACION
        $this->maxfilasAlmacenPropuestaReubicacion = $this->MAX_FILAS_GENERAL;


        //	OPERACIONES ESPECIALES >> CAMBIO NUMERO SERIE
        $this->maxfilasOperacionesEspecialesCambioNumeroSerie = $this->MAX_FILAS_GENERAL;

        //	OPERACIONES ESPECIALES >> CAMBIO REFERENCIA
        $this->maxfilasOperacionesEspecialesCambioReferencia = $this->MAX_FILAS_GENERAL;

        // ORDENES TRABAJO >> ORDENES TRABAJO
        $this->maxfilasOrdenesTrabajoOrdenesTrabajo = $this->MAX_FILAS_GENERAL;
        // ORDENES TRABAJO >> OT´s DE PLANIFICADOS
        $this->maxfilasOrdenesTrabajoOTsDePlanificados = $this->MAX_FILAS_GENERAL;
        // ORDENES TRABAJO >> OT´s CON PENDIENTES
        $this->maxfilasOrdenesTrabajoOTsConPendientes = $this->MAX_FILAS_GENERAL;
        // ORDENES TRABAJO >> PENDIENTES
        $this->maxfilasOrdenesTrabajoPendientes = $this->MAX_FILAS_GENERAL;
        // ORDENES TRABAJO >> TRANSFERENCIAS DE PENDIENTES
        $this->maxfilasOrdenesTrabajoTransferenciasDePendientes = $this->MAX_FILAS_GENERAL;
        // ORDENES TRABAJO >> JOB PLAN
        $this->maxfilasOrdenesTrabajoJobPlan = $this->MAX_FILAS_GENERAL;
        // ORDENES TRABAJO >> GAMAS
        $this->maxfilasOrdenesTrabajoGamas = $this->MAX_FILAS_GENERAL;
        // ORDENES TRABAJO >> ORDENES TRABAJO MATERIAL NUMEROS DE SERIE
        $this->maxfilasOrdenesTrabajoOrdenesTrabajoMaterialNumeroSerie = $this->MAX_FILAS_GENERAL;
        // ORDENES TRABAJO >> ORDENES TRABAJO SGA
        $this->maxfilasOrdenesTrabajoOrdenesTrabajoSGA = $this->MAX_FILAS_GENERAL;
        // ORDENES TRABAJO >> MOVIMIENTOS VEHICULOS
        $this->maxfilasOrdenesTrabajoMovimientosVehiculos = $this->MAX_FILAS_GENERAL;
        // ORDENES TRABAJO >> OPERACIONES MASIVAS
        $this->maxfilasOrdenesTrabajoOperacionesMasivas = $this->MAX_FILAS_GENERAL;

        // LOGISTICA INVERSA >> ENVIO MATERIAL A PROVEEDOR
        $this->maxfilasLogisticaInversaEnvioMaterialAProveedor = $this->MAX_FILAS_GENERAL;
        // LOGISTICA INVERSA >> ENVIO MATERIAL A PROVEEDOR
        $this->maxfilasLogisticaInversaMaterialEstropeado = $this->MAX_FILAS_GENERAL;
        // LOGISTICA INVERSA >> ENVIO MATERIAL A ALMACEN PRINCIPAL
        $this->maxfilasLogisticaInversaEnvioMaterialAAlmacenPrincipal = $this->MAX_FILAS_GENERAL;
        // LOGISTICA INVERSA >> MATERIAL PENDIENTE RECEPCIONAR
        $this->maxfilasLogisticaInversaMaterialPendienteRecepcionar = $this->MAX_FILAS_GENERAL;
        //	LOGISTICA INVERSA >> MATERIAL RECEPCIONADO
        $this->maxfilasLogisticaInversaMaterialRecepcionado = $this->MAX_FILAS_GENERAL;
        //	LOGISTICA INVERSA >> REPARACIONES INTERNAS
        $this->maxfilasLogisticaInversaReparacionesInternas = $this->MAX_FILAS_GENERAL;
        //	LOGISTICA INVERSA >> ROADMAP REPARACIONES MULTIPROVEEDOR
        $this->maxfilasLogisticaInversaRoadmapReparacionesMultiproveedor = $this->MAX_FILAS_GENERAL;
        //	LOGISTICA INVERSA >> REPARACIONES
        $this->maxfilasLogisticaInversaReparaciones = $this->MAX_FILAS_GENERAL;

        // INVENTARIOS >> INVENTARIOS
        $this->maxfilasInventariosInventarios = $this->MAX_FILAS_GENERAL;
        // INVENTARIOS >> ORDENES DE CONTEO
        $this->maxfilasInventariosOrdenesConteo = $this->MAX_FILAS_GENERAL;

        // CALIDAD >> CONTROL CALIDAD
        $this->maxfilasCalidadControlCalidad = $this->MAX_FILAS_GENERAL;
        // CALIDAD >> INCIDENCIAS
        $this->maxfilasCalidadIncidencias = $this->MAX_FILAS_GENERAL;
        // CALIDAD >> NCS
        $this->maxfilasCalidadNCs = $this->MAX_FILAS_GENERAL;
        // CALIDAD >> MATERIAL NO CONFORME
        $this->maxfilasCalidadMaterialNoConforme = $this->MAX_FILAS_GENERAL;
        // CALIDAD >> MATERIAL NO CONFORME PENDIENTE RECEPCIONAR
        $this->maxfilasCalidadMaterialNoConformePendienteRecepcionar = $this->MAX_FILAS_GENERAL;
        //	CALIDAD >> RECEPCION MATERIAL NO CONFORME
        $this->maxfilasCalidadRecepcionMaterialNoConforme = $this->MAX_FILAS_GENERAL;
        //	CALIDAD >> CONSULTA ENVIOS MATERIAL NO CONFORME
        $this->maxfilasCalidadConsultaEnviosMaterialNoConforme = $this->MAX_FILAS_GENERAL;
        //	CALIDAD >> CONSULTA GARANTIA MATERIAL
        $this->maxfilasCalidadGarantiaMaterial = $this->MAX_FILAS_GENERAL;

        //	INFORMES >> INFORME CALIDAD SERVICIO
        $this->maxfilasInformesInformeCalidadServicio = $this->MAX_FILAS_GENERAL;
        //	INFORMES >> TRATAMIENTO AUTOMATICO OTS
        $this->maxfilasInformesTratamientoAutomaticoOTs = $this->MAX_FILAS_GENERAL;
        //	INFORMES >> COLA INTERFACES CODIFICACION
        $this->maxfilasInformesColaInterfacesCodificacion = $this->MAX_FILAS_GENERAL;
        //	INFORMES >> RECEPCIONES CON PROBLEMAS
        $this->maxfilasInformesRecepcionesConProblemas = $this->MAX_FILAS_GENERAL;
        //	INFORMES >> ALERTAS APQ
        $this->maxfilasInformesAlertasAPQ = $this->MAX_FILAS_GENERAL;
        //	INFORMES >> INFORME ADR
        $this->maxfilasInformesInformeADR = $this->MAX_FILAS_GENERAL;
        //	INFORMES >> REPOSICIONES SPV
        $this->maxfilasInformesReposicionesSPV = $this->MAX_FILAS_GENERAL;
        //	INFORMES >> REPOSICIONES SPV
        $this->maxfilasInformesControlTiempoRevision = $this->MAX_FILAS_GENERAL;
        //	INFORMES >> TRANSACCIONES
        $this->maxfilasInformesTransacciones = $this->MAX_FILAS_GENERAL;
        //	INFORMES >> CONSTRUCCION
        $this->maxfilasInformesConstruccion = $this->MAX_FILAS_GENERAL;
        //	INFORMES >> REPARACIÓN
        $this->maxfilasInformesInformeReparacion = $this->MAX_FILAS_GENERAL;

        // PROBLEMAS >> MATERIALES ALMACEN
        $this->maxfilasProblemasMaterialAlmacen = $this->MAX_FILAS_GENERAL;
        // PROBLEMAS >> PEDIDOS COMPRA
        $this->maxfilasProblemasPedidosCompra = $this->MAX_FILAS_GENERAL;
        // PROBLEMAS >> RELEVANCIA PEDIDOS
        $this->maxfilasProblemasRelevanciaPedidos = $this->MAX_FILAS_GENERAL;
        // PROBLEMAS >> INCIDENCIAS SISTEMA
        $this->maxfilasProblemasIncidenciasSistema = $this->MAX_FILAS_GENERAL;

        // PROBLEMAS >> INCIDENCIAS SISTEMA
        $this->maxfilasProblemasIncidenciasBlockchain = $this->MAX_FILAS_GENERAL;

        //	NECESIDADES >> NECESIDADES
        $this->maxfilasNecesidadesNecesidades = $this->MAX_FILAS_GENERAL;

        //	PROVEEDORES >> CONTRATACIONES
        $this->maxfilasProveedoresContrataciones = $this->MAX_FILAS_GENERAL;

        //	PROVEEDORES >> SOLICITUDES
        $this->maxfilasProveedoresSolicitudes = $this->MAX_FILAS_GENERAL;

        //	BUSCADORES >> MATERIALES
        $this->maxfilasBuscadorMateriales = $this->MAX_FILAS_GENERAL;

        //BUSCADORES >> HUSOS HORARIOS
        $this->maxfilasBuscadorHusosHorarios = $this->MAX_FILAS_GENERAL;

        //BUSCADORES >> FRASES H
        $this->maxfilasBuscadorFrasesH = 100; // ESPECÍFICO PARA ESTE BUSCADOR

        //BUSCADORES >> APQ CLASE PELIGRO
        $this->maxfilasBuscadorApqClasesPeligro = 50; // ESPECÍFICO PARA ESTE BUSCADOR

        //BUSCADORES >> APQ ANEXO CLP
        $this->maxfilasBuscadorApqAnexosClp = 50; // ESPECÍFICO PARA ESTE BUSCADOR

        //BUSCADORES >> APQ CATEGORÍA
        $this->maxfilasBuscadorApqCategorias = 50; // ESPECÍFICO PARA ESTE BUSCADOR

        //BUSCADORES >> APQ FRASES H CARACTERÍSTICAS
        $this->maxfilasBuscadorApqFrasesHCaracteristicas = 100; // ESPECÍFICO PARA ESTE BUSCADOR

        // PEP CONSTRUCCION
        $this->maxfilasPepConstruccion = $this->MAX_FILAS_GENERAL;

        // PANEL SOLAR
        $this->maxfilasPanelSolar = $this->MAX_FILAS_GENERAL;

        // RESERVAS
        $this->maxfilasReservasReservas = $this->MAX_FILAS_GENERAL;

        // DEMANDA
        $this->maxfilasReservasDemandas = $this->MAX_FILAS_GENERAL;

        // COLAS DE RESERVAS
        $this->maxfilasReservasColas = $this->MAX_FILAS_GENERAL;

        //	PDA >> MATERIALES
        $this->maxfilasPDA_Materiales = $this->MAX_FILAS_GENERAL;

        //	PDA >> MATERIALES CENTRO FISICO
        $this->maxfilasPDA_MaterialesCentroFisico = $this->MAX_FILAS_GENERAL;
    }

//	//FUNCION SQL MODIFICADA PARA ACOTAR NUMERO DE RESULTADOS DESDE LA PRIMERA LLAMADA
//	function Sql($sql,&$maxfilas,&$numerofilas){
//		global $mostradas;
//		global $limite;
//		global $resultado;
//		global $maxahora;
//		global $pagina_act;
//		global $bd;
//		global $error;
//		global $ordenar_campo;
//		global $sent_ord;
//
//		// ELIMINACION DE SLASHES
//		global $eliminarslashes;
//		if(!isset($eliminarslashes)):
//			$eliminarslashes=true;
//		endif;
//		if($eliminarslashes):
//			$qr=stripslashes($sql);
//		else:
//		   $qr=$sql;
//		endif;
//
//		//ORDENACION DE COLUMNAS
//		if ($ordenar_campo<>""):
//			$qr = "$qr ORDER BY $ordenar_campo";
//			if ($sent_ord==1) $qr = "$qr DESC";
//		endif;
//		$this->copiaExport = $qr;
//
//		if ($pagina_act != ""): // HAYO LA SENTENCIA CON EL LIMITE ACTUALIZADO
//			//if ($maxfilas != 0):
//				$valor1 = $maxfilas * $pagina_act;
//				$limite = " limit $valor1,$maxfilas ";
//			//endif;
//
//			$qr=$qr." ".stripslashes($limite);
//			$error="NO";
//		else: 	// SI ES LA PRIMERA VEZ QUE SE EJECUTA, NO TIENE PAGINA ACTUAL
//				// HAYO EL NUMERO TOTAL DE FILAS
//			$sqlCount = trim($sql);
//			$sqlCount = preg_replace('/SELECT/', 'SELECT SQL_CALC_FOUND_ROWS ', $sqlCount, 1);
//			$sqlCount.= " LIMIT 1 ";
//			$bd->ExecSQL($sqlCount);
//			$sqlNumRows = "SELECT FOUND_ROWS() AS NUM";
//			$resTotales = $bd->ExecSQL($sqlNumRows);
//			$row = $bd->SigReg($resTotales);
//			$numerofilas = $row->NUM;
//
//			$pagina_act=0;
//			$valor1 = 0;
//
//			//if ($maxfilas != 0):
//				$limite = " limit $valor1,$maxfilas ";
//			//else:
//			//	$limite = "";
//			//endif;
//
//			$qr=$qr." ".stripslashes($limite);
//			$error="NO";
//		endif;
//
//		//if ($maxfilas != 0):
//			$mostradas=$pagina_act*$maxfilas;
//		//else:
//		//	$mostradas = 0;
//		//endif;
//
//		// EJECUTO LA SENTENCIA SQL
//		$resultado= $bd->ExecSQL($qr);
//
//		//if ($maxfilas != 0):
//			$maxahora=$maxfilas;
//			if ($mostradas+$maxahora>$numerofilas): // HAY MENOS DE maxfilas
//				$maxahora=$numerofilas-$mostradas;
//			endif;
//		//else:
//		//	$maxahora=$numerofilas;
//		//endif;
//
//		// EN maxahora TENGO EL NUMERO DE FILAS A MOSTRAR EN ESTA PAGINA
//		// AQUI SEA UN TIPO DE BUSQUEDA U OTRO TENDRE HECHA YA LA SENTENCIA SQL
//		// Y LO QUE VIENE A CONTINUACION ES IGUAL PARA TODO TIPO DE BUSQUEDAS
//
//	} // Fin Sql


    function Sql($sql, &$maxfilas, &$numerofilas, $basedatos = NULL)
    {
        global $mostradas;//echo "mostrada=$mostradas ";//sin valor
        global $limite;
        global $resultado;
        global $maxahora;//echo "maxaho=$maxahora ";// sin valor
        global $pagina_act;
        global $error;
        global $ordenar_campo;
        global $sent_ord;
        global $administrador;

        if ($basedatos == NULL):
            global $bd;
        else:
            $bd = $basedatos;
        endif;

        $qr = $sql;

        //ORDENACION DE COLUMNAS
        if ($ordenar_campo <> ""):
            $qr = "$qr ORDER BY $ordenar_campo";
            if ($sent_ord == 1) $qr = "$qr DESC";
        endif;

        $this->copiaExport = $qr;

        if ($pagina_act != "" || $pagina_act === 0): // HAYO LA SENTENCIA CON EL LIMITE ACTUALIZADO
            $valor1 = $maxfilas * $pagina_act;
            $limite = " limit $valor1,$maxfilas ";

            $qr = $qr . " " . stripslashes( (string)$limite);
            //echo $qr;
            $error = "NO";
        else:
            $pagina_act = 0;
        endif;
        $mostradas = $pagina_act * $maxfilas;
        // EJECUTO LA SENTENCIA SQL
        $resultado = $bd->ExecSQL($qr);

        if (!$limite):  // HAYO EL NUMERO TOTAL DE FILAS
            $numerofilas = $bd->NumRegs($resultado);
            // NUMERO DE FILAS MOSTRADAS HASTA AHORA
            $mostradas = 0;
        else:
            $mostradas = $valor1;
        endif;

        $maxahora = $maxfilas;

        if ($mostradas + $maxahora > $numerofilas): // HAY MENOS DE maxfilas
            //EN CASO DE NO SER LA PRIMERA PAGINA,RECALCULAMOS EL NUMERO TOTAL DE FILAS POR SI HA VARIADO
            if ($limite != ""):
                $resultadoTotal = $bd->ExecSQL($sql);
                $numerofilas    = $bd->NumRegs($resultadoTotal);
            endif;

            $maxahora = $numerofilas - $mostradas;
        //ARREGO PARA ERROR CAUSADO CUANDO SE CAMBIA DE NUM PAGINA RELLENANDO CAMPOS DE BUSQUEDA QUE NO VAN A OBTENER REGISTROS (SIN PULSAR BUSCAR)
        else:
            if ($limite != ""):
                $resultadoTotal = $bd->ExecSQL($sql);
                $numerofilas    = $bd->NumRegs($resultadoTotal);
            endif;
        endif;

        // EN maxahora TENGO EL NUMERO DE FILAS A MOSTRAR EN ESTA PAGINA
        // AQUI SEA UN TIPO DE BUSQUEDA U OTRO TENDRE HECHA YA LA SENTENCIA SQL
        // Y LO QUE VIENE A CONTINUACION ES IGUAL PARA TODO TIPO DE BUSQUEDAS
    } // Fin Sql

    function NumRegs($maxfilas, $maxahora, $numerofilas, $descripcion = "")
    {
        global $pagina_act;
        global $administrador;
        global $auxiliar;

        $desde_pant = ($pagina_act) * $maxfilas + 1;
        $hasta_pant = $desde_pant + $maxahora - 1;

        if ($descripcion == "") $descripcion = $auxiliar->traduce("registros(s) encontrado(s)", $administrador->ID_IDIOMA);

        if ($numerofilas > 0):
            echo number_format((float)$desde_pant, 0, ",", ".") . " " . $auxiliar->traduce("al", $administrador->ID_IDIOMA) . " " . number_format((float)$hasta_pant, 0, ",", ".") . " " . $auxiliar->traduce("de", $administrador->ID_IDIOMA) . " <b>" . number_format((float)$numerofilas, 0, ",", ".") . "</b>";
        endif;

    } // Fin NumRegs

    function SigAnt($sql, $maxfilas, $numerofilas, $i, $Form, $color)
    {
        global $mostradas;
        global $pagina_act;
        global $limite;

        $desde_pant = $mostradas + 1;
        $hasta_pant = $desde_pant + $maxahora - 1;

        $pagina_ant = $pagina_act - 1;
        $pagina_sig = $pagina_act + 1;

        $PalitoSeparador = " <font color='#FCAD56'>|</font> ";

        $sesion = session_id();
        $tmp    = stripslashes( (string)$sql);
        if ($mostradas > 0):  // NO ES LA PRIMERA PAGINA
            $pshow = $mostradas - $maxfilas;
            if ($pshow < 0):
                $pshow = 0;
            endif;
            $limite = "limit $pshow,$maxfilas";
            echo "<a href='javascript:$Form.pagina_act.value=$pagina_ant;$Form.mostradas.value=$pshow;$Form.submit()'><b>$maxfilas</b> Anteriores</a>";
        else:   // ES LA PRIMERA PAGINA
            $PalitoSeparador = "";
        endif;

        if ($i + $mostradas < $numerofilas): // AUN QUEDAN PAGS POR MOSTRAR
            $nshow = $i + $mostradas;
            if ($nshow > $numerofilas):
                $nshow = intval($numerofilas - $i);
            endif;
            $limite = "limit $nshow,$maxfilas";
            $t      = $nshow + $maxfilas;
            echo "$PalitoSeparador";
            echo "<a href='javascript:$Form.pagina_act.value=$pagina_sig;$Form.mostradas.value=$nshow;$Form.submit()'><b>$maxfilas</b> Siguientes</a>";
        else: // ES LA ULTIMA PAGINA QUE SE MUESTRA
            echo "<font color='$color'> <b>$maxfilas</b> Siguientes</font>";
        endif;

    } // Fin SigAnt


    function Numeros($sql, $maxfilas, $numerofilas, $i, $pagina, $color)
    {
        // maxfilas = numero de filas a mostrar por pagina
        // mostradas = numero de filas mostradas hasta llegar a esta pagina
        // i = numero de filas mostradas en esta pagina
        // numerofilas = numero total de filas a mostrar
        // pagina_act = numero de pagina en la que estamos, empieza en cero.

        global $mostradas;
        global $pagina_act;
        global $limite;
        global $estiloNum;
        global $color_actual;
        global $administrador;
        global $auxiliar;
        //echo "sq=$sql";

        if (!isset($color_actual)) $color_actual = "#525252";

        if (!isset($estiloNum)) $estilo = "enlaceceldas";
        else                    $estilo = $estiloNum;

        $PalitoSeparador = " <font color='" . $color . "'>|</font> ";
        $TotalPags       = ceil((float)$numerofilas / (float)$maxfilas);
        if ($TotalPags <= 1):
            return;
        endif;

        if ($pagina_act - 10 <= 0):
            $limite_inferior = 0;
        else:
            $limite_inferior = $pagina_act - 10;
        endif;

        if ($pagina_act + 10 >= $TotalPags):
            $limite_superior = $TotalPags;
        else:
            $limite_superior = $pagina_act + 10;
        endif;

        if ($pagina_act > 0):
            $PagAnt = $pagina_act - 1;
        else:
            $PagAnt = 0;
        endif;
        if ($pagina_act != 0):
            echo "<a href='javascript:document.FormSelect.pagina_act.value=$PagAnt;document.FormSelect.submit()' class='$estilo' title='" . $auxiliar->traduce("ir a la página anterior", $administrador->ID_IDIOMA) . "'><<</a>&nbsp;";
        endif;

        for ($j = $limite_inferior; $j < $limite_superior; $j++):
            $Pag = $j + 1;
            if ($pagina_act == $j):
                $Marcador1 = "<b><FONT COLOR='" . $color_actual . "'>";
                $Marcador2 = "</FONT></b>";
            else:
                $Marcador1 = "";
                $Marcador2 = "";
            endif;
            echo "<a href='javascript:document.FormSelect.pagina_act.value=$j;document.FormSelect.submit()' class='$estilo'  title='" . $auxiliar->traduce("ir a la página", $administrador->ID_IDIOMA) . " $Pag'>$Marcador1 $Pag $Marcador2</a>";

            if ($j < $limite_superior - 1):
                echo $PalitoSeparador;
            endif;
        endfor;

        if ($pagina_act < $TotalPags - 1):
            $PagSig = $pagina_act + 1;
        else:
            $PagSig = $TotalPags - 1;
        endif;
        if ($pagina_act != $TotalPags - 1):
            echo "&nbsp;<a href='javascript:document.FormSelect.pagina_act.value=$PagSig;document.FormSelect.submit()' class='$estilo' title='" . $auxiliar->traduce("ir a la página siguiente", $administrador->ID_IDIOMA) . "'>>></a>";
        endif;

    } // Fin Numeros(silvia 28/08/06)

    function navegacion_pags($sql, $maxfilas, $numerofilas, $i, $pagina, $mostradas, $maxahora)
    {

        global $mostradas;
        global $pagina_act;
        global $limite;
        global $seleccion;
        global $muestraVer;

        $desde_pant = $mostradas + 1;
        $hasta_pant = $desde_pant + $maxahora - 1;

        $pagina_ant = $pagina_act - 1;
        $pagina_sig = $pagina_act + 1;

        $sesion = session_id();
        $tmp    = stripslashes( (string)$sql);
        if ($mostradas > 0):  // NO ES LA PRIMERA PAGINA
            $pshow = $mostradas - $maxfilas;
            if ($pshow < 0):
                $pshow = 0;
            endif;
            $limite = "limit $pshow,$maxfilas";
            echo "<a href='$pagina?PHPSESSID=$sesion&pagina_act=$pagina_ant&mostradas=$pshow&$seleccion'><img src='http://www.numismaticadracma.com/imagenes/anterior_es.gif' width='50' height='10' border=0></a>";
        else:   // ES LA PRIMERA PAGINA
        endif;

        if ($muestraVer == "No"):
            echo " ";
        else:
            echo " Viendo $desde_pant a $hasta_pant de <b>$numerofilas </b>";
        endif;

        if ($i + $mostradas < $numerofilas): // AUN QUEDAN PAGS POR MOSTRAR
            $nshow = $i + $mostradas;
            if ($nshow > $numerofilas):
                $nshow = intval($numerofilas - $i);
            endif;
            $limite = "limit $nshow,$maxfilas";
            $t      = $nshow + $maxfilas;
            echo "<a href='$pagina?PHPSESSID=$sesion&pagina_act=$pagina_sig&mostradas=$nshow&$seleccion'>
	<img src='http://www.numismaticadracma.com/imagenes/siguiente_es.gif' width='50' height='10' border=0>
			  </a>";
        else: // ES LA ULTIMA PAGINA QUE SE MUESTRA
        endif;

    } // Fin navegacion_pags

    function DefinirColumnasOrdenacion($columnas, $columna_defecto, $sentido_defecto)
    {

        // $columnas: array definido por:
        // El indice es el "alias" de la columna
        // El valor es el "nombre" de la columna en la base de datos
        //$columna_defecto: alias de la columna de ordenación por defecto
        //$sentido_defecto: sentido de ordenación de la columna por defecto

        global $Buscar;
        global $pagina_act;
        global $ordenar_por;
        global $sent_ord;
        global $ult_ord;
        global $ordenar_campo;
        global $cambio_sent;

        if ($ordenar_por == ""):
            $ordenar_por = $columna_defecto;
            $sent_ord    = $sentido_defecto;
        endif;
        if ($sent_ord == "" || !isset($sent_ord)) $sent_ord = 0;
        if ($ordenar_por != "" && $ult_ord == $ordenar_por && $cambio_sent == '1'):
            $sent_ord = !$sent_ord;
        endif;
        $ult_ord = $ordenar_por;

        $ordenar_campo = $columnas[$ordenar_por];

        $hay_ordenacion_columnas = true;
    }

    function GenerarColumna($titulo_columna, $clase, $alias_columna, $pathRaiz)
    {

        // Genera el título de la columna con enlace para cambiar su ordenación

        global $ordenar_por;
        global $sent_ord;

        echo '&nbsp;<a class="' . $clase . '" href="#" onClick="document.FormSelect.cambio_sent.value=\'1\';document.FormSelect.ordenar_por.value=' . '\'' . $alias_columna . '\'' . ';document.FormSelect.submit();return false">';
        if ($ordenar_por == $alias_columna):
            if ($sent_ord == 1):
                echo "<img src='" . $pathRaiz . "imagenes/white-down-arrow.gif' width='6' height='12' border='0' align='absmiddle'>&nbsp;";
            else:
                echo "<img src='" . $pathRaiz . "imagenes/white-up-arrow.gif' width='6' height='12' border='0' align='absmiddle'>&nbsp;";
            endif;
        endif;
        echo "$titulo_columna";
        echo "</a>&nbsp;";
    }

    #Genera el combo para seleccionar el nº de registros por pagina en los listados
    function GenerarComboNumRegs($valor, $selLimite = "selLimite")
    {
        global $html;
        global $Estilo;
        global $onChange;
        global $addOnChange;
        global $Tamano;

        $Estilo                = "copyright";
        $onChange              = "onChange='$addOnChange document.FormSelect.CambiarLimite.value='Si';document.FormSelect.selLimite.value=document.FormSelect.$selLimite.value;document.FormSelect.submit();'";
        $Elementos             = array();
        $Elementos[0]["text"]  = "10";
        $Elementos[0]["valor"] = "10";
        $Elementos[1]["text"]  = "25";
        $Elementos[1]["valor"] = "25";
        $Elementos[2]["text"]  = "50";
        $Elementos[2]["valor"] = "50";
        $Elementos[3]["text"]  = "100";
        $Elementos[3]["valor"] = "100";
        $Elementos[4]["text"]  = "200";
        $Elementos[4]["valor"] = "200";
        $Elementos[5]["text"]  = "500";
        $Elementos[5]["valor"] = "500";
        $Elementos[6]["text"]  = "1000";
        $Elementos[6]["valor"] = "1000";
        $Elementos[7]["text"]  = "5000";
        $Elementos[7]["valor"] = "5000";
        $Tamano                = 45;
        $html->SelectArr($selLimite, $Elementos, $valor, "No");
        unset($Estilo);
        unset($onChange);
        unset($Tamano);
    }


    /**
     * DEVUELVE EL NUMERO DE ELEMNTOS QUE DEBERIA MOSTRAR EL LISTADO SEGUN EL NUMERO DE ELEMENTOS QUE DEVUELVE
     * LA CONSULTA SQL.
     * @param $numRegistros //NUMERO DE REGISTROS QUE DEVUELVE LA CONSULTA SQL
     * @return int
     */
    function NumMostrarPorNumRegistros($numRegistros)
    {
        $numElementosMostrar   = 10;
        $Elementos             = array();
        $Elementos[0]["text"]  = "10";
        $Elementos[0]["valor"] = "10";
        $Elementos[1]["text"]  = "25";
        $Elementos[1]["valor"] = "25";
        $Elementos[2]["text"]  = "50";
        $Elementos[2]["valor"] = "50";
        $Elementos[3]["text"]  = "100";
        $Elementos[3]["valor"] = "100";
        $Elementos[4]["text"]  = "200";
        $Elementos[4]["valor"] = "200";
        $Elementos[5]["text"]  = "500";
        $Elementos[5]["valor"] = "500";
        $Elementos[6]["text"]  = "1000";
        $Elementos[6]["valor"] = "1000";
        $Elementos[7]["text"]  = "5000";
        $Elementos[7]["valor"] = "5000";

        for ($i = 0; $i < count( (array)$Elementos); $i++) {
            if ($numRegistros <= $Elementos[$i]["valor"]) {
                $numElementosMostrar = $Elementos[$i]["valor"];
                break;
            }
        }

        return $numElementosMostrar;

    }

    // Genera los campos ocultos del formulario estándar de navegación

    function GenerarCamposOcultosForm()
    {   // SILVIA

        global $ordenar_por;
        global $sent_ord;
        global $ult_ord;

        global $pagina_act;
        global $mostradas;
        global $Buscar;

        echo '<input type=hidden name="CambiarLimite" value="">';

        echo '<INPUT TYPE="HIDDEN" NAME="Buscar" VALUE="' . $Buscar . '">';
        echo '<INPUT TYPE="HIDDEN" NAME="pagina_act" VALUE="">';
        echo '<INPUT TYPE="HIDDEN" NAME="mostradas" VALUE="">';

        echo '<INPUT TYPE="HIDDEN" NAME="ordenar_por" VALUE="' . $ordenar_por . '">';
        echo '<INPUT TYPE="HIDDEN" NAME="sent_ord" VALUE="' . $sent_ord . '">';
        echo '<INPUT TYPE="HIDDEN" NAME="ult_ord" VALUE="' . $ult_ord . '">';

        echo '<INPUT TYPE="HIDDEN" NAME="cambio_sent" VALUE="">';

    }

}// Fin de la Clase

?>