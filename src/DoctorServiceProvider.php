<?php

declare(strict_types=1);

namespace kayedspace\Doctor;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use kayedspace\Doctor\Console\ExplainRuleCommand;
use kayedspace\Doctor\Console\InstallSkillCommand;
use kayedspace\Doctor\Console\ListRulesCommand;
use kayedspace\Doctor\Console\McpServeCommand;
use kayedspace\Doctor\Console\PruneReportsCommand;
use kayedspace\Doctor\Console\ReportsCommand;
use kayedspace\Doctor\Console\ScanCommand;
use kayedspace\Doctor\Http\Middleware\Authorize;
use kayedspace\Doctor\Http\Support\DoctorRequestFactory;
use kayedspace\Doctor\Mcp\DoctorMcpServer;
use kayedspace\Doctor\Mcp\McpReportPresenter;
use kayedspace\Doctor\Mcp\RemediationPlanner;
use kayedspace\Doctor\Rules\RuleCatalog;
use kayedspace\Doctor\Rules\RuleRegistry;
use kayedspace\Doctor\Support\Reports\ReportSerializer;
use kayedspace\Doctor\Support\Reports\ReportStore;
use kayedspace\Doctor\Support\Reports\ScanStatusStore;
use Laravel\Mcp\Server\Registrar;

class DoctorServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/doctor.php', 'doctor');

        $this->app->singleton(RuleRegistry::class, function () {
            return RuleRegistry::default();
        });

        $this->app->singleton(RuleCatalog::class);

        $this->app->singleton(DoctorManager::class, function ($app): DoctorManager {
            return new DoctorManager($app->make(DoctorScanAction::class));
        });

        $this->app->singleton(DoctorScanAction::class);
        $this->app->singleton(ScanPlanResolver::class);
        $this->app->singleton(ReportSerializer::class);
        $this->app->singleton(ReportStore::class);
        $this->app->singleton(ScanStatusStore::class);
        $this->app->singleton(DoctorRequestFactory::class);
        $this->app->singleton(McpReportPresenter::class);
        $this->app->singleton(RemediationPlanner::class);
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        if ($this->app->bound(Registrar::class)) {
            $this->app->make(Registrar::class)->local('doctor', DoctorMcpServer::class);
        }

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'doctor');
        $this->registerDashboardRoutes();
        $this->registerApiRoutes();

        if ($this->app->runningInConsole()) {
            $this->commands([
                ExplainRuleCommand::class,
                InstallSkillCommand::class,
                ListRulesCommand::class,
                McpServeCommand::class,
                PruneReportsCommand::class,
                ReportsCommand::class,
                ScanCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/doctor.php' => $this->app->configPath('doctor.php'),
            ], 'doctor-config');

            $this->publishes([
                __DIR__.'/../resources/views' => $this->app->resourcePath('views/vendor/doctor'),
            ], 'doctor-views');

            $this->app->booted(function (): void {
                $event = $this->app->make(Schedule::class)->command(PruneReportsCommand::class);
                $frequency = (string) Config::get('doctor.reports.retention.schedule', 'daily');
                $allowedFrequencies = ['hourly', 'daily', 'weekly', 'monthly'];

                $event->{in_array($frequency, $allowedFrequencies, true) ? $frequency : 'daily'}();
            });
        }
    }

    /**
     * Register the read-only /_doctor dashboard routes when enabled.
     */
    protected function registerDashboardRoutes(): void
    {
        if (! Config::get('doctor.ui.enabled', true)) {
            return;
        }

        Route::group([
            'prefix' => Config::get('doctor.ui.path', '_doctor'),
            'middleware' => array_merge(
                (array) Config::get('doctor.ui.middleware', ['web']),
                [Authorize::class],
            ),
        ], function (): void {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });
    }

    protected function registerApiRoutes(): void
    {
        if (! Config::get('doctor.api.enabled', true)) {
            return;
        }

        Route::group([
            'prefix' => Config::get('doctor.api.path', '_doctor/api'),
            'middleware' => array_merge(
                (array) Config::get('doctor.api.middleware', ['api']),
                [Authorize::class],
            ),
        ], function (): void {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        });
    }
}
