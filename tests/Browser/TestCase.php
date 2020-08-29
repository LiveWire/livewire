<?php

namespace Tests\Browser;

use Closure;
use Exception;
use Psy\Shell;
use Throwable;
use Laravel\Dusk\Browser;
use Illuminate\Support\Facades\File;
use Livewire\LivewireServiceProvider;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Assert as PHPUnit;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Orchestra\Testbench\Dusk\Options as DuskOptions;
use Orchestra\Testbench\Dusk\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    public function setUp(): void
    {
        if (isset($_SERVER['CI'])) {
            DuskOptions::withoutUI();
        }

        $this->registerMacros();

        $this->afterApplicationCreated(function () {
            $this->makeACleanSlate();
        });

        $this->beforeApplicationDestroyed(function () {
            $this->makeACleanSlate();
        });

        parent::setUp();

        $this->tweakApplication(function () {
            app('livewire')->component(\Tests\Browser\Loading\Component::class);
            app('livewire')->component(\Tests\Browser\PushState\Component::class);
            app('livewire')->component(\Tests\Browser\PushState\NestedComponent::class);
            app('livewire')->component(\Tests\Browser\DataBinding\InputSelect\Component::class);
            app('livewire')->component(\Tests\Browser\FileDownloads\Component::class);
            app('livewire')->component(\Tests\Browser\Redirects\Component::class);
            app('livewire')->component(\Tests\Browser\SupportCollections\Component::class);
            app('livewire')->component(\Tests\Browser\Events\Component::class);
            app('livewire')->component(\Tests\Browser\Events\NestedComponentA::class);
            app('livewire')->component(\Tests\Browser\Events\NestedComponentB::class);
            app('livewire')->component(\Tests\Browser\Prefetch\Component::class);
            app('livewire')->component(\Tests\Browser\SupportDateTimes\Component::class);
            app('livewire')->component(\Tests\Browser\DataBinding\InputText\Component::class);
            app('livewire')->component(\Tests\Browser\DataBinding\InputTextarea\Component::class);
            app('livewire')->component(\Tests\Browser\DataBinding\InputCheckboxRadio\Component::class);
            app('livewire')->component(\Tests\Browser\Actions\Component::class);
            app('livewire')->component(\Tests\Browser\Init\Component::class);
            app('livewire')->component(\Tests\Browser\Dirty\Component::class);
            app('livewire')->component(\Tests\Browser\Alpine\Component::class);
            app('livewire')->component(\Tests\Browser\Hooks\Component::class);

            app('session')->put('_token', 'this-is-a-hack-because-something-about-validating-the-csrf-token-is-broken');

            app('config')->set('view.paths', [
                __DIR__.'/views',
                resource_path('views'),
            ]);

            config()->set('app.debug', true);
        });
    }

    protected function tearDown(): void
    {
        $this->removeApplicationTweaks();

        parent::tearDown();
    }

    public function makeACleanSlate()
    {
        Artisan::call('view:clear');

        File::deleteDirectory($this->livewireViewsPath());
        File::cleanDirectory(__DIR__.'/downloads');
        File::deleteDirectory($this->livewireClassesPath());
        File::delete(app()->bootstrapPath('cache/livewire-components.php'));
    }

    protected function getPackageProviders($app)
    {
        return [
            LivewireServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('view.paths', [
            __DIR__.'/views',
            resource_path('views'),
        ]);

        $app['config']->set('app.key', 'base64:Hupx3yAySikrM2/edkZQNQHslgDWYfiBfCuSThJ5SK8=');

        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $app['config']->set('filesystems.disks.dusk-downloads', [
            'driver' => 'local',
            'root' => __DIR__.'/downloads',
        ]);
    }

    protected function resolveApplicationHttpKernel($app)
    {
        $app->singleton('Illuminate\Contracts\Http\Kernel', 'Tests\HttpKernel');
    }

    protected function livewireClassesPath($path = '')
    {
        return app_path('Http/Livewire'.($path ? '/'.$path : ''));
    }

    protected function livewireViewsPath($path = '')
    {
        return resource_path('views').'/livewire'.($path ? '/'.$path : '');
    }

    protected function registerMacros()
    {
        Browser::macro('assertNotVisible', function ($selector) {
            $fullSelector = $this->resolver->format($selector);

            PHPUnit::assertFalse(
                $this->resolver->findOrFail($selector)->isDisplayed(),
                "Element [{$fullSelector}] is visible."
            );

            return $this;
        });

        Browser::macro('assertNotPresent', function ($selector) {
            $fullSelector = $this->resolver->format($selector);

            PHPUnit::assertTrue(
                is_null($this->resolver->find($selector)),
                "Element [{$fullSelector}] is present."
            );

            return $this;
        });

        Browser::macro('waitForLivewire', function () {
            return $this->waitForLivewireRequest()->waitForLivewireResponse();
        });

        Browser::macro('waitForLivewireRequest', function () {
            return $this->waitUsing(5, 25, function () {
                return $this->driver->executeScript('return window.livewire.requestIsInProgress() === true');
            }, 'Livewire request was never triggered');
        });

        Browser::macro('waitForLivewireResponse', function () {
            return $this->waitUsing(5, 25, function () {
                return $this->driver->executeScript('return window.livewire.requestIsDone() === true');
            }, 'Livewire response was never received');
        });

        Browser::macro('captureLivewireRequest', function () {
            $this->driver->executeScript('window.capturedRequestsForDusk = []');

            return $this;
        });

        Browser::macro('replayLivewireRequest', function () {
            $this->driver->executeScript('window.capturedRequestsForDusk.forEach(callback => callback()); delete window.capturedRequestsForDusk;');

            return $this;
        });

        Browser::macro('assertScript', function () {
            PHPUnit::assertTrue(
                $this->driver->executeScript('return window.livewire.requestIsOut() === false'),
                'Something this'
            );

            return $this;

        });
    }

    protected function driver(): RemoteWebDriver
    {
        $options = DuskOptions::getChromeOptions();

        $options->setExperimentalOption('prefs', [
            'download.default_directory' => __DIR__.'/downloads',
        ]);

        return RemoteWebDriver::create(
            'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY,
                $options
            )
        );
    }

    public function browse(Closure $callback)
    {
        parent::browse(function (...$browsers) use ($callback) {
            try {
                $callback(...$browsers);
            } catch (Exception $e) {
                if (DuskOptions::hasUI()) $this->breakIntoATinkerShell($browsers, $e);

                throw $e;
            } catch (Throwable $e) {
                if (DuskOptions::hasUI()) $this->breakIntoATinkerShell($browsers, $e);

                throw $e;
            }
        });
    }

    public function breakIntoATinkerShell($browsers, $e)
    {
        $sh = new Shell();

        $sh->add(new DuskCommand($this, $e));

        $sh->setScopeVariables([
            'browsers' => $browsers,
        ]);

        $sh->addInput('dusk');

        $sh->setBoundObject($this);

        $sh->run();

        return $sh->getScopeVariables(false);
    }
}
