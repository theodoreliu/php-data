<?php

declare(strict_types = 1);

namespace Util\Data\Test;

/**
 * Class StringObject
 *
 * @package Util\Data\Test
 */
class StringObject {

  private string $value;

  /**
   * Integer constructor.
   *
   * @param string $value
   */
  private function __construct(string $value) {
    $this->value = $value;
  }

  /**
   * newInstance.
   *
   * @param string $value Value.
   *
   * @return self
   */
  public static function newInstance(string $value): self {
    return new self($value);
  }

  /**
   * Setter for $this->value.
   *
   * @param string $value
   *
   * @return self
   */
  public function setValue(string $value): self {
    $this->value = $value;

    return $this;
  }

  /**
   * Getter for $this->value.
   *
   * @return string
   */
  public function getValue(): string {
    return $this->value;
  }

  /**
   * __toString.
   *
   * @return string
   */
  public function __toString(): string {
    return $this->value;
  }
}
