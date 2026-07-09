$('#errorMsg').hide();
var mailVerificacion = "";


var url = window.location.href; 
var url_string = url.slice(url.indexOf('?')+1,url.length); 
var searchParams = new URLSearchParams(url_string); 
var loginUser = searchParams.get('loginUser');
var process = searchParams.get('process');

if(loginUser){
    $("#username").val(loginUser);
}
if(process){
    if(process == 'verification'){
        $('#buttonsContainer').remove();
        $('#errorMsg').hide();
        $('#name-container').hide();
        $('#user-container').hide();
        $('#sucursal-container').hide();
        $('#pass-container').hide();
        $('#buttonsContainer2').hide();
        $('.code-verification').show();
        $('#code-buttons').show();
    }
}
console.log(searchParams.get('parametro'))

const animate = (id) => {
    $(id).addClass('animated shake').one('animationend oAnimationEnd mozAnimationEnd webkitAnimationEnd', function() {
        $(this).removeClass('animated shake');
    });
}
//Valida que las contraseñas sean iguales y posteriormente envia la nueva contraseña
function changePass(element){
    //console.log(element.id.substr(10));
    var id = element.id.substr(10);
    var password=$('#newPassword').val();
    var confPassword=$('#confNewPassword').val();
    if(password !== '' && confPassword !== ''){
        if(password === confPassword){
            //console.log('si son iguales');
            if($('#confNewPassword').hasClass('warning')){
                $('#confNewPassword').removeClass('warning');
                $('#msgError').html('');
            $('#msgError').css('display','none');
            }
            //enviar nueva contraseña
            //console.log(password);
            $.get('https://melorautopartes.com/dashboard/users', `data=${JSON.stringify({
                action: 'savePass',
                password,
                id
            })}`,function(response){
                //console.log(response);
                response = JSON.parse(response);
                if(response['success'] == '1'){
                    $('#msgError').html('Contraseña cambiada con éxito');
                    $('#msgError').css('color','green');
                    $('#msgError').css('display','block');
                    setTimeout(function(){ window.location.replace("https://melorautopartes.com/dashboard/login"); }, 3000);
                    //window.location.replace("https://www.gdltours.com/adminVentas/");
                }else{
                    $('#msgError').css('display','block');
                    $('#msgError').html('Error');
                    $('#msgError').css('color','red');
                }
            })
        }else{
            animate('#confNewPassword');
            $('#confNewPassword').addClass('warning');
            $('#msgError').html('Las contraseñas no coinciden');
            $('#msgError').css('display','block');
        }
    }else{
        if(password === ''){
            //console.log('newpas');
            animate('#newPassword');
        }
        if(confPassword === ''){
            //console.log('newpasConf');
            animate('#confNewPassword');
        }
    }
}
//Valida la nueva contraseña
function validation(element){
    if(element.id == 'confNewPassword'){
        //console.log('validarConfirmacionContraseña');
        var password=$('#newPassword').val();
        var confPassword=$('#confNewPassword').val();
        if(password === confPassword){
            //console.log('si son iguales');
            if($('#confNewPassword').hasClass('warning')){
                $('#confNewPassword').removeClass('warning');
                $('#msgError').html('');
            $('#msgError').css('display','none');
            }
        }else{
            animate('#confNewPassword');
            $('#confNewPassword').addClass('warning');
            $('#msgError').html('Las contraseñas no coinciden');
            $('#msgError').css('display','block');
        }
    }
}
//Obtiene los parametros en el url para identificar si se trata de reestablecer contraseña
function searchParam(){
    var url = window.location.href;
    var dominio = window.location.host;
    //console.log(dominio);
    var url_string = url.slice(url.indexOf('?') + 1, url.length);
    var searchParams = new URLSearchParams(url_string);
    var oaut = searchParams.get("AU");
    //console.log(oaut);
    if(!oaut){
        window.location.replace("https://melorautopartes.com/dashboard/login");
    }else{
        $.get('https://melorautopartes.com/dashboard/users', `data=${JSON.stringify({
            action:'auth',
            oaut,
        })}`, function(response){
            response = JSON.parse(response);
            //console.log(response['authenticate']);
            if(response['authenticate'] == 'false'){
                window.location.replace("https://melorautopartes.com/dashboard/login");
            }else if(response['authenticate'] == 'true'){
                $('.btnChangePass').attr('id','changePass'+response['msg']);
            }
        })
    }
}
//Obtiene el correo del usuario al cual se le enviara el correo con el link de 
//cambio de contraseña
function sendLink(){
    //console.log('sendLink');
    var mail= $('#username').val();
    $.get('https://melorautopartes.com/dashboard/users', `data=${JSON.stringify({
       action: 'sendMail',
       mail
    })}`, function(response){
        //console.log(response);
        response = JSON.parse(response);
        if(response['sent']=='true'){
            $('#errorMsg').html('Se envio un correo para cambio de contraseña');
            
            //console.log($('#errorMsg').hasClass('alert-warning'));
            if($('#errorMsg').hasClass('alert-warning')){
                $('#errorMsg').removeClass('alert-warning');
                $('#errorMsg').addClass('alert-success');
            }
        }else{
            $('#errorMsg').html('Error al enviar correo');
            if($('#errorMsg').hasClass('alert-warning')){
                $('#errorMsg').removeClass('alert-warning');
                $('#errorMsg').addClass('alert-danger');
            }
            if($('#errorMsg').hasClass('alert-success')){
                $('#errorMsg').removeClass('alert-warning');
                $('#errorMsg').addClass('alert-danger');
            }
            
        }
    });
}
$('#loginBtn').on('click', (event) => {
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
});

$('#signupBtn').on('click', () => {
    $('#buttonsContainer').remove();
    $('#name-container').show();
    $('#sucursal-container').show();
    $('#department-container').show();
    $('#buttonsContainer2').show();
    $('#username').val('');
    $('#password').val('');
});

$('#registerBtn').on('click', (event) => {
    event.preventDefault();
    mailVerificacion = $('#username').val();
    const name = $('#name').val();
    const user = $('#username').val();
    const pass = $('#password').val();
    const department = $('select[id=department]').val();
    const sucursal = $('select[id=sucursal]').val();
    
    //console.log(department);
    //console.log(sucursal);
    if (user !== '' && pass !== '' && name !== '' && sucursal != 0) {
        $.post('https://melorautopartes.com/dashboard/users', `data=${JSON.stringify({
            name,
            email: user,
            password: pass,
            department,
            sucursal,
            action: 'save'
        })}`, (response) => {
            if (response.success == 1) {
                $('#errorMsg').hide();
                $('#buttonsContainer2 button').prop( "disabled", true );
                //('#errorMsg').html('Su cuenta ha sido creada').show();
                setTimeout(() => {
                    //location.reload();
                    $('#errorMsg').hide();
                    $('#name-container').hide();
                    $('#user-container').hide();
                    $('#sucursal-container').hide();
                    $('#pass-container').hide();
                    $('#buttonsContainer2').hide();
                    $('.code-verification').show();
                    $('#code-buttons').show();
                }, 3000);
            } else {
                $('#errorMsg').html(response.error).show();
            
                if(name == ''){
                    animate('#name-container');
                }
                if(user == ''){
                    animate('#user-container');
                }
                if(pass == ''){
                    animate('#pass-container');
                }
                if(department == '0'){
                    animate('#department-container');
                }
                if(sucursal == '0'){
                    animate('#sucursal-container');
                }
            }
        }, 'json');
    } else {
        if(name == ''){
            animate('#name-container');
        }
        if(user == ''){
            animate('#user-container');
        }
        if(pass == ''){
            animate('#pass-container');
        }
        if(department == '0'){
            animate('#department-container');
        }
        if(sucursal == '0'){
            animate('#sucursal-container');
        }
        
        
        
    }
});

$("#verificacionBtn").click(function(){
    var codigo = $("#codigoVerf").val();
    $.post('https://melorautopartes.com/dashboard/users', `data=${JSON.stringify({
        action:'verificacion',
        codigo,
        mailVerificacion
    })}`,function(response){
        response = JSON.parse(response);
        if(response.error == 'false'){
            $('#errorMsg').html(response.msg).show();
            setTimeout(function(){
                window.location.replace(response.url);    
            },2000)
            
        }else if(response.error == 'true'){
            $('#errorMsg').html(response.msg).show();
        }
    });
})

$('.cancelBtn').on('click',() => {
    location.reload();
});