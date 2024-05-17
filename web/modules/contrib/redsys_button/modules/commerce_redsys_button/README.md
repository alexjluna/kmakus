# Commerce Redsys Button

This module integrates Redsys spanish payment into the Drupal Commerce 2 payment.

<a href="http://www.redsys.es/index.html#descargas">Redsys api docs</a>

# Requirements

- Drupal Commerce and Commerce Payment

## Installation

Its recommend to download this module with composer https://getcomposer.org/, enable the module

## Configuration

Create a Payment Gateway, and introduce the information of your merchant, FUC, merchant, currency, key, etc..

Administration > Commerce > Configuration > Payment gateways > Add payment gateway


## Configure your TPV admin

You must active the HTTP notifications, in your admin TPV panel, otherwise the module not recive the payment.
** Online Notificacion: HTTP
** Sinchronization: Asynchronous
** URL Notificacion: Empty (This module provide this url in the request)
** URL OK: empty (This module provide this url in the request)
** URL KO: empty (This module provide this url in the request)
** Send params in URL: No
