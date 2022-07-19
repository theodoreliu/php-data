<?php

declare(strict_types = 1);

namespace Util\Data;

use Generator;
use IteratorAggregate;
use JsonSerializable;
use Throwable;
use Traversable;
use TypeError;
use UnderflowException;
use Util\Data;

/**
 * Class Optional
 *
 * @package Util\Data
 */
final class Optional implements Data\Internal\Collectible, Data\Internal\GenericTyped, JsonSerializable {
  use Data\Internal\GenericTypedImpl;

  // region Instance functions

  private $value;

  /**
   * Optional constructor.
   *
   * @param mixed     $value The specified element contained in this Optional.
   * @param Data\Type $type  The specified expected Type for the element contained in this Optional, when present.
   */
  private function __construct($value, Data\Type $type) {
    $this->value = $value;
    $this->type = $type;
  }

  /**
   * Returns an empty Optional instance.
   *
   * @param Data\Type $type The specified expected Type for the element contained in this Optional, when present.
   *
   * @return self The resolved Optional instance.
   */
  public static function empty(Data\Type $type): self {
    return new self(null, $type);
  }

  /**
   * Returns an Optional instance describing the specified non-nullable element.
   *
   * @param mixed     $value The specified non-nullable element.
   * @param Data\Type $type  The specified expected Type for the element contained in this Optional, when present.
   *
   * @return self The resolved Optional instance.
   * @throws TypeError If the specified value is invalid.
   */
  public static function of($value, Data\Type $type): self {
    return new self($type->validate($value), $type);
  }

  /**
   * If the specified element is non-null, returns an Optional describing the element, or otherwise returns an empty Optional.
   *
   * @param mixed     $value The specified element.
   * @param Data\Type $type  The specified expected Type for the element contained in this Optional, when present.
   *
   * @return self The resolved Optional instance.
   * @throws TypeError If the specified value is invalid.
   */
  public static function ofNullable($value, Data\Type $type): self {
    return new self($value !== null ? $type->validate($value) : null, $type);
  }

  // endregion

  // region Chain functions

  /**
   * If an element is present in this Optional, and the element matches the specified predicate, returns an Optional
   * containing the element, or otherwise returns an empty Optional.
   *
   * @param callable $predicate The specified predicate.
   *
   * @return self The resolved Optional for chaining purposes.
   */
  public function filter(callable $predicate): self {
    $_predicate = fn($value): bool => $predicate($value);

    return $this->isPresent() && !$_predicate($this->value) ? self::empty($this->type) : $this;
  }

  /**
   * If an element is present in this Optional, returns the result of applying the given Optional-bearing mapping
   * function to the element, or otherwise returns an empty Optional.
   *
   * @param callable  $mapper The specified Optional-bearing mapping function.
   * @param Data\Type $type   The specified expected type for the output.
   *
   * @return self The resolved Optional for chaining purposes.
   * @throws TypeError If the mapped value is not of a valid type.
   */
  public function flatMap(callable $mapper, Data\Type $type): self {
    if ($this->isPresent()) {
      $_mapper = fn($value): self => $mapper($value);

      return self::ofNullable($_mapper($this->value)->value, $type);
    }

    return self::empty($type);
  }

  /**
   * If an element is present in this Optional, returns an Optional describing the result of applying the given mapping
   * function to the element, otherwise returns an empty Optional.
   *
   * @param callable  $mapper The specified mapping function.
   * @param Data\Type $type   The specified expected type for the output.
   *
   * @return self The resolved Optional for chaining purposes.
   * @throws TypeError If the mapped value is not of a valid type.
   */
  public function map(callable $mapper, Data\Type $type): self {
    return $this->isPresent() ? self::ofNullable($mapper($this->value), $type) : self::empty($type);
  }

  /**
   * If an element is present in this Optional, returns an Optional containing the element, or otherwise returns an
   * Optional produced by the specified Optional-supplying function.
   *
   * @param callable $supplier The specified Optional-supplying function.
   *
   * @return self The resolved Optional for chaining purposes.
   * @throws TypeError If the mapped value is not of a valid type.
   */
  public function or(callable $supplier): self {
    if ($this->isPresent()) {
      return $this;
    }

    $_supplier = fn(): self => $supplier();

    return self::ofNullable($_supplier()->value, $this->type);
  }

  // endregion

  // region Collector functions

  /**
   * If an element is present in this Optional, returns the element, or otherwise throws an UnderflowException.
   *
   * @return mixed The element contained in this Optional instance.
   * @throws UnderflowException If no element is contained in this Optional instance.
   */
  public function getValue() {
    if ($this->isPresent()) {
      return $this->value;
    }

    throw new UnderflowException('Optional::getValue(): No value present.');
  }

  /**
   * If an element is present in this Optional, performs the specified action with the element, or otherwise does nothing.
   *
   * @param callable $action The specified action.
   *
   * @return void
   */
  public function ifPresent(callable $action): void {
    if ($this->isPresent()) {
      $action($this->value);
    }
  }

  /**
   * If an element is present in this Optional, performs the specified action with the element, or otherwise performs the specified empty-based action.
   *
   * @param callable $action      The specified action.
   * @param callable $emptyAction The specified empty-based action.
   *
   * @return void
   */
  public function ifPresentOrElse(callable $action, callable $emptyAction): void {
    if ($this->isPresent()) {
      $action($this->value);
    } else {
      $emptyAction();
    }
  }

  /**
   * If an element is not present in this Optional, returns true, or otherwise returns false.
   *
   * @return bool True if an element is not present in this Optional, or false otherwise.
   */
  public function isEmpty(): bool {
    return $this->value === null;
  }

  /**
   * If an element is present in this Optional, returns true, or otherwise returns false.
   *
   * @return bool True if an element is present in this Optional, or false otherwise.
   */
  public function isPresent(): bool {
    return $this->value !== null;
  }

  /**
   * If an element is present in this Optional, returns the element, or otherwise returns the specified default value.
   *
   * @param mixed $other The specified default value.
   *
   * @return mixed The resolved value.
   * @throws TypeError If the default value is not of a valid type.
   */
  public function orElse($other) {
    return $this->value ?? ($other !== null ? $this->type->validate($other) : null);
  }

  /**
   * If an element is present in this Optional, returns the element, or otherwise returns the result produced by the specified supplying function.
   *
   * @param callable $supplier The specified supplying function.
   *
   * @return mixed The resolved value.
   * @throws TypeError If the default value is not of a valid type.
   */
  public function orElseGet(callable $supplier) {
    return $this->value ?? $this->orElse((fn() => $supplier())());
  }

  /**
   * If an element is present in this Optional, returns the element, or otherwise throws the Throwable produced by the specified Throwable-supplying function.
   *
   * @param callable $exceptionSupplier The specified Throwable-supplying function.
   *
   * @return mixed The resolved value.
   * @throws Throwable If there is no value present.
   */
  public function orElseThrow(callable $exceptionSupplier) {
    return $this->value ?? Data\Functional::throw($exceptionSupplier);
  }

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
    if ($this->isPresent()) {
      yield $this->value;
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
