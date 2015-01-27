<?php

namespace Riimu\Kit\BaseConversion;

/** Interface used to mark all library specific exceptions. */
interface Exception
{

}

/** Thrown whenever a value for invalid digit is requested. */
class InvalidDigitException extends \InvalidArgumentException implements Exception
{

}

/** Thrown if the number bases cannot be used by the conversion strategy. */
class InvalidNumberBaseException extends \InvalidArgumentException implements Exception
{

}
