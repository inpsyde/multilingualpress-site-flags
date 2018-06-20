<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the MultilingualPress package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Inpsyde\MultilingualPress\Flags\Flag;

use Inpsyde\MultilingualPress\Flags\Core\Admin;
use Inpsyde\MultilingualPress\Flags\Flag\Factory as FlagFactory;
use Inpsyde\MultilingualPress\Framework\Service\Container;
use Inpsyde\MultilingualPress\Framework\Service\ServiceProvider as FrameworkServiceProvider;

final class ServiceProvider implements FrameworkServiceProvider
{
    /**
     * @inheritdoc
     */
    public function register(Container $container)
    {
        $container->addFactory(
            FlagFactory::class,
            function () use ($container): Factory {
                return new Factory(
                    $container[Admin\SiteSettingsRepository::class]
                );
            }
        );
    }
}
