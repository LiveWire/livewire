<?php

namespace Tests\Browser\Actions;

use Livewire\Livewire;
use Tests\Browser\TestCase;

class Test extends TestCase
{
    public function test()
    {
        $this->browse(function ($browser) {
            Livewire::visit($browser, Component::class)
                /**
                 * Basic action (click).
                 */
                ->waitForLivewire()->click('@foo')
                ->assertSeeIn('@output', 'foo')

                /**
                 * Action with params.
                 */
                ->waitForLivewire()->click('@bar')
                ->assertSeeIn('@output', 'barbell')

                /**
                 * Action with various parameter formatting differences.
                 */
                ->waitForLivewire()->click('@ball')
                ->assertSeeIn('@output', 'abcdef')

                /**
                 * Action with no params, but still parenthesis.
                 */
                ->waitForLivewire()->click('@bowl')
                ->assertSeeIn('@output', 'foo')

                /**
                 * wire:click.self
                 */
                ->waitForLivewire()->click('@baz.inner')
                ->assertSeeIn('@output', 'foo')
                ->waitForLivewire()->click('@baz.outer')
                ->assertSeeIn('@output', 'baz')

                /**
                 * Blur event and click event get sent together
                 */
                ->click('@bop.input') // Fucus.
                ->assertSeeIn('@output', 'baz')
                ->waitForLivewire()->click('@bop.button')
                ->assertSeeIn('@output', 'bazbopbop')

                /**
                 * Two keydowns
                 */
                ->waitForLivewire()->keys('@bob', '{enter}')
                ->assertSeeIn('@output', 'bazbopbopbobbob')

                /**
                 * If listening for "enter", other keys don't trigger the action.
                 */
                ->keys('@lob', 'k')
                ->pause(150)
                ->assertDontSeeIn('@output', 'lob')
                ->waitForLivewire()->keys('@lob', '{enter}')
                ->assertSeeIn('@output', 'lob')

                /**
                 * keydown.shift.enter
                 */
                ->waitForLivewire()->keys('@law', '{shift}', '{enter}')
                ->assertSeeIn('@output', 'law')

                /**
                 * keydown.space
                 */
                ->waitForLivewire()->keys('@spa', '{space}')
                ->assertSeeIn('@output', 'spa')

                /**
                 * Elements are marked as read-only during form submission
                 */
                ->tap(function ($b) {
                    $this->assertNull($b->attribute('@blog.button', 'disabled'));
                    $this->assertNull($b->attribute('@blog.input', 'readonly'));
                    $this->assertNull($b->attribute('@blog.input.ignored', 'readonly'));
                })
                ->press('@blog.button')
                ->waitForLivewire()->tap(function ($b) {
                    $this->assertEquals('true', $b->attribute('@blog.button', 'disabled'));
                    $this->assertEquals('true', $b->attribute('@blog.input', 'readonly'));
                    $this->assertNull($b->attribute('@blog.input.ignored', 'readonly'));
                })
                ->tap(function ($b) {
                    $this->assertNull($b->attribute('@blog.button', 'disabled'));
                    $this->assertNull($b->attribute('@blog.input', 'readonly'));
                })

                /**
                 * Elements are un-marked as readonly when form errors out.
                 */
                ->press('@boo.button')
                ->waitForLivewire()->tap(function ($b) {
                    $this->assertEquals('true', $b->attribute('@boo.button', 'disabled'));
                })
                ->tap(function ($b) {
                    $this->assertNull($b->attribute('@blog.button', 'disabled'));
                })
                ->click('#livewire-error')

                /**
                 * keydown.debounce
                 */
                ->keys('@bap', 'x')
                ->pause(50)
                ->waitForLivewire()->assertDontSeeIn('@output', 'bap')
                ->assertSeeIn('@output', 'bap')

                /**
                 * replacing buttons on DOM tree - using ID
                 */
                ->refresh()
                ->waitForLivewire()->click('@show.button.actions')
                ->assertPresent('@button.with-actions')
                ->waitForLivewire()->click('@button.with-id-and-click')
                ->assertSeeIn('@output', 'button with ID and wire:clicked got triggered')

                /**
                 * replacing buttons on DOM tree - without IDs or :key
                 */
                ->refresh()
                ->waitForLivewire()->click('@show.button.actions')
                ->assertPresent('@button.with-actions')
                ->click('@button.with-click')
                ->assertSeeIn('@output', 'button with wire:clicked got triggered')
            ;
        });
    }
}
