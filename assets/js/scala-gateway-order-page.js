/*
 * Copyright (c) 2018, Ryo Currency Project
*/
function scala_showNotification(message, type='success') {
    var toast = jQuery('<div class="' + type + '"><span>' + message + '</span></div>');
    jQuery('#scala_toast').append(toast);
    toast.animate({ "right": "12px" }, "fast");
    setInterval(function() {
        toast.animate({ "right": "-400px" }, "fast", function() {
            toast.remove();
        });
    }, 2500)
}
function scala_showQR(show=true) {
    jQuery('#scala_qr_code_container').toggle(show);
}
function scala_fetchDetails() {
    var data = {
        '_': jQuery.now(),
        'order_id': scala_details.order_id
    };
    jQuery.get(scala_ajax_url, data, function(response) {
        if (typeof response.error !== 'undefined') {
            console.log(response.error);
        } else {
            scala_details = response;
            scala_updateDetails();
        }
    });
}

function scala_updateDetails() {

    var details = scala_details;

    jQuery('#scala_payment_messages').children().hide();
    switch(details.status) {
        case 'unpaid':
            jQuery('.scala_payment_unpaid').show();
            jQuery('.scala_payment_expire_time').html(details.order_expires);
            break;
        case 'partial':
            jQuery('.scala_payment_partial').show();
            jQuery('.scala_payment_expire_time').html(details.order_expires);
            break;
        case 'paid':
            jQuery('.scala_payment_paid').show();
            jQuery('.scala_confirm_time').html(details.time_to_confirm);
            jQuery('.button-row button').prop("disabled",true);
            break;
        case 'confirmed':
            jQuery('.scala_payment_confirmed').show();
            jQuery('.button-row button').prop("disabled",true);
            break;
        case 'expired':
            jQuery('.scala_payment_expired').show();
            jQuery('.button-row button').prop("disabled",true);
            break;
        case 'expired_partial':
            jQuery('.scala_payment_expired_partial').show();
            jQuery('.button-row button').prop("disabled",true);
            break;
    }

    jQuery('#scala_exchange_rate').html('1 XLA = '+details.rate_formatted+' '+details.currency);
    jQuery('#scala_total_amount').html(details.amount_total_formatted);
    jQuery('#scala_total_paid').html(details.amount_paid_formatted);
    jQuery('#scala_total_due').html(details.amount_due_formatted);

    jQuery('#scala_integrated_address').html(details.integrated_address);

    if(scala_show_qr) {
        var qr = jQuery('#scala_qr_code').html('');
        new QRCode(qr.get(0), details.qrcode_uri);
    }

    if(details.txs.length) {
        jQuery('#scala_tx_table').show();
        jQuery('#scala_tx_none').hide();
        jQuery('#scala_tx_table tbody').html('');
        for(var i=0; i < details.txs.length; i++) {
            var tx = details.txs[i];
            var height = tx.height == 0 ? 'N/A' : tx.height;
            var row = ''+
                '<tr>'+
                '<td style="word-break: break-all">'+
                '<a href="'+scala_explorer_url+'/tx/'+tx.txid+'" target="_blank">'+tx.txid+'</a>'+
                '</td>'+
                '<td>'+height+'</td>'+
                '<td>'+tx.amount_formatted+' Scala</td>'+
                '</tr>';

            jQuery('#scala_tx_table tbody').append(row);
        }
    } else {
        jQuery('#scala_tx_table').hide();
        jQuery('#scala_tx_none').show();
    }

    // Show state change notifications
    var new_txs = details.txs;
    var old_txs = scala_order_state.txs;
    if(new_txs.length != old_txs.length) {
        for(var i = 0; i < new_txs.length; i++) {
            var is_new_tx = true;
            for(var j = 0; j < old_txs.length; j++) {
                if(new_txs[i].txid == old_txs[j].txid && new_txs[i].amount == old_txs[j].amount) {
                    is_new_tx = false;
                    break;
                }
            }
            if(is_new_tx) {
                scala_showNotification('Transaction received for '+new_txs[i].amount_formatted+' Scala');
            }
        }
    }

    if(details.status != scala_order_state.status) {
        switch(details.status) {
            case 'paid':
                scala_showNotification('Your order has been paid in full');
                break;
            case 'confirmed':
                scala_showNotification('Your order has been confirmed');
                break;
            case 'expired':
            case 'expired_partial':
                scala_showNotification('Your order has expired', 'error');
                break;
        }
    }

    scala_order_state = {
        status: scala_details.status,
        txs: scala_details.txs
    };

}
jQuery(document).ready(function($) {
    if (typeof scala_details !== 'undefined') {
        scala_order_state = {
            status: scala_details.status,
            txs: scala_details.txs
        };
        setInterval(scala_fetchDetails, 30000);
        scala_updateDetails();
        new ClipboardJS('.clipboard').on('success', function(e) {
            e.clearSelection();
            if(e.trigger.disabled) return;
            switch(e.trigger.getAttribute('data-clipboard-target')) {
                case '#scala_integrated_address':
                    scala_showNotification('Copied destination address!');
                    break;
                case '#scala_total_due':
                    scala_showNotification('Copied total amount due!');
                    break;
            }
            e.clearSelection();
        });
    }
});