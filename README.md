# Contao Location Bundle

This bundle adds a location entity to contao.

## Features

- adds a new (nestable) location entity
- ConfigElementType for [List](https://github.com/heimrichhannot/contao-list-bundle) and [Reader bundle](https://github.com/heimrichhannot/contao-reader-bundle)

## Setup and Usage

## Installation

Install via composer: `composer require heimrichhannot/contao-location-bundle` and update your database.

### Usage

A typical use case for location is to set location in another entity like news or events. 
So you need to add it to the destiny bundle dca. There are many ways in contao to make a
connection to locations like selects or checkboxes. Our recommendation is to use the contao 
picker widget:

```php
$dca['tl_news']['fields']['locations'] = [
    'inputType' => 'picker',
    'relation' => ['type' => 'hasOne', 'load' => 'eager', 'table' => 'tl_location'],
    'eval' => [
        'multiple' => true, //
        'tl_class' => 'w50 clr autoheight'
    ],
    'sql' => 'blob NULL',
]
```
