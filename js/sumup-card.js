(function (Drupal, $) {
    "use strict";
    Drupal.Behaviours.sumupCreateCheckout = {
        attach: async function(context, settings) {
            
            const data = {
                "checkout_reference": "C07464d3",
                "amount": 1,
                "currency": "EUR",
                "pay_to_email": "email.com",
                "description": "Test iptv line"
            };

            const checkout_id = await fetch('https://api.sumup.com/v0.1/checkouts', {
                method: 'POST',
                mode: 'cors',
                cache: 'no-cache',
                credentials: 'same-origin',
                headers: {
                    'Authorization': 'Bearer 565e2d19cef68203170ddadb952141326d14e03f4ccbd46daa079c26c910a864',
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
    
}) (Drupal, jQuery);