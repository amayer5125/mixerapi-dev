# MixerAPI ExceptionRender

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mixerapi/exception-render.svg?style=flat-square)](https://packagist.org/packages/mixerapi/exception-render)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE.txt)
[![Build Status](https://travis-ci.org/mixerapi/exception-render.svg?branch=master)](https://travis-ci.org/mixerapi/exception-render)
[![Coverage Status](https://coveralls.io/repos/github/mixerapi/exception-render/badge.svg?branch=master)](https://coveralls.io/github/mixerapi/exception-render?branch=master)
[![MixerApi](https://mixerapi.com/assets/img/mixer-api-red.svg)](http://mixerapi.com)
[![CakePHP](https://img.shields.io/badge/cakephp-%3E%3D%204.0-red?logo=cakephp)](https://book.cakephp.org/4/en/index.html)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.2-8892BF.svg?logo=php)](https://php.net/)

!!! warning "" 
    This is an alpha stage plugin.

This plugin handles rendering entity validation errors and other exceptions for your API.

- Adds validation errors to the response for failed save operations (post, put, and patch)
- Adds the short name of the Exception thrown to the response

Read more at [MixerAPI.com](https://mixerapi.com).

## Installation 

!!! tip "" 
    You can skip this step if you have MixerApi installed. 

```console
composer require mixerapi/exception-render
bin/cake plugin load MixerApi/ExceptionRender
```

Alternatively after composer installing you can manually load the plugin in your Application:

```php
# src/Application.php
public function bootstrap(): void
{
    // other logic...
    $this->addPlugin('MixerApi/ExceptionRender');
}
```

## Setup

In your `config/app.php` file change the default `exceptionRenderer`:

```php
'Error' => [
    'errorLevel' => E_ALL,
    'exceptionRenderer' => MixerApi\ExceptionRender\MixerApiExceptionRenderer::class,
    'skipLog' => [],
    'log' => true,
    'trace' => true,
],
```

## Usage

Define your Validations as normal in your Table classes and `MixerApiExceptionRenderer` handles the rest by attaching 
a listener to the [afterMarshall](https://book.cakephp.org/4/en/orm/table-objects.html#aftermarshal) event which fires 
when request data is merged into entities during patchEntity() or newEntity() calls. If a validation fails then a 
`ValidationException` is thrown and rendered with an HTTP 422 status code.

Example controller action:

```php
public function add()
{
    $this->request->allowMethod('post');
    $actor = $this->Actors->newEmptyEntity();
    $actor = $this->Actors->patchEntity($actor, $this->request->getData()); // potential ValidationException here
    if ($this->Actors->save($actor)) {
        $this->viewBuilder()->setOption('serialize', 'actor');
        $this->set('actor', $actor);

        return;
    }
    throw new \Exception("Record failed to save");
}

```

Output:

```json
{
  "exception": "ValidationException",
  "message": "Error saving resource `Actor`",
  "url": "/actors",
  "code": 422,
  "violations": [
    {
      "propertyPath": "first_name",
      "messages": [
        {
          "rule": "_required",
          "message": "This field is required"
        }
      ]
    },
    {
      "propertyPath": "last_name",
      "messages": [
        {
          "rule": "_required",
          "message": "This field is required"
        }
      ]
    }
  ]
}
```

Using the controller example from above, we can catch the exception if desired and perform additional logic:

```php
try {
    $actor = $this->Actors->newEmptyEntity();
    $actor = $this->Actors->patchEntity($actor, $this->request->getData());
} catch (\MixerApi\ExceptionRender\ValidationException $e) {
    // do something here
}
```

### Exceptions

For non-validation based exceptions, even your projects own custom exceptions, the output is similar to CakePHP native 
output with the addition of an exception attribute. For example, a `MethodNotAllowedException` would result in: 

```json
{
  "exception": "MethodNotAllowedException",
  "message": "Your exception message here",
  "url": "/actors",
  "code": 405
}
```

If for instance you have a custom exception that is thrown, such as `InventoryExceededException`, you would see: 

```json
{
  "exception": "InventoryExceededException",
  "message": "No inventory exists",
  "url": "/requested-url",
  "code": 500
}
```

Providing an Exception name, in conjunction with the status code already provided by CakePHP, enables API clients 
to tailor their exception handling.

## Unit Tests

```bash
vendor/bin/phpunit
```

## Code Standards

```bash
composer check
```