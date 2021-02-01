<?php
/**
 *
 * Created By 皮神
 * Date: 2021/2/1
 */

namespace Shibapipi\Pay;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register()
    {
        $this->app->singleton('pay', function () {
            return new Pay();
        });
    }
}
