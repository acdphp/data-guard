<?php

namespace Acdphp\DataGuard\Traits;

use Acdphp\DataGuard\Exception\InvalidConditionException;
use Acdphp\DataGuard\Helpers\Node;

trait EvaluatesValues
{
    protected array $andConditions = [];
    protected array $orConditions = [];

    public function whereResource($key = null, $operator = null, $value = null): self
    {
        $this->andConditions[] = $this->whereBase(...func_get_args());

        return $this;
    }

    public function orWhereResource($key = null, $operator = null, $value = null): self
    {
        $this->orConditions[] = $this->whereBase(...func_get_args());

        return $this;
    }

    /**
     * @param mixed $data
     *
     * @return bool
     * @throws InvalidConditionException
     */
    protected function match($data): bool
    {
        foreach ($this->orConditions as $condition) {
            // If 1 of the OR conditions is positive, automatically return true.
            if ($this->matchEach($data, ...$condition)) {
                return true;
            }
        }

        foreach ($this->andConditions as $condition) {
            // If 1 of the AND conditions is negative, automatically set to false.
            if (! $this->matchEach($data, ...$condition)) {
                return false;
            }
        }

        // If both empty, return true
        return true;
    }

    protected function whereBase($key = null, $operator = null, $value = null): array
    {
        if (func_num_args() === 0) {
            $operator = '=';
            $value = true;
            $key = null;
        }

        if (func_num_args() === 1) {
            $operator = '=';
            $value = $key;
            $key = null;
        }

        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        return [$key, $operator, $value];
    }

    /**
     * @param ?(callable(self): self)|string $key
     * @param ?string $operator
     * @param mixed $value
     *
     * @return void
     */
    protected function setConditions(
        $key = null,
        ?string $operator = null,
        $value = null
    ): void {
        if (! is_string($key) && is_callable($key)) {
            $key($this);

            return;
        }

        $this->whereResource(...func_get_args());
    }

    /**
     * @throws InvalidConditionException
     */
    protected function matchEach(
        $data,
        ?string $conditionResource,
        string $conditionOperator,
        $conditionValue
    ): bool {
        // Automatically search in the data if resource is not provided
        if ($conditionResource === null) {
            return $this->matchFinalNode($data, $conditionOperator, $conditionValue);
        }

        $nodes = explode($this->separator, $conditionResource);

        // Final condition node
        if (count($nodes) === 1) {
            $node = current($nodes);
            $splits = Node::split($node, $this->splitter, $this->arrayIndicator);

            foreach ($splits as $split) {
                // Return true if condition key not is found in the resource data
                if (! isset($data[$split])) {
                    return false;
                }

                if ($this->matchFinalNode($data[$split], $conditionOperator, $conditionValue)) {
                    return true;
                }
            }
        }

        // Each of parent resource nodes
        foreach ($nodes as $k => $node) {
            $levelResource = implode($this->separator, array_slice($nodes, $k + 1));
            $splits = Node::split($node, $this->splitter, $this->arrayIndicator);

            foreach ($splits as $split) {
                if (Node::isArray($split, $data, $this->arrayIndicator)) {
                    foreach ($data[$split] as $dataSplit) {
                        if ($this->matchEach($dataSplit, $levelResource, $conditionOperator, $conditionValue)) {
                            return true;
                        }
                    }
                } elseif (isset($data[$split])) {
                    return $this->matchEach($data[$split], $levelResource, $conditionOperator, $conditionValue);
                }
            }
        }

        return false;
    }

    protected function matchFinalNode($dataNode, $conditionOperator, $conditionValue): bool
    {
        // Operator evaluation
        switch ($conditionOperator) {
            case '=':
                return $dataNode == $conditionValue;
            case '!=':
                return $dataNode !== $conditionValue;
            case 'in':
                if (!is_array($conditionValue)) {
                    throw new InvalidConditionException(
                        sprintf('%s: condition value must be an array', $conditionValue)
                    );
                }

                return in_array($dataNode, $conditionValue, false);
            case '!in':
                if (!is_array($conditionValue)) {
                    throw new InvalidConditionException(
                        sprintf('%s: condition value must be an array', $conditionValue)
                    );
                }

                return !in_array($dataNode, $conditionValue, false);
            case '>':
                return $dataNode > $conditionValue;
            case '<':
                return $dataNode < $conditionValue;
            case '>=':
                return $dataNode >= $conditionValue;
            case '<=':
                return $dataNode <= $conditionValue;
            case 'regex':
                return (bool) preg_match($conditionValue, $dataNode);
            default:
                throw new InvalidConditionException(
                    sprintf('Unsupported operator: %s', $conditionOperator)
                );
        }
    }
}
