var Login = function () {
    return {
        init: function () {
            this.setupHandlers();
            this.validation();
        },
        setupHandlers: function () {
            $('body').on({
                keypress: function (e) {
                    if (e.which == 13) {
                        $("#btn-sign-in").trigger('click');
                    }
                }
            }, "input");
            $("#authy-key").numberOnly();
            $("#btn-sign-in").on({
                click: function () {
                    // Are all fields filled in?
                    if ($("#login-form").valid()) {
                        $.ajax({
                            type: "POST",
                            dataType: 'json',
                            url: "/includes/ajax/",
                            data: {
                                endpoint: "Login",
                                email: $("#username").val(),
                                password: $("#password").val(),
                                key: $("#authy-key").val()
                            }
                        })
                            .done(function () {
                                window.location.href = '/dashboard';
                            })
                            .fail(function (xhr) {
                                $(document).handleNoty(xhr.responseJSON.error);
                                console.error("[ERROR] [" + xhr.status + "]: " + xhr.responseJSON.error);
                            });
                    }
                }
            })
        },
        validation: function () {
            $.validator.setDefaults({ignore: ":hidden"});
            $("#login-form").validate({
                errorPlacement: function (error, element) {
                    $(error).addClass('text-danger');
                    error.appendTo(element.closest(".form-group"));
                },
                rules: {
                    "username": "required",
                    "password": "required",
                    "authy-key": "required"
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
        }
    };
}();
$(document).ready(function () {
    Login.init();
});