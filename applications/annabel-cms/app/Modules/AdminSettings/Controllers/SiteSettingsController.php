<?php

namespace Codemonster\Cms\Modules\AdminSettings\Controllers;

use Codemonster\Cms\Modules\Admin\Contracts\AdminScreenRendererInterface;
use Codemonster\Cms\Modules\Settings\Models\SiteSetting;
use Codemonster\Cms\Modules\Settings\Services\SiteSettings;
use Codemonster\Http\Request;
use Codemonster\Http\Response;
use Codemonster\Validation\Validator;

class SiteSettingsController
{
    public function __construct(
        private AdminScreenRendererInterface $admin,
        private SiteSettings $settings,
        private Validator $validator,
    ) {
    }

    public function index(): Response
    {
        return $this->admin->renderAuthenticated('admin.settings.general');
    }

    public function data(): Response
    {
        return Response::json([
            'settings' => $this->payload($this->settings->current()),
            'timezone_options' => $this->timezoneOptions(),
            'csrf_token' => csrf_token(),
        ]);
    }

    public function update(Request $request): Response
    {
        $validated = $this->validator->validateOrFail([
            'site_name' => trim((string) $request->input('site_name')),
            'site_description' => trim((string) $request->input('site_description')),
            'locale' => trim((string) $request->input('locale')),
            'timezone' => trim((string) $request->input('timezone')),
        ], [
            'site_name' => 'required|string|max:120',
            'site_description' => 'nullable|string|max:500',
            'locale' => 'required|string|max:20',
            'timezone' => 'required|string|max:64',
        ], [
            'site_name' => 'site name',
            'site_description' => 'site description',
        ]);

        if (preg_match('/\A[a-zA-Z]{2,3}(?:-[a-zA-Z0-9]{2,8})*\z/', $validated['locale']) !== 1) {
            return $this->invalidSetting('locale', 'The locale must be a valid language tag.');
        }

        if (!in_array($validated['timezone'], timezone_identifiers_list(), true)) {
            return $this->invalidSetting('timezone', 'The timezone must be valid.');
        }

        $settings = $this->settings->update([
            'site_name' => $validated['site_name'],
            'site_description' => $validated['site_description'] === '' ? null : $validated['site_description'],
            'locale' => $validated['locale'],
            'timezone' => $validated['timezone'],
        ]);

        return Response::json([
            'message' => 'Site settings updated successfully.',
            'settings' => $this->payload($settings),
        ]);
    }

    /** @return array{site_name: string, site_description: string, locale: string, timezone: string} */
    private function payload(SiteSetting $settings): array
    {
        return [
            'site_name' => (string) $settings->site_name,
            'site_description' => (string) ($settings->site_description ?? ''),
            'locale' => (string) $settings->locale,
            'timezone' => (string) $settings->timezone,
        ];
    }

    /** @return list<array{value: string, label: string}> */
    private function timezoneOptions(): array
    {
        return array_map(
            static fn (string $timezone): array => ['value' => $timezone, 'label' => $timezone],
            timezone_identifiers_list(),
        );
    }

    private function invalidSetting(string $field, string $message): Response
    {
        return Response::json([
            'message' => 'The given data was invalid.',
            'errors' => [$field => [$message]],
        ], 422);
    }
}
