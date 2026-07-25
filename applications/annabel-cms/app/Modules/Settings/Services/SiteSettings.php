<?php

namespace Codemonster\Cms\Modules\Settings\Services;

use Codemonster\Cms\Modules\Settings\Models\SiteSetting;

class SiteSettings
{
    private const SETTINGS_ID = 1;

    public function current(): SiteSetting
    {
        $settings = SiteSetting::find(self::SETTINGS_ID);

        if (!$settings instanceof SiteSetting) {
            throw new \RuntimeException('Site settings are missing. Run the CMS database seeds.');
        }

        return $settings;
    }

    /**
     * @param array{site_name: string, locale: string, timezone: string} $attributes
     */
    public function update(array $attributes): SiteSetting
    {
        $settings = $this->current();
        $settings->fill($attributes);
        $settings->save();

        return $settings;
    }
}
