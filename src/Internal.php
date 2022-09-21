<?php

declare(strict_types = 1);

namespace Util\Data;

use InvalidArgumentException;
use OutOfBoundsException;
use Util\Data;

/**
 * Class Internal
 *
 * @package Util\Data
 * @internal
 */
class Internal {

  /**
   * Internal constructor.
   */
  private function __construct() {
  }

  public static function validateAndHash($value, Data\Type $type) {
    return ['value' => $type->validate($value), 'valueHash' => self::hashValue($value)];
  }

  /**
   * Hashes the specified value into a unique hash string, such that if two values are identical, their hashes will also
   * be identical. Not cryptographically secure.
   *
   * @param mixed $value The specified value to hash.
   *
   * @return string The resolved unique hash string.
   */
  public static function hashValue($value): string {
    if (!isset($value)) {
      return md5('null');
    }

    if (is_scalar($value)) {
      return md5(gettype($value) . '.' . $value);
    }

    if (is_object($value)) {
      return md5('object.' . spl_object_id($value));
    }

    if (is_array($value)) {
      $arrayHashes = [];
      foreach ($value as $key => $subValue) {
        $arrayHashes[$key] = self::hashValue($subValue);
      }

      return md5('array.' . json_encode($arrayHashes, JSON_THROW_ON_ERROR, 512));
    }

    static $memoizedHashes = [];
    $hash = array_search($value, $memoizedHashes, true) ?: md5('other.' . uniqid(mt_rand(), true));
    $memoizedHashes[$hash] = $value;

    return $hash;
  }

  /**
   * Returns an array of the specified elements, keyed by the hash of the respective values.
   *
   * @param array $elements The specified elements.
   *
   * @return array The resolved array.
   */
  public static function byHash(array $elements): array {
    $return = [];
    foreach ($elements as $element) {
      $elementHash = self::hashValue($element);
      if (!array_key_exists($elementHash, $return)) {
        $return[$elementHash] = $element;
      }
    }

    return $return;
  }

  /**
   * Normalizes a specified index to a value not less than 0 and less than the specified max.
   *
   * @param int $index The specified index.
   * @param int $max   The specified max.
   *
   * @return int The normalized index.
   * @throws InvalidArgumentException If the specified max is not positive.
   * @throws OutOFBoundsException If the specified index is less than the negative of the specified max, or not less than the specified max.
   */
  public static function normalizeSequentialIndex(int $index, int $max): int {
    if ($max < 0) {
      throw new InvalidArgumentException(self::class . "::normalizeSequentialIndex(): Expected input \$max to be positive, actually {$max}.");
    }

    if (-$max <= $index && $index < $max) {
      return $index + ($index < 0 ? $max : 0);
    }

    throw new OutOfBoundsException(self::class . "::normalizeSequentialIndex(): Expected input \$index to be in range [-\$max, \$max), actually {$index} (\$max = {$max}).");
  }
}
