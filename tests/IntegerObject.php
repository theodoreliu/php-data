<?php

declare(strict_types = 1);

namespace Util\Data\Test;

/**
 * Class IntegerObject
 *
 * @package Util\Data\Test
 */
class IntegerObject {

  private int $value;

  /**
   * Integer constructor.
   *
   * @param int $value
   */
  private function __construct(int $value) {
    $this->value = $value;
  }

  /**
   * newInstance.
   *
   * @param int $value Value.
   *
   * @return self
   */
  public static function newInstance(int $value): self {
    return new self($value);
  }

  /**
   * Setter for $this->value.
   *
   * @param int $value
   *
   * @return self
   */
  public function setValue(int $value): self {
    $this->value = $value;

    return $this;
  }

  /**
   * Getter for $this->value.
   *
   * @return int
   */
  public function getValue(): int {
    return $this->value;
  }

  /**
   * __toString.
   *
   * @return string
   */
  public function __toString(): string {
    return (string)$this->value;
  }
}
