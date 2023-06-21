define(['jquery'], function ($) {
    'use strict';

    $.widget('mage.orderedProducts', {
        options: {},

        _create: function () {
            this._bindEvents();
        },

        _bindEvents: function () {

            $('.qty-input').on('keypress', function (event) {
                const keyCode = event.which ? event.which : event.keyCode;
                return (keyCode >= 48 && keyCode <= 57) || keyCode === 8;
            });

            $('.qty-input').on('input', function () {
                let qtyValue = $(this).val();
                $(this).closest('tr').find('.qty-input-hidden').val(qtyValue);
            });

            $('.tocart.btn-cart').on('click', function () {
                let qtyInput = $(this).siblings('.qty-input-hidden');
                let qtyValue = parseInt(qtyInput.val());

                if (qtyValue <= 0 || isNaN(qtyValue)) {
                    alert($.mage.__('Please enter a valid quantity.'));
                    return false;
                }

                $(this).closest('form[data-role="tocart-form"]').submit();
            });
        }
    });

    return $.mage.orderedProducts;
});
