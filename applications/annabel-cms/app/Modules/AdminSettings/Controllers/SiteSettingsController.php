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
            'csrf_token' => csrf_token(),
        ]);
    }

    public function update(Request $request): Response
    {
        $validated = $this->validator->validateOrFail([
            'site_name' => trim((string) $request->input('site_name')),
            'locale' => trim((string) $request->input('locale')),
        ], [
            'site_name' => 'required|string|max:120',
            'locale' => 'required|string|max:20',
        ], [
            'site_name' => 'site name',
        ]);

        if (preg_match('/\A[a-zA-Z]{2,3}(?:-[a-zA-Z0-9]{2,8})*\z/', $validated['locale']) !== 1) {
            return $this->invalidSetting('locale', 'The locale must be a valid language tag.');
        }

        $settings = $this->settings->update([
            'site_name' => $validated['site_name'],
            'locale' => $validated['locale'],
        ]);

        return Response::json([
            'message' => 'Site settings updated successfully.',
            'settings' => $this->payload($settings),
        ]);
    }

    /** @return array{site_name: string, locale: string} */
    private function payload(SiteSetting $settings): array
    {
        return [
            'site_name' => (string) $settings->site_name,
            'locale' => (string) $settings->locale,
        ];
    }

    private function invalidSetting(string $field, string $message): Response
    {
        return Response::json([
            'message' => 'The given data was invalid.',
            'errors' => [$field => [$message]],
        ], 422);
    }
}
