(function (Drupal, drupalSettings, $) {
    "use strict";
    Drupal.Behaviours.sumupCreateCheckout = {
        attach: async function(context, settings) {
            
            const data = {
                "checkout_reference": "C07464d3",
                "amount": 0,
                "currency": "EUR",
                "pay_to_email": "email.com",
                "description": "Testing"
            };

            console.log('do we have an access token? ' + drupalSettings.state_api.access_token);

            const checkout_id = await fetch('https://api.sumup.com/v0.1/checkouts', {
                method: 'POST',
                mode: 'cors',
                cache: 'no-cache',
                credentials: 'same-origin',
                headers: {
                    'Authorization': 'Bearer ' + drupalSettings.state_api.access_token,
                    'Content-Type': 'application/json'
                },
                redirect: 'follow',
                referrerPolicy: 'no-referrer',
                body: JSON.stringify(data)
            });

            SumUpCard.mount({
                checkoutId: checkout_id,
                onResponse: function(type, body) {
                    console.log('Type', type);
                    console.log('Body', body);
                }
            });
        }
    }
    
}) (Drupal, drupalSettings, jQuery);