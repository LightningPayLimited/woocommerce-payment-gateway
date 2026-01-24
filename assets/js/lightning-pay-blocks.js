( function() {
    'use strict';

    var registerPaymentMethod = window.wc.wcBlocksRegistry.registerPaymentMethod;
    var createElement = window.wp.element.createElement;
    var decodeEntities = window.wp.htmlEntities.decodeEntities;

    var settings = window.wc.wcSettings.getSetting( 'lightning_pay_data', {} );
    var title = decodeEntities( settings.title || 'Lightning Pay' );
    var description = decodeEntities( settings.description || 'Pay with Bitcoin via the Lightning Network.' );

    var Content = function() {
        return createElement( 'div', null, description );
    };

    var Label = function( props ) {
        return createElement(
            'span',
            null,
            props.components.PaymentMethodLabel
                ? createElement( props.components.PaymentMethodLabel, { text: title } )
                : title
        );
    };

    registerPaymentMethod( {
        name: 'lightning_pay',
        label: createElement( Label, null ),
        content: createElement( Content, null ),
        edit: createElement( Content, null ),
        canMakePayment: function() {
            return true;
        },
        ariaLabel: title,
        supports: {
            features: settings.supports || [ 'products' ],
        },
    } );
} )();
