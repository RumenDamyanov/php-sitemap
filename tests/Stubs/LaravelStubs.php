<?php

/**
 * Laravel contract stubs for testing without Laravel installation.
 *
 * These stubs allow testing Laravel adapters without requiring
 * the full Laravel framework as a dependency.
 */

namespace Illuminate\Contracts\Cache
{
    if (!interface_exists(Repository::class)) {
        interface Repository
        {
        }
    }
}

namespace Illuminate\Contracts\Config
{
    if (!interface_exists(Repository::class)) {
        interface Repository
        {
        }
    }
}

namespace Illuminate\Filesystem
{
    if (!class_exists(Filesystem::class)) {
        class Filesystem
        {
        }
    }
}

namespace Illuminate\Contracts\Routing
{
    if (!interface_exists(ResponseFactory::class)) {
        interface ResponseFactory
        {
        }
    }
}

namespace Illuminate\Contracts\View
{
    if (!interface_exists(Factory::class)) {
        interface Factory
        {
        }
    }
}
