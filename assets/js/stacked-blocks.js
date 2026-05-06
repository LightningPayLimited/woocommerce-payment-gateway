( function() {
    'use strict';

    var registerPaymentMethod = window.wc.wcBlocksRegistry.registerPaymentMethod;
    var createElement = window.wp.element.createElement;
    var decodeEntities = window.wp.htmlEntities.decodeEntities;

    var settings = window.wc.wcSettings.getSetting( 'stacked_data', {} );
    var title = decodeEntities( settings.title || 'Stacked' );
    var description = decodeEntities( settings.description || 'Pay with Bitcoin via the Lightning Network.' );
    var icon = settings.icon || '';

    var Content = function() {
        return createElement( 'div', null, description );
    };

    var Label = function( props ) {
        var labelChildren = [
            props.components.PaymentMethodLabel
                ? createElement( props.components.PaymentMethodLabel, { key: 'label', text: title } )
                : title
        ];
        if ( icon ) {
            labelChildren.unshift( createElement( 'img', {
                key: 'icon',
                src: icon,
                alt: title,
                style: { maxHeight: '24px', width: 'auto', marginRight: '8px', verticalAlign: 'middle' }
            } ) );
        }
        return createElement(
            'span',
            { style: { display: 'inline-flex', alignItems: 'center' } },
            labelChildren
        );
    };

    registerPaymentMethod( {
        name: 'stacked',
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
