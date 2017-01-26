# Step-up U2fBundle
[![Build Status](https://travis-ci.org/OpenConext/Stepup-u2f-bundle.svg)](https://travis-ci.org/OpenConext/Stepup-u2f-bundle) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/OpenConext/Stepup-u2f-bundle/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/OpenConext/Stepup-u2f-bundle/?branch=develop)

The SURFnet Step-up U2F Bundle contains server-side device verification, and the necessary forms and resources to enable client-side U2F interaction with Step-up Identities

## Installation and configuration

 * Add the package to your Composer file
    ```sh
    composer require surfnet/stepup-u2f-bundle
    ```

 * Add the bundle to your kernel in `app/AppKernel.php`
    ```php
    public function registerBundles()
    {
        // ...
        $bundles[] = new Surfnet\StepupU2fBundle\SurfnetStepupU2fBundle();
    }
    ```

## Configuration

### AppID

```yaml
# config.yml
surfnet_stepup_u2f:
    app_id: 'https://application.tld/U2F/AppID'
```

## Usage

### Registering U2F devices

```php
/** @Template */
public function registerDeviceAction(Request $request)
{
    $service = $this->get('surfnet_stepup_u2f.service.u2f');

    $registerRequest = $service->requestRegistration();
    $registerResponse = new RegisterResponse();
    $form = $this->createForm('surfnet_stepup_u2f_register_device', $registerResponse, [
        'register_request' => $registerRequest,
    ]);

    if (!$form->isValid()) {
        $this->get('my.session.bag')->set('request', $registerRequest);
        return ['form' => $form->createView()];
    }

    $result = $service->verifyRegistration(
        $this->get('my.session.bag')->get('request'),
        $registerResponse
    );

    if ($result->wasSuccessful()) {
        $registration = $result->getRegistration());
        // ...
    } elseif ($result->handleAllErrorMethods()) {
        // Display an error to the user and allow him/her to retry with a new request
    }
}
```

**Note:** Don't display the registration form after an error: the browser or device may immediately respond with the
same error, causing an infinite form submission loop. Let the user device whether to initiate a new registration.

### Verifying U2F device authentications

```php
/** @Template */
public function verifyDeviceAuthenticationAction(Request $request)
{
    $service = $this->get('surfnet_stepup_u2f.service.authentication');

    $signRequest = $service->requestAuthentication();
    $signResponse = new SignResponse();
    $form = $this->createForm('surfnet_stepup_u2f_verify_device_authentication', $signResponse, [
        'sign_request' => $signRequest,
    ]);

    if (!$form->isValid()) {
        $this->get('my.session.bag')->set('request', $signRequest);
        return ['form' => $form->createView()];
    }

    $result = $service->verifyAuthentication(
        $this->get('my.session.bag')->get('request'),
        $signResponse
    );

    if ($result->wasSuccessful()) {
        // ...
    } elseif ($result->handleAllErrorMethods()) {
        // Display an error to the user and allow him/her to retry with a new request
    }
}
```

**Note:** Don't display the authentication form after an error: the browser or device may immediately respond with the
same error, causing an infinite form submission loop. Let the user device whether to initiate a new authentication.
