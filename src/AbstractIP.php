<?php declare(strict_types=1);

namespace Darsyn\IP;

use Darsyn\IP\Formatter\ConsistentFormatter;
use Darsyn\IP\Formatter\ProtocolFormatterInterface;

abstract class AbstractIP implements IpInterface
{
    /** @var \Darsyn\IP\Formatter\ProtocolFormatterInterface $formatter */
    protected static $formatter;

    /**
     * Keep this private to prevent modification of object's main value from
     * child classes.
     * @var string $ip
     */
    private $ip;

    public static function setProtocolFormatter(ProtocolFormatterInterface $formatter): void
    {
        self::$formatter = $formatter;
    }

    /**
     * Get the protocol formatter set by the user, falling back to using our
     * custom formatter for consistency by default if the user has not set one
     * globally.
     */
    protected static function getProtocolFormatter(): ProtocolFormatterInterface
    {
        if (null === self::$formatter) {
            self::$formatter = new ConsistentFormatter;
        }
        return self::$formatter;
    }

    protected function __construct(string $ip)
    {
        $this->ip = $ip;
    }

    final public function getBinary(): string
    {
        return $this->ip;
    }

    public function isVersion(int $version): bool
    {
        return $this->getVersion() === $version;
    }

    public function isVersion4(): bool
    {
        return $this->isVersion(4);
    }

    public function isVersion6(): bool
    {
        return $this->isVersion(6);
    }

    /** {@inheritDoc} */
    public function getNetworkIp(int $cidr): IpInterface
    {
        // Providing that the CIDR is valid, bitwise AND the IP address binary
        // sequence with the mask generated from the CIDR.
        return new static($this->getBinary() & $this->generateBinaryMask(
            $cidr,
            Binary::getLength($this->getBinary())
        ));
    }

    /** {@inheritDoc} */
    public function getBroadcastIp(int $cidr): IpInterface
    {
        // Providing that the CIDR is valid, bitwise OR the IP address binary
        // sequence with the inverse of the mask generated from the CIDR.
        return new static($this->getBinary() | ~$this->generateBinaryMask(
            $cidr,
            Binary::getLength($this->getBinary())
        ));
    }

    /** {@inheritDoc} */
    public function inRange(IpInterface $ip, int $cidr): bool
    {
        try {
            return $this->getNetworkIp($cidr)->getBinary() === $ip->getNetworkIp($cidr)->getBinary();
        } catch (Exception\InvalidCidrException $e) {
            return false;
        }
    }

    public function isMapped(): bool
    {
        return (new Strategy\Mapped)->isEmbedded($this->getBinary());
    }

    public function isDerived(): bool
    {
        return (new Strategy\Derived)->isEmbedded($this->getBinary());
    }

    public function isCompatible(): bool
    {
        return (new Strategy\Compatible)->isEmbedded($this->getBinary());
    }

    public function isEmbedded(): bool
    {
        return false;
    }

    /**
     * 128-bit masks can often evaluate to integers over PHP_MAX_INT, so we have
     * to construct the bitmask as a string instead of doing any mathematical
     * operations (such as base_convert).
     *
     * @throws \Darsyn\IP\Exception\InvalidCidrException
     */
    protected function generateBinaryMask(int $cidr, int $length): string
    {
        // CIDR is measured in bits, we're describing the length in bytes.
        if ($cidr < 0 || $length < 0 || $cidr > $length * 8) {
            throw new Exception\InvalidCidrException($cidr, $length);
        }
        // Since it takes 4 bits per hexadecimal, how many sections of complete
        // 1's do we have (f's)?
        $mask = \str_repeat('f', (int) \floor($cidr / 4));
        // Now we have less than four 1 bits left we need to determine what
        // hexadecimal character should be added next. Of course, we should only
        // add them in there are 1 bits leftover to prevent going over the
        // 128-bit limit.
        if (0 !== $bits = $cidr % 4) {
            // Create a string representation of a 4-bit binary sequence
            // beginning with the amount of leftover 1's.
            $bin = \str_pad(\str_repeat('1', $bits), 4, '0', STR_PAD_RIGHT);
            // Convert that 4-bit binary string into a hexadecimal character,
            // and append it to the mask.
            $mask .= \dechex(\bindec($bin));
        }
        // Fill the rest of the string up with zero's to pad it out to the
        // correct length (one hex character is worth half a byte).
        $mask = \str_pad($mask, $length * 2, '0', STR_PAD_RIGHT);
        // Pack the hexadecimal sequence into a real, 4 or 16-byte binary
        // sequence.
        $mask = Binary::fromHex($mask);
        return $mask;
    }
}
