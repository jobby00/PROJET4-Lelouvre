var prixTotale = $('.price-totale').html();
var adressEmail = $('.email').html();
$(document).ready(function() {
    var handler = StripeCheckout.configure({
        key: 'sk_test_CGUR0LzqpU5EUhIPfAdqatvm',
        image: 'https://stripe.com/img/documentation/checkout/marketplace.png',
        locale: 'auto',
        token: function(token)
        {
            // You can access the token ID with `token.id`. // Get the token ID to your server-side code for use.
            $('.formPart form').submit();
        }
    });
    $('.stripe-button').click(function(e)
    { e.preventDefault(); // Open Checkout with further options:
        handler.open({
            name: 'Musée du Louvre',
            description: 'Billeterie',
            zipCode: true,
            amount: prixTotale*100,
            email: adressEmail,
            billingAddress: true,
            currency: 'EUR',
            allowRememberMe: true });
    });
    window.addEventListener('popstate', function() { handler.close(); });
});
$('.stripe-button-el').addClass('hidden');

$('.stripe-carte').click(function(){
    $('.stripe-button-el').click();
});