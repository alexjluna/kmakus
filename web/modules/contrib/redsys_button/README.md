# Redsys button payment

With this module, we can create a payment form in Drupal compatible with RedSys without the need to use Commerce. In other words, this module allows you to solve two very common problems easily.


## Table of contents

- Requirements
- Installation
- Usage
- Contributing
- License
- Maintainers

## Installation

Complete the necessary data to connect to the API in /admin/config/system/redsys-settings

Let's see what they are:

1. I want to receive payments through RedSys on my website, whether for collecting donations, billing invoices, or any other reason; and I don't want to have to set up the whole infrastructure, heaviness, and maintenance of a commerce module.
2. I have the Commerce module installed, working, and my store sells perfectly, but I want to separate the payments I make for my consultancy invoices from the sales I make through the POS in my online store. Of course, I want these payments to come via RedSys because PayPal charges a high fee and some customers do not want transfers. This is the second case we are discussing.
RedSys is the most used method by most banks within Spain: Banco Santander, BBVA, La Caixa, Banco Sabadell, ING, etc.

## Commerce Integration

Now you can use Redys how payment gateway, inside of redsys_button there is a submodule called commerce_redsys_button that let to integrate with commerce module optionally very easylly.

[Commerce Core](https://www.drupal.org/project/commerce)

Wordefull! now you can have a payment gateway only for invoice payment, donations and if you have a electronic commerce you que enable the submodule and automatically you will have payment gateway for yuo commerce using Redsys.

## Usage

Activa el módulo y coloca el bloque donde mejor te convenga y estará listo para recibir pagos.


## Contributing

Contributions to this module are welcome. Please submit bug reports, feature requests, or pull requests to the module's repository on GitHub.


## License

This module is licensed under the GNU General Public License v2.0. See the LICENSE file for more details.


## Maintainers

- Alex J. Luna - [alexjluna](https://www.drupal.org/u/alexjluna)
