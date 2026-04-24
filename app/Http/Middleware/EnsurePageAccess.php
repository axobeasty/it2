<?php

namespace App\Http\Middleware;

use App\Models\Employee;
use App\Support\PageAccess;
use Brian2694\Toastr\Facades\Toastr;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePageAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $publicPaths = ['/auth', '/logout', '/employees/{id}/activate/{code}'];

        $currentPath = '/'.ltrim($request->path(), '/');
        foreach ($publicPaths as $publicPath) {
            $regex = preg_replace('/\{[^}]+\}/', '[^\/]+', preg_quote($publicPath, '/'));
            if (preg_match('/^'.$regex.'$/', $currentPath)) {
                return $next($request);
            }
        }

        if (!$request->session()->has('user')) {
            Toastr::warning('Требуется вход', 'Для доступа к этой странице войдите в систему.', ['progressBar' => true]);
            return redirect('/');
        }

        /** @var Employee|null $sessionUser */
        $sessionUser = $request->session()->get('user');
        if (!$sessionUser) {
            Toastr::warning('Требуется вход', 'Для доступа к этой странице войдите в систему.', ['progressBar' => true]);
            return redirect('/');
        }

        $user = $sessionUser;
        $lastRefreshAt = (int) $request->session()->get('user_permissions_refreshed_at', 0);
        $needsRefresh = ! $user->relationLoaded('role') || (time() - $lastRefreshAt) > 120;
        if ($needsRefresh) {
            $freshUser = Employee::with('role.pagePermissions')->find($sessionUser->id);
            if (! $freshUser) {
                $request->session()->forget('user');
                Toastr::error('Сессия недействительна', 'Войдите в систему снова.', ['progressBar' => true]);
                return redirect('/');
            }

            $user = $freshUser;
            $request->session()->put('user', $user);
            $request->session()->put('user_permissions_refreshed_at', time());
        }

        $pageKey = PageAccess::pathToPageKey($request->path());
        if (!$pageKey) {
            return $next($request);
        }

        if (!$user->canAccessPage($pageKey)) {
            Toastr::error('Ошибка доступа', 'У вас недостаточно прав для открытия этой страницы.', ['progressBar' => true]);
            return redirect('/');
        }

        return $next($request);
    }
}
