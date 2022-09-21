<?php

declare(strict_types = 1);

namespace Util\Data;

use ArrayAccess;
use Countable;
use Generator;
use IteratorAggregate;
use JsonSerializable;
use Traversable;
use TypeError;
use Util\Data;

/**
 * Class Set
 *
 * @package Util\Data
 */
final class Set implements Data\Internal\Collectible, Data\Internal\GenericTyped, ArrayAccess, Countable, JsonSerializable {
  use Data\Internal\GenericTypedImpl;

  // region Instance functions

  private array $entries;
  private Data\Type $arrayType;

  /**
   * Set constructor.
   *
   * @param Data\Type $type The specified expected Type for all elements contained in this Set.
   */
  private function __construct(Data\Type $type) {
    $this->entries = [];
    [$this->type, $this->arrayType] = [$type, Data\Type::arrayOf($type)];
  }

  /**
   * Returns a Set instance. All elements contained in this Set must be of the specified Type.
   *
   * @param Data\Type $type The specified expected Type for all elements contained in this Set.
   *
   * @return self The resolved Set instance.
   */
  public static function ofType(Data\Type $type): self {
    return new self($type);
  }

  // endregion

  // region Chain functions

  /**
   * Removes all of the elements from this Set.
   *
   * @return self The resolved Set for chaining purposes.
   */
  public function clear(): self {
    $this->entries = [];

    return $this;
  }

  /**
   * Performs the specified action for each element of this Set, in the order of iteration, until all elements have been
   * processed, or the action throws an exception. Exceptions thrown are relayed to the caller.
   *
   * @param callable $action The specified action.
   *
   * @return self The resolved Set for chaining purposes.
   */
  public function foreach(callable $action): self {
    foreach ($this->entries as $valueHash => $value) {
      $action($value);
    }

    return $this;
  }

  /**
   * Adds to this Set all of the specified elements which are not already present.
   *
   * @param mixed ...$elements The specified elements.
   *
   * @return self The resolved Set for chaining purposes.
   * @throws TypeError If any of the specified elements are of an invalid Type.
   */
  public function withAll(...$elements): self {
    $this->addAll(...$elements);

    return $this;
  }

  /**
   * Removes from this Set all of the specified elements which are present.
   *
   * @param mixed ...$elements The specified elements.
   *
   * @return self The resolved Set for chaining purposes.
   * @throws TypeError If any of the specified elements are of an invalid Type.
   */
  public function withNone(...$elements): self {
    $this->removeAll(...$elements);

    return $this;
  }

  /**
   * Retains in this Set only the specified elements which are present.
   *
   * @param mixed ...$elements The specified elements.
   *
   * @return self The resolved Set for chaining purposes.
   * @throws TypeError If any of the specified elements are of an invalid Type.
   */
  public function withOnly(...$elements): self {
    $this->retainAll(...$elements);

    return $this;
  }

  // endregion

  // region Collector functions

  /**
   * Adds the specified element to this Set, if it is not already present.
   *
   * @param mixed $element The specified element.
   *
   * @return bool True if this Set was changed, or otherwise false.
   * @throws TypeError If the specified element is of an invalid Type.
   */
  public function add($element): bool {
    return $this->addAll($element);
  }

  /**
   * Adds to this Set all of the specified elements which are not already present.
   *
   * @param mixed ...$elements The specified elements.
   *
   * @return bool True if this Set was changed, or otherwise false.
   * @throws TypeError If any of the specified elements are of an invalid Type.
   */
  public function addAll(...$elements): bool {
    foreach ($this->arrayType->validate($elements) as $inputValue) {
      $inputValueHash = Data\Internal::hashValue($inputValue);
      if (!array_key_exists($inputValueHash, $this->entries)) {
        $result = true;
        $this->entries[$inputValueHash] = $inputValue;
      }
    }

    return $result ?? false;
  }

  /**
   * If this Set contains the specified element, returns true, otherwise returns false.
   *
   * @param mixed $element The specified element.
   *
   * @return bool True if this Set contains the specified element, or otherwise false.
   * @throws TypeError If the specified element is of an invalid Type.
   */
  public function contains($element): bool {
    return $this->containsAll($element);
  }

  /**
   * If this Set contains all of the specified elements, returns true, otherwise returns false.
   *
   * @param mixed ...$elements The specified elements.
   *
   * @return bool True if this Set contains all of the specified elements, or otherwise false.
   * @throws TypeError If any of the specified elements are of an invalid Type.
   */
  public function containsAll(...$elements): bool {
    foreach ($this->arrayType->validate($elements) as $inputValue) {
      $inputValueHash = Data\Internal::hashValue($inputValue);
      if (!array_key_exists($inputValueHash, $this->entries)) {
        return false;
      }
    }

    return true;
  }

  /**
   * Removes the specified element from this Set, if it is present.
   *
   * @param mixed $element The specified element.
   *
   * @return bool True if this Set was changed, or otherwise false.
   * @throws TypeError If the specified element is of an invalid Type.
   */
  public function remove($element): bool {
    return $this->removeAll($element);
  }

  /**
   * Removes from this Set all of the specified elements which are present.
   *
   * @param mixed ...$elements The specified elements.
   *
   * @return bool True if this Set was changed, or otherwise false.
   * @throws TypeError If any of the specified elements are of an invalid Type.
   */
  public function removeAll(...$elements): bool {
    foreach ($this->arrayType->validate($elements) as $inputValue) {
      $inputValueHash = Data\Internal::hashValue($inputValue);
      if (array_key_exists($inputValueHash, $this->entries)) {
        $result = true;
        unset($this->entries[$inputValueHash]);
      }
    }

    return $result ?? false;
  }

  /**
   * Removes from this Set all of the elements which satisfy the specified predicate.
   *
   * @param callable $filter The specified predicate.
   *
   * @return bool True if this Set was changed, or otherwise false.
   */
  public function removeIf(callable $filter): bool {
    $_filter = fn($value): bool => $filter($value);
    foreach ($this->entries as $valueHash => $value) {
      if ($_filter($value)) {
        $result = true;
        unset($this->entries[$valueHash]);
      }
    }

    return $result ?? false;
  }

  /**
   * Retains in this Set only the specified elements which are present.
   *
   * @param mixed ...$elements The specified elements.
   *
   * @return bool If this Set was changed, or otherwise false.
   * @throws TypeError If any of the specified elements are of an invalid Type.
   */
  public function retainAll(...$elements): bool {
    $inputEntries = Data\Internal::byHash($this->arrayType->validate($elements));
    foreach ($this->entries as $valueHash => $value) {
      if (!array_key_exists($valueHash, $inputEntries)) {
        $result = true;
        unset($this->entries[$valueHash]);
      }
    }

    return $result ?? false;
  }

  /**
   * Retains in this Set only the elements which satisfy the specified predicate.
   *
   * @param callable $filter The specified predicate.
   *
   * @return bool True if this Set was changed, or otherwise false.
   */
  public function retainIf(callable $filter): bool {
    $_filter = fn($value): bool => $filter($value);
    foreach ($this->entries as $valueHash => $value) {
      if (!$_filter($value)) {
        $result = true;
        unset($this->entries[$valueHash]);
      }
    }

    return $result ?? false;
  }

  // endregion

  // region ArrayAccess

  /**
   * @inheritDoc
   * @throws TypeError If a specified offset is of an invalid Type.
   */
  public function offsetExists($offset): bool {
    return $this->containsAll($offset);
  }

  /**
   * @inheritDoc
   * @throws TypeError If a specified offset is of an invalid Type.
   */
  public function offsetGet($offset) {
    return $this->containsAll($offset) ? $offset : null;
  }

  /**
   * @inheritDoc
   * @throws TypeError If a specified offset or value is of an invalid Type.
   */
  public function offsetSet($offset, $value): void {
    $this->addAll($value);
  }

  /**
   * @inheritDoc
   * @throws TypeError If a specified offset is of an invalid Type.
   */
  public function offsetUnset($offset): void {
    $this->removeAll($offset);
  }

  // endregion

  /**
   * If this Set contains no elements, returns true, otherwise returns false.
   *
   * @return bool True if this Set contains no elements, or otherwise false.
   */
  public function isEmpty(): bool {
    return empty($this->entries);
  }

  // region Countable

  /**
   * @inheritDoc
   * @see Countable::count()
   */
  public function count(): int {
    return count($this->entries);
  }

  // endregion

  // region Data\Internal\Collectible

  /**
   * @inheritDoc
   * @see Data\Internal\Collectible::asSequence()
   */
  public function asSequence(): Data\Sequence {
    return Data\Sequence::ofType($this->getType())->withAll(...$this->getIterator());
  }

  /**
   * @inheritDoc
   * @see Data\Internal\Collectible::asSet()
   */
  public function asSet(): Data\Set {
    return $this;
  }

  /**
   * @inheritDoc
   * @see Data\Internal\Collectible::asStream()
   */
  public function asStream(): Data\Stream {
    return Data\Stream::generate($this->getIterator(), $this->getType());
  }

  /**
   * @inheritDoc
   * @see Data\Internal\Collectible::asGenerator()
   */
  public function asGenerator(): Generator {
    yield from $this->getIterator();
  }

  /**
   * @inheritDoc
   * @see Data\Internal\Collectible::asArray()
   */
  public function asArray(): array {
    return [...$this->getIterator()];
  }

  /**
   * @inheritDoc
   * @see IteratorAggregate::getIterator()
   * @see Data\Internal\Collectible::getIterator()
   */
  public function getIterator(): Traversable {
    foreach ($this->entries as $valueHash => $value) {
      yield $value;
    }
  }

  /**
   * @inheritDoc
   * @see JsonSerializable::jsonSerialize()
   * @see Data\Internal\Collectible::jsonSerialize()
   */
  public function jsonSerialize(): array {
    return ['type' => $this->type, 'entries' => [...$this->getIterator()]];
  }

  // endregion
}
