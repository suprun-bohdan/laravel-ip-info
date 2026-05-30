<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Data;

use JsonSerializable;

final readonly class IpThreatSignals implements JsonSerializable
{
    public function __construct(
        public ?bool $tor = null,
        public ?bool $proxy = null,
        public ?bool $vpn = null,
        public ?bool $hosting = null,
        public ?string $source = null,
    ) {}

    public static function unknown(?string $source = null): self
    {
        return new self(source: $source);
    }

    public function isTor(): bool
    {
        return $this->tor === true;
    }

    public function isProxy(): bool
    {
        return $this->proxy === true;
    }

    public function isVpn(): bool
    {
        return $this->vpn === true;
    }

    public function isHosting(): bool
    {
        return $this->hosting === true;
    }

    public function isAnonymous(): bool
    {
        return $this->isTor() || $this->isProxy() || $this->isVpn();
    }

    public function merge(self $other): self
    {
        return new self(
            tor: $this->pick($this->tor, $other->tor),
            proxy: $this->pick($this->proxy, $other->proxy),
            vpn: $this->pick($this->vpn, $other->vpn),
            hosting: $this->pick($this->hosting, $other->hosting),
            source: $this->mergeSources($this->source, $other->source),
        );
    }

    /**
     * @return array{
     *     tor: ?bool,
     *     proxy: ?bool,
     *     vpn: ?bool,
     *     hosting: ?bool,
     *     source: ?string
     * }
     */
    public function toArray(): array
    {
        return [
            'tor' => $this->tor,
            'proxy' => $this->proxy,
            'vpn' => $this->vpn,
            'hosting' => $this->hosting,
            'source' => $this->source,
        ];
    }

    /**
     * @return array{
     *     tor: ?bool,
     *     proxy: ?bool,
     *     vpn: ?bool,
     *     hosting: ?bool,
     *     source: ?string
     * }
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    private function pick(?bool $current, ?bool $incoming): ?bool
    {
        if ($current === true || $incoming === true) {
            return true;
        }

        if ($current === false) {
            return $incoming ?? false;
        }

        if ($incoming === false) {
            return false;
        }

        return null;
    }

    private function mergeSources(?string $left, ?string $right): ?string
    {
        if ($left === null) {
            return $right;
        }

        if ($right === null || $left === $right) {
            return $left;
        }

        return $left.','.$right;
    }
}
