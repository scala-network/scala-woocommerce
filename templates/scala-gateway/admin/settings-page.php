<?php foreach($errors as $error): ?>
<div class="error"><p><strong>Scala Gateway Error</strong>: <?php echo $error; ?></p></div>
<?php endforeach; ?>

<h1>Scala Gateway Settings</h1>

<?php if($confirm_type === 'scala-wallet-rpc'): ?>
<div style="border:1px solid #ddd;padding:5px 10px;">
    <?php
         echo 'Wallet height: ' . $balance['height'] . '</br>';
         echo 'Your balance is: ' . $balance['balance'] . '</br>';
         echo 'Unlocked balance: ' . $balance['unlocked_balance'] . '</br>';
         ?>
</div>
<?php endif; ?>

<table class="form-table">
    <?php echo $settings_html ?>
</table>

<h4><a href="https://github.com/scala-network/scala-woocommerce">Learn more about using the Scala payment gateway</a></h4>

<script>
function scalaUpdateFields() {
    var confirmType = jQuery("#woocommerce_scala_gateway_confirm_type").val();
    if(confirmType == "scala-wallet-rpc") {
        jQuery("#woocommerce_scala_gateway_scala_address").closest("tr").hide();
        jQuery("#woocommerce_scala_gateway_viewkey").closest("tr").hide();
        jQuery("#woocommerce_scala_gateway_daemon_host").closest("tr").show();
        jQuery("#woocommerce_scala_gateway_daemon_port").closest("tr").show();
    } else {
        jQuery("#woocommerce_scala_gateway_scala_address").closest("tr").show();
        jQuery("#woocommerce_scala_gateway_viewkey").closest("tr").show();
        jQuery("#woocommerce_scala_gateway_daemon_host").closest("tr").hide();
        jQuery("#woocommerce_scala_gateway_daemon_port").closest("tr").hide();
    }
    var useScalaPrices = jQuery("#woocommerce_scala_gateway_use_scala_price").is(":checked");
    if(useScalaPrices) {
        jQuery("#woocommerce_scala_gateway_use_scala_price_decimals").closest("tr").show();
    } else {
        jQuery("#woocommerce_scala_gateway_use_scala_price_decimals").closest("tr").hide();
    }
}
scalaUpdateFields();
jQuery("#woocommerce_scala_gateway_confirm_type").change(scalaUpdateFields);
jQuery("#woocommerce_scala_gateway_use_scala_price").change(scalaUpdateFields);
</script>

<style>
#woocommerce_scala_gateway_scala_address,
#woocommerce_scala_gateway_viewkey {
    width: 100%;
}
</style>