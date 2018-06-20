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

namespace Inpsyde\MultilingualPress\Flags\NavMenu;

use Inpsyde\MultilingualPress\Flags\Core\Admin\SiteSettingsRepository;
use Inpsyde\MultilingualPress\Flags\Flag\Factory as FlagFactory;
use Inpsyde\MultilingualPress\Framework\Service\BootstrappableServiceProvider;
use Inpsyde\MultilingualPress\Framework\Service\Container;

/**
 * Class ServiceProvider
 */
final class ServiceProvider implements BootstrappableServiceProvider
{
    /**
     * @inheritdoc
     */
    public function register(Container $container)
    {
        $container->addService(
            NavMenuLanguageStyleFilter::class,
            function (Container $container): NavMenuLanguageStyleFilter {
                return new NavMenuLanguageStyleFilter(
                    $container[SiteSettingsRepository::class],
                    $container[FlagFactory::class]
                );
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function bootstrap(Container $container)
    {
        add_filter(
            'nav_menu_item_title',
            [$container[NavMenuLanguageStyleFilter::class], 'filterItem'],
            10,
            2
        );
    }
}
