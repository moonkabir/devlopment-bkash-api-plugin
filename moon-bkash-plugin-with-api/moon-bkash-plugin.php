<?php
/*
 * Plugin Name: WooCommerce bkash Payment Gateway
 * Plugin URI: https://github.com/moonkabir/bkash-plugin
 * Description: Take credit card payments on your store.
 * Author: Moon kabir
 * Author URI: https://moonkabir.xyz
 * Version: 1.0.1
 * Text Domain: moon-bkash-plugin
 * Domain Path: /languages/
*/

function moon_bkash_plugin_load_textdomain()
{
    load_plugin_textdomain('moon-bkash-plugin', false, dirname(__FILE__) . "/languages");

}
add_action('plugins_loaded', 'moon_bkash_plugin_load_textdomain');

/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter( 'woocommerce_payment_gateways', 'misha_add_gateway_class' );
function misha_add_gateway_class( $gateways ) {
	$gateways[] = 'WC_Misha_Gateway'; // your class name is here
	return $gateways;
}
 
/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action( 'plugins_loaded', 'misha_init_gateway_class' );
function misha_init_gateway_class() {
 
	class WC_Misha_Gateway extends WC_Payment_Gateway {
        
 		/**
 		 * Class constructor, more about it in Step 3
 		 */
        public function __construct() {

            $this->id = 'misha'; // payment gateway plugin ID
            $this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
            $this->has_fields = true; // in case you need a custom credit card form
            $this->method_title = 'Misha Gateway';
            $this->method_description = 'Description of Misha payment gateway'; // will be displayed on the options page
         
            // gateways can support subscriptions, refunds, saved payment methods,
            // but in this tutorial we begin with simple payments
            $this->supports = array(
                'products'
            );
         
            // Method with all the options fields
            $this->init_form_fields();
            $this->token();
            // $this->createpayment();
            // $this->executepayment();


            // Load the settings.
            $this->init_settings();
            $this->title = $this->get_option( 'title' );
            $this->description = $this->get_option( 'description' );
            $this->enabled = $this->get_option( 'enabled' );
            $this->testmode = 'yes' === $this->get_option( 'testmode' );
            $this->private_key = $this->testmode ? $this->get_option( 'test_private_key' ) : $this->get_option( 'private_key' );
            $this->publishable_key = $this->testmode ? $this->get_option( 'test_publishable_key' ) : $this->get_option( 'publishable_key' );
         
            // This action hook saves the settings
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
         
            // We need custom JavaScript to obtain a token
            add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
         
            // You can also register a webhook here
            add_action( 'woocommerce_api_{webhook name}', array( $this, 'webhook' ) );
        }
        public function token()
        {
            // session_start();

            $url = dirname(__FILE__) . '/config.json';
            $request_token = $this->_bkash_Get_Token();
            $idtoken = $request_token['id_token'];
            $_SESSION['token'] = $idtoken;
            $strJsonFileContents = file_get_contents($url);
            $array = json_decode($strJsonFileContents, true);
            $array = $this->_get_config_file();
            $array['token'] = $idtoken;
            $newJsonString = json_encode($array);


            echo $idtoken;

            // die();
            // File::put(storage_path() . '/app/public/config.json', $newJsonString);
            // $url = dirname(__FILE__) . '/config.json';

            file_put_contents($url,$newJsonString);
    
            // echo $idtoken;
        }
    
        protected function _bkash_Get_Token()
        {
            /*$strJsonFileContents = file_get_contents("config.json");
            $array = json_decode($strJsonFileContents, true);*/
    
            $array = $this->_get_config_file();
    
            $post_token = array(
                'app_key' => $array["app_key"],
                'app_secret' => $array["app_secret"]
            );
    
            $url = curl_init($array["tokenURL"]);
            // $proxy = $array["proxy"];
            $posttoken = json_encode($post_token);
            $header = array(
                'Content-Type:application/json',
                'password:' . $array["password"],
                'username:' . $array["username"]
            );
    
            curl_setopt($url, CURLOPT_HTTPHEADER, $header);
            curl_setopt($url, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($url, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($url, CURLOPT_POSTFIELDS, $posttoken);
            curl_setopt($url, CURLOPT_FOLLOWLOCATION, 1);
            //curl_setopt($url, CURLOPT_PROXY, $proxy);
            $resultdata = curl_exec($url);
            curl_close($url);
            return json_decode($resultdata, true);
        }
    
        protected function _get_config_file()
        {
            $path = dirname(__FILE__) . '/config.json';
            return json_decode(file_get_contents($path), true);
        }

            // $url = dirname(__FILE__) . '/config.json';
            // echo $url;
            // die();



        public function createpayment()
        {
            // session_start();
    
            /*$strJsonFileContents = file_get_contents("config.json");
            $array = json_decode($strJsonFileContents, true);*/
    
            $array = $this->_get_config_file();
    
            $amount = WC()->cart->cart_contents_total;
            $invoice = "BKSHMOON".rand(10,10000000000); // must be unique
            $intent = "sale";
            // $proxy = $array["proxy"];
            $createpaybody = array('amount' => $amount, 'currency' => 'BDT', 'merchantInvoiceNumber' => $invoice, 'intent' => $intent);
            $url = curl_init($array["createURL"]);
    
            $createpaybodyx = json_encode($createpaybody);
    
            $header = array(
                'Content-Type:application/json',
                'authorization:' . $array["token"],
                'x-app-key:' . $array["app_key"]
            );
    
            curl_setopt($url, CURLOPT_HTTPHEADER, $header);
            curl_setopt($url, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($url, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($url, CURLOPT_POSTFIELDS, $createpaybodyx);
            curl_setopt($url, CURLOPT_FOLLOWLOCATION, 1);
            // curl_setopt($url, CURLOPT_PROXY, $proxy);
    
            $resultdata = curl_exec($url);
            curl_close($url);
            echo $resultdata;
        }
        
        public function executepayment()
        {
            // session_start();
    
            /*$strJsonFileContents = file_get_contents("config.json");
            $array = json_decode($strJsonFileContents, true);*/
    
            $array = $this->_get_config_file();
    
            $paymentID = "8AZN30C1611742243055";
            // $proxy = $array["proxy"];
    
            $url = curl_init($array["executeURL"] . $paymentID);
    
            $header = array(
                'Content-Type:application/json',
                'authorization:' . $array["token"],
                'x-app-key:' . $array["app_key"]
            );
    
            curl_setopt($url, CURLOPT_HTTPHEADER, $header);
            curl_setopt($url, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($url, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($url, CURLOPT_FOLLOWLOCATION, 1);
            // curl_setopt($url, CURLOPT_PROXY, $proxy);
    
            $resultdatax = curl_exec($url);
            curl_close($url);
    
            $this->_updateOrderStatus($resultdatax);
    
            // echo $resultdatax;
        }
    
        protected function _updateOrderStatus($resultdatax)
        {
            $resultdatax = json_decode($resultdatax);
    
            // if ($resultdatax && $resultdatax->paymentID != null && $resultdatax->transactionStatus == 'Completed') {
            //     DB::table('orders')->where([
            //         'invoice' => $resultdatax->merchantInvoiceNumber
            //     ])->update([
            //         'status' => 'Processing', 'trxID' => $resultdatax->trxID
            //     ]);
            // }
        }

























		/**
 		 * Plugin options, we deal with it in Step 3 too
 		 */
        public function init_form_fields(){


            // $url = dirname(__FILE__) . '/config.json';
            // echo $url;
            // die();

            $this->form_fields = array(
                'enabled' => array(
                    'title'       => 'Enable/Disable',
                    'label'       => 'Enable Misha Gateway',
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'no'
                ),
                'title' => array(
                    'title'       => 'Title',
                    'type'        => 'text',
                    'description' => 'This controls the title which the user sees during checkout.',
                    'default'     => 'Credit Card',
                    'desc_tip'    => true,
                ),
                'description' => array(
                    'title'       => 'Description',
                    'type'        => 'textarea',
                    'description' => 'This controls the description which the user sees during checkout.',
                    'default'     => 'Pay with your credit card via our super-cool payment gateway.',
                ),
                'testmode' => array(
                    'title'       => 'Test mode',
                    'label'       => 'Enable Test Mode',
                    'type'        => 'checkbox',
                    'description' => 'Place the payment gateway in test mode using test API keys.',
                    'default'     => 'yes',
                    'desc_tip'    => true,
                ),
                'test_publishable_key' => array(
                    'title'       => 'Test Publishable Key',
                    'type'        => 'text'
                ),
                'test_private_key' => array(
                    'title'       => 'Test Private Key',
                    'type'        => 'password',
                ),
                'publishable_key' => array(
                    'title'       => 'Live Publishable Key',
                    'type'        => 'text'
                ),
                'private_key' => array(
                    'title'       => 'Live Private Key',
                    'type'        => 'password'
                )
            );
        }
 
		/**
		 * You will need it if you want your custom credit card form, Step 4 is about it
		 */
		// public function payment_fields() {
 
		// ...
 
		// }
 
		/*
		 * Custom CSS and JS, in most cases required only when you decided to go with a custom credit card form
		 */
        public function payment_scripts() {
 
            // we need JavaScript to process a token only on cart/checkout pages, right?
            if ( ! is_cart() && ! is_checkout() && ! isset( $_GET['pay_for_order'] ) ) {
                return;
            }
         
            // if our payment gateway is disabled, we do not have to enqueue JS too
            if ( 'no' === $this->enabled ) {
                return;
            }
         
            // no reason to enqueue JavaScript if API keys are not set
            if ( empty( $this->private_key ) || empty( $this->publishable_key ) ) {
                return;
            }
         
            // do not work with card detailes without SSL unless your website is in a test mode
            if ( ! $this->testmode && ! is_ssl() ) {
                return;
            }
         
            // let's suppose it is our payment processor JavaScript that allows to obtain a token
            wp_enqueue_script( 'misha_js', 'https://scripts.sandbox.bka.sh/versions/1.2.0-beta/checkout/bKash-checkout-sandbox.js' );
         
            // and this is our custom JS in your plugin directory that works with token.js
            wp_register_script( 'woocommerce_misha', plugins_url( 'misha.js', __FILE__ ), array( 'jquery', 'misha_js' ) );
         
            // in most payment processors you have to use PUBLIC KEY to obtain a token
            wp_localize_script( 'woocommerce_misha', 'misha_params', array(
                'publishableKey' => $this->publishable_key
            ) );
         
            wp_enqueue_script( 'woocommerce_misha' );
            wp_enqueue_script( "moon-bkash-plugin-js", plugins_url( 'js/main.js', __FILE__ ),array( "jquery" ), '1.0.0', true );
            // wp_enqueue_script( "main-js", get_theme_file_uri("/assets/js/main.js"),array( "jquery" ), '1.0.0', true );
        }
 
		/*
 		 * Fields validation, more in Step 5
		 */
		public function payment_fields() {
 
            // ok, let's display some description before the payment form
            if ( $this->description ) {
                // you can instructions for test mode, I mean test card numbers etc.
                if ( $this->testmode ) {
                    $this->description .= ' TEST MODE ENABLED. In test mode, you can use the card numbers listed in <a href="#" target="_blank" rel="noopener noreferrer">documentation</a>.';
                    $this->description  = trim( $this->description );
                }
                // display the description with <p> tags etc.
                echo wpautop( wp_kses_post( $this->description ) );
            }
         
            // I will echo() the form, but you can close PHP tags and print it directly in HTML
            echo '<fieldset id="wc-' . esc_attr( $this->id ) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';
         
            // Add this action hook if you want your custom payment gateway to support it
            do_action( 'woocommerce_credit_card_form_start', $this->id );
         
            // I recommend to use inique IDs, because other gateways could already use #ccNo, #expdate, #cvc
            echo '<button id="bKash_button">Pay With bKash with Moon</button>'; 
         
            do_action( 'woocommerce_credit_card_form_end', $this->id );
         
            echo '<div class="clear"></div></fieldset>';
         
        }
 
		/*
		 * We're processing the payments here, everything about it is in Step 5
		 */
		public function process_payment( $order_id ) {
 
            global $woocommerce;
         
            // we need it to get any order detailes
            $order = wc_get_order( $order_id );
         
         
            /*
              * Array with parameters for API interaction
             */

            $args = array(

                

            );

        //  print_r($order->total);
            /*
             * Your API interaction could be built with wp_remote_post()
              */
             $response = wp_remote_post( '{payment processor endpoint}', $args );
         
         
             if( !is_wp_error( $response ) ) {
         
                 $body = json_decode( $response['body'], true );
         
                 // it could be different depending on your payment processor
                 if ( $body['response']['responseCode'] == 'APPROVED' ) {
         
                    // we received the payment
                    $order->payment_complete();
                    // $order->reduce_order_stock();
         
                    // some notes to customer (replace true with false to make it private)
                    $order->add_order_note( 'Hey, your order is paid! Thank you!', true );
         
                    // Empty cart
                    $woocommerce->cart->empty_cart();
         
                    // Redirect to the thank you page
                    return array(
                        'result' => 'success',
                        'redirect' => $this->get_return_url( $order )
                    );
         
                 } else {
                    wc_add_notice(  'Please try again.', 'error' );
                    return;
                }
         
            } else {
                wc_add_notice(  'Connection error.', 'error' );
                return;
            }
         
        }
 
		/*
		 * In case you need a webhook, like PayPal IPN etc
		 */
		public function webhook() {
 
            $order = wc_get_order( $_GET['id'] );
            $order->payment_complete();
            // $order->reduce_order_stock();s
         
            update_option('webhook_debug', $_GET);
        }
        
     }
    function wpb_hook_javascript() {
        // if (is_page ('8')) { 
            ?>
                <script type="text/javascript">
                    var accessToken='';
                    $(document).ready(function(){
                        $.ajax({
                            url: "http://localhost/lead-demo/wp-content/plugins/moon-bkash-plugin/token.php",
                            type: 'POST',
                            contentType: 'application/json',
                            success: function (data) {
                                console.log('got data from token  ..');
                                console.log(JSON.stringify(data));
                                
                                accessToken=JSON.stringify(data);
                            },
                            error: function(){
                                console.log('error');     
                            }
                        });

                        var paymentConfig={
                            createCheckoutURL:"http://localhost/lead-demo/wp-content/plugins/moon-bkash-plugin/createpayment.php",
                            executeCheckoutURL:"http://localhost/lead-demo/wp-content/plugins/moon-bkash-plugin/executepayment.php",
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
                                
                                $.ajax({
                                    url: 'http://localhost/lead-demo/wp-content/plugins/moon-bkash-plugin/paymentConfig.createCheckoutURL+"?amount="+paymentRequest.amount',
                                    type:'GET',
                                    contentType: 'application/json',
                                    success: function(data) {
                                        console.log('got data from create  ..');
                                        console.log('data ::=>');
                                        console.log(JSON.stringify(data));
                                        
                                        var obj = JSON.parse(data);


                                        // var url = 'config.json';
                                        // console.log(url)

                                        const URL = 'http://localhost/lead-demo/wp-content/plugins/moon-bkash-plugin/config.json';
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
                                $.ajax({
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
                </script>
            <?php
    }
    add_action('wp_footer', 'wpb_hook_javascript');
}