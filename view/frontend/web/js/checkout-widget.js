define([
    'jquery',
    'uiComponent',
    'ko',
    'mage/url',
    'DeunaCheckout',
], function ($, Component, ko, Url, DunaCheckout) {
    'use strict';
    window.DeunaCDL = DeunaCDL
    return Component.extend({
        defaults: {
            template: 'Deuna_Checkout/widget',
            dunaCheckout: DunaCheckout(),
            hasEnable: ko.observable(true)
        },
        initialize: function () {
            this._super();
        },
        configure: function (data) {
            const obj = JSON.parse(data);
            this.dunaCheckout.configure({
                apiKey: this.apiKey,
                env: this.env,
                orderToken: obj.orderToken
            });
        },
        show: function () {
            const self = this,
                  tokenUrl = Url.build('rest/v1/deuna/token');
            this.preventClick();
            $.ajax({
                method: 'GET',
                url: tokenUrl
            })
            .done(function (data) {
                self.configure(data);
                self.dunaCheckout.show();
            });
        },
        preventClick: function () {
            const self = this;
            this.hasEnable(false);
            setTimeout(function () {
                self.hasEnable(true);
            }, 4000)
        }
    });
});
