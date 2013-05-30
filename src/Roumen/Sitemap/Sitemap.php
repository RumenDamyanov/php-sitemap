<?php namespace Roumen\Sitemap;
/**
 * Sitemap class for laravel4-sitemap package.
 *
 * @author Roumen Damianoff <roumen@dawebs.com>
 * @version 2.0.2
 * @link http://roumen.me/projects/laravel4-sitemap
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */

class Sitemap
{

    public $items = array();
    public $title;
    public $link;


    /**
     * Add new sitemap item to $items array
     *
     * @param string $loc
     * @param string $lastmod
     * @param string $priority
     * @param string $freq
     * @param string $title
     *
     * @return void
     */
    public function add($loc, $lastmod = null, $priority = '0.50', $freq = 'monthly', $title = null)
    {
        $this->items[] = array(
            'loc' => $loc,
            'lastmod' => $lastmod,
            'priority' => $priority,
            'freq' => $freq,
            'title'=> $title
        );
    }


    /**
     * Returns document with all sitemap items from $items array
     *
     * @param string $format (options: xml, html, txt, ror-rss, ror-rdf)
     *
     * @return View
     */
    public function render($format = 'xml')
    {
        if (empty($this->link)) $this->link = \Config::get('application.url');
        if (empty($this->title)) $this->title = 'Sitemap for ' . $this->link;

        $channel = array(
            'title' => $this->title,
            'link' => $this->link
        );

       \View::addNamespace('sitemap', '../workbench/roumen/sitemap/src/views');

        switch ($format)
        {
            case 'ror-rss':
                return \Response::make(\View::make('sitemap::ror-rss', array('items' => $this->items, 'channel' => $channel)), 200, array('Content-type' => 'text/rss+xml; charset=utf-8'));
                break;
            case 'ror-rdf':
                return \Response::make(\View::make('sitemap::ror-rdf', array('items' => $this->items, 'channel' => $channel)), 200, array('Content-type' => 'text/rdf+xml; charset=utf-8'));
                break;
            case 'html':
                return \Response::make(\View::make('sitemap::html', array('items' => $this->items, 'channel' => $channel)), 200, array('Content-type' => 'text/html'));
                break;
            case 'txt':
                return \Response::make(\View::make('sitemap::txt', array('items' => $this->items, 'channel' => $channel)), 200, array('Content-type' => 'text/plain'));
                break;
            default:
               return \Response::make(\View::make('sitemap::xml', array('items' => $this->items)), 200, array('Content-type' => 'text/xml; charset=utf-8'));
        }
    }

    /**
     * Generate sitemap and store it to a file
     *
     * @param string $format (options: xml, html, txt, ror-rss, ror-rdf)
     * @param string $filename (without file extension, may be a path like 'sitemaps/sitemap1' but must exist)
     *
     * @return void
     */
    public function store($format = 'xml', $filename = 'sitemap')
    {
        $content = $this->render($format);

        if ($format == 'ror-rss' || $format == 'ror-rdf') $format = 'xml';

        $file = path('public') . $filename . '.' .$format;

        \File::put($file, $content);
    }

}