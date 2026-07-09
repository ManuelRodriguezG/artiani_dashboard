<?php
ob_start();
$path = "media/apps/ecommerce/logo/artiani_logo_negro.png";
$type = pathinfo($path, PATHINFO_EXTENSION);
$data = file_get_contents($path);
$base64img = 'data:image/' . $type . ';base64,' . base64_encode($data);
$catalogo_nombre = 'null';
if ($_GET['clasificacion'] && $_GET['categoria']) {
    $catalogo_nombre = $_GET['categoria'];
} else {
    if ($_GET['clasificacion']) {
        $catalogo_nombre = $_GET['clasificacion'];
    }
}
//$catalogo_nombre = !isset($_GET['clasificacion']) ? $_GET['clasificacion'] : $catalogo_nombre != 'null' ? $catalogo_nombre : 'null';
//$catalogo_nombre = 'beay-' . $catalogo_nombre;
$array = array(
    "perros-y-gatos"
);
?>
<html>
    <style>
        @page {
            margin:0;
        }
        #invoice-POS{
            box-shadow: 0 0 1in -0.25in rgba(0, 0, 0, 0.5);
            /*padding:2mm;*/
            margin: 0 auto;
            width: 40mm;
            background: #FFF;

        }
        ::selection {
            background: #f31544;
            color: #FFF;
        }
        ::moz-selection {
            background: #f31544;
            color: #FFF;
        }
        h1{
            font-size: 1.5em;
            color: #222;
        }
        h2{
            font-size: .9em;
        }
        h3{
            font-size: 1.2em;
            font-weight: 300;
            line-height: 2em;
        }
        p{
            font-size: .7em;
            color: #666;
        }

        #top, #mid,#bot{ /* Targets all id with 'col-' */
            border-bottom: 1px solid #EEE;
        }

        #top{
            margin-top:10px;
        }
        #mid{
            min-height: 50px;
        }
        #bot{
            min-height: 30px;
        }

        #top .logo{
        }
        .clientlogo{

            background: url(http://michaeltruong.ca/images/client.jpg) no-repeat;

        }
        .info{
            display: block;
            margin-left: 0;
        }
        .title{
            float: right;
        }
        .title p{
            text-align: right;
        }
        table{
            width: 100%;
        }
        td{
        }
        .tabletitle{
            font-size: .5em;
            background: #EEE;
        }
        .service{
            border-bottom: 1px solid #EEE;
        }
        .item{
        }
        .itemtext{
            font-size: .5em;
        }

        #legalcopy{
        }
        .tableitem p{
            margin: 0;
        }




    </style>
    <body style="margin: 0;">
        <div id="invoice-POS">

            <center id="top">
                <div class="logo" style="text-align:center;justify-content: center;">
                    <img src="<?= $base64img ?>" style="width: 50px;">
                </div>
                <div class="info"> 
                    <h2 style="margin: 0;color:black;">Artiani</h2>
                </div><!--End Info-->
            </center>
            <!--End InvoiceTop-->

            <div id="mid">
                <div class="info" style="font-size: 8px;text-align: center;">
                    <p style="margin: 0;margin-top: 0px;">RFC: ROGM9604248P5<br>
                    </p>
                    <p style="margin: 0;">Fecha: 08/04/2024 - 01:55:25<br>
                    </p>
                    <p style="margin: 0;">Folio: AR25<br>
                    </p>
                    <p style="margin: 0;">Ave. Francisco Javier Mina #967 Oblatos<br>
                    </p>
                    <p style="margin: 0;">Guadalajara, Jal - TEL 3322068429<br>
                    </p>
                    <p style="margin: 0;">MANUEL ALEJANDRO RODRIGUEZ GUTIERREZ<br>
                    </p>
                    <p style="margin: 0;">Vendedor: Miguel<br>
                    </p>
                </div>
            </div>
            <!--End Invoice Mid-->

            <div id="bot">
                <div id="table">
                    <table style="width: 100%;font-size:7px;">
                        <tbody style="width: 100%;">
                            <tr class="service" style="width: 100%;">
                                <td class="Hours">
                                    <p>
                                        Cant.
                                    </p>
                                </td>
                                <td class="item">
                                    <p>
                                        Producto
                                    </p>
                                </td>
                                <td class="Price">
                                    <p>
                                        Precio
                                    </p>
                                </td>
                                <td class="Rate">
                                    <p>
                                        Sub.
                                    </p>
                                </td>
                            </tr>

                            <tr class="service">
                                <td class="tableitem">
                                    <p class="itemtext">
                                        5
                                    </p>
                                </td>
                                <td class="tableitem">
                                    <p class="itemtext">
                                        Jaula para conejo spf-3fdf III (spf-453)
                                    </p>
                                </td>
                                <td class="tableitem">
                                    <p class="itemtext">
                                        $75.00
                                    </p>
                                </td>
                                <td class="tableitem">
                                    <p class="itemtext">
                                        $375.00
                                    </p>
                                </td>
                            </tr>
                            <tr class="service">
                                <td class="tableitem">
                                    <p class="itemtext">
                                        5
                                    </p>
                                </td>
                                <td class="tableitem">
                                    <p class="itemtext">
                                        Jaula para conejo spf-3fdf III (spf-453)
                                    </p>
                                </td>
                                <td class="tableitem">
                                    <p class="itemtext">
                                        $75.00
                                    </p>
                                </td>
                                <td class="tableitem">
                                    <p class="itemtext">
                                        $375.00
                                    </p>
                                </td>
                            </tr>
                            <tr class="service">
                                <td></td>
                                <td></td>
                                <td class="tableitem">
                                    <p class="itemtext">
                                        Tax.
                                    </p>
                                </td>
                                <td class="tableitem">
                                    <p class="itemtext">
                                        $419.25
                                    </p>
                                </td>
                            </tr>

                            <tr class="service">
                                <td></td>
                                <td></td>
                                <td class="tableitem">
                                    <p class="itemtext">
                                        Total
                                    </p>
                                </td>
                                <td class="tableitem">
                                    <p class="itemtext">
                                        $3,644.25
                                    </p>
                                </td>
                            </tr>
                            <tr class="service">
                                <td></td> 
                                <td class="tableitem">
                                    <p class="itemtext">
                                        Método(s) Pago:
                                    </p>
                                </td> 
                                <td class="tableitem">
                                    <p class="itemtext">
                                        Efectivo
                                    </p>
                                </td>
                                <td class="tableitem">
                                    <p class="itemtext">
                                        $15.00
                                    </p>
                                </td>
                            </tr>
                            <tr class="service">
                                <td></td> 
                                <td></td> 
                                <td class="tableitem">
                                    <p class="itemtext">
                                        Transferencia
                                    </p>
                                </td>
                                <td class="tableitem">
                                    <p class="itemtext">
                                        $15.00
                                    </p>
                                </td>
                            </tr>
                            <tr class="service">
                                <td></td> 
                                <td></td> 
                                <td class="tableitem">
                                    <p class="itemtext">
                                        Tarjeta de crédito
                                    </p>
                                </td>
                                <td class="tableitem">
                                    <p class="itemtext">
                                        $1,500.00
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!--End Table-->
                <div id="legalcopy">
                    <p class="legal" style="text-align: center;">
                        <strong style="font-size: 11px;">
                            Gracias por su compra!
                        </strong>
                    </p>
                    <p class="legal" style="text-align: center;margin: 0;font-size: 6px;">
                        Consulta de términos y condiciones en el siguiente enlace: https://artiani.com.mx/legales/terminos_y_condiciones
                    </p>
                    <p class="legal" style="text-align: center;margin: 0;font-size: 6px;">
                        Consulta de políticas de cambios en el siguiente enlace: https://artiani.com.mx/legales/politicas_de_cambios
                    </p>
                    <p class="legal" style="text-align: center;margin: 0;font-size: 6px;">
                        Guarde su ticket para futuros cambios y aclaraciones, ya que sin su ticket no hay cambios ni devoluciones.
                    </p>
                </div>

            </div>
            <!--End InvoiceBot-->

        </div>
        <!--End Invoice-->

    </body>
    <!--end::Body-->
</html>
<?php
include_once "../app/helpers/PDF/autoload.inc.php";

// reference the Dompdf namespace
use Dompdf\Dompdf;

// instantiate and use the dompdf class
$dompdf = new Dompdf();
$dompdf->loadHtml(ob_get_clean());

// (Optional) Setup the paper size and orientation
//portrait
//lanscape
$orientation = 'landscape';
$customPaper = array(0, 0, 113.386, 283.465);
$dompdf->setPaper($customPaper, 'portrait');

// Render the HTML as PDF
$dompdf->render();

// Output the generated PDF to Browser
$dompdf->stream("ticket-1029.pdf");
