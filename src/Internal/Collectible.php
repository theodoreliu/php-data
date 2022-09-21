<?php

declare(strict_types = 1);

namespace Util\Data\Internal;

use Generator;
use IteratorAggregate;
use JsonSerializable;
use TypeError;
use Util\Data;

/**
 * Interface Collectible
 *
 * @package Util\Data\Internal
 * @internal
 */
interface Collectible extends IteratorAggregate {

  /**
   * Returns the contents of this Collectible as a Sequence.
   *
   * @return Data\Sequence
   * @throws TypeError
   */
  public function asSequence(): Data\Sequence;

  /**
   * Returns the contents of this Collectible as a Set.
   *
   * @return Data\Set
   * @throws TypeError
   */
  public function asSet(): Data\Set;

  /**
   * Returns the contents of this Collectible as a Stream.
   *
   * @return Data\Stream
   * @throws TypeError
   */
  public function asStream(): Data\Stream;

  /**
   * Yields the contents of this Collectible.
   *
   * @return Generator
   */
  public function asGenerator(): Generator;

  /**
   * Returns the contents of this Collectible as an array.
   *
   * @return array
   */
  public function asArray(): array;
  
  /**
   * @inheritDoc
   * @see IteratorAggregate::getIterator()
   */
  public function getIterator(): Traversable;

  /**
   * @inheritDoc
   * @see JsonSerializable::jsonSerialize()
   */
  public function jsonSerialize(): array;
}
