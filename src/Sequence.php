<?php

declare(strict_types = 1);

namespace Util\Data;

use ArrayAccess;
use Countable;
use Generator;
use IteratorAggregate;
use JsonSerializable;
use OutOfBoundsException;
use Traversable;
use TypeError;
use Util\Data;

/**
 * Class Sequence
 *
 * @package Util\Data
 */
final class Sequence implements Data\Internal\Collectible, Data\Internal\GenericTyped, ArrayAccess, Countable, JsonSerializable {
  use Data\Internal\GenericTypedImpl;

  // region Instance functions

  private array $entries;
  private Data\Type $arrayType;

  /**
   * Sequence constructor.
   *
   * @param Data\Type $type The specified expected Type for all elements contained in this Sequence.
   */
  private function __construct(Data\Type $type) {
    $this->entries = [];
    [$this->type, $this->arrayType] = [$type, Data\Type::arrayOf($type)];
  }

  /**
   * Returns a Sequence instance. All elements contained in this Sequence must be of the specified Type.
   *
   * @param Data\Type $type The specified expected Type for all elements contained in this Sequence.
   *
   * @return self The resolved Sequence instance.
   */
  public static function ofType(Data\Type $type): self {
    return new self($type);
  }

  // endregion

  // region Chain functions

  /**
   * Removes all of the elements from this Sequence.
   *
   * @return self The resolved Sequence for chaining purposes.
   */
  public function clear(): self {
    $this->entries = [];

    return $this;
  }

  /**
   * Performs the specified action for each element of this Sequence, in the order of iteration, until all elements have
   * been processed, or the action throws an exception. Exceptions thrown are relayed to the caller.
   *
   * @param callable $action The specified action.
   *
   * @return self The resolved Sequence for chaining purposes.
   */
  public function foreach(callable $action): self {
    foreach ($this->entries as $index => ['valueHash' => $valueHash, 'value' => $value]) {
      $action($value);
    }

    return $this;
  }

  /**
   * Adds all of the specified elements to the end of this Sequence.
   *
   * @param mixed ...$elements The specified elements.
   *
   * @return self The resolved Sequence for chaining purposes.
   * @throws TypeError If any of the specified elements are of an invalid Type.
   */
  public function withAll(...$elements): self {
    $this->appendAll(...$elements);

    return $this;
  }

  /**
   * Removes from this Sequence all of the specified elements which are present.
   *
   * @param mixed ...$elements The specified elements.
   *
   * @return self The resolved Sequence for chaining purposes.
   * @throws TypeError If any of the specified elements are of an invalid Type.
   */
  public function withNone(...$elements): self {
    $this->removeAll(...$elements);

    return $this;
  }

  /**
   * Retains in this Sequence only the specified elements which are present.
   *
   * @param mixed ...$elements The specified elements.
   *
   * @return self The resolved Sequence for chaining purposes.
   * @throws TypeError If any of the specified elements are of an invalid Type.
   */
  public function withOnly(...$elements): self {
    $this->retainAll(...$elements);

    return $this;
  }

  // endregion

  // region Collector functions

  /**
   * Adds the specified element to the end of this Sequence.
   *
   * @param mixed $element The specified element.
   *
   * @return bool True if this Sequence was changed, or otherwise false.
   * @throws TypeError If the specified element is of an invalid Type.
   */
  public function append($element): bool {
    return $this->insertAll($this->count(), $element);
  }

  /**
   * Adds all of the specified elements to the end of this Sequence.
   *
   * @param mixed ...$elements The specified elements.
   *
   * @return bool True if this Sequence was changed, or otherwise false.
   * @throws TypeError If any of the specified elements are of an invalid Type.
   */
  public function appendAll(...$elements): bool {
    return $this->insertAll($this->count(), ...$elements);
  }

  /**
   * Adds the specified element to the front of this Sequence.
   *
   * @param mixed $element The specified element.
   *
   * @return bool True if this Sequence was changed, or otherwise false.
   * @throws TypeError If the specified element is of an invalid Type.
   */
  public function prepend($element): bool {
    return $this->insertAll(0, $element);
  }

  /**
   * Adds all of the specified elements to the front of this Sequence.
   *
   * @param mixed ...$elements The specified elements.
   *
   * @return bool True if this Sequence was changed, or otherwise false.
   * @throws TypeError If any of the specified elements are of an invalid Type.
   */
  public function prependAll(...$elements): bool {
    return $this->insertAll(0, ...$elements);
  }

  /**
   * Adds the specified element to the specified position in this Sequence.
   *
   * @param int   $index   The specified position.
   * @param mixed $element The specified element.
   *
   * @return bool True if this Sequence was changed, or otherwise false.
   * @throws TypeError If the specified element is of an invalid Type.
   * @throws OutOFBoundsException If the specified position is less than the negative of the count of this Sequence, or greater than the count of this Sequence.
   */
  public function insert(int $index, $element): bool {
    return $this->insertAll($index, $element);
  }

  /**
   * Adds all of the specified elements to the specified position in this Sequence.
   *
   * @param int   $index       The specified position.
   * @param mixed ...$elements The specified elements.
   *
   * @return bool True if this Sequence was changed, or otherwise false.
   * @throws TypeError If any of the specified elements are of an invalid Type.
   * @throws OutOFBoundsException If the specified position is less than the negative of the count of this Sequence, or greater than the count of this Sequence.
   */
  public function insertAll(int $index, ...$elements): bool {
    $normalizedIndex = $index === $this->count() ? $index : Data\Internal::normalizeSequentialIndex($index, $this->count());

    $newEntries = [];
    foreach ($this->arrayType->validate($elements) as $inputValue) {
      $inputValueHash = Data\Internal::hashValue($inputValue);
      $newEntries[] = ['valueHash' => $inputValueHash, 'value' => $inputValue];
    }

    if ($normalizedIndex === 0) {
      $this->entries = [...$newEntries, ...$this->entries];
    } elseif ($normalizedIndex === $this->count()) {
      $this->entries = [...$this->entries, ...$newEntries];
    } else {
      array_splice($this->entries, $normalizedIndex, 0, $newEntries);
    }

    return true;
  }

  /**
   * If this Sequence contains the specified element, returns true, otherwise returns false.
   *
   * @param mixed $element The specified element.
   *
   * @return bool True if this Sequence contains the specified element, or otherwise false.
   * @throws TypeError If the specified element is of an invalid Type.
   */
  public function contains($element): bool {
    // return $this->indexOf($element) !== null;
    $inputValue = $this->type->validate($element);
    $inputValueHash = Data\Internal::hashValue($inputValue);
    foreach ($this->entries as $index => ['valueHash' => $valueHash, 'value' => $value]) {
      if ($inputValueHash === $valueHash) {
        return true;
      }
    }

    return false;
  }

  /**
   * If this Sequence contains all of the specified elements, returns true, otherwise returns false.
   *
   * @param mixed ...$elements The specified elements.
   *
   * @return bool True if this Sequence contains all of the specified elements, or otherwise false.
   * @throws TypeError If any of the specified elements are of an invalid Type.
   */
  public function containsAll(...$elements): bool {
    $entriesByHash = [];
    foreach ($this->entries as $index => ['valueHash' => $valueHash, 'value' => $value]) {
      $entriesByHash[$valueHash] ??= $value;
    }

    foreach ($this->arrayType->validate($elements) as $inputValue) {
      $inputValueHash = Data\Internal::hashValue($inputValue);
      if (!array_key_exists($inputValueHash, $entriesByHash)) {
        return false;
      }
    }

    return true;
  }

  /**
   * Returns the element at the specified position.
   *
   * @param int $index The specified position.
   *
   * @return mixed The element at the specified position.
   * @throws OutOFBoundsException If the specified position is less than the negative of the count of this Sequence, or not less than the count of this Sequence.
   */
  public function get(int $index) {
    $normalizedIndex = Data\Internal::normalizeSequentialIndex($index, $this->count());

    return $this->entries[$normalizedIndex]['value'] ?? null;
  }

  /**
   * If this Sequence contains the specified element, returns the index of the first such occurrence, otherwise returns null.
   *
   * @param mixed $element The specified element.
   *
   * @return int|null The index of the first occurrence of the specified element, or null otherwise
   * @throws TypeError If the specified element is of an invalid Type.
   */
  public function firstIndexOf($element): ?int {
    $inputValue = $this->type->validate($element);
    $inputValueHash = Data\Internal::hashValue($inputValue);
    for ($count = $this->count(), $index = 0; $index < $count; ++$index) {
      if ($inputValueHash === $this->entries[$index]['valueHash']) {
        return $index;
      }
    }

    return null;
  }

  /**
   * If this Sequence contains the specified element, returns the index of the last such occurrence, otherwise returns null.
   *
   * @param mixed $element The specified element.
   *
   * @return int|null The index of the last occurrence of the specified element, or null otherwise
   * @throws TypeError If the specified element is of an invalid Type.
   */
  public function lastIndexOf($element): ?int {
    $inputValue = $this->type->validate($element);
    $inputValueHash = Data\Internal::hashValue($inputValue);
    for ($index = $this->count() - 1; $index >= 0; --$index) {
      if ($inputValueHash === $this->entries[$index]['valueHash']) {
        return $index;
      }
    }

    return null;
  }

  /**
   * Removes the element at the specified position.
   *
   * @param int $index The specified position.
   *
   * @return mixed The element which was at the specified position.
   * @throws OutOFBoundsException If the specified position is less than the negative of the count of this Sequence, or not less than the count of this Sequence.
   */
  public function removeAt(int $index) {
    $normalizedIndex = Data\Internal::normalizeSequentialIndex($index, $this->count());

    return array_splice($this->entries, $normalizedIndex, 1)[0]['value'] ?? null;
  }

  /**
   * Removes the first occurrence of the specified element from this Sequence, if it is present.
   *
   * @param mixed $element The specified element.
   *
   * @return bool True if this Sequence was changed, or otherwise false.
   * @throws TypeError If the specified element is of an invalid Type.
   */
  public function remove($element): bool {
    $inputValue = $this->type->validate($element);
    $inputValueHash = Data\Internal::hashValue($inputValue);
    for ($count = $this->count(), $index = 0; $index < $count; ++$index) {
      if ($inputValueHash === $this->entries[$index]['valueHash']) {
        unset($this->entries[$index]);
        $this->entries = array_values($this->entries);

        return true;
      }
    }

    return false;
  }

  /**
   * Removes from this Sequence all of the specified elements which are present.
   *
   * @param mixed ...$elements The specified elements.
   *
   * @return bool True if this Sequence was changed, or otherwise false.
   * @throws TypeError If any of the specified elements are of an invalid Type.
   */
  public function removeAll(...$elements): bool {
    $inputEntries = Data\Internal::byHash($this->arrayType->validate($elements));
    foreach ($this->entries as $index => ['valueHash' => $valueHash, 'value' => $value]) {
      if (array_key_exists($valueHash, $inputEntries)) {
        unset($this->entries[$index]);
        $result = true;
      }
    }

    if ($result ?? false) {
      $this->entries = array_values($this->entries);

      return true;
    }

    return false;
  }

  /**
   * Replaces the element at the specified position with the specified element, and returns the original element.
   *
   * @param int   $index   The specified position.
   * @param mixed $element The specified element.
   *
   * @return mixed The element which was at the specified position.
   * @throws TypeError If the specified element is of an invalid Type.
   * @throws OutOFBoundsException If the specified position is less than the negative of the count of this Sequence, or not less than the count of this Sequence.
   */
  public function replaceAt(int $index, $element) {
    $normalizedIndex = Data\Internal::normalizeSequentialIndex($index, $this->count());
    $inputValue = $this->type->validate($element);
    $inputValueHash = Data\Internal::hashValue($inputValue);
    $oldValue = $this->entries[$normalizedIndex]['value'] ?? null;
    $this->entries[$normalizedIndex] = ['valueHash' => $inputValueHash, 'value' => $inputValue];

    return $oldValue;
  }

  /**
   * Replaces all elements in this Sequence with the value provided by the specified operator.
   *
   * @param callable $operator The specified operator.
   *
   * @return void
   * @throws TypeError If any of the new elements are of an invalid Type.
   */
  public function replaceAll(callable $operator): void {
    for ($i = 0; $i < $this->count(); ++$i) {
      $newValue = $this->type->validate($operator($this->entries[$i]['value'] ?? null));
      $newValueHash = Data\Internal::hashValue($newValue);
      $this->entries[$i] = ['valueHash' => $newValueHash, 'value' => $newValue];
    }
  }

  /**
   * Retains in this Sequence only the specified elements which are present.
   *
   * @param mixed ...$elements The specified elements.
   *
   * @return bool If the Set was changed, or otherwise false.
   * @throws TypeError If any of the specified elements are of an invalid Type.
   */
  public function retainAll(...$elements): bool {
    $inputEntries = Data\Internal::byHash($this->arrayType->validate($elements));
    foreach ($this->entries as $index => ['valueHash' => $valueHash, 'value' => $value]) {
      if (!array_key_exists($valueHash, $inputEntries)) {
        $result = true;
        unset($this->entries[$valueHash]);
      }
    }

    if ($result ?? false) {
      $this->entries = array_values($this->entries);

      return true;
    }

    return false;
  }

  /**
   * Sorts this Sequence using the specified comparator.
   *
   * @param callable $comparator The specified comparator.
   *
   * @return void
   */
  public function sort(callable $comparator): void {
    usort($this->entries, fn($a, $b): int => $comparator($a['value'], $b['value']));
  }

  /**
   * Returns an array containing the elements contained in the specified position range.
   *
   * @param int $fromIndex The specified starting position.
   * @param int $toIndex   The specified ending position.
   *
   * @return array The elements contained in the specified position range.
   */
  public function subList(int $fromIndex, int $toIndex): array {
    $normalizedFromIndex = Data\Internal::normalizeSequentialIndex($fromIndex, $this->count());
    $normalizedToIndex = Data\Internal::normalizeSequentialIndex($toIndex, $this->count());

    $slice = [];
    foreach (range($normalizedFromIndex, $normalizedToIndex) as $index) {
      $slice[] = $this->entries[$index]['value'] ?? null;
    }

    return $slice;
  }

  // endregion

  // region ArrayAccess

  /**
   * @inheritDoc
   * @throws TypeError If a specified offset is of an invalid Type.
   */
  public function offsetExists($offset): bool {
    return 0 <= Data\Type::int()->validate($offset) && $offset < $this->count();
  }

  /**
   * @inheritDoc
   * @throws TypeError If a specified offset is of an invalid Type.
   */
  public function offsetGet($offset) {
    return $this->get(Data\Type::int()->validate($offset));
  }

  /**
   * @inheritDoc
   * @throws TypeError If a specified offset or value is of an invalid Type.
   */
  public function offsetSet($offset, $value): void {
    isset($offset) ? $this->replaceAt(Data\Type::int()->validate($offset), $value) : $this->append($value);
  }

  /**
   * @inheritDoc
   * @throws TypeError If a specified offset is of an invalid Type.
   */
  public function offsetUnset($offset): void {
    $this->removeAt(Data\Type::int()->validate($offset));
  }

  // endregion

  /**
   * If this Sequence contains no elements, returns true, otherwise returns false.
   *
   * @return bool True if this Sequence contains no elements, or otherwise false.
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
    return $this;
  }

  /**
   * @inheritDoc
   * @see Data\Internal\Collectible::asSet()
   */
  public function asSet(): Data\Set {
    return Data\Set::ofType($this->getType())->withAll(...$this->getIterator());
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
    foreach ($this->entries as $index => ['valueHash' => $valueHash, 'value' => $value]) {
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
