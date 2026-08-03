<?php

namespace Codemonster\Cms\Modules\Settings\Models;

use Codemonster\Database\ORM\Model;

/**
 * @property int|string $id
 * @property string $site_name
 * @property string $locale
 */
class SiteSetting extends Model
{
    protected string $table = 'site_settings';

    /** @var list<string> */
    protected array $fillable = [
        'id',
        'site_name',
        'locale',
    ];
}
