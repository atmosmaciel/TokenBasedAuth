<?php
namespace TBA\Silex\Skeleton\Service;

use \TBA\TokenBasedAuth;
use Silex\Application;
use Silex\ServiceProviderInterface;

class TBAProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
    	$app['tba'] = $app->share(function() use ($app) {
    		$config = array(
    			'table_name'],
    			'user_field'],
    			'pass_field'],
    			'token_timeout'],
    		);
    		$tba = new TokenBasedAuth( $config );

    		return $tba;
    	}
    }

    public function boot(Application $app)
    {
    }
}