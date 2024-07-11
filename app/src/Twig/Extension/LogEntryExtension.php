<?php

namespace App\Twig\Extension;

use App\Twig\Runtime\LogEntryExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class LogEntryExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('logUserName', [LogEntryExtensionRuntime::class, 'userName']),
            new TwigFilter('logAction', [LogEntryExtensionRuntime::class, 'action']),
            new TwigFilter('logEntityLink', [LogEntryExtensionRuntime::class, 'entityLink'], ['is_safe' => ['html']]),
        ];
    }
}
