<?php

declare(strict_types = 1);

namespace Util\Data;

use Countable;
use Generator;
use IteratorAggregate;
use JsonSerializable;
use Traversable;
use TypeError;
use Util\Data;

/**
 * Class Stream
 *
 * @package Util\Data
 */
final class Stream implements Data\Internal\Collectible, Data\Internal\GenericTyped, Countable, JsonSerializable {
  use Data\Internal\GenericTypedImpl;

  // region Instance functions

  private iterable $entries;

  /**
   * Stream constructor.
   *
   * @param iterable  $stream The specified elements iterated through this Stream.
   * @param Data\Type $type   The specified expected Type for all elements iterated through this Stream.
   */
  private function __construct(iterable $stream, Data\Type $type) {
    $this->entries = $stream;
    $this->type = $type;
  }

  /**
   * Returns a Stream instance with no elements.
   *
   * @param Data\Type $type The specified expected Type for all elements iterated through this Stream.
   *
   * @return self The resolved Stream instance.
   */
  public static function empty(Data\Type $type): self {
    return new self([], $type);
  }

  /**
   * Returns a Stream instance using a specified iterable. All elements iterated through this Stream must be of the specified type.
   *
   * @param iterable  $iterable The specified elements iterated through this Stream.
   * @param Data\Type $type     The specified expected Type for all elements iterated through this Stream.
   *
   * @return self The resolved Stream instance.
   * @throws TypeError If any of the specified elements are of an invalid Type.
   */
  public static function generate(iterable $iterable, Data\Type $type): self {
    $generator = static function (iterable $iterable, Data\Type $type): Generator {
      foreach ($iterable as $value) {
        yield $type->validate($value);
      }
    };

    return new self($generator($iterable, $type), $type);
  }

  /**
   * Returns a Stream instance with elements as given by the specified default value, predicate, and increment. All elements iterated through this Stream must be of the specified type.
   *
   * @param mixed     $seed    The specified seed.
   * @param callable  $hasNext The specified predicate.
   * @param callable  $next    The specified increment.
   * @param Data\Type $type    The specified expected Type for all elements iterated through this Stream.
   *
   * @return self The resolved Stream instance.
   * @throws TypeError If any of the specified elements are of an invalid Type.
   */
  public static function iterate($seed, callable $hasNext, callable $next, Data\Type $type): self {
    $generator = static function ($seed, callable $hasNext, callable $next, Data\Type $type): Generator {
      for ($value = $seed; $hasNext($value); $value = $next($value)) {
        yield $type->validate($value);
      }
    };

    return new self($generator($seed, fn($value): bool => $hasNext($value), fn($value) => $next($value), $type), $type);
  }

  // endregion

  // region Chain functions

  /**
   * Manipulates a stream. Appends the specified streams.
   *
   * @param iterable ...$streams The specified streams.
   *
   * @return self The resolved Stream for chaining purposes.
   * @throws TypeError If any of the specified elements are of an invalid Type.
   */
  public function append(iterable ...$streams): self {
    $generator = function (iterable ...$streams): Generator {
      foreach ($streams as $stream) {
        foreach ($stream as $value) {
          yield $this->type->validate($value);
        }
      }
    };

    return new self($generator($this->entries, ...$streams), $this->type);
  }

  /**
   * Manipulates a stream. Skips any element that has already been streamed.
   *
   * @return self The resolved Stream for chaining purposes.
   */
  public function distinct(): self {
    $generator = static function (iterable $stream): Generator {
      $seen = [];
      foreach ($stream as $value) {
        $hash = Data\Internal::hashValue($value);
        if (!($seen[$hash] ?? false)) {
          $seen[$hash] = true;
          yield $value;
        }
      }
    };

    return new self($generator($this->entries), $this->type);
  }

  /**
   * Manipulates a stream. Omits elements that pass the specified predicate, until the specified predicate fails, and
   * streams the rest.
   *
   * @param callable $predicate The specified predicate to test.
   *
   * @return self The resolved Stream for chaining purposes.
   */
  public function dropWhile(callable $predicate): self {
    $generator = static function (iterable $stream, callable $predicate): Generator {
      foreach ($stream as $value) {
        if (($memo ?? false) || ($memo = !$predicate($value))) {
          yield $value;
        }
      }
    };

    return new self($generator($this->entries, fn($value): bool => $predicate($value)), $this->type);
  }

  /**
   * Manipulates a stream. Filters elements using a specified predicate.
   *
   * @param callable $predicate The specified predicate.
   *
   * @return self The resolved Stream for chaining purposes.
   */
  public function filter(callable $predicate): self {
    $generator = static function (iterable $stream, callable $predicate): Generator {
      foreach ($stream as $value) {
        if ($predicate($value)) {
          yield $value;
        }
      }
    };

    return new self($generator($this->entries, fn($value): bool => $predicate($value)), $this->type);
  }

  /**
   * Manipulates a stream with a specified function.
   *
   * @param callable  $mapper The specified function.
   * @param Data\Type $type   The specified expected type for the output.
   *
   * @return self The resolved Stream for chaining purposes.
   * @throws TypeError If any of the specified elements are of an invalid Type.
   */
  public function flatMap(callable $mapper, Data\Type $type): self {
    $generator = static function (iterable $stream, callable $flatMapper, Data\Type $type): Generator {
      foreach ($stream as $value) {
        foreach ($flatMapper($value) as $innerValue) {
          yield $type->validate($innerValue);
        }
      }
    };

    return new self($generator($this->entries, fn($value): iterable => $mapper($value), $type), $type);
  }

  /**
   * Manipulates a stream. Streams a specified number of elements at the front and omits the rest.
   *
   * @param int $threshold The specified number of elements to stream.
   *
   * @return self The resolved Stream for chaining purposes.
   */
  public function limit(int $threshold): self {
    $generator = static function (iterable $stream, int $threshold): Generator {
      foreach ($stream as $value) {
        if (--$threshold < 0) {
          return;
        }

        yield $value;
      }
    };

    return new self($generator($this->entries, max(0, $threshold)), $this->type);
  }

  /**
   * Manipulates a stream with a specified function.
   *
   * @param callable  $mapper The specified function.
   * @param Data\Type $type   The specified expected type for the output.
   *
   * @return self The resolved Stream for chaining purposes.
   * @throws TypeError If any of the specified elements are of an invalid Type.
   */
  public function map(callable $mapper, Data\Type $type): self {
    $generator = static function (iterable $stream, callable $mapper, Data\Type $type): Generator {
      foreach ($stream as $value) {
        yield $type->validate($mapper($value));
      }
    };

    return new self($generator($this->entries, fn($value) => $mapper($value), $type), $type);
  }

  /**
   * Calls a specified function on a stream and passes through the original value.
   *
   * @param callable $action The specified function.
   *
   * @return self The resolved Stream for chaining purposes.
   */
  public function peek(callable $action): self {
    $generator = static function (iterable $stream, callable $action): Generator {
      foreach ($stream as $value) {
        $action($value);
        yield $value;
      }
    };

    return new self($generator($this->entries, fn($value): void => $action($value)), $this->type);
  }

  /**
   * Manipulates a stream. Omits a specified number of elements at the front and streams the rest.
   *
   * @param int $threshold The specified number of elements to skip.
   *
   * @return self The resolved Stream for chaining purposes.
   */
  public function skip(int $threshold): self {
    $generator = static function (iterable $stream, int $threshold): Generator {
      foreach ($stream as $value) {
        if (--$threshold < 0) {
          yield $value;
        }
      }
    };

    return new self($generator($this->entries, max(0, $threshold)), $this->type);
  }

  /**
   * Manipulates a stream. Streams elements that pass the specified predicate, until the specified predicate fails, and
   * omits the rest.
   *
   * @param callable $predicate The specified predicate to test.
   *
   * @return self The resolved Stream for chaining purposes.
   */
  public function takeWhile(callable $predicate): self {
    $generator = static function (iterable $stream, callable $predicate): Generator {
      foreach ($stream as $value) {
        if (!$predicate($value)) {
          return;
        }

        yield $value;
      }
    };

    return new self($generator($this->entries, fn($value): bool => $predicate($value)), $this->type);
  }

  /**
   * Manipulates a stream. Streams streams containing batches of elements in counts no greater than the specified
   * threshold.
   *
   * @param int $threshold The specified threshold.
   *
   * @return self The resolved Stream for chaining purposes.
   */
  public function batch(int $threshold): self {
    $generator = static function (iterable $stream, int $threshold): Generator {
      $memo = [];
      foreach ($stream as $value) {
        $memo[] = $value;
        if (count($memo) >= $threshold) {
          yield $memo;
          $memo = [];
        }
      }

      if (count($memo) > 0) {
        yield $memo;
      }
    };

    return new self($generator($this->entries, max(1, $threshold)), Data\Type::arrayOf($this->type));
  }

  /**
   * Manipulates a stream with a specified operation.
   *
   * @param callable  $operator The specified operation.
   * @param Data\Type $type     The specified expected type for the output. This is not blocking until Stream::enforceTypes() is called.
   *
   * @return self The resolved Stream for chaining purposes.
   * @throws TypeError If any of the specified elements are of an invalid Type.
   */
  public function withOperator(callable $operator, Data\Type $type): self {
    $generator = static function (iterable $stream, callable $operator, Data\Type $type): Generator {
      foreach ($operator($stream) as $value) {
        yield $type->validate($value);
      }
    };

    return new self($generator($this->entries, fn(iterable $stream): iterable => $operator($stream), $type), $type);
  }

  // endregion

  // region Collector functions

  /**
   * Collects a final result from the generator stream. Returns true if all elements match the specified predicate or
   * false otherwise.
   *
   * @param callable $predicate The specified predicate.
   *
   * @return bool The final result.
   */
  public function allMatch(callable $predicate): bool {
    $_predicate = fn($value): bool => $predicate($value);
    foreach ($this->entries as $value) {
      if (!$_predicate($value)) {
        return false;
      }
    }

    return true;
  }

  /**
   * Collects a final result from the generator stream. Returns true if any element matches the specified predicate or
   * false otherwise.
   *
   * @param callable $predicate The specified predicate.
   *
   * @return bool The final result.
   */
  public function anyMatch(callable $predicate): bool {
    $_predicate = fn($value): bool => $predicate($value);
    foreach ($this->entries as $value) {
      if ($_predicate($value)) {
        return true;
      }
    }

    return false;
  }

  /**
   * Collects a final result from the generator stream. Returns an optional containing the first streamed element.
   *
   * @return Data\Optional The final result.
   */
  public function findFirst(): Data\Optional {
    foreach ($this->entries as $value) {
      return Data\Optional::of($value, $this->type);
    }

    return Data\Optional::empty($this->type);
  }

  /**
   * Performs the specified action for each element of this Stream, in the order of iteration, until all elements have
   * been processed, or the action throws an exception. Exceptions thrown are relayed to the caller.
   *
   * @param callable $action The specified action.
   *
   * @return void
   */
  public function foreach(callable $action): void {
    foreach ($this->entries as $value) {
      $action($value);
    }
  }

  /**
   * Collects a final result from the generator stream. Returns an optional containing the maximum value of the stream.
   *
   * @param callable $comparator The specified comparator.
   *
   * @return Data\Optional The final result.
   */
  public function max(callable $comparator = null): Data\Optional {
    $_comparator = $comparator ?? fn($a, $b): int => $a <=> $b;
    $__comparator = fn($a, $b): int => $_comparator($a, $b) <=> 0;

    $memo = null;
    foreach ($this->entries as $value) {
      if (!isset($memo) || ($memo instanceof Data\Optional && $__comparator($value, $memo->getValue()) === 1)) {
        $memo = Data\Optional::of($value, $this->type);
      }
    }

    return $memo ?? Data\Optional::empty($this->type);
  }

  /**
   * Collects a final result from the generator stream. Returns an optional containing the minimum value of the stream.
   *
   * @param callable $comparator The specified comparator.
   *
   * @return Data\Optional The final result.
   */
  public function min(callable $comparator = null): Data\Optional {
    $_comparator = $comparator ?? fn($a, $b): int => $a <=> $b;
    $__comparator = fn($a, $b): int => $_comparator($a, $b) <=> 0;

    $memo = null;
    foreach ($this->entries as $value) {
      if (!isset($memo) || ($memo instanceof Data\Optional && $__comparator($value, $memo->getValue()) === -1)) {
        $memo = Data\Optional::of($value, $this->type);
      }
    }

    return $memo ?? Data\Optional::empty($this->type);
  }

  /**
   * Collects a final result from the generator stream. Returns true if no element matches the specified predicate or
   * false otherwise.
   *
   * @param callable $predicate The specified predicate.
   *
   * @return bool The final result.
   */
  public function noneMatch(callable $predicate): bool {
    $_predicate = fn($value): bool => $predicate($value);
    foreach ($this->entries as $value) {
      if ($_predicate($value)) {
        return false;
      }
    }

    return true;
  }

  /**
   * Collects a final result from the generator stream. Reduces the stream using a specified function and default value.
   *
   * @param callable  $function The specified function.
   * @param mixed     $default  The specified default.
   * @param Data\Type $type     The specified result type.
   *
   * @return mixed The final result.
   * @throws TypeError If any of the specified elements are of an invalid Type.
   */
  public function reduce(callable $function, $default, Data\Type $type) {
    $_reducer = fn($memo, $value) => $function($memo, $value);

    $memo = $default;
    foreach ($this->entries as $value) {
      $memo = $_reducer($memo, $value);
    }

    return $type->validate($memo);
  }

  /**
   * Collects a final result from the generator stream using a specified collector.
   *
   * @param callable $collector The specified collector.
   *
   * @return mixed The final result.
   */
  public function collect(callable $collector) {
    return (fn(iterable $a) => $collector($a))($this->entries);
  }

  // endregion

  // region Countable

  /**
   * @inheritDoc
   * @see Countable::count()
   */
  public function count(): int {
    $count = 0;
    foreach ($this->entries as $value) {
      ++$count;
    }

    return $count;
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
    return $this;
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
    foreach ($this->entries as $value) {
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
