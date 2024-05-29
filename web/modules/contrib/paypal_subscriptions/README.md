# paypal_subscriptions

INTRODUCTION
------------
PayPal Subscriptions for Commerce 2.x

An extension of the commerce paypal module which allows you 
to use recurring payments through the paypal express checkout.

This module contains:
- A payment gateway for PayPal subscriptions.

Note that this uses the express checkout method through paypal.

REQUIREMENTS
------------
- commerce_paypal

INSTALLATION
------------
- Install via `composer install drupal/paypal_subscriptions` or
via Drupal Admin UI on menu Extend / Install
- Enable the module via `drush en paypal_subscription` or
via Admin UI again.

CONFIGURATION
-------------
- Be able to make a payment through PayPal Express checkout.
- Be able to select frequency of Billing.
- Be able to create a billing agreement for users.

Payment Gateway
---------------
This payment gateway forces the user to go through subsciptions 
in paypal. A billing agreement is create and then a user is 
billed at the desired interval.
