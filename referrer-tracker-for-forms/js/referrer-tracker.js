/**
 * Referrer Tracker JavaScript
 * 
 * This script handles tracking of referrer values and updates form fields dynamically.
 */

// Get URL parameters function
function getUrlParameter(name) {
    name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
    var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
    var results = regex.exec(location.search);
    return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
}

// Get cookie function
function getCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) == 0) return decodeURIComponent(c.substring(nameEQ.length, c.length));
    }
    return '';
}

// Get tracking value (try URL parameter first, then cookie, then refetrfoValues)
function getTrackingValue(type) {
    // Check debug mode
    var debug = (typeof refetrfoValues !== 'undefined' && refetrfoValues.debug === 'yes');
    
    // PRIORITY 1: Try URL parameter first (for UTM parameters)
    if (type === 'campaign' || type === 'source' || type === 'medium') {
        var paramValue = getUrlParameter('utm_' + type);
        if (paramValue) {
            if (debug) console.log('Refetrfo Debug: Found URL parameter for utm_' + type + ': ' + paramValue);
            return paramValue;
        }
        
        // Check for typos in medium parameter
        if (type === 'medium') {
            var typoParamValue = getUrlParameter('urm_' + type);
            if (typoParamValue) {
                if (debug) console.log('Refetrfo Debug: Found URL parameter for urm_' + type + ' (typo correction): ' + typoParamValue);
                return typoParamValue;
            }
        }
    }
    
    // PRIORITY 2: Try cookie
    var cookieValue = getCookie('refetrfo_' + type);
    if (cookieValue) {
        if (debug) console.log('Refetrfo Debug: Found cookie value for ' + type + ': ' + cookieValue);
        return cookieValue;
    }
    
    // PRIORITY 3: Try refetrfoValues from PHP
    if (typeof refetrfoValues !== 'undefined' && refetrfoValues[type]) {
        if (debug) console.log('Refetrfo Debug: Using refetrfoValues for ' + type + ': ' + refetrfoValues[type]);
        return refetrfoValues[type];
    }
    
    // Default values if nothing else is found
    if (type === 'source') return 'direct';
    if (type === 'medium') return 'none';
    if (type === 'campaign') return 'none';
    
    return '';
}

// Update WPForms fields
function updateWPFormsFields() {
    // Check debug mode
    var debug = (typeof refetrfoValues !== 'undefined' && refetrfoValues.debug === 'yes');
    
    if (debug) {
        console.log('Refetrfo Debug: Updating WPForms fields');
        console.log('Refetrfo Debug: Current cookies:', document.cookie);
    }
    
    // Get tracking values - prioritize URL parameters
    var source = getTrackingValue('source');
    var medium = getTrackingValue('medium');
    var campaign = getTrackingValue('campaign');
    var referrer = getTrackingValue('referrer');
    
    if (debug) {
        console.log('Refetrfo Debug: Tracking values to set:');
        console.log('- source:', source);
        console.log('- medium:', medium);
        console.log('- campaign:', campaign);
        console.log('- referrer:', referrer);
    }
    
    // Find WPForms fields by class
    jQuery('.js-refetrfo-source').each(function() {
        jQuery(this).val(source);
        if (debug) console.log('Refetrfo Debug: Set source field by class to:', source);
    });
    
    jQuery('.js-refetrfo-medium').each(function() {
        jQuery(this).val(medium);
        if (debug) console.log('Refetrfo Debug: Set medium field by class to:', medium);
    });
    
    jQuery('.js-refetrfo-campaign').each(function() {
        jQuery(this).val(campaign);
        if (debug) console.log('Refetrfo Debug: Set campaign field by class to:', campaign);
    });
    
    jQuery('.js-refetrfo-referrer').each(function() {
        jQuery(this).val(referrer);
        if (debug) console.log('Refetrfo Debug: Set referrer field by class to:', referrer);
    });
    
    // Find WPForms fields by container
    jQuery('.wpforms-field-hidden').each(function() {
        var $field = jQuery(this);
        var fieldId = $field.attr('id');
        var inputId = $field.find('input').attr('id');
        var inputName = $field.find('input').attr('name');
        
        if (debug) {
            console.log('Refetrfo Debug: Checking field:', fieldId, 'Input ID:', inputId, 'Input name:', inputName);
        }
        
        // Check for specific field IDs (8, 9, 10, 11)
        var fieldIdNumber = fieldId ? fieldId.replace('wpforms-field-', '') : '';
        if (['8', '9', '10', '11'].includes(fieldIdNumber)) {
            if (debug) console.log('Refetrfo Debug: Found field by ID:', fieldIdNumber);
            
            // Field 8 = source, 9 = medium, 10 = campaign, 11 = referrer
            if (fieldIdNumber === '8' && source) {
                $field.find('input').val(source);
                if (debug) console.log('Refetrfo Debug: Set source field (ID 8) to:', source);
            } else if (fieldIdNumber === '9' && medium) {
                $field.find('input').val(medium);
                if (debug) console.log('Refetrfo Debug: Set medium field (ID 9) to:', medium);
            } else if (fieldIdNumber === '10' && campaign) {
                $field.find('input').val(campaign);
                if (debug) console.log('Refetrfo Debug: Set campaign field (ID 10) to:', campaign);
            } else if (fieldIdNumber === '11' && referrer) {
                $field.find('input').val(referrer);
                if (debug) console.log('Refetrfo Debug: Set referrer field (ID 11) to:', referrer);
            }
        }
        
        // Also check by field name pattern
        if (inputName) {
            if (inputName.indexOf('source') !== -1 && source) {
                $field.find('input').val(source);
                if (debug) console.log('Refetrfo Debug: Set source field by name to:', source);
            } else if (inputName.indexOf('medium') !== -1 && medium) {
                $field.find('input').val(medium);
                if (debug) console.log('Refetrfo Debug: Set medium field by name to:', medium);
            } else if (inputName.indexOf('campaign') !== -1 && campaign) {
                $field.find('input').val(campaign);
                if (debug) console.log('Refetrfo Debug: Set campaign field by name to:', campaign);
            } else if (inputName.indexOf('referrer') !== -1 && referrer) {
                $field.find('input').val(referrer);
                if (debug) console.log('Refetrfo Debug: Set referrer field by name to:', referrer);
            }
        }
    });
}

// Document ready function
jQuery(document).ready(function($) {
    // Check debug mode
    var debug = (typeof refetrfoValues !== 'undefined' && refetrfoValues.debug === 'yes');
    
    if (debug) {
        console.log('Refetrfo Debug: Document ready');
        console.log('Refetrfo Debug: refetrfoValues:', refetrfoValues);
        console.log('Refetrfo Debug: URL parameters:');
        console.log('- utm_source:', getUrlParameter('utm_source'));
        console.log('- utm_medium:', getUrlParameter('utm_medium'));
        console.log('- utm_campaign:', getUrlParameter('utm_campaign'));
    }
    
    // Update fields immediately
    updateWPFormsFields();
    
    // Update fields again after a short delay (for dynamically loaded forms)
    setTimeout(function() {
        updateWPFormsFields();
        if (debug) console.log('Refetrfo Debug: Fields updated after 500ms');
    }, 500);
    
    // Update fields periodically for 10 seconds
    var updateCount = 0;
    var updateInterval = setInterval(function() {
        updateWPFormsFields();
        updateCount++;
        if (debug) console.log('Refetrfo Debug: Fields updated in interval', updateCount);
        
        // Stop after 10 seconds (20 updates at 500ms interval)
        if (updateCount >= 20) {
            clearInterval(updateInterval);
            if (debug) console.log('Refetrfo Debug: Stopped periodic updates after 10 seconds');
            
            // Final check for empty fields
            setTimeout(function() {
                // Check if any fields are still empty and try to fill them with URL parameters directly
                jQuery('.wpforms-field-hidden').each(function() {
                    var $input = jQuery(this).find('input');
                    var value = $input.val();
                    var fieldId = jQuery(this).attr('id');
                    var fieldIdNumber = fieldId ? fieldId.replace('wpforms-field-', '') : '';
                    
                    if (!value || value === '') {
                        if (fieldIdNumber === '8') {
                            var directValue = getUrlParameter('utm_source');
                            if (directValue) {
                                $input.val(directValue);
                                if (debug) console.log('Refetrfo Debug: Final update - Set source field to URL param:', directValue);
                            }
                        } else if (fieldIdNumber === '9') {
                            var directValue = getUrlParameter('utm_medium');
                            if (directValue) {
                                $input.val(directValue);
                                if (debug) console.log('Refetrfo Debug: Final update - Set medium field to URL param:', directValue);
                            }
                        } else if (fieldIdNumber === '10') {
                            var directValue = getUrlParameter('utm_campaign');
                            if (directValue) {
                                $input.val(directValue);
                                if (debug) console.log('Refetrfo Debug: Final update - Set campaign field to URL param:', directValue);
                            }
                        }
                    }
                });
            }, 1000);
        }
    }, 500);
    
    // Also update fields when WPForms is initialized
    $(document).on('wpformsReady', function() {
        if (debug) console.log('Refetrfo Debug: WPForms ready event triggered');
        updateWPFormsFields();
    });
    
    // Update fields when form is submitted
    $(document).on('wpformsBeforeSubmit', function() {
        if (debug) console.log('Refetrfo Debug: WPForms before submit event triggered');
        updateWPFormsFields();
    });
});
