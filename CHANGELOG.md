# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## 2.2.0
- Change flow for PENDING payments coming from Satispay API
- 
## 2.1.2
- Fixed bug regarding the <public_key> parameter. If the user didn't flush the cache after enabling the Plugin, the website threw an error. 
- Now the user has to: Enable the Plugin > Save the configuration > Set the token

## 2.1.1
- Added Satispay logo on checkout

## 2.1.0
- Added cron to finalize unhandled payments
- Added support to web-app-web switch

## 2.0.5
- Replace description with external_code in payment creation

## 2.0.3
- Send new order email only when payment is accepted
- Cancel order if payment is canceled

## 2.0.2
- Performed refactoring to adhere to Magento 2 Coding Standards
- Set SDK requirement to version ^1.2
- Removed SDK directory
- Set setup_version in module.xml
- Added checkout-agreements-block to payment form
- Added `sort_order` field in system.xml

## 2.0.1
bump version

## 2.0.0
Merge pull request #9 from satispay/new-version
New version with GBusiness APIs

## 1.2.4
fix capture notification

## 1.2.3
update sdk and bump version

## 1.2.2
bump version

## 1.2.1
bump version

## 1.2.0
working version

## 1.1.1
bump version

## 1.1.0
publish 1.1.0

## 1.0.0
1.0.0
