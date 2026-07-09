$(document).ready(function(){
    /**
     * Valid Session
    */
    $.get('https://melorautopartes.com/dashboard/session',`data=${JSON.stringify({
        action:'sessionActive'
    })}`,function(response){
        console.log(response);
        response = JSON.parse(response);
        if(response.sessionActive == "false"){
            window.location = "https://melorautopartes.com/dashboard/login";
        }
    })
    
    /**
     * Close Session
    */
    $("#close-session").click(function(){
        $.get('https://melorautopartes.com/dashboard/session',`data=${JSON.stringify({
            action:'closeSession'
        })}`,function(response){
            console.log(response);
            response = JSON.parse(response);
            if(response.sessionActive == "false"){
                window.location = "https://melorautopartes.com/dashboard/login";
            }
        })    
    });
})

function recuperarSession(){
    event.preventDefault();
    const user = $('#username').val();
    const pass = $('#password').val();

    if (user !== '' && pass !== '') {
        $.post('https://melorautopartes.com/dashboard/users', `data=${JSON.stringify({
            email: user,
            password: pass,
            action: 'authenticate'
        })}`, (response) => {
            if (response.authenticate == 1) {
                window.location.replace(response.url);
            } else {
                $('#errorMsg').html(response.error).show();
                if(response.error == 'Por favor verifica tu contraseña'){
                    $('#generateLinkContra').html('<a href="javascript:sendLink();">Reestrablecer contraseña</a>');    
                }
                animate('#user-container');
                animate('#pass-container');
            }
        }, 'json');
    } else {
        if ($('#username').val() === '') {
            animate('#user-container');
        }
        if ($('#password').val() === '') {
            animate('#pass-container');
        }
    }
}