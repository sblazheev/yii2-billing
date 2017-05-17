/**
 * @author Harry Tang <harry@modernkernel.com>
 * @link https://modernkernel.com
 * @copyright Copyright (c) 2017 Modern Kernel
 */
function setAddress(){
    $("#btc-address").html($("#btc-address").data('addr'));
}

function checkPayment() {
    var url=$("#check-payment-url").data("check-payment-url");
    //alert(url);
    $.ajax({
        url: url,
    })
        .done(function( html ) {
            $( "#payment-result" ).html( html );
        });
}

$("#copy-tab").on("click", "#btc-address", function () {
    var $temp = $("<input>");
    $("body").append($temp);
    $temp.val($(this).data('addr')).select();
    document.execCommand("copy");
    $temp.remove();
    $(this).html($(this).data('copied'));
    setTimeout(setAddress, 2000);
});

setInterval(checkPayment, 5000);