(function ($) {
    'use strict';
    $(document).ready(function () {
        // Ignore clicks on hash tags
        $("body").on({
            click: function (e) {
                e.preventDefault()
            }
        }, 'a[href^="#"]');

        $.fn.handleNoty = function (_msg, _type, _timeout, _killer) {
            var _ty = (_type === undefined) ? 'error' : _type,
                _tOut = (_timeout === undefined) ? 5000 : _timeout,
                _kill = (_killer === undefined) ? true : _killer;

            new Noty({
                text: _msg,
                theme: 'bootstrap-v3',
                layout: 'top',
                progressBar: true,
                type: _ty,
                timeout: _tOut,
                killer: _kill,
                queue: 'global',
                container: false
            }).show();
        };
    });
})(jQuery);
