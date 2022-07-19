<?php

declare(strict_types = 1);

namespace Util\Data\Test;

use Generator;
use PHPUnit\Framework\TestCase;
use Throwable;
use TypeError;
use UnexpectedValueException;
use Util\Data;

/**
 * Class UtilDataTest
 *
 * @package Util\Data\Test
 */
class UtilDataTest extends TestCase {

  // region Data\Type

  /**
   * Data provider for testType.
   *
   * @return array
   */
  public function dataProviderType(): array {
    [$int, $intType] = [1, Data\Type::int()];
    [$integerObject, $integerObjectType] = [Data\Test\IntegerObject::newInstance(1), Data\Type::ofClass(Data\Test\IntegerObject::class)];
    [$string, $stringType] = ['big chungus', Data\Type::string()];

    $mixedType = Data\Type::mixed();
    $arrayIntType = Data\Type::arrayOf($intType);
    $tupleType1 = Data\Type::tuple($intType, $stringType, $intType);
    $tupleType2 = Data\Type::tuple($intType, $stringType, Data\Type::tuple($stringType), Data\Type::tuple($intType));

    return [
      '0-0' => ['type' => $mixedType, 'value' => $int, 'expected' => true],
      '0-1' => ['type' => $mixedType, 'value' => $string, 'expected' => true],
      '0-2' => ['type' => $mixedType, 'value' => null, 'expected' => true],
      '0-3' => ['type' => $mixedType, 'value' => $integerObject, 'expected' => true],
      '1-0' => ['type' => $intType, 'value' => $int, 'expected' => true],
      '1-1' => ['type' => $intType, 'value' => $string, 'expected' => false],
      '1-2' => ['type' => $intType, 'value' => null, 'expected' => false],
      '1-3' => ['type' => $intType, 'value' => $integerObject, 'expected' => false],
      '2-0' => ['type' => $stringType, 'value' => $int, 'expected' => false],
      '2-1' => ['type' => $stringType, 'value' => $string, 'expected' => true],
      '2-2' => ['type' => $stringType, 'value' => null, 'expected' => false],
      '2-3' => ['type' => $stringType, 'value' => $integerObject, 'expected' => false],
      '3-0' => ['type' => $integerObjectType, 'value' => $int, 'expected' => false],
      '3-1' => ['type' => $integerObjectType, 'value' => $string, 'expected' => false],
      '3-2' => ['type' => $integerObjectType, 'value' => null, 'expected' => false],
      '3-3' => ['type' => $integerObjectType, 'value' => $integerObject, 'expected' => true],
      '4-0' => ['type' => Data\Type::union($mixedType, $stringType), 'value' => $int, 'expected' => true],
      '4-1' => ['type' => Data\Type::union($mixedType, $stringType), 'value' => $string, 'expected' => true],
      '4-2' => ['type' => Data\Type::union($mixedType, $stringType), 'value' => null, 'expected' => true],
      '4-3' => ['type' => Data\Type::union($mixedType, $stringType), 'value' => $integerObject, 'expected' => true],
      '5-0' => ['type' => Data\Type::union($intType, $mixedType), 'value' => $int, 'expected' => true],
      '5-1' => ['type' => Data\Type::union($intType, $mixedType), 'value' => $string, 'expected' => true],
      '5-2' => ['type' => Data\Type::union($intType, $mixedType), 'value' => null, 'expected' => true],
      '5-3' => ['type' => Data\Type::union($intType, $mixedType), 'value' => $integerObject, 'expected' => true],
      '6-0' => ['type' => Data\Type::union($intType, $stringType), 'value' => $int, 'expected' => true],
      '6-1' => ['type' => Data\Type::union($intType, $stringType), 'value' => $string, 'expected' => true],
      '6-2' => ['type' => Data\Type::union($intType, $stringType), 'value' => null, 'expected' => false],
      '6-3' => ['type' => Data\Type::union($intType, $stringType), 'value' => $integerObject, 'expected' => false],
      '7-0' => ['type' => Data\Type::union($intType, $integerObjectType), 'value' => $int, 'expected' => true],
      '7-1' => ['type' => Data\Type::union($intType, $integerObjectType), 'value' => $string, 'expected' => false],
      '7-2' => ['type' => Data\Type::union($intType, $integerObjectType), 'value' => null, 'expected' => false],
      '7-3' => ['type' => Data\Type::union($intType, $integerObjectType), 'value' => $integerObject, 'expected' => true],
      '8-0' => ['type' => $arrayIntType, 'value' => [], 'expected' => true],
      '8-1' => ['type' => $arrayIntType, 'value' => [$int], 'expected' => true],
      '8-2' => ['type' => $arrayIntType, 'value' => [$int, $int, $int], 'expected' => true],
      '8-3' => ['type' => $arrayIntType, 'value' => [$int, null, $int], 'expected' => false],
      '8-4' => ['type' => $arrayIntType, 'value' => [$int, $string, $int], 'expected' => false],
      '9-0' => ['type' => $tupleType1, 'value' => [$int, $string], 'expected' => false],
      '9-1' => ['type' => $tupleType1, 'value' => [$int, $string, $int], 'expected' => true],
      '9-2' => ['type' => $tupleType1, 'value' => [$int, $string, $int, $int], 'expected' => false],
      '9-3' => ['type' => $tupleType1, 'value' => [$int, $int, $int], 'expected' => false],
      '10-0' => ['type' => $tupleType2, 'value' => [$int, $string, [$string], [$int]], 'expected' => true],
      '10-1' => ['type' => $tupleType2, 'value' => [$int, $string, [$string], [$string]], 'expected' => false],
      '10-2' => ['type' => $tupleType2, 'value' => [$int, $string, [$string], $int], 'expected' => false],
      '10-3' => ['type' => $tupleType2, 'value' => [$int, [$string], [$int]], 'expected' => false],
    ];
  }

  /**
   * Tests whether the specified value is of the specified type.
   *
   * @param Data\Type $type     Type.
   * @param mixed     $value    Value.
   * @param bool      $expected Expected.
   *
   * @dataProvider dataProviderType
   *
   * @return void
   */
  public function testType(Data\Type $type, $value, bool $expected): void {
    $scalarValue = is_scalar($value) ? $value : json_encode($value);

    $this->assertEquals($expected, $type->isValid($value), "TypeTest::testType: Type::isValid() failed to correctly check if {$scalarValue} is a valid {$type}.");

    try {
      $validatedValue = $type->validate($value);
      $validated = true;
    } catch (TypeError $typeError) {
      $validated = false;
    }

    $this->assertEquals($expected, $validated, "TypeTest::testType: Type::validate() failed to correctly check if {$scalarValue} is a valid {$type}.");
    $this->assertEquals($expected ? $value : null, $validatedValue ?? null, "TypeTest::testType: Type::validate() failed to return {$scalarValue} on a valid {$type}.");
  }

  // endregion

  // region Data\Optional

  /**
   * Data provider for testOptional.
   *
   * @return array
   */
  public function dataProviderOptional(): array {
    $optionalOf = fn($value): callable => fn(): Data\Optional => Data\Optional::of($value, Data\Type::int());
    $optionalOfNullable = fn($value): callable => fn(): Data\Optional => Data\Optional::ofNullable($value, Data\Type::int());
    $optionalEmpty = fn(): callable => fn(): Data\Optional => Data\Optional::empty(Data\Type::int());

    return [
      'Optional::of(invalid)' => ['construct' => $optionalOf(1.5)],
      'Optional::ofNullable(invalid)' => ['construct' => $optionalOfNullable(1.5)],
      'Optional::of(valid)' => ['construct' => $optionalOf(1), 'expectExceptionOnConstruct' => false],
      'Optional::ofNullable(valid)' => ['construct' => $optionalOfNullable(1), 'expectExceptionOnConstruct' => false],
      'Optional::empty()' => ['construct' => $optionalOf(null)],
      'Optional::of(null)' => ['construct' => $optionalEmpty(), 'expectExceptionOnConstruct' => false, 'expectIsPresent' => false],
      'Optional::ofNullable(null)' => ['construct' => $optionalOfNullable(null), 'expectExceptionOnConstruct' => false, 'expectIsPresent' => false],
    ];
  }

  /**
   * Tests Optional for valid and invalid inputs.
   *
   * @see          \Util\Data\Optional::of()
   * @see          \Util\Data\Optional::ofNullable()
   * @see          \Util\Data\Optional::empty()
   * @see          \Util\Data\Optional::isPresent()
   * @see          \Util\Data\Optional::getValue()
   * @see          \Util\Data\Optional::orElse()
   * @see          \Util\Data\Optional::orElseGet()
   * @see          \Util\Data\Optional::orElseThrow()
   *
   * @param callable $construct                  Construct.
   * @param bool     $expectExceptionOnConstruct Expect exception on construct.
   * @param bool     $expectIsPresent            Expect is present.
   *
   * @return void
   * @dataProvider dataProviderOptional
   */
  public function testOptional(callable $construct, bool $expectExceptionOnConstruct = true, bool $expectIsPresent = true): void {
    // region Instantiation
    try {
      $optional = $construct();
    } catch (TypeError $typeError) {
      if (!$expectExceptionOnConstruct) {
        $this->fail('OptionalTest::testOptional: Optional constructor threw an unexpected TypeError.');
      } else {
        $this->assertNull($optional ?? null);
      }

      return;
    } catch (Throwable $throwable) {
      $this->fail('OptionalTest::testOptional: Optional constructor threw an unexpected ' . get_class($throwable) . '.');
    }

    /** @var Data\Optional $optional */
    $this->assertInstanceOf(Data\Optional::class, $optional);
    // endregion

    // region Optional::isPresent
    $this->assertEquals($expectIsPresent, $optional->isPresent());
    // endregion

    // region Optional::getValue
    $getValueContext = 'OptionalTest::testOptional: Optional::getValue()';
    /** @var Data\Optional $optional */
    $getValue = fn() => $optional->getValue();

    $this->assertOptional($getValue, !$expectIsPresent, 1, $getValueContext);
    // endregion

    // region Optional::orElse
    $orElseContext = 'OptionalTest::testOptional: Optional::orElse()';
    /** @var Data\Optional $optional */
    $orElse = fn($else): callable => fn() => $optional->orElse($else);

    $this->assertOptional($orElse(2), false, $expectIsPresent ? 1 : 2, $orElseContext);
    $this->assertOptional($orElse(null), false, $expectIsPresent ? 1 : null, $orElseContext);
    $this->assertOptional($orElse(2.5), !$expectIsPresent, $expectIsPresent ? 1 : 2.5, $orElseContext);
    $this->assertOptional($orElse('big chungus'), !$expectIsPresent, $expectIsPresent ? 1 : 'big chungus', $orElseContext);
    // endregion

    // region Optional::orElseGet
    $orElseGetContext = 'OptionalTest::testOptional: Optional::orElseGet()';
    /** @var Data\Optional $optional */
    $orElseGet = fn($else): callable => fn() => $optional->orElseGet($this->getGetter($else));

    $this->assertOptional($orElseGet(2), false, $expectIsPresent ? 1 : 2, $orElseGetContext);
    $this->assertOptional($orElseGet(null), false, $expectIsPresent ? 1 : null, $orElseGetContext);
    $this->assertOptional($orElseGet(2.5), !$expectIsPresent, $expectIsPresent ? 1 : 2.5, $orElseGetContext);
    $this->assertOptional($orElseGet('big chungus'), !$expectIsPresent, $expectIsPresent ? 1 : 'big chungus', $orElseGetContext);
    // endregion

    // region Optional::orElseThrow
    $orElseThrowContext = 'OptionalTest::testOptional: Optional::orElseThrow()';
    /** @var Data\Optional $optional */
    $orElseThrow = fn() => $optional->orElseThrow($this->getGetter(new TypeError()));

    $this->assertOptional($orElseThrow, !$expectIsPresent, 1, $orElseThrowContext);
    // endregion
  }

  /**
   * Tests that an union type will work properly.
   *
   * @return void
   */
  public function testOptionalUnionType(): void {
    // region Instantiation
    $type = Data\Type::union(Data\Type::int(), Data\Type::float());

    $intOptional = Data\Optional::of(1, $type);
    $floatOptional = Data\Optional::of(1.5, $type);

    try {
      $bogusOptional = Data\Optional::of('big chungus', $type);
      $this->fail('OptionalTest::testOutputUnionType invalid type did not trigger a TypeError.');
    } catch (TypeError $typeError) {
    }
    // endregion

    // region Optional::getValue
    $this->assertEquals(1, $intOptional->getValue());
    $this->assertEquals(1.5, $floatOptional->getValue());
    $this->assertNull($bogusOptional ?? null);
    // endregion
  }

  /**
   * Data provider for testOptionalIfPresentFilterFlatMapMap
   *
   * @return array
   */
  public function dataProviderOptionalIfPresentFilterFlatMapMap(): array {
    return [
      'odd' => [Data\Optional::of(1, Data\Type::int()), 1],
      'even' => [Data\Optional::of(2, Data\Type::int()), 2],
      'null' => [Data\Optional::empty(Data\Type::int()), null],
    ];
  }

  /**
   * Tests Optional::filter, Optional::flatMap, Optional::map, and Optional::ifPresent with present and null values.
   *
   * @see \Util\Data\Optional::ifPresent()
   * @see \Util\Data\Optional::filter()
   * @see \Util\Data\Optional::flatMap()
   * @see \Util\Data\Optional::map()
   *
   * Also has a dependency on the following functions: of, empty, isPresent, getValue
   *
   * @param Data\Optional $optional           Optional.
   * @param int|null      $expectCurrentValue Expected current value.
   *
   * @return void
   * @dataProvider dataProviderOptionalIfPresentFilterFlatMapMap
   */
  public function testOptionalIfPresentFilterFlatMapMap(Data\Optional $optional, ?int $expectCurrentValue): void {
    [$present, $odd] = [isset($expectCurrentValue), $expectCurrentValue % 2 === 1];

    // region Optional::ifPresent
    try {
      $optional->ifPresent($this->getConsumerIfPresent($expectCurrentValue));
      $this->assertFalse($present, 'OptionalTest::testOptionalIfPresentFilterFlatMapMap ifPresent not invoked on valid value');
    } catch (UnexpectedValueException $exception) {
      $this->assertTrue($present, 'OptionalTest::testOptionalIfPresentFilterFlatMapMap ifPresent invoked on null value');
    }
    // endregion

    // region Optional::filter
    $testOptional = $optional->filter(fn(int $v, $k = null): bool => $v % 2 === 0);
    $this->assertEquals($present && !$odd, $testOptional->isPresent());
    $this->assertEquals($present && !$odd ? $expectCurrentValue : null, $testOptional->orElse(null));

    $testOptional = $optional->filter(fn(int $v, $k = null): bool => $v % 2 === 1);
    $this->assertEquals($present && $odd, $testOptional->isPresent());
    $this->assertEquals($present && $odd ? $expectCurrentValue : null, $testOptional->orElse(null));
    // endregion

    // region Optional::flatMap
    $testOptional = $optional->flatMap($this->getFlatMapperDoubleIfOdd(), Data\Type::int());
    $this->assertEquals($present && $odd, $testOptional->isPresent());
    $this->assertEquals($present && $odd ? $expectCurrentValue * 2 : null, $testOptional->orElse(null));
    // endregion

    // region Optional::map
    $testOptional = $optional->map(fn(int $v): int => 3 * $v, Data\Type::int());
    $this->assertEquals($present, $testOptional->isPresent());
    $this->assertEquals($present ? $expectCurrentValue * 3 : null, $testOptional->orElse(null));
    // endregion
  }

  // endregion

  // region Data\Sequence

  /**
   * testSequenceOfType.
   *
   * @return void
   */
  public function testSequenceOfType(): void {
    $Sequence = Data\Sequence::ofType(Data\Type::string());

    $this->assertEquals(0, $Sequence->count());

    $Sequence[] = 'one';
    $Sequence[] = 'two';

    $this->assertEquals(2, $Sequence->count());
    $this->assertEquals(['one', 'two'], $Sequence->asArray());

    $Sequence = Data\Sequence::ofType(Data\Type::string());

    $this->assertEquals(0, $Sequence->count());

    $Sequence['one-one'] = 'one';
    $Sequence['two-two'] = 'two';

    try {
      $Sequence['three-three'] = 3;
      $this->fail('Sequence::offsetSet invalid type did not throw a type error');
    } catch (TypeError $typeError) {
    } catch (Throwable $throwable) {
      $this->fail('Sequence::offsetSet threw an unexpected throwable');
    }

    $this->assertEquals(2, $Sequence->count());
    $this->assertEquals(['one-one' => 'one', 'two-two' => 'two'], $Sequence->asArray());

    unset($Sequence['two-two']);

    $this->assertEquals(1, $Sequence->count());
    $this->assertEquals(['one-one' => 'one'], $Sequence->asArray());
  }

  /**
   * testSequenceOfClass.
   *
   * @return void
   */
  public function testSequenceOfClass(): void {
    $one = Data\Test\StringObject::newInstance('one');
    $two = Data\Test\StringObject::newInstance('two');

    $Sequence = Data\Sequence::ofClass(Data\Test\StringObject::class);

    $Sequence[] = $one;
    $Sequence[] = $two;

    $this->assertEquals([$one, $two], $Sequence->asArray());

    $Sequence = Data\Sequence::ofClass(Data\Test\StringObject::class);

    $Sequence['one'] = $one;
    $Sequence['two'] = $two;

    $this->assertEquals(['one' => $one, 'two' => $two], $Sequence->asArray());

    unset($Sequence['two']);

    $this->assertEquals(['one' => $one], $Sequence->asArray());
  }

  // endregion

  // region Data\Stream

  /**
   * Tests the positive case for the following functions:
   *
   * @return void
   *@see \Util\Data\Stream::toArray()
   *
   * @see \Util\Data\Stream::generate()
   */
  public function testStreamFromIterable(): void {

    // Run the stream.
    $output = Data\Stream
      ::generate($this->getGeneratorArray([1, 2, 3, 4, 5]), Data\Type::int())
      ->toArray();

    // Validate the output of the stream.
    $this->assertEquals([1, 2, 3, 4, 5], $output);
  }

  /**
   * Tests the positive case for the following functions:
   *
   * @see \Util\Data\Stream::empty()
   *
   * Also has a dependency on the following functions: toArray
   *
   * @return void
   */
  public function testStreamEmpty(): void {

    // Run the stream.
    $output = Data\Stream
      ::empty(Data\Type::int())
      ->toArray();

    // Validate the output of the stream.
    $this->assertEquals([], $output);
  }

  /**
   * Tests the positive case for the following functions:
   *
   * @return void
   *@see \Util\Data\Stream::peek()
   *
   * Also has a dependency on the following functions: toArray
   *
   * @see \Util\Data\Stream::generate()
   */
  public function testStreamFromArray(): void {

    // Setup the stream.
    $array = [1, 2, 3, 4, 5];
    $expected = [
      ['fromIterable', 1],
      ['fromIterable', 2],
      ['fromIterable', 3],
      ['fromIterable', 4],
      ['fromIterable', 5],
    ];

    // Run the stream.
    $output = Data\Stream
      ::generate($array, Data\Type::int())
      ->peek($this->assertStream('fromIterable', $expected))
      ->toArray();

    // Validate the output of the stream.
    $this->assertEquals($array, $output);

    // Assert that all expected values have been checked.
    $this->assertEmpty($expected);
  }

  /**
   * Tests the positive case for the following functions:
   *
   * @see \Util\Data\Stream::takeWhile()
   * @see \Util\Data\Stream::dropWhile()
   *
   * Also has a dependency on the following functions: peek, fromIterable, toArray
   *
   * @return void
   */
  public function testStreamDropWhileTakeWhile(): void {

    // Setup the stream.
    $expected = [
      ['fromIterable', 1], ['takeWhile', 1],
      ['fromIterable', 2], ['takeWhile', 2],
      ['fromIterable', 3], ['takeWhile', 3],
      ['fromIterable', 4], ['takeWhile', 4],
      ['fromIterable', 5], ['takeWhile', 5], ['dropWhile', 5],
      ['fromIterable', 6], ['takeWhile', 6], ['dropWhile', 6],
      ['fromIterable', 7], ['takeWhile', 7], ['dropWhile', 7],
      ['fromIterable', 8], ['takeWhile', 8], ['dropWhile', 8],
      ['fromIterable', 9], ['takeWhile', 9], ['dropWhile', 9],
      ['fromIterable', 10],
    ];

    // Run the stream.
    $output = Data\Stream
      ::generate($this->getGeneratorNaturalNumbers()(), Data\Type::int())
      ->peek($this->assertStream('fromIterable', $expected))
      ->takeWhile(fn(int $v, $k = null): bool => $v < 10)
      ->peek($this->assertStream('takeWhile', $expected))
      ->dropWhile(fn(int $v, $k = null): bool => $v < 5)
      ->peek($this->assertStream('dropWhile', $expected))
      ->toArray();

    // Validate the output of the stream.
    $this->assertEquals([5, 6, 7, 8, 9], $output);

    // Assert that all expected values have been checked.
    $this->assertEmpty($expected);
  }

  /**
   * Tests the positive case for the following functions:
   *
   * @see \Util\Data\Stream::filter()
   * @see \Util\Data\Stream::skip()
   * @see \Util\Data\Stream::limit()
   * @see \Util\Data\Stream::map()
   *
   * Also has a dependency on the following functions: fromIterable, takeWhile, dropWhile, peek, toArray
   *
   * @return void
   */
  public function testStreamFilteringMap(): void {

    // Setup the stream.
    $expected = [
      ['dropWhile', 10], ['filter', 10],
      ['dropWhile', 11],
      ['dropWhile', 12], ['filter', 12],
      ['dropWhile', 13],
      ['dropWhile', 14], ['filter', 14],
      ['dropWhile', 15],
      ['dropWhile', 16], ['filter', 16],
      ['dropWhile', 17],
      ['dropWhile', 18], ['filter', 18],
      ['dropWhile', 19],
      ['dropWhile', 20], ['filter', 20],
      ['dropWhile', 21],
      ['dropWhile', 22], ['filter', 22],
      ['dropWhile', 23],
      ['dropWhile', 24], ['filter', 24],
      ['dropWhile', 25],
      ['dropWhile', 26], ['filter', 26],
      ['dropWhile', 27],
      ['dropWhile', 28], ['filter', 28],
      ['dropWhile', 29],
      ['dropWhile', 30], ['filter', 30], ['skip', 30], ['limit', 30], ['map', 90],
      ['dropWhile', 31],
      ['dropWhile', 32], ['filter', 32], ['skip', 32], ['limit', 32], ['map', 96],
      ['dropWhile', 33],
      ['dropWhile', 34], ['filter', 34], ['skip', 34], ['limit', 34], ['map', 102],
      ['dropWhile', 35],
      ['dropWhile', 36], ['filter', 36], ['skip', 36], ['limit', 36], ['map', 108],
      ['dropWhile', 37],
      ['dropWhile', 38], ['filter', 38], ['skip', 38], ['limit', 38], ['map', 114],
      ['dropWhile', 39],
      ['dropWhile', 40], ['filter', 40], ['skip', 40],
    ];

    // Run the stream.
    $output = Data\Stream
      ::generate($this->getGeneratorNaturalNumbers()(), Data\Type::int())
      ->takeWhile(fn(int $v): bool => $v < 50)
      ->dropWhile(fn(int $v): bool => $v < 10)
      ->peek($this->assertStream('dropWhile', $expected))
      ->filter(fn(int $v): bool => $v % 2 === 0)
      ->peek($this->assertStream('filter', $expected))
      ->skip(10)
      ->peek($this->assertStream('skip', $expected))
      ->limit(5)
      ->peek($this->assertStream('limit', $expected))
      ->map(fn(int $v): int => 3 * $v, Data\Type::int())
      ->peek($this->assertStream('map', $expected))
      ->toArray();

    // Validate the output of the stream.
    $this->assertEquals([90, 96, 102, 108, 114], $output);

    // Assert that all expected values have been checked.
    $this->assertEmpty($expected);
  }

  /**
   * Tests the positive case for the following functions:
   *
   * @see \Util\Data\Stream::iterate()
   * @see \Util\Data\Stream::count()
   *
   * Also has a dependency on the following functions: peek, filter
   *
   * @return void
   */
  public function testStreamIterateCount(): void {

    // Setup the stream.
    $expected = [
      ['iterate', 42], ['filter', 42],
      ['iterate', 21],
      ['iterate', 64], ['filter', 64],
      ['iterate', 32], ['filter', 32],
      ['iterate', 16], ['filter', 16],
      ['iterate', 8], ['filter', 8],
      ['iterate', 4], ['filter', 4],
      ['iterate', 2], ['filter', 2],
    ];

    // Run the stream.
    $output = Data\Stream
      ::iterate(42, fn(int $v): bool => $v !== 1, $this->getMapperCollatz(), Data\Type::int())
      ->peek($this->assertStream('iterate', $expected))
      ->filter(fn(int $v, $k = null): bool => $v % 2 === 0)
      ->peek($this->assertStream('filter', $expected))
      ->count();

    // Validate the output of the stream.
    $this->assertEquals(7, $output);

    // Assert that all expected values have been checked.
    $this->assertEmpty($expected);
  }

  /**
   * Tests the positive case for the following functions:
   *
   * @see \Util\Data\Stream::flatMap()
   * @see \Util\Data\Stream::getIterator()
   * @see \Util\Data\Stream::distinct()
   * @see \Util\Data\Stream::reduce()
   *
   * Also has a dependency on the following functions: fromIterable, peek, iterate
   *
   * @return void
   */
  public function testStreamFlatMapDistinctMap(): void {

    // Setup the stream.
    $array = [6, 7, 8, 9, 10];
    $expected = [
      ['fromIterable', 6],
      ['flatMap', 6], ['distinct', 6],
      ['flatMap', 3], ['distinct', 3],
      ['flatMap', 10], ['distinct', 10],
      ['flatMap', 5], ['distinct', 5],
      ['flatMap', 16], ['distinct', 16],
      ['flatMap', 8], ['distinct', 8],
      ['flatMap', 4], ['distinct', 4],
      ['flatMap', 2], ['distinct', 2],
      ['fromIterable', 7],
      ['flatMap', 7], ['distinct', 7],
      ['flatMap', 22], ['distinct', 22],
      ['flatMap', 11], ['distinct', 11],
      ['flatMap', 34], ['distinct', 34],
      ['flatMap', 17], ['distinct', 17],
      ['flatMap', 52], ['distinct', 52],
      ['flatMap', 26], ['distinct', 26],
      ['flatMap', 13], ['distinct', 13],
      ['flatMap', 40], ['distinct', 40],
      ['flatMap', 20], ['distinct', 20],
      ['flatMap', 10],
      ['flatMap', 5],
      ['flatMap', 16],
      ['flatMap', 8],
      ['flatMap', 4],
      ['flatMap', 2],
      ['fromIterable', 8],
      ['flatMap', 8],
      ['flatMap', 4],
      ['flatMap', 2],
      ['fromIterable', 9],
      ['flatMap', 9], ['distinct', 9],
      ['flatMap', 28], ['distinct', 28],
      ['flatMap', 14], ['distinct', 14],
      ['flatMap', 7],
      ['flatMap', 22],
      ['flatMap', 11],
      ['flatMap', 34],
      ['flatMap', 17],
      ['flatMap', 52],
      ['flatMap', 26],
      ['flatMap', 13],
      ['flatMap', 40],
      ['flatMap', 20],
      ['flatMap', 10],
      ['flatMap', 5],
      ['flatMap', 16],
      ['flatMap', 8],
      ['flatMap', 4],
      ['flatMap', 2],
      ['fromIterable', 10],
      ['flatMap', 10],
      ['flatMap', 5],
      ['flatMap', 16],
      ['flatMap', 8],
      ['flatMap', 4],
      ['flatMap', 2],
    ];

    // Run the stream.
    $output = Data\Stream
      ::generate($array, Data\Type::int())
      ->peek($this->assertStream('fromIterable', $expected))
      ->flatMap($this->getGeneratorCollatzSequence(), Data\Type::int())
      ->peek($this->assertStream('flatMap', $expected))
      ->distinct()
      ->peek($this->assertStream('distinct', $expected))
      ->reduce(fn(int $v1, int $v2): int => $v1 + $v2, 0, Data\Type::int());

    // Validate the output of the stream.
    $this->assertEquals(347, $output);

    // Assert that all expected values have been checked.
    $this->assertEmpty($expected);
  }

  /**
   * Tests the positive case for the following functions:
   *
   * @see \Util\Data\Stream::findFirst()
   *
   * Also has a dependency on the following functions: iterate, peek, filter
   *
   * @return void
   */
  public function testStreamFirst(): void {

    // Setup the stream.
    $expected = [
      ['iterate', 42],
      ['iterate', 21],
      ['iterate', 64],
      ['iterate', 32],
      ['iterate', 16], ['filter', 16],
    ];

    // Run the stream.
    $output = Data\Stream
      ::iterate(42, fn(int $v): bool => $v !== 1, $this->getMapperCollatz(), Data\Type::int())
      ->peek($this->assertStream('iterate', $expected))
      ->filter(fn(int $v, $k = null): bool => $v < 20)
      ->peek($this->assertStream('filter', $expected))
      ->findFirst();

    // Validate the output of the stream.
    $this->assertEquals(16, $output->getValue());

    // Assert that all expected values have been checked.
    $this->assertEmpty($expected);
  }

  /**
   * Tests the positive case for the following functions:
   *
   * @see \Util\Data\Stream::min()
   *
   * Also has a dependency on the following functions: iterate, peek, filter
   *
   * @return void
   */
  public function testStreamMin(): void {

    // Setup the stream.
    $expected = [
      ['iterate', 9],
      ['iterate', 28], ['filter', 28],
      ['iterate', 14],
      ['iterate', 7],
      ['iterate', 22], ['filter', 22],
      ['iterate', 11],
      ['iterate', 34], ['filter', 34],
      ['iterate', 17],
      ['iterate', 52], ['filter', 52],
      ['iterate', 26], ['filter', 26],
      ['iterate', 13],
      ['iterate', 40], ['filter', 40],
      ['iterate', 20],
      ['iterate', 10],
      ['iterate', 5],
      ['iterate', 16],
      ['iterate', 8],
      ['iterate', 4],
      ['iterate', 2],
    ];

    // Run the stream.
    $output = Data\Stream
      ::iterate(9, fn(int $v): bool => $v !== 1, $this->getMapperCollatz(), Data\Type::int())
      ->peek($this->assertStream('iterate', $expected))
      ->filter(fn(int $v, $k = null): bool => $v > 20)
      ->peek($this->assertStream('filter', $expected))
      ->min();

    // Validate the output of the stream.
    $this->assertEquals(22, $output->getValue());

    // Assert that all expected values have been checked.
    $this->assertEmpty($expected);
  }

  /**
   * Tests the positive case for the following functions:
   *
   * @see \Util\Data\Stream::max()
   *
   * Also has a dependency on the following functions: iterate, peek, filter
   *
   * @return void
   */
  public function testStreamMax(): void {

    // Setup the stream.
    $expected = [
      ['iterate', 9], ['filter', 9],
      ['iterate', 28],
      ['iterate', 14], ['filter', 14],
      ['iterate', 7], ['filter', 7],
      ['iterate', 22],
      ['iterate', 11], ['filter', 11],
      ['iterate', 34],
      ['iterate', 17], ['filter', 17],
      ['iterate', 52],
      ['iterate', 26],
      ['iterate', 13], ['filter', 13],
      ['iterate', 40],
      ['iterate', 20],
      ['iterate', 10], ['filter', 10],
      ['iterate', 5], ['filter', 5],
      ['iterate', 16], ['filter', 16],
      ['iterate', 8], ['filter', 8],
      ['iterate', 4], ['filter', 4],
      ['iterate', 2], ['filter', 2],
    ];

    // Run the stream.
    $output = Data\Stream
      ::iterate(9, fn(int $v): bool => $v !== 1, $this->getMapperCollatz(), Data\Type::int())
      ->peek($this->assertStream('iterate', $expected))
      ->filter(fn(int $v, $k = null): bool => $v < 20)
      ->peek($this->assertStream('filter', $expected))
      ->max();

    // Validate the output of the stream.
    $this->assertEquals(17, $output->getValue());

    // Assert that all expected values have been checked.
    $this->assertEmpty($expected);
  }

  /**
   * Tests the positive case for the following functions:
   *
   * @see \Util\Data\Stream::anyMatch()
   *
   * Also has a dependency on the following functions: fromIterable, fromIterable, iterate, filter, map
   *
   * @return void
   */
  public function testStreamAnyMatch(): void {

    // Run the stream.
    $shouldBeFalse = Data\Stream
      ::generate([1, 2, 3, 4, 5, 6, 7], Data\Type::int())
      ->filter(fn(int $v, $k = null): bool => $v % 2 === 0)
      ->anyMatch(fn(int $v, $k = null): bool => $v % 2 === 1);

    $shouldBeTrue = Data\Stream
      ::generate($this->getGeneratorNaturalNumbers()(), Data\Type::int())
      ->map(fn(int $v): int => 3 * $v, Data\Type::int())
      ->anyMatch(fn(int $v, $k = null): bool => $v % 2 === 1);

    // Validate the output of the stream.
    $this->assertFalse($shouldBeFalse);
    $this->assertTrue($shouldBeTrue);
  }

  /**
   * Tests the positive case for the following functions:
   *
   * @see \Util\Data\Stream::allMatch()
   *
   * Also has a dependency on the following functions: fromIterable, fromIterable, iterate, filter, map
   *
   * @return void
   */
  public function testStreamAllMatch(): void {

    // Run the stream.
    $shouldBeTrue = Data\Stream
      ::generate([1, 2, 3, 4, 5, 6, 7], Data\Type::int())
      ->filter(fn(int $v, $k = null): bool => $v % 2 === 1)
      ->allMatch(fn(int $v, $k = null): bool => $v % 2 === 1);

    $shouldBeFalse = Data\Stream
      ::generate($this->getGeneratorNaturalNumbers()(), Data\Type::int())
      ->map(fn(int $v): int => 3 * $v, Data\Type::int())
      ->allMatch(fn(int $v, $k = null): bool => $v % 2 === 1);

    // Validate the output of the stream.
    $this->assertTrue($shouldBeTrue);
    $this->assertFalse($shouldBeFalse);
  }

  /**
   * Tests the positive case for the following functions:
   *
   * @see \Util\Data\Stream::allMatch()
   *
   * Also has a dependency on the following functions: fromIterable, fromIterable, iterate, filter, map
   *
   * @return void
   */
  public function testStreamNoneMatch(): void {

    // Run the stream.
    $shouldBeTrue = Data\Stream
      ::generate([1, 2, 3, 4, 5, 6, 7], Data\Type::int())
      ->filter(fn(int $v, $k = null): bool => $v % 2 === 1)
      ->noneMatch(fn(int $v, $k = null): bool => $v % 2 === 0);

    $shouldBeFalse = Data\Stream
      ::generate($this->getGeneratorNaturalNumbers()(), Data\Type::int())
      ->map(fn(int $v): int => 3 * $v, Data\Type::int())
      ->noneMatch(fn(int $v, $k = null): bool => $v % 2 === 0);

    // Validate the output of the stream.
    $this->assertTrue($shouldBeTrue);
    $this->assertFalse($shouldBeFalse);
  }

  /**
   * Tests the positive case for the following functions:
   *
   * @see \Util\Data\Stream::batch()
   *
   * Also has a dependency on the following functions: iterate, peek, filter
   *
   * @return void
   */
  public function testStreamBatch(): void {

    // Setup the stream.
    $expected = [
      ['iterate', 9],
      ['iterate', 28],
      ['iterate', 14], ['batch_1', [9, 28, 14]],
      ['iterate', 7],
      ['iterate', 22],
      ['iterate', 11], ['batch_1', [7, 22, 11]], ['batch_2', [[9, 28, 14], [7, 22, 11]]],
      ['iterate', 34],
      ['iterate', 17],
      ['iterate', 52], ['batch_1', [34, 17, 52]],
      ['iterate', 26],
      ['iterate', 13],
      ['iterate', 40], ['batch_1', [26, 13, 40]], ['batch_2', [[34, 17, 52], [26, 13, 40]]], ['batch_3', [[[9, 28, 14], [7, 22, 11]], [[34, 17, 52], [26, 13, 40]]]],
      ['iterate', 20],
      ['iterate', 10],
      ['iterate', 5], ['batch_1', [20, 10, 5]],
      ['iterate', 16],
      ['iterate', 8],
      ['iterate', 4], ['batch_1', [16, 8, 4]], ['batch_2', [[20, 10, 5], [16, 8, 4]]],
      ['iterate', 2], ['batch_1', [2]], ['batch_2', [[2]]], ['batch_3', [[[20, 10, 5], [16, 8, 4]], [[2]]]],
    ];

    // Run the stream.
    $output = Data\Stream
      ::iterate(9, fn(int $v): bool => $v !== 1, $this->getMapperCollatz(), Data\Type::int())
      ->peek($this->assertStream('iterate', $expected))
      ->batch(3)
      ->map(fn(Data\Sequence $Sequence): array => $Sequence->asArray(), Data\Type::arrayOf(Data\Type::int()))
      ->peek($this->assertStream('batch_1', $expected))
      ->batch(2)
      ->map(fn(Data\Sequence $Sequence): array => $Sequence->asArray(), Data\Type::arrayOf(Data\Type::arrayOf(Data\Type::int())))
      ->peek($this->assertStream('batch_2', $expected))
      ->batch(2)
      ->map(fn(Data\Sequence $Sequence): array => $Sequence->asArray(), Data\Type::arrayOf(Data\Type::arrayOf(Data\Type::arrayOf(Data\Type::int()))))
      ->peek($this->assertStream('batch_3', $expected))
      ->toArray();

    // Validate the output of the stream.
    $this->assertEquals([[[[9, 28, 14], [7, 22, 11]], [[34, 17, 52], [26, 13, 40]]], [[[20, 10, 5], [16, 8, 4]], [[2]]]], $output);

    // Assert that all expected values have been checked.
    $this->assertEmpty($expected);
  }

  /**
   * Tests that an invalid type will trigger a TypeError.
   *
   * @return void
   */
  public function testStreamInvalidType(): void {

    // Setup the stream.
    $expected = [
      ['fromIterable', 1],
      ['fromIterable', 2],
      ['fromIterable', 3],
    ];

    // Run the stream.
    try {
      $output = Data\Stream
        ::generate([1, 2, 3, 4.5, 6], Data\Type::int())
        ->peek($this->assertStream('fromIterable', $expected))
        ->toArray();

      $this->fail('Did not throw a TypeError');
    } catch (TypeError $typeError) {
    }

    // Validate the output of the stream.
    $this->assertNull($output ?? null);

    // Assert that all expected values have been checked.
    $this->assertEmpty($expected);
  }

  /**
   * Tests that an union type will work properly.
   *
   * @return void
   */
  public function testStreamUnionType(): void {

    // Setup the stream.
    $expected = [
      ['fromIterable', 1],
      ['fromIterable', 2],
      ['fromIterable', 3],
      ['fromIterable', 'big chungus'],
    ];

    // Run the stream.
    $output = Data\Stream
      ::generate([1, 2, 3, 'big chungus'], Data\Type::union(Data\Type::int(), Data\Type::string()))
      ->peek($this->assertStream('fromIterable', $expected))
      ->toArray();

    // Validate the output of the stream.
    $this->assertEquals([1, 2, 3, 'big chungus'], $output);

    // Assert that all expected values have been checked.
    $this->assertEmpty($expected);
  }

  // endregion

  // region Asserts

  /**
   * assertOptional.
   *
   * @param callable $callable            Callable.
   * @param bool     $typeErrorIsExpected Expect type error.
   * @param mixed    $expectedValue       Expected value.
   * @param string   $context             Context.
   *
   * @return mixed
   */
  private function assertOptional(callable $callable, bool $typeErrorIsExpected, $expectedValue, string $context) {
    try {
      $typeErrorIsThrown = false;
      $value = $callable();
    } catch (TypeError $typeError) {
      if (!$typeErrorIsExpected) {
        $this->fail("{$context} threw an unexpected TypeError for the expected value.");
      }

      $typeErrorIsThrown = true;
    } catch (Throwable $throwable) {
      $this->fail("{$context} threw an unexpected " . get_class($throwable) . '.');
    }

    if (!$typeErrorIsThrown) {
      $this->assertEquals($expectedValue, $value ?? null, "{$context} did not retrieve the expected value.");
    }

    return $value ?? null;
  }

  /**
   * assertStream.
   *
   * @param mixed $checkpoint Checkpoint.
   * @param array $expected   Expected.
   *
   * @return callable
   */
  private function assertStream($checkpoint, array &$expected): callable {
    return function ($v, $k = null) use ($checkpoint, &$expected): void {
      $this->assertNotEmpty($expected);
      $this->assertEquals(array_shift($expected), [$checkpoint, $v]);
    };
  }

  // endregion

  // region Optional Supplier

  /**
   * getGetter.
   *
   * @param mixed $value Value.
   *
   * @return callable
   */
  private function getGetter($value): callable {
    return fn() => $value;
  }

  // endregion

  // region Stream Generators

  /**
   * getGeneratorArray.
   *
   * @param array $array Array.
   *
   * @return \Generator
   */
  private function getGeneratorArray(array $array): Generator {
    yield from $array;
  }

  /**
   * getGeneratorNaturalNumbers.
   *
   * @return callable
   */
  private function getGeneratorNaturalNumbers(): callable {
    return static function (): Generator {
      while (true) {
        yield $a = ($a ?? 0) + 1;
      }
    };
  }

  /**
   * getGeneratorCollatzSequence.
   *
   * @return callable
   */
  private function getGeneratorCollatzSequence(): callable {
    return function (int $a): Generator {
      yield from Data\Stream
        ::iterate($a, fn(int $v): bool => $v !== 1, $this->getMapperCollatz(), Data\Type::int())
        ->getIterator();
    };
  }

  // endregion

  // region FlatMappers

  /**
   * getFlatMapperDouble.
   *
   * @return callable
   */
  private function getFlatMapperDoubleIfOdd(): callable {
    return fn($v): Data\Optional => $v % 2 === 1
      ? Data\Optional::of(2 * $v, Data\Type::int())
      : Data\Optional::empty(Data\Type::int());
  }

  // endregion

  // region Mappers

  /**
   * getMapperCollatz.
   *
   * @return callable
   */
  private function getMapperCollatz(): callable {
    return fn(int $v): int => ($v % 2 === 0) ? ($v / 2) : (3 * $v + 1);
  }

  // endregion

  // region Optional Consumers

  /**
   * getConsumerIfPresentShouldBeInvoked.
   *
   * @param mixed $expectedValue Expected value.
   *
   * @return callable
   */
  private function getConsumerIfPresent($expectedValue): callable {
    return function ($value) use ($expectedValue): void {
      $this->assertEquals($expectedValue, $value);

      throw new UnexpectedValueException('');
    };
  }

  // endregion
}
