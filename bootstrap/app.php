<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\JwtMiddleware; // لاحظ حرف A كبير في App
use Spatie\Permission\Middleware\RoleMiddleware;
//use \Illuminate\Routing\Middleware\ThrottleRequests;
use Spatie\Permission\Middlewares\PermissionMiddleware;
use Spatie\Permission\Middlewares\RoleOrPermissionMiddleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
         $middleware->alias([
            'jwt' => JwtMiddleware::class,
      'role' =>  RoleMiddleware::class,
         // 'throttle' =>ThrottleRequests::class,

             //     'permission' => PermissionMiddleware::class,
                //  'role_or_permission' => RoleOrPermissionMiddleware::class,




        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
     ->withSchedule(function (Schedule $schedule) {
        $schedule->command('backup:run')->everyFiveMinutes();

        /*
        
        $schedule->command('backup:run')->everyThirtyMinutes();
        $schedule->command('backup:run')->hourly();
        $schedule->command('backup:run')->everyTwoHours();
        $schedule->command('backup:run')->daily();
        $schedule->command('backup:run')->dailyAt('02:30');
        $schedule->command('backup:run')->weekly();
        $schedule->command('backup:run')->weeklyOn(1, '03:00');
        $schedule->command('backup:run')->monthly();
        $schedule->command('backup:run')->monthlyOn(1, '00:00');
        $schedule->command('backup:run')->yearly();

        */
     })->create();
