<?php

namespace Mochgani\CrudLaravel;

use Illuminate\Support\ServiceProvider;
use Mochgani\CrudLaravel\Console\MakeApiCrud;
use Mochgani\CrudLaravel\Console\MakeCommentable;
use Mochgani\CrudLaravel\Console\MakeCrud;
use Mochgani\CrudLaravel\Console\MakeService;
use Mochgani\CrudLaravel\Console\MakeViews;
use Mochgani\CrudLaravel\Console\RemoveApiCrud;
use Mochgani\CrudLaravel\Console\RemoveCommentable;
use Mochgani\CrudLaravel\Console\RemoveCrud;
use Mochgani\CrudLaravel\Console\RemoveService;

class CrudgenServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //publish config file
        $this->publishes([__DIR__.'/../config/crudgen.php' => config_path('crudgen.php')]);

        //default-theme
        $this->publishes([__DIR__.'/stubs/default-theme/' => resource_path('crudgen/views/default-theme/')]);

        //default-layout
        $this->publishes([__DIR__.'/stubs/default-layout.stub' => resource_path('views/default.blade.php')]);

        //and commentable stub
        $this->publishes([
            __DIR__.'/stubs/commentable/views/comment-block.stub' => resource_path('crudgen/commentable/comment-block.stub')
        ], 'commentable-stub');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/crudgen.php', 'crudgen');

        $this->commands(
            MakeCrud::class,
            MakeViews::class,
            RemoveCrud::class,
            MakeApiCrud::class,
            RemoveApiCrud::class,
            MakeCommentable::class,
            RemoveCommentable::class,
            MakeService::class,
            RemoveService::class
        );
    }
}
