
if(Notification.permission === 'granted'){
    btn = document.getElementById('enableNotificacionesBtn')
    btn.classList = ['btn btn-secondary disabled'];
    btn.innerHTML = "Las notificaciones estan habilitadas en este dispositivo."
}
function registrarSuscripcion(suscripcion){
    var  ruta = Routing.generate('app_notificacion_registrar');
    $.ajax({
        type:'POST',
        url: ruta,
        data:({suscripcion: suscripcion}),
        async: false,
        dataType: "json",
        success: function (data){
        }
    })

}
function enableNotif() {
    Notification.requestPermission().then((permission)=> {
            if (permission === 'granted') {
                navigator.serviceWorker.ready.then((notificaciones)=> {
                    notificaciones.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: "BNnsx1A1k1VRV3twYiAM1onhVk2Jm_xvEoytObUTcjsHwdQsVkAetODIiFGP_F-Vow3uSIpjTuRuZJaOMlFXFfE"
                    }).then((subscription)=> {
                        registrarSuscripcion(JSON.stringify(subscription));
                        btn = document.getElementById('enableNotificacionesBtn')
                        btn.classList = ['btn btn-secondary disabled'];
                        btn.innerHTML = "Las notificaciones estan habilitadas en este dispositivo."
                    }).catch((err)=>{
                        console.log(err);
                    });
                });
            }else{
                console.log(permission)
            }
        }).catch((err)=>{
            console.log(err)
        });

}



