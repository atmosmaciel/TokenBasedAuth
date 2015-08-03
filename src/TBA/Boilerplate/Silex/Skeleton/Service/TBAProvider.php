<?php
namespace TBA\Boilerplate\Silex\Skeleton\Service;

use \TBA\TokenBasedAuth;
use Silex\Application;
use Silex\ServiceProviderInterface;

class TBAProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['tba'] = $app->share(function() use ($app)
        {
            $config = array();
            $config['table_name'] = $app['tba.table_name'];
            $config['user_field'] = $app['tba.user_field'];
            $config['pass_field'] = $app['tba.pass_field'];
            $config['token_timeout'] = $app['tba.token_timeout'];
            $config['salt'] = $app['tba.salt'];

            $tba = new TokenBasedAuth( $config );

            return $tba;
        });
    }

    public function boot(Application $app)
    {
    }
}