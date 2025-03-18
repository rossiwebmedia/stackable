<?php
/**
 * Stackable Shipping Admin
 *
 * @package Stackable_Shipping
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Stackable Shipping Admin class
 */
class Stackable_Shipping_Admin {
    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
        
        // Ajax handlers
        add_action( 'wp_ajax_stackable_save_carrier', array( $this, 'ajax_save_carrier' ) );
        add_action( 'wp_ajax_stackable_delete_carrier', array( $this, 'ajax_delete_carrier' ) );
        add_action( 'wp_ajax_stackable_get_carriers', array( $this, 'ajax_get_carriers' ) );
    }
    
    /**
     * Add admin menu
     */
    public function admin_menu() {
        add_submenu_page(
            'woocommerce',
            __( 'Stackable Shipping', 'stackable-shipping' ),
            __( 'Stackable Shipping', 'stackable-shipping' ),
            'manage_woocommerce',
            'stackable-shipping',
            array( $this, 'admin_page' )
        );
    }
    
    /**
     * Admin page
     */
    public function admin_page() {
        $current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'carriers';
        
        echo '<div class="wrap">';
        $this->show_tabs( $current_tab );
        
        if ( 'carriers' === $current_tab ) {
            $this->show_carriers_page();
        } elseif ( 'settings' === $current_tab ) {
            $this->show_settings_page();
        }
        
        echo '</div>';
    }
    
    /**
     * Show tabs
     *
     * @param string $current_tab Current tab.
     */
    private function show_tabs( $current_tab ) {
        echo '<nav class="nav-tab-wrapper woo-nav-tab-wrapper">';
        
        echo '<a href="' . esc_url( admin_url( 'admin.php?page=stackable-shipping&tab=carriers' ) ) . '" class="nav-tab ' . ( 'carriers' === $current_tab ? 'nav-tab-active' : '' ) . '">' . esc_html__( 'Gestione Corrieri', 'stackable-shipping' ) . '</a>';
        
        echo '<a href="' . esc_url( admin_url( 'admin.php?page=stackable-shipping&tab=settings' ) ) . '" class="nav-tab ' . ( 'settings' === $current_tab ? 'nav-tab-active' : '' ) . '">' . esc_html__( 'Impostazioni', 'stackable-shipping' ) . '</a>';
        
        echo '</nav>';
    }
    
    /**
     * Show carriers page
     */
    private function show_carriers_page() {
        include_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/views/html-carrier-management.php';
    }
    
    /**
     * Show settings page
     */
    private function show_settings_page() {
        include_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/views/html-settings.php';
    }
    
    /**
     * Load admin scripts
     *
     * @param string $hook Current admin page.
     */
    public function admin_scripts( $hook ) {
        if ( 'woocommerce_page_stackable-shipping' !== $hook ) {
            return;
        }
        
        wp_enqueue_style( 'stackable-shipping-admin', STACKABLE_SHIPPING_URL . 'assets/css/admin.css', array(), STACKABLE_SHIPPING_VERSION );
        
        wp_enqueue_script( 'stackable-shipping-admin', STACKABLE_SHIPPING_URL . 'assets/js/admin.js', array( 'jquery' ), STACKABLE_SHIPPING_VERSION, true );
        
        wp_localize_script( 'stackable-shipping-admin', 'stackable_shipping', array(
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
        ) );
    }
    
  /**
 * Handle Ajax request to save carrier
 */
public function ajax_save_carrier() {
    check_ajax_referer( 'stackable_shipping_admin', 'nonce' );
    
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_die( -1 );
    }
    
    // Aggiungi logging per debug
    error_log('=== START CARRIER SAVE ===');
    error_log('Raw POST data: ' . print_r($_POST, true));
    
    // Riceviamo i dati non filtrati per gestire array annidati
    $raw_carrier_data = isset( $_POST['carrier'] ) ? $_POST['carrier'] : array();
    if ( empty( $raw_carrier_data ) || ! isset( $raw_carrier_data['name'] ) ) {
        wp_send_json_error( array(
            'message' => __( 'Dati del corriere mancanti o incompleti.', 'stackable-shipping' ),
        ) );
    }
    
    error_log('Raw carrier data received: ' . print_r($raw_carrier_data, true));
    
    // Sanitizza i dati preservando la struttura
    $carrier_data = array();
    
    // Campi di base (stringhe/numeri)
    $carrier_data['id'] = isset( $raw_carrier_data['id'] ) ? sanitize_text_field( $raw_carrier_data['id'] ) : 'carrier_' . time();
    $carrier_data['name'] = sanitize_text_field( $raw_carrier_data['name'] );
    $carrier_data['description'] = isset( $raw_carrier_data['description'] ) ? sanitize_textarea_field( $raw_carrier_data['description'] ) : '';
    $carrier_data['enabled'] = isset( $raw_carrier_data['enabled'] ) ? 'yes' : 'no';
    $carrier_data['calculation_type'] = isset( $raw_carrier_data['calculation_type'] ) ? sanitize_text_field( $raw_carrier_data['calculation_type'] ) : 'higher';
    $carrier_data['volumetric_divisor'] = isset( $raw_carrier_data['volumetric_divisor'] ) && !empty($raw_carrier_data['volumetric_divisor']) ? absint( $raw_carrier_data['volumetric_divisor'] ) : 5000;
    
    // Gestione dei limiti dimensionali
    if ( isset( $raw_carrier_data['dimension_limits'] ) && is_array( $raw_carrier_data['dimension_limits'] ) ) {
        $carrier_data['dimension_limits'] = array(
            'length_max' => isset( $raw_carrier_data['dimension_limits']['length_max'] ) ? floatval( $raw_carrier_data['dimension_limits']['length_max'] ) : 0,
            'width_max'  => isset( $raw_carrier_data['dimension_limits']['width_max'] ) ? floatval( $raw_carrier_data['dimension_limits']['width_max'] ) : 0,
            'height_max' => isset( $raw_carrier_data['dimension_limits']['height_max'] ) ? floatval( $raw_carrier_data['dimension_limits']['height_max'] ) : 0,
            'girth_max'  => isset( $raw_carrier_data['dimension_limits']['girth_max'] ) ? floatval( $raw_carrier_data['dimension_limits']['girth_max'] ) : 0,
        );
    } else {
        error_log('WARNING: dimension_limits non trovato nei dati del form!');
        // Assicuriamoci che esista comunque la struttura
        $carrier_data['dimension_limits'] = array(
            'length_max' => 0,
            'width_max'  => 0,
            'height_max' => 0,
            'girth_max'  => 0,
        );
    }
    
    // Log dei dati dopo la sanitizzazione
    error_log('Processed carrier data: ' . print_r($carrier_data, true));
    
    // Gestione delle fasce di peso
    if ( isset( $raw_carrier_data['rates'] ) && is_array( $raw_carrier_data['rates'] ) ) {
        $carrier_data['rates'] = array();
        foreach ( $raw_carrier_data['rates'] as $rate ) {
            if ( isset( $rate['weight_min'], $rate['weight_max'], $rate['price'] ) ) {
                $carrier_data['rates'][] = array(
                    'weight_min' => floatval( $rate['weight_min'] ),
                    'weight_max' => floatval( $rate['weight_max'] ),
                    'price'      => floatval( $rate['price'] ),
                );
            }
        }
    }
    
    $carriers_obj = new Stackable_Shipping_Carriers();
    $result = $carriers_obj->save_carrier( $carrier_data );
    
    // Verifichiamo il risultato e recuperiamo il corriere aggiornato per il debug
    $updated_carrier = $carriers_obj->get_carrier( $carrier_data['id'] );
    error_log('Saved carrier result: ' . ($result ? 'success' : 'failure'));
    error_log('Updated carrier data: ' . print_r($updated_carrier, true));
    error_log('=== END CARRIER SAVE ===');
    
    if ( $result ) {
        wp_send_json_success( array(
            'carrier_id' => $carrier_data['id'],
            'message'    => __( 'Corriere salvato con successo.', 'stackable-shipping' ),
            'debug_carrier' => $updated_carrier // Solo per debug
        ) );
    } else {
        wp_send_json_error( array(
            'message' => __( 'Errore durante il salvataggio del corriere.', 'stackable-shipping' ),
        ) );
    }
}
    /**
 * Handle Ajax request to delete carrier
 */
public function ajax_delete_carrier() {
    // Aggiungi log per debug
    error_log('=== DELETE CARRIER START ===');
    error_log('POST data: ' . print_r($_POST, true));
    
    // Verifica il nonce di sicurezza
    check_ajax_referer('stackable_shipping_admin', 'nonce');
    
    // Verifica i permessi utente
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(array(
            'message' => __('Non hai i permessi per eseguire questa azione.', 'stackable-shipping'),
        ));
        return;
    }
    
    // Ottieni e valida l'ID del corriere
    $carrier_id = isset($_POST['carrier_id']) ? sanitize_text_field($_POST['carrier_id']) : '';
    error_log('Carrier ID to delete: ' . $carrier_id);
    
    if (empty($carrier_id)) {
        error_log('Error: Empty carrier ID');
        wp_send_json_error(array(
            'message' => __('ID del corriere mancante.', 'stackable-shipping'),
        ));
        return;
    }
    
    // Prova a eliminare il corriere
    $carriers_obj = new Stackable_Shipping_Carriers();
    
    // Verifica prima se il corriere esiste
    $carrier = $carriers_obj->get_carrier($carrier_id);
    if (!$carrier) {
        error_log('Error: Carrier not found');
        wp_send_json_error(array(
            'message' => __('Corriere non trovato.', 'stackable-shipping'),
        ));
        return;
    }
    
    // Esegui l'eliminazione
    $result = $carriers_obj->delete_carrier($carrier_id);
    error_log('Delete result: ' . ($result ? 'success' : 'failure'));
    
    if ($result) {
        error_log('=== DELETE CARRIER END: SUCCESS ===');
        wp_send_json_success(array(
            'message' => __('Corriere eliminato con successo.', 'stackable-shipping'),
        ));
    } else {
        error_log('=== DELETE CARRIER END: FAILURE ===');
        wp_send_json_error(array(
            'message' => __('Errore durante l\'eliminazione del corriere.', 'stackable-shipping'),
        ));
    }
}
    
    /**
     * Handle Ajax request to get carriers
     */
    public function ajax_get_carriers() {
        check_ajax_referer( 'stackable_shipping_admin', 'nonce' );
        
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( -1 );
        }
        
        $carriers_obj = new Stackable_Shipping_Carriers();
        $carriers = $carriers_obj->get_carriers();
        
        wp_send_json_success( array(
            'carriers' => $carriers,
        ) );
    }
}

new Stackable_Shipping_Admin();