<?php declare(strict_types=1);

namespace Darsyn\IP\Tests\Strategy;

use Darsyn\IP\Strategy\Derived;
use Darsyn\IP\Tests\TestCase;

class DerivedTest extends TestCase
{
    /** @var \Darsyn\IP\Strategy\EmbeddingStrategyInterface $strategy */
    private $strategy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->strategy = new Derived;
    }

    /**
     * @test
     * @dataProvider \Darsyn\IP\Tests\DataProvider\Strategy\Derived::getInvalidIpAddresses()
     */
    public function testIsEmbeddedReturnsFalseForAStringOtherThan16BytesLong($value): void
    {
        $this->assertFalse($this->strategy->isEmbedded($value));
    }

    /**
     * @test
     * @dataProvider \Darsyn\IP\Tests\DataProvider\Strategy\Derived::getValidIpAddresses()
     */
    public function testIsEmbedded($value, $isEmbedded): void
    {
        $this->assertSame($isEmbedded, $this->strategy->isEmbedded($value));
    }

    /**
     * @test
     * @dataProvider \Darsyn\IP\Tests\DataProvider\Strategy\Derived::getInvalidIpAddresses()
     * @expectedException \Darsyn\IP\Exception\Strategy\ExtractionException
     */
    public function testExceptionIsThrownWhenTryingToExtractFromStringsNot16Bytes($value): void
    {
        $this->strategy->extract($value);
    }

    /**
     * @test
     * @dataProvider \Darsyn\IP\Tests\DataProvider\Strategy\Derived::getValidSequences()
     */
    public function testCorrectSequenceExtractedFromIpBinary($ipv6, $ipv4): void
    {
        $this->assertSame($ipv4, $this->strategy->extract($ipv6));
    }

    /**
     * @test
     * @dataProvider \Darsyn\IP\Tests\DataProvider\Strategy\Derived::getInvalidIpAddresses()
     * @expectedException \Darsyn\IP\Exception\Strategy\PackingException
     */
    public function testExceptionIsThrownWhenTryingToPackStringsNot4Bytes($value): void
    {
        $this->strategy->pack($value);
    }

    /**
     * @test
     * @dataProvider \Darsyn\IP\Tests\DataProvider\Strategy\Derived::getValidSequences()
     */
    public function testSequenceCorrectlyPackedIntoIpBinaryFromIpBinary($ipv6, $ipv4): void
    {
        $this->assertSame($ipv6, $this->strategy->pack($ipv4));
    }
}
