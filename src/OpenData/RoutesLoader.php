<?php

namespace OpenData;

use Silex\Application;

class RoutesLoader {

    public $app;

    public function __construct(Application $app) {

        $this->app = $app;
        $this->instantiateControllers();
    }

    private function instantiateControllers() {

        $loader = $this;
        $this->app['api.controller'] = $this->app->share(function () use ($loader) {

            $apiController = new Controllers\ApiController(
                    class_exists('\Memcache') ? $loader->app['memcache'] : null
            );

            $apiController->registerPacketHandler($loader->app['handler.analytics']);
            $apiController->registerPacketHandler($loader->app['handler.ping']);
            $apiController->registerPacketHandler($loader->app['handler.fileinfo']);
            $apiController->registerPacketHandler($loader->app['handler.crashlog']);
            $apiController->registerPacketHandler($loader->app['handler.filelist']);
            $apiController->registerPacketHandler($loader->app['handler.knownfiles']);

            return $apiController;
        });

        $this->app['site.controller'] = $this->app->share(function () use ($loader) {
            return new Controllers\SiteController(
                    $loader->app['twig'], $loader->app['files.service'], $loader->app['mods.service']
            );
        });

        $this->app['home.controller'] = $this->app->share(function () use ($loader) {
            return new Controllers\HomeController(
                    $loader->app['twig'], $loader->app['mods.service']
            );
        });

        $this->app['mod.controller'] = $this->app->share(function () use ($loader) {
            return new Controllers\ModController(
                    $loader->app['twig'], $loader->app, $loader->app['files.service'], $loader->app['mods.service']
            );
        });

        $this->app['package.controller'] = $this->app->share(function () use ($loader) {
            return new Controllers\PackageController(
                    $loader->app['twig'], $loader->app['files.service'], $loader->app['mods.service'], $loader->app['crashes.service']
            );
        });

        $this->app['crashes.controller'] = $this->app->share(function () use ($loader) {
            return new Controllers\CrashesController(
                    $loader->app['twig'], $loader->app, $loader->app['mods.service'], $loader->app['files.service'], $loader->app['crashes.service'], $loader->app['form.factory']
            );
        });

        $this->app['browse.controller'] = $this->app->share(function () use ($loader) {
            return new Controllers\BrowseController(
                    $loader->app['twig'], $loader->app['mongo']
            );
        });
    }

    public function bindRoutesToControllers() {

        $api = $this->app["controllers_factory"];
        $site = $this->app["controllers_factory"];

        /**
         * API
         */
        $api->post('/data', "api.controller:main");
        $api->post('/crash', "api.controller:crash");


        /**
         * Home
         */
        $site->get('/', "home.controller:home");
        $site->get('/download', "home.controller:download");
        $site->get('/downloads', 'home.controller:get_file');
        $site->get('/storage-policy', "home.controller:storagepolicy");
        $site->get('/configuration', "home.controller:configuration");
        $site->get('/faq', "home.controller:faq");
        $site->get('/stats',"home.controller:stats");
        $site->get('/contact',"home.controller:contact");

        $site->get('/letter/{letter}', "home.controller:letter");
        $site->get('/tag/{tag}', "home.controller:tag");
        $site->get('/all', "home.controller:all");

        /**
         * Browse
         */
        $site->get('/browse', "browse.controller:index");
        $site->get('/browse/{table}', "browse.controller:table");
        $site->get('/browse/raw/{table}/{id}', "browse.controller:single");

        /**
         * Mods
         */
        $site->get('/file/{fileId}', "mod.controller:fileinfo");
        $site->get('/mod/find', "mod.controller:find");
        $site->get('/mod', "mod.controller:redirect");
        $site->get('/mod/{modId}', "mod.controller:modinfo");
        $site->get('/mod/{modId}/{versionFilter}', "mod.controller:modinfo");

        $site->get('/mod/{modId}/crashes', "mod.controller:crashes");
        $site->get('/mod/{modId}/crashes/{fileId}', "mod.controller:crashes");

        /**
         * Crashes
         */
        $site->get('/crashes', "crashes.controller:search");
        $site->post('/crashes', "crashes.controller:search");
        $site->get('/crashes/{stackhash}', "crashes.controller:view");
        $site->get('/commoncrash/{slug}', "crashes.controller:commoncrash");

        /**
         * Packages
         */
        $site->get('/package', "package.controller:listAll");
        $site->get('/package/{package}', "package.controller:package");

        $this->app->mount($this->app["api.endpoint"] . '/' . $this->app["api.version"], $api);
        $this->app->mount('/', $site);
    }

}
