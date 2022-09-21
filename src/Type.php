<?php

declare(strict_types = 1);

namespace Util\Data;

use Closure;
use Generator;
use InvalidArgumentException;
use JsonSerializable;
use OutOfBoundsException;
use Throwable;
use TypeError;
use Util\Data;

/**
 * Class Type
 *
 * @package Util\Data
 */
final class Type implements JsonSerializable {

  /** @var self[] */
  private static array $typeCache = [];

  private Closure $predicate;
  private string $baseName;
  private string $name;
  private array $subTypes;

  /**
   * Type constructor.
   *
   * @param callable $predicate   The predicate used to determine adherence to this Type.
   * @param string   $baseName    The base name of the type represented by this instance.
   * @param self     ...$subTypes The sub-Type referenced by this Type.
   * @throws TypeError If this type has already been instantiated.
   */
  private function __construct(callable $predicate, string $baseName, self ...$subTypes) {
    if (array_key_exists($cacheKey = empty($subTypes) ? $baseName : "{$baseName}<" . self::ids(...$subTypes) . '>', self::$typeCache)) {
      throw self::throwError(self::class . '::__construct(): Colliding type detected: ' . self::$typeCache[$cacheKey]);
    }

    self::$typeCache[$cacheKey] = $this;

    $this->predicate = fn($value): bool => $predicate($value);
    $this->baseName = $baseName;
    $this->name = empty($subTypes) ? $baseName : $baseName . '<' . implode(',', $subTypes) . '>';
    $this->subTypes = $subTypes;
  }

  /**
   * The string representation of this Type.
   *
   * @return string
   */
  public function __toString(): string {
    return $this->name;
  }

  /**
   * Returns the base name of this Type.
   *
   * @return string The base name of this Type.
   */
  public function baseName(): string {
    return $this->baseName;
  }

  /**
   * Returns the sub-Type referenced by this Type at the specified index.
   *
   * @param int $index The specified index.
   *
   * @return self The sub-Type referenced by this Type at the specified index.
   * @throws OutOFBoundsException If the specified index is invalid.
   */
  public function subTypeAt(int $index): self {
    return $this->subTypes[Data\Internal::normalizeSequentialIndex($index, count($this->subTypes))];
  }

  /**
   * Returns the set of sub-Types referenced by this Type.
   *
   * @return self[] The set of sub-Types referenced by this Type.
   */
  public function subTypes(): array {
    return $this->subTypes;
  }

  // region Type functions

  /**
   * Returns a Type instance representing a null value.
   *
   * @return self The resolved Type instance.
   */
  public static function null(): self {
    static $cache;

    return $cache ??= new self(fn($value): bool => !isset($value), 'null');
  }

  /**
   * Returns a Type instance representing a mixed value.
   *
   * @return self The resolved Type instance.
   */
  public static function mixed(): self {
    static $cache;

    return $cache ??= new self(fn($value): bool => true, 'mixed');
  }

  /**
   * Returns a Type instance representing a string.
   *
   * @return self The resolved Type instance.
   */
  public static function string(): self {
    static $cache;

    return $cache ??= new self(fn($value): bool => is_string($value), 'string');
  }

  /**
   * Returns a Type instance representing a int.
   *
   * @return self The resolved Type instance.
   */
  public static function int(): self {
    static $cache;

    return $cache ??= new self(fn($value): bool => is_int($value), 'int');
  }

  /**
   * Returns a Type instance representing a float.
   *
   * @return self The resolved Type instance.
   */
  public static function float(): self {
    static $cache;

    return $cache ??= new self(fn($value): bool => is_float($value), 'float');
  }

  /**
   * Returns a Type instance representing a numeric (int/float).
   *
   * @return self The resolved Type instance.
   */
  public static function numeric(): self {
    static $cache;

    return $cache ??= self::union(self::float(), self::int());
  }

  /**
   * Returns a Type instance representing a bool.
   *
   * @return self The resolved Type instance.
   */
  public static function bool(): self {
    static $cache;

    return $cache ??= new self(fn($value): bool => is_bool($value), 'bool');
  }

  /**
   * Returns a Type instance representing a array.
   *
   * @return self The resolved Type instance.
   */
  public static function array(): self {
    static $cache;

    return $cache ??= new self(fn($value): bool => is_array($value), 'array');
  }

  /**
   * Returns a Type instance representing a resource.
   *
   * @return self The resolved Type instance.
   */
  public static function resource(): self {
    static $cache;

    return $cache ??= new self(fn($value): bool => is_resource($value), 'resource');
  }

  /**
   * Returns a Type instance representing an iterable.
   *
   * @return self The resolved Type instance.
   */
  public static function iterable(): self {
    static $cache;

    return $cache ??= new self(fn($value): bool => is_iterable($value), 'iterable');
  }

  /**
   * Returns a Type instance representing a callable.
   *
   * @return self The resolved Type instance.
   */
  public static function callable(): self {
    static $cache;

    return $cache ??= new self(fn($value): bool => is_callable($value), 'callable');
  }

  /**
   * Returns a Type instance representing an object.
   *
   * @return self The resolved Type instance.
   */
  public static function object(): self {
    static $cache;

    return $cache ??= new self(fn($value): bool => is_object($value), 'object');
  }

  /**
   * Returns a Type instance representing the specified class.
   *
   * @param string $class       The specified class.
   * @param self   ...$subTypes The specified sub-Types referenced by this Type.
   *
   * @return self The resolved Type instance.
   * @throws TypeError If the specified class is invalid.
   */
  public static function ofClass(string $class, self ...$subTypes): self {
    if (!class_exists($class)) {
      throw self::throwError(self::class . "::ofClass(): Specified class {$class} does not exist.");
    }

    static $cache = [];

    // TODO: Check sub-Types.
    return $cache[$class] ??= new self(fn($value): bool => $value instanceof $class, $class, ...$subTypes);
  }

  // endregion

  // region Composition functions

  /**
   * Returns a Type instance representing the tuple of the specified ordered set of Types.
   *
   * @param self ...$types The specified ordered set of Types.
   *
   * @return self The resolved Type instance.
   */
  public static function tuple(self ...$types): self {
    static $cache = [];

    if (isset($cache[$cacheKey = self::ids(...$types)])) {
      return $cache[$cacheKey];
    }

    $predicate = static function ($values) use ($types): bool {
      foreach (array_values($values) as $key => $value) {
        if (!$types[$key]->isValid($value)) {
          return false;
        }
      }

      return true;
    };

    return $cache[$cacheKey] = new self(fn($values): bool => is_array($values) && count($values) === count($types) && $predicate($values), 'tuple', ...$types);
  }

  /**
   * Returns a Type instance representing an array of a specified Type.
   *
   * @param self $type The specified Type.
   *
   * @return self The resolved Type instance.
   */
  public static function arrayOf(self $type): self {
    static $cache = [];

    if (isset($cache[$cacheKey = $type->id()])) {
      return $cache[$cacheKey];
    }

    $predicate = static function ($values) use ($type): bool {
      foreach ($values as $value) {
        if (!$type->isValid($value)) {
          return false;
        }
      }

      return true;
    };

    return $cache[$cacheKey] = new self(fn($values): bool => is_array($values) && $predicate($values), 'array', $type);
  }

  /**
   * Returns a Type instance representing the union of the specified unordered set of Types.
   *
   * @param self ...$types The specified unordered set of Types.
   *
   * @return self The resolved Type instance.
   */
  public static function union(self ...$types): self {
    $flatten = static function (Type ...$types) use (&$flatten): Generator {
      foreach ($types as $type) {
        yield from $type->baseName() === 'union' ? $flatten(...$type->subTypes()) : [$type];
      }
    };

    /** @var self[] $filteredTypes */
    $filteredTypes = [];
    foreach ($flatten(...$types) as $type) {
      if (self::mixed() === $type) {
        return $type;
      }

      $filteredTypes[$type->id()] ??= $type;
    }

    if (count($filteredTypes) <= 1) {
      return reset($filteredTypes) ?: self::null();
    }

    ksort($filteredTypes);

    static $cache = [];

    if (isset($cache[$cacheKey = self::ids(...$filteredTypes)])) {
      return $cache[$cacheKey];
    }

    $predicate = static function ($value) use ($filteredTypes): bool {
      foreach ($filteredTypes as $type) {
        if ($type->isValid($value)) {
          return true;
        }
      }

      return false;
    };

    return $cache[$cacheKey] = new self($predicate, 'union', ...$filteredTypes);
  }

  /**
   * Returns a Type instance representing the intersection of the specified unordered set of Types.
   *
   * @param self ...$types The specified unordered set of Types.
   *
   * @return self The resolved Type instance.
   */
  public static function intersection(self ...$types): self {
    $flatten = static function (Type ...$types) use (&$flatten): Generator {
      foreach ($types as $type) {
        yield from $type->baseName() === 'intersection' ? $flatten(...$type->subTypes()) : [$type];
      }
    };

    /** @var self[] $filteredTypes */
    $filteredTypes = [];
    foreach ($flatten(...$types) as $type) {
      if (self::null() === $type) {
        return $type;
      }

      $filteredTypes[$type->id()] ??= $type;
    }

    if (count($filteredTypes) <= 1) {
      return reset($filteredTypes) ?: self::mixed();
    }

    ksort($filteredTypes);

    static $cache = [];

    if (isset($cache[$cacheKey = self::ids(...$filteredTypes)])) {
      return $cache[$cacheKey];
    }

    $predicate = static function ($value) use ($filteredTypes): bool {
      foreach ($filteredTypes as $type) {
        if (!$type->isValid($value)) {
          return false;
        }
      }

      return true;
    };

    return $cache[$cacheKey] = new self($predicate, 'intersection', ...$filteredTypes);
  }

  /**
   * Returns a Type instance representing a nullable version of a specified Type.
   *
   * @param self $type The specified Type.
   *
   * @return self The resolved Type instance.
   */
  public static function nullable(self $type): self {
    static $cache = [];

    return $cache[$type->id()] ??= self::union($type, self::null());
  }

  /**
   * Returns a Type instance representing a Map of a specified key and value Type.
   *
   * @param self $keyType   The specified key Type.
   * @param self $valueType The specified value Type.
   *
   * @return self The resolved Type instance.
   */
  public static function map(self $keyType, self $valueType): self {
    static $cache = [];

    return $cache["{$keyType->id()},{$valueType->id()}"] ??= new self(fn($value): bool => $value instanceof Data\Map && $value->getKeyType() === $keyType && $value->getValueType() === $valueType, Data\Map::class, $keyType, $valueType);
  }

  /**
   * Returns a Type instance representing an Optional of a specified Type.
   *
   * @param self $type The specified Type.
   *
   * @return self The resolved Type instance.
   */
  public static function optional(self $type): self {
    static $cache = [];

    return $cache[$type->id()] ??= new self(fn($value): bool => $value instanceof Data\Optional && $value->getType() === $type, Data\Optional::class, $type);
  }

  /**
   * Returns a Type instance representing a Sequence of a specified Type.
   *
   * @param self $type The specified Type.
   *
   * @return self The resolved Type instance.
   */
  public static function sequence(self $type): self {
    static $cache = [];

    return $cache[$type->id()] ??= new self(fn($value): bool => $value instanceof Data\Sequence && $value->getType() === $type, Data\Sequence::class, $type);
  }

  /**
   * Returns a Type instance representing a Set of a specified Type.
   *
   * @param self $type The specified Type.
   *
   * @return self The resolved Type instance.
   */
  public static function set(self $type): self {
    static $cache = [];

    return $cache[$type->id()] ??= new self(fn($value): bool => $value instanceof Data\Set && $value->getType() === $type, Data\Set::class, $type);
  }

  /**
   * Returns a Type instance representing a Stream of a specified Type.
   *
   * @param self $type The specified Type.
   *
   * @return self The resolved Type instance.
   */
  public static function stream(self $type): self {
    static $cache = [];

    return $cache[$type->id()] ??= new self(fn($value): bool => $value instanceof Data\Stream && $value->getType() === $type, Data\Stream::class, $type);
  }

  // endregion

  // region Chain functions

  /**
   * Returns a Type instance representing a tuple of $this of the specified length.
   *
   * @param int $length The specified length.
   *
   * @return self The resolved Type instance.
   */
  public function intoNTuple(int $length): self {
    return self::tuple(...array_fill(0, $length, $this));
  }

  /**
   * Returns a Type instance representing an array of $this.
   *
   * @return self The resolved Type instance.
   */
  public function intoArray(): self {
    return self::arrayOf($this);
  }

  /**
   * Returns a Type instance representing the union of $this and the specified unordered set of Types.
   *
   * @param self ...$types The specified unordered set of Types.
   *
   * @return self The resolved Type instance.
   */
  public function or(self ...$types): self {
    return self::union($this, ...$types);
  }

  /**
   * Returns a Type instance representing the intersection of $this and the specified unordered set of Types.
   *
   * @param self ...$types The specified unordered set of Types.
   *
   * @return self The resolved Type instance.
   */
  public function and(self ...$types): self {
    return self::intersection($this, ...$types);
  }

  /**
   * Returns a Type instance representing a Optional of $this.
   *
   * @return self The resolved Type instance.
   */
  public function intoOptional(): self {
    return self::optional($this);
  }

  /**
   * Returns a Type instance representing a Sequence of $this.
   *
   * @return self The resolved Type instance.
   */
  public function intoSequence(): self {
    return self::sequence($this);
  }

  /**
   * Returns a Type instance representing a Set of $this.
   *
   * @return self The resolved Type instance.
   */
  public function intoSet(): self {
    return self::set($this);
  }

  /**
   * Returns a Type instance representing a Stream of $this.
   *
   * @return self The resolved Type instance.
   */
  public function intoStream(): self {
    return self::stream($this);
  }

  // endregion

  // region Parser functions

  public static function parse(string $typeString): self {
    if (empty(preg_match_all("/([\\\\\\w]+|<|>|,)/", $typeString, $typeParts))) {
      throw new InvalidArgumentException('');
    }

    $parse = static function(string $type, self ...$subTypes): self {
      return self::null();
    };

    $typeStack = [];
    foreach ($typeParts[0] as $typePart) {
      switch($typePart) {
        case '<':
          break;
        case '>':
          $subTypes = [];
          while (count($typeStack) > 0 && ($type = array_pop($typeStack)) instanceof self) {
            $subTypes[] = $type;
          }

          $typeStack[] = $parse($type, ...array_reverse($subTypes));
          break;
        case ',':
          $typeStack[] = $parse(array_pop($typeStack));
          break;
        default:
          $typeStack[] = $typePart;
          break;
      }
    }

    return null;
  }

  // endregion

  // region Validation functions

  /**
   * Validates that the specified value fits this Type.
   *
   * @param mixed $value The specified value.
   *
   * @return bool Whether the specified value fits this Type.
   */
  public function isValid($value): bool {
    return ($this->predicate)($value);
  }

  /**
   * Validates that the specified value fits this Type.
   *
   * @param mixed $value The specified value.
   *
   * @return mixed The specified value.
   * @throws TypeError If the specified value is invalid.
   */
  public function validate($value) {
    if ($this->isValid($value)) {
      return $value;
    }

    $scalarValue = is_scalar($value) ? $value : json_encode($value);

    throw self::throwError(self::class . "::validate(): {$scalarValue} is an invalid {$this->name}.");
  }

  /**
   * Validates that the specified callable fits the specified input and output Types.
   *
   * @param callable  $callable           The specified callable.
   * @param self      $outputType         The specified output Type.
   * @param self[]    $requiredInputTypes The specified required input Types.
   * @param self[]    $optionalInputTypes The specified optional input Types.
   * @param self|null $variadicInputType  The specified optional variadic input Type.
   *
   * @return callable The validated callable.
   * @throws TypeError If the specified callable or any of the specified input types are invalid.
   */
  public static function validateCallable($callable, self $outputType, array $requiredInputTypes = [], array $optionalInputTypes = [], ?Type $variadicInputType = null): callable {
    $arrayOfType = self::arrayOf(self::ofClass(self::class));
    $arrayOfType->validate($requiredInputTypes);
    $arrayOfType->validate($optionalInputTypes);
    self::callable()->validate($callable);

    return static function (...$inputs) use ($callable, $outputType, $requiredInputTypes, $optionalInputTypes, $variadicInputType) {
      $inputCount = count($inputs);
      $inputTypes = $requiredInputTypes;
      foreach ($optionalInputTypes as $inputType) {
        if ($inputCount > count($inputTypes)) {
          $inputTypes[] = $inputType;
        }
      }

      if (isset($variadicInputType)) {
        while($inputCount > count($inputTypes)) {
          $inputTypes[] = $variadicInputType;
        }
      }

      return $outputType->validate($callable(...self::tuple($inputTypes)->validate($inputs)));
    };
  }

  // endregion

  // region Helper functions

  /**
   * Returns the internal ID of this Type.
   *
   * @return string The internal ID of this Type.
   */
  private function id(): string {
    return (string)spl_object_id($this);
  }

  /**
   * Returns the comma-separated IDs of the specified set of Types.
   *
   * @param self ...$types The specified set of Types.
   *
   * @return string The comma-separated IDs.
   */
  private static function ids(self ...$types): string {
    return implode(',', array_map(fn(Type $type): string => $type->id(), $types));
  }

  /**
   * Throws a TypeError with the specified message, code, and previous throwable.
   *
   * @param string         $message  The specified message.
   * @param int            $code     The specified code.
   * @param Throwable|null $previous The specified previous throwable.
   *
   * @return TypeError|mixed The created TypeError, which is always thrown.
   * @throws TypeError The created TypeError, which is always thrown.
   */
  public static function throwError($message = '', $code = 0, Throwable $previous = null) {
    throw new TypeError($message, $code, $previous);
  }

  /**
   * Returns a new empty Sequence of this Type.
   *
   * @return Data\Sequence The created Sequence.
   */
  public function newSequence(): Data\Sequence {
    return Data\Sequence::ofType($this);
  }

  /**
   * Returns a new empty Map of this Type. This Type must be a tuple of two Types, or a TypeError will be thrown.
   *
   * @return Data\Map The created Map.
   * @throws TypeError If this Type is not a tuple of two Types.
   */
  public function newMap(): Data\Map {
    return $this->baseName === 'tuple' && count($this->subTypes) === 2
      ? Data\Map::ofType(...$this->subTypes)
      : self::throwError(self::class . "::newMap(): Cannot create a Map from this type ({$this->name})");
  }

  /**
   * Returns a new empty Map of this Type mapped to a specified value Type.
   *
   * @param self $valueType The specified value Type.
   *
   * @return Data\Map The created Map.
   */
  public function newMapTo(self $valueType): Data\Map {
    return Data\Map::ofType($this, $valueType);
  }

  /**
   * Returns a new empty Map of this Type mapped from a specified key Type.
   *
   * @param self $keyType The specified key Type.
   *
   * @return Data\Map The created Map.
   */
  public function newMapFrom(self $keyType): Data\Map {
    return Data\Map::ofType($keyType, $this);
  }

  /**
   * Returns a new empty Optional of this Type.
   *
   * @return Data\Optional The created Optional.
   */
  public function newOptional(): Data\Optional {
    return Data\Optional::empty($this);
  }

  /**
   * Returns a new empty Stream of this Type.
   *
   * @return Data\Stream The created Stream.
   */
  public function newStream(): Data\Stream {
    return Data\Stream::empty($this);
  }

  // endregion

  // region JsonSerializable

  /**
   * @inheritDoc
   * @see JsonSerializable::jsonSerialize()
   */
  public function jsonSerialize() {
    return ['base' => $this->baseName, 'subTypes' => $this->subTypes];
  }

  // endregion
}
