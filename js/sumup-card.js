(function (Drupal, drupalSettings, $) {
    "use strict";
    Drupal.behaviors.sumupCreateCheckout = {
        attach: async function(context, settings) {
            // we need to figure out how to get product details.
            const data = {
                "checkout_reference": "C07464d3",
                "amount": 0,
                "currency": "EUR",
                "merchant_code": "email.com",
                "description": "Testing"
            };

            console.log('do we have an access token? ' + drupalSettings.state_api.access_token);

            // const checkout_id = await fetch('https://api.sumup.com/v0.1/checkouts', {
            //     method: 'POST',
            //     mode: 'cors',
            //     cache: 'no-cache',
            //     credentials: 'same-origin',
            //     headers: {
            //         'Authorization': 'Bearer ' + drupalSettings.state_api.access_token,
            //         'Content-Type': 'application/json'
            //     },
            //     redirect: 'follow',
            //     referrerPolicy: 'no-referrer',
            //     body: JSON.stringify(data)
            // });

            // SumUpCard.mount({
            //     checkoutId: checkout_id,
            //     onResponse: function(type, body) {
            //         console.log('Type', type);
            //         console.log('Body', body);
            //     }
            // });

            SumUpCard.mount({
                checkoutId: '2ceffb63-cbbe-4227-87cf-0409dd191a98',
                onResponse: function(type, body) {
                    console.log('Type', type);
                    console.log('Body', body);
                }
            });
        }
    }
    
}) (Drupal, drupalSettings, jQuery);