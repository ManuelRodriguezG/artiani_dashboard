
<style type="text/css">
    .a-bg{
        background:transparent;
    }
    @font-face {
      font-family: "Free Script";
      src: url("fonts/FREESCPT.TTF");
    }
    @font-face {
      font-family: "Lato";
      src: url("fonts/header/Karla-Regular.ttf");
    }
    .navigation-link{
        
        color:black !important;
    }
    .navigation-nav{
        padding:0 !important;
    }

    /*arrow dropdown*/

    /*after*/


    /*before*/
    .navbar-nav .dropdown-menu{
        position: sticky !important;
    }
    @media (min-width: 992px){
        .navbar-expand-lg .navbar-nav .dropdown-menu{
            position: absolute !important;
        }
    }
    .dropdown-menu{
        background-color: #b0ded3 !important;
    }
    .dropdown-menu:after{
        content:"";
        height:0;
        width:0;
        display:block;
        position:absolute;
        pointer-events:none;
        margin-right: auto;
        left: 50%;

        bottom: 100%;
        border-bottom:7px solid #b0ded3;
        border-right:7px solid transparent;
        border-left:7px solid rgba(0, 0, 0, 0);  
        border-top:7px solid rgba(0, 0, 0, 0);  
    }
    .label-nav{
        font-size: 15px;
        cursor:pointer;
        transition: 2s;
        margin:0px;
    }
    #logo-page{
        width: 50px;
    }
    .logo-page-big{
        width: 100px !important; 
    }
    .header-navbar-transparent{
        background: transparent;
    }
    .header-navbar-transparent .link-header-navbar{
        color: black !important;
    }
    .header-navbar-color{
        background: #b0ded3;
    }
    .header-navbar-dark{
        background: black;
    }
    .header-navbar-dark .link-header-navbar{
        color: white !important;
    }
    .header-navbar-dark .link-header-navbar-sub{
        color: black !important;
        position: relative;
        text-align: center;
    }
    .header-navbar{
        transition: 2s
    }
    /* toogle menu*/
    .container-toogle-menu{
        position: relative;
    }
    .line-1-toogle-menu-dark{
        width: 40px;
        background: #000000b8;
        height: 5px;
        margin:5px;
        border-radius: 5px;
    }
    .line-2-toogle-menu-dark{
        width: 30px;
        background: #000000b8;
        height: 5px;
        margin:5px;
        border-radius: 5px;
        position: relative;
        left: 5px;
    }
    .line-1-toogle-menu-light{
        width: 40px;
        background: #ffffffb8;
        height: 5px;
        margin:5px;
        border-radius: 5px;
    }
    .line-2-toogle-menu-light{
        width: 30px;
        background: #ffffffb8;
        height: 5px;
        margin:5px;
        border-radius: 5px;
        position: relative;
        left: 5px;
    }
    .btn-toogle-menu{
        background: transparent;
        border:none !important;
    }
    .btn-toogle-menu{
        outline: none !important;
    }
    .header-navbar{
        z-index: 5;
    }
    .header-navbar-dark .link-header-navbar-sub:hover{
        background: #ffffffa6 !important;

    }
    .header-navbar-dark .link-header-navbar-sub::before{
        content:"";
        position: absolute;
        bottom: 0%;
        left: 37%;
        width:25%;
        height: 1px;
        background: black;
        display: block;
        -webkit-transform-origin:right top;
        -ms-transform-origin:right top;
        -webkit-transform:scale(0, 1);
        -ms-transform:scale(0, 1);
        -webkit-transition:transform 0.4s cubic-bezier(1, 0, 0, 1);
        transition:transform 0.4s cubic-bezier(1, 0, 0, 1);
    }
    .header-navbar-dark .link-header-navbar-sub:hover::before{
        
        -webkit-transform-origin:left top;
        -ms-transform-origin:left top;
        transform-origin:left top;
        -webkit-transform:scale(1, 1);
        -ms-transform:scale(1, 1);
        transform:scale(1, 1);
    }
</style>
<header class="header-navbar sticky-top header-navbar-color header-navbar" style="font-family: Lato;width: 100%;padding: 0px 15px;box-shadow: 0px 0px 2px 0px rgba(0,0,0,0.5);">
    <nav class="navbar navbar-expand-lg navbar-light a-bg navigation-nav">
      <a class="navbar-brand" href="#">
          <img id="logo-page" src="image/logo/logoBw.png">
      </a>
      <button class="navbar-toggler btn-toogle-menu" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <div class="container-toogle-menu">
            <p class="design-funnel-l-1 line-1-toogle-menu-light">
            </p><p class="design-funnel-l-1 line-1-toogle-menu-light">
            </p><p class="design-funnel-l-2 line-2-toogle-menu-light">
            </p></div>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link link-header-navbar navigation-link dropdown-toggle label-nav" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">

                      <label class="label-nav">Bettas</label>
                  </a>
                  <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                    <a class="dropdown-item link-header-navbar-sub" href="#">
                        <label class="label-nav">Corona</label>
                    </a>
                    <a class="dropdown-item link-header-navbar-sub" href="#">
                        <label class="label-nav">Media luna</label>
                    </a>
                    <a class="dropdown-item link-header-navbar-sub" href="#">
                        <label class="label-nav">Dumbo</label>
                    </a>
                    <a class="dropdown-item link-header-navbar-sub" href="#">
                        <label class="label-nav">Plakat</label>
                    </a>
                </div>
            </li>
            <li class="nav-item active">
                <a class="nav-link link-header-navbar navigation-link" href="#">
                    <label class="label-nav">Accesorios</label>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link sticky-top navigation-link link-header-navbar" href="#">
                    <label class="label-nav">Blog</label>
                </a>
            </li>


        </ul>
        <!--<form class="form-inline my-2 my-lg-0">
          <input class="form-control mr-sm-2" type="search" placeholder="Search" aria-label="Search">
          <button class="btn btn-outline-success my-2 my-sm-0" type="submit">Search</button>
      </form>-->
  </div>
</nav>
</header>
<script type="text/javascript">
    /*$(document).scroll(function(){
        console.log($(document).scrollTop());
        if($(document).scrollTop() == 0){
            //$("#logo-page").addClass("logo-page-big");
            //$("header").removeClass("header-navbar-dark");
            //$("header").addClass("header-navbar-transparent");
            $(".design-funnel-l-1").addClass("line-1-toogle-menu-dark");
            $(".design-funnel-l-1").removeClass("line-1-toogle-menu-light");
            $(".design-funnel-l-2").addClass("line-2-toogle-menu-dark");
            $(".design-funnel-l-2").removeClass("line-2-toogle-menu-light");
        }else{
            //$("header").addClass("header-navbar-dark");
            //$("header").removeClass("header-navbar-transparent");
            $(".design-funnel-l-1").removeClass("line-1-toogle-menu-dark");
            $(".design-funnel-l-1").addClass("line-1-toogle-menu-light");
            $(".design-funnel-l-2").removeClass("line-2-toogle-menu-dark");
            $(".design-funnel-l-2").addClass("line-2-toogle-menu-light");
            //$("#logo-page").removeClass("logo-page-big");
        }
    })*/
</script>

