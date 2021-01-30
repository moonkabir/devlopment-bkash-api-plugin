var accessToken='';
    jQuery(document).ready(function(){
        jQuery.ajax({
            url: "http://localhost/demo/wp-content/plugins/moon-bkash-plugin-with-api/token.php",
            type: 'POST',
            contentType: 'application/json',
            success: function (data) {
                console.log('got data from token  ..');
                // console.log(JSON.stringify(data));
                
                accessToken=JSON.stringify(data);
            },
            error: function(){
                console.log('error');     
            }
        });

        var paymentConfig={
            createCheckoutURL:"http://localhost/demo/wp-content/plugins/moon-bkash-plugin-with-api/createpayment.php",
            executeCheckoutURL:"http://localhost/demo/wp-content/plugins/moon-bkash-plugin-with-api/executepayment.php",
        };

        var paymentRequest;
        paymentRequest = { amount:'10',intent:'sale'};
        console.log(JSON.stringify(paymentRequest));

        bKash.init({
            paymentMode: 'checkout',
            paymentRequest: paymentRequest,
            createRequest: function(request){
                console.log('=> createRequest (request) :: ');
                console.log(request);
                
                jQuery.ajax({
                    url: 'http://localhost/demo/wp-content/plugins/moon-bkash-plugin-with-api//paymentConfig.createCheckoutURL+"?amount="+paymentRequest.amount',
                    type:'GET',
                    contentType: 'application/json',
                    success: function(data) {
                        console.log('got data from create  ..');
                        console.log('data ::=>');
                        console.log(JSON.stringify(data));
                        
                        var obj = JSON.parse(data);
                        const URL = 'http://localhost/demo/wp-content/plugins/moon-bkash-plugin-with-api//config.json';
                        var paymentID = obj.paymentID;
                        var invoiceID = obj.merchantInvoiceNumber;
                        fetch(URL)
                            .then(response => response.json())
                            .then(data =>console.log(data));


                        // url.setItem('paumentID', paymentID);

                        console.log(paymentID, invoiceID);

                        if(data && obj.paymentID != null){
                            paymentID = obj.paymentID;
                            bKash.create().onSuccess(obj);
                        }
                        else {
                            console.log('error');
                            bKash.create().onError();
                        }
                    },
                    error: function(){
                        console.log('error');
                        bKash.create().onError();
                    }
                });
            },
            
            executeRequestOnAuthorization: function(){
                console.log('=> executeRequestOnAuthorization');
                jQuery.ajax({
                    url: paymentConfig.executeCheckoutURL+"?paymentID="+paymentID,
                    type: 'GET',
                    contentType:'application/json',
                    success: function(data){
                        console.log('got data from execute  ..');
                        console.log('data ::=>');
                        console.log(JSON.stringify(data));
                        
                        data = JSON.parse(data);
                        if(data && data.paymentID != null){
                            alert('[Payment is successful] data : ' + JSON.stringify(data));
                            window.location.href = "success.html";                              
                        }
                        else {
                            bKash.execute().onError();
                        }
                    },
                    error: function(){
                        bKash.execute().onError();
                    }
                });
            }
        });
        
        console.log("Right after init ");
    
        
    });
    
    function callReconfigure(val){
        bKash.reconfigure(val);
    }

    function clickPayButton(){
        $("#bKash_button").trigger('click');
    }