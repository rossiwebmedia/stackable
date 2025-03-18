<?php
/**
 * Stackable Shipping Carriers
 *
 * @package Stackable_Shipping
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Stackable_Shipping_Carriers class
 */
class Stackable_Shipping_Carriers {
    /**
     * Option name for storing carriers data
     */
    const OPTION_NAME = 'stackable_shipping_carriers';
    
    /**
     * Get carriers
     *
     * @return array
     */
    public function get_carriers() {
        $carriers = get_option( self::OPTION_NAME, array() );
        
        if ( ! is_array( $carriers ) ) {
            $carriers = array();
        }
        
        return $carriers;
    }
    
    /**
     * Get enabled carriers
     *
     * @return array
     */
    public function get_enabled_carriers() {
        $carriers = $this->get_carriers();
        $enabled_carriers = array();
        
        foreach ( $carriers as $carrier ) {
            if ( isset( $carrier['enabled'] ) && 'yes' === $carrier['enabled'] ) {
                $enabled_carriers[] = $carrier;
            }
        }
        
        return $enabled_carriers;
    }
    
    /**
     * Get carrier by ID
     *
     * @param string $carrier_id Carrier ID.
     * @return array|false
     */
    public function get_carrier( $carrier_id ) {
        $carriers = $this->get_carriers();
        
        foreach ( $carriers as $carrier ) {
            if ( $carrier['id'] === $carrier_id ) {
                return $carrier;
            }
        }
        
        return false;
    }
    
    /**
     * Save carrier data
     * 
     * @param array $carrier_data Carrier data.
     * @return bool True if carrier saved successfully.
     */
    public function save_carrier( $carrier_data ) {
        if ( empty( $carrier_data['id'] ) ) {
            return false;
        }
        
        $carriers = $this->get_carriers();
        
        $carrier_id = $carrier_data['id'];
        $carrier_exists = false;
        
        // Log per debug
        error_log('Saving carrier with id: ' . $carrier_id);
        error_log('Carrier data to save: ' . print_r($carrier_data, true));
        
        // Check if carrier already exists
        foreach ( $carriers as $key => $carrier ) {
            if ( $carrier['id'] === $carrier_id ) {
                $carriers[ $key ] = $carrier_data;
                $carrier_exists = true;
                break;
            }
        }
        
        // Add new carrier
        if ( ! $carrier_exists ) {
            $carriers[] = $carrier_data;
        }
        
        // Log dei carriers dopo l'aggiornamento
        error_log('Updated carriers array: ' . print_r($carriers, true));
        
        // Save carriers
        $result = update_option( self::OPTION_NAME, $carriers );
        
        // Log result
        error_log('Save result: ' . ($result ? 'success' : 'failure'));
        
        return $result;
    }
    
    /**
     * Delete carrier
     *
     * @param string $carrier_id Carrier ID.
     * @return bool True if carrier deleted successfully.
     */
    public function delete_carrier( $carrier_id ) {
        $carriers = $this->get_carriers();
        
        foreach ( $carriers as $key => $carrier ) {
            if ( $carrier['id'] === $carrier_id ) {
                unset( $carriers[ $key ] );
                break;
            }
        }
        
        // Re-index array
        $carriers = array_values( $carriers );
        
        return update_option( self::OPTION_NAME, $carriers );
    }
    
    /**
     * Maybe initialize carriers
     */
    public function maybe_initialize_carriers() {
        $carriers = $this->get_carriers();
        
        if ( empty( $carriers ) ) {
            $default_carriers = array(
                array(
                    'id'          => 'carrier_1',
                    'name'        => 'Corriere Standard',
                    'description' => 'Corriere standard per tutte le spedizioni',
                    'enabled'     => 'yes',
                    'calculation_type' => 'higher', // 'actual', 'volumetric', 'higher'
                    'volumetric_divisor' => 5000, // Divisore per il calcolo volumetrico
                    'dimension_limits' => array(
                        'length_max' => 150, // cm
                        'width_max'  => 100, // cm
                        'height_max' => 100, // cm
                        'girth_max'  => 300  // cm (lunghezza + 2*altezza + 2*larghezza)
                    ),
                    'rates'       => array(
                        array(
                            'weight_min' => 0,
                            'weight_max' => 5,
                            'price'      => 10,
                        ),
                        array(
                            'weight_min' => 5,
                            'weight_max' => 10,
                            'price'      => 15,
                        ),
                        array(
                            'weight_min' => 10,
                            'weight_max' => 20,
                            'price'      => 20,
                        ),
                        array(
                            'weight_min' => 20,
                            'weight_max' => 30,
                            'price'      => 25,
                        ),
                    ),
                )
            );
            
            update_option( self::OPTION_NAME, $default_carriers );
        }
    }
    
    /**
     * Calculate shipping cost based on weight
     *
     * @param string $carrier_id Carrier ID.
     * @param float $weight Package weight.
     * @return float|false
     */
    public function calculate_shipping_cost( $carrier_id, $weight ) {
        $carrier = $this->get_carrier( $carrier_id );
        
        if ( ! $carrier || ! isset( $carrier['rates'] ) || ! is_array( $carrier['rates'] ) ) {
            return false;
        }
        
        // Sort rates by weight_min (ascending)
        usort( $carrier['rates'], function( $a, $b ) {
            return $a['weight_min'] <=> $b['weight_min'];
        } );
        
        foreach ( $carrier['rates'] as $rate ) {
            if ( $weight >= $rate['weight_min'] && $weight <= $rate['weight_max'] ) {
                return $rate['price'];
            }
        }
        
        // If no matching rate found, check if the weight is higher than the highest rate
        $last_rate = end( $carrier['rates'] );
        
        if ( $weight > $last_rate['weight_max'] ) {
            // Weight exceeds highest rate, could return false or apply a calculation
            return false;
        }
        
        return false;
    }
}