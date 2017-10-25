$.fn.serializeObject = function () {
    var data = {};
    $.each(this.serializeArray(), function (key, obj) {
        var a = obj.name.match(/(.*?)\[(.*?)\]/);
        if (a !== null) {
            var subName = new String(a[1]);
            var subKey = new String(a[2]);
            if (!data[subName]) {
                data[subName] = {};
                // data[subName].length = 0;
            }
            if (!subKey.length) {
                subKey = data[subName].length;
            }
            if (data[subName][subKey]) {
                if ($.isArray(data[subName][subKey])) {
                    data[subName][subKey].push(obj.value);
                } else {
                    data[subName][subKey] = [data[subName][subKey]];
                    data[subName][subKey].push(obj.value);
                }
            } else {
                data[subName][subKey] = obj.value;
            }
            // data[subName].length++;
        } else {
            var keyName = new String(obj.name);
            if (data[keyName]) {
                if ($.isArray(data[keyName])) {
                    data[keyName].push(obj.value);
                } else {
                    data[keyName] = [data[keyName]];
                    data[keyName].push(obj.value);
                }
            } else {
                data[keyName] = obj.value;
            }
        }
    });
    return data;
};
var SettingsStorage = {
        _setCookie: function (cname, cvalue, exdays) {
            if (exdays) {
                var d = new Date();
                d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
                var expires = "expires=" + d.toUTCString();
                document.cookie = cname + "=" + cvalue + ";"
            } else {
                document.cookie = cname + "=" + cvalue + ";path=/";
            }

        },
        _getCookie: function (cname) {
            var name = cname + "=";
            var ca = document.cookie.split(';');
            for (var i = 0; i < ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0) === ' ') {
                    c = c.substring(1)
                }
                if (c.indexOf(name) === 0) {
                    return decodeURIComponent(c.substring(name.length, c.length));
                }
            }
            return "";
        },
        readAllSettings: function () {
            var _store;
            try {
                if (window.localStorage) {
                    _store = JSON.parse(localStorage.getItem('LicencingSettingsObject'));
                } else {
                    _store = JSON.parse(this._getCookie('LicencingSettingsObject'));
                }
            } catch (e) {
                console.log(e);
                _store = {};
            }
            return _store || {};
        },
        saveSetting: function (key, value) {
            var _settings;
            _settings = this.readAllSettings();
            _settings[key] = value;
            if (window.localStorage) {
                localStorage.setItem('LicencingSettingsObject', JSON.stringify(_settings));
            } else {

                this._setCookie('LicencingSettingsObject', JSON.stringify(_settings));
            }
        }
    },
    t,
    select2Methods = {
        formatResult: function (result) {
            if (result.loading) {
                return result.text
            }
            return "<div class='select2-result clearfix'>" + (result.name) + "</div>";
        },
        formatSelection: function (result) {
            return result.name || result.text
        }
    },
    Base = function () {
        var AppSettings = SettingsStorage.readAllSettings();
        return {
            init: function () {
                this.handleSidebar();
                this.handleNavigation();
                this.handleNavbar();
                this.handlePlugins();
            },
            defaults: {
                niceScroll: {
                    cursorwidth: '5px',
                    cursorborder: '0px',
                    railalign: 'left',
                    cursoropacitymin: 1,
                    cursorcolor: "#FFF"
                },
                select2: {
                    options: {
                        placeholder: "",
                        allowClear: true,
                        ajax: {
                            url: "",
                            delay: 300,
                            type: "POST",
                            dataType: 'json',
                            data: function (params) {
                                return {
                                    q: params.term,
                                    page: params.page,
                                    type: ""
                                }
                            },
                            processResults: function (data, params) {
                                params.page = params.page || 1;
                                return {
                                    results: data.items,
                                    pagination: {
                                        more: (params.page * 30) < data.total_count
                                    }
                                }
                            }
                        },
                        escapeMarkup: function (markup) {
                            return markup;
                        },
                        minimumInputLength: 1,
                        templateResult: select2Methods.formatResult,
                        templateSelection: select2Methods.formatSelection
                    },
                    methods: {}
                },
                datepicker: {
                    defaultDate: "+0",
                    changeMonth: true,
                    changeYear: true,
                    minDate: -0,
                    altFormat: 'yy-mm-dd',
                    dateFormat: 'dd M yy'
                }
            },
            functions: {
                nl2br: function (str, isXhtml) {
                    if (typeof str === 'undefined' || str === null) {
                        return ''
                    }
                    // Adjust comment to avoid issue on locutus.io display
                    var breakTag = (isXhtml || typeof isXhtml === 'undefined') ? '<br ' + '/>' : '<br>';
                    return (str + '').replace(/(\r\n|\n\r|\r|\n)/g, breakTag + '$1')
                },
                debounce: function (callback, ms) {
                    if (!ms) {
                        ms = 1000
                    }
                    clearInterval(t);  //clear any interval on key up
                    t = setTimeout(callback, ms);
                }
            },
            handlePlugins: function () {
                $('.input-group-addon:not(.select-addon)').on({
                    click: function () {
                        $(this).closest('.input-group').find("input").focus().trigger('focus');
                    }
                });
                $('[data-toggle=tooltip]').tooltip({
                    animation: 'fade',
                    trigger: 'hover',
                    container: 'body'
                });
                $('[data-toggle=popover]').popover({
                    animation: 'fade',
                    trigger: 'hover',
                    container: 'body'
                });
                autosize($('textarea'));
            },
            handleSidebar: function () {
                function checkHeight() {
                    var heightSidebarLeft = $(window).outerHeight() - $('#header').outerHeight() - $('#sidebar-left .media').outerHeight();
                    $('#sidebar-left .sidebar-menu').height(heightSidebarLeft).niceScroll($.extend({}, Base.defaults.niceScroll, {horizrailenabled: false}));
                }

                checkHeight();
                // Bind event listener
                $(window).resize(checkHeight);
            },
            handleNavigation: function () {
                // Create trigger click for open menu sidebar
                $('.submenu > a').click(function () {
                    var parentElement = $(this).parent('.submenu'),
                        nextElement = $(this).nextAll(),
                        arrowIcon = $(this).find('.arrow'),
                        plusIcon = $(this).find('.plus');
                    if (parentElement.parent('ul').find('ul:visible')) {
                        parentElement.parent('ul').find('ul:visible').slideUp('fast');
                        parentElement.parent('ul').find('.open').removeClass('open');
                    }

                    if (nextElement.is('ul:visible')) {
                        arrowIcon.removeClass('open');
                        plusIcon.removeClass('open');
                        nextElement.slideUp('fast');
                        arrowIcon.removeClass('fa-angle-double-down').addClass('fa-angle-double-right');
                    }

                    if (!nextElement.is('ul:visible')) {
                        arrowIcon.addClass('open');
                        plusIcon.addClass('open');
                        nextElement.slideDown('fast');
                        arrowIcon.removeClass('fa-angle-double-right').addClass('fa-angle-double-down');
                    }

                });
            },
            handleNavbar: function () {
                if (AppSettings.sidebarMinimized === undefined || AppSettings.sidebarMinimized === null) {
                    SettingsStorage.saveSetting('sidebarMinimized', 0);
                    $('#call-notifocations-toggle').prop("checked", true);
                } else {
                    if (AppSettings.sidebarMinimized === 1) {
                        $('body').addClass('page-sidebar-minimize');
                    } else {
                        $('body').removeClass('page-sidebar-minimize');
                    }
                }
                $('.navbar-minimize a').on({
                    click: function () {
                        SettingsStorage.saveSetting('sidebarMinimized', ((AppSettings.sidebarMinimized === 1) ? 0 : 1));
                        $('body').toggleClass('page-sidebar-minimize');
                    }
                });
            }
        };
    }();
$(document).ready(function () {
    Base.init();
});