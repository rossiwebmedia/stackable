/**
 * Stackable Shipping Admin JavaScript
 */
jQuery(document).ready(function($) {
    console.log('Stackable Shipping Admin Script Loaded');
    
    var StackableShippingAdmin = {
        init: function() {
            this.carrierModal = $('#carrier-modal');
            this.carrierForm = $('#carrier-form');
            
            this.initEvents();
            console.log('Admin JS initialized');
        },
        
        initEvents: function() {
            // Open add new carrier modal
            $('#add-new-carrier').on('click', this.openAddCarrierModal.bind(this));
            
            // Open edit carrier modal
            $('.carriers-list').on('click', '.edit-carrier', this.openEditCarrierModal.bind(this));
            
            // Delete carrier
            $('.carriers-list').on('click', '.delete-carrier', this.deleteCarrier.bind(this));
            
            // Add rate row
            $('.add-rate').on('click', this.addRateRow.bind(this));
            
            // Remove rate row
            this.carrierForm.on('click', '.remove-rate', this.removeRateRow.bind(this));
            
            // Save carrier
            this.carrierForm.on('submit', this.saveCarrier.bind(this));
            
            // Close modal
            $('.close, .cancel-edit').on('click', this.closeModal.bind(this));
            
            console.log('Events bound');
        },
        
        openAddCarrierModal: function(e) {
            if (e) e.preventDefault();
            
            console.log('Opening add carrier modal');
            
            // Reset form
            this.carrierForm[0].reset();
            
            // Clear carrier ID
            $('#carrier-id').val('');
            
            // Set title
            $('#modal-title').text(stackable_shipping.i18n.add_carrier || 'Aggiungi Corriere');
            
            // Add empty rate row
            $('#rate-rows').empty();
            this.addRateRow(null, {
                weight_min: 0,
                weight_max: 5,
                price: 10
            });
            
            // Set default values
            $('#carrier-calculation-type').val('higher');
            $('#carrier-volumetric-divisor').val('5000');
            $('#carrier-length-max').val('0');
            $('#carrier-width-max').val('0');
            $('#carrier-height-max').val('0');
            $('#carrier-girth-max').val('0');
            
            // Show modal
            this.carrierModal.show();
        },
        
        openEditCarrierModal: function(e) {
            e.preventDefault();
            
            var carrierId = $(e.currentTarget).data('carrier-id');
            console.log('Opening edit carrier modal for ID:', carrierId);
            
            var carrier = this.getCarrierById(carrierId);
            
            if (!carrier) {
                console.error('Carrier not found with ID:', carrierId);
                return;
            }
            
            console.log('Editing carrier:', carrier);
            
            // Reset form
            this.carrierForm[0].reset();
            
            // Populate form
            $('#carrier-id').val(carrier.id);
            $('#carrier-name').val(carrier.name);
            $('#carrier-description').val(carrier.description || '');
            $('#carrier-enabled').prop('checked', carrier.enabled === 'yes');
            
            // Set calculation type
            $('#carrier-calculation-type').val(carrier.calculation_type || 'higher');
            
            // Set volumetric divisor
            $('#carrier-volumetric-divisor').val(carrier.volumetric_divisor || '5000');
            
            // Set dimension limits
            if (carrier.dimension_limits) {
                $('#carrier-length-max').val(carrier.dimension_limits.length_max || '0');
                $('#carrier-width-max').val(carrier.dimension_limits.width_max || '0');
                $('#carrier-height-max').val(carrier.dimension_limits.height_max || '0');
                $('#carrier-girth-max').val(carrier.dimension_limits.girth_max || '0');
            } else {
                $('#carrier-length-max').val('0');
                $('#carrier-width-max').val('0');
                $('#carrier-height-max').val('0');
                $('#carrier-girth-max').val('0');
            }
            
            // Set title
            $('#modal-title').text(stackable_shipping.i18n.edit_carrier || 'Modifica Corriere');
            
            // Add rate rows
            $('#rate-rows').empty();
            if (carrier.rates && carrier.rates.length > 0) {
                $.each(carrier.rates, function(index, rate) {
                    this.addRateRow(null, rate);
                }.bind(this));
            } else {
                this.addRateRow(null, {
                    weight_min: 0,
                    weight_max: 5,
                    price: 10
                });
            }
            
            // Show modal
            this.carrierModal.show();
        },
        
        closeModal: function(e) {
            if (e) e.preventDefault();
            this.carrierModal.hide();
        },
        
        addRateRow: function(e, data) {
            if (e) e.preventDefault();
            
            var template = $('#rate-row-template').html();
            var rowCount = $('#rate-rows .rate-row').length;
            var rowData = data || { weight_min: 0, weight_max: 0, price: 0 };
            
            // Replace placeholders
            var rowHtml = template.replace(/\{index\}/g, rowCount);
            rowHtml = rowHtml.replace(/\{weight_min\}/g, rowData.weight_min);
            rowHtml = rowHtml.replace(/\{weight_max\}/g, rowData.weight_max);
            rowHtml = rowHtml.replace(/\{price\}/g, rowData.price);
            
            $('#rate-rows').append(rowHtml);
        },
        
        removeRateRow: function(e) {
            e.preventDefault();
            
            $(e.currentTarget).closest('.rate-row').remove();
            
            // Reindex remaining rows
            $('#rate-rows .rate-row').each(function(index, row) {
                $(row).find('input').each(function() {
                    var name = $(this).attr('name');
                    $(this).attr('name', name.replace(/\[\d+\]/, '[' + index + ']'));
                });
            });
        },
        
        saveCarrier: function(e) {
            e.preventDefault();
            
            console.log('Saving carrier...');
            
            // Log form values before submission
            console.log('Form values:');
            console.log('ID:', $('#carrier-id').val());
            console.log('Name:', $('#carrier-name').val());
            console.log('Calculation type:', $('#carrier-calculation-type').val());
            console.log('Volumetric divisor:', $('#carrier-volumetric-divisor').val());
            
            var formData = this.carrierForm.serialize();
            
            $.ajax({
                url: stackable_shipping.ajax_url,
                type: 'POST',
                data: formData + '&action=stackable_save_carrier&nonce=' + stackable_shipping.nonce,
                dataType: 'json',
                beforeSend: function() {
                    $('.save-carrier').prop('disabled', true).text(stackable_shipping.i18n.saving || 'Salvataggio...');
                },
                success: function(response) {
                    console.log('Save response:', response);
                    
                    if (response && response.success) {
                        // Ensure message exists
                        var message = 'Corriere salvato con successo.';
                        if (response.data && response.data.message) {
                            message = response.data.message;
                        }
                        
                        // Show success message
                        $('#stackable-shipping-message')
                            .removeClass('notice-error')
                            .addClass('notice-success')
                            .html('<p>' + message + '</p>')
                            .show();
                        
                        // Reload page after a short delay
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                        
                        // Close modal
                        this.closeModal();
                    } else {
                        // Show error message
                        var errorMessage = 'Errore durante il salvataggio.';
                        if (response && response.data && response.data.message) {
                            errorMessage = response.data.message;
                        }
                        
                        $('#stackable-shipping-message')
                            .removeClass('notice-success')
                            .addClass('notice-error')
                            .html('<p>' + errorMessage + '</p>')
                            .show();
                    }
                }.bind(this),
                error: function(xhr, status, error) {
                    console.error('AJAX error:', {
                        xhr: xhr,
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });
                    
                    // Try to parse the response
                    var errorMessage = 'Errore durante il salvataggio.';
                    try {
                        var jsonResponse = JSON.parse(xhr.responseText);
                        if (jsonResponse && jsonResponse.data && jsonResponse.data.message) {
                            errorMessage = jsonResponse.data.message;
                        }
                    } catch (e) {
                        console.error('Error parsing JSON response:', e);
                    }
                    
                    // Show error message
                    $('#stackable-shipping-message')
                        .removeClass('notice-success')
                        .addClass('notice-error')
                        .html('<p>' + errorMessage + '</p>')
                        .show();
                },
                complete: function() {
                    $('.save-carrier').prop('disabled', false).text(stackable_shipping.i18n.save || 'Salva');
                }
            });
        },
        
        deleteCarrier: function(e) {
            e.preventDefault();
            
            if (!confirm(stackable_shipping.i18n.delete_confirm || 'Sei sicuro di voler eliminare questo corriere?')) {
                return;
            }
            
            var deleteButton = $(e.currentTarget);
            var carrierId = deleteButton.data('carrier-id');
            
            console.log('Deleting carrier ID:', carrierId);
            console.log('Security nonce:', stackable_shipping.nonce);
            
            // Verifica che l'ID del carrier sia valido
            if (!carrierId) {
                console.error('Error: No carrier ID found');
                alert('Errore: ID corriere mancante');
                return;
            }
            
            $.ajax({
                url: stackable_shipping.ajax_url,
                type: 'POST',
                data: {
                    action: 'stackable_delete_carrier',
                    nonce: stackable_shipping.nonce,
                    carrier_id: carrierId
                },
                dataType: 'json',
                beforeSend: function() {
                    deleteButton.prop('disabled', true).text('Eliminazione...');
                },
                success: function(response) {
                    console.log('Delete success response:', response);
                    
                    // Messaggio predefinito
                    var message = 'Corriere eliminato con successo.';
                    
                    if (response && response.success) {
                        // Usa il messaggio dalla risposta se disponibile
                        if (response.data && response.data.message) {
                            message = response.data.message;
                        }
                        
                        $('#stackable-shipping-message')
                            .removeClass('notice-error')
                            .addClass('notice-success')
                            .html('<p>' + message + '</p>')
                            .show();
                        
                        // Ricarica la pagina dopo un breve ritardo
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        // Gestione errore
                        var errorMessage = 'Errore durante l\'eliminazione.';
                        if (response && response.data && response.data.message) {
                            errorMessage = response.data.message;
                        }
                        
                        $('#stackable-shipping-message')
                            .removeClass('notice-success')
                            .addClass('notice-error')
                            .html('<p>' + errorMessage + '</p>')
                            .show();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Delete error:', xhr.responseText);
                    
                    var errorMessage = 'Errore durante l\'eliminazione del corriere.';
                    
                    // Prova a interpretare la risposta
                    if (xhr.responseText === '0') {
                        errorMessage = 'Errore di autenticazione. Ricarica la pagina e riprova.';
                    } else if (xhr.status === 400) {
                        errorMessage = 'Richiesta non valida. Parametri mancanti o non corretti.';
                    }
                    
                    $('#stackable-shipping-message')
                        .removeClass('notice-success')
                        .addClass('notice-error')
                        .html('<p>' + errorMessage + '</p>')
                        .show();
                },
                complete: function() {
                    deleteButton.prop('disabled', false).text('Elimina');
                }
            });
        },
        
        getCarrierById: function(id) {
            if (!window.stackable_shipping_carriers) {
                console.error('Carriers data not available');
                return null;
            }
            
            for (var i = 0; i < window.stackable_shipping_carriers.length; i++) {
                if (window.stackable_shipping_carriers[i].id === id) {
                    return window.stackable_shipping_carriers[i];
                }
            }
            
            return null;
        }
    };
    
    // Initialize admin
    StackableShippingAdmin.init();
});