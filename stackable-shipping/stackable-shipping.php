<?php
/**
 * Plugin Name: Stackable Shipping for WooCommerce
 * Plugin URI: https://github.com/rossiwebmedia/stackable-shipping
 * Description: Gestione avanzata delle spedizioni con supporto per prodotti impilabili e calcolo automatico del peso volumetrico.
 * Version: 1.0.0
 * Author: RossiWebMedia
 * Author URI: https://rossiwebmedia.com
 * Text Domain: stackable-shipping
 * Domain Path: /languages
 * WC requires at least: 9.3
 * WC tested up to: 9.3
 * Requires at least: 6.6.2
 * Requires PHP: 7.4
 *
 * @package Stackable_Shipping
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Define plugin constants
define( 'STACKABLE_SHIPPING_VERSION', '1.0.0' );
define( 'STACKABLE_SHIPPING_PATH', plugin_dir_path( __FILE__ ) );
define( 'STACKABLE_SHIPPING_URL', plugin_dir_url( __FILE__ ) );

/**
 * Check if WooCommerce is active
 */
function stackable_shipping_check_woocommerce() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die( __( 'Stackable Shipping richiede WooCommerce per funzionare. Per favore installa e attiva WooCommerce.', 'stackable-shipping' ) );
    }
}
register_activation_hook( __FILE__, 'stackable_shipping_check_woocommerce' );

/**
 * Declare HPOS compatibility
 */
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );

/**
 * Main plugin class
 */
class Stackable_Shipping {

    /**
     * Instance of this class.
     *
     * @var object
     */
    protected static $instance = null;

    /**
     * Initialize the plugin.
     */
    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Return an instance of this class.
     *
     * @return object A single instance of this class.
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Include required files.
     */
    private function includes() {
        // Admin
        require_once STACKABLE_SHIPPING_PATH . 'includes/admin/class-stackable-shipping-admin.php';
        
        // Core functionality
        require_once STACKABLE_SHIPPING_PATH . 'includes/class-stackable-shipping-product-fields.php';
        require_once STACKABLE_SHIPPING_PATH . 'includes/class-stackable-shipping-carriers.php';
        require_once STACKABLE_SHIPPING_PATH . 'includes/class-stackable-shipping-calculator.php';
        require_once STACKABLE_SHIPPING_PATH . 'includes/class-stackable-shipping-method.php';
    }

    /**
     * Initialize hooks.
     */
    private function init_hooks() {
        add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );
        add_action( 'woocommerce_shipping_init', array( $this, 'include_shipping_method' ) );
        add_filter( 'woocommerce_shipping_methods', array( $this, 'add_shipping_method' ) );
        
        // Admin scripts and initialization
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
        add_action( 'init', array( $this, 'maybe_initialize_carriers' ) );
    }
    
    /**
     * Maybe initialize default carriers
     */
    public function maybe_initialize_carriers() {
        if (class_exists('Stackable_Shipping_Carriers')) {
            $carriers_obj = new Stackable_Shipping_Carriers();
            $carriers_obj->maybe_initialize_carriers();
        }
    }

    /**
     * Load admin scripts and styles
     */
    public function admin_scripts( $hook ) {
        // Carica gli script solo nella pagina di gestione di Stackable Shipping
        if ( 'woocommerce_page_stackable-shipping' !== $hook ) {
            return;
        }
        
        // Definisci una versione per evitare problemi di cache
        $version = defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : STACKABLE_SHIPPING_VERSION;
        
        // Registra e carica il CSS
        wp_enqueue_style( 
            'stackable-shipping-admin', 
            STACKABLE_SHIPPING_URL . 'assets/css/admin.css', 
            array(), 
            $version 
        );
        
        // Registra e carica il JavaScript
        wp_enqueue_script( 
            'stackable-shipping-admin', 
            STACKABLE_SHIPPING_URL . 'assets/js/admin.js', 
            array( 'jquery' ), 
            $version, 
            true 
        );
        
        // Passa le variabili a JavaScript
        wp_localize_script( 
            'stackable-shipping-admin', 
            'stackable_shipping', 
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'stackable_shipping_admin' ),
                'i18n'     => array(
                    'delete_confirm' => __( 'Sei sicuro di voler eliminare questo corriere?', 'stackable-shipping' ),
                    'error'          => __( 'Si è verificato un errore. Riprova.', 'stackable-shipping' ),
                    'saving'         => __( 'Salvataggio...', 'stackable-shipping' ),
                    'save'           => __( 'Salva', 'stackable-shipping' ),
                    'add_carrier'    => __( 'Aggiungi Corriere', 'stackable-shipping' ),
                    'edit_carrier'   => __( 'Modifica Corriere', 'stackable-shipping' ),
                ),
            )
        );
    }

    /**
     * Load plugin textdomain.
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain( 'stackable-shipping', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }

    /**
     * Include shipping method.
     */
    public function include_shipping_method() {
        // Already included in the includes method
    }

    /**
     * Add shipping method to WooCommerce.
     *
     * @param array $methods Shipping methods.
     * @return array
     */
    public function add_shipping_method( $methods ) {
        $methods['stackable_shipping'] = 'Stackable_Shipping_Method';
        return $methods;
    }
}

// Start the plugin
add_action( 'plugins_loaded', function() {
    // Check if WooCommerce is active
    if ( class_exists( 'WooCommerce' ) ) {
        Stackable_Shipping::get_instance();
    }
});