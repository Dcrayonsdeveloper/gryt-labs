<?php

namespace App\Services\Checkout\Gateway;

use InvalidArgumentException;

/**
 * Money value object — minor units (paise for INR), no float math.
 *
 * Path A Phase 1: introduced to kill the float arithmetic that produced the
 * "₹100.00 vs ₹100.01" reconciliation pain in the audit (Track 2). Every
 * gateway interaction goes through Money, so amounts are integer paise from
 * the call site to the gateway and back.
 */
final class Money
{
    public function __construct(
        public readonly int $minor,
        public readonly string $currency = 'INR',
    ) {
        if ($minor < 0) {
            throw new InvalidArgumentException("Money cannot be negative (got {$minor}).");
        }
        if (strlen($currency) !== 3) {
            throw new InvalidArgumentException("Currency must be ISO 4217 (got '{$currency}').");
        }
    }

    public static function fromMajor(float|int|string $major, string $currency = 'INR'): self
    {
        // Round to 2dp before casting; defends against float fuzz like 99.999999.
        $minor = (int) round(((float) $major) * 100);
        return new self($minor, $currency);
    }

    public static function fromMinor(int $minor, string $currency = 'INR'): self
    {
        return new self($minor, $currency);
    }

    public static function zero(string $currency = 'INR'): self
    {
        return new self(0, $currency);
    }

    public function major(): float
    {
        return $this->minor / 100;
    }

    public function isZero(): bool
    {
        return $this->minor === 0;
    }

    public function equals(Money $other): bool
    {
        return $this->minor === $other->minor && $this->currency === $other->currency;
    }

    public function plus(Money $other): self
    {
        $this->assertSameCurrency($other);
        return new self($this->minor + $other->minor, $this->currency);
    }

    public function minus(Money $other): self
    {
        $this->assertSameCurrency($other);
        return new self($this->minor - $other->minor, $this->currency);
    }

    private function assertSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                "Currency mismatch: {$this->currency} vs {$other->currency}."
            );
        }
    }

    public function __toString(): string
    {
        return number_format($this->major(), 2, '.', '') . ' ' . $this->currency;
    }
}
