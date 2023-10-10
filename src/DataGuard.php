<?php

namespace Acdphp\DataGuard;

use Acdphp\DataGuard\Exception\InvalidConditionException;
use Acdphp\DataGuard\Helpers\Node;
use Acdphp\DataGuard\Traits\EvaluatesValues;

class DataGuard
{
    use EvaluatesValues;

    protected string $separator;

    protected string $splitter;

    protected string $arrayIndicator;

    protected string $maskWith;

    protected array $data;

    protected string $resource;

    protected bool $mask = false;

    public function __construct(string $separator, string $splitter, string $arrayIndicator, string $maskWith)
    {
        $this->separator = $separator;
        $this->splitter = $splitter;
        $this->arrayIndicator = $arrayIndicator;
        $this->maskWith = $maskWith;
    }

    public function setData($data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getResult(): array
    {
        return $this->protect($this->data, $this->resource);
    }

    /**
     * @param  (callable(self): self)|string|null  $key
     * @param  mixed  $value
     */
    public function hide(
        string $resource,
        $key = null,
        string $operator = null,
        $value = null
    ): self {
        $this->resource = $resource;

        $this->setConditions(...array_slice(func_get_args(), 1));

        return $this;
    }

    /**
     * @param  (callable(self): self)|string|null  $key
     * @param  mixed  $value
     */
    public function mask(
        string $resource,
        $key = null,
        string $operator = null,
        $value = null
    ): self {
        $this->mask = true;

        return $this->hide(...func_get_args());
    }

    /**
     * @throws InvalidConditionException
     */
    protected function protect(array $data, string $resource): array
    {
        $nodes = explode($this->separator, $resource);

        // Final resource node match against condition
        if (count($nodes) === 1) {
            $node = current($nodes);
            $splits = explode($this->splitter, $node);

            foreach ($splits as $split) {
                if (Node::isArray($split, $data, $this->arrayIndicator)) {
                    for ($i = 0, $count = count($data[$split]); $i < $count; $i++) {
                        $this->process($data[$split], $i);
                    }

                    // Reindex
                    $data[$split] = array_values($data[$split]);
                } elseif (isset($data[$split])) {
                    $this->process($data, $split);
                }
            }

            return $data;
        }

        // Each of parent resource nodes
        foreach ($nodes as $i => $node) {
            $levelResource = implode($this->separator, array_slice($nodes, $i + 1));
            $splits = explode($this->splitter, $node);

            foreach ($splits as $split) {
                if (Node::isArray($split, $data, $this->arrayIndicator)) {
                    foreach ($data[$split] as $j => $single) {
                        $data[$split][$j] = $this->protect($data[$split][$j], $levelResource);
                    }
                } elseif (isset($data[$split])) {
                    $data[$split] = $this->protect($data[$split], $levelResource);
                }
            }
        }

        return $data;
    }

    /**
     * @throws InvalidConditionException
     */
    protected function process(array &$data, string $key): void
    {
        if ($this->match($data[$key])) {
            if ($this->mask) {
                $data[$key] = $this->maskWith;
            } else {
                unset($data[$key]);
            }
        }
    }
}
