# Library for number base conversion of positive integers of arbitrary size #

The NumberConversion package contains class that

## About different conversion methods ##

When converting numbers from base to another, there are several different
methods to achieve the goal. This section discusses the different conversion
methods implemented in this library and how they differ. Generally you do not
need to worry about this. The BaseConverter class will choose the appropriate
method to use for you.

This library implements three different main strategies to convert numbers from
base to another. These strategies are named decimal conversion, replacement
conversion and direct conversion within this library.

### Decimal Conversion ###

Decimal conversion approaches the number conversion issue by first converting
the input number into a decimal number, which is then converted into the target
base. The exact implementation varies slightly for converting integers and
fractions but that is the base idea.

The problem with this method is that converting large numbers requires the use
of arbitrary precision mathematics. So, the speed of this depends entirely on
the available extensions in PHP. The GMP extension is extremely fast for the
purposes of this library, but BCMath tends to be rather slow. The Internal
implementation implements arbitrary precision arithmetic using PHP and it is
extremely slow. The internal implementation exists mostly because fraction
conversion requires at least one decimal conversion method to be available.

