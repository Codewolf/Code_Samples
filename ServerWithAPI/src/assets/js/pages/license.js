var License = function () {
    return {
        init: function () {
            this.setupHandlers();
            this.validation();
        },
        formatSelect2State: function (state) {
            if (!state.id) {
                return state.text;
            }
            var _data = $(state.element).data();
            return $('<div>' + state.text + '<span class="managed-by">Managed By: ' + _data.managedBy + '</span></div>');
        },
        processResult: function (type, _data) {
            function loadDetails() {
                // Reset Fields.
                $('#referringIP,#referringDomain,#activation-date,#activation-date-iso,#expiry-date,#expiry-date-iso').val('');
                $(".module-activate").each(function () {
                    $(this).prop('checked', false);
                });

                //Repopulate Fields.
                if (!_data) {
                    _data = {};
                    _data.license = {};
                    _data.license.creation_date = '';
                    _data.license.activation_date = '';
                    _data.license.expiry_date = '';
                    _data.license.modules_names = [];
                    _data.license.ip_addr = [];
                    _data.license.domains = [];
                }
                $("#license-created-date").text(_data.license.creation_date);
                $("#license-activation-date").text(_data.license.activation_date);
                $("#license-expiry-date").text(_data.license.expiry_date);
                var _modules = '', _ip = '', _domain = '';
                for (var i in _data.license.modules_names) {
                    _modules += _data.license.modules_names[i] + "<br>";
                    $(".module-activate[value='" + _data.license.modules_installed[i] + "']").prop("checked", true);
                }
                for (var j in _data.license.ip_addr) {
                    _ip += _data.license.ip_addr[j] + "\n";
                }
                for (var k in _data.license.domains) {
                    _domain += _data.license.domains[k] + "\n";
                }
                $("#license-modules-installed").html(_modules);
                $('#referringIP').val(_ip.trim());
                $('#referringDomain').val(_domain.trim());
            }

            var icon, message, $details = $("#licenseDetails");
            switch (type) {
                case "danger":
                    icon = 'times-circle';
                    message = 'This Client has an expired license.';
                    loadDetails(_data);
                    $details.show();
                    $details.addClass('fadeIn');
                    break;

                case "warning":
                    icon = 'exclamation-circle';
                    message = 'This Client has an active license.';
                    loadDetails(_data);
                    $details.show();
                    $details.addClass('fadeIn');
                    break;

                case "success":
                default:
                    icon = 'check-circle';
                    message = 'This Client does not yet have a license.';
                    $details.removeClass('fadeIn').hide();
                    loadDetails();
                    break;
            }
            var _html = '<i class="fa fa-' + icon + ' pr-10"></i>' + message;
            $("#licenseStatus").html(_html).show().addClass("fadeIn alert-" + type);
            $("#createLicense").show();
        },
        setupHandlers: function () {
            this.generationHandlers();
            this.historyHandlers();
        },
        validation: function () {
            $.validator.setDefaults({ignore: ":hidden"});
            $.validator.addMethod("requiredgroup", function () {
                return $('.module-activate:checked').length > 0
            }, function () {
                return "At Least One Module Must be chosen"
            });
            $("#generation-form").validate({
                errorPlacement: function (error, element) {
                    $(error).addClass('text-danger');
                    error.appendTo(element.closest(".form-group"));
                },
                rules: {
                    "referringIP": {required: "#referringDomain:blank"},
                    "referringDomain": {required: "#referringIP:blank"},
                    "activation-date": "required",
                    "expiry-date": "required",
                    "moduleActive[]": "requiredgroup"
                },
                onkeyup: false,
                messages: {},
                invalidHandler: function (_event, _validator) {
                    // Add effect animation css
                    var _errors = _validator.numberOfInvalids();
                    $('.login-wrapper').addClass('animated shake');
                    if (_errors) {
                        $(document).handleNoty('You have an error in <b>' + _errors + '</b> field' + ((_errors === 1) ? '. It has ' : 's. They have ') + 'been highlighted below');
                    }
                    setTimeout(function () {
                        $('.login-wrapper').removeClass('animated shake')
                    }, 1500);
                },
                highlight: function (_element) {
                    $(_element).parents('.form-group').addClass('has-error has-feedback');
                    $(_element).parents('.form-group').find('label').addClass('text-danger');
                },
                unhighlight: function (_element) {
                    $(_element).parents('.form-group').removeClass('has-error');
                    $(_element).parents('.form-group').find('label').removeClass('text-danger');
                }
            })
        },
        generationHandlers: function () {
            function getDate(element) {
                var date;
                try {
                    date = $.datepicker.parseDate('dd M yy', element.value);
                } catch (e) {
                    console.error(e);
                    date = null;
                }
                return date;
            }

            $("#clients").select2({
                allowClear: true,
                placeholder: 'Client...',
                minimumInputLength: 0,
                templateResult: License.formatSelect2State
            }).on({
                "select2:select": function () {
                    var _client = $("#clients").val();
                    $(".license-display-panel").hide().find("#license-key").text('');
                    if (_client !== '') {
                        $.ajax({
                            type: "POST",
                            dataType: 'json',
                            url: "/includes/ajax/",
                            data: {
                                endpoint: "License",
                                type: "fetchExistingLicenses",
                                clientId: $("#clients :selected").data('client-id')
                            }
                        })
                            .done(function (data) {
                                if (data.license) {
                                    if (data.license.active) {
                                        License.processResult('warning', data)
                                    } else {
                                        License.processResult('danger', data)
                                    }
                                } else {
                                    License.processResult('success');
                                }
                            })
                            .fail(function (xhr) {
                                $(document).handleNoty(xhr.responseJSON.error);
                                console.error("[ERROR] [" + xhr.status + "]: " + xhr.responseJSON.error);
                            });
                    }
                },
                "select2:close": function () {
                    if ($("#clients").val() === '') {
                        $(".license-display-panel").hide().find("#license-key").text('');
                        $("#createLicense").hide().removeClass("fadeIn");
                        $("#licenseDetails").hide();
                        $("#licenseStatus").removeClass('alert-success alert-warning alert-danger fadeIn').html('').hide();
                        $(".generation-panel").hide();
                    }
                }
            });

            $("#referringIP").numberOnly({regex: "[0-9\n\.]+"});

            // Datepickers.
            $("#activation-date").datepicker($.extend({}, Base.defaults.datepicker, {
                altField: "#activation-date-iso"
            })).on("change", function () {
                $to.datepicker("option", "minDate", getDate(this));
            });
            var $to = $("#expiry-date").datepicker($.extend({}, Base.defaults.datepicker, {
                altField: "#expiry-date-iso",
                defaultDate: "+1y"
            }));

            // Buttons.
            $("#createLicense").on({
                click: function () {
                    $(this).hide();
                    $("#clients").prop('disabled', true);
                    $(".generation-panel").show();
                    autosize.update($('textarea'));
                }
            });
            $("#emailLicense").on({
                click: function () {
                    var $self = $(this), _content = $self.html();
                    try {
                        $self.html('Please Wait...').prop("disabled", true);
                        $.ajax({
                            type: "POST",
                            dataType: 'json',
                            url: "/includes/ajax/",
                            data: {
                                endpoint: "License",
                                type: "emailLicenseToClient",
                                clientId: $("#clients").val()
                            }
                        })
                            .done(function (data) {
                                $self.html(_content).prop('disabled', false);
                            })
                            .fail(function (xhr) {
                                $(document).handleNoty(xhr.responseJSON.error);
                                console.error("[ERROR] [" + xhr.status + "]: " + xhr.responseJSON.error);
                                $self.html(_content).prop('disabled', false)
                            });
                    } catch (e) {
                        console.error(e);
                        $self.html(_content).prop('disabled', false)
                    }
                }
            });
            $("#generateLicense").on({
                click: function () {
                    var $self = $(this), _content = $self.html();
                    try {
                        if ($("#generation-form").valid()) {

                            var _modules = [];
                            $(".module-activate:checked").each(function () {
                                _modules.push($(this).val())
                            });

                            $self.html('Please Wait...').prop("disabled", true);
                            $.ajax({
                                type: "POST",
                                dataType: 'json',
                                url: "/includes/ajax/",
                                data: {
                                    endpoint: "License",
                                    type: "generateLicenseKeys",
                                    clientId: $("#clients :selected").data('client-id'),
                                    ips: $("#referringIP").val(),
                                    domains: $("#referringDomain").val(),
                                    activation: $("#activation-date-iso").val(),
                                    expiry: $("#expiry-date-iso").val(),
                                    modules: _modules
                                }
                            })
                                .done(function (data) {
                                    $self.html(_content).prop('disabled', false);
                                    $(".generation-panel").hide();
                                    $(".license-display-panel").show().find("#license-key").html(Base.functions.nl2br(data.license));
                                    $("#clients").prop('disabled', false);
                                    autosize.update($('textarea'));
                                })
                                .fail(function (xhr) {
                                    $(document).handleNoty(xhr.responseJSON.error);
                                    console.error("[ERROR] [" + xhr.status + "]: " + xhr.responseJSON.error);
                                    $self.html(_content).prop('disabled', false)
                                });
                        }
                    } catch (e) {
                        console.error(e);
                        $self.html(_content).prop('disabled', false)
                    }
                }
            });
        },
        historyHandlers: function () {
            var _self = this, _dt;
            $("#clientHistory").select2({
                allowClear: true,
                placeholder: 'Client...',
                minimumInputLength: 0,
                templateResult: License.formatSelect2State
            }).on({
                "select2:select": function () {
                    var _client = $("#clientHistory").val();
                    $(".license-display-panel").hide().find("#license-key").text('');
                    if (_client !== '') {
                        if (_dt) {
                            _dt.destroy();
                        }
                        _dt = _self._setupDataTable();
                    }
                },
                "select2:close": function () {
                    if ($("#clientHistory").val() === '') {
                        $("#licenseHistory").removeClass('fadeIn').hide().find("tbody").empty();
                        _dt.destroy();
                        _dt = null;
                    }
                }
            });
            $("#licenseHistory")
                .on({
                    click: function () {
                        var $self = $(this), _content = $self.html(), _data = $(this).closest("tr").data();
                        try {
                            $self.html('Please Wait...').prop("disabled", true);
                            $.ajax({
                                type: "POST",
                                dataType: 'json',
                                url: "/includes/ajax/",
                                data: {
                                    endpoint: "License",
                                    type: "fetchLicenseKey",
                                    kid: _data.id
                                }
                            })
                                .done(function (data) {
                                    $self.html(_content).prop('disabled', false);
                                    var uriContent = "data:application/octet-stream," + encodeURIComponent(data.license),
                                        $link = $('<a id="dlf" href="' + uriContent + '" download="license.dlf">');
                                    $self.closest('td').append($link);
                                    document.getElementById('dlf').click();
                                    $('#dlf').remove();
                                })
                                .fail(function (xhr) {
                                    $(document).handleNoty(xhr.responseJSON.error);
                                    console.error("[ERROR] [" + xhr.status + "]: " + xhr.responseJSON.error);
                                    $self.html(_content).prop('disabled', false)
                                });
                        } catch (e) {
                            console.error(e);
                            $self.html(_content).prop('disabled', false)
                        }
                    }
                }, '.show-license-key')
                .on({
                    click: function () {
                        var $self = $(this), _content = $self.html();
                        try {
                            $self.html('Please Wait...').prop("disabled", true);
                            $.ajax({
                                type: "POST",
                                dataType: 'json',
                                url: "/includes/ajax/",
                                data: {
                                    endpoint: "License",
                                    type: "emailLicenseToClient",
                                    clientId: $("#clientHistory").val()
                                }
                            })
                                .done(function (data) {
                                    $self.html(_content).prop('disabled', false);
                                })
                                .fail(function (xhr) {
                                    $(document).handleNoty(xhr.responseJSON.error);
                                    console.error("[ERROR] [" + xhr.status + "]: " + xhr.responseJSON.error);
                                    $self.html(_content).prop('disabled', false)
                                });
                        } catch (e) {
                            console.error(e);
                            $self.html(_content).prop('disabled', false)
                        }
                    }
                }, '.email-license-key');
        },
        _setupDataTable: function () {
            return $("#licenseHistory").DataTable({
                autoWidth: false,
                ajax: {
                    "url": "/includes/ajax/",
                    "type": "POST",
                    "dataSrc": "licenses",
                    "data": function (d) {
                        d.endpoint = "License";
                        d.type = "fetchLicenseHistory";
                        d.clientId = $("#clientHistory :selected").data('client-id');
                    }
                },
                "pageLength": 25,
                "lengthMenu": [[10, 25, 50, 250, -1], [10, 25, 50, 250, "All"]],
                "sDom": "<'row'<'col-md-6 col-xs-12 'l><'col-md-6 col-xs-12'f>r>t<'row'<'col-md-4 col-xs-12'i><'col-md-8 col-xs-12'p>>",
                "order": [[1, "desc"]],
                "columns": [
                    {
                        "data": "creation_date"
                    },
                    {
                        "data": "creation_date_iso"
                    },
                    {
                        "data": "activation_date"
                    },
                    {
                        "data": "activation_date_iso"
                    },
                    {
                        "data": "expiry_date"
                    },
                    {
                        "data": "expiry_date_iso"
                    },
                    {
                        "data": "modules_names[<br>]"
                    },
                    {
                        "data": "ip_addr[<br>]"
                    },
                    {
                        "data": "domains[<br>]"
                    },
                    {
                        "data": null,
                        "defaultContent": "<button type='button' class='btn btn-default btn-sm show-license-key'><i class='fa fa-eye pr-10'></i>Download License Key</button>"
                    }
                ],
                "columnDefs": [
                    {
                        "targets": [-1, -2, -3, -4],
                        "orderable": false
                    },
                    {
                        "targets": [1, 3, 5],
                        "visible": false,
                        "searchable": true
                    },
                    {"orderData": [1, 3, 5], "targets": [0, 2, 4]}
                ],
                "initComplete": function () {
                    $("#licenseHistory").show().addClass("fadeIn");
                },
                "language": {
                    emptyTable: '<p class="text-center p-all-20"> -- No Licenses Available For Client -- </p>',
                    zeroRecords: '<p class="text-center p-all-20"> -- No Matching Licenses Found -- </p>',
                    search: "_INPUT_",
                    searchPlaceholder: "Search"
                },
                "createdRow": function (row, data) {
                    if (data.active === false) {
                        $(row).addClass("inactive-license").find('.show-license-key').remove()
                    }
                    if (data.isLatest) {
                        $(row).addClass("currently-active-license").find("td:eq(6)").append("<br><button type='button' class='btn btn-default btn-sm email-license-key mt-10'><i class='fa fa-envelope-o pr-10'></i>Email License Key To Client</button>");
                    }
                    $(row).data(data);
                }
            });
        }
    };
}();
$(document).ready(function () {
    License.init();
});