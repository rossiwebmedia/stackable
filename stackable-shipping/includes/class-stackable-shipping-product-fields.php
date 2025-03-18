<?php
/**
 * Product fields for stackable shipping
 *
 * @package Stackable_Shipping
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Stackable_Shipping_Product_Fields class.
 */
class Stackable_Shipping_Product_Fields {

    /**
     * Constructor.
     */
    public function __construct() {
        // Add custom product fields
        add_action( 'woocommerce_product_options_dimensions', array( $this, 'add_stackable_fields' ) );
        add_action( 'woocommerce_process_product_meta', array( $this, 'save_stackable_fields' ) );
        
        // Add fields to variation products
        add_action( 'woocommerce_variation_options_dimensions', array( $this, 'add_stackable_fields_to_variations' ), 10, 3 );
        add_action( 'woocommerce_save_product_variation', array( $this, 'save_stackable_fields_variations' ), 10, 2 );
    }

    /**
     * Add stackable fields to simple products
     */
    public function add_stackable_fields() {
        global $post;
        
        echo '<div class="options_group">';
        
        // Stackable checkbox field
        woocommerce_wp_checkbox( array(
            'id'          => '_is_stackable',
            'label'       => __( 'Prodotto impilabile', 'stackable-shipping' ),
            'description' => __( 'Spunta se questo prodotto è impilabile per la spedizione', 'stackable-shipping' ),
        ) );
        
        // Additional height per stacked item field
        woocommerce_wp_text_input( array(
            'id'          => '_stackable_additional_height',
            'label'       => __( 'Altezza aggiuntiva per prodotto impilato (cm)', 'stackable-shipping' ),
            'description' => __( 'Incremento di altezza per ogni prodotto aggiuntivo impilato', 'stackable-shipping' ),
            'desc_tip'    => true,
            'type'        => 'number',
            'custom_attributes' => array(
                'step' => '0.1',
                'min'  => '0',
            ),
        ) );
        
        echo '</div>';
    }

    /**
     * Save stackable fields for simple products
     *
     * @param int $post_id Product ID.
     */
    public function save_stackable_fields( $post_id ) {
        // Save stackable checkbox
        $is_stackable = isset( $_POST['_is_stackable'] ) ? 'yes' : 'no';
        update_post_meta( $post_id, '_is_stackable', $is_stackable );
        
        // Save additional height
        if ( isset( $_POST['_stackable_additional_height'] ) ) {
            update_post_meta( $post_id, '_stackable_additional_height', wc_clean( wp_unslash( $_POST['_stackable_additional_height'] ) ) );
        }
    }

    /**
     * Add stackable fields to variation products
     *
     * @param int     $loop           Position in the loop.
     * @param array   $variation_data Variation data.
     * @param WP_Post $variation      Post data.
     */
    public function add_stackable_fields_to_variations( $loop, $variation_data, $variation ) {
        // Stackable checkbox field for variation
        woocommerce_wp_checkbox( array(
            'id'            => '_is_stackable_variation[' . $loop . ']',
            'name'          => '_is_stackable_variation[' . $loop . ']',
            'label'         => __( 'Prodotto impilabile', 'stackable-shipping' ),
            'description'   => __( 'Spunta se questa variazione è impilabile per la spedizione', 'stackable-shipping' ),
            'value'         => get_post_meta( $variation->ID, '_is_stackable', true ),
            'wrapper_class' => 'form-row form-row-first',
        ) );
        
        // Additional height per stacked item field for variation
        woocommerce_wp_text_input( array(
            'id'            => '_stackable_additional_height_variation[' . $loop . ']',
            'name'          => '_stackable_additional_height_variation[' . $loop . ']',
            'label'         => __( 'Altezza aggiuntiva per prodotto impilato (cm)', 'stackable-shipping' ),
            'description'   => __( 'Incremento di altezza per ogni prodotto aggiuntivo impilato', 'stackable-shipping' ),
            'desc_tip'      => true,
            'type'          => 'number',
            'value'         => get_post_meta( $variation->ID, '_stackable_additional_height', true ),
            'wrapper_class' => 'form-row form-row-last',
            'custom_attributes' => array(
                'step' => '0.1',
                'min'  => '0',
            ),
        ) );
    }

    /**
     * Save stackable fields for variation products
     *
     * @param int $variation_id Variation ID.
     * @param int $loop         Position in the loop.
     */
    public function save_stackable_fields_variations( $variation_id, $loop ) {
        // Save stackable checkbox for variation
        $is_stackable = isset( $_POST['_is_stackable_variation'][ $loop ] ) ? 'yes' : 'no';
        update_post_meta( $variation_id, '_is_stackable', $is_stackable );
        
        // Save additional height for variation
        if ( isset( $_POST['_stackable_additional_height_variation'][ $loop ] ) ) {
            update_post_meta( $variation_id, '_stackable_additional_height', wc_clean( wp_unslash( $_POST['_stackable_additional_height_variation'][ $loop ] ) ) );
        }
    }
}

new Stackable_Shipping_Product_Fields();