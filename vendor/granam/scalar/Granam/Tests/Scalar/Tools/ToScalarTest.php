<?php
declare(strict_types=1);

namespace Granam\Tests\Scalar\Tools;

use Granam\Scalar\Tools\ToScalar;
use PHPUnit\Framework\TestCase;

class ToScalarTest extends TestCase
{
    /** @test */
    public function Scalar_values_remain_untouched(): void
    {
        self::assertSame('foo', ToScalar::toScalar('foo'));
        self::assertSame('foo', ToScalar::toScalar('foo', true /* strict */));
        self::assertSame(123456, ToScalar::toScalar(123456));
        self::assertSame(123456, ToScalar::toScalar(123456, true /* strict */));
        self::assertSame(123456.789654, ToScalar::toScalar(123456.789654));
        self::assertSame(123456.789654, ToScalar::toScalar(123456.789654, true /* strict */));
        self::assertSame(0.9999999999, ToScalar::toScalar(0.9999999999));
        self::assertSame(0.9999999999, ToScalar::toScalar(0.9999999999, true /* strict */));
        self::assertFalse(ToScalar::toScalar(false));
        self::assertFalse(ToScalar::toScalar(false, true /* strict */));
        self::assertTrue(ToScalar::toScalar(true));
        self::assertTrue(ToScalar::toScalar(true, true /* strict */));
    }

    /**
     * @test
     */
    public function I_can_pass_through_with_null_if_not_strict(): void
    {
        self::assertNull(ToScalar::toScalar(null, false /* not strict */));
    }

    /**
     * @test
     * @expectedException \Granam\Scalar\Tools\Exceptions\WrongParameterType
     * @expectedExceptionMessageRegExp ~got NULL$~
     */
    public function I_cannot_pass_through_with_null_by_default(): void
    {
        ToScalar::toScalar(null);
    }

    /**
     * @test
     * @expectedException \Granam\Scalar\Tools\Exceptions\WrongParameterType
     * @expectedExceptionMessageRegExp ~got NULL$~
     */
    public function I_cannot_pass_through_with_null_if_strict(): void
    {
        ToScalar::toScalar(null, true /* strict */);
    }

    /**
     * @test
     * @expectedException \Granam\Scalar\Tools\Exceptions\WrongParameterType
     */
    public function Throws_exception_with_array(): void
    {
        /** @noinspection PhpParamsInspection */
        ToScalar::toScalar([]);
    }

    /**
     * @test
     * @expectedException \Granam\Scalar\Tools\Exceptions\WrongParameterType
     */
    public function Throws_exception_with_resource(): void
    {
        ToScalar::toScalar(\tmpfile());
    }

    /**
     * @test
     * @expectedException \Granam\Scalar\Tools\Exceptions\WrongParameterType
     */
    public function Throws_exception_with_object(): void
    {
        ToScalar::toScalar(new \stdClass());
    }

    /**
     * @test
     */
    public function with_to_string_object_is_that_object_value_as_string(): void
    {
        $objectWithToString = new TestObjectWithToString($string = 'foo');
        self::assertSame($string, ToScalar::toScalar($objectWithToString));
    }
}