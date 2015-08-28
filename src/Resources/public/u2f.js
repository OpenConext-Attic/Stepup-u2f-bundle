jQuery(function ($) {

    /**
     * Success. Not used in errors but reserved.
     *
     * @type {number}
     * @see https://fidoalliance.org/specs/fido-u2f-v1.0-nfc-bt-amendment-20150514/fido-u2f-javascript-api.html#error-codes
     */
    var ERROR_CODE_OK = 0;

    $('form[data-u2f-register-request]').first().forEach(function () {
        var $form = $(this),
            $errorCode = $form.find('input[data-u2f-register-response-field="errorCode"]'),
            $registrationData = $form.find('input[data-u2f-register-response-field="registrationData"]'),
            $clientData = $form.find('input[data-u2f-register-response-field="clientData"]'),
            registerRequest = $form.data('u2f-register-request');

        u2f.register([registerRequest], [], function (response) {
            $errorCode.val(response.errorCode || ERROR_CODE_OK);
            $registrationData.val(response.registrationData);
            $clientData.val(response.clientData);
            $form.submit();
        });
    });

});
