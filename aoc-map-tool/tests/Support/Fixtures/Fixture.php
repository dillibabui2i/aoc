<?php

namespace Tests\Support\Fixtures;

use Illuminate\Support\Facades\DB;
use Tests\Support\ResolvesOverrides;

abstract class Fixture
{
    use ResolvesOverrides;

    private array $state = [];

    abstract protected function table(): string;

    abstract protected function defaults(): array;

    /**
     * Named states (e.g. ::eligibleForCccCalls()) call this to layer extra attributes
     * on top of defaults(). Returns a clone so chaining/reuse never mutates the
     * original instance's state.
     */
    protected function state(array $attributes): static
    {
        $clone = clone $this;
        $clone->state = array_merge($this->state, $attributes);

        return $clone;
    }

    public function make(array $overrides = []): array
    {
        return $this->resolveOverrides(
            array_merge($this->defaults(), $this->state),
            $overrides
        );
    }

    public function create(array $overrides = []): object
    {
        $id = DB::table($this->table())->insertGetId($this->make($overrides));

        return DB::table($this->table())->find($id);
    }
}
