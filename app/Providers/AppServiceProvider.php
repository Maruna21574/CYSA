<?php

namespace App\Providers;

use App\Gamification\GamificationSubscriber;
use App\Models\User;
use App\Services\AI\Clients\AnthropicClient;
use App\Services\AI\Contracts\AiClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // AI provider behind a provider-independent interface (AI_PROVIDER in .env).
        $this->app->bind(AiClient::class, fn (): AiClient => match (config('cysa.ai.provider')) {
            'anthropic' => new AnthropicClient(
                apiKey: (string) config('services.anthropic.key'),
                model: (string) config('cysa.ai.model'),
                effort: (string) config('cysa.ai.effort'),
                timeout: (int) config('cysa.ai.timeout'),
            ),
            default => throw new \InvalidArgumentException('Unsupported AI provider: '.config('cysa.ai.provider')),
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Catch N+1 queries and typos in attribute names during development.
        Model::shouldBeStrict(! $this->app->isProduction());

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        // The super admin passes every policy check. Everyone else is decided by policies.
        Gate::before(fn (User $user): ?bool => $user->isSuperAdmin() ? true : null);

        Password::defaults(fn (): Password => Password::min(8)->letters()->numbers()->max(255));

        // Optional gamification module (XP, levels, badges) - listens to domain events only.
        Event::subscribe(GamificationSubscriber::class);
    }
}
