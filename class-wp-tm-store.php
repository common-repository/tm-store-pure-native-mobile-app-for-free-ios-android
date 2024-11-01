<?php
/**
 * WordPress TM Store
 * @package TMS
 * @author  TM Store
 */
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}
class tms_main_class
{
    /**
     *
     * @since   1.0.0
     *
     * @var     string
     */
    protected $version = '1.1.1';
    /**
     * Instance of this class.
     *
     * @since    1.0.0
     *
     * @var      object
     */
    protected static $instance = null;
    /**
     * Unique identifier for plugin.
     *
     * @since    1.0.0
     *
     * @var      string
     */
    public $plugin_slug = 'wordpress-tm-store';
    public $api_endpoint_base = 'wp-tm-store-notify/api';
    public $allowed_post_types = array('plugin-active', 'social-login', 'register', 'login', 'forget-password', 'cart_items', 'countries_list', 'splash_products', 'login_website', 'load_products', 'pole_products', 'calculate_shipping', 'woo_version', 'filter_data_price', 'filter_data_attribute', 'add_coupon', 'filter_products', 'product_variation_price', 'filter_all_products', 'menu_data', 'payment_gateway_list', 'exship_data', 'reset_password', 'product_full_data', 'upload_image', 'load_category_products', 'add_reviews','blog_info');
    /**
     * Initialize the plugin by setting localization, filters.
     *
     * @since     1.0.0
     */
    public $platform_ = 'web';
    public $user_platform = ''; // Default
    function __construct()
    {
        // Database variables
        global $wpdb;
        $this->db =& $wpdb;
        add_action('admin_init', array(
            &$this,
            'tms_register_settings'
        ));
        add_action('woocommerce_thankyou', array(
            &$this,
            'tms_notify_tm_store_abt_new_order'
        ), 11, 1);
        add_action('woocommerce_cancelled_order', array(
            &$this,
            'tms_notify_tm_store_abt_new_order'
        ), 11, 1);
        //add_action( 'woocommerce_new_order', 'tms_notify_tm_store_abt_new_order', 10, 1 );
        add_action('wp_ajax_save_tms_data', array(
            &$this,
            'tms_save_tms_data'
        ));
        add_action('init', array(
            &$this,
            'tms_add_api_endpoint'
        ));
        add_action('template_redirect', array(
            &$this,
            'tms_handle_api_endpoints'
        ));
        add_action('admin_init', array(
            &$this,
            'tms_send_social_login_api_details'
        ));
        add_action('init', array(
            &$this,
            'tms_set_checkout_page_cookie'
        ));
        add_action('tms_admin_ui_footer_end', array(
            &$this,
            'tms_add_support_link'
        ));
        add_action('init', array(
            &$this,
            'tms_myStartSession'
        ), 1);
        add_action('wp_logout', array(
            &$this,
            'tms_myEndSession'
        ));
        add_action('wp_login', array(
            &$this,
            'tms_myEndSession'
        ));
        add_action('init', array(
            &$this,
            'tms_get_user_plateform'
        ));
        add_action('admin_head', array(
            &$this,
            'tms_get_user_plateform'
        ));
        add_action('wp_head', array(
            &$this,
            'tms_get_user_plateform'
        ));
        add_action('wp_login', array(
            &$this,
            'tms_my_login_success'
        ));
    }
    function tms_get_user_plateform()
    {
        if (isset($_GET['user_platform'])) {
            $user_platform             = $_GET['user_platform'];
            $_SESSION['platform']      = 'mobile';
            $_SESSION['user_platform'] = $user_platform;
        }
        /*
         * Check if plateform is set 
         */
        if (isset($_SESSION['platform'])) {
            $platform_ = $_SESSION['platform'];
            if (isset($_SESSION['user_platform'])) {
                $user_platform = $_SESSION['user_platform'];
            } else {
                // $user_platform='Android';
            }
        } else {
            $platform_ = 'web';
        }
    }
    function tms_my_login_success()
    {
        return;
    }
    function tms_myStartSession()
    {
        if (!session_id()) {
            session_start();
        }
    }
    function tms_myEndSession()
    {
        //session_destroy ();
    }
    /**
     * Function to register activation actions
     * 
     * @since 1.0.0
     */
    function tms_plugin_activate()
    {
        //Check for WooCommerce Installment
        if (!is_plugin_active('woocommerce/woocommerce.php') and current_user_can('activate_plugins')) {
            // Stop activation redirect and show error
            wp_die('Sorry, but this plugin requires the Woocommerce to be installed and active. <br><a href="' . admin_url('plugins.php') . '">&laquo; Return to Plugins</a>');
        }
        update_option('tms_plugin_activate', true);
        $data = array(
            'site_url' => get_bloginfo('url'),
            'plugin_version' => '1.1.1'
        );
        $data = http_build_query($data);
        $url  = 'https://thetmstore.com/tmadmin/pluginUrlDetails.php';
        $ch   = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec($ch);
        curl_close($ch);
    }
    /**
     * Function to register deactivation actions
     * 
     * @since 1.0.0
     */
    function tms_plugin_deactivate_plugin()
    {
        delete_option('tms_plugin_activate');
        delete_option('tms_settings');
    }
    function tms_add_support_link()
    {
        echo '<p style="float: right;">For more Details or Queries regarding TM Store Plugin, Contact Us <a href="mailto:support@twistmobile.in">support@twistmobile.in</a></p>';
    }
    /**
     * Function to register the plugin settings options
     * 
     * @since 1.0.0
     */
    public function tms_register_settings()
    {
        register_setting('tms_register_settings', 'tms_settings');
    }
    /**
     * Function to get end-point of API
     * 
     * @since 1.0.0
     */
    function tms_getApiUrl()
    {
        if (file_exists(plugin_dir_path(__FILE__) . 'config.txt')) {
            $response = file_get_contents(plugin_dir_path(__FILE__) . 'config.txt');
            $response = json_decode($response);
            if (!empty($response)) {
                return $response->api_endpoint;
            }
        }
    }
    /**
     * Function to get userkey
     * 
     * @since 1.0.0
     */
    public function tms_getUserKey()
    {
        $sq_options = get_option('tms_settings');
        $user_key   = $sq_options['user_key'];
        return $user_key;
    }
    /**
     * Function to check if plugin is enabled
     * 
     * @since 1.0.0
     */
    public function tms_isEnabled()
    {
        $sq_options = get_option('tms_settings');
        $enable     = $sq_options['enable'];
        return $enable;
    }
    function tms_set_checkout_page_cookie()
    {
        if (isset($_REQUEST['device_type'])) {
            $device_type = (!empty($_REQUEST['device_type'])) ? $_REQUEST['device_type'] : '';
            if (!empty($device_type)) {
                $tm = intval(3600 * 24);
                setcookie("TMSDEVICE", $device_type, time() + $tm, "/");
            }
        }
    }
    function tms_notify_tm_store_abt_new_order($order_id)
    {
        //echo 'Payment Success ful-------------------------------->'.$order_id;
        if (!empty($order_id)) {
            ///echo 'Payment Success ful-------------------------------->'.$order_id;
            global $current_user;
            get_currentuserinfo();
            $user_email_id = $current_user->user_email;
            global $woocommerce;
            $order         = new WC_Order($order_id);
            $order_status  = $order->get_status();
            $user_platform = (isset($_COOKIE['TMSDEVICE'])) ? $_COOKIE['TMSDEVICE'] : '';
            if (!empty($user_platform)) {
?>
             <script type="text/javascript">
                //<![CDATA[
                var orderid = <?php
                echo $order_id;
?>;    
                var orderstatus = '<?php
                echo $order_status;
?>';      
                function sendResponse_IOS(_key, _val) {
                    var iframe = document.createElement("IFRAME"); 
                    iframe.setAttribute("src", _key + ":##sendToApp##" + _val); 
                    document.documentElement.appendChild(iframe); 
                    iframe.parentNode.removeChild(iframe); 
                    iframe = null; 
                }
                function sendResponse_ANDROID(resposne)
                {
                    Android.showToast(""+resposne);
                } 
                //]]>
                </script>
                <?php
                if ($user_platform == 'android') {
                    //echo 'ggoogog';
?>
                 <script type="text/javascript">    
                    var email_id = '<?php
                    echo $user_email_id;
?>';
                    Android.showToast("["+orderid + "]Purchase " + orderstatus+" emailid:"+email_id);    
                    </script>
                <?php
                    exit();
                }
                if ($user_platform == 'ios') {
?>

                <script type="text/javascript">    
                sendToApp( "purchase","orderid:"+orderid + ",orderstatus:" + orderstatus);
                function sendToApp(_key, _val) 
                {
                var iframe = document.createElement("IFRAME"); 
                iframe.setAttribute("src", _key + ":##sendToApp##" + _val); 
                document.documentElement.appendChild(iframe); 
                iframe.parentNode.removeChild(iframe); 
                iframe = null; 
                }    
                </script>
                <?php
                    exit();
                    if (!strcmp($platform_, 'mobile')) {
                        exit();
                    }
                }
            }
        }
    }
    function tms_save_tms_data()
    {
        $response_array = array(
            'results' => 0,
            'error' => 'Data not send.'
        );
        $nonce          = $_POST['_wpnonce'];
        if (!wp_verify_nonce($nonce, 'tms_setup_form')) {
            $response_array = array(
                'results' => 0,
                'error' => 'Security error.'
            );
            $res            = json_encode($response_array);
            die($res);
        }
        if (!current_user_can('manage_options')) {
            $response_array = array(
                'results' => 0,
                'error' => 'Security error.'
            );
            $res            = json_encode($response_array);
            die($res);
        }
        if (isset($_POST)) {
            $data                        = $_POST;
            $insertdata                  = array();
            $insertdata['username']      = (isset($_POST['username'])) ? sanitize_text_field($_POST['username']) : '';
            $insertdata['email-api']     = (isset($_POST['email-api'])) ? sanitize_email($_POST['email-api']) : '';
            $insertdata['website-api']   = (isset($_POST['website-api'])) ? esc_url_raw($_POST['website-api']) : '';
            $insertdata['wc-api-key']    = (isset($_POST['wc-api-key'])) ? sanitize_text_field($_POST['wc-api-key']) : '';
            $insertdata['wc-api-secret'] = (isset($_POST['wc-api-secret'])) ? sanitize_text_field($_POST['wc-api-secret']) : '';
            $insertdata['site_url']      = (isset($_POST['site_url'])) ? sanitize_text_field($_POST['site_url']) : '';
            $data                        = http_build_query($insertdata);
            $url                         = 'https://thetmstore.com/tmadmin/PluginFormDetails.php';
            $ch                          = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $server_output = curl_exec($ch);
            if (curl_exec($ch) === false) {
                $response_array = array(
                    'results' => 0,
                    'error' => curl_error($ch)
                );
            } else {
                curl_close($ch);
                if (!is_serialized($insertdata)) {
                    //insert/update data
                    $insertdata = maybe_serialize($insertdata);
                    update_option('tms_settings_data', $insertdata);
                }
                if ($server_output == '9999') {
                    $email_str = '';
                    if (isset($_POST['email-api'])) {
                        $email_str = sanitize_email($_POST['email-api']);
                    }
                    if ($email_str == "" || empty($email_str)) {
                        $response_array = array(
                            'results' => 3,
                            'error' => ''
                        );
                    } else {
                        $response_array = array(
                            'results' => 2,
                            'error' => ''
                        );
                    }
                } else {
                    $response_array = array(
                        'results' => 1,
                        'error' => ''
                    );
                }
            }
        }
        echo json_encode($response_array);
        die();
    }
    /**
     * Create our json endpoint by adding new rewrite rules to WordPress
     */
    function tms_add_api_endpoint()
    {
        global $wp_rewrite;
        $post_type_tag = $this->api_endpoint_base . '_type';
        $post_id_tag   = $this->api_endpoint_base . '_id';
        add_rewrite_tag("%{$post_type_tag}%", '([^&]+)');
        add_rewrite_tag("%{$post_id_tag}%", '([0-9]+)');
        add_rewrite_rule($this->api_endpoint_base . '/([^&]+)/([0-9]+)/?', 'index.php?' . $post_type_tag . '=$matches[1]&' . $post_id_tag . '=$matches[2]', 'top');
        add_rewrite_rule($this->api_endpoint_base . '/([^&]+)/?', 'index.php?' . $post_type_tag . '=$matches[1]', 'top');
        $wp_rewrite->flush_rules(false);
    }
    /**
     * Handle the request of an endpoint
     */
    function tms_handle_api_endpoints()
    {
        global $wp_query;
        // get the query args and sanitize them for confidence
        $type = sanitize_text_field($wp_query->get($this->api_endpoint_base . '_type'));
        $id   = intval($wp_query->get($this->api_endpoint_base . '_id'));
        // only allowed post_types
        if (!in_array($type, $this->allowed_post_types)) {
            return;
        }
        switch ($type) {
            case "plugin-active":
                $data = $this->tms_api_plugin_activate_status_action($_POST);
                break;
            case "social-login":
                $data = $this->tms_api_social_login_action($_POST);
                break;
            case "register":
                $data = $this->tms_api_register_action($_POST);
                break;
            case "login":
                $data = $this->tms_api_login_action($_POST);
                break;
            case "forget-password":
                $data = $this->tms_api_forget_password_action($_POST);
                break;
            case "cart_items":
                $data = $this->tms_push_to_cart($_POST);
                break;
            case "countries_list":
                $data = $this->tms_get_countries_list($_POST);
                break;
            case "splash_products":
                $data = $this->tms_get_woocommerce_product_list($_POST);
                break;
            case "payment_gateway_list":
                $data = $this->tms_get_available_payment_gateways();
                break;
            case "login_website":
                $data = $this->tms_login_website($_POST);
                break;
            case "load_products":
                $data = $this->tms_load_products($_POST);
                break;
            case "pole_products":
                $data = $this->tms_load_pole_products($_POST);
                break;
            case "calculate_shipping":
                $data = $this->tms_calculate_shipping();
                break;
            case "woo_version":
                $data = $this->tms_get_woo_version();
                break;
            case "filter_data_price":
                $data = $this->tms_filter_data_price();
                break;
            case "filter_data_attribute":
                $data = $this->tms_filter_data_attribute();
                break;
            case "add_coupon":
                $data = $this->tms_add_coupon_call($_POST);
                break;
            case "filter_products":
                $data = $this->tms_filter_products($_POST);
                break;
            case "product_variation_price":
                $data = $this->tms_product_variation_price($_POST);
                break;
            case "filter_all_products":
                $data = $this->tms_get_category_price_range($_POST);
                break;
            case "menu_data":
                $data = $this->tms_get_menu_data();
                break;
            case "exship_data":
                $data = $this->tms_get_exship_data($_POST);
                break;
            case "reset_password":
                $data = $this->tms_reset_password_website($_POST);
                break;
            case "product_full_data":
                $data = $this->tms_get_product_fulldata($_POST);
                break;
            case "upload_image":
                $data = $this->tms_get_upload_image($_POST);
                break;
            case "upload_image":
                $data = $this->tms_get_upload_image($_POST);
                break;
            case "load_category_products":
                $data = $this->tms_load_category_products($_POST);
                break;
            case "add_reviews":
                $data = $this->tms_add_product_reviews($_POST);
                break;
			case "blog_info":				
			    $data=$this->tms_get_blog_info($_POST);				
			 break;
        }
        // data is built. print as json and stop
        if (isset($data) && !empty($data)) {
            //wp_send_json( $data ); 
            echo json_encode($data);
            exit();
        } else {
            $data = array(
                'status' => 'failed',
                'error' => 1,
                'message' => 'No data received.'
            );
            //wp_send_json( $data ); 
            echo json_encode($data);
            exit();
        }
        echo '';
        exit;
    }
    function escapeJsonString($value)
    {
        $escapers     = array(
            "\\",
            "/",
            "\"",
            "\n",
            "\r",
            "\t",
            "\x08",
            "\x0c"
        );
        $replacements = array(
            "\\\\",
            "\\/",
            "\\\"",
            "\\n",
            "\\r",
            "\\t",
            "\\f",
            "\\b"
        );
        $result       = str_replace($escapers, $replacements, $value);
        return $result;
    }
    function tms_push_to_cart($postData = array())
    {
        if (isset($postData)) {
            global $woocommerce;
            $woocommerce->cart->empty_cart();
            $str         = '
            [
            {
                "pid":458,
                "variation_id":297,
                "quantity":3,
                "attributes":[
                    {
                        "name":"color",
                        "value":"red"
                    },
                    {
                        "name":"size",
                        "value":"small"
                    }
                ]
            },
            {
                "pid":459,
                "variation_id":297,
                "quantity":1,
                "attributes":[
                    {
                        "name":"color",
                        "value":"red"
                    },
                    {
                        "name":"size",
                        "value":"small"
                    }
                ]
            }
    
         ]';
            //$str_temp=$postData['cart_data'];
            //str_replace(array("/"),array(""),$str_temp);
            //$json = (array) json_decode($str_temp);
            //echo ''.count($json);
            $recived_str = stripslashes($postData['cart_data']);
            //  echo 'ship str '.$ship_str;
            $json        = json_decode($recived_str, true);
            for ($i = 0; $i < count($json); $i++) {
                $product_id   = $json[$i]["pid"];
                //echo 'inside  '.$product_id;
                $variation_id = $json[$i]['variation_id'];
                $quantity     = $json[$i]['quantity'];
                //echo 'pallavi'.$quantity;
                $spec         = array();
                for ($j = 0; $j < count($json[$i]['attributes']); $j++) {
                    $spec[$json[$i]['attributes'][$j]['name']] = $json[$i]['attributes'][$j]['value'];
                }
                // echo 'product id '.$product_id.' sss'.$quantity.' variation id '.$variation_id;
                if ($variation_id != -1) {
                    $woocommerce->cart->add_to_cart($product_id, $quantity, $variation_id, $spec, null);
                } else {
                    $woocommerce->cart->add_to_cart($product_id, $quantity);
                }
            }
        }
        //apply coupon code here
        $recived_str_coupon = stripslashes($postData['coupon_data']);
        // $recived_str_coupon='[{"id":778,"code":"a1s2d3"},{"id":779,"code":"happy100"}]';
        $coupon_data        = json_decode($recived_str_coupon, true);
        //var_dump($coupon_data);
        for ($i = 0; $i < count($coupon_data); $i++) {
            $this->tms_add_coupon($coupon_data[$i]['code']);
        }
        $shipping_methods = $this->tms_calculate_shipping($postData['ship_data']);
        $payment_gateWays = $this->tms_get_available_payment_gateways();
        $arr_data         = array(
            "shipping_data" => $shipping_methods,
            "payment" => $payment_gateWays
        );
        return $arr_data;
    }
    function tms_get_woo_version()
    {
        global $woocommerce;
        $meta_data = array(
            'woo_version' => $woocommerce->version,
            'ssl_enabled' => ('yes' === get_option('woocommerce_force_ssl_checkout')),
            'permalinks_enabled' => ('' !== get_option('permalink_structure')),
            'tm_version' => '1.1.1'
        );
        return $meta_data;
    }
    function tms_get_metadata()
    {
        $tax_settings         = "";
        $add_price_to_product = false;
        if (get_option('woocommerce_calc_taxes') == "yes") {
            if (get_option('woocommerce_tax_display_shop') == 'incl') {
                $add_price_to_product = true;
            }
            $tax_settings = array(
                "shipping_tax_class" => get_option('woocommerce_shipping_tax_class'),
                "tax_based_on" => get_option('woocommerce_tax_based_on'),
                "store_base_location" => wc_get_base_location(),
                "taxes" => $this->tms_get_taxes(),
                "woocommerce_prices_include_tax" => get_option('woocommerce_prices_include_tax'),
                "woocommerce_tax_display_shop" => get_option('woocommerce_tax_display_shop'),
                "woocommerce_prices_include_cart" => get_option('woocommerce_tax_display_cart'),
                'add_price_to_product' => $add_price_to_product
            );
        }
        global $woocommerce;
        $cart_url     = $woocommerce->cart->get_cart_url();
        $checkout_url = $woocommerce->cart->get_checkout_url();
        $meta_data    = array(
            'tz' => wc_timezone_string(),
            'c' => get_woocommerce_currency(),
            'c_f' => get_woocommerce_currency_symbol(),
            't_i' => wc_prices_include_tax(),
            'add_price_to_product' => $add_price_to_product,
            // 'taxes'       =>$this->tms_get_taxes(),
            'd_u' => get_option('woocommerce_dimension_unit'),
            //'ssl_enabled'         => ( 'yes' === get_option( 'woocommerce_force_ssl_checkout' ) ),
            //'permalinks_enabled' => ( '' !== get_option( 'permalink_structure' ) ),
            'd_s' => get_option('woocommerce_price_decimal_sep'),
            't_s' => get_option('woocommerce_price_thousand_sep'),
            'p_d' => absint(get_option('woocommerce_price_num_decimals', 2)),
            'c_p' => get_option('woocommerce_currency_pos'),
            'cart_url' => $cart_url,
            'checkout_url' => $checkout_url,
            'hide_out_of_stock' => get_option('woocommerce_hide_out_of_stock_items')
        );
        if (get_option('woocommerce_calc_taxes') == "yes") {
            $meta_data['tax_settings'] = $tax_settings;
        }
        return $meta_data;
    }
    function tms_login_website($postData = array())
    {
        if (isset($postData) && !empty($postData['user_emailID'])) {
            $_SESSION['user_platform'] = $postData['user_platform'];
            $_SESSION['platform']      = 'mobile';
            $email_id                  = $postData['user_emailID'];
            $user                      = get_user_by('email', $email_id);
            $user_id                   = $user->ID;
            if ($user) {
                wp_set_current_user($user_id, $user->user_login);
                wp_set_auth_cookie($user_id);
                do_action('wp_login', $user->user_login,$user);
            }
            global $current_user;
            get_currentuserinfo();
            $user_email_id = $current_user->user_email;
            if (isset($_SESSION['user_platform'])) {
                $platform_     = $_SESSION['platform'];
                $user_platform = $_SESSION['user_platform'];
                if (!strcmp($platform_, 'mobile')) {
                    if (!strcmp($user_platform, 'Android')) {
?>
                 <script type="text/javascript">    
                    var email_id = '<?php
                        echo $user_email_id;
?>';
                   Android.showToast("Login Successful"+email_id);  
                   </script>
               <?php
                        echo $user_email_id;
                        if (!strcmp($platform_, 'mobile')) {
                            exit();
                        }
                    }
                    if (!strcmp($user_platform, 'IOS')) {
?>

                    <script type="text/javascript">    
                    var email_id_ios = '<?php
                        echo $user_email_id;
?>';
                    sendToApp( "Login","Successful email:"+email_id_ios);
                    function sendToApp(_key, _val) 
                    {
                    var iframe = document.createElement("IFRAME"); 
                    iframe.setAttribute("src", _key + ":##sendToApp##" + _val); 
                    document.documentElement.appendChild(iframe); 
                    iframe.parentNode.removeChild(iframe); 
                    iframe = null; 
                    }    
                    </script>
                    <?php
                        exit();
                        if (!strcmp($platform_, 'mobile')) {
                            exit();
                        }
                    }
                }
            }
        }
    }
    function tms_get_available_payment_gateways()
    {
        $available_payment_gateways = WC()->payment_gateways()->get_available_payment_gateways();
        //return $available_payment_gateways;
        $gateway_list               = array();
        foreach ($available_payment_gateways as $key => $gateway) {
            $account_details = array();
            for ($i = 0; $i < count($gateway->account_details); $i++) {
                if ($gateway->account_details[$i]['account_name'] != "") {
                    array_push($account_details, $gateway->account_details[$i]);
                }
            }
            $advanced_cod_array = array();
            //seetings for advanced cod
            if (isset($gateway->settings['disable_cod_adv'])) {
                if ($gateway->settings['disable_cod_adv'] == 'no') {
                    $advanced_cod_array = array(
                        "extra_charges" => $gateway->settings['extra_charges'],
                        "extra_charges_msg" => $gateway->settings['extra_charges_msg'],
                        "extra_charges_type" => $gateway->settings['extra_charges_type'],
                        "cod_pincodes" => $gateway->settings['cod_pincodes'],
                        "in_ex_pincode" => $gateway->settings['in_ex_pincode']
                    );
                }
            }
            $icon_url = "";
            if (isset($gateway->settings['icon'])) {
                $icon_url = $gateway->settings['icon'];
            }
            $gateway_list['gateways'][] = array(
                "id" => $gateway->id,
                "title" => $gateway->get_title(),
                "description" => $gateway->get_description(),
                "icon" => $icon_url,
                "chosen" => $gateway->chosen,
                "order_button_text" => $gateway->order_button_text,
                "enabled" => $gateway->enabled,
                // "testmode" =>$gateway->testmode,
                "instructions" => $gateway->settings['instructions'],
                "account_details" => $account_details,
                "settings" => $advanced_cod_array
                // "availability" =>$gateway->availability,
                //"supports" =>$gateway->supports,
            );
        }
        return $gateway_list;
    }
    function tms_get_countries_list($postData = array())
    {
        global $woocommerce;
        $list_countries     = WC()->countries->get_allowed_countries();
        $specific_states    = WC()->countries->get_allowed_country_states();
        $list_array['list'] = array();
        $i                  = -1;
        foreach ($list_countries as $key => $country) {
            $list_array['list'][++$i] = array(
                "id" => $key,
                "n" => html_entity_decode($country),
                "s" => array()
            );
            if (isset($specific_states[$key]) && is_array($specific_states[$key])) {
                foreach ($specific_states[$key] as $key => $state) {
                    $list_array['list'][$i]["s"][] = array(
                        "id" => $key,
                        "n" => html_entity_decode($state)
                    );
                }
            }
        }
        return $list_array;
    }
    function tms_get_states_list($postData = array())
    {
        if (isset($postData) && !empty($postData['country_code'])) {
            global $woocommerce;
            $countries_obj = new WC_Countries();
            $countries     = $countries_obj->get_states($postData['country_code']);
            return $countries;
        }
        return null;
    }
    function tms_load_pole_products($postData = array())
    {
        if (isset($postData) && !empty($postData['pole_param'])) {
            $pole_parm_string = $postData['pole_param'];
            $pole_parma_array = explode(';', $pole_parm_string);
            $productlist      = array();
            for ($i = 0; $i < count($pole_parma_array); $i++) {
                if ($pole_parma_array[$i] != "") {
                    $product      = wc_get_product($pole_parma_array[$i]);
                    $product_info = $this->tms_get_product_short_info($product, 2);
                    array_push($productlist, $product_info);
                }
            }
            return $productlist;
        } else if (isset($postData) && !empty($postData['cart_param'])) {
            /* $recived_str=' [
            {
            "pid": 429,
            "vid": 508
            },
            {
            "pid": 466,
            "vid": -1
            }
            
            ]';*/
            $recived_str = stripslashes($postData['cart_param']);
            $json        = json_decode($recived_str, true);
            $productlist = array();
            for ($i = 0; $i < count($json); $i++) { {
                    $product      = wc_get_product($json[$i]['pid']);
                    $product_info = $this->tms_get_product_short_info($product, 2);
                    if ($json[$i]['vid'] != -1) {
                        $product_variation = new WC_Product_Variation($json[$i]['vid']);
                        //var_dump($product_variation);
                        if ($product_variation) {
                            $product_info['manage_stock']  = $product_variation->manage_stock;
                            $product_info['stock']         = (int) $product_variation->get_stock_quantity(); //, $product_variation->stock;
                            $product_info['stock_status']  = $product_variation->stock_status;
                            $product_info['total_stock']   = $product_variation->get_total_stock();
                            $product_info['price']         = $product_variation->price;
                            $product_info['regular_price'] = $product_variation->regular_price;
                            $product_info['sale_price']    = $product_variation->sale_price;
                            $product_info['backorders']    = $product_variation->backorders;
                            $img_url_arr                   = wp_get_attachment_image_src(get_post_thumbnail_id($json[$i]['vid']), 'large');
                            $img_url                       = $img_url_arr[0];
                            if ($img_url == "" || $img_url == false || $img_url == 0) {
                            } else {
                                $product_info['img'] = $img_url;
                            }
                            //$product_info['img']=$img_url;
                        }
                    } else {
                        $product_stockdata = new WC_Product($json[$i]['pid']);
                        if ($product_stockdata) {
                            $product_info['manage_stock']  = $product_stockdata->manage_stock;
                            $product_info['stock']         = (int) $product_stockdata->get_stock_quantity(); // $product_stockdata->stock;
                            $product_info['stock_status']  = $product_stockdata->stock_status;
                            $product_info['total_stock']   = $product_stockdata->get_total_stock();
                            $product_info['price']         = $product_stockdata->price;
                            $product_info['regular_price'] = $product_stockdata->regular_price;
                            $product_info['sale_price']    = $product_stockdata->sale_price;
                            $product_info['backorders']    = $product_stockdata->backorders;
                        }
                    }
                    $product_info['vid']   = $json[$i]['vid'];
                    $product_info['pid']   = $json[$i]['pid'];
                    $product_info['index'] = $json[$i]['index'];
                    array_push($productlist, $product_info);
                }
            }
            return $productlist;
        }
        return "";
    }
    function tms_load_products($postData = array())
    {
        $product_limit = 10;
        if (isset($postData) && !empty($postData['product_limit'])) {
            $product_limit = $postData['product_limit'];
        }
        // $product_limit=2;
        $product_category_list = array();
        $args                  = array(
            'number' => $number,
            'orderby' => $orderby,
            'order' => $order,
            'hide_empty' => $hide_empty,
            'include' => $ids
        );
        $product_categories    = get_terms('product_cat', $args);
        //return $product_categories;
        //exit;
        foreach ($product_categories as $cat) {
            $category_info = $this->tms_get_category_short_info($cat, '0');
            $product_list  = array();
            $children      = get_term_children($cat->term_id, $cat->taxonomy);
            if (empty($children)) {
                $args = array(
                    'posts_per_page' => $product_limit,
                    'post_type' => 'product',
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'product_cat',
                            'field' => 'id',
                            'terms' => $cat->term_id
                        )
                    )
                );
                $r    = new WP_Query($args);
                if ($r->have_posts()) {
                    while ($r->have_posts()):
                        $r->the_post();
                        global $product;
                        $product_info = $this->tms_get_product_short_info($product, 0);
                        array_push($product_list, $product_info);
                    endwhile;
                }
            }
            $arr = array(
                "category" => $category_info,
                "products" => $product_list
            );
            array_push($product_category_list, $arr);
        }
        return $product_category_list;
    }
    // according to splash requirement.
    function tms_get_woocommerce_product_list($postData = array())
    {
        $product_limit = 10;
        if (isset($postData) && !empty($postData['product_limit'])) {
            $product_limit = $postData['product_limit'];
        }
        // $product_limit=2;
        $product_category_list = array();
        $args                  = array(
            'number' => $number,
            'orderby' => $orderby,
            'order' => $order,
            'hide_empty' => $hide_empty,
            'include' => $ids
        );
        $product_categories    = get_terms('product_cat');
        //return $product_categories;
        //exit;
        foreach ($product_categories as $cat) {
            $category_info = $this->tms_get_category_short_info($cat, '1');
            $product_list  = array();
            // if($cat->parent==0)
            {
                $args = array(
                    'posts_per_page' => $product_limit,
                    'post_type' => 'product',
                    'post_status' => 'publish',
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'product_cat',
                            'field' => 'id',
                            'terms' => $cat->term_id
                        )
                    )
                );
                $r    = new WP_Query($args);
                if ($r->have_posts()) {
                    while ($r->have_posts()):
                        $r->the_post();
                        global $product;
                        //echo 'product id'.the_ID();
                        //echo 'count '.count($category_info['img_url']);
                        if ($category_info['img_url'][0] == "" || $category_info['img_url'][0] == '') {
                            $img_url = wp_get_attachment_image_src(get_post_thumbnail_id($r->post->ID), 'large');
                            if (count($img_url) > 0) {
                                $category_info['img_url'] = $img_url[0];
                                break;
                            }
                        }
                    //$product_info=  $this->tms_get_product_short_info($product);
                        
                    //array_push($product_list, $product_info);
                    endwhile;
                }
            }
            //  $arr = array("category" => $category_info,"products" => $product_list);
            array_push($product_category_list, $category_info);
        }
        $meta_data        = $this->tms_get_metadata();
        $best_selling     = $this->tms_get_best_selling_products($product_limit); //trending 
        $new_arrivals     = $this->tms_get_recent_products($product_limit); //new_arrivals
        $new_sales        = $this->tms_get_sale_products($product_limit);
        $payment_gateWays = $this->tms_get_available_payment_gateways();
        $arr_meta_product = array(
            "category" => $product_category_list,
            "meta_data" => $meta_data,
            "best_selling" => $best_selling,
            "new_arrivals" => $new_arrivals,
            "new_sales" => $new_sales,
            "payment" => $payment_gateWays
        );
        return $arr_meta_product;
        /*$args = array('post_status' =>'publish','tax_query' =>array( 'taxonomy' => 'categories','field'    => 'id','term'=> '15'));
        $the_query = new wp_query($args);
        echo ''.json_encode($the_query);*/
    }
    function tms_get_category_short_info($catr, $format)
    {
        $cat                 = get_term($catr->term_id, 'product_cat');
        $category_info['id'] = $cat->term_id;
        if ($format != '0') {
            $thumbnail_id             = get_woocommerce_term_meta($cat->term_id, 'thumbnail_id', true);
            // get the image URL
            $image                    = wp_get_attachment_url($thumbnail_id);
            $category_info['parent']  = $cat->parent;
            $category_info['id']      = $cat->term_id;
            $category_info['name']    = $cat->name;
            $category_info['slug']    = $cat->slug;
            $category_info['img_url'] = $image;
            $category_info['count']   = $cat->count;
            $category_info['link']    = get_category_link($cat->term_id);
        }
        return $category_info;
    }
    function tms_get_product_short_info($product, $format)
    {
        //$product=96;
        if (!is_a($product, "WC_Product"))
        //$product =  get_product($product);
            $details['p_temp'] = 'zzz';
        $details['stock'] = $product->is_in_stock();
        $details['url']   = $product->get_permalink();
        if ($format != 2) //for pole products
            {
            $short_desc      = apply_filters('woocommerce_mobapp_short_description', $product->get_post_data()->post_excerpt);
            $details['desc'] = do_shortcode($short_desc);
        }
        $details['title'] = $product->post->post_title;
        //echo 'id is '.$product->post->ID;
        $details['id']    = $product->post->ID;
        $temp_url         = wp_get_attachment_image_src(get_post_thumbnail_id($product->post->ID), 'large');
        if (count($temp_url) > 0) {
            $details['img'] = $temp_url[0];
        }
        //$details['img_0'] = $details['featured_src'][0];
        $details['type']          = $product->product_type;
        $details['price']         = $product->get_price();
        $details['regular_price'] = $product->get_regular_price();
        $details['sale_price']    = $product->get_sale_price();
        if ($format != 0) //categories are sent for best selling etc products
            {
            $cat_data = wp_get_post_terms($product->post->ID, 'product_cat');
            $catarray = array();
            foreach ($cat_data as $cat) {
                array_push($catarray, $cat->term_id);
            }
            $details['category_ids'] = $catarray;
            /*if($product->product_type == 'variable')
            {
            $details['var']=$product->get_available_variations();
            }*/
        }
        if ($product->product_type == 'variable') {
            $details['min_var_price'] = $product->get_variation_price('min', true);
            $details['max_var_price'] = $product->get_variation_price('max', true);
        }
        $details['created_at']     = $product->get_post_data()->post_date_gmt;
        $details['average_rating'] = WC_format_decimal($product->get_average_rating(), 2);
        $details['total_sales']    = metadata_exists('post', $product->id, 'total_sales') ? (int) get_post_meta($product->id, 'total_sales', true) : 0;
        $details['featured']       = $product->is_featured();
        $details['taxable']        = $product->is_taxable();
        $details['tax_status']     = $product->get_tax_status();
        $details['tax_class']      = $product->get_tax_class();
		$details['weight']   =   $product->get_weight() ? WC_format_decimal($product->get_weight(), 2) : null;
        return $details;
    }
    public function tms_get_recent_products($product_limit)
    {
        $atts = array(
            'per_page' => '12',
            'columns' => '4',
            'orderby' => 'date',
            'order' => 'desc'
        );
        extract($atts);
        $meta_query = WC()->query->get_meta_query();
        $args       = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'ignore_sticky_posts' => 1,
            'posts_per_page' => $product_limit,
            'orderby' => $orderby,
            'order' => $order,
            'meta_query' => $meta_query
        );
        $products   = $this->tms_get_ids($args, $atts);
        return $products;
    }
    public function tms_get_featured_products()
    {
        $atts = array(
            'per_page' => '12',
            'columns' => '4',
            'orderby' => 'date',
            'order' => 'desc'
        );
        extract($atts);
        $args     = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'ignore_sticky_posts' => 1,
            'posts_per_page' => $per_page,
            'orderby' => $orderby,
            'order' => $order,
            'meta_query' => array(
                array(
                    'key' => '_visibility',
                    'value' => array(
                        'catalog',
                        'visible'
                    ),
                    'compare' => 'IN'
                ),
                array(
                    'key' => '_featured',
                    'value' => 'yes'
                )
            )
        );
        $products = $this->tms_get_ids($args, $atts);
        return $products;
    }
    public function tms_get_sale_products($product_limit)
    {
        $atts = array(
            'per_page' => '12',
            'columns' => '4',
            'orderby' => 'title',
            'order' => 'asc'
        );
        extract($atts);
        // Get products on sale
        $product_ids_on_sale = wc_get_product_ids_on_sale();
        $meta_query          = array();
        $meta_query[]        = WC()->query->visibility_meta_query();
        $meta_query[]        = WC()->query->stock_status_meta_query();
        $meta_query          = array_filter($meta_query);
        $args                = array(
            'posts_per_page' => $product_limit,
            'orderby' => $orderby,
            'order' => $order,
            'no_found_rows' => 1,
            'post_status' => 'publish',
            'post_type' => 'product',
            'meta_query' => $meta_query,
            'post__in' => array_merge(array(
                0
            ), $product_ids_on_sale)
        );
        $products            = $this->tms_get_ids($args, $atts);
        return $products;
    }
    public function tms_get_best_selling_products($product_limit)
    {
        $atts = array(
            'per_page' => '12',
            'columns' => '4'
        );
        extract($atts);
        $args     = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'ignore_sticky_posts' => 1,
            'posts_per_page' => $product_limit,
            'meta_key' => 'total_sales',
            'orderby' => 'meta_value_num',
            'meta_query' => array(
                array(
                    'key' => '_visibility',
                    'value' => array(
                        'catalog',
                        'visible'
                    ),
                    'compare' => 'IN'
                )
            )
        );
        $products = $this->tms_get_ids($args, $atts);
        return $products;
    }
    public function tms_top_rated_products()
    {
        $atts = array(
            'per_page' => '12',
            'columns' => '4',
            'orderby' => 'title',
            'order' => 'asc'
        );
        extract($atts);
        $args     = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'ignore_sticky_posts' => 1,
            'orderby' => $orderby,
            'order' => $order,
            'posts_per_page' => $per_page,
            'meta_query' => array(
                array(
                    'key' => '_visibility',
                    'value' => array(
                        'catalog',
                        'visible'
                    ),
                    'compare' => 'IN'
                )
            )
        );
        $products = $this->tms_get_ids($args, $atts);
        return $products;
    }
    private function tms_get_ids($args, $atts)
    {
        $r            = new WP_Query(apply_filters('woocommerce_shortcode_products_query', $args, $atts,''));

        $product_info = array();
        if ($r->have_posts()) {
            while ($r->have_posts()):
                $r->the_post();
                global $product;
            //echo 'product id'.the_ID();
                $product_info[] = $this->tms_get_product_short_info($product, 1);
            //array_push($product_list, $product_info);
            endwhile;
        }
        return $product_info;
    }
    public function tms_get_shipping_methods()
    {
        global $woocommerce;
        WC()->customer->calculated_shipping(true);
        $this->shipping_calculated = true;
        do_action('woocommerce_calculated_shipping');
        $woocommerce->cart->calculate_shipping();
        $packages = WC()->shipping()->get_packages();
        //return  $packages;
        $return   = array();
        if ($woocommerce->cart->needs_shipping()) {
            $return['show_shipping'] = 1;
            $woocommerce->cart->calculate_shipping();
            $packages = WC()->shipping()->get_packages();
            ///return  $packages;
            foreach ($packages as $i => $package) {
                $chosen_method        = isset(WC()->session->chosen_shipping_methods[$i]) ? WC()->session->chosen_shipping_methods[$i] : '';
                $return['shipping'][] = array(
                    'methods' => $this->tms_getMethodsInArray($package['rates']),
                    'chosen' => $chosen_method,
                    'index' => $i
                );
            }
        } else {
            $return['show_shipping'] = 0;
            $return['shipping']      = array();
        }
        if (empty($return['shipping']) || is_null($return['shipping']) || !is_array($return['shipping'])) {
            $return['show_shipping'] = 0;
            $return['shipping']      = array();
        }
        //$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
        return $return;
    }
    private function tms_getMethodsInArray($methods)
    {
        $return = array();
        foreach ($methods as $method) {
            $return[] = array(
                'id' => $method->id,
                'label' => $method->label,
                'cost' => $method->cost,
                'taxes' => $method->taxes,
                'method_id' => $method->method_id
                //    'title'=>$method->method_title,
            );
        }
        return $return;
    }
    function cart()
    {
        global $woocommerce;
        return $woocommerce->cart;
    }
    public function tms_get_cart_meta($data)
    {
        $this->cart()->calculate_shipping();
        global $woocommerce;
        $return = array(
            "count" => $this->cart()->get_cart_contents_count(),
            "shipping_fee" => !empty($this->cart()->shipping_total) ? $this->cart()->shipping_total : 0,
            "tax" => $this->cart()->get_cart_tax(),
            "total_tax" => WC()->cart->tax_total,
            "shipping_tax" => WC()->cart->shipping_tax_total,
            "fees" => $this->cart()->get_fees(),
            "currency" => get_woocommerce_currency(),
            "currency_symbol" => get_woocommerce_currency_symbol(),
            "total" => $this->cart()->get_cart_subtotal(true),
            "cart_total" => $this->cart()->cart_contents_total,
            "order_total" => $woocommerce->cart->get_cart_total(),
            "price_format" => get_woocommerce_price_format(),
            'timezone' => wc_timezone_string(),
            'tax_included' => ('yes' === get_option('woocommerce_prices_include_tax')),
            'weight_unit' => get_option('woocommerce_weight_unit'),
            'dimension_unit' => get_option('woocommerce_dimension_unit'),
            "can_proceed" => true,
            "error_message" => ""
        );
        return $return;
    }
    function tms_api_plugin_activate_status_action()
    {
        $response = array(
            'status' => 'success',
            'error' => '',
            'message' => 'Plugin is active.'
        );
        return $response;
    }
    public function tms_get_cart_api()
    {
        global $woocommerce;
        $cart   = array_filter((array) $woocommerce->cart->cart_contents);
        $return = array();
        foreach ($cart as $key => $item) {
            $item["key"] = $key;
            $variation   = array();
            if (isset($item["variation"]) && is_array($item["variation"])) {
                foreach ($item["variation"] as $id => $variation_value) {
                    $variation[] = array(
                        "id" => str_replace('attribute_', '', $id),
                        "name" => wc_attribute_label(str_replace('attribute_', '', $id)),
                        "value_id" => $variation_value,
                        "value" => trim(esc_html(apply_filters('woocommerce_variation_option_name', $variation_value)))
                    );
                }
            }
            $item["variation"] = $variation;
            $item              = array_merge($item, $this->tms_get_product_short_info($item["data"], 0));
            unset($item["data"]);
            $return[] = $item;
        }
        return $return;
    }
    function tms_calculate_shipping($postData1 = array())
    {
        //'AF','CG','500029','Indore'
        /* $_POST = 
        array(
        'cal_shipping_postcode'=>'500029',
        'cal_shipping_country'=>'IN',
        'cal_shipping_state'=>'CG',
        'cal_shipping_city'=>'Indore', 
        'cal_chosen_method'=>'flat_rate'
        );
        */
        $ship_str    = stripslashes($postData1);
        $postData    = json_decode($ship_str, true);
        // echo 'country  '.$postData['cal_shipping_country'].' state '.$postData['cal_shipping_state'].' post code '.$postData['cal_shipping_postcode'].' city  '.$postData['cal_shipping_city'];
        /*$postData['cal_shipping_country']='AT';
        $postData['cal_shipping_state']='CG';
        $postData['cal_shipping_postcode']='500029';
        $postData['cal_shipping_city']='Indore';
        //$postData['cal_chosen_method']='flat_rate';
        
        /*$postData['cal_shipping_country']='AF';
        $postData['cal_shipping_state']='KA';
        $postData['cal_shipping_postcode']='12345';
        $postData['cal_shipping_city']='kabul';*/
        $reponseData = array();
        $data        = array();
        try {
            WC()->shipping->reset_shipping();
            if (isset($postData['cal_chosen_method']) && !empty($postData['cal_chosen_method'])) {
                WC()->session->set('chosen_shipping_methods', array(
                    $postData['cal_chosen_method']
                ));
            }
            $country  = $postData['cal_shipping_country'];
            $state    = $postData['cal_shipping_state'];
            $postcode = $postData['cal_shipping_postcode'];
            $city     = $postData['cal_shipping_city'];
            //echo 'region Data  '. $country.'  -- '.$state.'  -- '.$postcode.' --  ' .$city;
            if (!empty($postcode) && !WC_Validation::is_postcode($postcode, $country)) {
                $reponseData = array(
                    'status' => 'failed',
                    'error' => '',
                    'message' => 'Please enter a valid postcode/ZIP.'
                );
                return $reponseData;
            } elseif (!empty($postcode)) {
                $postcode = wc_format_postcode($postcode, $country);
            }
            if ($country) {
                WC()->customer->set_location($country, $state, $postcode, $city);
                WC()->customer->set_shipping_location($country, $state, $postcode, $city);
            } else {
                WC()->customer->set_to_base();
                WC()->customer->set_shipping_to_base();
            }
            WC()->customer->calculated_shipping(true);
            $this->shipping_calculated = true;
            do_action('woocommerce_calculated_shipping');
            WC()->session->set('wc_shipping_calculate_details', $postData);
            //$this->cart()->calculate_totals();
            //$reponseData=array('cart_data'=>$this->tms_get_cart_api(),'cart_meta_data'=>$this->tms_get_cart_meta($postData),'cart_shipping_methods'=>$this->tms_get_shipping_methods());
            $reponseData = $this->tms_get_shipping_methods();
        }
        catch (Exception $e) {
        }
        return $reponseData;
    }
    function tms_api_social_login_action($postData = array())
    {
        $response = array();
        if (isset($postData) && !empty($postData['user_emailID'])) {
            $username = base64_decode($postData['user_emailID']);
            $username = sanitize_user($username, true);
            if (is_email($username)) {
                if (username_exists($username) || email_exists($username)) {
                    $response = array(
                        'status' => 'success',
                        'error' => '',
                        'message' => 'Login Successful.'
                    );
                } else {
                    $random_password = wp_generate_password($length = 12, $include_standard_special_chars = false);
                    $user_email      = (is_email($username)) ? $username : '';
                    if (!empty($user_email)) {
                        $user_id = wp_create_user($username, $random_password, $user_email);
                    } else {
                        $user_id = wp_create_user($username, $random_password);
                    }
                    if ($user_id) {
                        wp_new_user_notification($user_id, null, 'both');
                        $response = array(
                            'status' => 'success',
                            'error' => '',
                            'message' => 'Registration Successful for user: ' . $username . '.'
                        );
                    } else {
                        $response = array(
                            'status' => 'failed',
                            'error' => 1,
                            'message' => 'Not able to register user: ' . $username . '.'
                        );
                    }
                }
            } else {
                $response = array(
                    'status' => 'failed',
                    'error' => 1,
                    'message' => 'Invalid email address.'
                );
            }
        }
        return $response;
    }
    function tms_api_register_action($postData = array())
    {
        $response = array();
        if (isset($postData) && !empty($postData['user_name']) && !empty($postData['user_emailID']) && !empty($postData['user_pass'])) {
            $username   = base64_decode($postData['user_name']);
            $password   = base64_decode($postData['user_pass']);
            $user_email = base64_decode($postData['user_emailID']);
            $username   = sanitize_user($username, true);
            $user_email = sanitize_user($user_email, true);
            if (is_email($user_email)) {
                if (username_exists($username)) {
                    $response = array(
                        'status' => 'failed',
                        'error' => 1,
                        'message' => 'Username already exists.'
                    );
                } else if (email_exists($user_email)) {
                    $response = array(
                        'status' => 'failed',
                        'error' => 1,
                        'message' => 'Email address already exists.'
                    );
                } else {
                    $user_email = (is_email($user_email)) ? $user_email : '';
                    if (!empty($user_email)) {
                        $user_id = wp_create_user($username, $password, $user_email);
                    } else {
                        $user_id = wp_create_user($username, $password);
                    }
                    if ($user_id) {
                        wp_new_user_notification($user_id, null, 'both');
                        $response = array(
                            'status' => 'success',
                            'error' => '',
                            'message' => 'Registration Successful for user: ' . $username . '.'
                        );
                    } else {
                        $response = array(
                            'status' => 'failed',
                            'error' => 1,
                            'message' => 'Not able to register user: ' . $username . '.'
                        );
                    }
                }
            } else {
                $response = array(
                    'status' => 'failed',
                    'error' => 1,
                    'message' => 'Invalid email address.'
                );
            }
        }
        return $response;
    }
    function tms_api_login_action($postData = array())
    {
        $response = array();
        if (isset($postData) && !empty($postData['user_emailID']) && !empty($postData['user_pass'])) {
            $emailID = base64_decode($postData['user_emailID']);
            $emailID = sanitize_user($emailID, true);
            if (is_email($emailID)) {
                if (email_exists($emailID)) {
                    $user                   = get_user_by('email', $emailID);
                    $user_id                = $user->ID;
                    $password               = base64_decode($postData['user_pass']);
                    $creds                  = array();
                    $creds['user_login']    = $user->data->user_login;
                    $creds['user_password'] = $password;
                    $user                   = wp_signon($creds, false);
                    if (is_wp_error($user)) {
                        $erStr         = 'ERROR: ';
                        $error_message = str_replace($erStr, "", strip_tags($user->get_error_message()));
                        $erStr1        = 'Lost your password?';
                        $error_message = str_replace($erStr1, "", $error_message);
                        $response      = array(
                            'status' => 'failed',
                            'error' => 1,
                            'message' => $error_message
                        );
                    } else {
                        //for testing.
                        //if($user) 
                        {
                            wp_set_current_user($user_id, $user->user_login);
                            wp_set_auth_cookie($user_id);
                            do_action('wp_login', $user->user_login,$user);
                        }
                        $response = array(
                            'status' => 'success',
                            'error' => '',
                            'message' => 'Login Successful.'
                        );
                    }
                } else {
                    $response = array(
                        'status' => 'failed',
                        'error' => 1,
                        'message' => 'Email address does not exists.'
                    );
                }
            } else {
                $response = array(
                    'status' => 'failed',
                    'error' => 1,
                    'message' => 'Invalid email address.'
                );
            }
        }
        return $response;
    }
    function tms_api_forget_password_action($postData = array())
    {
        global $woocommerce;
        $response = array();
        if (isset($postData) && !empty($postData['user_emailID'])) {
            $user_email = base64_decode($postData['user_emailID']);
            $user_email = sanitize_user($user_email, true);
            if (is_email($user_email)) {
                if (email_exists($user_email)) {
                    $user_login      = $user_email;
                    $htmlValueNoonce = wp_nonce_field('lost_password', '_wpnonce', '', false);
                    $dom             = new DOMDocument();
                    $dom->loadHTML($htmlValueNoonce);
                    $xp         = new DOMXpath($dom);
                    $nodes      = $xp->query('//input[@name="_wpnonce"]');
                    $node       = $nodes->item(0);
                    $wpnonceVal = $node->getAttribute('value');
                    $url        = esc_url(wc_get_endpoint_url('lost-password', '', wc_get_page_permalink('myaccount')));
                    $ch         = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_POST, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $server_output = curl_exec($ch);
                    curl_close($ch);
                    libxml_use_internal_errors(true);
                    $dom = new DOMDocument();
                    $dom->loadHTML($server_output);
                    $xpath = new DOMXPath($dom);
                    foreach ($xpath->query("//title") as $node) {
                        $pageNotFound = $node->textContent;
                    }
                    $pattern = '/Page not found/';
                    if (!preg_match($pattern, $pageNotFound)) {
                        $siteURL         = get_bloginfo('url');
                        $wp_http_referer = explode($siteURL, $url);
                        $wp_http_referer = (isset($wp_http_referer[1])) ? $wp_http_referer[1] : '';
                        if (!empty($wpnonceVal)) {
                            $data = array(
                                'user_login' => $user_login,
                                'wc_reset_password' => true,
                                '_wpnonce' => $wpnonceVal,
                                '_wp_http_referer' => $wp_http_referer
                            );
                            $data = http_build_query($data);
                            $ch   = curl_init();
                            curl_setopt($ch, CURLOPT_URL, $url);
                            curl_setopt($ch, CURLOPT_POST, 1);
                            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            $server_output = curl_exec($ch);
                            curl_close($ch);
                            $response = array(
                                'status' => 'success',
                                'error' => '',
                                'message' => 'Lost password email sent successfully.'
                            );
                        } else {
                            $response = array(
                                'status' => 'failed',
                                'error' => 1,
                                'message' => 'Unable to send lost password email.'
                            );
                        }
                    } else {
                        $response = array(
                            'status' => 'failed',
                            'error' => 404,
                            'message' => 'Lost password page not set for WooCommerce.'
                        );
                    }
                } else {
                    $response = array(
                        'status' => 'failed',
                        'error' => 1,
                        'message' => 'Email address does not exists.'
                    );
                }
            } else {
                $response = array(
                    'status' => 'failed',
                    'error' => 1,
                    'message' => 'Invalid email address.'
                );
            }
        }
        return $response;
    }
    function tms_send_social_login_api_details()
    {
        if (isset($_POST['option_page']) && $_POST['option_page'] == 'tms-settings-group') {
            if (!current_user_can('manage_options')) {
                return;
            }
            $data = array(
                'site_url' => get_bloginfo('url'),
                'Facebook_enabled' => $_POST['tms_settings_Facebook_enabled'],
                'Facebook_app_id' => sanitize_text_field($_POST['tms_settings_Facebook_app_id']),
                'Facebook_app_secret' => sanitize_text_field($_POST['tms_settings_Facebook_app_secret']),
                'Google_enabled' => $_POST['tms_settings_Google_enabled'],
                'Google_app_id' => sanitize_text_field($_POST['tms_settings_Google_app_id']),
                'Google_app_secret' => sanitize_text_field($_POST['tms_settings_Google_app_secret']),
                'Twitter_enabled' => $_POST['tms_settings_Twitter_enabled'],
                'Twitter_app_key' => sanitize_text_field($_POST['tms_settings_Twitter_app_key']),
                'Twitter_app_secret' => sanitize_text_field($_POST['tms_settings_Twitter_app_secret'])
            );
            $data = http_build_query($data);
            $url  = 'https://thetmstore.com/tmadmin/socialLoginDetails.php';
            $ch   = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $server_output = curl_exec($ch);
            curl_close($ch);
        }
    }
    /**
     * Return an instance of this class.
     *
     * @since     1.0.0
     *
     * @return    object    A single instance of this class.
     */
    public static function get_instance()
    {
        // If the single instance hasn't been set, set it now.
        if (null == self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }
    function test()
    {
    }
    function setAddress()
    {
        global $woocommerce;
        $woocommerce->customer->set_shipping_postcode(12345);
        $woocommerce->customer->set_postcode(12345);
        //get it
        $woocommerce->customer->get_shipping_postcode();
        $woocommerce->customer->get_postcode();
    }
    function tms_active_widget_attribute_data()
    {
        $result_array = array();
        global $wp_registered_widgets, $wp_registered_widget_controls;
        //return $wp_registered_widgets;
        $dummy           = new WC_Widget_Layered_Nav();
        $settings        = $dummy->get_settings();
        $attribute_array = array();
        foreach ($settings as $filtersetting) {
            $matchfound = false;
            for ($j = 0; $j < count($attribute_array); $j++) {
                if ($filtersetting['attribute'] == $attribute_array[$j]['attribute']) {
                    $matchfound = true;
                }
            }
            if (!$matchfound) {
                /*$taxanomyarray= wc_attribute_taxonomy_name($filtersetting['attribute']);
                $terms = get_terms( $taxanomyarray );
                $tax_data=array('filter_array'=>$filtersetting,'attributes_data'=>$terms);*/
                array_push($attribute_array, $filtersetting);
            }
        }
        $total_array = array();
        for ($i = 0; $i < count($attribute_array); $i++) {
            $taxanomyarray = wc_attribute_taxonomy_name($attribute_array[$i]['attribute']);
            $terms         = get_terms($taxanomyarray);
            $tax_data      = array(
                'filter_array' => $attribute_array[$i],
                'attributes_data' => $terms
            );
            array_push($total_array, $tax_data);
        }
        ///=================Attribute variations ================
        return $total_array;
    }
    function tms_get_category_price_range()
    {
        //==========================================
        $product_list       = array();
        $product_categories = get_terms('product_cat');
        $max_cat_array      = array();
        $min_price          = 99999;
        foreach ($product_categories as $cat) {
            $return    = $this->get_filtered_price($cat->term_id);
            $cat_array = array(
                "c_id" => $cat->term_id,
                "max_limit" => $return->max_price,
                "min_limit" => $return->min_price
            );
            array_push($max_cat_array, $cat_array);
        }
        //var_dump(WC()->query->layered_nav_product_ids);
        return $max_cat_array;
    }
    function tms_filter_data_price()
    {
        //==========================================
        /*    $product_categories = get_terms( 'product_cat', $args );
        $max_cat_array=array();
        
        foreach( $product_categories as $cat ) 
        {
        $category = array($cat->slug);
        $args = array(
        'posts_per_page' => -1,
        'post_type' => array('product','product_variation'),
        'orderby' => 'meta_value_num',
        'order' => 'DESC',
        'tax_query' => array(
        array(
        'taxonomy' => 'product_cat',
        'field' => 'slug',
        'terms' => $category,
        'operator' => 'IN'
        )
        ),
        'meta_query' => array(
        'relation' => 'OR',
        array(
        'key' => '_price',
        ),
        array(
        'key'=>'_max_variation_price',
        )
        )       
        );
        
        
        $loop = new WP_Query($args);
        $cat_array=array("c_id"=>$cat->term_id,"max_limit"=>get_post_meta($loop->posts[0]->ID, '_price', true));
        array_push($max_cat_array,$cat_array);
        }    
        //==========================================
        $min_cat_array=array();
        foreach( $product_categories as $cat ) 
        {
        $category = array($cat->slug);
        $args = array(
        'posts_per_page' => -1,
        'post_type' => array('product','product_variation'),
        'orderby' => 'meta_value_num',
        'order' => 'ASC',
        'tax_query' => array(
        array(
        'taxonomy' => 'product_cat',
        'field' => 'slug',
        'terms' => $category,
        'operator' => 'IN'
        )
        ),
        'meta_query' => array(
        'relation' => 'OR',
        array(
        'key' => '_price',
        ),
        array(
        'key'=>'_min_variation_price',
        )
        
        
        )       
        );
        $loop = new WP_Query($args);
        $cat_array=array("c_id"=>$cat->term_id,"min_limit"=>get_post_meta($loop->posts[0]->ID, '_price', true));
        array_push($min_cat_array,$cat_array);
        }
        //var_dump($min_cat_array);
        //var_dump($max_cat_array);
        
        $price_array=array();
        for($i=0;$i<count($max_cat_array);$i++)
        {
        for($j=0;$j<count($min_cat_array);$j++)
        {
        if($max_cat_array[$i]['c_id']==$min_cat_array[$j]['c_id'])
        {
        $temparray=array("c_id"=>$max_cat_array[$i]['c_id'],"max_limit"=>$max_cat_array[$i]['max_limit'],"min_limit"=>$min_cat_array[$i]['min_limit']);
        array_push($price_array,$temparray);
        
        }
        }    
        }
        */
        //============================================price
        //=================================add attributes here================
        $result_array = array();
        //global $woocommerce;
        //global $_chosen_attributes;
        global $wp_registered_widgets, $wp_registered_widget_controls;
        $dummy           = new WC_Widget_Layered_Nav();
        $settings        = $dummy->get_settings();
        $attribute_array = array();
        foreach ($settings as $filtersetting) {
            $matchfound = false;
            for ($j = 0; $j < count($attribute_array); $j++) {
                if ($filtersetting['attribute'] == $attribute_array[$j]['attribute']) {
                    $matchfound = true;
                }
            }
            if (!$matchfound) {
                array_push($attribute_array, $filtersetting);
            }
        }
        $total_array = array();
        for ($i = 0; $i < count($attribute_array); $i++) {
            $taxanomyarray = wc_attribute_taxonomy_name($attribute_array[$i]['attribute']);
            $terms         = get_terms($taxanomyarray);
            $tax_data      = array(
                'attribute_name' => $attribute_array[$i]
            );
            array_push($total_array, $tax_data);
        }
        ///=================Attribute variations ================
        // $return_array=array('cat_price_range'=>$price_array,'attribute_avail_filters'=>$total_array);
        $return_array = array(
            'cat_price_range' => $this->tms_get_category_price_range(),
            'attribute_avail_filters' => $total_array
        );
        return $return_array;
    }
    function get_filtered_price($cat_id)
    {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'product_cat',
                'terms' => $cat_id
            )
        );
        global $wpdb, $wp_the_query;
        //$args       = $wp_the_query->query_vars;
        $tax_query  = isset($args['tax_query']) ? $args['tax_query'] : array();
        $meta_query = isset($args['meta_query']) ? $args['meta_query'] : array();
        if (!empty($args['taxonomy']) && !empty($args['term'])) {
            $tax_query[] = array(
                'taxonomy' => $args['taxonomy'],
                'terms' => array(
                    $args['term']
                ),
                'field' => 'slug'
            );
        }
        foreach ($meta_query as $key => $query) {
            if (!empty($query['price_filter']) || !empty($query['rating_filter'])) {
                unset($meta_query[$key]);
            }
        }
        $meta_query     = new WP_Meta_Query($meta_query);
        $tax_query      = new WP_Tax_Query($tax_query);
        $meta_query_sql = $meta_query->get_sql('post', $wpdb->posts, 'ID');
        $tax_query_sql  = $tax_query->get_sql($wpdb->posts, 'ID');
        $sql            = "SELECT min( CAST( price_meta.meta_value AS UNSIGNED ) ) as min_price, max( CAST( price_meta.meta_value AS UNSIGNED ) ) as max_price FROM {$wpdb->posts} ";
        $sql .= " LEFT JOIN {$wpdb->postmeta} as price_meta ON {$wpdb->posts}.ID = price_meta.post_id " . $tax_query_sql['join'] . $meta_query_sql['join'];
        $sql .= "     WHERE {$wpdb->posts}.post_type = 'product'
                    AND {$wpdb->posts}.post_status = 'publish'
                    AND price_meta.meta_key IN ('" . implode("','", array_map('esc_sql', apply_filters('woocommerce_price_filter_meta_keys', array(
            '_price'
        )))) . "')
                    AND price_meta.meta_value > '' ";
        $sql .= $tax_query_sql['where'] . $meta_query_sql['where'];
        return $wpdb->get_row($sql);
    }
    function tms_filter_data_attribute()
    {
        $cat_attribute_array = array();
        $product_categories  = get_terms('product_cat', null);
        foreach ($product_categories as $cat) {
            $atttibute_data       = $this->tms_active_widget_attribute_data();
            $cat_attribute_vector = array();
            foreach ($atttibute_data as $attibute_var) {
                $attribute_tax = $attibute_var['attributes_data'];
                //var_dump($attribute_tax);
                $temp_array    = array();
                foreach ($attribute_tax as $attibute_tax_var) {
                    $attribute_taxanomy = $attibute_tax_var->taxonomy;
                    $attribute_slug     = $attibute_tax_var->slug;
                    $args               = array(
                        'posts_per_page' => -1,
                        'post_type' => 'product',
                        'post_status' => 'publish',
                        'tax_query' => array(
                            array(
                                'taxonomy' => $attribute_taxanomy,
                                'field' => 'slug',
                                'terms' => $attribute_slug // name of publisher
                            ),
                            array(
                                'taxonomy' => 'product_cat',
                                'field' => 'id',
                                'terms' => $cat->term_id
                            )
                        )
                    );
                    $products           = get_posts($args);
                    //return $products;
                    $match_found        = false;
                    foreach ($products as $product) {
                        $match_found = true;
                        break;
                    }
                    if ($match_found) {
                        //foreach($attribute_tax as $tempattibute_tax_var)
                        {
                            $attribute_array['term_id']  = $attibute_tax_var->term_id;
                            $attribute_array['name']     = $attibute_tax_var->name;
                            $attribute_array['slug']     = $attibute_tax_var->slug;
                            $attribute_array['taxonomy'] = $attibute_tax_var->taxonomy;
                            array_push($temp_array, $attribute_array);
                        }
                        //  break;
                    }
                }
                if (count($temp_array) > 0) {
                    array_push($cat_attribute_vector, array(
                        "attribute_data" => $attibute_var['filter_array'],
                        "attribute_var_data" => $temp_array
                    ));
                }
            }
            array_push($cat_attribute_array, array(
                "c_id" => $cat->term_id,
                "attribute" => $cat_attribute_vector
            ));
        }
        return $cat_attribute_array;
    }
    public function tms_get_error()
    {
        $notices = WC()->session->get('wc_notices', array());
        if (!empty($notices['error'])) {
            $return = array();
            foreach ($notices['error'] as $key => $error) {
                $return = 'cart_add_error_' . $key . '' . html_entity_decode($error);
            }
            wc_clear_notices();
            return $return;
        } else {
            return false;
        }
    }
    public function tms_add_coupon_call($postData = array())
    {
        if (isset($postData) && !empty($postData['coupon_code'])) {
            $coupon_code = $postData['coupon_code'];
            $return      = tms_add_coupon($coupon_code);
            return $return;
        }
        return null;
    }
    public function tms_add_coupon($coupon_code)
    {
        // echo 'coupon coe'.$coupon_code;
        global $woocommerce;
        $added = $woocommerce->cart->add_discount($coupon_code);
        if (!$added) {
            $return = $this->tms_get_error();
            $return = array(
                'status' => 'failed',
                'error' => 1,
                'message' => $this->tms_get_error()
            );
        } else {
            $woocommerce->cart->persistent_cart_update();
            $return = $this->tms_get_cart_meta();
            $return = array(
                'status' => 'success',
                'error' => '',
                'message' => 'Coupon Applied Successfully'
            );
        }
        //var_dump($return);
        return $return;
    }
    function tms_filter_products($postData = array())
    {
        //echo '====recieved_array===';
        //var_dump($postData);
        //if(true)
        if (isset($postData) && !empty($postData['filter_data'])) {
            /*$recived_str='
            {
            "attributes": [
            {
            "attribute": "nap",
            "options": [
            {
            "name": "large",
            "slug": "large",
            "taxo": "pa_nap"
            },
            {
            "name": "small",
            "slug": "small",
            "taxo": "pa_nap"
            }
            ]
            }
            
            ],
            "cat_slug": "9",
            "chkStock": true,
            "maxPrice": 2103.5,
            "minPrice": 1
            }';*/
            $recived_str            = stripslashes($postData['filter_data']);
            $json                   = json_decode($recived_str, true);
            //echo '====parsed_array===';
            //    var_dump($json);
            $return_attribute_array = array();
            $category_slug          = $json['cat_slug'];
            $min_range              = $json['minPrice'];
            $max_range              = $json['maxPrice'];
            $return_max_range       = $min_range;
            $return_min_range       = $min_range;
            $products_required      = $postData['products_required'];
            $tax_array              = array();
            //push cat data
            $category               = array(
                $category_slug
            );
            $cat_taxonomy           = array(
                'taxonomy' => 'product_cat',
                'field' => 'slug',
                'terms' => $category,
                'operator' => 'IN'
            );
            array_push($tax_array, $cat_taxonomy);
            //push attribute_data
            $attribute_array      = $json['attributes'];
            $temp_attribute_array = array();
            ///======================
            for ($i = 0; $i < count($attribute_array); $i++) {
                $taxonomy_name         = '';
                $recived_options_array = $attribute_array[$i]['options'];
                $temp_options_array    = array();
                for ($j = 0; $j < count($recived_options_array); $j++) {
                    $taxonomy_name = $recived_options_array[$j]['taxo'];
                    array_push($temp_options_array, $recived_options_array[$j]['slug']);
                }
                $temp_array = array(
                    "taxo" => $taxonomy_name,
                    "options" => $temp_options_array
                );
                array_push($temp_attribute_array, $temp_array);
            }
            //var_dump($temp_attribute_array);
            //===================
            for ($i = 0; $i < count($temp_attribute_array); $i++) {
                $attribute_array_temp = array(
                    'taxonomy' => $temp_attribute_array[$i]['taxo'],
                    'field' => 'slug',
                    'terms' => $temp_attribute_array[$i]['options'] //explode(",",$str) // name of publisher
                );
                array_push($tax_array, $attribute_array_temp);
            }
            //var_dump($tax_array);
            //
            $category_slug = $json['cat_slug'];
            $min_range     = $json['minPrice'];
            $max_range     = $json['maxPrice'];
            $onsale        = false;
            if (isset($json['onsale'])) {
                $onsale = $json['onsale'];
            }
            $isinstock = false;
            if (isset($json['chkStock'])) {
                $isinstock = $json['chkStock'];
            }
            global $wpdb;
            $str       = 'black,blue';
            $category  = array(
                $category_slug
            );
            $sort_type = 7;
            if (isset($json['sort_type']) && !empty($json['sort_type'])) {
                $sort_type = $json['sort_type'];
            }
            $args            = null;
            $push_meta_array = array();
            /*array_push($push_meta_array,array(
            'key' => '_price',
            'value' => array($min_range, $max_range),
            'compare' => 'BETWEEN',
            'type' => 'NUMERIC'
            ));*/
            switch ($sort_type) {
                case 0: //recent
                    {
                    $meta_query = WC()->query->get_meta_query();
                    $args       = array(
                        'post_status' => 'publish',
                        'post_type' => array(
                            'product',
                            'product_variation'
                        ),
                        'posts_per_page' => -1,
                        'orderby' => 'date',
                        'order' => 'desc',
                        'tax_query' => $tax_array
                    );
                    array_push($push_meta_array, $meta_query);
                }
                    break;
                case 1: //featured
                    {
                    $args = array(
                        'post_status' => 'publish',
                        'post_type' => array(
                            'product',
                            'product_variation'
                        ),
                        'posts_per_page' => -1,
                        'orderby' => 'date',
                        'order' => 'desc',
                        'tax_query' => $tax_array
                    );
                    array_push($push_meta_array, array(
                        'key' => '_featured',
                        'value' => 'yes'
                    ));
                }
                    break;
                case 2: //discount
                    {
                    $meta_query          = array();
                    $meta_query[]        = WC()->query->visibility_meta_query();
                    $meta_query[]        = WC()->query->stock_status_meta_query();
                    $meta_query          = array_filter($meta_query);
                    $product_ids_on_sale = wc_get_product_ids_on_sale();
                    $args                = array(
                        'post_status' => 'publish',
                        'post_type' => array(
                            'product',
                            'product_variation'
                        ),
                        'posts_per_page' => -1,
                        'ignore_sticky_posts' => 1,
                        'orderby' => 'title',
                        'order' => 'asc',
                        'post__in' => array_merge(array(
                            0
                        ), $product_ids_on_sale),
                        'tax_query' => $tax_array
                    );
                    array_push($push_meta_array, $meta_query);
                }
                    break;
                case 3: //top rated
                    {
                    $args = array(
                        'post_status' => 'publish',
                        'post_type' => array(
                            'product',
                            'product_variation'
                        ),
                        'posts_per_page' => -1,
                        'meta_key' => 'total_sales',
                        'orderby' => '_wc_average_rating',
                        'tax_query' => $tax_array
                    );
                }
                    break;
                case 4: //price high to low
                    {
                    $args = array(
                        'post_status' => 'publish',
                        'post_type' => array(
                            'product',
                            'product_variation'
                        ),
                        'posts_per_page' => -1,
                        'meta_key' => '_price',
                        'orderby' => 'meta_value_num',
                        'order' => 'desc',
                        'tax_query' => $tax_array
                    );
                }
                    break;
                case 5: //low to high
                    {
                    $args = array(
                        'post_status' => 'publish',
                        'post_type' => array(
                            'product'
                        ),
                        'posts_per_page' => -1,
                        'meta_key' => '_price',
                        'orderby' => 'meta_value_num',
                        'order' => 'asc',
                        'tax_query' => $tax_array
                    );
                }
                    break;
                case 6: //best selling
                    {
                    $args = array(
                        'post_status' => 'publish',
                        'post_type' => array(
                            'product',
                            'product_variation'
                        ),
                        'posts_per_page' => -1,
                        'meta_key' => 'total_sales',
                        'orderby' => 'meta_value_num',
                        'tax_query' => $tax_array
                    );
                }
                    break;
                case 7: //normal
                    {
                    $args = array(
                        'post_status' => 'publish',
                        'post_type' => array(
                            'product',
                            'product_variation'
                        ),
                        'posts_per_page' => -1,
                        /*'tax_query' => array(
                        array(
                        'taxonomy' => 'product_cat',
                        'field' => 'slug',
                        'terms' => $category,
                        'operator' => 'IN'
                        ),array(
                        'taxonomy' => 'pa_nap',
                        'field' => 'slug',
                        'terms' => 'large' // name of publisher
                        )
                        ),*/
                        'tax_query' => $tax_array
                    );
                }
                    break;
            }
            if ($onsale) {
                $meta_query                  = array();
                $meta_query[]                = WC()->query->visibility_meta_query();
                $meta_query[]                = WC()->query->stock_status_meta_query();
                $meta_query                  = array_filter($meta_query);
                $product_ids_on_sale         = wc_get_product_ids_on_sale();
                $args['ignore_sticky_posts'] = 1;
                $args['post__in']            = array_merge(array(
                    0
                ), $product_ids_on_sale);
                array_push($args, $meta_query);
            }
            if ($isinstock) {
                array_push($push_meta_array, array(
                    'key' => '_stock_status',
                    'value' => 'instock'
                ));
            }
            $args['meta_query'] = $push_meta_array;
            //    echo ''.json_encode($args);
            //array_push($args,"meta_query"=>$push_meta_array);
            $product_list       = array();
            $r                  = new WP_Query($args);
            if ($r->have_posts()) {
                while ($r->have_posts()):
                    $r->the_post();
                    global $product;
                    $product_info = $this->tms_get_product_short_info($product, 1);
                    $push_item    = false;
                    if (($product->get_price() >= $min_range && $product->get_price() <= $max_range)) {
                        $push_item = true;
                    } else if ($product->product_type == 'variable') {
                        if (($product->get_variation_price('min', true) >= $min_range && $product->get_variation_price('min', true) <= $max_range)) {
                            $push_item = true;
                        } else if (($product->get_variation_price('max', true) >= $min_range && $product->get_variation_price('max', true) <= $max_range)) {
                            $push_item = true;
                        }
                    }
                    if ($push_item) {
                        array_push($product_list, $product_info);
                        if ($products_required == 0) {
                            if ($product->get_price() >= $return_max_range) {
                                $return_max_range = $product->get_price();
                            }
                            if ($product->product_type == 'variable') {
                                if ($product->get_variation_price('max', true) >= $return_max_range) {
                                    $return_max_range = $product->get_variation_price('max', true);
                                }
                            }
                            $attributes = $product->get_attributes();
                            foreach ($attributes as $attribute) {
                                if ($attribute['is_taxonomy']) {
                                    $values                 = wc_get_product_terms($product->id, $attribute['name'], array(
                                        'fields' => 'names'
                                    ));
                                    $return_attribute_array = $this->addAttributeData($return_attribute_array, $attribute['name'], $values);
                                }
                            }
                        }
                    }
                //var_dump($attributes);
                endwhile;
            }
            //var_dump($return_attribute_array);
            if ($products_required == 0) {
                return array(
                    "attribute" => $return_attribute_array,
                    "max_limit" => $return_max_range,
                    "min_limit" => $return_min_range
                );
            } else {
                return array(
                    "product_list" => $product_list
                );
            }
        } else {
            return;
        }
    }
    function addAttributeData($baseArray, $taxo, $slugarray)
    {
        //if(count($baseArray)>0)
        {
            $main_taxo_found = false;
            for ($i = 0; $i < count($baseArray); $i++) {
                if ($baseArray[$i]['taxo'] == $taxo) {
                    $main_taxo_found = true;
                    $match_found     = false;
                    for ($m = 0; $m < count($slugarray); $m++) {
                        for ($j = 0; $i < count($baseArray[$i]['name']); $j++) {
                            if ($baseArray[$i]['name'][$j] == $slug) {
                                //echo '@'.$slug.'--------------'.$baseArray[$i]['slug'][$j];
                                $match_found = true;
                                break;
                            }
                        }
                        if (!$match_found) {
                            echo '' . count($baseArray[$i]['name']);
                            // $slug_array=;
                            echo '----' . $slug . '---' . $taxo;
                            array_push($baseArray[$i]['name'], $slugarray[$m]);
                            //$temp_array=array("taxo"=>$taxo,"slug"=>$baseArray[$i]['slug']);
                            //array_push($baseArray,$temp_array);
                            break;
                        }
                    }
                }
            }
            if (!$main_taxo_found) {
                //    echo '#########'.$slug;
                $slug_array = array();
                array_push($slug_array, $slugarray);
                $temp_array = array(
                    "taxo" => $taxo,
                    "names" => $slugarray
                );
                array_push($baseArray, $temp_array);
            }
        }
        return $baseArray;
    }
    function tms_get_main_menu_data()
    {
        $menu_array = array();
        $menus      = get_terms('nav_menu');
        foreach ($menus as $menu) {
            $menu_items = wp_get_nav_menu_items($menu->name);
            $menu_data  = array(
                "name" => $menu->name,
                "slug" => $menu->slug,
                "id" => $menu->term_id
            );
            array_push($menu_array, $temp_array);
        }
    }
    function tms_get_menu_data()
    {
        $menu_array = array();
        $menus      = get_terms('nav_menu');
        //var_dump($menus);
        foreach ($menus as $menu) {
            $menu_items        = wp_get_nav_menu_items($menu->name);
            $menu_data         = array(
                "name" => $menu->name,
                "slug" => $menu->slug,
                "id" => $menu->term_id
            );
            $menu_option_array = array();
            for ($i = 0; $i < count($menu_items); $i++) {
                $menu_option  = $menu_items[$i];
                $cat_id       = $this->getcategoryID($menu_option->url);
                $redirect_url = "";
                if ($cat_id == -1) {
                    $redirect_url = $menu_option->url;
                }
                $temp_option_array = array(
                    "id" => $menu_option->ID,
                    "parent" => $menu_option->menu_item_parent,
                    "menu_order" => $menu_option->menu_order,
                    "redirect_cid" => $cat_id,
                    "redirect_url" => $redirect_url,
                    "name" => $menu_option->title
                );
                array_push($menu_option_array, $temp_option_array);
            }
            $temp_array = array(
                "menu" => $menu_data,
                "options" => $menu_option_array
            );
            array_push($menu_array, $temp_array);
        }
        return $menu_array;
    }
    function getcategoryID($caturl)
    {
        $siteURL            = get_bloginfo('url');
        $product_categories = get_terms('product_cat');
        foreach ($product_categories as $cat) {
            //  $caturl=str_replace($siteURL,"",$caturl); 
            //  $caturl=str_replace("/","",$caturl);
            $cat_url = get_category_link($cat->term_id);
            //$cat_url= $cat->slug;
            if ($cat_url == $caturl) {
                return (int) $cat->term_id;
            }
        }
        return -1;
    }
    function tms_get_exship_data($postData = array())
    {
        $shipping_type = "";
        if (isset($postData) && !empty($postData['ship_type'])) {
            $shipping_type = base64_decode($postData['ship_type']);
        } else {
            return;
        }
        switch ($shipping_type) {
            case "aftership":
                if (isset($postData) && !empty($postData['order_id'])) {
                    $options = get_option('aftership_option_name');
                    $plugin  = $options['plugin'];
                    if ($plugin == 'aftership') {
                        $order_id          = base64_decode($postData['order_id']);
                        $tracking_provider = get_post_meta($order_id, '_aftership_tracking_provider', true);
                        $tracking_number   = get_post_meta($order_id, '_aftership_tracking_number', true);
                        $track_data        = array(
                            "provider" => $tracking_provider,
                            "tracking_id" => $tracking_number,
                            "order_id" => $order_id
                        );
                        return $track_data;
                    } else {
                        $data = array(
                            'status' => 'failed',
                            'error' => 1,
                            'message' => 'No data received.'
                        );
                    }
                } else {
                    return;
                }
                break;
        }
    }
    public function tms_get_taxes($fields = null, $filter = array(), $class = null, $page = 1)
    {
        if (!empty($class)) {
            $filter['tax_rate_class'] = $class;
        }
        $filter['page'] = $page;
        $query          = $this->tms_query_tax_rates($filter);
        $taxes          = array();
        foreach ($query['results'] as $tax) {
            $taxes[] = current($this->tms_get_tax($tax->tax_rate_id, $fields));
        }
        // Set pagination headers
        //$this->server->add_pagination_headers( $query['headers'] );
        return $taxes;
    }
    /**
     * Get the tax for the given ID
     *
     * @since 2.5.0
     *
     * @param int $id The tax ID
     * @param string $fields fields to include in response
     *
     * @return array|WP_Error
     */
    public function tms_get_tax($id, $fields = null)
    {
        global $wpdb;
        try {
            $id       = absint($id);
            // Get tax rate details
            $tax      = WC_Tax::_get_tax_rate($id);
            $tax_data = array(
                'id' => (int) $tax['tax_rate_id'],
                'country' => $tax['tax_rate_country'],
                'state' => $tax['tax_rate_state'],
                'postcode' => '',
                'city' => '',
                'rate' => $tax['tax_rate'],
                'name' => $tax['tax_rate_name'],
                'priority' => (int) $tax['tax_rate_priority'],
                'compound' => (bool) $tax['tax_rate_compound'],
                'shipping' => (bool) $tax['tax_rate_shipping'],
                'order' => (int) $tax['tax_rate_order'],
                'class' => $tax['tax_rate_class'] ? $tax['tax_rate_class'] : 'standard'
            );
            // Get locales from a tax rate
            $locales  = $wpdb->get_results($wpdb->prepare("
                SELECT location_code, location_type
                FROM {$wpdb->prefix}woocommerce_tax_rate_locations
                WHERE tax_rate_id = %d
            ", $id));
            if (!is_wp_error($tax) && !is_null($tax)) {
                foreach ($locales as $locale) {
                    $tax_data[$locale->location_type] = $locale->location_code;
                }
            }
            return array(
                'tax' => apply_filters('woocommerce_api_tax_response', $tax_data, $tax, $fields, $this)
            );
        }
        catch (WC_API_Exception $e) {
            return new WP_Error($e->getErrorCode(), $e->getMessage(), array(
                'status' => $e->getCode()
            ));
        }
    }
    protected function tms_query_tax_rates($args, $count_only = false)
    {
        global $wpdb;
        $results = '';
        // Set args
        $args    = array(); //$this->merge_query_args( $args, array() );
        $query   = "
            SELECT tax_rate_id
            FROM {$wpdb->prefix}woocommerce_tax_rates
            WHERE 1 = 1
        ";
        // Filter by tax class
        if (!empty($args['tax_rate_class'])) {
            $tax_rate_class = 'standard' !== $args['tax_rate_class'] ? sanitize_title($args['tax_rate_class']) : '';
            $query .= " AND tax_rate_class = '$tax_rate_class'";
        }
        if (!$count_only) {
            $results = $wpdb->get_results($query);
        }
        return array(
            'results' => $results
        );
    }
    private function temp_tms_getMethodsInArray($methods)
    {
        $return = array();
        foreach ($methods as $method) {
            $cost = $method->cost;
            if ($method->cost == null || $method->cost == '') {
                $cost = 0;
            }
            $tax_status = true;
            if ($method->tax_status == "none") {
                $tax_status = false;
            }
            $return[] = array(
                'id' => $method->id,
                'label' => $method->title,
                'cost' => $cost,
                'taxable' => $tax_status,
                'taxes' => $method->taxes,
                'method_id' => $method->id
                //    'title'=>$method->method_title,
            );
        }
        return $return;
    }
    function tms_reset_password_website($postData = array())
    {
        $response = array();
        if (isset($postData) && !empty($postData['user_emailID']) && !empty($postData['user_pass']) && !empty($postData['user_pass_new'])) {
            $emailID = base64_decode($postData['user_emailID']);
            $emailID = sanitize_user($emailID, true);
            if (is_email($emailID)) {
                if (email_exists($emailID)) {
                    $user                   = get_user_by('email', $emailID);
                    $user_id                = $user->ID;
                    $password               = base64_decode($postData['user_pass']);
                    $newpassword            = base64_decode($postData['user_pass_new']);
                    $creds                  = array();
                    $creds['user_login']    = $user->data->user_login;
                    $creds['user_password'] = $password;
                    $user                   = wp_signon($creds, false);
                    if (is_wp_error($user)) {
                        $erStr         = 'ERROR: ';
                        $error_message = str_replace($erStr, "", strip_tags($user->get_error_message()));
                        $erStr1        = 'Lost your password?';
                        $error_message = str_replace($erStr1, "", $error_message);
                        $response      = array(
                            'status' => 'failed',
                            'error' => 1,
                            'message' => $error_message
                        );
                    } else {
                        //for testing.
                        //if($user) 
                        {
                            wp_set_password($newpassword, $user_id);
                        }
                        $response = array(
                            'status' => 'success',
                            'error' => '',
                            'message' => 'Reset Password Successfully.'
                        );
                    }
                } else {
                    $response = array(
                        'status' => 'failed',
                        'error' => 1,
                        'message' => 'Email address does not exists.'
                    );
                }
            } else {
                $response = array(
                    'status' => 'failed',
                    'error' => 1,
                    'message' => 'Invalid email address.'
                );
            }
        } else {
            $response = array(
                'status' => 'failed',
                'error' => 1,
                'message' => 'InSufficient Data.'
            );
        }
        return $response;
    }
    function tms_get_product_fulldata($postData = array())
    {
        if (isset($postData['pids'])) {
            $product_array = json_decode(base64_decode($postData['pids']), true);
            $data_array    = array();
            for ($i = 0; $i < count($product_array); $i++) {
                $pid     = $product_array[$i];
                $product = wc_get_product($pid);
                if ($product != null) {
                    if ($product->is_type('variation')) {
                        $product = $product->parent;
                    }
                    $short_desc = $product->get_post_data()->post_excerpt;
                    $desc       = $product->get_post_data()->post_content;
                    $desc       = empty($desc) ? $short_desc : $desc;
                    $desc       = do_shortcode($desc);
                    $short_desc = do_shortcode($short_desc);
                    $return     = array(
                        'title' => $product->get_title(),
                        'id' => (int) $product->is_type('variation') ? $product->get_variation_id() : $product->id,
                        'created_at' => $product->get_post_data()->post_date_gmt,
                        'updated_at' => $product->get_post_data()->post_modified_gmt,
                        'type' => $product->product_type,
                        'status' => $product->get_post_data()->post_status,
                        'downloadable' => $product->is_downloadable(),
                        'virtual' => $product->is_virtual(),
                        'permalink' => $product->get_permalink(),
                        'sku' => $product->get_sku(),
                        'price' => $product->get_price(),
                        'regular_price' => (is_a($product, 'WC_Product_Variable')) ? (($product->get_variation_regular_price())) : (($product->get_regular_price())),
                        'sale_price' => !$product->is_on_sale() ? null : (is_a($product, 'WC_Product_Variable')) ? ($product->get_variation_sale_price() ? ($product->get_variation_sale_price()) : null) : ($product->get_sale_price() ? ($product->get_sale_price()) : null),
                        'price_html' => '', //$product->get_price_html(),
                        'taxable' => $product->is_taxable(),
                        'tax_status' => $product->get_tax_status(),
                        'tax_class' => $product->get_tax_class(),
                        'managing_stock' => $product->managing_stock(),
                        'stock_quantity' => (int) $product->get_stock_quantity(),
                        'in_stock' => $product->is_in_stock(),
                        'backorders_allowed' => $product->backorders_allowed(),
                        'backordered' => $product->is_on_backorder(),
                        'sold_individually' => $product->is_sold_individually(),
                        'purchaseable' => $product->is_purchasable(),
                        'featured' => $product->is_featured(),
                        'visible' => $product->is_visible(),
                        'catalog_visibility' => $product->visibility,
                        'on_sale' => $product->is_on_sale(),
                        'weight' => $product->get_weight() ? WC_format_decimal($product->get_weight(), 2) : null,
                        'dimensions' => array(
                            'length' => $product->length,
                            'width' => $product->width,
                            'height' => $product->height
                        ),
                        'shipping_required' => $product->needs_shipping(),
                        'shipping_taxable' => $product->is_shipping_taxable(),
                        'shipping_class' => $product->get_shipping_class(),
                        'shipping_class_id' => (0 !== $product->get_shipping_class_id()) ? $product->get_shipping_class_id() : null,
                        'description' => $desc,
                        'short_description' => $short_desc,
                        'reviews_allowed' => ('open' === $product->get_post_data()->comment_status),
                        'average_rating' => WC_format_decimal($product->get_average_rating(), 2),
                        'rating_count' => (int) $product->get_rating_count(),
                        'upsell_ids' => array_map('absint', $product->get_upsells()),
                        'related_ids' => array_map('absint', array_values($product->get_related())),
                        'cross_sell_ids' => array_map('absint', $product->get_cross_sells()),
                        'categories' => wp_get_post_terms($product->id, 'product_cat', array(
                            'fields' => 'names'
                        )),
                        'tags' => wp_get_post_terms($product->id, 'product_tag', array(
                            'fields' => 'names'
                        )),
                        'images' => $this->get_images($product),
                        'featured_src' => wp_get_attachment_url(get_post_thumbnail_id($product->is_type('variation') ? $product->variation_id : $product->id)),
                        'attributes' => $this->get_attributes($product, true),
                        'attributes_array' => $this->get_attributes($product),
                        'downloads' => $this->get_downloads($product),
                        'download_limit' => (int) $product->download_limit,
                        'download_expiry' => (int) $product->download_expiry,
                        'download_type' => $product->download_type,
                        'purchase_note' => $product->purchase_note,
                        'total_sales' => metadata_exists('post', $product->id, 'total_sales') ? (int) get_post_meta($product->id, 'total_sales', true) : 0
                    );
                    if ($product->is_type('variable')) {
                        $return['variations'] = $this->get_variation_data($product);
                    }
                    // add the parent product data to an individual variation
                    if ($product->is_type('variation')) {
                        //$return['parent'] = $this->get_product_data( $product->parent );
                    }
                    array_push($data_array, array(
                        $pid => $return
                    ));
                }
            }
            return $data_array;
        } else {
            return array(
                'status' => 'failed',
                'error' => 1,
                'message' => 'InSufficient Data.'
            );
        }
    }
    public function get_related_prodcuts($product)
    {
        $ids     = array();
        $related = $product->get_related();
        if (sizeof($related) == 0)
            return $ids;
        $args     = array(
            'fields' => 'ids',
            'post_type' => 'product',
            'ignore_sticky_posts' => 1,
            'no_found_rows' => 1,
            'post__in' => $related,
            'post__not_in' => array(
                $product->id
            )
        );
        $products = new WP_Query($args);
        foreach ($products->posts as $id) {
            $id    = wc_get_product($id);
            $ids[] = $this->get_product_data($id, true);
        }
        return $ids;
    }
    private function get_variation_data($product)
    {
        $variations = array();
        foreach ($product->get_children() as $child_id) {
            $variation = $product->get_child($child_id);
            if (!$variation->exists())
                continue;
            $variation    = array(
                'id' => $variation->get_variation_id(),
                'created_at' => ($variation->get_post_data()->post_date_gmt),
                'updated_at' => ($variation->get_post_data()->post_modified_gmt),
                'downloadable' => $variation->is_downloadable(),
                'virtual' => $variation->is_virtual(),
                'permalink' => $variation->get_permalink(),
                'sku' => $variation->get_sku(),
                'price' => ($variation->get_price()),
                'regular_price' => $variation->get_regular_price(),
                'sale_price' => !$variation->is_on_sale() ? null : $variation->get_sale_price() ? $variation->get_sale_price() : null,
                'taxable' => $variation->is_taxable(),
                'tax_status' => $variation->get_tax_status(),
                'tax_class' => $variation->get_tax_class(),
                'stock_quantity' => (int) $variation->get_stock_quantity(),
                'in_stock' => $variation->is_in_stock(),
                'backordered' => $variation->is_on_backorder(),
                'purchaseable' => $variation->is_purchasable(),
                'visible' => $variation->variation_is_visible(),
                'on_sale' => $variation->is_on_sale(),
                'weight' => $variation->get_weight() ? WC_format_decimal($variation->get_weight(), 2) : null,
                'dimensions' => array(
                    'length' => $variation->length,
                    'width' => $variation->width,
                    'height' => $variation->height,
                    'unit' => get_option('woocommerce_mobapp_dimension_unit')
                ),
                'shipping_class' => $variation->get_shipping_class(),
                'shipping_class_id' => (0 !== $variation->get_shipping_class_id()) ? $variation->get_shipping_class_id() : null,
                'image' => $this->get_images($variation),
                'attributes' => $this->get_attributes($variation),
                'downloads' => $this->get_downloads($variation),
                'download_limit' => (int) $product->download_limit,
                'download_expiry' => (int) $product->download_expiry
            );
            $variations[] = $variation;
        }
        return $variations;
    }
    private function get_attributes($product, $old_type = false)
    {
        $attributes = array();
        if ($product->is_type('variation')) {
            // variation attributes
            foreach ($product->get_variation_attributes() as $attribute_name => $attribute) {
                // taxonomy-based attributes are prefixed with `pa_`, otherwise simply `attribute_`
                $attributes[] = array(
                    'id' => str_replace('attribute_', '', $attribute_name),
                    'name' => wc_attribute_label(str_replace('attribute_', '', $attribute_name)),
                    // ucwords( str_replace( 'attribute_', '', str_replace( 'pa_', '', $attribute_name ) ) ),
                    'option' => $attribute
                );
            }
        } else {
            if ($product->is_type('variable'))
                $variation_attrs = $product->get_variation_attributes();
            else
                $variation_attrs = array();
            foreach ($product->get_attributes() as $attribute) {
                // taxonomy-based attributes are comma-separated, others are pipe (|) separated
                if ($attribute['is_taxonomy']) {
                    $options = wc_get_product_terms($product->id, $attribute['name'], array(
                        'fields' => 'all'
                    ));
                } else
                    $options = explode('|', $product->get_attribute($attribute['name']));
                if ($old_type) {
                    foreach ($options as $key => $option) {
                        if (is_a($option, 'stdClass') || is_a($option, 'WP_Term'))
                            $options[$key] = trim(esc_html(apply_filters('woocommerce_variation_option_name', $option->name)));
                        else
                            $options[$key] = trim(esc_html(apply_filters('woocommerce_variation_option_name', $option)));
                    }
                } else {
                    if ($product->is_type('variable')) {
                        $variations = isset($variation_attrs[$attribute['name']]) ? $variation_attrs[$attribute['name']] : array();
                        $variations = array_map('sanitize_title', $variations);
                    } else
                        $variations = array();
                    foreach ($options as $key => $option) {
                        if (is_a($option, 'stdClass') || is_a($option, 'WP_Term')) {
                            $slug   = $option->slug;
                            $option = $option->name;
                        } else {
                            $slug = sanitize_title($option);
                        }
                        if (empty($variations))
                            $is_variation = false;
                        else
                            $is_variation = preg_grep("/" . $slug . "/i", $variations) ? true : false;
                        $options[$key] = array(
                            'id' => $slug,
                            'is_variation' => $is_variation,
                            'value' => html_entity_decode(trim(esc_html(apply_filters('woocommerce_variation_option_name', $option))), ENT_QUOTES, 'UTF-8')
                        );
                    }
                }
                $attributes[] = array(
                    'id' => sanitize_title($attribute['name']),
                    'name' => html_entity_decode(wc_attribute_label($attribute['name']), ENT_QUOTES, 'UTF-8'),
                    'position' => $attribute['position'],
                    'visible' => (bool) $attribute['is_visible'],
                    'variation' => (bool) $attribute['is_variation'],
                    'options' => $options
                );
            }
        }
        $attributes = $attributes;
        return $attributes;
    }
    private function get_images($product)
    {
        $images = $attachment_ids = array();
        if ($product->is_type('variation')) {
            if (has_post_thumbnail($product->get_variation_id())) {
                // add variation image if set
                $attachment_ids[] = get_post_thumbnail_id($product->get_variation_id());
            } elseif (has_post_thumbnail($product->id)) {
                // otherwise use the parent product featured image if set
                $attachment_ids[] = get_post_thumbnail_id($product->id);
            }
        } else {
            // add featured image
            if (has_post_thumbnail($product->id)) {
                $attachment_ids[] = get_post_thumbnail_id($product->id);
            }
            // add gallery images
            $attachment_ids = array_merge($attachment_ids, $product->get_gallery_attachment_ids());
        }
        // build image data
        foreach ($attachment_ids as $position => $attachment_id) {
            $attachment_post = get_post($attachment_id);
            if (is_null($attachment_post))
                continue;
            $attachment = wp_get_attachment_image_src($attachment_id, 'full');
            if (!is_array($attachment))
                continue;
            $images[] = current($attachment);
            //    $images[] = array(
            //        'id'         => (int) $attachment_id,
            //        'created_at' => $this->server->format_datetime( $attachment_post->post_date_gmt ),
            //        'updated_at' => $this->server->format_datetime( $attachment_post->post_modified_gmt ),
            //        'src'        => current( $attachment ),
            //        'title'      => get_the_title( $attachment_id ),
            //        'alt'        => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
            //        'position'   => $position,
            //    );
        }
        // set a placeholder image if the product has no images set
        if (empty($images)) {
            $images[] = WC_placeholder_img_src();
        }
        return apply_filters('appilder_woocommerce_product_images', $images, $product);
    }
    private function get_downloads($product)
    {
        $downloads = array();
        if ($product->is_downloadable()) {
            foreach ($product->get_files() as $file_id => $file) {
                $downloads[] = array(
                    'id' => $file_id, // do not cast as int as this is a hash
                    'name' => $file['name'],
                    'file' => $file['file']
                );
            }
        }
        return $downloads;
    }
    public function display_price($price_html)
    {
        preg_match_all("/<span class=\"amount\">(.*?)<\/span>/", $price_html, $matches);
        $price_html = end($matches[1]);
        $price_html = strip_tags($price_html);
        $price_html = trim($price_html);
        return $price_html;
    }
    public function price($price)
    {
        $price = wc_price($price);
        $price = strip_tags($price);
        return $price;
    }
    private function get_product_data($product)
    {
        return array(
            'title' => $product->get_title(),
            'id' => (int) $product->is_type('variation') ? $product->get_variation_id() : $product->id,
            'created_at' => $this->format_datetime($product->get_post_data()->post_date_gmt),
            'updated_at' => $this->format_datetime($product->get_post_data()->post_modified_gmt),
            'type' => $product->product_type,
            'status' => $product->get_post_data()->post_status,
            'downloadable' => $product->is_downloadable(),
            'virtual' => $product->is_virtual(),
            'permalink' => $product->get_permalink(),
            'sku' => $product->get_sku(),
            'price' => wc_format_decimal($product->get_price(), 2),
            'regular_price' => wc_format_decimal($product->get_regular_price(), 2),
            'sale_price' => $product->get_sale_price() ? wc_format_decimal($product->get_sale_price(), 2) : null,
            'price_html' => $product->get_price_html(),
            'taxable' => $product->is_taxable(),
            'tax_status' => $product->get_tax_status(),
            'tax_class' => $product->get_tax_class(),
            'managing_stock' => $product->managing_stock(),
            'stock_quantity' => (int) $product->get_stock_quantity(),
            'in_stock' => $product->is_in_stock(),
            'backorders_allowed' => $product->backorders_allowed(),
            'backordered' => $product->is_on_backorder(),
            'sold_individually' => $product->is_sold_individually(),
            'purchaseable' => $product->is_purchasable(),
            'featured' => $product->is_featured(),
            'visible' => $product->is_visible(),
            'catalog_visibility' => $product->visibility,
            'on_sale' => $product->is_on_sale(),
            'weight' => $product->get_weight() ? wc_format_decimal($product->get_weight(), 2) : null,
            'dimensions' => array(
                'length' => $product->length,
                'width' => $product->width,
                'height' => $product->height,
                'unit' => get_option('woocommerce_dimension_unit')
            ),
            'shipping_required' => $product->needs_shipping(),
            'shipping_taxable' => $product->is_shipping_taxable(),
            'shipping_class' => $product->get_shipping_class(),
            'shipping_class_id' => (0 !== $product->get_shipping_class_id()) ? $product->get_shipping_class_id() : null,
            'description' => apply_filters('the_content', $product->get_post_data()->post_content),
            'short_description' => apply_filters('woocommerce_short_description', $product->get_post_data()->post_excerpt),
            'reviews_allowed' => ('open' === $product->get_post_data()->comment_status),
            'average_rating' => wc_format_decimal($product->get_average_rating(), 2),
            'rating_count' => (int) $product->get_rating_count(),
            'related_ids' => array_map('absint', array_values($product->get_related())),
            'upsell_ids' => array_map('absint', $product->get_upsells()),
            'cross_sell_ids' => array_map('absint', $product->get_cross_sells()),
            'categories' => wp_get_post_terms($product->id, 'product_cat', array(
                'fields' => 'names'
            )),
            'tags' => wp_get_post_terms($product->id, 'product_tag', array(
                'fields' => 'names'
            )),
            'images' => $this->get_images($product),
            'featured_src' => wp_get_attachment_url(get_post_thumbnail_id($product->is_type('variation') ? $product->variation_id : $product->id)),
            'attributes' => $this->get_attributes($product),
            'downloads' => $this->get_downloads($product),
            'download_limit' => (int) $product->download_limit,
            'download_expiry' => (int) $product->download_expiry,
            'download_type' => $product->download_type,
            'purchase_note' => apply_filters('the_content', $product->purchase_note),
            'total_sales' => metadata_exists('post', $product->id, 'total_sales') ? (int) get_post_meta($product->id, 'total_sales', true) : 0,
            'variations' => array(),
            'parent' => array()
        );
    }
    function tms_get_upload_image($file = array())
    {
        if (!isset($file['image'])) {
            return array(
                'status' => 'failed',
                'error' => 1,
                'message' => 'InSufficient Data(image)'
            );
        }
        if (!isset($file['name'])) {
            return array(
                'status' => 'failed',
                'error' => 1,
                'message' => 'InSufficient Data(name)'
            );
        }
        $upload_dir      = wp_upload_dir();
        $upload_path     = str_replace('/', DIRECTORY_SEPARATOR, $upload_dir['path']) . DIRECTORY_SEPARATOR;
        $img             = $file['image'];
        $img             = str_replace('data:image/png;base64,', '', $img);
        $img             = str_replace(' ', '+', $img);
        $decoded         = base64_decode($img);
        $filename        = $file['name'];
        $hashed_filename = $filename;
        $image_upload    = file_put_contents($upload_path . $filename, $decoded);
        //HANDLE UPLOADED FILE
        if (!function_exists('wp_handle_sideload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        // Without that I'm getting a debug error!?
        if (!function_exists('wp_get_current_user')) {
            require_once(ABSPATH . 'wp-includes/pluggable.php');
        }
        // @new
        $file             = array();
        $file['error']    = '';
        $file['tmp_name'] = $upload_path . $hashed_filename;
        $file['name']     = $hashed_filename;
        $file['type']     = 'image/png';
        $file['size']     = filesize($upload_path . $hashed_filename);
        // upload file to server
        $file_return      = wp_handle_sideload($file, array(
            'test_form' => false
        ));
        $filename         = $file_return['file'];
        $attachment       = array(
            'post_mime_type' => $file_return['type'],
            'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
            'post_content' => '',
            'post_status' => 'inherit',
            'guid' => $wp_upload_dir['url'] . '/' . basename($filename)
        );
        $attach_id        = wp_insert_attachment($attachment, $filename, 289);
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $filename);
        wp_update_attachment_metadata($attach_id, $attach_data);
        /*$jsonReturn = array(
        'Status'  =>  'Success'
        );*/
        if ($attach_id) {
            $data = array(
                'status' => 'success',
                'error' => 0,
                'url' => $file_return['url'],
                'message' => 'Image uploaded successfully.'
            );
        } else {
            $data = array(
                'status' => 'failed',
                'error' => 1,
                'message' => 'No data received.'
            );
        }
        return $data;
        exit;
    }
    function tms_load_category_products($postData = array())
    {
        $product_limit = "";
        $category_id   = "";
        $offset        = "";
        if (isset($postData) && !empty($postData['product_limit'])) {
            $product_limit = base64_decode($postData['product_limit']);
        }
        if (isset($postData) && !empty($postData['category_id'])) {
            $category_id = base64_decode($postData['category_id']);
        }
        if (isset($postData) && !empty($postData['offset'])) {
            $offset = base64_decode($postData['offset']);
        }
        if ($product_limit == "" || $category_id == "" || $offset == "") {
            return array(
                'status' => 'failed',
                'error' => 1,
                'message' => 'InSufficient Data'
            );
        }
        $product_list = array();
        $args         = array(
            'posts_per_page' => $product_limit,
            'post_type' => 'product',
            'offset' => $offset,
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'id',
                    'terms' => $category_id
                )
            )
        );
        $r            = new WP_Query($args);
        if ($r->have_posts()) {
            while ($r->have_posts()):
                $r->the_post();
                global $product;
                $product_info = tms_get_product_short_info($product, 1);
                array_push($product_list, $product_info);
            endwhile;
        }
        return $product_list;
    }
    function tms_add_product_reviews($postData = array())
    {
        if (isset($postData) && !empty($postData['product_id']) && !empty($postData['rating']) && !empty($postData['user_name']) && !empty($postData['comment']) && !empty($postData['user_email'])) {
            $product_id = base64_decode($postData['product_id']);
            $rating     = intval(base64_decode($postData['rating']));
            $review     = base64_decode($postData['comment']);
            $user_name  = base64_decode($postData['user_name']);
            $user_email = base64_decode($postData['user_email']);
            $check_user = get_user_by('email', $user_email);
            if ($check_user) {
                $user_id   = $check_user->ID;
                $user_info = get_userdata($user_id);
                $author    = $user_info->user_login;
                $email     = $user_info->user_email;
            } else {
                $author = $user_name;
                $email  = $user_email;
            }
            $data = array(
                'comment_post_ID' => $product_id,
                'rating' => $rating,
                'comment' => $review,
                'author' => $user_name,
                'email' => $user_email
            );
            if (isset($rating) && 'product' === get_post_type($product_id)) {
                if (!$rating || $rating > 5 || $rating < 0) {
                    return array(
                        'status' => 'failed',
                        'error' => 1,
                        'message' => 'Rating cannot be greater 5 or less than zero'
                    );
                }
                $comment    = wp_handle_comment_submission($data);
                $comment_id = $comment->comment_ID;
                add_comment_meta($comment_id, 'rating', (int) esc_attr($rating), true);
                $post_id = isset($product_id) ? (int) $product_id : 0;
                if ($post_id) {
                    WC_Comments::clear_transients($post_id);
                }
                return array(
                    'status' => 'success',
                    'error' => 0,
                    'message' => 'Review Succesfully added'
                );
            } else
                return array(
                    'status' => 'failed',
                    'error' => 1,
                    'message' => 'Unable to add review'
                );
        } else {
            return array(
                'status' => 'failed',
                'error' => 1,
                'message' => 'InSufficient Data'
            );
        }
    }
	/*
	*
	===========
	  BLOG API
	===========
	*
	*/
	
	function tms_get_blog_info($postData=array())
	{
		$type='';
		if(!empty($postData) && $postData['type']!='')
			$type =base64_decode($postData['type']);
		
		if($type =='')
			return;
		
		switch ( $type ) 
		{
			case "menus":
			    $lang ='';
        		if(isset($postData['lang']) && $postData['lang']!='')
        			$lang =base64_decode($postData['lang']);
        	
			    $menuLocations = get_nav_menu_locations();
				$menuID = $menuLocations['primary'];
				return $primaryNav = wp_get_nav_menu_items($menuID);
			    /*$lang =$lang_code = '';
				if(!empty($postData) && $postData['lang']!='')
				{
					$lang =base64_decode($postData['lang']);
					$lang_code='_'.$lang;
				}
				return 'home'.$lang_code;
				return wp_get_nav_menu_items('home'.$lang_code);
				*/
				break;
			case "blogs":
				return $data = $this->tms_get_posts( $_POST );
				break;
			case "blog":
			    $blog_id =-1;
        		if(isset($postData['blog_id']) && $postData['blog_id']!='')
        			$blog_id =base64_decode($postData['blog_id']);
				
				$lang ='';
        		if(isset($postData['lang']) && $postData['lang']!='')
        			$lang =base64_decode($postData['lang']);
				
				$post =get_post( $blog_id );
				$post->featured_img =$featured_img_url = get_the_post_thumbnail_url($blog_id,'full');
				return $post;
				break;
			case "categories":
			    $lang ='';
        		if(isset($postData['lang']) && $postData['lang']!='')
        			$lang =base64_decode($postData['lang']);
				
				return $post_categories = get_terms('category');
				break;
			case "tags":
			    $lang ='';
        		if(isset($postData['lang']) && $postData['lang']!='')
        			$lang =base64_decode($postData['lang']);
				
				return $post_tags = get_terms('post_tag');
				break;
		}
		
		//return wp_get_nav_menu_items('main-menu');
		exit;
	}
	
	function tms_get_posts($postData=array())
	{
		
	    $args = array(
			'posts_per_page'   => 10,
			'offset'           => 0,
			'category'         => '',
			'category_name'    => '',
			'orderby'          => 'date',
			'order'            => 'DESC',
			'include'          => '',
			'exclude'          => '',
			'meta_key'         => '',
			'meta_value'       => '',
			'post_type'        => 'post',
			'post_mime_type'   => '',
			'post_parent'      => '',
			'author'	   => '',
			'author_name'	   => '',
			'post_status'      => 'publish',
			'suppress_filters' => true 
		);
		
		$lang ='';
		if(isset($postData['lang']) && $postData['lang']!='')
			$lang =base64_decode($postData['lang']);
		
		//return $latest_posts = get_posts( $args );exit;
		
		$latest_posts = get_posts( $args );
		$blogs =array();
		foreach($latest_posts as $latest_post)
		{
			///$post_id = icl_object_id($latest_post->ID, 'post', true, $lang);
			$blog= get_post( $latest_post->ID );
			$blog->featured_img =$featured_img_url = get_the_post_thumbnail_url($latest_post->ID,'full');
			array_push($blogs,$blog);
			
		}
		return $blogs;
		exit;
	}
}