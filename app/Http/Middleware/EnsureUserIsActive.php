<?php

namespace App\Http\Middleware;

use App\Enums\AuditAction;
use App\Services\Audit\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Terminates the session of a user whose account (or school) was deactivated
 * while they were logged in.
 */
class EnsureUserIsActive
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && ! $user->canSignIn()) {
            $this->audit->log(AuditAction::AccountBlocked, auditable: $user, user: $user);

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => __('Váš účet je deaktivovaný. Kontaktujte administrátora školy.')]);
        }

        return $next($request);
    }
}
