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

use Inpsyde\MultilingualPress\Flags\Core\Admin\SiteMenuLanguageStyleSetting;
use Inpsyde\MultilingualPress\Flags\Core\Admin\SiteSettingsRepository;
use Inpsyde\MultilingualPress\Flags\Flag\Factory;
use Inpsyde\MultilingualPress\NavMenu\ItemRepository;

/**
 * Class NavMenuLanguageStyleFilter
 *
 * @package Inpsyde\MultilingualPress\Flags\NavMenu
 */
class NavMenuLanguageStyleFilter
{
    /**
     * @var SiteSettingsRepository
     */
    private $settingsRepository;

    /**
     * @var Factory
     */
    private $flagFactory;

    /**
     * NavMenuLanguageStyleFilter constructor
     * @param SiteSettingsRepository $settingsRepository
     * @param Factory $flagFactory
     */
    public function __construct(SiteSettingsRepository $settingsRepository, Factory $flagFactory)
    {
        $this->settingsRepository = $settingsRepository;
        $this->flagFactory = $flagFactory;
    }

    /**
     * @param string $title
     * @param \WP_Post $item
     * @return string
     */
    public function filterItem(string $title, \WP_Post $item): string
    {
        if ($item->object !== ItemRepository::ITEM_TYPE) {
            return $title;
        }

        $siteId = (int)get_post_meta($item->ID, ItemRepository::META_KEY_SITE_ID, true);
        $menuStyle = $this->settingsRepository->siteMenuLanguageStyle(get_current_blog_id());
        $flag = $this->flagFactory->create($siteId);
        $menuUseFlags = [
            SiteMenuLanguageStyleSetting::FLAG_AND_LANGUAGES,
            SiteMenuLanguageStyleSetting::ONLY_FLAGS,
        ];

        if ($menuStyle === SiteMenuLanguageStyleSetting::ONLY_FLAGS) {
            $title = "<span class=\"screen-reader-text\">{$title}</span>";
        }
        if (in_array($menuStyle, $menuUseFlags, true)) {
            $title = $flag->markup() . ' ' . $title;
        }

        return $title;
    }
}
