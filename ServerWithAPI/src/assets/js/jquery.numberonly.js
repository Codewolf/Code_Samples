(function ($) {
    var numberOnly = function (element, options) {
        this.element = element;
        this.$element = $(element);
        this.options = options;
        this.group = $(element).closest(".form-element");
    };
    numberOnly.prototype = {
        defaults: {
            regex: "[0-9]+",
            maxChar: 20,
            stripLeadingZero: false,
            useMaxChar: false
        },
        init: function () {
            this.config = $.extend(true, {}, this.defaults, this.options);
            this._addToolTip();
            this._addHandlers();
            this.$element.data("numberOnly", this);
            return this;
        },
        _addToolTip: function () {
            var _this = this;
            if (this.config.useMaxChar) {
                this.$element.tooltip({
                        placement: "bottom",
                        title: "Maximum length reached: " + _this.config.maxChar + " characters.",
                        trigger: "manual",
                        container: 'body'
                    }
                )
            }
        },
        _addHandlers: function () {
            var _this = this;
            this.$element
                .on({
                    keypress: function (e) {
                        var code = (e.which || e.keyCode),
                            char = String.fromCharCode(code);
                        if (code === 13) {
                            char = "\n";
                        }

                        if (_this.config.useMaxChar) {
                            if ((_this.$element.val() + char).length > _this.config.maxChar) {
                                _this.$element.tooltip('show');
                            } else {
                                _this.$element.tooltip('hide');
                            }
                        }

                        if (!char.match(_this.config.regex) || (_this.$element.val().length >= _this.config.maxChar && _this.config.useMaxChar)) {
                            e.preventDefault();
                        }
                    },
                    blur: function () {
                        if (_this.config.stripLeadingZero) {
                            if (slz = _this.$element.val().match("^0(.+)")) {
                                _this.$element.val(slz[1]);
                            }
                        }
                        _this.$element.tooltip('hide');
                    }
                });
        }
    };
    numberOnly.defaults = numberOnly.prototype.defaults;

    $.fn.numberOnly = function (options) {
        var $args = Array.prototype.slice.call(arguments, 1);
        return this.each(function () {
            var plugin = (!$(this).data("numberOnly")) ? new numberOnly(this, options) : $(this).data("numberOnly");
            if (typeof options === 'object' || !options) {
                plugin.init();
            } else if (typeof options === 'string' && typeof numberOnly[options] === 'function' && options.indexOf('_') === -1) {
                plugin[options].apply(plugin, $args);
            } else {
                $.error('Method ' + options + ' does not exist');
            }
        })
    }
}(jQuery));