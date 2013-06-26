# Abitrary precision base conversion library for integers and fractions #

*WARNING*: This library has not reached stable status yet and anything in this
library may change rapidly before 1.0.0 release.

## Usage ##

In most cases, all you need to do to use this library is to create new instance
of `BaseConverter` and use the `BaseConverter::convert()` method to convert
numbers. For example:

```php
<?php
$converter = new Riimu\Kit\NumberConversion\BaseConverter(10, 16);
echo $converter->convert('42'); // Will output '2A'
```

The constructor arguments are the number bases used for conversion. The first is the
number base used by input numbers and second is the number base for result
numbers. The values can be given as arguments to `NumberBase` constructor or
as instances of `NumberBase`.

Converting fractions is just as easy. For example:

```php
<?php
$converter = new Riimu\Kit\NumberConversion\BaseConverter(8, 12);
echo $converter->convert('-1337.1337'); // Will output '-513.21A0B'
```

See below for further information about how fractions and number bases are
handled.

### Fractions ###

In some cases, it is impossible to convert fractions accurately. For example,
0.1 in base 3 equals 0.3333... in base 10. For these situations, there is a method
`BaseConverter::setPrecision()`. A positive integer of 1 or greater indicates the
number of digits in these inaccurate conversions. For example:

```php
<?php
$converter = new Riimu\Kit\NumberConversion\BaseConverter(10, 12);
echo $converter->convert('-1337.1337') . PHP_EOL; // Outputs '-935.17305'

$converter->setPrecision(10);
echo $converter->convert('-1337.1337') . PHP_EOL; // Outputs '-935.17304A0891'

$converter->setPrecision(4);
echo $converter->convert('-1337.1337') . PHP_EOL; // Outputs '-935.1730'
```

The last number is always rounded. However, by using 0 or a negative integer as
the precision, the converter will determine the number of digits required to
represent the number in at least same accuracy as the input number. If the
precision is negative, digits will be added equal to the absolute value of the
precision. The default precision is -1. For example:

```php
<?php
$converter = new Riimu\Kit\NumberConversion\BaseConverter(15, 2);
echo $converter->convert('0.A7') . PHP_EOL; // Outputs '0.101100101'

$converter->setPrecision(-3);
echo $converter->convert('0.A7') . PHP_EOL; // Outputs '0.10110010101'

$converter->setPrecision(-10);
echo $converter->convert('0.A7') . PHP_EOL; // Outputs '0.101100101010000110'
```

Note that precision parameter is ignored if the number is converted using
replacement logic as this always yields accurate reults. Additionaly, if the
number can be accurately represented using less digits than the indicated
precision, the resulting number will only have the necessary digits.

### Case sensitivity ###

The general rule of this number conversion library is to act in case insensitive
manner whenever possible. For example, if the base 36 is used, it will be case
insensitive, because it only contains the digits 0-9A-Z, thus making the 
letter case irrelevant. However, If base 64 was used instead, the digits would
be case sensitive, because the digits 'A' and 'a' would have different values.

### Number Bases ###

## About different conversion methods ##

When converting numbers from base to another, there are several different
methods to achieve the goal. This section discusses the different conversion
methods implemented in this library and how they differ. Generally you do not
need to worry about this. The BaseConverter class will choose the appropriate
method to use for you.

The reason these different conversion methods exist is due to speed and
limitations posed by different conversion strategies. This library implements
three different main strategies to convert numbers from base to another. These
strategies are named decimal conversion, replacement conversion and direct
conversion within this library.

### Decimal Conversion ###

The basic idea of decimal conversion is to convert the number into decimal
number, which is then converted into the target base.

For integers, the conversion is done by first multiplying each digit by
their place value and then summing them up. The number is then converted to
target base by repeated application of modulo and integer division.

Fractions are converted by first converting the numerator and denominator in
common fraction from source base into decimal. The target base is then achieved
by repeated multiplication of the numerator.

Use of either of these methods on numbers with large amount of digits requires
the use of arbitrary precision arithmetic. The speed of these algorithms depend
greatly on the used arbitrary precision library. The following libraries are
implemented in the number conversion library:

- *Method\Decimal\GMPConverter* uses the GNU MP library included in the GMP
  PHP extension. For most conversions, these are extremely fast and not much
  slower compared to replacement conversion methods.
- *Method\Decimal\BCMathConverter* uses the BCMath PHP extension for
  calculation. Compared to GMP, however, this is much slower and usually not
  advisable solution.
- *Method\Decimal\InternalConverter* implements it's own arbitrary precision
  arithmetic and it is not dependant on any additional PHP extension. This
  library is bad choice for anything but small numbers. This library exists
  mostly to provide a fallback method if GMP nor BCMath is available on the PHP
  installation.

The complexity of these algorithm vary widely depending on the complexity of the
library used. In general, however, the time taken grows exponentially relative
to the size of the number as each additional digit increases the value of the
decimal number exponentially. The size of the number bases, however, have much
smaller effect on the conversion speed.

The advantage of decimal conversion is that it is only limited by the memory and
processing time available. The only hard limit posed by the decimal conversion
is the fact that number of digits is limited by 32 bit integers. Realistically,
however, these conversion methods start to slow down after several thousand
digits.

### Replacement Conversion ###

Replacement conversion is a special case of base conversion. For replacement
conversion to work, the source base needs to be either the nth root or nth
power of the target base. If this condition is fulfilled, each digit in the
larger base is represented exactly by n digits in the smaller base. This allows
us to basically just replace the digits with digits from the other base.

There are couple different specific approaches that can be taken to actually do
this. The following methods are implemented in this library:

- *Method\Replace\DirectReplaceConverter* implements the replacement method by
  building a conversion table between corresponding digits in source and target
  base. If no case insensitive lookups are required, this method benefits from
  not having to convert the digits to their decimal values.
- *Method\Replace\NumberReplaceConverter* use similar approach as the
  DirectReplaceConverter. The key difference is that digit decimal values are
  used instead, which are used to build a faster hashtable for replacement
  lookups which is beneficial for larger number bases.
- *Method\Replace\MathReplaceConverter* does not build a conversion table for
  digits. Instead, it calculates the decimal values in smaller chunks for the
  new digit. If converting short numbers with large bases, this will come out
  faster as it doesn't have to build a conversion table first.
- *Method\Replace\StringReplaceConverter* takes advantage of special case in
  this special case. If all digits in both bases are strings and have equal
  length, a simple string translation can be used. If usable, then it's faster
  than the other iterative approaches.

The greatest advantage of the replacement conversion methods is the linear
complexity. Unlike other conversion strategies, the conversion speed only grows
linearly as more digits are added. Replacing digits is also considerably faster
than doing complex calculations to reach the result, which makes the replacement
conversion fastest method in almost every case.

The only disadvantage of these methods is the fact that most of them have to
build a conversion table between the number bases, which can be memory intensive
on larger number bases.

### Direct Conversion ###

## Copyright ##

This library is Copyright (c) 2013 to Riikka Kalliomäki. See LICENSE for
complete copyright notice.
