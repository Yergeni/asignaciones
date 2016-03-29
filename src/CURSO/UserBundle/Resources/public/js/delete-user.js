$(document).ready(function() {
    //seleccionamos la clase btn-delete que tienen todos los botones delete y le pasamos el evento e
    $('.btn-delete').click(function(e){
        e.preventDefault();                 //previene que se cargue la pagina cada vez que demos clic sobre el boton
        
        var row = $(this).parents('tr');    //se obtiene el elemtnto padre en este caso el tr (la fila entera)
        var id = row.data('id');            //obtenemos el valor del id (pasado desde el controlador) del usuario de la fila correspondiente donde esta el boton eliminar
        
        // alert(id);
        var form = $('#form-delete');       //obtenemos el formulario
        var url = form.attr('action').replace(':USER_ID', id); //aqui buscamos el atributo action que tiene la url de eliminar y reemplazamos
                                                               //el parametro :USER_ID por el id obtenido en la variable id (atributo pasado desde el controlador)
                                                              
        var data = form.serialize();        //datos a enviar serializados
        
        // alert(data);
        
        //mensaje por bootbox para mantener el diseño
        bootbox.confirm(message, function(selected){    //variable selected toma el valor del boton seleccionado en el dialgbox mostrado (OK or CANCEL)
            if(selected == true)
            {
                $('#delete-progress').removeClass('hidden');  
                //enviamos los datos al controlador mediante la funcion $.post se le pasa la ruta, los datos serializados, una funcion que devuelve succes si la
                //peticion fue satisfactoria o sea si se elimino. El Response enviado por el controlador(metodo deleteAction) se usa para saber si el usuario 
                //fue eliminado o no
                $.post(url, data, function(result){             //esta funcion envia por POST todos estos datos y espera un RESPONSE json_encode en result
                $('#delete-progress').addClass('hidden'); 
                    if(result.removed == 1)                     //Si removed viene con valor 1 se elimino el usuario
                    {
                        row.fadeOut();                          //Quitamos toda la fila a eliminar
                        $('#message').removeClass('hidden');     //Quitamos la clase hidden del div message de las pagina success
                        $('#user-message').text(result.message_ajax); //ponemos el mensaje enviado por el RESPONSE con el metodo text() en el span con id user-message
                        var totalUsers = $('#total').text();    //obtenemos el total de usuario actual
                        if($.isNumeric(totalUsers))             //verificamos que sea un valor numerico
                        {
                            $('#total').text(totalUsers-1);     //como se elimino se le resta 1
                        }
                        else{
                            $('#total').text(result.countUsers); //si no se obtiene el mismo valor de usuarios enviados desde el Response
                        }
                    }
                    else //en caso que no se haya eliminado el usuario o sea removed == 0
                    {
                        $('#message-error').removeClass('hidden');           //Quitamos la clase hidden del div message-error de las pagina success
                         $('#user-message-error').text(result.message_ajax); //ponemos el mensaje enviado por el RESPONSE con el metodo text() en el span con id 
                                                                             //user-message-error
                    }
                }).fail(function()
                {
                    alert('ERROR');
                    row.show();
                })
            }
        })
        
    })
})