define([
    'jquery',
    'uiComponent',
    'mage/url',
    'DeunaCDL',
    'DunaCheckout',
], function ($, Component, Url, DeunaCDL, DunaCheckout) {
    'use strict';
    return Component.extend({
        defaults: {
            template: 'DUna_Payments/widget',
            dunaCheckout: DunaCheckout(),
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
                  tokenUrl = Url.build('rest/V1/DUna/token');
            $.ajax({
                method: 'GET',
                url: tokenUrl
            })
            .done(function(data) {
                self.configure(data);
                self.dunaCheckout.show();
            });
        }
    });
});
