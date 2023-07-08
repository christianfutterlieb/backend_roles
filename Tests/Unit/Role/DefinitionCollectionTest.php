<?php

declare(strict_types=1);

namespace AawTeam\LanguageMatcher\Tests\Unit\Context\Context;

/*
 * Copyright by Agentur am Wasser | Maeder & Partner AG
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use AawTeam\BackendRoles\Exception\RoleDefinitionException;
use AawTeam\BackendRoles\Role\Definition;
use AawTeam\BackendRoles\Role\DefinitionCollection;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * DefinitionCollectionTest
 */
class DefinitionCollectionTest extends UnitTestCase
{
    /**
     * @test
     */
    public function addAndRetrieveDefinitions(): void
    {
        $existingDefinition = new Definition('definition-1');
        $inexistentDefinition = new Definition('definition-2');

        $collection = new DefinitionCollection();
        $collection->add($existingDefinition);

        // API
        self::assertTrue($collection->has($existingDefinition));
        self::assertFalse($collection->has($inexistentDefinition));

        // \Iterator
        $counter = 0;
        foreach ($collection as $retrievedDefinition) {
            self::assertSame($existingDefinition, $retrievedDefinition);
            ++$counter;
        }
        self::assertSame(1, $counter);

        // \ArrayAccess
        self::assertTrue($collection->offsetExists($existingDefinition->getIdentifier()));
        self::assertFalse($collection->offsetExists($inexistentDefinition->getIdentifier()));
        self::assertSame($existingDefinition, $collection->offsetGet($existingDefinition->getIdentifier()));

        $collection->offsetUnset($existingDefinition->getIdentifier());
        self::assertFalse($collection->offsetExists($existingDefinition->getIdentifier()));

        // API (again)
        self::assertFalse($collection->has($existingDefinition));
    }

    /**
     * @test
     */
    public function offsetSetThrowsException(): void
    {
        $identifier = 'identifier';
        $collection = new DefinitionCollection();

        $this->expectException(\RuntimeException::class);
        $collection->offsetSet($identifier, new Definition($identifier));
    }

    /**
     * @test
     * @depends addAndRetrieveDefinitions
     */
    public function retrievingAnInexistentIdentifierThrowsException(): void
    {
        $unknownIdentifier = 'unknown-identifier';
        $collection = new DefinitionCollection();

        self::assertFalse($collection->offsetExists($unknownIdentifier));

        $this->expectException(\InvalidArgumentException::class);
        $collection->offsetGet($unknownIdentifier);
    }

    /**
     * @test
     * @depends addAndRetrieveDefinitions
     */
    public function addingDuplicateIdentifierThrowsException(): void
    {
        $identifier = 'a-duplicate-identifier';

        $collection = new DefinitionCollection();
        $collection->add(new Definition($identifier));

        $this->expectException(RoleDefinitionException::class);
        $this->expectExceptionCode(1688739853);
        $collection->add(new Definition($identifier));
    }

    /**
     * @test
     * @depends addAndRetrieveDefinitions
     */
    public function addingDuplicateIdentifierFromAnotherCollectionThrowsException(): void
    {
        $identifier = 'a-duplicate-identifier';

        $collectionToTest = new DefinitionCollection();
        $collectionToTest->add(new Definition($identifier));

        $collectionWithDuplicateDefinition = new DefinitionCollection();
        $collectionWithDuplicateDefinition->add(new Definition($identifier));

        $this->expectException(RoleDefinitionException::class);
        $this->expectExceptionCode(1688739853);
        $collectionToTest->addFromCollection($collectionWithDuplicateDefinition);
    }

    /**
     * @test
     * @depends addAndRetrieveDefinitions
     */
    public function addingFromCollection(): void
    {
        $definition = new Definition('definition-1');

        $collectionToTest = new DefinitionCollection();
        $collectionWithDefinition = new DefinitionCollection();
        $collectionWithDefinition->add($definition);

        $collectionToTest->addFromCollection($collectionWithDefinition);
        self::assertTrue($collectionToTest->has($definition));
    }

    /**
     * @test
     * @depends addAndRetrieveDefinitions
     */
    public function toArrayReturnsCorrectData(): void
    {
        $identifier1 = 'definition-1';
        $identifier2 = 'definition-2';
        $definition1 = new Definition($identifier1);
        $definition2 = new Definition($identifier2);

        $collection = new DefinitionCollection();

        // Empty array
        self::assertSame([], $collection->toArray());

        // Add one definition
        $collection->add($definition1);
        self::assertSame([
            $identifier1 => $definition1,
        ], $collection->toArray());

        // Add another definition
        $collection->add($definition2);
        self::assertSame([
            $identifier1 => $definition1,
            $identifier2 => $definition2,
        ], $collection->toArray());

        // Remove the first definition
        $collection->offsetUnset($identifier1);
        self::assertSame([
            $identifier2 => $definition2,
        ], $collection->toArray());

        // Remove the second definition => empty array
        $collection->offsetUnset($identifier2);
        self::assertSame([], $collection->toArray());
    }
}
