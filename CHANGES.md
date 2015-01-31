# Changelog #

## v1.1.0 (2015-01-31) ##

  * Improvements in code quality and documentation
  * DecimalConverter now uses GMP for conversion when possible
  * The NumberBase now uses an instance of DigitList\DigitList to represent the
    list of digits used by the number base.
  * Added BaseConverter::baseConvert() static method as a convenient replacement
    for base_convert()
  * Invalid digits now cause an DigitList\InvalidDigitException to be thrown

## v1.0.1 (2014-05-16) ##

  * Some clean up and optimization of the NumberBase class
  * Fixed missing case sensitivity in some NumberBases initialized with integer.
  * Added GMP extension to composer requirements

## v1.0.2 (2014-06-01) ##

  * Code cleanup and documentation fixes
