<?php
include 'global/config.php';
include 'global/conexion.php';
include 'carrito.php';
include 'templates/cabecera.php';

?>



<?php 
    if($_POST){
        $total=0;
        $SID=session_id();
        $correo=$_POST['email'];

        foreach($_SESSION['CARRITO'] as $indice=>$producto){
            $total=$total+($producto['PRECIO']*$producto['CANTIDAD']);
        }

            $sentencia=$pdo->prepare("INSERT INTO `tblventas` 
            (`ID`, `ClaveTransaccion`, `PaypalDatos`, `Fecha`, `Correo`, `Total`, `Status`) 
            VALUES (NULL, :ClaveTransaccion, '', NOW(), :Correo, :Total, 'pendiente');"
        );
            $sentencia->bindParam(":ClaveTransaccion",$SID);
            $sentencia->bindParam(":Correo",$correo);
            $sentencia->bindParam(":Total",$total);
            $sentencia->execute();
            $idventa=$pdo->lastInsertId();

            foreach($_SESSION['CARRITO'] as $indice=>$producto){
                $sentencia=$pdo->prepare("INSERT INTO `tbldetalleventa` 
                (`ID`, `IDVENTA`, `IDPRODUCTO`, `PRECIOUNITARIO`, `CANTIDAD`, `DESCARGADO`) 
                VALUES (NULL, :IDVENTA, :IDPRODUCTO, :PRECIOUNITARIO, :CANTIDAD, '0');"
                );

                $sentencia->bindParam(":IDVENTA",$idventa);
                $sentencia->bindParam(":IDPRODUCTO",$producto['ID']);
                $sentencia->bindParam(":PRECIOUNITARIO", $producto['PRECIO']);
                $sentencia->bindParam(":CANTIDAD", $producto['CANTIDAD']);
                $sentencia->execute();
            }
        //echo "<h3>". $total . "</h3>";
    }
?>



<div class="jumbotron text-center">
    <h1 class="display-4">¡Paso Final!</h1>
    <hr class="my-4">
    <p class="lead">Estas a punto de pagar con paypal la cantidad de:
        <h4>$<?php echo number_format($total,2); ?></h4>
        <div id="paypal-button-container"></div>
    </p>
    
    <p>Los productos podrán ser descargados una vez que se procese el pago<br/>
        <strong>(Para aclaraciones :jolubaqui@gmail.com)</strong>
    </p>
</div>

<script>
        // Render the PayPal button into #paypal-button-container
        paypal.Buttons({

            // Set up the transaction
            createOrder: function(data, actions) {
                return actions.order.create({
                    purchase_units: [{
                        amount: {
                            value: '<?php echo $total;?>'
                        },
                        description:"Compra d productos a Jolubaqui_Developer: $<?php echo number_format($total,2);?>",
                        custom:"<?php echo $SID;?>#<?php echo openssl_encrypt($idventa, COD, KEY);?>"
                    }]
                });
            },

            // Finalize the transaction
            onApprove: function(data, actions) {
                return actions.order.capture().then(function(orderData) {
                    // Successful capture! For demo purposes:
                    console.log('Capture result', orderData, JSON.stringify(orderData, null, 2));
                    var transaction = orderData.purchase_units[0].payments.captures[0];
                    alert('Transaction '+ transaction.status + ': ' + transaction.id + '\n\nSee console for all available details');
                    window.location="verificador.php?paymentToken="+data.paymentToken

                    // Replace the above to show a success message within this page, e.g.
                    // const element = document.getElementById('paypal-button-container');
                    // element.innerHTML = '';
                    // element.innerHTML = '<h3>Thank you for your payment!</h3>';
                    // Or go to another URL:  actions.redirect('thank_you.html');
                });
            }


        }).render('#paypal-button-container');
    </script>


<?php include 'templates/pie.php'; ?>