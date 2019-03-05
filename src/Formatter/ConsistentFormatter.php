<?php declare(strict_types=1);

namespace Darsyn\IP\Formatter;

use Darsyn\IP\Binary;
use Darsyn\IP\Exception\Formatter\FormatException;

class ConsistentFormatter extends NativeFormatter
{
    /** {@inheritdoc} */
    public function ntop(string $binary): string
    {
        if (\is_string($binary)) {
            $length = Binary::getLength($binary);
            if ($length === 16) {
                return $this->ntopVersion6(Binary::toHex($binary));
            }
            if ($length === 4) {
                return $this->ntopVersion4($binary);
            }
        }
        throw new FormatException($binary);
    }

    private function ntopVersion6(string $hex): string
    {
        $parts = \str_split($hex, 4);
        $zeroes = \array_map(function ($part) {
            return $part === '0000';
        }, $parts);
        $length = $i = 0;
        $sequences = [];
        foreach ($zeroes as $zero) {
            $length = $zero ? ++$length : 0;
            $sequences[++$i] = $length;
        }
        $maxLength = \max($sequences);
        $position = \array_search($maxLength, $sequences, true) - $maxLength;
        $parts = \array_map(function ($part) {
            return \ltrim($part, '0') ?: '0';
        }, $parts);
        if ($maxLength > 0) {
            \array_splice($parts, $position, $maxLength, ':');
        }
        return \str_pad(\preg_replace('/\:{2,}/', '::', \implode(':', $parts)), 2, ':');
    }

    /** @throws \Darsyn\IP\Exception\Formatter\FormatException */
    private function ntopVersion4(string $binary): string
    {
        $protocol = \inet_ntop(\pack('A4', $binary));
        if (!\is_string($protocol)) {
            throw new FormatException($binary);
        }
        return $protocol;
    }
}
