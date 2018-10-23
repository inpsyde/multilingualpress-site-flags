<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the MultilingualPress Site Flag package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Inpsyde\MultilingualPress\Flags;

use Inpsyde\MultilingualPress\Flags\Core\Admin\SiteSettingsRepository;
use Inpsyde\MultilingualPress\Flags\Flag\Factory as FlagFactory;
use Inpsyde\MultilingualPress\Framework\Service\BootstrappableServiceProvider;
use Inpsyde\MultilingualPress\Framework\Service\Container;
use Inpsyde\MultilingualPress\TranslationUi\Post\TableList;

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
            FlagFilter::class,
            function (Container $container): FlagFilter {
                return new FlagFilter(
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
        $flagFilter = $container[FlagFilter::class];

        add_filter('nav_menu_item_title', [$flagFilter, 'navMenuItems'], 10, 2);
        add_filter(
            TableList::FILTER_SITE_LANGUAGE_TAG,
            [$flagFilter, 'tableListPostsRelations'],
            10,
            2
        );
    }
}
