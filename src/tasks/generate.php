<?php

class Sitemap_Generate_Task
{

    public function run($arguments)
    {
        /* Example Sitemap - START */

        $sitemap = new Sitemap();

        $sitemap->add(URL::to(), '2013-01-25T20:10:00+02:00', '1.0', 'daily');
        $sitemap->add(URL::to('welcome'), '2013-01-25T10:00:00+02:00', '0.95', 'monthly');

        $sitemap->store('xml','sitemap');

        /* Example Sitemap - END  */
        echo 'done.';

    }

}