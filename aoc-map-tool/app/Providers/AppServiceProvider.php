<?php

namespace App\Providers;

use App\ConsentTemplate;
use App\Mixins\SoftDeleteQueryBuilderMixin;
use App\Models\Adjustment;
use App\Observers\AdjustmentObserver;
use App\Observers\ConsentTrackingObserver;
use App\Observers\TrachVentClassRequestObserver;
use App\Repositories\GpCalculator\InMemoryScheduleMasterRepository;
use App\Repositories\GpCalculator\ScheduleMasterRepository;
use App\Repositories\GpCalculator\ScheduleMasterRepositoryInterface;
use App\TrachVentClassRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use App\Services\Utils\AwsSecrets;
use Aws\SecretsManager\SecretsManagerClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    protected bool $defer = false;

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerGpCalculatorServices();
        $this->registerAwsServices();
    }


    protected function registerAwsServices(): void
    {
        $this->app->scoped(SecretsManagerClient::class, function () {
            return new SecretsManagerClient([
                'version' => 'latest',
                'region' => config('services.ses.region', 'us-west-1')
            ]);
        });
        $this->app->scoped(AwsSecrets::class);
    }

    protected function registerGpCalculatorServices() :void
    {
        // ------- GP calculator testing env  if local, real DB call otherwise-------- //
        $this->app->singleton(ScheduleMasterRepositoryInterface::class, function ($app) {
            if ($app->environment(['local'])) {
                return new InMemoryScheduleMasterRepository();
            }
            return $app->make(ScheduleMasterRepository::class);
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        \Validator::extend('recaptcha', 'App\\Validators\\ReCaptcha@validate');

        #Observers
        ConsentTemplate::observe(ConsentTrackingObserver::class);
        Adjustment::observe(AdjustmentObserver::class);
        TrachVentClassRequest::observe(TrachVentClassRequestObserver::class);

        if (class_exists(DB::class)) {
            DB::macro('stream', function (string $sql, array $bindings = []) {
                foreach (DB::cursor($sql, $bindings) as $item) {
                    yield (array) $item;
                }
            });
        }

        Builder::mixin(new SoftDeleteQueryBuilderMixin());

        Relation::morphMap([
            'role' => \App\Roles::class,
            'user' => \App\User::class,
        ]);
    }
}
