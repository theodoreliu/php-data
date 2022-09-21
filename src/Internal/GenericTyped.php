<?php

declare(strict_types = 1);

namespace Util\Data\Internal;

use Util\Data;

/**
 * Interface GenericTyped
 *
 * @package Util\Data\Internal
 * @internal
 */
interface GenericTyped {

  /**
   * Returns the expected Type for elements contained in this data structure.
   *
   * @return Data\Type
   */
  public function getType(): Data\Type;
}
