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
 * Class Map
 *
 * @package Util\Data
 */
final class Map implements Data\Internal\Collectible, Data\Internal\GenericTyped, ArrayAccess, Countable, JsonSerializable {
  use Data\Internal\GenericTypedImpl;

  // region Instance functions

  private array $entries;
  private Data\Type $keyType;
  private Data\Type $keyArrayType;
  private Data\Type $valueType;
  private Data\Type $valueArrayType;

  /**
   * Map constructor.
   *
   * @param Data\Type $keyType   The specified expected Type for all keys contained in this Map.
   * @param Data\Type $valueType The specified expected Type for all values contained in this Map.
   */
  private function __construct(Data\Type $keyType, Data\Type $valueType) {
    $this->entries = [];
    $this->type = Data\Type::tuple($keyType, $valueType);
    [$this->keyType, $this->keyArrayType] = [$keyType, Data\Type::arrayOf($keyType)];
    [$this->valueType, $this->valueArrayType] = [$valueType, Data\Type::arrayOf($valueType)];
  }

  /**
   * Returns the expected Type for all keys contained in this Map.
   *
   * @return Data\Type The expected Type for all keys contained in this Map.
   */
  public function getKeyType(): Data\Type {
    return $this->keyType;
  }

  /**
   * Returns the expected Type for all values contained in this Map.
   *
   * @return Data\Type The expected Type for all values contained in this Map.
   */
  public function getValueType(): Data\Type {
    return $this->valueType;
  }

  /**
   * Returns a Map instance. All keys and values contained in this Map must be of the specified Types.
   *
   * @param Data\Type $keyType   The specified expected Type for all keys contained in this Map.
   * @param Data\Type $valueType The specified expected Type for all values contained in this Map.
   *
   * @return self The resolved Map instance.
   */
  public static function ofType(Data\Type $keyType, Data\Type $valueType): self {
    return new self($keyType, $valueType);
  }

  // endregion

  // region Chain functions

  /**
   * Removes all of the keys and values from this Map.
   *
   * @return self The resolved Map for chaining purposes.
   */
  public function clear(): self {
    $this->entries = [];

    return $this;
  }

  /**
   * Performs the specified action for each entry in this Map, in the order of iteration, until all entries have been
   * processed, or the action throws an Exception. Exceptions thrown are relayed to the caller.
   *
   * @param callable $action The specified action.
   *
   * @return self The resolved Map for chaining purposes.
   */
  public function foreach(callable $action): self {
    foreach ($this->entries as $keyHash => ['key' => $key, 'valueHash' => $valueHash, 'value' => $value]) {
      $action($key, $value);
    }

    return $this;
  }

  /**
   * Associates the specified value with the specified key in this Map. If this Map previously contained a mapping for the specified key, the old value is replaced by the specified value.
   *
   * @param mixed $key   The specified key.
   * @param mixed $value The specified value.
   *
   * @return self The resolved Map for chaining purposes.
   * @throws TypeError If a specified key or value is of an invalid Type.
   */
  public function with($key, $value): self {
    $this->put($key, $value);

    return $this;
  }

  /**
   * Associates the specified value with the specified key in this Map, if no such mapping already exists.
   *
   * @param mixed $key   The specified key.
   * @param mixed $value The specified value.
   *
   * @return self The resolved Map for chaining purposes.
   * @throws TypeError If a specified key or value is of an invalid Type.
   */
  public function withIfAbsent($key, $value): self {
    $this->putIfAbsent($key, $value);

    return $this;
  }

  // endregion

  // region Element functions

  /**
   * Attempts to compute a mapping for the specified key and its currently mapped value (or null if there is no current mapping).
   *
   * @param mixed    $key               The specified key.
   * @param callable $remappingFunction The specified mapping function.
   *
   * @return mixed|null The current (existing or computed) value associated with the specified key, or otherwise null.
   * @throws TypeError If the specified key or the new value is of an invalid Type.
   */
  public function compute($key, callable $remappingFunction) {
    $oldValue = $this->get($key);
    $newValue = (fn($key, $value) => $remappingFunction($key, $value))($key, $oldValue);

    if (isset($newValue)) {
      $this->put($key, $newValue);
    } elseif ($this->containsKey($key)) {
      $this->removeAt($key);
    }

    return $this->get($key);
  }

  /**
   * Attempts to compute a mapping for the specified key and its currently mapped value, if such a mapping does not exist.
   *
   * @param mixed    $key             The specified key.
   * @param callable $mappingFunction The specified mapping function.
   *
   * @return mixed|null The current (existing or computed) value associated with the specified key, or otherwise null.
   * @throws TypeError If the specified key or the new value is of an invalid Type.
   */
  public function computeIfAbsent($key, callable $mappingFunction) {
    if (!$this->containsKey($key)) {
      $newValue = $mappingFunction($key);

      if (isset($newValue)) {
        $this->put($key, $newValue);
      }
    }

    return $this->get($key);
  }

  /**
   * Attempts to compute a mapping for the specified key and its currently mapped value, if such a mapping exists.
   *
   * @param mixed    $key               The specified key.
   * @param callable $remappingFunction The specified mapping function.
   *
   * @return mixed|null The current (existing or computed) value associated with the specified key, or otherwise null.
   * @throws TypeError If the specified key or the new value is of an invalid Type.
   */
  public function computeIfPresent($key, callable $remappingFunction) {
    if ($this->containsKey($key)) {
      $newValue = $remappingFunction($key, $this->get($key));

      if (isset($newValue)) {
        $this->put($key, $newValue);
      } else {
        $this->removeAt($key);
      }
    }

    return $this->get($key);
  }

  /**
   * If this Map contains the specified key, returns true, otherwise returns false.
   *
   * @param mixed $element The specified key.
   *
   * @return bool True if this Map contains the specified key, or otherwise false.
   * @throws TypeError If the specified key is of an invalid Type.
   */
  public function containsKey($element): bool {
    return $this->containsKeys($element);
  }

  /**
   * If this Map contains all of the specified keys, returns true, otherwise returns false.
   *
   * @param mixed ...$elements The specified keys.
   *
   * @return bool True if this Map contains all of the specified keys, or otherwise false.
   * @throws TypeError If any of the specified keys are of an invalid Type.
   */
  public function containsKeys(...$elements): bool {
    foreach ($this->keyArrayType->validate($elements) as $inputValue) {
      $inputValueHash = Data\Internal::hashValue($inputValue);
      if (!array_key_exists($inputValueHash, $this->entries)) {
        return false;
      }
    }

    return true;
  }

  /**
   * If this Map contains the specified value, returns true, otherwise returns false.
   *
   * @param mixed $element The specified value.
   *
   * @return bool True if this Map contains the specified value, or otherwise false.
   * @throws TypeError If the specified value is of an invalid Type.
   */
  public function containsValue($element): bool {
    $inputValue = $this->type->validate($element);
    $inputValueHash = Data\Internal::hashValue($inputValue);
    foreach ($this->entries as $keyHash => ['key' => $key, 'valueHash' => $valueHash, 'value' => $value]) {
      if ($inputValueHash === $valueHash) {
        return true;
      }
    }

    return false;
  }

  /**
   * If this Map contains all of the specified values, returns true, otherwise returns false.
   *
   * @param mixed ...$elements The specified values.
   *
   * @return bool True if this Map contains all of the specified values, or otherwise false.
   * @throws TypeError If any of the specified values are of an invalid Type.
   */
  public function containsValues(...$elements): bool {
    $entriesByHash = [];
    foreach ($this->entries as $keyHash => ['key' => $key, 'valueHash' => $valueHash, 'value' => $value]) {
      $entriesByHash[$valueHash] ??= $value;
    }

    foreach ($this->valueArrayType->validate($elements) as $inputValue) {
      $inputValueHash = Data\Internal::hashValue($inputValue);
      if (!array_key_exists($inputValueHash, $entriesByHash)) {
        return false;
      }
    }

    return true;
  }

  /**
   * Returns a Set containing all key-value entries in this Map.
   *
   * @return Data\Set The Set containing all key-value entries in this Map.
   */
  public function entrySet(): Data\Set {
    $set = Data\Set::ofType($this->type);
    foreach ($this->entries as $keyHash => ['key' => $key, 'valueHash' => $valueHash, 'value' => $value]) {
      $set->add([$key, $value]);
    }

    return $set;
  }

  /**
   * Returns the value to which the specified key is mapped, or null if no such mapping exists. Note that the specified key may also be mapped to null.
   *
   * @param mixed $key The specified key.
   *
   * @return mixed|null The value associated with the specified key, if such a value exists, or otherwise null.
   * @throws TypeError If the specified key is of an invalid Type.
   */
  public function get($key) {
    $keyHash = Data\Internal::hashValue($this->keyType->validate($key));

    return $this->entries[$keyHash]['value'] ?? null;
  }

  /**
   * Returns the value to which the specified key is mapped, or the specified default value if no such mapping exists.
   *
   * @param mixed $key          The specified key.
   * @param mixed $defaultValue The specified default value.
   *
   * @return mixed The value associated with the specified key, if such a value exists, or otherwise null.
   * @throws TypeError If a specified key or value is of an invalid Type.
   */
  public function getOrDefault($key, $defaultValue) {
    return $this->containsKey($key) ? $this->get($key) : $this->keyType->validate($defaultValue);
  }

  /**
   * Returns a Set containing all keys in this Map.
   *
   * @return Data\Set The Set containing all keys in this Map.
   */
  public function keySet(): Data\Set {
    $set = Data\Set::ofType($this->keyType);
    foreach ($this->entries as $keyHash => ['key' => $key, 'valueHash' => $valueHash, 'value' => $value]) {
      $set->add($key);
    }

    return $set;
  }

  /**
   * Associates the specified value with the specified key in this Map. If this Map previously contained a mapping for the specified key, the old value is replaced by the specified value.
   *
   * @param mixed $key   The specified key.
   * @param mixed $value The specified value.
   *
   * @return mixed|null The previous value associated with the specified key, if such a value existed, or otherwise null.
   * @throws TypeError If a specified key or value is of an invalid Type.
   */
  public function put($key, $value) {
    $keyHash = Data\Internal::hashValue($this->keyType->validate($key));
    $oldValue = $this->entries[$keyHash]['value'] ?? null;
    $newValue = $this->valueType->validate($value);
    $newValueHash = Data\Internal::hashValue($newValue);
    $this->entries[$keyHash] = ['key' => $key, 'valueHash' => $newValueHash, 'value' => $newValue];

    return $oldValue;
  }

  /**
   * Associates the specified value with the specified key in this Map, if no such mapping already exists.
   *
   * @param mixed $key   The specified key.
   * @param mixed $value The specified value.
   *
   * @return mixed|null The previous value associated with the specified key, if such a value existed, or otherwise null.
   * @throws TypeError If a specified key or value is of an invalid Type.
   */
  public function putIfAbsent($key, $value) {
    return $this->containsKey($key) ? $this->get($key) : $this->put($key, $value);
  }

  /**
   * Removes the mapping for the specified key from this Map, if such a mapping exists.
   *
   * @param mixed $key The specified key.
   *
   * @return mixed|null The previous value associated with the specified key, if such a value existed, or otherwise null.
   * @throws TypeError If the specified key is of an invalid Type.
   */
  public function removeAt($key) {
    $keyHash = Data\Internal::hashValue($this->keyType->validate($key));

    $oldValue = $this->entries[$keyHash]['value'] ?? null;

    if (isset($this->entries[$keyHash])) {
      unset($this->entries[$keyHash]);
    }

    return $oldValue;
  }

  /**
   * Removes the mapping for the specified key from this Map, if the specified key is currently mapped to the specified value.
   *
   * @param mixed $key   The specified key.
   * @param mixed $value The specified value.
   *
   * @return bool True if this Map was changed, or otherwise false.
   * @throws TypeError If a specified key or value is of an invalid Type.
   */
  public function removeIf($key, $value): bool {
    if ($this->containsKey($key) && $this->get($key) === $this->valueType->validate($value)) {
      $this->removeAt($key);

      return true;
    }

    return false;
  }

  /**
   * Replaces the mapping for the specified key to the specified value, only if it is currently mapped to some value.
   *
   * @param mixed $key   The specified key.
   * @param mixed $value The specified value.
   *
   * @return mixed|null The previous value associated with the specified key, if such a value existed, or otherwise null.
   * @throws TypeError If a specified key or value is of an invalid Type.
   */
  public function replace($key, $value) {
    return $this->containsKey($key) ? $this->put($key, $value) : null;
  }

  /**
   * Replaces the mapping for the specified key to the specified value, only if it is currently mapped to the specified current value.
   *
   * @param mixed $key      The specified key.
   * @param mixed $oldValue The specified old value.
   * @param mixed $newValue The specified new value.
   *
   * @return bool True if this Map was changed, or otherwise false.
   * @throws TypeError If a specified key or value is of an invalid Type.
   */
  public function replaceIf($key, $oldValue, $newValue): bool {
    if ($this->containsKey($key) && $this->get($key) === $this->valueType->validate($oldValue)) {
      $this->put($key, $newValue);

      return true;
    }

    return false;
  }

  /**
   * Returns a Sequence containing all values in this Map.
   *
   * @return Data\Sequence A Sequence containing all values in this Map.
   */
  public function valuesSequence(): Data\Sequence {
    $sequence = Data\Sequence::ofType($this->valueType);
    foreach ($this->entries as $keyHash => ['key' => $key, 'valueHash' => $valueHash, 'value' => $value]) {
      $sequence->append($value);
    }

    return $sequence;
  }

  // endregion

  // region ArrayAccess

  /**
   * @inheritDoc
   * @throws TypeError If a specified offset is of an invalid Type.
   */
  public function offsetExists($offset): bool {
    return $this->containsKey($offset);
  }

  /**
   * @inheritDoc
   * @throws TypeError If a specified offset is of an invalid Type.
   */
  public function offsetGet($offset) {
    return $this->getOrDefault($offset, null);
  }

  /**
   * @inheritDoc
   * @throws TypeError If a specified offset or value is of an invalid Type.
   */
  public function offsetSet($offset, $value): void {
    $this->put($offset, $value);
  }

  /**
   * @inheritDoc
   * @throws TypeError If a specified offset is of an invalid Type.
   */
  public function offsetUnset($offset): void {
    $this->removeAt($offset);
  }

  // endregion

  /**
   * If this Map contains no elements, returns true, otherwise returns false.
   *
   * @return bool True if this Map contains no elements, or otherwise false.
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
    foreach ($this->entries as $keyHash => ['key' => $key, 'valueHash' => $valueHash, 'value' => $value]) {
      yield [$key, $value];
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
