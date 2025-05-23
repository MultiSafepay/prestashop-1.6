# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

***

## 3.15.0
Release date: May 16th, 2025

### Added
+ DAVAMS-859: Add Billink
+ DAVAMS-805: Add BIZUM

### Removed
+ DAVAMS-894: Remove ALIPAY payment method
+ DAVAMS-816: Remove gender checkout field from iDEAL in3

***

## 3.14.0
Release date: Sep 12th, 2024

### Fixed
+ PLGPRSS-501: Fix gender fields are not translatable
+ PLGPRSS-500: Fix PHP warnings

### Removed
+ PLGPRSS-510: Remove issuers from iDEAL
+ PLGPRSS-509: Remove composer.json file from release package

### Changed
+ DAVAMS-798: Rebrand Afterpay - Riverty Logo

***

## 3.13.1
Release date: May 7th, 2024

### Fixed
+ PLGPRSS-506: Fix to get the plugin version taking the module as reference

***

## 3.13.0
Release date: Apr 18th, 2024

### Added
+ DAVAMS-763: Add in3 B2B payment method
+ DAVAMS-683: Add Multibanco payment method
+ DAVAMS-682: Add MB WAY payment method
+ DAVAMS-655: Add BNPL_MF payment method
+ DAVAMS-534: Add support for Template ID in the Payment Component

### Changed
+ DAVAMS-744: Rebranding in3 B2C
+ DAVAMS-704: Rebrand in3 payment method name and remove birthday checkout field

### Fixed
+ DAVAMS-751: Fix the 'template_id' setting field within the Payment Components

***

## 3.12.0
Release date: Oct 13th, 2023

### Added
+ PLGPRSS-498: New option to control the inclusion of shopping cart data in order requests

### Fixed
+ PLGPRSS-497: Fix the input field being displayed when it should be hidden
+ DAVAMS-667: Refactor module for better error handling

***

## 3.11.0
Release date: Aug 9th, 2023

### Added
+ DAVAMS-656: Add Zinia Payment Option

### Fixed
+ PLGPRSS-495: Fix warnings related with undefined variables
+ PLGPRSS-494: Prevent log an exception when a cancel action triggers the notification controller and this one does not contain the transaction_id

### Changed
+ DAVAMS-645: Refactor and general improvements over the payment component

***

## 3.10.5
Release date: Jul 12th, 2023

### Fixed
+ PLGPRSS-492: Fix an issue when the transaction needs to be set as shipped but the carrier name can't be found

***

## 3.10.4
Release date: Jun 26th, 2023

### Fixed
+ PLGPRSS-490: Add payment method name and PSP ID into Order Payment object

***

## 3.10.3
Release date: Jun 16th, 2023

### Fixed
+ PLGPRSS-488: Fix an issue where the customer is being redirected to the shop but the shopping cart is not being duplicated when the pre-transaction is cancelled on the payment page

***

## 3.10.2
Release date: Jun 2nd, 2023

### Fixed
+ PLGPRSS-486: Fix the notification process to update the order status, when multiple orders are being created after PrestaShop splits the shopping cart into several orders

### Changed
+ DAVAMS-615: Rename 'Credit Card' payment method as 'Card payment'

***

## 3.10.1
Release date: Jun 1st, 2023

### Fixed
+ PLGPRSS-484: Fix CSS and JS loading when OPC is enabled

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
