# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

***

## 3.10.0
Release date: Feb 22nd, 2023

### Added
+ DAVAMS-575: Add Pay After Delivery Installments payment option (#124)

### Changed
+ DAVAMS-590: Rebrand Pay After Delivery logo (#125)
+ DAVAMS-570: Remove Google Analytics tracking ID from order request (#123)

***

## 3.9.0
Release date: Dec 7th, 2022

### Changed
+ Removed extra confirmation step in the checkout
+ Component and tokenization configurations have been moved from global level, to payment method level settings. Please make sure configuration is still correct by navigating to **Modules > Module Manager > Select your payment method**.
+ Modules are now installable via PrestaShop backoffice
+ All payment methods modules only can be activated when MultiSafepay main module is installed
+ Afterpay rebranding to Riverty

### Added
+ MyBank
+ Google Pay
+ Alipay
+ Alipay+
+ Add checkout modal for direct payment methods

### Removed
+ Erotiekbon
+ Fietsenbon
+ Givacard
+ Goodcard
+ Kelly giftcard
+ Nationale Verwen cadeaubon
+ Winkelcheque
+ ING Home'Pay
+ Parfumcadeaukaart

***

## 3.8.1
Release date: Sep 14th, 2022

### Fixed
+ PLGPRSS-455: Generic Gateway code not used

***

## 3.8.0
Release date: Jul 13th, 2022

### Added
+ PLGPRSS-427: Add 3 Generic Gateways

### Fixed
+ PLGPRSS-426: Fix invalid post data when using free shipping discount

### Changed
+ PLGPRSS-435: Update MultiSafepay branding and payment icons
+ PLGPRSS-413: Remove separate API key for Pay After Delivery

***

## 3.7.1
Release date: Jan 7th, 2022

### Changed
PLGPRSS-423: Rename Client class to MultiSafepayClient to avoid conflict with third party modules

***

## 3.7.0
Release date: Nov 25th, 2021

### Added
+ DAVAMS-232: Add support for in3 payment method
+ PLGPRSS-420: Add Payment Component support for Credit Card payment method
+ PLGPRSS-409: Add support for gift products within the Shopping Cart object
+ PLGPRSS-406: Add support for Good4fun gift card

### Fixed
+ PLGPRSS-414: Fix locale code when submit the Order Request, which was generating errors in case payment address code of the customer is different from the language selected

### Changed
+ PLGPRSS-408: When a payment is cancelled, the shopping cart will not be emptied
+ DAVAMS-314: Rebrand Klarna with new logo
+ DAVAMS-298: Rebrand Direct Bank Transfer as Request to Pay

***

## 3.6.0
Release date: Jul 21st, 2020

### Added
+ DAVAMS-269: Add CBC payment method

### Changed
+ DAVAMS-213: Add track & trace to shipment request
+ PLGPRSS-404: Set order to status shipped for all payment methods

***

## 3.5.0
Release date: Apr 9th, 2020

### Added
+ PLGPRSS-344: Add AfterPay

### Fixed
+ PLGPRSS-396: Correct spelling of ING Home'Pay
+ PLGPRSS-397: Fix incorrect gateway code for ING Home'Pay

***

## 3.4.0
Release date: Apr 2nd, 2020

### Added
+ PLGPRSS-400: Add Apple Pay
+ PLGPRSS-399: Add Direct Bank Transfer

***

## 3.3.0
Release date: Feb 26th, 2020

### Fixed
+ PLGPRSS-309: Prevent multiple transactions being created for the same order
+ PLGPRSS-391: Prevent duplicated orders by adding file locking
+ PLGPRSS-267: Mobile presentation of payment methods is not fully responsive

### Changed
+ PLGPRSS-190: Send shopping cart data for all payment methods
+ PLGPRSS-352: Improve parsing of address into street and apartment
