<?php # -*- coding: utf-8 -*-
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
 * Plugin URI: https://wordpress.org/plugins/multilingualpress/
 * Description: Add flags feature to multilingualpress plugin.
 * Author: Inpsyde GmbH
 * Author URI: https://inpsyde.com
 * Version: 1.0.0
 * Text Domain: multilingualpress
 * Domain Path: /languages/
 * License: MIT
 * Network: true
 * Requires at least: 4.8
 * Requires PHP: 7.0
 */

// phpcs:disable PSR1.Files.SideEffects
// phpcs:disable NeutronStandard.StrictTypes.RequireStrictTypes
// phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration

namespace Inpsyde\MultilingualPress\Flags;

use Inpsyde\MultilingualPress\Flags\Flag;
use Inpsyde\MultilingualPress\Framework\Service\ServiceProvidersCollection;

defined('ABSPATH') or die();

if (version_compare(PHP_VERSION, '7', '<')) {
    wp_die(
        esc_html__(
            'MultilingualPress Flags requires at least PHP version 7.',
            'multilingualpress'
        )
        . '<br>' .
        esc_html__(
            'Please ask your server administrator to update your environment to PHP version 7.',
            'multilingualpress'
        ),
        esc_html__('MultilingualPress Flags Activation', 'multilingualpress')
    );
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
            ->add(new NavMenu\ServiceProvider());
    },
    0
);
