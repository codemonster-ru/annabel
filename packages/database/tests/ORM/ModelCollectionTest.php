<?php

namespace Codemonster\Database\Tests\ORM;

use Codemonster\Database\ORM\ModelCollection;
use Codemonster\Database\Tests\Fakes\FakeModels\User;
use PHPUnit\Framework\TestCase;

class ModelCollectionTest extends TestCase
{
    public function test_add_and_access_models()
    {
        $collection = new ModelCollection();

        $collection->add(new User(['name' => 'One'], true));
        $collection[] = new User(['name' => 'Two'], true);

        $this->assertCount(2, $collection);
        $this->assertEquals('One', $collection[0]->name);
        $this->assertEquals('Two', $collection[1]->name);
    }

    public function test_only_models_are_allowed()
    {
        $this->expectException(\InvalidArgumentException::class);

        $collection = new ModelCollection();
        $collection[] = new \stdClass();
    }

    public function test_json_serialization_returns_array_data()
    {
        $collection = new ModelCollection([
            new User(['name' => 'Json'], true),
        ]);

        $this->assertSame([['name' => 'Json']], $collection->jsonSerialize());
    }
}
