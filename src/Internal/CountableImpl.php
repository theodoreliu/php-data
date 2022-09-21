<?php

declare(strict_types = 1);

namespace Util\Data\Internal;

use Countable;

/**
 * Trait CountableImpl
 *
 * @package Util\Data\Internal
 * @internal
 */
trait CountableImpl {

  // region Data\Internal\Countable

  /**
   * If this instance is empty, returns true, or otherwise returns false.
   *
   * @return bool True if this instance is empty, or otherwise false.
   */
  public function isEmpty(): bool {
    return empty($this->count());
  }

  /**
   * If this instance is not empty, returns true, or otherwise returns false.
   *
   * @return bool True if this instance is not empty, or otherwise false.
   */
  public function isNotEmpty(): bool {
    return !empty($this->count());
  }

  // endregion

  // region Countable

  /**
   * @inheritDoc
   * @see Countable::count()
   */
  abstract public function count(): int;

  // endregion
}
