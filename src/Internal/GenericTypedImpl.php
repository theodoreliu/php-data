<?php

declare(strict_types = 1);

namespace Util\Data\Internal;

use Util\Data;

/**
 * Trait GenericTypedImpl
 *
 * @package Util\Data\Internal
 * @internal
 */
trait GenericTypedImpl {

  private Data\Type $type;

  /**
   * Returns the expected Type for elements contained in this data structure.
   *
   * @return Data\Type The expected Type for elements contained in this data structure.
   * @see Data\Internal\GenericTyped::getType()
   */
  public function getType(): Data\Type {
    return $this->type;
  }
}
