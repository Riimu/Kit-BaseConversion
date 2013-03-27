# Library for number base conversion of positive integers of arbitrary size #

The NumberConversion package contains class that

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
implemented in the number conversion library

- *DecimalConverter\GMPConverter* uses the GNU MP library included in the GMP
  PHP extension. For most conversions, these are extremely fast and not much
  slower compared to replacement conversion methods.
- *DecimalConverter\BCMathConverter* uses the BCMath PHP extension for
  calculation. Compared to GMP, however, this is much slower and usually not
  advisable solution.
- *DecimalConverter\InternalConverter* implements it's own arbitrary precision
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
source base is represented by exactly certain amount of digits in the target
base. As a result, we can essentially just perform a simple replace
