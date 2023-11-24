<!-- Modal -->
<script language="JavaScript">
    function marcarHorarioVerano(obj) {
        if (jQuery(obj).prop('checked')) {
            jQuery('input[name="event-anotaciones"]').val('Horario de Verano');
//            jQuery('select[name="event-tipoFestivo"]').val('No Festivo');
        }
        else {
            jQuery('input[name="event-anotaciones"]').val('');
            jQuery('select[name="event-tipoFestivo"]').val('');
        }

    }

    function cambioTipo() {
        jQuery('input[name="event-index"]').val('');
        jQuery('input[name="event-tipoFestivo"]').val('');
        jQuery('input[name="event-start-time"]').val('');
        jQuery('input[name="event-end-time"]').val('');
        jQuery('input[name="event-start-time2"]').val('');
        jQuery('input[name="event-end-time2"]').val('');
        jQuery('#datetimepickerInicio').data("DateTimePicker").clear();
        jQuery('#datetimepickerInicio2').data("DateTimePicker").clear();
        jQuery('#datetimepickerFin').data("DateTimePicker").clear();
        jQuery('#datetimepickerFin2').data("DateTimePicker").clear();
    }
    function mostrarCapaFestivo() {
        jQuery("#divFestivo").show();
        jQuery("#divHorario").hide();
        jQuery("#divHorario2").hide();
        jQuery("#divHorarioVerano").hide();

        jQuery('input[name="event-anotaciones"]').val("");
        jQuery('input[name="event-anotaciones"]').attr('readonly', false);

        cambioTipo();
    }
    function mostrarCapaHorario() {
        jQuery("#divFestivo").hide();
        jQuery("#divHorario").show();
        jQuery("#divHorarioVerano").show();
        jQuery('input[name="event-anotaciones"]').val("Horario Especial");
        jQuery('input[name="event-anotaciones"]').attr('readonly', true);
        cambioTipo();
    }
</script>
<script type="text/javascript">
    jQuery(function () {
        jQuery('#datetimepickerInicio').datetimepicker({
            format: 'HH:mm'
        })
    });
    jQuery(function () {
        jQuery('#datetimepickerFin').datetimepicker({
            format: 'HH:mm'
        });
    });
    jQuery(function () {
        jQuery('#datetimepickerInicio2').datetimepicker({
            format: 'HH:mm'
        });
    });
    jQuery(function () {
        jQuery('#datetimepickerFin2').datetimepicker({
            format: 'HH:mm'
        });
    });
</script>
<div class="modal modal-fade" id="event-modal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span><span
                        class="sr-only"><?= $auxiliar->traduce("Cerrar", $administrador->ID_IDIOMA); ?></span></button>
                <h4 class="modal-title">
                    <?= $auxiliar->traduce("Evento", $administrador->ID_IDIOMA) ?>
                </h4>
            </div>
            <div class="modal-body">
                <input name="event-index" type="hidden">
                <input name="event-idCalendario" type="hidden">

                <form class="form-horizontal">
                    <div class="form-group">
                        <label for="min-date"
                               class="col-sm-4 control-label"><?= $auxiliar->traduce("Tipo", $administrador->ID_IDIOMA); ?></label>

                        <div class="col-sm-7">
                            <label class="radio-inline">
                                <input type="radio" id="chCapaFestivo" name="optradio"
                                       onchange="mostrarCapaFestivo()"><?= $auxiliar->traduce("Festivo", $administrador->ID_IDIOMA); ?>
                            </label>
                            <label class="radio-inline">
                                <input type="radio" id="chCapaHorario" name="optradio"
                                       onchange="mostrarCapaHorario()"><?= $auxiliar->traduce("Horario", $administrador->ID_IDIOMA); ?>
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="min-date"
                               class="col-sm-4 control-label"><?= $auxiliar->traduce("Descripcion", $administrador->ID_IDIOMA); ?></label>

                        <div class="col-sm-7">
                            <input name="event-anotaciones" class="form-control" type="text">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="min-date"
                               class="col-sm-4 control-label"><?= $auxiliar->traduce("Fecha", $administrador->ID_IDIOMA); ?></label>

                        <div class="col-sm-7">
                            <div class="input-group input-daterange" data-provide="datepicker">
                                <input name="event-start-date" class="form-control" value="2018-04-05" type="text">
                                <span
                                    class="input-group-addon"><?= $auxiliar->traduce("a", $administrador->ID_IDIOMA) ?></span>
                                <input name="event-end-date" class="form-control" value="2018-04-19" type="text">
                            </div>
                        </div>
                        <!--                        <button type="button" id="btnFecha" class="btn btn-primary" onclick="jQuery('#divFecha2').show();jQuery(this).attr('disabled','disabled');">-->
                        <!--                            <span class="glyphicon glyphicon-plus-sign"></span>-->
                        <!--                        </button>-->
                    </div>
                    <div class="form-group collapse" id="divFecha2">
                        <label for="min-date"
                               class="col-sm-4 control-label"><?= $auxiliar->traduce("Fecha", $administrador->ID_IDIOMA); ?></label>

                        <div class="col-sm-7">
                            <div class="input-group input-daterange" data-provide="datepicker">
                                <input name="event-start-date2" class="form-control" value="" type="text">
                                <span
                                    class="input-group-addon"><?= $auxiliar->traduce("a", $administrador->ID_IDIOMA) ?></span>
                                <input name="event-end-date2" class="form-control" value="" type="text">
                            </div>
                        </div>
                    </div>

                    <div class="form-group collapse" id="divFestivo">
                        <label
                            class="col-sm-4 control-label"><?= $auxiliar->traduce("Tipo Festivo", $administrador->ID_IDIOMA); ?></label>

                        <div class="col-sm-7">
                            <select class="form-control" name="event-tipoFestivo">
                                <!--                                <option-->
                                <!--                                    value="No Festivo">-->
                                <? //= $auxiliar->traduce("No Festivo", $administrador->ID_IDIOMA) ?><!--</option>-->
                                <option
                                    value="No Laborable"><?= $auxiliar->traduce("No Laborable", $administrador->ID_IDIOMA) ?></option>
                                <option
                                    value="Nacional"><?= $auxiliar->traduce("Nacional", $administrador->ID_IDIOMA) ?></option>
                                <option
                                    value="Autonomico"><?= $auxiliar->traduce("Autonomico", $administrador->ID_IDIOMA) ?></option>
                                <option
                                    value="Local"><?= $auxiliar->traduce("Local", $administrador->ID_IDIOMA) ?></option>
                            </select>
                        </div>
                    </div>


                    <div class="form-group collapse" id="divHorario">
                        <label for="min-date"
                               class="col-sm-4 control-label"><?= $auxiliar->traduce("Horario", $administrador->ID_IDIOMA); ?></label>

                        <div class="col-sm-7">
                            <div class='input-group'>
                                <input name="event-start-time" type='text' id='datetimepickerInicio'
                                       class="form-control"/>
                                <span
                                    class="input-group-addon"><?= $auxiliar->traduce("a", $administrador->ID_IDIOMA) ?></span>
                                <input name="event-end-time" type='text' id='datetimepickerFin' class="form-control"/>

                            </div>
                        </div>

                        <button type="button" id="btnHorario" class="btn btn-primary"
                                onclick="jQuery('#divHorario2').show();jQuery(this).attr('disabled','disabled');">
                            <span class="glyphicon glyphicon-plus-sign"></span>
                        </button>
                    </div>
                    <div class="form-group collapse" id="divHorario2">
                        <label for="min-date"
                               class="col-sm-4 control-label"><?= $auxiliar->traduce("Horario", $administrador->ID_IDIOMA); ?> </label>

                        <div class="col-sm-7">
                            <div class='input-group'>
                                <input name="event-start-time2" type='text' id='datetimepickerInicio2'
                                       class="form-control"/>
                                <span
                                    class="input-group-addon"><?= $auxiliar->traduce("a", $administrador->ID_IDIOMA) ?></span>
                                <input name="event-end-time2" type='text' id='datetimepickerFin2' class="form-control"/>

                            </div>
                        </div>
                    </div>
                    <!--                    <div class="form-group collapse" id='divHorarioVerano'>-->
                    <!--                        <label-->
                    <!--                            class="col-sm-4 control-label">-->
                    <? //= $auxiliar->traduce("Horario verano", $administrador->ID_IDIOMA); ?><!--</label>-->
                    <!---->
                    <!--                        <div class="col-sm-1">-->
                    <!--                            <input name="event-horarioVerano" class="form-control" type="checkbox"-->
                    <!--                                   onchange="marcarHorarioVerano(this);">-->
                    <!--                        </div>-->
                    <!--                    </div>-->
                    <div class="form-group">
                        <label class="col-sm-1 control-label"></label>

                        <div id="error-modal" class="alert alert-danger col-sm-10 collapse" role="alert">

                        </div>

                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default"
                        data-dismiss="modal"><?= $auxiliar->traduce("Cancelar", $administrador->ID_IDIOMA) ?></button>

                <button type="button" class="btn btn-danger" id="delete-event">
                    <?= $auxiliar->traduce("Borrar", $administrador->ID_IDIOMA) ?>
                </button>
                <button type="button" class="btn btn-primary" id="save-event">
                    <?= $auxiliar->traduce("Guardar", $administrador->ID_IDIOMA) ?>
                </button>

            </div>
        </div>
    </div>
</div>