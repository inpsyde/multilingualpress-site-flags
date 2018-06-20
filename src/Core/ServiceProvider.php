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

namespace Inpsyde\MultilingualPress\Flags\Core;

use Inpsyde\MultilingualPress\Core\Admin\SiteSettingsRepository as FrameworkSiteSettingsRepository;
use Inpsyde\MultilingualPress\Core\Admin\SiteSettings as FrameworkSiteSettings;
use \Inpsyde\MultilingualPress\Core\Admin\SiteSettingsUpdater as FrameworkSiteSettingsUpdater;
use Inpsyde\MultilingualPress\Core\Admin\SiteSettingsUpdateRequestHandler as FrameworkSiteSiteSettingsUpdateRequestHandler;
use Inpsyde\MultilingualPress\Flags\Core\Admin\SiteFlagUrlSetting;
use Inpsyde\MultilingualPress\Flags\Core\Admin\SiteMenuLanguageStyleSetting;
use Inpsyde\MultilingualPress\Framework\Factory\NonceFactory;
use Inpsyde\MultilingualPress\Framework\Http\ServerRequest;
use Inpsyde\MultilingualPress\Framework\Service\BootstrappableServiceProvider;
use Inpsyde\MultilingualPress\Framework\Service\Container;
use Inpsyde\MultilingualPress\Framework\Setting\Site\SiteSettingMultiView;
use Inpsyde\MultilingualPress\Framework\Setting\Site\SiteSettingsSectionView;

/**
 * Service provider for all Core objects.
 *
 * phpcs:disable Inpsyde.CodeQuality.FunctionLength.TooLong
 */
final class ServiceProvider implements BootstrappableServiceProvider
{
    /**
     * @inheritdoc
     */
    public function register(Container $container)
    {
        $this->registerAdmin($container);
    }

    /**
     * @inheritdoc
     */
    private function registerAdmin(Container $container)
    {
        $container->share(
            Admin\SiteSettingsRepository::class,
            function (Container $container): Admin\SiteSettingsRepository {
                return new Admin\SiteSettingsRepository(
                    $container[FrameworkSiteSettingsRepository::class]
                );
            }
        );

        $container->addService(
            Admin\SiteFlagUrlSetting::class,
            function (Container $container): SiteFlagUrlSetting {
                return new Admin\SiteFlagUrlSetting(
                    $container[Admin\SiteSettingsRepository::class]
                );
            }
        );

        $container->addService(
            Admin\SiteMenuLanguageStyleSetting::class,
            function (Container $container): SiteMenuLanguageStyleSetting {
                return new Admin\SiteMenuLanguageStyleSetting(
                    $container[Admin\SiteSettingsRepository::class]
                );
            }
        );

        $container->addService(
            'FlagsSiteSettings',
            function (Container $container): FrameworkSiteSettings {
                return new FrameworkSiteSettings(
                    SiteSettingMultiView::fromViewModels(
                        [
                            $container[Admin\SiteFlagUrlSetting::class],
                            $container[Admin\SiteMenuLanguageStyleSetting::class],
                        ]
                    )
                );
            }
        );

        $container->addService(
            Admin\SiteSettingsUpdater::class,
            function (Container $container): Admin\SiteSettingsUpdater {
                return new Admin\SiteSettingsUpdater(
                    $container[Admin\SiteSettingsRepository::class],
                    $container[ServerRequest::class]
                );
            }
        );

        $container->addService(
            'FlagSiteSettingsUpdateHandler',
            function (Container $container): FrameworkSiteSiteSettingsUpdateRequestHandler {
                return new FrameworkSiteSiteSettingsUpdateRequestHandler(
                    $container[Admin\SiteSettingsUpdater::class],
                    $container[ServerRequest::class],
                    $container[NonceFactory::class]->create(['save_site_settings'])
                );
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function bootstrap(Container $container)
    {
        $flagSiteSettingsUpdateHandler = $container[ 'FlagSiteSettingsUpdateHandler'];

        add_action(SiteSettingsSectionView::ACTION_AFTER . '_mlp-site-settings', [
            $container['FlagsSiteSettings'],
            'renderView',
        ]);

        add_action(
            FrameworkSiteSettingsUpdater::ACTION_UPDATE_SETTINGS,
            function () use ($flagSiteSettingsUpdateHandler) {
                $flagSiteSettingsUpdateHandler->handlePostRequest();
            }
        );
    }
}
