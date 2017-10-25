var emailRegex = /^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/,
    faTypeIcons = [
        'fa-phone',
        'fa-mobile',
        'fa-fax',
        'fa-at'
    ],
    Client = function () {
        return {
            init: function () {
                this.setupHandlers();
            },
            formatSelect2State: function (state) {
                if (!state.id) {
                    return state.text;
                }
                var _data = $(state.element).data();
                return $('<div>' + state.text + '<span class="managed-by">Managed By: ' + _data.managedBy + '</span></div>');
            },
            setupHandlers: function () {
                this._newClientHandlers();
                this._clientListHandlers();
            },
            validation: function () {
                $.validator.addMethod("customValidation", function (value, element) {
                    return $(element).data('valid') || $(element).is("[readonly]") || false;
                });
                $.validator.setDefaults({ignore: ":hidden:not(.select2-hidden-accessible)"});
                $.validator.addMethod("postcodeUK", function (value, element) {
                    if ($('#client-country').val() == 230) {
                        return this.optional(element) || /^((([A-PR-UWYZ][0-9])|([A-PR-UWYZ][0-9][0-9])|([A-PR-UWYZ][A-HK-Y][0-9])|([A-PR-UWYZ][A-HK-Y][0-9][0-9])|([A-PR-UWYZ][0-9][A-HJKSTUW])|([A-PR-UWYZ][A-HK-Y][0-9][ABEHMNPRVWXY]))\s?([0-9][ABD-HJLNP-UW-Z]{2})|(GIR)\s?(0AA))$/i.test(value);
                    } else {
                        return true;
                    }
                }, "Please specify a valid UK postcode");
                return $("#client-form").validate({
                    errorPlacement: function (error, element) {
                        $(error).addClass('text-danger');
                        if (element.parent().hasClass('input-group')) {
                            error.appendTo(element.parent().parent());
                        } else {
                            error.appendTo(element.parent());
                        }

                    },
                    rules: {
                        "client-name": "required",
                        "client-email": {"required": true, "email": true, customValidation: true},
                        "client-address": "required",
                        "client-town": "required",
                        "client-postcode": {"required": true, "postcodeUK": true},
                        "client-manager": "required"
                    },
                    onkeyup: false,
                    messages: {},
                    invalidHandler: function (_event, _validator) {
                        // Add effect animation css
                        var _errors = _validator.numberOfInvalids();
                        if (_errors) {
                            $(document).handleNoty('You have an error in <b>' + _errors + '</b> field' + ((_errors === 1) ? '. It has ' : 's. They have ') + 'been highlighted below');
                        }
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
            checkClientEmail: function () {
                $("#client-email").closest('.form-group').addClass('has-feedback has-warning')
                    .find('.email-label').removeClass('text-success text-danger').addClass('text-warning');
                $.ajax({
                    type: "POST",
                    dataType: 'json',
                    url: "/includes/ajax/",
                    data: {
                        endpoint: "Client",
                        type: "checkClientEmail",
                        email: $("#client-email").val()
                    }
                })
                    .done(function (data) {
                        if (data.free) {
                            $("#client-email")
                                .data('valid', true)
                                .closest('.form-group').removeClass('has-warning has-error').addClass('has-success')
                                .find('.email-label').removeClass('text-warning text-danger').addClass('text-success').end()
                                .find('label.freeEmail').remove();
                        } else {
                            $("#client-email")
                                .data('valid', false)
                                .closest('.form-group').removeClass('has-warning has-success').addClass('has-error')
                                .find('.email-label').removeClass('text-warning text-success').addClass('text-danger').end()
                                .find('label.freeEmail').remove().end().append('<label class="error freeEmail text-danger" style="display: block;" for="client-email">That Email Is already in use by another client.</label>');
                        }
                    })
                    .fail(function (xhr) {
                        $(document).handleNoty(xhr.responseJSON.error);
                        console.error("[ERROR] [" + xhr.status + "]: " + xhr.responseJSON.error);
                    });
            },
            _newClientHandlers: function () {
                var validationForm = this.validation();
                var _class = this;
                $("#client-email").on({
                    change: function () {
                        $("#client-email").closest('.form-group').removeClass('has-warning has-success has-error');
                        if (emailRegex.test($(this).val())) {
                            _class.checkClientEmail();
                        }
                    },
                    "keyup": function () {
                        var _self = this;
                        Base.functions.debounce(function () {
                            $("#client-email").closest('.form-group').removeClass('has-warning has-success has-error');
                            if (emailRegex.test($(_self).val())) {
                                _class.checkClientEmail();
                            }
                        }, 2000)
                    }
                });
                $('.contact-name,.contact-relation').each(function () {
                    $(this).rules('add', "required");
                });
                $('.contact-details').each(function () {
                    $(this).rules('add', {"required": true, "email": true});
                });
                var _contactHTML = '<div class="contact-row form-group" data-row="{row}">' +
                        '<div class="col-xs-12 col-sm-3">' +
                        '<input type="text" name="contact-name[{row}]" class="form-control contact-name" placeholder="Contact Name">' +
                        '</div>' +
                        '<div class="col-xs-12 col-sm-3">' +
                        '<input type="text" name="contact-relation[{row}]" class="form-control contact-relation" placeholder="Relation to Client/Job Title">' +
                        '</div>' +
                        '<div class="col-xs-12 col-sm-3">' +
                        '<div class="contact-container">' +
                        '<div class="contact-details-row form-group" data-row="0">' +
                        '<input type="hidden" name="contact-details-id[0][0]" class="contact-details-id" value="">' +
                        '<i class="fa fa-times remove-details pl-5"></i>' +
                        '<div class="input-group">' +
                        '<span class="input-group-addon select-addon"><i class="fa fa-at" data-type="3" data-offset="3"></i></span>' +
                        '<input type="text" name="contact-details[{row}][0]" class="form-control contact-details" placeholder="Contact Details">' +
                        '<input name="contact-details-type[{row}][0]" class="contact-type" type="hidden" value="3">' +
                        '</div>' +
                        '</div>' +
                        '</div>' +
                        '<button type="button" class="btn btn-success contact-details-add mb-15"><i class="fa fa-plus"></i> Add Contact Details</button>' +
                        '</div>' +
                        '<i class="fa fa-trash remove-contact" title="Remove Contact"></i>' +
                        '</div>',
                    _contactDetailsHTML = '<div class="contact-details-row form-group" data-row="{contact}">' +
                        '<input type="hidden" class="contact-details-id" name="contact-details-id[{row}][{contact}]" value="">' +
                        '<i class="fa fa-times remove-details pl-5"></i>' +
                        '<div class="input-group">' +
                        '<span class="input-group-addon select-addon"><i class="fa fa-at" data-type="3" data-offset="3"></i></span>' +
                        '<input type="text" name="contact-details[{row}][{contact}]" class="form-control contact-details" placeholder="Contact Details">' +
                        '<input name="contact-details-type[{row}][{contact}]" class="contact-type" type="hidden" value="3">' +
                        '</div>' +
                        '</div>',
                    _typeSelectDefaults = {
                        containerClassName: ".input-group",
                        inputGroupClassName: '.select-addon',
                        title: 'Click to change Contact type',
                        optionsHTML: [
                            {title: 'Land Line', html: '<i class="fa ' + faTypeIcons[0] + '" data-type="0"></i>'},
                            {title: 'Mobile/Cell Phone', 'html': '<i class="fa ' + faTypeIcons[1] + '" data-type="1"></i>'},
                            {title: 'Fax', html: '<i class="fa ' + faTypeIcons[2] + '" data-type="2"></i>'},
                            {title: 'Email', html: '<i class="fa ' + faTypeIcons[3] + '" data-type="3"></i>'}
                        ]
                    };
                $("#client-country").select2();
                $("#client-manager").select2({allowClear: true, placeholder: "Select Account Manager"});
                $('.contact-group')
                    .find('.contact-details-row').typeSelect(_typeSelectDefaults).end()
                    .on({
                        'ts-selected': function (e, selected) {
                            $('.contact-type', this).val($(selected).data('type'));
                            $(this).find('.contact-details').rules('remove');
                            if ($(selected).data('type') == 3) {
                                $(this).find('.contact-details').rules('add', {"required": true, "email": true});
                            } else {
                                $(this).find('.contact-details').rules('add', {"required": true});
                            }


                        }
                    }, '.contact-details-row')
                    .on({
                        click: function () {
                            var _key = ((($(this).closest('.contact-row').find('.contact-details-row').last().data('row') || 0 ) * 1) + 1).toString(),
                                _html = _contactDetailsHTML
                                    .replace(/\{row\}/g, $(this).closest('.contact-row').data("row").toString())
                                    .replace(/\{contact\}/g, _key),
                                _append = $(_html).appendTo($(this).closest('.contact-row').find('.contact-container'));
                            $('.contact-details', _append).each(function () {
                                $(this).rules('add', {"required": true, "email": true});
                            });
                            _append.typeSelect(_typeSelectDefaults);
                        }
                    }, '.contact-details-add')
                    .on({
                        click: function () {
                            if ($(this).closest(".contact-container").find('.contact-details-row').length > 1) {
                                var _self = this;
                                if ($(_self).closest('.contact-details-row').find('.contact-details-id').val() != '') {
                                    swal({
                                        title: "Warning!",
                                        text: "Deleting Contact Details is not reversible, are you sure you want to continue?",
                                        type: "warning",
                                        confirmButtonClass: "btn-danger",
                                        confirmButtonText: "Yes, Delete Contact Details",
                                        showCancelButton: true,
                                        cancelButtonText: "No, Cancel",
                                        cancelButtonClass: "btn-default",
                                    }, function (confirm) {
                                        if (confirm) {
                                            $.ajax({
                                                type: "POST",
                                                dataType: 'json',
                                                url: "/includes/ajax/",
                                                data: {
                                                    endpoint: "Client",
                                                    type: "deleteClientContactDetails",
                                                    id: $(_self).closest('.contact-details-row').find('.contact-details-id').val()
                                                }
                                            })
                                                .done(function () {
                                                    var _detailType, $row = $(_self).closest('.contact-details-row');
                                                    switch (parseInt($row.find('.contact-type').val())) {
                                                        case 0:
                                                            _detailType = 'Phone Number ';
                                                            break;
                                                        case 1:
                                                            _detailType = 'Mobile Number ';
                                                            break;
                                                        case 2:
                                                            _detailType = 'Fax Number ';
                                                            break;
                                                        case 3:
                                                            _detailType = 'Email Address ';
                                                            break;
                                                        default:
                                                            _detailType = '';
                                                            break;

                                                    }
                                                    $(document).handleNoty(_detailType + $row.find('.contact-details').val() + " Successfully Removed from " + $(_self).closest('.contact-row').find('.contact-name').val() + ".", "success");
                                                    $(_self).closest(".contact-details-row").remove();
                                                })
                                                .fail(function (xhr) {
                                                    $(document).handleNoty(xhr.responseJSON.error);
                                                    console.error("[ERROR] [" + xhr.status + "]: " + xhr.responseJSON.error);
                                                });
                                        }
                                    });
                                } else {
                                    $(_self).closest(".contact-details-row").remove();
                                }
                            } else {
                                swal({
                                    title: "Error!",
                                    text: "Contact must have at least method of contacting them",
                                    type: "error",
                                    confirmButtonClass: "btn-danger"
                                })
                            }
                        }
                    }, '.remove-details')
                    .on({
                        click: function () {
                            if ($('.contact-group .contact-row').length > 1) {
                                var _self = this;
                                if ($(_self).closest('.contact-row').find('.contact-id').length > 0) {
                                    swal({
                                        title: "Warning!",
                                        text: "Deleting this contact will remove all details associated with this contact as well as the contact itself, are you sure you want to remove it?",
                                        type: "warning",
                                        confirmButtonClass: "btn-danger",
                                        confirmButtonText: "Yes, Delete Contact and Details",
                                        showCancelButton: true,
                                        cancelButtonText: "No, Cancel",
                                        cancelButtonClass: "btn-default",
                                    }, function (confirm) {
                                        if (confirm) {
                                            $.ajax({
                                                type: "POST",
                                                dataType: 'json',
                                                url: "/includes/ajax/",
                                                data: {
                                                    endpoint: "Client",
                                                    type: "deleteClientContact",
                                                    id: $(_self).closest('.contact-row').find('.contact-id').val()
                                                }
                                            })
                                                .done(function () {
                                                    $(document).handleNoty("Contact '" + $(_self).closest('.contact-row').find('.contact-name').val() + "' Successfully Removed from " + $("#client-name").val() + ".", "success");
                                                    $(_self).closest(".contact-row").remove();
                                                })
                                                .fail(function (xhr) {
                                                    $(document).handleNoty(xhr.responseJSON.error);
                                                    console.error("[ERROR] [" + xhr.status + "]: " + xhr.responseJSON.error);
                                                });
                                        }
                                    });
                                } else {
                                    $(_self).closest(".contact-row").remove();
                                }
                            } else {
                                swal({
                                    title: "Error!",
                                    text: "Client must have at least one contact",
                                    type: "error",
                                    confirmButtonClass: "btn-danger"
                                })
                            }
                        }
                    }, '.remove-contact');
                $('.add-contact').on({
                    click: function () {
                        var _key = ((($('.contact-row').last().data("row") || 0) * 1) + 1).toString(),
                            _html = _contactHTML.replace(/\{row\}/g, _key),
                            _append = $(_html).appendTo($('.contact-group'));
                        $('.contact-name,.contact-relation', _append).each(function () {
                            $(this).rules('add', "required");
                        });
                        $('.contact-details', _append).each(function () {
                            $(this).rules('add', {"required": true, "email": true});
                        });
                        _append.typeSelect(_typeSelectDefaults);
                    }
                });
                $('#save-client').on({
                    click: function () {
                        var $clientForm = $("#client-form"),
                            _original = $(this).html(),
                            _self = this;
                        if ($clientForm.valid()) {
                            $(_self).prop('disabled', true).html('Please Wait...');
                            $.ajax({
                                type: "POST",
                                dataType: 'json',
                                url: "/includes/ajax/",
                                data: {
                                    endpoint: "Client",
                                    type: "newClient",
                                    form: $clientForm.serializeObject()
                                }
                            })
                                .done(function (data) {
                                    $(document).handleNoty("Client '" + $("#client-name").val() + "' Successfully Saved.", "success");
                                    validationForm.resetForm();
                                    $(_self).prop('disabled', false).html(_original);
                                })
                                .fail(function (xhr) {
                                    $(document).handleNoty(xhr.responseJSON.error);
                                    console.error("[ERROR] [" + xhr.status + "]: " + xhr.responseJSON.error);
                                    $(_self).prop('disabled', false).html(_original);
                                });
                        }
                    }
                });
                $('#save-changes').on({
                    click: function () {
                        var $clientForm = $("#client-form"),
                            _original = $(this).html(),
                            _self = this;
                        if ($clientForm.valid()) {
                            $(_self).prop('disabled', true).html('Please Wait...');
                            $.ajax({
                                type: "POST",
                                dataType: 'json',
                                url: "/includes/ajax/",
                                data: {
                                    endpoint: "Client",
                                    type: "editClient",
                                    form: $clientForm.serializeObject()
                                }
                            })
                                .done(function (data) {
                                    $(document).handleNoty("Client '" + $("#client-name").val() + "' Successfully Saved.", "success");
                                    validationForm.resetForm();
                                    $(_self).prop('disabled', false).html(_original);
                                })
                                .fail(function (xhr) {
                                    $(document).handleNoty(xhr.responseJSON.error);
                                    console.error("[ERROR] [" + xhr.status + "]: " + xhr.responseJSON.error);
                                    $(_self).prop('disabled', false).html(_original);
                                });
                        }
                    }
                });
                $('#reset-form').on({
                    click: function () {
                        if ($('#save-changes').length > 0) {
                            window.location.reload();
                        } else {
                            validationForm.resetForm();
                        }
                    }
                })
            },
            _contactsDetailsBuilder: function (contacts) {
                var _html = '';
                for (var i in contacts) {
                    _html += '<tr>' +
                        '<td>' + contacts[i].contact_name + '</td>' +
                        '<td>' + contacts[i].contact_relation + '</td>' +
                        '<td>';
                    for (var k in contacts[i].contact_details) {
                        var valueRaw = (contacts[i].contact_details[k].value).replace(/[\s\t]/g, '');
                        _html += '<div><i class="pr-5 fa ' + faTypeIcons[contacts[i].contact_details[k].type] + '"></i><a href="' + ((contacts[i].contact_details[k].type == 3) ? 'mailto:' : 'tel:') + valueRaw + '">' + contacts[i].contact_details[k].value + '</a></div>';
                    }
                    _html += '</td>' +
                        '</tr>';
                }
                return _html;
            },
            _clientListHandlers: function () {
                var _self = this, _dt;
                _self._setupDataTable();
                $("#clientList")
                    .on({
                        click: function (e) {
                            e.stopPropagation();
                            window.open("/Client/edit/" + $(this).data('id'));
                        }
                    }, 'tbody tr')
                    .on({
                        click: function (e) {
                            e.stopPropagation();
                            var _data = $(this).closest('tr').data();
                            if (_dt) {
                                _dt.destroy();
                            }
                            $("#contacts-modal")
                                .find('.client-name-header').text(_data.client_name).end()
                                .find('.contacts-table tbody').empty().html(_self._contactsDetailsBuilder(_data.contacts)).end()
                                .modal("show");
                            _dt = $("#modal-contacts-table").DataTable(
                                {
                                    "sDom": "<'row'<'col-md-6 col-xs-12 'l><'col-md-6 col-xs-12'f>r>t<'row'<'col-md-4 col-xs-12'i><'col-md-8 col-xs-12'p>>",
                                    "language": {
                                        emptyTable: '<p class="text-center p-all-20"> -- No Contacts Available -- </p>',
                                        zeroRecords: '<p class="text-center p-all-20"> -- No Matching Contacts Found -- </p>',
                                        search: "_INPUT_",
                                        searchPlaceholder: "Search"
                                    },
                                    "paging": false
                                }
                            );
                        }
                    }, 'tbody .contacts-count')
            },
            _setupDataTable: function () {
                return $("#clientList").DataTable({
                    autoWidth: false,
                    ajax: {
                        "url": "/includes/ajax/",
                        "type": "POST",
                        "dataSrc": "clients",
                        "data": function (d) {
                            d.endpoint = "Client";
                            d.type = "fetchClientList";
                        }
                    },
                    "pageLength": 25,
                    "lengthMenu": [[10, 25, 50, 250, -1], [10, 25, 50, 250, "All"]],
                    "sDom": "<'row'<'col-md-6 col-xs-12 'l><'col-md-6 col-xs-12'f>r>t<'row'<'col-md-4 col-xs-12'i><'col-md-8 col-xs-12'p>>",
                    "order": [[1, "desc"]],
                    "columns": [
                        {
                            "data": "client_name"
                        },
                        {
                            "data": "client_email"
                        },
                        {
                            "data": "client_address[,<br>]"
                        },
                        {
                            "data": "creation_date"
                        },
                        {
                            "data": "creation_date_iso"
                        },
                        {
                            "data": "account_manager"
                        },
                        {
                            "data": "contacts_count",
                            "class": "contacts-count"
                        },
                        {
                            "data": null,
                            "defaultContent": ''
                        }
                    ],
                    "columnDefs": [
                        {
                            "targets": [4],
                            "visible": false,
                            "searchable": true
                        },
                        {"orderData": [4], "targets": [3]}
                    ],
                    "initComplete": function () {
                        $("#clientList").show().addClass("fadeIn");
                    },
                    "language": {
                        emptyTable: '<p class="text-center p-all-20"> -- No Clients Available -- </p>',
                        zeroRecords: '<p class="text-center p-all-20"> -- No Matching Clients Found -- </p>',
                        search: "_INPUT_",
                        searchPlaceholder: "Search"
                    },
                    "createdRow": function (row, data) {
                        // Set the contacts Button.
                        if (data.contacts_count > 0) {
                            $(row).find("td:eq(5)").append('<i class="fa fa-users pl-10 show-contacts"></i>');
                        }

                        // Set the license State Details
                        var _state;
                        switch (data.license_state) {
                            case 0:
                                _state = 'Expired';
                                break;

                            case 1:
                                _state = 'Expiring Soon: ' + data.expiry_date;
                                break;
                            case 2:
                                _state = 'Active';
                                break;
                            default:
                                _state = "No License";
                                break;
                        }
                        $(row).find("td:eq(6)").text(_state);

                        $(row).data(data);
                    }
                });
            }
        };
    }();
$(document).ready(function () {
    Client.init();
});