<?php
/**
 * Admin view for carrier management
 *
 * @package Stackable_Shipping
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get carriers
$carriers_obj = new Stackable_Shipping_Carriers();
$carriers = $carriers_obj->get_carriers();
?>
<script>
// Debug AJAX per trovare il problema
(function($) {
    // Salva il riferimento originale a $.ajax
    var originalAjax = $.ajax;
    
    // Sovrascrivi la funzione $.ajax
    $.ajax = function(options) {
        console.log('AJAX REQUEST:', options);
        
        // Salva i riferimenti alle callback originali
        var originalSuccess = options.success;
        var originalError = options.error;
        
        // Sostituisci la callback di successo
        options.success = function(response, status, xhr) {
            console.log('AJAX SUCCESS RESPONSE:', {
                response: response,
                status: status,
                xhr: xhr
            });
            
            // Chiama la callback originale se definita
            if (typeof originalSuccess === 'function') {
                originalSuccess.apply(this, arguments);
            }
        };
        
        // Sostituisci la callback di errore
        options.error = function(xhr, status, error) {
            console.log('AJAX ERROR RESPONSE:', {
                xhr: xhr,
                status: status,
                error: error,
                responseText: xhr.responseText
            });
            
            // Chiama la callback originale se definita
            if (typeof originalError === 'function') {
                originalError.apply(this, arguments);
            }
        };
        
        // Chiama la funzione $.ajax originale con le opzioni modificate
        return originalAjax.apply(this, arguments);
    };
})(jQuery);
</script>
<div class="wrap stackable-shipping-admin">
    <h1><?php esc_html_e( 'Gestione Corrieri', 'stackable-shipping' ); ?></h1>
    
    <div id="stackable-shipping-message" class="notice" style="display: none;"></div>

    <div class="carrier-controls">
        <button type="button" class="button button-primary" id="add-new-carrier"><?php esc_html_e( 'Aggiungi Corriere', 'stackable-shipping' ); ?></button>
    </div>
    
    <div class="carriers-list">
        <?php if ( empty( $carriers ) ) : ?>
            <div class="no-carriers">
                <?php esc_html_e( 'Nessun corriere configurato. Aggiungi un nuovo corriere per iniziare.', 'stackable-shipping' ); ?>
            </div>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Nome', 'stackable-shipping' ); ?></th>
                        <th><?php esc_html_e( 'Descrizione', 'stackable-shipping' ); ?></th>
                        <th><?php esc_html_e( 'Stato', 'stackable-shipping' ); ?></th>
                        <th><?php esc_html_e( 'Fasce di peso', 'stackable-shipping' ); ?></th>
                        <th><?php esc_html_e( 'Azioni', 'stackable-shipping' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $carriers as $carrier ) : ?>
                        <tr data-carrier-id="<?php echo esc_attr( $carrier['id'] ); ?>">
                            <td><?php echo esc_html( $carrier['name'] ); ?></td>
                            <td><?php echo esc_html( isset( $carrier['description'] ) ? $carrier['description'] : '' ); ?></td>
                            <td><?php echo esc_html( isset( $carrier['enabled'] ) && 'yes' === $carrier['enabled'] ? __( 'Attivo', 'stackable-shipping' ) : __( 'Non attivo', 'stackable-shipping' ) ); ?></td>
                            <td>
                                <?php 
                                if ( isset( $carrier['rates'] ) && is_array( $carrier['rates'] ) ) : 
                                    echo esc_html( count( $carrier['rates'] ) . ' ' . __( 'fasce', 'stackable-shipping' ) );
                                else : 
                                    echo '&mdash;';
                                endif; 
                                ?>
                            </td>
                            <td>
                                <button type="button" class="button edit-carrier" data-carrier-id="<?php echo esc_attr( $carrier['id'] ); ?>"><?php esc_html_e( 'Modifica', 'stackable-shipping' ); ?></button>
                                <button type="button" class="button delete-carrier" data-carrier-id="<?php echo esc_attr( $carrier['id'] ); ?>"><?php esc_html_e( 'Elimina', 'stackable-shipping' ); ?></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <!-- Carrier edit modal -->
    <div id="carrier-modal" class="stackable-modal" style="display: none;">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 id="modal-title"><?php esc_html_e( 'Modifica Corriere', 'stackable-shipping' ); ?></h2>
            
            <form id="carrier-form">
                <input type="hidden" id="carrier-id" name="carrier[id]" value="">
                
                <div class="form-field">
                    <label for="carrier-name"><?php esc_html_e( 'Nome', 'stackable-shipping' ); ?> <span class="required">*</span></label>
                    <input type="text" id="carrier-name" name="carrier[name]" required>
                </div>
                
                <div class="form-field">
                    <label for="carrier-description"><?php esc_html_e( 'Descrizione', 'stackable-shipping' ); ?></label>
                    <textarea id="carrier-description" name="carrier[description]"></textarea>
                </div>
                
                <div class="form-field">
                    <label>
                        <input type="checkbox" id="carrier-enabled" name="carrier[enabled]" value="yes">
                        <?php esc_html_e( 'Attivo', 'stackable-shipping' ); ?>
                    </label>
                </div>
                
                <h3><?php esc_html_e( 'Fasce di Peso', 'stackable-shipping' ); ?></h3>
                
                <div id="rate-rows">
                    <!-- Rate rows will be added here -->
                </div>
                
                <button type="button" class="button add-rate"><?php esc_html_e( 'Aggiungi Fascia', 'stackable-shipping' ); ?></button>
                
                <h3><?php esc_html_e( 'Tipo di calcolo del peso', 'stackable-shipping' ); ?></h3>
                <div class="form-field">
                    <select id="carrier-calculation-type" name="carrier[calculation_type]">
                        <option value="actual"><?php esc_html_e( 'Solo peso reale', 'stackable-shipping' ); ?></option>
                        <option value="volumetric"><?php esc_html_e( 'Solo peso volumetrico', 'stackable-shipping' ); ?></option>
                        <option value="higher" selected><?php esc_html_e( 'Valore maggiore tra reale e volumetrico', 'stackable-shipping' ); ?></option>
                    </select>
                </div>

                <h3><?php esc_html_e( 'Divisore volumetrico', 'stackable-shipping' ); ?></h3>
                <div class="form-field">
                    <input type="number" min="1" step="1" id="carrier-volumetric-divisor" name="carrier[volumetric_divisor]" value="5000">
                    <p class="description">
                        <?php esc_html_e( 'Divisore per calcolare il peso volumetrico: (Lunghezza × Larghezza × Altezza) ÷ Divisore.', 'stackable-shipping' ); ?><br>
                        <?php esc_html_e( 'Standard: 5000 internazionale, 4000 Europa, 3000 express.', 'stackable-shipping' ); ?>
                    </p>
                </div>

                <h3><?php esc_html_e( 'Limiti dimensionali', 'stackable-shipping' ); ?></h3>
                <p class="description"><?php esc_html_e( 'Lascia 0 se non ci sono limiti per una dimensione', 'stackable-shipping' ); ?></p>

                <div class="form-field dimension-limits">
                    <div class="dimension-field">
                        <label for="carrier-length-max"><?php esc_html_e( 'Lunghezza massima (cm)', 'stackable-shipping' ); ?></label>
                        <input type="number" min="0" step="0.1" id="carrier-length-max" name="carrier[dimension_limits][length_max]" value="0">
                    </div>
                    
                    <div class="dimension-field">
                        <label for="carrier-width-max"><?php esc_html_e( 'Larghezza massima (cm)', 'stackable-shipping' ); ?></label>
                        <input type="number" min="0" step="0.1" id="carrier-width-max" name="carrier[dimension_limits][width_max]" value="0">
                    </div>
                    
                    <div class="dimension-field">
                        <label for="carrier-height-max"><?php esc_html_e( 'Altezza massima (cm)', 'stackable-shipping' ); ?></label>
                        <input type="number" min="0" step="0.1" id="carrier-height-max" name="carrier[dimension_limits][height_max]" value="0">
                    </div>
                    
                    <div class="dimension-field">
                        <label for="carrier-girth-max"><?php esc_html_e( 'Circonferenza massima (cm)', 'stackable-shipping' ); ?></label>
                        <input type="number" min="0" step="0.1" id="carrier-girth-max" name="carrier[dimension_limits][girth_max]" value="0">
                        <p class="description"><?php esc_html_e( 'Circonferenza = lunghezza + 2*altezza + 2*larghezza', 'stackable-shipping' ); ?></p>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="button button-primary save-carrier"><?php esc_html_e( 'Salva', 'stackable-shipping' ); ?></button>
                    <button type="button" class="button cancel-edit"><?php esc_html_e( 'Annulla', 'stackable-shipping' ); ?></button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Rate row template -->
    <script type="text/template" id="rate-row-template">
        <div class="rate-row">
            <div class="rate-field">
                <label><?php esc_html_e( 'Peso min (kg)', 'stackable-shipping' ); ?></label>
                <input type="number" name="carrier[rates][{index}][weight_min]" value="{weight_min}" min="0" step="0.1" required>
            </div>
            <div class="rate-field">
                <label><?php esc_html_e( 'Peso max (kg)', 'stackable-shipping' ); ?></label>
                <input type="number" name="carrier[rates][{index}][weight_max]" value="{weight_max}" min="0" step="0.1" required>
            </div>
            <div class="rate-field">
                <label><?php esc_html_e( 'Prezzo (€)', 'stackable-shipping' ); ?></label>
                <input type="number" name="carrier[rates][{index}][price]" value="{price}" min="0" step="0.01" required>
            </div>
            <button type="button" class="button remove-rate"><?php esc_html_e( 'Rimuovi', 'stackable-shipping' ); ?></button>
        </div>
    </script>
    
    <!-- Carrier data store -->
    <script type="text/javascript">
        var stackable_shipping_carriers = <?php echo wp_json_encode( $carriers ); ?>;
    </script>
</div>