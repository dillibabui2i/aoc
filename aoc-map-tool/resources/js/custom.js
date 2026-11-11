(function ($) {
    $.fn.usPhoneFormat = function (options) {
        const params = $.extend({
            format: 'xxx-xxx-xxxx',
            international: false,
        }, options);
        const formatFunctions = {
            'xxx-xxx-xxxx': formatXXX,
            '(xxx) xxx-xxxx': formatParentheses
        };
        if (formatFunctions.hasOwnProperty(params.format)) {
            formatFunctions[params.format].call(this);
        }
        function formatXXX() {
            handleFormat.call(this, 12, /(\d{3})(\d{3})(\d{4})/, "$1-$2-$3", "-");
        }
        function formatParentheses() {
            handleFormat.call(this, 14, /(\d{3})(\d{3})(\d{4})/, "($1) $2-$3", "-");
        }
        function handleFormat(maxLength, regexPattern, replacePattern, separator) {
            $(this).on('input', function (e) {
                if (!isValidInput(e)) return;
                let curval = $(this).val().replace(/\D/g, '');
                if (curval.length > maxLength) {
                    curval = curval.substring(0, maxLength);
                }
                const formattedValue = curval.replace(regexPattern, replacePattern);
                $(this).val(formattedValue);
            });

            $(this).attr('maxlength', maxLength);
        }
        function isValidInput(e) {
            return e.which === 8 || e.which === 0 || e.which === 13 || (e.which >= 48 && e.which <= 57);
        }
    }
}(jQuery));
