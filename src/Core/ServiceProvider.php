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

use Inpsyde\MultilingualPress\Core\Admin\SiteSettings as ParentSiteSettings;
use Inpsyde\MultilingualPress\Core\Admin\NewSiteSettings as ParentNewSiteSettings;
use Inpsyde\MultilingualPress\Core\Admin\SiteSettingsUpdater as ParentSiteSettingsUpdater;
use Inpsyde\MultilingualPress\Core\Admin\SiteSettingsUpdateRequestHandler as ParentSiteSiteSettingsUpdateRequestHandler;
use Inpsyde\MultilingualPress\Core\Locations;
use Inpsyde\MultilingualPress\Flags\PluginProperties;
use Inpsyde\MultilingualPress\Framework\Asset\AssetManager;
use Inpsyde\MultilingualPress\Framework\Factory\NonceFactory;
use Inpsyde\MultilingualPress\Framework\Http\ServerRequest;
use Inpsyde\MultilingualPress\Framework\Service\BootstrappableServiceProvider;
use Inpsyde\MultilingualPress\Framework\Service\Container;
use Inpsyde\MultilingualPress\Framework\Setting\Site\SiteSettingMultiView;
use Inpsyde\MultilingualPress\Framework\Setting\Site\SiteSettingsSectionView;
use Inpsyde\MultilingualPress\Flags\Core\Admin\SiteFlagUrlSetting;
use Inpsyde\MultilingualPress\Flags\Core\Admin\SiteMenuLanguageStyleSetting;

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
        $this->registerCore($container);
        $this->registerAdmin($container);

        $container->shareValue(
            PluginProperties::class,
            new PluginProperties(dirname(__DIR__))
        );
    }

    /**
     * @param Container $container
     */
    private function registerCore(Container $container)
    {
        $container->share(
            'FlagsLocations',
            function (Container $container): Locations {

                $properties = $container[PluginProperties::class];
                $pluginPath = rtrim($properties->dirPath(), '/');
                $pluginUrl = rtrim($properties->dirUrl(), '/');
                $assetsPath = "{$pluginPath}/public";
                $assetsUrl = "{$pluginUrl}/public";

                $locations = new Locations();

                return $locations
                    ->add('plugin', $pluginPath, $pluginUrl)
                    ->add('css', "{$assetsPath}/css", "{$assetsUrl}/css")
                    ->add('js', "{$assetsPath}/js", "{$assetsUrl}/js");
            }
        );
    }

    /**
     * @inheritdoc
     */
    private function registerAdmin(Container $container)
    {
        $container->share(
            Admin\SiteSettingsRepository::class,
            function (Container $container): Admin\SiteSettingsRepository {
                return new Admin\SiteSettingsRepository();
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
            function (Container $container): ParentSiteSettings {
                return new ParentSiteSettings(
                    SiteSettingMultiView::fromViewModels(
                        [
                            $container[Admin\SiteFlagUrlSetting::class],
                            $container[Admin\SiteMenuLanguageStyleSetting::class],
                        ]
                    ),
                    $container[AssetManager::class]
                );
            }
        );

        $container->addService(
            'FlagsNewSiteSettings',
            function (Container $container): ParentNewSiteSettings {
                return new ParentNewSiteSettings(
                    SiteSettingMultiView::fromViewModels(
                        [
                            $container[Admin\SiteFlagUrlSetting::class],
                            $container[Admin\SiteMenuLanguageStyleSetting::class],
                        ]
                    ),
                    $container[AssetManager::class]
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
            function (Container $container): ParentSiteSiteSettingsUpdateRequestHandler {
                return new ParentSiteSiteSettingsUpdateRequestHandler(
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
        if (is_admin()) {
            $this->bootstrapAdmin($container);
            is_network_admin() and $this->bootstrapNetworkAdmin($container);

            return;
        }

        $this->bootstrapFrontend($container);
    }

    /**
     * @param Container $container
     */
    public function bootstrapAdmin(Container $container)
    {
        $flagSiteSettingsUpdateHandler = $container['FlagSiteSettingsUpdateHandler'];

        add_action(SiteSettingsSectionView::ACTION_AFTER . '_mlp-site-settings', [
            $container['FlagsSiteSettings'],
            'renderView',
        ]);

        add_action(
            ParentSiteSettingsUpdater::ACTION_UPDATE_SETTINGS,
            function () use ($flagSiteSettingsUpdateHandler) {
                $flagSiteSettingsUpdateHandler->handlePostRequest();
            },
            20
        );
    }

    /**
     * @param Container $container
     */
    public function bootstrapFrontend(Container $container)
    {
        $container[AssetManager::class]->enqueueStyle('multilingualpress-site-flags-front');
    }

    /**
     * @param Container $container
     */
    public function bootstrapNetworkAdmin(Container $container)
    {
        $newSiteSettings = $container['FlagsNewSiteSettings'];

        add_action(
            SiteSettingsSectionView::ACTION_AFTER . '_mlp-new-site-settings',
            function ($siteId) use ($newSiteSettings) {
                $newSiteSettings->renderView((int)$siteId);
            }
        );

        add_action(
            ParentSiteSettingsUpdater::ACTION_DEFINE_INITIAL_SETTINGS,
            [$container[Admin\SiteSettingsUpdater::class], 'defineInitialSettings']
        );
    }
}
