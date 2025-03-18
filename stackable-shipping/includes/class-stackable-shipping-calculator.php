<?php
/**
 * Shipping calculator for stackable products
 *
 * @package Stackable_Shipping
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Stackable_Shipping_Calculator class.
 */
class Stackable_Shipping_Calculator {

    /**
 * Default volumetric weight divisor if not specified
 */
const DEFAULT_VOLUMETRIC_DIVISOR = 5000;

    /**
     * Constructor.
     */
    public function __construct() {
        // Nothing to initialize here
    }

    /**
     * Calculate volumetric weight for a single product
     *
     * @param WC_Product $product Product object.
     * @param int        $quantity Quantity of the product.
     * @return float|bool
     */
public function calculate_product_volumetric_weight( $product, $quantity = 1, $divisor = null ) {
        if ( ! $product ) {
            return false;
        }
        
        $length = wc_get_dimension( $product->get_length(), 'cm' );
        $width = wc_get_dimension( $product->get_width(), 'cm' );
        $height = wc_get_dimension( $product->get_height(), 'cm' );
        
        if ( empty( $length ) || empty( $width ) || empty( $height ) ) {
            return false;
        }

        // Check if product is stackable
        $is_stackable = get_post_meta( $product->get_id(), '_is_stackable', true ) === 'yes';
        
        if ( $is_stackable && $quantity > 1 ) {
            $additional_height = floatval( get_post_meta( $product->get_id(), '_stackable_additional_height', true ) );
            
            // Base height + additional height for each additional item
            $total_height = $height + ( $additional_height * ( $quantity - 1 ) );
        } else {
            // If not stackable, multiply height by quantity
            $total_height = $height * $quantity;
        }
        
     // Calculate volume in cubic cm
    $volume = $length * $width * $total_height;
    
    // Use provided divisor or default
    $volumetric_divisor = $divisor ? $divisor : self::DEFAULT_VOLUMETRIC_DIVISOR;
    
    // Calculate volumetric weight (volume / divisor)
    $volumetric_weight = $volume / $volumetric_divisor;
    
    return $volumetric_weight;
}

    /**
 * Calculate total volumetric weight for cart items
 *
 * @param array $items Cart items.
 * @param int $divisor Optional volumetric divisor.
 * @return float
 */
public function calculate_cart_volumetric_weight( $items, $divisor = null ) {
    $total_volumetric_weight = 0;
    
    foreach ( $items as $item ) {
        $product_id = $item['data']->get_id();
        $product = wc_get_product( $product_id );
        
        if ( ! $product ) {
            continue;
        }
        
        $volumetric_weight = $this->calculate_product_volumetric_weight( $product, $item['quantity'], $divisor );
        
        if ( $volumetric_weight ) {
            $total_volumetric_weight += $volumetric_weight;
        }
    }
    
    return $total_volumetric_weight;
}

    /**
     * Calculate actual weight for cart items
     *
     * @param array $items Cart items.
     * @return float
     */
    public function calculate_cart_actual_weight( $items ) {
        $total_weight = 0;
        
        foreach ( $items as $item ) {
            $product_id = $item['data']->get_id();
            $product = wc_get_product( $product_id );
            
            if ( ! $product ) {
                continue;
            }
            
            $weight = $product->get_weight();
            
            if ( $weight ) {
                $total_weight += wc_get_weight( $weight, 'kg' ) * $item['quantity'];
            }
        }
        
        return $total_weight;
    }

    /**
     * Get the higher weight between actual and volumetric
     *
     * @param array $items Cart items.
     * @return float
     */
    public function get_billable_weight( $items ) {
        $volumetric_weight = $this->calculate_cart_volumetric_weight( $items );
        $actual_weight = $this->calculate_cart_actual_weight( $items );
        
        return max( $volumetric_weight, $actual_weight );
    }
}