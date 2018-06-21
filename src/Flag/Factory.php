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

use function Inpsyde\MultilingualPress\languageByTag;
use Inpsyde\MultilingualPress\Framework\Language\Language;
use Inpsyde\MultilingualPress\Flags\Core\Admin\SiteSettingsRepository;
use function Inpsyde\MultilingualPress\siteLanguageTag;

/**
 * MultilingualPress Flag Factory
 */
class Factory
{
    /**
     * @var SiteSettingsRepository
     */
    private $settingsRepository;

    /**
     * Factory constructor
     * @param SiteSettingsRepository $settingsRepository
     */
    public function __construct(SiteSettingsRepository $settingsRepository)
    {
        $this->settingsRepository = $settingsRepository;
    }

    /**
     * @param int $siteId
     * @return Flag
     */
    public function create(int $siteId): Flag
    {
        $flag = null;
        $language = languageByTag(siteLanguageTag($siteId));
        $url = $this->flagUrlBySetting($siteId);

        if ($url) {
            $flag = new Raster($siteId, $language, $url);
        }
        if (!$flag) {
            $flag = new Raster($siteId, $language, $this->flag($language));
        }

        return $flag;
    }

    /**
     * @param int $siteId
     * @return string
     */
    private function flagUrlBySetting(int $siteId)
    {
        $siteFlagUrl = $this->settingsRepository->siteFlagUrl($siteId);

        return $siteFlagUrl;
    }

    /**
     * @param Language $language
     * @return string
     */
    private function flag(Language $language)
    {
        $languageCode = $language->isoCode();
        $siteFlagUrl = plugin_dir_url(dirname(__DIR__))
            . "resources/images/flags/{$languageCode}.gif";

        return $siteFlagUrl;
    }
}
