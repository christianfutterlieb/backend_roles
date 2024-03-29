<?php

declare(strict_types=1);

namespace AawTeam\BackendRoles\Role;

/*
 * Copyright by Agentur am Wasser | Maeder & Partner AG
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use AawTeam\BackendRoles\Exception\RoleDefinitionException;

/**
 * DefinitionCollection
 *
 * @implements \Iterator<Definition>
 * @implements \ArrayAccess<string, Definition>
 */
final class DefinitionCollection implements \Iterator, \ArrayAccess
{
    /**
     * @var Definition[]
     */
    private array $definitions = [];

    public function add(Definition $definition): void
    {
        if ($this->has($definition)) {
            throw new RoleDefinitionException('A definition with the identifier "' . htmlspecialchars($definition->getIdentifier()) . '" already exists', 1688739853);
        }
        $this->definitions[$definition->getIdentifier()] = $definition;
    }

    public function has(Definition $definition): bool
    {
        return $this->offsetExists($definition->getIdentifier());
    }

    public function addFromCollection(DefinitionCollection $definitionCollection): void
    {
        foreach ($definitionCollection as $definition) {
            $this->add($definition);
        }
    }

    /**
     * @return Definition[]
     */
    public function toArray(): array
    {
        return $this->definitions;
    }

    public function next(): void
    {
        next($this->definitions);
    }

    public function valid(): bool
    {
        return current($this->definitions) !== false;
    }

    /**
     * @return Definition|false
     */
    public function current(): mixed
    {
        return current($this->definitions);
    }

    public function rewind(): void
    {
        reset($this->definitions);
    }

    /**
     * @return int|string|null
     */
    public function key(): mixed
    {
        return key($this->definitions);
    }

    public function offsetGet(mixed $offset): mixed
    {
        if (!$this->offsetExists($offset)) {
            throw new \InvalidArgumentException('Offset ' . htmlspecialchars($offset) . ' does not exist.', 1688738748);
        }
        return $this->definitions[$offset];
    }

    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->definitions);
    }

    public function offsetUnset(mixed $offset): void
    {
        if ($this->offsetExists($offset)) {
            unset($this->definitions[$offset]);
        }
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \RuntimeException('This method is not implemented, use DefinitionCollection::add() instead.');
    }
}
