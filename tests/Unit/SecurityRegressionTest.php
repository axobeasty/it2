<?php

use App\Support\PageAccess;
use Illuminate\Routing\Route as IlluminateRoute;
use Illuminate\Support\Facades\Route;

function routeByUriAndMethod(string $uri, string $method): ?IlluminateRoute
{
    $routes = collect(Route::getRoutes()->getRoutes());

    return $routes->first(static fn (IlluminateRoute $route) => $route->uri() === ltrim($uri, '/') && in_array(strtoupper($method), $route->methods(), true));
}

test('sensitive state-changing routes are not exposed via GET', function () {
    $routes = [
        'employees/delete/{id}' => 'DELETE',
        'employees/deactivate/{id}' => 'PATCH',
        'employees/activate/{id}' => 'PATCH',
        'roles/{id}/delete' => 'DELETE',
        'groups/{id}/delete' => 'DELETE',
        'groups/students/{id}/detach' => 'DELETE',
        'schedule/entries/{id}/delete' => 'DELETE',
        'schedule/constructor/subjects/{id}/delete' => 'DELETE',
        'inv/departments/delete/{id}' => 'DELETE',
        'orders/{id}/status/set/{code}' => 'PATCH',
        'orders/categories/delete/{id}' => 'DELETE',
        'portfolio/types/{id}/delete' => 'DELETE',
        'settings/general/site/disable' => 'PATCH',
        'settings/general/site/enable' => 'PATCH',
        'notifications/mark-all-read' => 'POST',
        'wiki/store' => 'POST',
    ];

    foreach ($routes as $uri => $method) {
        $route = routeByUriAndMethod($uri, $method);
        expect($route)->not->toBeNull("Route {$uri} must allow {$method}.");
        expect($route->methods())->not->toContain('GET', "Route {$uri} must not allow GET.");
    }
});

test('login endpoints are protected by custom throttle middleware', function () {
    $webLoginRoute = routeByUriAndMethod('auth', 'POST');
    expect($webLoginRoute)->not->toBeNull('Web login route must exist.');
    expect($webLoginRoute->gatherMiddleware())->toContain('throttle:login-web');

    $mobileLoginRoute = routeByUriAndMethod('api/mobile/login', 'POST');
    if (! $mobileLoginRoute) {
        $mobileLoginRoute = routeByUriAndMethod('mobile/login', 'POST');
    }
    expect($mobileLoginRoute)->not->toBeNull('Mobile API login route must exist.');
    expect($mobileLoginRoute->gatherMiddleware())->toContain('throttle:login-mobile');
});

test('wiki mutating routes do not allow GET', function () {
    $store = routeByUriAndMethod('wiki/store', 'POST');
    expect($store)->not->toBeNull('wiki.store must exist.');
    expect($store->methods())->not->toContain('GET');

    $update = Route::getRoutes()->getByName('wiki.update');
    expect($update)->not->toBeNull();
    expect($update->methods())->toContain('PATCH');
    expect($update->methods())->not->toContain('GET');

    $destroy = Route::getRoutes()->getByName('wiki.destroy');
    expect($destroy)->not->toBeNull();
    expect($destroy->methods())->toContain('DELETE');
    expect($destroy->methods())->not->toContain('GET');
});

test('page access map includes wiki paths in correct order', function () {
    $keys = array_keys(PageAccess::MAP);
    $editIdx = array_search('knowledge_wiki_edit', $keys, true);
    $readIdx = array_search('knowledge_wiki', $keys, true);
    expect($editIdx)->not->toBeFalse();
    expect($readIdx)->not->toBeFalse();
    expect($editIdx)->toBeLessThan($readIdx);

    $edit = PageAccess::MAP['knowledge_wiki_edit'] ?? [];
    $read = PageAccess::MAP['knowledge_wiki'] ?? [];
    expect($edit)->toContain('/wiki/create');
    expect($edit)->toContain('/wiki/store');
    expect($edit)->toContain('/wiki/{slug}/edit');
    expect($read)->toContain('/wiki');
    expect($read)->toContain('/wiki/{slug}');
});

test('wiki article update and delete require edit permission key', function () {
    expect(PageAccess::pathToPageKey('wiki/my-article', 'GET'))->toBe('knowledge_wiki');
    expect(PageAccess::pathToPageKey('wiki/my-article', 'PATCH'))->toBe('knowledge_wiki_edit');
    expect(PageAccess::pathToPageKey('wiki/my-article', 'DELETE'))->toBe('knowledge_wiki_edit');
});

test('page access map contains all sensitive settings endpoints', function () {
    $settings = PageAccess::MAP['settings'] ?? [];
    $settingsDb = PageAccess::MAP['settings_database'] ?? [];

    expect($settings)->toContain('/settings/email/test');
    expect($settings)->toContain('/settings/git/check-updates');
    expect($settings)->toContain('/settings/git/pull-updates');
    expect($settings)->toContain('/settings/git/deploy-ref');

    expect($settingsDb)->toContain('/settings/database/save');
    expect($settingsDb)->toContain('/settings/database/save-remote-draft');
    expect($settingsDb)->toContain('/settings/database/activate-profile');
    expect($settingsDb)->toContain('/settings/database/test-connection');
    expect($settingsDb)->toContain('/settings/database/dry-run-init');
    expect($settingsDb)->toContain('/settings/database/initialize');
    expect($settingsDb)->toContain('/settings/database/initialize-stream');
    expect($settingsDb)->toContain('/settings/database/migrate');
    expect($settingsDb)->toContain('/settings/database/migrate-stream');
});

test('smtp insecure tls mode is disabled by default', function () {
    expect((bool) config('mail.smtp_allow_insecure_tls', false))->toBeFalse();
});

