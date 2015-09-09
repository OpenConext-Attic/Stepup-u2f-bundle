jQuery(function ($) {

    /**
     * Success. Not used in errors but reserved.
     *
     * @type {number}
     * @see https://fidoalliance.org/specs/fido-u2f-v1.0-nfc-bt-amendment-20150514/fido-u2f-javascript-api.html#error-codes
     */
    var ERROR_CODE_OK = 0;

    $('form#surfnet-stepup-u2f-register-device').each(function () {
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

    $('form#surfnet-stepup-u2f-verify-device-authentication').each(function () {
        var $form = $(this),
            $errorCode = $form.find('input[data-u2f-sign-response-field="errorCode"]'),
            $keyHandle = $form.find('input[data-u2f-sign-response-field="keyHandle"]'),
            $signatureData = $form.find('input[data-u2f-sign-response-field="signatureData"]'),
            $clientData = $form.find('input[data-u2f-sign-response-field="clientData"]'),
            signRequest = $form.data('u2f-sign-request');

        u2f.sign([signRequest], function (response) {
            $errorCode.val(response.errorCode || ERROR_CODE_OK);
            $keyHandle.val(response.keyHandle);
            $signatureData.val(response.signatureData);
            $clientData.val(response.clientData);
            $form.submit();
        });
    });

});
