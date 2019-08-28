<?php

namespace Tests;

use Livewire\Component;
use Livewire\Exceptions\ComponentMismatchException;

class SubsequentComponentRequestsMustMatchOriginalComponentTest extends TestCase
{
    public function test()
    {
        $this->expectException(ComponentMismatchException::class);

        app('livewire')->component('safe', SafeComponentStub::class);
        app('livewire')->component('unsafe', UnsafeComponentStub::class);
        $component = app('livewire')->test('safe');

        // Hijack the "safe" component, with "unsafe"
        $component->name = 'unsafe';

        // If the hijack was stopped, the expected exception will be thrown.
        // If it worked, an exception will be thrown that will fail the test.
        $component->runAction('someMethod');
    }
}

class SafeComponentStub extends Component
{
    public function someMethod()
    {
    }

    public function render()
    {
        return app('view')->make('null-view');
    }
}

class UnsafeComponentStub extends Component
{
    public function someMethod()
    {
        throw new \Exception('Should not be able to access me!');
    }

    public function render()
    {
        return app('view')->make('null-view');
    }
}
