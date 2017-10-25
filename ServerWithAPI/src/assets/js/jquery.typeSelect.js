
/*Input Group Select*/
(function ($) {
    var typeSelect = function (element, options) {
        this.element = element;
        this.$element = $(element);
        this.options = options;
    };
    typeSelect.prototype = {
        defaults: {
            optionsHTML: [{}],
            className: '.type-select',
            inputGroupClassName: '.input-group-addon',
            title: 'Click To Change Type',
            align: 'left',
            containerClassName: '.form-group'
        },
        init: function () {
            this.config = $.extend(true, {}, this.defaults, this.options);
            this._addHandlers();
            this.$element.data("typeSelect", this);
            return this;
        },
        _addHandlers: function () {
            $(this.config.inputGroupClassName, this.$element).prop("title", this.config.title);
            $(this.config.containerClassName, this.$element).css({"position": "relative", "margin-bottom": "15px"});
            var _self = this;

            $(document).off("click.typeSelect").on({
                "click.typeSelect": function (e) {
                    var container = $(_self.config.inputGroupClassName + ',' + _self.config.className);
                    if (!container.is(e.target) && container.has(e.target).length === 0) {
                        $(_self.config.className).remove();
                        $(_self.$element).trigger('ts-closed');
                    }
                }
            });
            this.$element
                .on({
                    click: function () {
                        var _offset = ($('> *', this).data('offset') * 34 || 0);
                        _offset += $('> *', this).data('offset');
                        var $select = $('<div class="' + (_self.config.className).replace(/^[\.#]/, '') + '" style="top:-' + _offset + 'px; ' + _self.config.align + ':0"></div>');
                        $.each(_self.config.optionsHTML, function (index, html) {
                            var $html;
                            if (html instanceof Object) {
                                $html = $(html.html).addClass((_self.config.className).replace(/^[\.#]/, '') + '-option').data('offset', index);
                                $.each(html, function (attr, val) {
                                    $html.prop(attr, val);
                                })
                            } else {
                                $html = $(html).addClass((_self.config.className).replace(/^[\.#]/, '') + '-option').data('offset', index);
                            }

                            $select.append($html);

                        });
                        $(this).closest(_self.config.containerClassName).append($select);
                        $(_self.$element).trigger('ts-opened');
                    }
                }, _self.config.inputGroupClassName)
                .on({
                    click: function (e) {
                        $(this).closest(_self.config.containerClassName).find(_self.config.inputGroupClassName).html(e.target);
                        $(_self.$element).trigger('ts-selected', [e.target]);
                        $(_self.config.className).remove();
                    }
                }, _self.config.className + ' > *');
        }
    };
    typeSelect.defaults = typeSelect.prototype.defaults;

    $.fn.typeSelect = function (options) {
        var $args = Array.prototype.slice.call(arguments, 1);
        return this.each(function () {
            var plugin = ($(this).data("typeSelect")) ? $(this).data("typeSelect") : new typeSelect(this, options);
            if (typeof options === 'object' || !options) {
                plugin.init();
            } else if (typeof options === 'string' && typeof typeSelect[options] === 'function' && options.indexOf('_') === -1) {
                plugin[options].apply(plugin, $args);
            } else {
                $.error('Method ' + options + ' does not exist');
            }
        })
    }

}(jQuery));