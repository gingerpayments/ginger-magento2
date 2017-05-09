define(
    [
        'jquery',
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        $,
        Component,
        rendererList
    ) {
        'use strict';
        var defaultComponent = 'Ginger_Payments/js/view/payment/method-renderer/default';
        var idealComponent = 'Ginger_Payments/js/view/payment/method-renderer/ideal';
        var methods = [
            {type: 'ginger_methods_bancontact', component: defaultComponent},
            {type: 'ginger_methods_banktransfer', component: defaultComponent},
            {type: 'ginger_methods_creditcard', component: defaultComponent},
            {type: 'ginger_methods_ideal', component: idealComponent},
            {type: 'ginger_methods_sofort', component: idealComponent}
        ];
        $.each(methods, function (k, method) {
            rendererList.push(method);
        });
        return Component.extend({});
    }
);
