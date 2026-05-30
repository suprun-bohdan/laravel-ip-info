<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Intel;

use JsonSerializable;

final readonly class ClientIpRiskScore implements JsonSerializable
{
    /**
     * @param  list<array{reason: string, points: int}>  $signals
     */
    public function __construct(
        public int $score,
        public string $level,
        public array $signals = [],
    ) {}

    public static function none(): self
    {
        return new self(0, 'low');
    }

    public function isHigh(): bool
    {
        return $this->level === 'high';
    }

    public function isMedium(): bool
    {
        return $this->level === 'medium';
    }

    public function isLow(): bool
    {
        return $this->level === 'low';
    }

    /**
     * @return array{score: int, level: string, signals: list<array{reason: string, points: int}>}
     */
    public function toArray(): array
    {
        return [
            'score' => $this->score,
            'level' => $this->level,
            'signals' => $this->signals,
        ];
    }

    /**
     * @return array{score: int, level: string, signals: list<array{reason: string, points: int}>}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
