var cantidad_disponible_Booking = 0;

function changelan(id_lang){
    var dropdownSelectLang = document.getElementById('lang-seceted-dropdown');
    var  ruta = Routing.generate('app_cahngelanguage');
    $.ajax({
        type:'POST',
        url: ruta,
        data:({idiomaId: id_lang}),
        async: true,
        dataType: "json",
        success: function (data){
            dropdownSelectLang.innerHTML='<img src="'+data.padtoimg+'" alt="" style="height: 1rem"> ' + data.nombre
        },
        error:   function(response) {
            alert('No fue posible cambiar el idioma')
        }
    })
}
function getBookingsbyfecha(filtrofechainput = null){

    if(filtrofechainput == null){
        filtrofechainput = document.getElementById('filtrofecha').value;
    }

    var  ruta = Routing.generate('app_inicio_search');
    var row = document.getElementById('bookingrowinicio')
    row.innerHTML = '<div class="spinner-border" role="status">\n' +
        '  <span class="visually-hidden">Loading...</span>\n' +
        '</div>'

    $.ajax({
        type:'POST',
        url: ruta,
        data:({fechafiltro: filtrofechainput}),
        async: true,
        dataType: "json",
        success: function (data){
            if(data.render){
                row.innerHTML = data.render;
            }
        },
        error:   function(response) {
        }
    })
}

function seleccionarfechabookingsolicitud(fecha,fechaindexloop){
    if(document.getElementById('solicitud_reserva_submit').attributes.getNamedItem('disabled')) {
        document.getElementById('solicitud_reserva_submit').attributes.removeNamedItem('disabled');
    }
    document.getElementById('solicitud_reserva_fechaSeleccionadaString').value = fecha;
    cantidad_disponible_Booking = document.getElementById('fechacantidad'+fechaindexloop).value;
}
function habilitarFormularioAdicionalBooking(){
    var datosprincipal = document.getElementById('rowdatosprincipalbooking');
    var datosadicionales = document.getElementById('Formularioreservaadicional');
    var tabladatos = document.getElementById('rowAgregarPersona');
    var datosSolicitante = document.getElementById('datosSolicitante');
    var hidden =document.createAttribute("hidden");
    datosprincipal.attributes.setNamedItem(hidden)
    if(datosadicionales.attributes.getNamedItem('hidden')){
        datosadicionales.attributes.removeNamedItem('hidden')
        datosadicionales.style.backgroundColor = "#0d6efd";
        datosadicionales.style.transition = "background .5s ease-out";
        setTimeout(function(){
            $('#Formularioreservaadicional').css({backgroundColor: ''});
        },50);
    }

    if(tabladatos.attributes.getNamedItem('hidden')) {
        tabladatos.attributes.removeNamedItem('hidden')
    }
    if(datosSolicitante.attributes.getNamedItem('hidden')) {
        datosSolicitante.attributes.removeNamedItem('hidden')
    }
    document.getElementById('btnagregarpersonabooking').attributes.setNamedItem(document.createAttribute("disabled"));
    var required =document.createAttribute("required");
    document.getElementById('solicitud_reserva_surnameAdicional').attributes.setNamedItem(required)
    required =document.createAttribute("required");
    document.getElementById('solicitud_reserva_nameAdicional').attributes.setNamedItem(required)
    var disabled =document.createAttribute("disabled");
    document.getElementById('solicitud_reserva_submit').attributes.setNamedItem(disabled)
}
function agregarpersonabooking(){
    event.preventDefault();



    var tbodyPersonas = document.getElementById('tbody-personasinBooking');
    if(cantidad_disponible_Booking > tbodyPersonas.children.length + 1) {
        habilitarFormularioAdicionalBooking();
        tbodyPersonas.innerHTML += '<tr id="nuevapersonatablaadicional">' +
            '<td id="personaenformularioNombre"></td>' +
            '<td id="personaenformularioApellido"></td>' +
            '<td id="personaenformularioAccion"><button class="btn btn-link" onclick="finagregarpersonabooking()">Cancelar</button></td>' +
            '</tr>'
        document.getElementById('btnagregarpersonabooking').attributes.setNamedItem(document.createAttribute("disbled"));
        var rowDatosPrincipalshow = document.getElementById('uldatossolicitante');
        var nombre = document.getElementById('solicitud_reserva_name').value;
        var apellido = document.getElementById('solicitud_reserva_surname').value;
        var email = document.getElementById('solicitud_reserva_email').value;
        var telefono = document.getElementById('solicitud_reserva_phone').value;
        document.getElementById('btn-finalizaragregarpersona').replaceWith(document.getElementById('btn-finalizaragregarpersona').cloneNode(true))
        document.getElementById('btn-finalizaragregarpersona').addEventListener("click", function () {
            finagregarpersonabooking(true, null);
        });
        document.getElementById('solicitud_reserva_nameAdicional').value = ""
        document.getElementById('solicitud_reserva_surnameAdicional').value = ""
        var datos_adicionales = document.getElementById('datosadicionales-nuevaPersona').children
        if (datos_adicionales && datos_adicionales.length > 0) {
            for (let dato of datos_adicionales) {
                if (dato.tagName === "INPUT") dato.value = "";
            }
        }

        rowDatosPrincipalshow.innerHTML = "<p>" + nombre.replace(/(^\w{1})|(\s+\w{1})/g, letter => letter.toUpperCase()) + " " + apellido.replace(/(^\w{1})|(\s+\w{1})/g, letter => letter.toUpperCase()) + ", " + email + ", " + telefono + "</p>"
    }else{
        if(cantidad_disponible_Booking == 0 && document.getElementById('solicitud_reserva_fechaSeleccionadaString').value == ''){
            alert('Seleccione una fecha de servicio antes de agregar.')
        }else{
            alert('Solo hay ' + cantidad_disponible_Booking + ' lugares disponibles para esta fecha.')
        }
    }
}

function datoadicionalmodificado(datoId){
    var inputDato = document.getElementById(datoId);
    var dato = document.getElementById('label'+datoId).innerHTML;
    var formRequerido = document.getElementById('solicitud_reserva_form_required');

    var datosObjet = []
    var datoInForm = null;
    if(formRequerido.value){
        datosObjet = JSON.parse(formRequerido.value);
        datoInForm = datosObjet.find((datoObj) => datoObj.dato === dato && datoObj.type === inputDato.type)
        if(datoInForm){
            datoInForm.value = inputDato.value;
        }else{
            datosObjet.push({id:parseInt(datoId.split('-')[1]),dato:dato,type:inputDato.type,value:inputDato.value.toUpperCase()})
        }
        formRequerido.value = JSON.stringify(datosObjet);
    }
}
function finagregarpersonabooking(guardar=false,idPersona=null){
    event.preventDefault();
    //Formulario principal
    var datosprincipal = document.getElementById('rowdatosprincipalbooking');
    //formulario addicional
    var datosadicionales = document.getElementById('Formularioreservaadicional');

    //tabla datoss adicionales
    var tabladatos = document.getElementById('rowAgregarPersona');
    //row de la tabla de personas adici
    var trrow = document.getElementById('nuevapersonatablaadicional');

    //inputsadicional
    var inputNombreAdicional = document.getElementById('solicitud_reserva_nameAdicional')
    var inputApellidoAdicional = document.getElementById('solicitud_reserva_surnameAdicional')


    var datosSolicitante = document.getElementById('datosSolicitante');
    var hidden =document.createAttribute("hidden");
    var personas = document.getElementById('tbody-personasinBooking');

    if(inputApellidoAdicional.attributes.getNamedItem('required'))inputApellidoAdicional.attributes.removeNamedItem('required')
    if(inputNombreAdicional.attributes.getNamedItem('required'))inputNombreAdicional.attributes.removeNamedItem('required')
    if(idPersona!= null && idPersona >= 0){
        var btnEditar = document.getElementById('btneditardato-'+ idPersona)
        var btnQuitar = document.getElementById('btnquitardato-'+ idPersona)
        var trrow = document.getElementById('rowPersonaBookin-'+idPersona)
        if(btnEditar && btnEditar.attributes.getNamedItem('disabled'))btnEditar.attributes.removeNamedItem('disabled');
        if(btnQuitar && btnQuitar.attributes.getNamedItem('disabled'))btnQuitar.attributes.removeNamedItem('disabled');
        nombre = trrow.children[0];
        apellido = trrow.children[1];
    }else{
        var nombre = document.getElementById('personaenformularioNombre');
        var apellido = document.getElementById('personaenformularioApellido');
        var accion = document.getElementById('personaenformularioAccion');
    }
    if(!guardar){
        if(trrow!=null && idPersona == null) trrow.remove();
    }else{

        if(nombre)nombre.innerHTML =  inputNombreAdicional.value.replace(/(^\w{1})|(\s+\w{1})/g, letter => letter.toUpperCase());
        if(apellido)apellido.innerHTML = inputApellidoAdicional.value.replace(/(^\w{1})|(\s+\w{1})/g, letter => letter.toUpperCase());
        var id = cargarpersonaalformulario(idPersona);


        if(accion){
            trrow.id = "rowPersonaBookin-"+id
            accion.innerHTML ='<button class="btn btn-link" id="btneditardato-'+id+'" onclick="editarpersonabooking(\'rowPersonaBookin-'+ id +'\')">Editar</button>'
            accion.innerHTML +='<button class="btn btn-link" id="btnquitardato-'+id+'" onclick="quitarpersonabooking(\'rowPersonaBookin-'+ id +'\')">Borrar</button>'

            if(accion.attributes.getNamedItem('id'))accion.attributes.removeNamedItem('id')

        }

        if(nombre ) {
            if(nombre.attributes.getNamedItem('id'))nombre.attributes.removeNamedItem('id')
        }
        if(apellido) {
            if(apellido.attributes.getNamedItem('id')) apellido.attributes.removeNamedItem('id')
        }

    }
    habilitarformulariosegunfecha();
    if(personas.children.length === 0){
        tabladatos.attributes.setNamedItem(hidden);
    }
    if(idPersona!=null &&
        document.getElementById('btneditardato-'+parseInt(idPersona)).attributes.getNamedItem('disabled')){
        document.getElementById('btneditardato-'+parseInt(idPersona)).attributes.removeNamedItem('disabled')}
    if(idPersona!=null &&
        document.getElementById('btnquitardato-'+parseInt(idPersona)).attributes.getNamedItem('disabled')){
        document.getElementById('btnquitardato-'+parseInt(idPersona)).attributes.removeNamedItem('disabled')
    }
    hidden =document.createAttribute("hidden");
    datosadicionales.attributes.setNamedItem(hidden);
    hidden =document.createAttribute("hidden");
    datosSolicitante.attributes.setNamedItem(hidden);
    if(datosprincipal.attributes.getNamedItem('hidden'))datosprincipal.attributes.removeNamedItem('hidden')
    if(document.getElementById('btnagregarpersonabooking').attributes.getNamedItem('disabled'))document.getElementById('btnagregarpersonabooking').attributes.removeNamedItem('disabled')
}


function habilitarformulariosegunfecha(){
    var tienefechas = document.getElementById('rowfechasdisponibles');
    var submitfecha = document.getElementById('solicitud_reserva_submit');
    if( tienefechas && tienefechas.children.length > 0 ){
        for(fecha of tienefechas.getElementsByTagName('input')) {
            if(fecha.checked){
                submitfecha.attributes.removeNamedItem('disabled');
            }
        }
    }else{
        submitfecha.attributes.removeNamedItem('disabled');
    }
}


function cargarpersonaalformulario(id=null){
    var formSolicitudRe_datospersonas = document.getElementById('solicitud_reserva_inChargeOf')
    var formsolicitud_reserva_inChargeOf = JSON.parse(formSolicitudRe_datospersonas.value)
    var datosrequeridos = document.getElementById('datosadicionales-nuevaPersona').getElementsByTagName('label')
    var nombre =  document.getElementById('solicitud_reserva_nameAdicional')
    var apellido =document.getElementById('solicitud_reserva_surnameAdicional')
    var persona = {}
    var personaIndexOf = -1
    if(id != null && formsolicitud_reserva_inChargeOf.length > 0 )personaIndexOf = formsolicitud_reserva_inChargeOf.findIndex((datoObj) => parseInt(datoObj.id) === parseInt(id));
    persona.form_required = []
    for (let dato of datosrequeridos) {
        var idDato = parseInt(dato.id.split('-')[1])
        var inputDato = document.getElementById('datoFormBookingAdicional-'+idDato)
        datoInForm = persona.form_required.find((datoObj) => datoObj.dato === dato && datoObj.type === inputDato.type)
        if(datoInForm){
            datoInForm.value = inputDato.value;
        }else{
            persona.form_required.push({id:idDato,dato:dato.innerHTML,type:inputDato.type,value:inputDato.value.toUpperCase()})
        }
        inputDato.value = ""
    }
    persona.name = nombre.value.replace(/(^\w{1})|(\s+\w{1})/g, letter => letter.toUpperCase());
    persona.surname = apellido.value.replace(/(^\w{1})|(\s+\w{1})/g, letter => letter.toUpperCase());

    if(personaIndexOf !==-1){
        persona.id = parseInt(id)
        formsolicitud_reserva_inChargeOf[personaIndexOf]=persona;
    }else{
        if(formsolicitud_reserva_inChargeOf){
            formsolicitud_reserva_inChargeOf.forEach((dato)=>{
                if(id==null || dato.id > parseInt(id)){
                    id=parseInt(dato.id)
                }
            })
        }
        if(id){
            persona.id= id + 1
        }else{
            persona.id = parseInt(formsolicitud_reserva_inChargeOf.length)
        }

        formsolicitud_reserva_inChargeOf.push(persona)
    }
    formSolicitudRe_datospersonas.value = JSON.stringify(formsolicitud_reserva_inChargeOf)
    nombre.value = ""
    apellido.value = ""
    return persona.id;
}
function quitarpersonabooking(id){
    event.preventDefault();
    var formSolicitudRe_datospersonas = document.getElementById('solicitud_reserva_inChargeOf')
    var formsolicitud_reserva_inChargeOf = JSON.parse(formSolicitudRe_datospersonas.value)
    var personaId =parseInt(id.split('-')[1]);
    if(personaId != null && personaId >= 0){
        var persona = formsolicitud_reserva_inChargeOf.find((datoObj) => parseInt(datoObj.id) === personaId);
        if(persona){
            var nombre = persona.name || "";
            if(confirm("Quitar a " + nombre + " de la reserva? ")){
                document.getElementById(id).remove();
                var index = formsolicitud_reserva_inChargeOf.findIndex((datoObj) => parseInt(datoObj.id) === parseInt(personaId))
                if(index!=null && index >= 0){
                    formsolicitud_reserva_inChargeOf.splice(index, 1)
                    document.getElementById('solicitud_reserva_inChargeOf').value = JSON.stringify(formsolicitud_reserva_inChargeOf)
                }
            }
        }
    }
}
function cargar_datos_formularioBooking(){
    var reservasExtras = document.getElementById('solicitud_reserva_form_required').value;
    if(reservasExtras){
        Obj_reservasExtras = JSON.parse(reservasExtras);
        Obj_reservasExtras.forEach((dato)=>{
            var datoInput = document.getElementById('datoFormBooking-'+dato.id)
            if(datoInput)datoInput.value=dato.value;
        })
    }
    var formsolicitud_reserva_inChargeOf = document.getElementById('solicitud_reserva_inChargeOf').value;
    if(formsolicitud_reserva_inChargeOf){
        Obj_formsolicitud_reserva_inChargeOf = JSON.parse(formsolicitud_reserva_inChargeOf);
        if(Obj_formsolicitud_reserva_inChargeOf.length >0){
            var tabladatos = document.getElementById('rowAgregarPersona');
            var tbodyPersonas = document.getElementById('tbody-personasinBooking');
            if(tabladatos.attributes.getNamedItem('hidden')) {
                tabladatos.attributes.removeNamedItem('hidden')
            }
            Obj_formsolicitud_reserva_inChargeOf.forEach((dato)=>{
                tbodyPersonas.innerHTML += '<tr id="rowPersonaBookin-'+dato.id+'">' +
                    '<td>'+dato.name+'</td>' +
                    '<td>'+dato.surname+'</td>' +
                    '<td>' +
                    '<button class="btn btn-link" id="btneditardato-'+dato.id+'" onclick="editarpersonabooking(\'rowPersonaBookin-'+ dato.id +'\')">Editar</button>' +
                    '<button class="btn btn-link" id="btnquitardato-'+dato.id+'" onclick="quitarpersonabooking(\'rowPersonaBookin-'+ dato.id +'\')">Borrar</button>' +
                    '</td>' +
                    '</tr>'
            })
        }
    }
}
function editarpersonabooking(idPersona){
    event.preventDefault();

    var formsolicitud_reserva_inChargeOf = document.getElementById('solicitud_reserva_inChargeOf').value;
    if(formsolicitud_reserva_inChargeOf){
        Obj_formsolicitud_reserva_inChargeOf = JSON.parse(formsolicitud_reserva_inChargeOf);
        if(Obj_formsolicitud_reserva_inChargeOf.length >0){
            persona = Obj_formsolicitud_reserva_inChargeOf.find((datoObj) => datoObj.id === parseInt(idPersona.split('-')[1]));
            if(persona){
                habilitarFormularioAdicionalBooking();
                document.getElementById('btneditardato-'+parseInt(idPersona.split('-')[1])).attributes.setNamedItem(document.createAttribute('disabled'))
                document.getElementById('btnquitardato-'+parseInt(idPersona.split('-')[1])).attributes.setNamedItem(document.createAttribute('disabled'))
                document.getElementById('btn-finalizaragregarpersona').replaceWith(document.getElementById('btn-finalizaragregarpersona').cloneNode(true))
                document.getElementById('btn-finalizaragregarpersona').addEventListener("click",function () {
                    finagregarpersonabooking(true,parseInt(persona.id));
                });
                document.getElementById('btnagregarpersonabooking').attributes.setNamedItem(document.createAttribute('disabled'))
                document.getElementById('solicitud_reserva_nameAdicional').value = persona.name;
                document.getElementById('solicitud_reserva_surnameAdicional').value = persona.surname;
                var reservasExtras = persona.form_required;
                if(reservasExtras){
                    reservasExtras.forEach((dato)=>{
                        var datoInput = document.getElementById('datoFormBookingAdicional-'+dato.id)
                        if(datoInput)datoInput.value=dato.value;
                    })
                }
            }
        }
    }
}