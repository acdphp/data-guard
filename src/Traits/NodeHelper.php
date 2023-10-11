<?php

namespace Acdphp\DataGuard\Traits;

trait NodeHelper
{
    protected function isNodeArray(string &$node, iterable $data): bool
    {
        if (substr($node, -strlen($this->arrayIndicator)) !== $this->arrayIndicator) {
            return false;
        }

        $node = trim($node, $this->arrayIndicator);

        return isset($data[$node]) &&
            is_array($data[$node]) &&
            array_keys($data[$node]) === range(0, count($data[$node]) - 1);
    }

    protected function nodeSplit(string $node): array
    {
        $splits = explode($this->splitter, $node);

        // Sort splits to process array types first
        $arrayIndicator = $this->arrayIndicator;

        return [
            ...array_filter($splits, static fn ($v) => str_ends_with($v, $arrayIndicator)),
            ...array_filter($splits, static fn ($v) => ! str_ends_with($v, $arrayIndicator)),
        ];
    }
}
