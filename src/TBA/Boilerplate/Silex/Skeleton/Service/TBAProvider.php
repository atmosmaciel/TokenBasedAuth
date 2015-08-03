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
    			'table_name' => $this->app['tba.table_name'],
    			'user_field' => $this->app['tba.user_field'],
    			'pass_field' => $this->app['tba.pass_field'],
    			'token_timeout' => $this->app['tba.token_timeout'],
                'salt' => $this->app['tba.salt']
    		);
    		$tba = new TokenBasedAuth( $config );

    		return $tba;
    	}
    }

    public function boot(Application $app)
    {
    }
}