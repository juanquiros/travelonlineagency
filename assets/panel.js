
const logo = document.getElementById("logo");
const barraLateral = document.querySelector(".barra-lateral");
const spans = document.querySelectorAll("span");
const palanca = document.querySelector(".switch");
const circulo = document.querySelector(".circulo");
const menu = document.querySelector(".menu");
const main = document.querySelector("main");

menu.addEventListener("click", () => {
    barraLateral.classList.toggle("max-barra-lateral");
    const isMaxBarraLateral = barraLateral.classList.contains("max-barra-lateral");
    menu.children[0].style.display = isMaxBarraLateral ? "none" : "block";
    menu.children[1].style.display = isMaxBarraLateral ? "block" : "none";

    if (window.innerWidth <= 320) {
        barraLateral.classList.add("mini-barra-lateral");
        main.classList.add("min-main");
        spans.forEach((span) => span.classList.add("oculto"));
    }
});

palanca.addEventListener("click", () => {
    document.body.classList.toggle("dark-mode");
    circulo.classList.toggle("prendido");
});

logo.addEventListener("click", () => {
    barraLateral.classList.toggle("mini-barra-lateral");
    main.classList.toggle("min-main");
    spans.forEach((span) => span.classList.toggle("oculto"));
});


function uploadImgBooking(e){
    e.preventDefault();
    var btn = document.getElementById('btnSubirImagenBooking');
    btn.innerHTML = 'Cargando';
    btn.enable = false;
    var rowImagenes = document.getElementById('rowImagenesBooking');

    const formData = new FormData();

    var totalFiles = document.getElementById('newImagenBooking').files.length;
    var bookingid= document.getElementById('bookingId').value;
    var isportada = document.getElementById('isportada').checked;
    var booking_imagenes = document.getElementById('booking_imagenes');
    console.log(totalFiles);
    formData.append("data", JSON.stringify({bookingid:bookingid,isportada:isportada,enform:booking_imagenes.value}));
    for (var i = 0; i < totalFiles; i++) {
        var file = document.getElementById('newImagenBooking').files[i];
        formData.append("imagen", file);
        console.log(formData);
    }

    rowImagenes.innerHTML = '<div class="spinner-border" role="status">\n' +
        '  <span class="visually-hidden">Loading...</span>\n' +
        '</div>';
    var  ruta = Routing.generate('app_uploadimagenbooking');
    $.ajax({
        type:'POST',
        url: ruta,
        data:formData,
        async: true,
        dataType: "json",
        contentType: false,
        processData: false,
        success: function (data){

            booking_imagenes.value = JSON.stringify(data.files);

            rowImagenes.innerHTML="";
            data.files.forEach((imagen)=>{
                img =  '<div class="img-thumbnail" style="display: flex;flex-direction: column; width: 120px;">' +
                    '<img src="/img/booking/'+ imagen.imagen +'"  alt="" />' +
                    '<input class="mt-1" type="checkbox" onchange="cambiarportada(\'' + imagen.imagen + '\')" id="' + imagen.imagen + '"';
                if(imagen.portada){
                    img += ' checked ';
                }
                img +='>' +
                    '</div>';
                rowImagenes.innerHTML += img;
            })

        },
        error:   function(response) {

            alert('No fue posible subir la imagen reintentar')
        }
    })
    btn.innerHTML = 'Subir';
    btn.enable = true;
}

function cambiarportada(imgenParaPortada){
    var btn = document.getElementById('btnSubirImagenBooking');
    btn.enable = false;
    var booking_imagenes = document.getElementById('booking_imagenes');
    var rowImagenes = document.getElementById('rowImagenesBooking');
    var form_images =  JSON.parse(booking_imagenes.value)
    rowImagenes.innerHTML = ""
    form_images.forEach((images)=>{
        images.portada = false;
        img =  '<div class="img-thumbnail" style="display: flex;flex-direction: column; width: 120px;">' +
            '<img src="/img/booking/'+ images.imagen +'"  alt="" />' +
            '<input class="mt-1" type="checkbox" onchange="cambiarportada(\'' + images.imagen + '\')" id="' + images.imagen + '"';
        if(imgenParaPortada === images.imagen){
            img += ' checked ';
            images.portada = true;
        }
        img +='>' +
            '</div>';
        rowImagenes.innerHTML += img;
    })
    booking_imagenes.value = JSON.stringify(form_images);
    var imgPortada = document.getElementById(imgenParaPortada);
    imgPortada.checked = true;
    imgPortada.enable = false;

    btn.enable = true;
}
function modificarnombredato(id){

    var tdnombredato = document.getElementById('dato-'+id)

    if(tdnombredato.children.length == 0 ){
        var nombre = tdnombredato.innerHTML
        tdnombredato.innerHTML = "<input type='text' value='" + nombre + "' id='input-" + id + "-dato'>"

        tdnombredato.children[0].focus()
        tdnombredato.children[0].setSelectionRange(tdnombredato.children[0].value.length,tdnombredato.children[0].value.length)
    }
}
function close_modificarnombredato(id){
    var tdnombredato = document.getElementById('dato-'+id)
    if(tdnombredato.children.length > 0 ){
        tdnombredato.innerHTML = document.getElementById('input-'+id+'-dato').value
    }
    document.getElementById('booking_form_requerido').value = getDatosAdiconales();
}

function cambiartipodato(){
    document.getElementById('booking_form_requerido').value = getDatosAdiconales();
}
function agregardatoadicional(){
    cargartabladatosadicional({dato:"Nuevo dato",tipo:"text"});
}
function quitarDatoAdicional(id){
    var dato = document.getElementById('tr-dato-'+id+'')
    if(dato && confirm('¿Eliminar dato?'))dato.remove()
}

function getDatosAdiconales(){
    var tbody_datos = document.getElementById('tbody-datosadicionales')
    var resp = [];
    console.log(tbody_datos.children);
    if(tbody_datos.children.length > 0){
        var childrenArray = [... tbody_datos.children];
        childrenArray.forEach((dato)=>{
            var datoarray = {}
            var id = parseInt(dato.id.split('-')[2]);
            var selected = document.getElementById('tipodato-'+id)
            datoarray.id = id
            datoarray.dato = document.getElementById('dato-'+id).innerHTML;
            datoarray.tipo = selected.children[selected.selectedIndex].value;
            resp.push(datoarray);
        })
    }
    return JSON.stringify(resp);
}

function cargartabladatosadicional(dato,id=null){
    if(dato){
        var rowhtml="";
        var tbody_datos = document.getElementById('tbody-datosadicionales')
        if(!id){
            ultimo_id = 1;
            if(tbody_datos.children.length > 0){
                var ultimo_id = tbody_datos.children[tbody_datos.children.length -1 ]
                ultimo_id = parseInt(ultimo_id.id.split('-')[2]) + 1
            }
        }else{
            ultimo_id = id;
        }

        rowhtml += '<tr id="tr-dato-'+ultimo_id+'">' +
            '                        <td onclick="modificarnombredato('+ultimo_id+')" onfocusout="close_modificarnombredato('+ultimo_id+')" id="dato-'+ultimo_id+'">'+dato.dato+'</td>' +
            '                        <td>' +
            '                            <select class="form-select form-select-sm" name="tipodato-'+ultimo_id+'" id="tipodato-'+ultimo_id+'" aria-label=".form-select-sm example" onchange="cambiartipodato()">' +
            '                                <option value="text" ';
        if(dato.tipo === "text"){rowhtml += ' selected';}


        rowhtml += '>Texto 256 caracteres</option>' +
            '<option value="number" ';

        if(dato.tipo === "number"){rowhtml += ' selected';}

        rowhtml += ' >Número</option>' +
            '</select>' +
            '                        </td>' +
            '                        <td><a onclick="quitarDatoAdicional('+ultimo_id+')">Quitar</a>' +
            '</td>' +
            '                    </tr>'

        tbody_datos.innerHTML += rowhtml;
        }
}

function loadMoney(){
    var tbody_precios = document.getElementById('tbody-preciosBooking').children
    if(tbody_precios){
     var booking_preciosaux = document.getElementById('booking_preciosaux')
     booking_preciosaux.value = JSON.stringify([{id:1,valor:14.75,monedaId:1}]);
    }
}

function cargartablapreciosBooking(monedas,precio,id=""){
    if(precio){
        var rowhtml="";
        var tbody_monedas = document.getElementById('tbody-preciosBooking')
        if(!id){
            ultimo_id = 1;
            if(tbody_monedas.children.length > 0){
                var ultimo_id = tbody_monedas.children[tbody_monedas.children.length -1 ]
                ultimo_id = parseInt(ultimo_id.id.split('-')[2]) + 1
            }
        }else{
            ultimo_id = id;
        }

        rowhtml += '<tr id="tr-precio-'+ultimo_id+'">' +
            '<td style="color: gray" id="precioIdBooking-'+ultimo_id+'">'+id+'</td>' +
            '                        <td onclick="modificarvalorpreciobooking('+ultimo_id+')" onfocusout="close_modificarvalorpreciobooking('+ultimo_id+')" id="precio-'+ultimo_id+'">'+precio.valor+'</td>' +
            '                        <td>' +
            '                            <select class="form-select form-select-sm" name="preciobmoneda-'+ultimo_id+'" id="preciobmoneda-'+ultimo_id+'" aria-label=".form-select-sm example" onchange="cambiarmonedadepreciobooking()">';
        if(monedas && monedas.length > 0){
            monedas.forEach((moneda)=>{
                rowhtml += '<option value="'+ moneda.id +'" ';
                if(precio.monedaId == moneda.id){rowhtml += ' selected';}
                rowhtml += '>'+ moneda.nombre +'</option>' ;
            })
        }
        rowhtml += '</select>' +
            '                        </td>' +
            '                        <td><a onclick="quitarprecioBooking('+ultimo_id+','+id+')">Quitar</a>' +
            '</td>' +
            '                    </tr>'

        tbody_monedas.innerHTML += rowhtml;
        document.getElementById('booking_preciosaux').value = getpreciosBookingTbody();
    }
}
function modificarvalorpreciobooking(id){

    var tdnombreprecio = document.getElementById('precio-'+id)

    if(tdnombreprecio.children.length == 0 ){
        var valor = tdnombreprecio.innerHTML
        tdnombreprecio.innerHTML = "<input type='number' value='" + valor + "' id='input-" + id + "-precio'>"

        tdnombreprecio.children[0].focus()
    }
}
function getpreciosBookingTbody(){
    var tbody_datos = document.getElementById('tbody-preciosBooking')
    var resp = [];
    if(tbody_datos.children.length > 0){
        var childrenArray = [... tbody_datos.children];
        childrenArray.forEach((precio)=>{
            var precioarray = {}
            var id_row = parseInt(precio.id.split('-')[2]);
            var id = document.getElementById('precioIdBooking-'+id_row);
            var selected = document.getElementById('preciobmoneda-'+id_row)
            if(id && parseInt(id.innerHTML) > 0){
                precioarray.id = parseInt(id.innerHTML)
            }else{
                precioarray.id =null
            }
            precioarray.valor = parseFloat(document.getElementById('precio-'+id_row).innerHTML);
            precioarray.monedaId =parseInt(selected.children[selected.selectedIndex].value);
            resp.push(precioarray);
        })
    }
    return JSON.stringify(resp);
}
function close_modificarvalorpreciobooking(id){
    var tdnombreprecio = document.getElementById('precio-'+id)
    if(tdnombreprecio.children.length > 0 ){
        tdnombreprecio.innerHTML = document.getElementById('input-'+id+'-precio').value
    }
    document.getElementById('booking_preciosaux').value = getpreciosBookingTbody();
}
function quitarprecioBooking(idRow,id){
    e.preventDefault();
    var precio = document.getElementById('tr-precio-'+idRow+'')
    if(precio && confirm('¿Eliminar precio?')){
        if(id){
            console.log('eliminar:'+id)
            var  ruta = Routing.generate('app_service_booking_del_precio');
            $.ajax({
                type:'POST',
                url: ruta,
                data:JSON.stringify({precioId:parseInt(id)}),
                async: true,
                dataType: "json",
                contentType: false,
                processData: false,
                success: function (data){
                    if(data.eliminado){
                        precio.remove()
                    }else{
                        alert('No fue posible quitar el precio')
                    }
                },
                error:   function(response) {
                    alert('No fue posible quitar el precio')
                }
            })
        }else{
            precio.remove()
        }

    }
}
function quitarFechaBooking(bookingId,fechastring,id){
    event.preventDefault();
    var fecha = document.getElementById(id)
    if(fecha && confirm('¿Eliminar fecha '+fechastring+'?')){
        if(bookingId){
            console.log('eliminar:'+fechastring)
            var  ruta = Routing.generate('app_service_booking_de_fecha',{id:bookingId});
            $.ajax({
                type:'POST',
                url: ruta,
                data:JSON.stringify({fechaString:fechastring}),
                async: true,
                dataType: "json",
                contentType: false,
                processData: false,
                success: function (data){
                    if(data.eliminado){
                        fecha.remove()
                    }else{
                        alert('No fue posible quitar la fecha s')
                    }
                },
                error:   function(response) {
                    alert('No fue posible quitar la fecha ss')
                }
            })}else {
            fecha.remove()
        }

    }
}
function quitarprecioFAKs(id){

    if(confirm('¿Eliminar pregunta?')){
        if(id){
            var  ruta = Routing.generate('app_admin_remove_fak');
            $.ajax({
                type:'POST',
                url: ruta,
                data:JSON.stringify({preguntaId:parseInt(id)}),
                async: true,
                dataType: "json",
                contentType: false,
                processData: false,
                success: function (data){
                    if(data.eliminado){
                    }else{
                        alert('No fue posible quitar la pregunta')
                    }
                },
                error:   function(response) {
                    alert('No fue posible quitar la pregunta')
                }
            })
        }

    }
}
function cambiarmonedadepreciobooking(){
    document.getElementById('booking_preciosaux').value = getpreciosBookingTbody();
}
function agregarprecioBooking(){
    cargartablapreciosBooking(monedas,{valor:0.0,monedaId:1});
}
function agregarFechaBooking(fecha=null,cantidad=0,bookingId=""){
    var index = 0;
    var tbodyfechasBooking = document.getElementById('tbody-fechasBooking');
    if(tbodyfechasBooking.children.length > 0){
        index = tbodyfechasBooking.children.length - 1;
    }
    if(fecha == null) {
        fecha = document.getElementById('filtrofecha').value;
        cantidad = document.getElementById('cantidaddereservas').value;
    }

    if(fecha){
        tbodyfechasBooking.innerHTML += '<tr id="fechabooking-'+index+'" >' +
            '<td>'+fecha+'</td>' +
            '<td>'+cantidad+'</td>' +
            '<td><button class="btn btn-link" onclick="quitarFechaBooking('+bookingId+',\''+fecha+'\',\'fechabooking-'+index+'\')">Quitar</button></td>' +
            '</tr>'
    }
    document.getElementById('booking_fechasdelservicio').value = getfechasBookingTbody();
}
function getfechasBookingTbody(){
    var tbody_fechas = document.getElementById('tbody-fechasBooking')
    var resp = [];
    if(tbody_fechas.children.length > 0){
        var childrenArray = [... tbody_fechas.children];
        childrenArray.forEach((fecha)=>{
            var fechaarray = {}
            fechaarray.fecha = fecha.children[0].innerHTML;
            fechaarray.cantidad = fecha.children[1].innerHTML;
            resp.push(fechaarray);
        })
    }
    return JSON.stringify(resp);
}
function filtrarFechaSolicitudesBooking(bookingId,idSelect){
    //app_administrador_booking
    fechaFiltro = document.getElementById(idSelect).value.replaceAll("/", "-")
    var  ruta = Routing.generate('app_administrador_booking',{id:bookingId,ff:fechaFiltro},true);
    window.location.replace(ruta)
}

console.log('Load file js')