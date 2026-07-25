<?php

namespace Codemonster\Cms\Modules\Settings\Models;

use Codemonster\Database\ORM\Model;

/**
 * @property int|string $id
 * @property string $site_name
 * @property string|null $site_description
 * @property string $locale
 * @property string $timezone
 */
class SiteSetting extends Model
{
    protected string $table = 'site_settings';

    /** @var list<string> */
    protected array $fillable = [
        'id',
        'site_name',
        'site_description',
        'locale',
        'timezone',
    ];
}
