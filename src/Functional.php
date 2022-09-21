<?php

declare(strict_types = 1);

namespace Util\Data;

use Generator;
use JsonSerializable;
use Throwable;
use TypeError;
use Util\Data;

/**
 * Class Functional
 *
 * @package Util\Data
 */
final class Functional {

  /**
   * Functional constructor.
   */
  private function __construct() {
  }

  // region UnaryPredicates

  /**
   * Returns the one-parameter predicate corresponding to the empty() function.
   *
   * @return callable The returned one-parameter predicate.
   */
  public static function empty(): callable {
    return fn($a): bool => empty($a);
  }

  /**
   * Returns the one-parameter predicate corresponding to the !empty() function.
   *
   * @return callable The returned one-parameter predicate.
   */
  public static function notEmpty(): callable {
    return fn($a): bool => !empty($a);
  }

  /**
   * Returns the one-parameter predicate corresponding to the isset() function.
   *
   * @return callable The returned one-parameter predicate.
   */
  public static function isset(): callable {
    return fn($a): bool => isset($a);
  }

  /**
   * Returns the one-parameter predicate corresponding to the !isset() function.
   *
   * @return callable The returned one-parameter predicate.
   */
  public static function isNotSet(): callable {
    return fn($a): bool => !isset($a);
  }

  /**
   * Returns the one-parameter predicate which always returns true.
   *
   * @return callable The returned one-parameter predicate.
   */
  public static function alwaysTrue(): callable {
    return fn($a): bool => true;
  }

  /**
   * Returns the one-parameter predicate which always returns false.
   *
   * @return callable The returned one-parameter predicate.
   */
  public static function alwaysFalse(): callable {
    return fn($a): bool => false;
  }

  /**
   * Returns the one-parameter predicate corresponding to the is_string() function.
   *
   * @return callable The returned one-parameter predicate.
   */
  public static function isString(): callable {
    return fn($a): bool => is_string($a);
  }

  /**
   * Returns the one-parameter predicate corresponding to the is_int() function.
   *
   * @return callable The returned one-parameter predicate.
   */
  public static function isInt(): callable {
    return fn($a): bool => is_int($a);
  }

  /**
   * Returns the one-parameter predicate corresponding to the is_float() function.
   *
   * @return callable The returned one-parameter predicate.
   */
  public static function isFloat(): callable {
    return fn($a): bool => is_float($a);
  }

  /**
   * Returns the one-parameter predicate corresponding to the is_numeric() function.
   *
   * @return callable The returned one-parameter predicate.
   */
  public static function isNumeric(): callable {
    return fn($a): bool => is_numeric($a);
  }

  /**
   * Returns the one-parameter predicate corresponding to the is_bool() function.
   *
   * @return callable The returned one-parameter predicate.
   */
  public static function isBool(): callable {
    return fn($a): bool => is_bool($a);
  }

  /**
   * Returns the one-parameter predicate corresponding to the is_array() function.
   *
   * @return callable The returned one-parameter predicate.
   */
  public static function isArray(): callable {
    return fn($a): bool => is_array($a);
  }

  /**
   * Returns the one-parameter predicate corresponding to the is_resource() function.
   *
   * @return callable The returned one-parameter predicate.
   */
  public static function isResource(): callable {
    return fn($a): bool => is_resource($a);
  }

  /**
   * Returns the one-parameter predicate corresponding to the is_object() function.
   *
   * @return callable The returned one-parameter predicate.
   */
  public static function isObject(): callable {
    return fn($a): bool => is_object($a);
  }

  /**
   * Returns the one-parameter predicate corresponding to the instanceof operator, with the specified class as the second operand.
   *
   * @param string $class The specified class.
   *
   * @return callable The returned one-parameter predicate.
   * @throws TypeError If the specified class is invalid.
   */
  public static function isInstanceOf(string $class): callable {
    if (!class_exists($class)) {
      throw Data\Type::throwError("Cannot instantiate a data encapsulation of type {$class}.");
    }

    return fn($a): bool => $a instanceof $class;
  }

  // endregion

  // region UnaryGenerators

  /**
   * Returns the one-parameter generator taking in an iterable and yielding back its elements.
   *
   * @return callable The returned one-parameter generator.
   */
  public static function generate(): callable {
    return static function (iterable $iterable): Generator {
      foreach ($iterable as $key => $value) {
        yield $key => $value;
      }
    };
  }

  // endregion

  // region UnaryFunctions

  /**
   * Returns the one-parameter function taking in an iterable and yielding back its elements.
   *
   * @return mixed Actually throws the throwable supplied by the specified supplier.
   */
  public static function json(): callable {
    static $function;

    return $function ??= fn($value) => $value instanceof JsonSerializable ? $value->jsonSerialize() : $value;
  }

  // endregion

  // region Throwable

  /**
   * Throws the throwable supplied by the specified callable.
   *
   * @param callable $callable The specified no-parameter, Throwable-valued callable.
   *
   * @return mixed Actually throws the throwable supplied by the specified supplier.
   * @throws Throwable The throwable supplied by the specified callable.
   */
  public static function throw(callable $callable) {
    throw (fn(): Throwable => $callable())();
  }

  // endregion

  // region BiPredicates

  /**
   * Returns the two-parameter predicate corresponding to the == operator.
   *
   * @return callable The returned two-parameter predicate.
   */
  public static function equal(): callable {
    return fn($a, $b): bool => $a == $b;
  }

  /**
   * Returns the two-parameter predicate corresponding to the != operator.
   *
   * @return callable The returned two-parameter predicate.
   */
  public static function notEqual(): callable {
    return fn($a, $b): bool => $a != $b;
  }

  /**
   * Returns the two-parameter predicate corresponding to the === operator.
   *
   * @return callable The returned two-parameter predicate.
   */
  public static function identical(): callable {
    return fn($a, $b): bool => $a === $b;
  }

  /**
   * Returns the two-parameter predicate corresponding to the !== operator.
   *
   * @return callable The returned two-parameter predicate.
   */
  public static function notIdentical(): callable {
    return fn($a, $b): bool => $a !== $b;
  }

  /**
   * Returns the two-parameter predicate corresponding to the < operator.
   *
   * @return callable The returned two-parameter predicate.
   */
  public static function lessThan(): callable {
    return fn($a, $b): bool => $a < $b;
  }

  /**
   * Returns the two-parameter predicate corresponding to the >= operator.
   *
   * @return callable The returned two-parameter predicate.
   */
  public static function notLessThan(): callable {
    return fn($a, $b): bool => $a >= $b;
  }

  /**
   * Returns the two-parameter predicate corresponding to the > operator.
   *
   * @return callable The returned two-parameter predicate.
   */
  public static function greaterThan(): callable {
    return fn($a, $b): bool => $a > $b;
  }

  /**
   * Returns the two-parameter predicate corresponding to the <= operator.
   *
   * @return callable The returned two-parameter predicate.
   */
  public static function notGreaterThan(): callable {
    return fn($a, $b): bool => $a <= $b;
  }

  // endregion

  // region Comparators

  /**
   * Returns a default comparator.
   *
   * @return callable The returned comparator.
   */
  public static function defaultComparator(): callable {
    return fn($a, $b): int => $a <=> $b;
  }

  // endregion
}
