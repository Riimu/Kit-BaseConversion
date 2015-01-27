# Abitrary precision base conversion #

This library provides functionality to convert numbers from any base to any
other base. This library offers a more complete solution compared to PHP's
built in `base_convert()` as this library offers conversion of fractions in
addition to taking advantage of arbitrary precision arithmetic to handle numbers
of any size.

The library also provides optimized conversion of cases like converting from
base 2 to base 16, in which digits can be simply replaced instead of calculating
the new digits.

The `NumberBase` class also offers much more customization for number bases
allowing you to define custom digits. It also provides support for bases as
large as unsigned integers allow.

API documentation is [available](http://kit.riimu.net/api/baseconversion/) and it
can be generated using ApiGen.

[![Build Status](https://travis-ci.org/Riimu/Kit-BaseConversion.svg?branch=master)](https://travis-ci.org/Riimu/Kit-BaseConversion)
[![Coverage Status](https://coveralls.io/repos/Riimu/Kit-BaseConversion/badge.png)](https://coveralls.io/r/Riimu/Kit-BaseConversion)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Riimu/Kit-BaseConversion/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Riimu/Kit-BaseConversion/?branch=master)

## Requirements ##

The arbitrary precision arithmetic in this library requires the following
extension for fast calculations:

  * [GMP library](http://php.net/manual/en/book.gmp.php)

## Installation ##

This library can be easily installed using [Composer](http://getcomposer.org/)
by including the following dependency in your `composer.json`:

```json
{
    "require": {
        "riimu/kit-baseconversion": "1.*"
    }
}
```

The library will be the installed by running `composer install` and the classes
can be loaded with simply including the `vendor/autoload.php` file.

## Usage ##

In most cases, all you need to do to use this library is to create new instance
of `BaseConverter` and use the `convert()` method to convert numbers. For
example:

```php
<?php
$converter = new Riimu\Kit\BaseConversion\BaseConverter(10, 16);
echo $converter->convert('42'); // Will output '2A'
```

The constructor arguments are the number bases used for conversion. The first
argument is the number base used by input numbers and the second is the number
based used by output numbers. For information about defining numbers bases, see
below.

Converting fractions is just as easy. For example:

```php
<?php
$converter = new Riimu\Kit\BaseConversion\BaseConverter(8, 12);
echo $converter->convert('-1337.1337'); // Will output '-513.21A0B'
```

See below for further information about how fractions and number bases are
handled.

### Fractions ###

In some cases, it is impossible to convert fractions accurately. For example,
if the number 0.1 is converted from base 3 to base 10, it equals 1/3 which is
0.3333... For cases like this, the precision used for conversion can be set
using `setPrecision()`. A positive integer of 1 or greater indicates the number
of digits in these inaccurate conversions. For example:

```php
<?php
$converter = new Riimu\Kit\BaseConversion\BaseConverter(10, 12);
$converter->setPrecision(5);
echo $converter->convert('-1337.1337') . PHP_EOL; // Outputs '-935.17304'

$converter->setPrecision(10);
echo $converter->convert('-1337.1337') . PHP_EOL; // Outputs '-935.17304A0890'

$converter->setPrecision(4);
echo $converter->convert('-1337.1337') . PHP_EOL; // Outputs '-935.1730'
```

Note that the last number is not rounded. However, by using 0 or a negative
integer as the precision, the converter will determine the number of digits
required to represent the number in at least same accuracy as the input number.
If the precision is negative, digits will be added equal to the absolute value
of the precision. The default precision is -1. For example:

```php
<?php
$converter = new Riimu\Kit\BaseConversion\BaseConverter(15, 2);
echo $converter->convert('0.A7') . PHP_EOL; // Outputs '0.101100101'

$converter->setPrecision(-3);
echo $converter->convert('0.A7') . PHP_EOL; // Outputs '0.10110010101'

$converter->setPrecision(-10);
echo $converter->convert('0.A7') . PHP_EOL; // Outputs '0.101100101010000110'
```

Note that precision parameter is ignored if the number is converted using
replacement logic as this always yields accurate results. Additionally, if the
number can be accurately represented using less digits than the indicated
precision, the resulting number won't have trailing zeros.

### Case sensitivity ###

In general, the number bases are case insensitive whenever possible. For example,
base 16 has digits 0-9A-F, which allows you to use either lower or upper case
letters for the digits. However, for bases like base 62, the characters are
case sensitive, because the number base contains both 'a' and 'A' digits and
they have different values. In numerical bases, the library prefers upper case
letters in output. This may be easily overridden by providing the digits
yourself in the constructor, for example:

```php
<?php
$converter = new Riimu\Kit\BaseConversion\BaseConverter(10, '0123456789abcdef');
echo $converter->convert('42'); // Will output '2a'
```

### Number Bases ###

When creating new instances of `BaseConverter`, the arguments can be provided as
instances of `NumberBase` or the constructor arguments passed to `NumberBase`. The
constructor argument of `NumberBase` allows you to define number bases in couple
different ways.

The easiest way is to provide a positive integer. If the integer is between 2
and 62, the characters from sequence 0-9A-Za-z are used as digits. If the number
is 64, the the number base will use the digits as defined in base64 encoding.
For other numbers equal to 256 or smaller single bytes are used with byte value
used as digit value. If the number is 257 or greater, then each digit is
represented by hash followed by the digit value. For example, in base 1028 the
number 372 is represented by "#0372".

The second way to provide a number base is to provide a string of characters.
The position of each character indicates their digit value. For example, binary
would be given as "012". Decimal would be "0123456789" and hexadecimal would be
"0123456789ABCDEF".

The third way to create a number base is to provide the digits as an array. In
the array, the index indicates the value of the digit and the value is the digit
itself. The number base is completely agnostic to the value types used in the
array. However, it is worth noting that `NumberBase` uses loose comparison to
compare digit equality.

## Credits ##

This library is copyright 2013 - 2015 to Riikka Kalliomäki.

See LICENSE for license and copying information.
