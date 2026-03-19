# Dedicated Servers Module for MyAdmin

[![Tests](https://github.com/detain/myadmin-servers-module/actions/workflows/tests.yml/badge.svg)](https://github.com/detain/myadmin-servers-module/actions/workflows/tests.yml)
[![Latest Stable Version](https://poser.pugx.org/detain/myadmin-servers-module/version)](https://packagist.org/packages/detain/myadmin-servers-module)
[![Total Downloads](https://poser.pugx.org/detain/myadmin-servers-module/downloads)](https://packagist.org/packages/detain/myadmin-servers-module)
[![License](https://poser.pugx.org/detain/myadmin-servers-module/license)](https://packagist.org/packages/detain/myadmin-servers-module)

A MyAdmin plugin module that provides dedicated server management capabilities. It integrates with the MyAdmin service lifecycle to handle server provisioning, activation, reactivation, deactivation, and suspension through the Symfony EventDispatcher system.

## Features

- Dedicated server service lifecycle management (activate, deactivate, enable, reactivate, disable, terminate)
- Configurable billing with prorate support and customizable day offsets
- Automated email notifications for server reactivation events and pending setup alerts
- Admin settings panel with out-of-stock toggle for controlling server sales
- Event-driven architecture using Symfony EventDispatcher hooks
- Server status management with suspended and active-billing states

## Installation

Install with Composer:

```sh
composer require detain/myadmin-servers-module
```

## Configuration

The module provides configurable settings through the `Plugin::$settings` array including service ID offsets, billing parameters, suspension thresholds, and database table mappings.

## Testing

Run the test suite with PHPUnit:

```sh
composer install
vendor/bin/phpunit
```

## License

The Dedicated Servers Module for MyAdmin is licensed under the LGPL-v2.1 license.
