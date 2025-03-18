<?php
/**
 * Stackable Shipping Method
 *
 * @package Stackable_Shipping
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Stackable Shipping Method class
 */
class Stackable_Shipping_Method extends WC_Shipping_Method {
    /**
     * Constructor
     *
     * @param int $instance_id Instance ID.
     */
    public function __construct( $instance_id = 0 ) {
        $this->id                 = 'stackable_shipping';
        $this->instance_id        = absint( $instance_id );
        $this->method_title       = __( 'Stackable Shipping', 'stackable-shipping' );
        $this->method_description = __( 'Gestione avanzata delle spedizioni con supporto per prodotti impilabili e calcolo automatico del peso volumetrico.', 'stackable-shipping' );
        $this->supports           = array(
            'shipping-zones',
            'instance-settings',
        );
        
        $this->init();
    }
    
    /**
     * Initialize settings
     */
    public function init() {
        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();
        
        // Define user set variables.
        $this->title = $this->get_option( 'title' );
        $this->tax_status = $this->get_option( 'tax_status' );
        
        // Save settings in admin.
        add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
    }
    
    /**
     * Init form fields
     */
    public function init_form_fields() {
        $this->instance_form_fields = array(
            'title' => array(
                'title'       => __( 'Titolo', 'stackable-shipping' ),
                'type'        => 'text',
                'description' => __( 'Titolo da mostrare al checkout.', 'stackable-shipping' ),
                'default'     => __( 'Spedizione Standard', 'stackable-shipping' ),
                'desc_tip'    => true,
            ),
            'tax_status' => array(
                'title'       => __( 'Tassazione', 'stackable-shipping' ),
                'type'        => 'select',
                'description' => __( 'Scegli se applicare le tasse al costo di spedizione.', 'stackable-shipping' ),
                'default'     => 'taxable',
                'options'     => array(
                    'taxable' => __( 'Tassabile', 'stackable-shipping' ),
                    'none'    => __( 'Non tassabile', 'stackable-shipping' ),
                ),
                'desc_tip'    => true,
            ),
        );
    }
    
    /**
     * Calculate shipping
     *
     * @param array $package Package information.
     */
    public function calculate_shipping( $package = array() ) {
        if ( empty( $package['contents'] ) ) {
            return;
        }
        
        $calculator = new Stackable_Shipping_Calculator( $package );
        $carriers = $calculator->get_available_carriers();
        
        if ( empty( $carriers ) ) {
            // No carriers available.
            return;
        }
        
        foreach ( $carriers as $carrier ) {
            $rate = array(
                'id'      => $this->get_rate_id( $carrier['id'] ),
                'label'   => $carrier['name'],
                'cost'    => $carrier['cost'],
                'package' => $package,
            );
            
            $this->add_rate( $rate );
        }
    }
    
    /**
     * Get shipping rate ID.
     *
     * @param string $suffix Optional suffix to append to the rate ID.
     * @return string
     */
    public function get_rate_id( $suffix = '' ) {
        // Se viene passato un carrier_id come suffisso, usiamo quello
        $carrier_id = !empty( $suffix ) ? $suffix : '';
        
        // Manteniamo la compatibilità con il codice esistente che passa un carrier_id
        if ( !empty( $carrier_id ) ) {
            return $this->id . ':' . $carrier_id;
        }
        
        // Comportamento predefinito come nella classe padre
        $rate_id = $this->id;
        
        if ( '' !== $suffix ) {
            $rate_id .= ':' . $suffix;
        }
        
        return $rate_id;
    }
    
    /**
     * Is available
     *
     * @param array $package Package information.
     * @return bool
     */
    public function is_available( $package ) {
        $is_available = true;
        
        if ( empty( $package['contents'] ) ) {
            return false;
        }
        
        $calculator = new Stackable_Shipping_Calculator( $package );
        $carriers = $calculator->get_available_carriers();
        
        if ( empty( $carriers ) ) {
            $is_available = false;
        }
        
        return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', $is_available, $package, $this );
    }
}