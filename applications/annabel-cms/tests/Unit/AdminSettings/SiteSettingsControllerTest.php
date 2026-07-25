<?php

namespace Codemonster\Cms\Tests\Unit\AdminSettings;

use Codemonster\Cms\Modules\Admin\Contracts\AdminScreenRendererInterface;
use Codemonster\Cms\Modules\AdminSettings\Controllers\SiteSettingsController;
use Codemonster\Cms\Modules\Settings\Models\SiteSetting;
use Codemonster\Cms\Modules\Settings\Services\SiteSettings;
use Codemonster\Http\Request;
use Codemonster\Validation\Validator;
use PHPUnit\Framework\TestCase;

class SiteSettingsControllerTest extends TestCase
{
    public function testItUpdatesValidatedSiteSettings(): void
    {
        $repository = $this->createMock(SiteSettings::class);
        $repository->expects(self::once())
            ->method('update')
            ->with([
                'site_name' => 'Example site',
                'locale' => 'en-US',
                'timezone' => 'Europe/Paris',
            ])
            ->willReturn($this->settings());

        $response = $this->controller($repository)->update(new Request('POST', '/', [], [
            'site_name' => ' Example site ',
            'locale' => 'en-US',
            'timezone' => 'Europe/Paris',
        ]));
        $payload = json_decode((string) $response->getContent(), true);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('Site settings updated successfully.', $payload['message']);
        self::assertSame('Example site', $payload['settings']['site_name']);
    }

    public function testItRejectsInvalidLocale(): void
    {
        $repository = $this->createMock(SiteSettings::class);
        $repository->expects(self::never())->method('update');

        $response = $this->controller($repository)->update(new Request('POST', '/', [], [
            'site_name' => 'Example site',
            'locale' => 'not_a_locale',
            'timezone' => 'UTC',
        ]));
        $payload = json_decode((string) $response->getContent(), true);

        self::assertSame(422, $response->getStatusCode());
        self::assertSame('The locale must be a valid language tag.', $payload['errors']['locale'][0]);
    }

    private function controller(SiteSettings $settings): SiteSettingsController
    {
        return new SiteSettingsController(
            $this->createStub(AdminScreenRendererInterface::class),
            $settings,
            new Validator(),
        );
    }

    private function settings(): SiteSetting
    {
        return new SiteSetting([
            'id' => 1,
            'site_name' => 'Example site',
            'locale' => 'en-US',
            'timezone' => 'Europe/Paris',
        ], true);
    }
}
