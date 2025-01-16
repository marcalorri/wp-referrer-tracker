jQuery(document).ready(function($) {
    // Global function to get referrer values
    window.getReferrerValue = function(field) {
        if (typeof wpReferrerTracker === 'undefined') {
            return '';
        }
        
        switch(field) {
            case 'source':
                return wpReferrerTracker.source || '';
            case 'medium':
                return wpReferrerTracker.medium || '';
            case 'campaign':
                return wpReferrerTracker.campaign || '';
            case 'referrer':
                return wpReferrerTracker.referrer || '';
            default:
                return '';
        }
    };

    // Only proceed with auto-insertion if enabled in settings
    if (typeof wpReferrerTracker !== 'undefined' && wpReferrerTracker.settings.autoFields) {
        function addHiddenFields(form) {
            const prefix = wpReferrerTracker.settings.fieldPrefix;
            const fields = {
                [prefix + 'source']: wpReferrerTracker.source,
                [prefix + 'medium']: wpReferrerTracker.medium,
                [prefix + 'campaign']: wpReferrerTracker.campaign,
                [prefix + 'referrer']: wpReferrerTracker.referrer
            };

            for (let [name, value] of Object.entries(fields)) {
                if (value && !form.find(`input[name="${name}"]`).length) {
                    $('<input>').attr({
                        type: 'hidden',
                        name: name,
                        value: value
                    }).appendTo(form);
                }
            }
        }

        // Add fields to all forms on page load
        $('form').each(function() {
            addHiddenFields($(this));
        });

        // Monitor for dynamically added forms
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                const forms = $(mutation.target).find('form');
                if (forms.length) {
                    forms.each(function() {
                        addHiddenFields($(this));
                    });
                }
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
});
