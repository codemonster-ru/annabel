<?php

namespace Codemonster\Database\Tests\ORM;

use Codemonster\Database\ORM\ModelCollection;
use Codemonster\Database\Tests\Fakes\FakeModels\User;
use PHPUnit\Framework\TestCase;

class ModelCollectionTest extends TestCase
{
    public function test_add_and_access_models(): void
    {
        /** @var ModelCollection<User> $collection */
        $collection = new ModelCollection();

        $collection->add(new User(['name' => 'One'], true));
        $collection[] = new User(['name' => 'Two'], true);

        $first = $collection[0];
        $second = $collection[1];

        $this->assertInstanceOf(User::class, $first);
        $this->assertInstanceOf(User::class, $second);
        $this->assertCount(2, $collection);
        $this->assertEquals('One', $first->name);
        $this->assertEquals('Two', $second->name);
    }

    public function test_only_models_are_allowed(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $collection = new ModelCollection();
        $notModel = $this->notAModel();
        $collection[] = $notModel;
    }

    public function test_json_serialization_returns_array_data(): void
    {
        /** @var ModelCollection<User> $collection */
        $collection = new ModelCollection([
            new User(['name' => 'Json'], true),
        ]);

        $this->assertSame([['name' => 'Json']], $collection->jsonSerialize());
    }

    private function notAModel(): mixed
    {
        return new \stdClass();
    }
}
