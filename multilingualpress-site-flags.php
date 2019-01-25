<?php # -*- coding: utf-8 -*-
// phpcs:disable
/*
 * This file is part of the MultilingualPress package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @wordpress-plugin
 * Plugin Name: MultilingualPress Site Flags
 * Plugin URI: https://multilingualpress.org/
 * Description: Add flags to MultilingualPress plugin
 * Author: Inpsyde GmbH
 * Author URI: https://inpsyde.com
 * Version: 1.1.1
 * Text Domain: multilingualpress
 * Domain Path: /languages/
 * License: MIT
 * Network: true
 * Requires at least: 4.8
 * Requires PHP: 7.0
 */

namespace Inpsyde\MultilingualPress\Flags;

use Inpsyde\MultilingualPress\Flags\Flag;
use Inpsyde\MultilingualPress\Framework\Service\ServiceProvidersCollection;

defined('ABSPATH') or die();

(function () {
    /**
     * @param $function
     */
    function deactivateNotice($function)
    {
        if (!is_callable($function)) {
            return;
        }
        $hooks = [
            'admin_notices',
            'network_admin_notices',
        ];
        foreach ($hooks as $hook) {
            add_action($hook, function () use ($function) {
                $function();

                deactivate_plugins(plugin_basename(__FILE__));

                // Suppress the "Plugin activated" notice.
                unset($_GET['activate']); // phpcs:ignore
            });
        }
    }

    function requirements()
    {
        if (!function_exists('is_plugin_active')) {
            /** @noinspection PhpIncludeInspection */
            require_once untrailingslashit(ABSPATH) . '/wp-admin/includes/plugin.php';
        }
    }

    function autoload()
    {
        static $done;
        if (is_bool($done)) {
            return $done;
        }
        if (class_exists(Flag\Flag::class)) {
            $done = true;

            return true;
        }
        if (is_readable(__DIR__ . '/autoload.php')) {
            /** @noinspection PhpIncludeInspection */
            require_once __DIR__ . '/autoload.php';
            $done = true;

            return true;
        }
        if (is_readable(__DIR__ . '/vendor/autoload.php')) {
            require_once __DIR__ . '/vendor/autoload.php';
            $done = true;

            return true;
        }
        $done = false;

        return false;
    }

    requirements();

    if (version_compare(PHP_VERSION, '7', '<')) {
        deactivateNotice(function () {
            $message = __(
                'MultilingualPress Flags requires at least PHP version 7. <br />Please ask your server administrator to update your environment to PHP version 7.',
                'multilingualpress'
            );

            printf(
                '<div class="notice notice-error"><span class="notice-title">%1$s</span><p>%2$s</p></div>',
                esc_html__(
                    'The plugin MultilingualPress Flags has been deactivated',
                    'multilingualpress'
                ),
                wp_kses($message, ['br' => true])
            );

            deactivate_plugins(plugin_basename(__FILE__));
        });
        return;
    }

    if (!is_plugin_active('multilingualpress/multilingualpress.php')) {
        deactivateNotice(function () {
            $message = __(
                'You tried to activate MultilingualPress Site Flags but MultilingualPress plugin isn\'t active.',
                'multilingualpress'
            );

            printf(
                '<div class="notice notice-error"><span class="notice-title">%1$s</span><p>%2$s</p></div>',
                esc_html__(
                    'The plugin MultilingualPress Site Flags has been deactivated',
                    'multilingualpress'
                ),
                wp_kses($message, ['a' => ['href' => true]])
            );
        });
        return;
    }

    if (!autoload()) {
        return;
    }

    /**
     * Bootstraps MultilingualPress Language Flags.
     *
     * @return bool
     *
     * @wp-hook multilingualpress.add_service_providers
     */
    add_action(
        'multilingualpress.add_service_providers',
        function (ServiceProvidersCollection $providers) {
            $providers
                ->add(new Asset\ServiceProvider())
                ->add(new Core\ServiceProvider())
                ->add(new Flag\ServiceProvider())
                ->add(new ServiceProvider());
        },
        0
    );
})();
