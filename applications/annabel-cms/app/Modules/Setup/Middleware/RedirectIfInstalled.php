<?php

namespace Codemonster\Cms\Modules\Setup\Middleware;

use Codemonster\Cms\Modules\Setup\Services\InstallationState;
use Codemonster\Http\Request;
use Codemonster\Http\Response;

class RedirectIfInstalled
{
    public function __construct(
        private InstallationState $state,
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        if ($this->state->isInstalled()) {
            return $request->wantsJson()
                ? Response::json([
                    'message' => 'CMS is already installed.',
                    'redirect' => '/admin/login',
                ], 409)
                : Response::redirect('/admin/login');
        }

        $response = $next($request);

        return $response instanceof Response
            ? $response
            : new Response((string) $response);
    }
}
