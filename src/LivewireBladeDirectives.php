<?php

namespace Livewire;

use Illuminate\Support\Str;

class LivewireBladeDirectives
{
    public static function livewire($expression)
    {
        $lastArg = trim(last(explode(',', $expression)));

        if (Str::startsWith($lastArg, 'key(') && Str::endsWith($lastArg, ')')) {
            $cachedKey = Str::replaceFirst('key(', '', Str::replaceLast(')', '', $lastArg));
            $args = explode(',', $expression);
            array_pop($args);
            $expression = implode(',', $args);
        } else {
            $cachedKey = "'".Str::random(7)."'";
        }

        return <<<EOT
<?php
if (! isset(\$_instance)) {
    \$response = \Livewire\Livewire::mount({$expression});
    \$dom = \$response instanceof \Livewire\InitialResponsePayload ? \$response->toHtml() : \$response;
} elseif (\$_instance->childHasBeenRendered($cachedKey)) {
    \$componentId = \$_instance->getRenderedChildComponentId($cachedKey);
    \$componentTag = \$_instance->getRenderedChildComponentTagName($cachedKey);
    \$dom = \Livewire\Livewire::dummyMount(\$componentId, \$componentTag);
    \$_instance->preserveRenderedChild($cachedKey);
} else {
    \$response = \Livewire\Livewire::mount({$expression});
    \$dom = \$response->toHtml();
    \$_instance->logRenderedChild($cachedKey, \$response->id, \$response->getRootElementTagName());
}
echo \$dom;
?>
EOT;
    }
}
