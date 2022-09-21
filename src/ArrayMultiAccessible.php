<?php

declare(strict_types = 1);

namespace Util\Data;

use ArrayAccess;
use ArrayIterator;
use Util\Data;

/**
 * Class ArrayMultiAccessible
 *
 * @package Util\Data
 */
class ArrayMultiAccessible implements ArrayAccess {

  private ArrayAccess $array;

  /**
   * ArrayMultiAccessible constructor.
   *
   * @param ArrayAccess $array
   */
  private function __construct(ArrayAccess $array) {
    $this->array = $array;
  }

  /**
   * TODO: INSERT DOCUMENTATION HERE
   *
   * @param array $array
   *
   * @return static
   */
  public static function ofArray(array $array): self {
    return new self(new ArrayIterator($array));
  }

  // region ArrayAccess

  /**
   * @inheritDoc
   */
  public function offsetExists($offset): bool {
    Data\Type::iterable()->validate($offset);
    foreach ($offset as $key) {
      if (!isset($this->array[$key])) {
        return false;
      }
    }

    return true;
  }

  /**
   * @inheritDoc
   */
  public function offsetGet($offset) {
    Data\Type::iterable()->validate($offset);
    $result = [];
    foreach ($offset as $resultKey => $key) {
      $result[$resultKey] = $this->array[$key] ?? null;
    }

    return $result;
  }

  /**
   * @inheritDoc
   */
  public function offsetSet($offset, $value): void {
    Data\Type::iterable()->validate($offset);
    if (empty($offset)) {
      $this->array[] = $value;
    } else {
      foreach ($offset as $key) {
        $this->array[$key] = $value;
      }
    }
  }

  /**
   * @inheritDoc
   */
  public function offsetUnset($offset): void {
    Data\Type::iterable()->validate($offset);
    foreach ($offset as $key) {
      unset($this->array[$key]);
    }
  }

  // endregion
}
