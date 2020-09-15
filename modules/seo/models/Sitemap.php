<?php

namespace modules\seo\models;

use common\components\F3deshaHelpers;
use Exception;
use modules\dealerinventory\models\backend\Dealerinventory;
use modules\users\models\backend\Dealership;
use modules\users\models\backend\DealershipGroup;
use modules\users\models\backend\User;
use PhpOffice\PhpSpreadsheet\Shared\File;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use modules\seo\models\SeoCatalog;

/**
 * Class Sitemap
 * @package modules\seo\models
 *
 * @property array $staticPagesAllUrls
 * @property void $blogSitemap
 * @property void $igniteSitemap
 * @property void $staticPagesSitemap
 * @property array $allBlogUrls
 * @property void $seocatalogSitemap
 */
class Sitemap extends Model
{
    /**
     *
     */
    const DEALERSHIP_DOMAIN_TYPE = 1;
    /**
     *
     */
    const DEALERSHIP_GROUP_DOMAIN_TYPE = 2;
    /**
     *
     */
    const DEALERSHIP_PREFIX = 'dealership_';
    /**
     *
     */
    const DEALERSHIP_GROUP_PREFIX = 'dealership_group_';
    /**
     *
     */
    const CARVOY_PREFIX = 'main_';
    /**
     * @var
     */
    public $config;
    /**
     * @var
     */
    protected $owners_path;
    /**
     * @var
     */
    protected $root_category_sitemap;
    /**
     * @var int
     */
    protected $sitemap_limit = 50000;
    /**
     * @var
     */
    protected $absolute_path;
    /**
     * @var
     */
    protected $sitemap_protocol;
    /**
     * @var
     */
    protected $sitemap_host;
    /**
     * @var
     */
    protected $root_category_sitemap_path;
    /**
     * @var
     */
    private $subdomain;
    /**
     * @var
     */
    private $full_custom_subdomain;
    /**
     * @var bool
     */
    private $domain_type = false;
    /**
     * @var bool
     */
    private $owner_id = false;

    /**
     * Sitemap constructor.
     * @param array $config
     */
    public function __construct($config = [])
    {
    }

    /**
     *
     */
    public function createCategorySitemap()
    {
    }

    /**
     *
     */
    public function test()
    {
    }

    /**
     *
     */
    public function getIgniteSitemap()
    {
        $managersMask = false;

        $makes = Dealerinventory::getAllMakes($managersMask, $this->domain_type, $this->owner_id);
        if ($makes) {
            foreach ($makes as $make) {
                $key = $make['model'];
                $make = str_replace(" ", "", $make['model']);
                $make = str_replace("-", "", $make);
                $make = strtolower($make);
                $makesArray[$key] = $make;
            }
        }
        if (!empty($makesArray)) {
            foreach ($makesArray as $old_make => $transmited_make) {
                $make = $old_make;
                $i = 1;
                while ($urls = $this->getIgniteUrlsThroughOffset($i, $make, $managersMask)) {
                    $sitemap_object = $this->getSitemapDataProviderInterface($urls, 'ignite', $i, $transmited_make);
                    $this->generateSitemapChunk($sitemap_object);
                    $i++;
                }
            }
        }
    }

    /**
     * @param $iterator
     * @param $make
     * @param $managersMask
     * @return array
     */
    public function getIgniteUrlsThroughOffset($iterator, $make, $managersMask)
    {
        $urls = [];
        $start = $iterator * $this->sitemap_limit - $this->sitemap_limit;

        $cars = Dealerinventory::find()->where(
            Dealerinventory::activeItemsCommonConditions($managersMask, $this->domain_type, $this->owner_id)
        )
            ->andWhere(['make' => $make])
			->andWhere(['user_id' => Dealerinventory::getAllDealershipManagersInMainCatalogShown()])
            ->offset($start)
            ->limit($this->sitemap_limit)
            ->all();
        $host = $this->sitemap_protocol . "://" . $this->subdomain . $this->sitemap_host;
        if (!empty($this->full_custom_subdomain)) {
            $host = $this->full_custom_subdomain;
        }
        foreach ($cars as $car) {
            $location = $car->receiveItemsLocationByZip($car->zip);
            $carLink = $host . "/" .
                Dealerinventory::MODULE_NAME . "/" . F3deshaHelpers::encodeForUrl(
                    $car->make
                ) . "/" . F3deshaHelpers::encodeForUrl($car->model) . "/" . F3deshaHelpers::encodeForUrl(
                    $car->short_trim
                ) . "/" .
                F3deshaHelpers::encodeForUrl($car->year) . "/" . F3deshaHelpers::encodeForUrl(
                    $location['state_code']
                ) . "/" . F3deshaHelpers::encodeForUrl($location['city']) . "/" . F3deshaHelpers::encodeForUrl(
                    $car->id
                );
            $carLink = str_replace(' ', '+', $carLink);
            $carLink = F3deshaHelpers::prepareLinkForSitemap($carLink);
            $urls[] = array(
                'loc' => $carLink,
                'lastmod' => date(DATE_ATOM, $car->updated_at),
                'changefreq' => 'daily',
            );
        }

        return $urls;
    }

    /**
     * @param $urls
     * @param $prefix_name
     * @param $iteration
     * @param null $subcategory_folder
     * @return bool
     */
    public function getSitemapDataProviderInterface($urls, $prefix_name, $iteration, $subcategory_folder = null)
    {
        $response = false;
        if (!empty($urls)) {
            $subcategory_prefix = $subcategory_folder ? '_' . $subcategory_folder : '';
            $sitemap_data_array = [
                'sitemap_data' => [
                    'filename' => $prefix_name . $subcategory_prefix . '_' . $iteration,
                    'urls' => $urls
                ]
            ];

            if (!empty($subcategory_folder)) {
                $response[$prefix_name][$subcategory_folder] = $sitemap_data_array;
            } else {
                $response[$prefix_name] = $sitemap_data_array;
            }
        }
        return $response;
    }

    /**
     * @param $sitemap_object
     */
    public function generateSitemapChunk($sitemap_object)
    {
        $result = $this->generateBySitemapArray($sitemap_object, $this->owners_path);
        $this->reopenSitemapindex();

        $url = [
            'loc' => $result['fullname']
        ];
        $this->addSitemapUrl($this->root_category_sitemap, $url);
        $this->close($this->root_category_sitemap);
    }

    /**
     * @param $sitemap_object
     * @param $current_path
     * @return array
     */
    public function generateBySitemapArray($sitemap_object, $current_path)
    {
        $prev_url = $current_path;
        foreach ($sitemap_object as $key => $value) {
            if (is_array($value) && $key !== 'sitemap_data') {
                $new_path = $prev_url . '/' . $key;
                try {
                    FileHelper::createDirectory($new_path);
                } catch (Exception $e) {
                }
                return $this->generateBySitemapArray($value, $new_path);
            } elseif ($key === 'sitemap_data') {
                $sitemap_data = $value;
                //create file for each url
                $path_without_extension = $prev_url . '/' . $sitemap_data['filename'];
                $filepath = $path_without_extension . '.xml';

                $handler = $this->create($filepath);
                $this->_fwrite($handler, "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n");
                $this->_fwrite($handler, "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n");
                foreach ($sitemap_data['urls'] as $url_object) {
                    $this->addUrl(
                        $handler,
                        [
                            'loc' => $url_object['loc'],
                            'lastmod' => date(DATE_ATOM, time()),
                            'changefreq' => 'daily'
                        ]
                    );
                }
                $this->_fwrite($handler, "</urlset>");

                $content = file_get_contents($filepath);
                return $this->createGzFile($prev_url, $sitemap_data['filename'], $content);
            }
        }
    }

    /**
     * @param $sitemap_name
     * @return false|resource
     */
    public function create($sitemap_name)
    {
        return fopen($sitemap_name, "w");
    }

    /**
     * @param $handler
     * @param $data
     */
    public function addUrl($handler, $data)
    {
        $currentDateTime = time();

        $info = "<url>\n" .
            "<loc>" . ArrayHelper::getValue($data, 'loc', "") . "</loc>\n" .
            "<lastmod>" . ArrayHelper::getValue($data, 'lastmod', "") . "</lastmod>\n" .
            "<changefreq>" . ArrayHelper::getValue($data, 'changefreq', "") . "</changefreq>\n" .
            "</url>\n";
        $this->_fwrite($handler, $info);
    }

    /**
     * @param $path
     * @param $filename
     * @param $content
     * @return array
     */
    public function createGzFile($path, $filename, $content)
    {
        $gzFilePath = $path . '/' . $filename . ".gz";
        $gz = gzopen($gzFilePath, 'w9');
        gzwrite($gz, $content);
        gzclose($gz);
        $absolute_path = explode('sitemaps', $path);
        $fullname = $this->absolute_path . '/sitemaps' . $absolute_path[1] . '/' . $filename . '.gz';
        $parts = explode('/', $absolute_path[1]);
        $owner_name = $parts[1];
        unset($parts[0]);
        unset($parts[1]);
        unlink($path . '/' . $filename . '.xml');
        return [
            'category_sitemap_path' => $path,
            'fullname' => $fullname,
            'parts' => $parts,
            'owner_name' => $owner_name
        ];
    }

    /**
     *
     */
    public function reopenSitemapindex()
    {
        $handle = fopen($this->root_category_sitemap_path, 'r');
        $linecount = 0;
        while (!feof($handle)) {
            $lines[] = fgets($handle);
            $linecount++;
        }

// Pop the last item from the array
        array_pop($lines);

// Join the array back into a string
        $file = join('', $lines);

// Write the string back into the file
        $this->root_category_sitemap = $this->create($this->root_category_sitemap_path);

        fputs($this->root_category_sitemap, $file);
    }

    /**
     * @param $handler
     * @param $data
     */
    public function addSitemapUrl($handler, $data)
    {
        $currentDateTime = time();

        $info = "<sitemap>\n" .
            "<loc>" . ArrayHelper::getValue($data, 'loc', "") . "</loc>\n" .
            "<lastmod>" . date(DATE_ATOM, time()) . "</lastmod>\n" .
            "</sitemap>\n";
        $this->_fwrite($handler, $info);
    }

    /**
     * @param $handler
     * @param $data
     */
    public function _fwrite($handler, $data)
    {
        fwrite($handler, $data);
    }

    /**
     * @param $handler
     */
    public function close($handler)
    {
        if ($handler) {
            $this->_fwrite($handler, "</sitemapindex>");
        }
    }

    /**
     *
     */
    public function getSeocatalogSitemap()
    {
        $i = 1;
        while ($urls = $this->getSeocatalogUrlsThroughOffset($i)) {
            $sitemap_object = $this->getSitemapDataProviderInterface($urls, 'seocatalog', $i, 'links');
            $this->generateSitemapChunk($sitemap_object);
            $i++;
        }
    }

    /**
     * @param $iterator
     * @return array
     */
    public function getSeocatalogUrlsThroughOffset($iterator)
    {
        $urls = [];
        $start = $iterator * $this->sitemap_limit - $this->sitemap_limit;
        $end = $start + $this->sitemap_limit;
        $source_path = Yii::getAlias('@runtime');
        $filename = $source_path . '/' . SeoCatalog::SEOLINK_FILE_NAME . '.txt';
        $urls_full = [];
        if (file_exists($filename)) {
            $handle = fopen($filename, "r");
            $counter = 0;
            if ($handle) {
                while (($line = fgets($handle)) !== false) {
                    if ($counter >= $start && $counter < $end) {
                        // process the line read.
                        $line = F3deshaHelpers::prepareLinkForSitemap($line);
                        $urls[] = $line;
                    }
                    $counter++;
                }
                fclose($handle);
            }

            foreach ($urls as $url_element) {
                if (substr($url_element, -1) == "\n") {
                    $url_element = substr_replace($url_element, "", -1);
                }
                $urls_full[] = [
                    'loc' => $url_element,
                ];
            }
        }


        return $urls_full;
    }

    /**
     *
     */
    public function getBlogSitemap()
    {
        $i = 1;
        while ($urls = $this->getBlogUrlsThroughOffset($i)) {
            $sitemap_object = $this->getSitemapDataProviderInterface($urls, 'blog', $i);
            $this->generateSitemapChunk($sitemap_object);
            $i++;
        }
    }

    /**
     * @param $iterator
     * @return array
     */
    public function getBlogUrlsThroughOffset($iterator)
    {
        $start = $iterator * $this->sitemap_limit - $this->sitemap_limit;
        $urls = $this->getAllBlogUrls();
        return array_slice($urls, $start, $this->sitemap_limit);
    }

    /**
     * @return array
     */
    public function getAllBlogUrls()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
            $ch,
            CURLOPT_URL,
            "https://blog.carvoy.com/wp-json/wp/v2/posts?_fields[]=link&_fields[]=date&status=publish&per_page=100"
        );
        $result = curl_exec($ch);
        curl_close($ch);
        $blogPosts = json_decode($result);
        if (!empty($blogPosts) && is_array($blogPosts)) {
            foreach ($blogPosts as $blogPost) {
                $urls[] = array(
                    'loc' => $blogPost->link,
                    'lastmod' => date(DATE_ATOM, strtotime($blogPost->date))
                );
            }
        }

        return $urls;
    }

    /**
     * @param $sitemap_postfix
     * @return string
     */
    public function parseRouteByPostfix($sitemap_postfix)
    {
        $last_element = '';
        $path = '';
        if (!empty($sitemap_postfix)) {
            $parsed = explode('_', $sitemap_postfix);
            $path = implode('/', $parsed);
            $path = '/' . $path;
            $last_element = array_pop($parsed);
            $last_element = '_' . $last_element;
        }
        $path .= '/sitemap' . $last_element . '.xml';
        return $path;
    }

    /**
     *
     */
    public function getStaticPagesSitemap()
    {
        $i = 1;
        while ($urls = $this->getStaticPagesUrlsThroughOffset($i)) {
            $sitemap_object = $this->getSitemapDataProviderInterface($urls, 'staticpage', $i);
            $this->generateSitemapChunk($sitemap_object);
            $i++;
        }
    }

    /**
     * @param $iterator
     * @return array
     */
    public function getStaticPagesUrlsThroughOffset($iterator)
    {
        $start = $iterator * $this->sitemap_limit - $this->sitemap_limit;
        $urls = $this->getStaticPagesAllUrls();
        return array_slice($urls, $start, $this->sitemap_limit);
    }

    /**
     * @return array
     */
    public function getStaticPagesAllUrls()
    {
        $site = $this->sitemap_protocol . '://' . $this->sitemap_host;
        return [
            [
                'loc' => $site . "/",
                //'lastmod' => date('Y-m-d H:i:s P', 1538041843)
            ],
            [
                'loc' => $site . "/ignite/",
            ],
            [
                'loc' => $site . "/how-it-works-builder/",
            ],
            [
                'loc' => $site . "/educenter/",
            ],
            [
                'loc' => $site . "/lease-assumption-part-2/",
            ],
            [
                'loc' => $site . "/car-leasing-101/",
            ],
            [
                'loc' => $site . "/what-is-leasing/",
            ],
            [
                'loc' => $site . "/blog/",
            ],
            [
                'loc' => $site . "/about/",
            ],
            [
                'loc' => $site . "/partner/",
            ],
            [
                'loc' => $site . "/faq/",
            ],
            [
                'loc' => $site . "/contacts/",
            ],
        ];
    }

    /**
     * @param $config
     * @throws InvalidConfigException
     */
    public function prepareSitemap($config)
    {
        $path = Yii::getAlias('@sitemap');

        //Define absolute path based on owner name
        $this->sitemap_protocol = $config['sitemap_protocol'];
        $this->sitemap_host = $config['sitemap_host'];
        if ($config['sitemap_owner'] !== 'carvoy') {
            $this->subdomain = $config['sitemap_owner'] . '.';
            if ($config['domain_type']) {
                $this->domain_type = $config['domain_type'];
                switch ($this->domain_type) {
                    //Dealership
                    case 1:
                        $dealership = Dealership::find()->where(['subdomain' => $config['sitemap_owner']])->one();
                        $this->full_custom_subdomain = $dealership->custom_subdomain;
                        $this->owner_id = $dealership->id;
                        break;
                    //Dealership group
                    case 2:
                        $this->owner_id = [];
                        $dealership_group = DealershipGroup::find()->where(
                            ['subdomain' => $config['sitemap_owner']]
                        )->one();
                        $this->full_custom_subdomain = $dealership_group->custom_subdomain;
                        $dealerships = $dealership_group->getGroup()->asArray()->all();
                        $this->owner_id = ArrayHelper::getColumn($dealerships, 'id');
                        break;
                }
            }
        } else {
            $this->subdomain = '';
        }

        $this->absolute_path = $this->sitemap_protocol . "://" . $this->subdomain . $this->sitemap_host;
        if (!empty($this->full_custom_subdomain)) {
            $this->absolute_path = $this->full_custom_subdomain;
        }

        $current_path = $path . '/' . $config['sitemap_owner'];
        $this->owners_path = $current_path;

        try {
            FileHelper::removeDirectory($current_path);
            FileHelper::createDirectory($current_path);

            //At this moment add category sitemap path to main ROOT_CATEGORY sitemap
            $this->root_category_sitemap_path = Yii::getAlias(
                    '@sitemap'
                ) . '/' . $config['sitemap_owner'] . '/sitemap.xml';
            if (!file_exists($this->root_category_sitemap_path)) {
                $this->root_category_sitemap = $this->create($this->root_category_sitemap_path);
                $this->_fwrite($this->root_category_sitemap, "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n");
                $this->_fwrite(
                    $this->root_category_sitemap,
                    "<?xml-stylesheet type=\"text/xsl\" href=\"sitemap.xsl\"?>\n"
                );
                $this->_fwrite(
                    $this->root_category_sitemap,
                    "<sitemapindex xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n"
                );
                $this->finishSitemap();
            }
        } catch (Exception $e) {
        }
    }

    /**
     *
     */
    public function finishSitemap()
    {
        $this->_fwrite($this->root_category_sitemap, "</sitemapindex>");
    }

    /**
     * @param $handler
     */
    public function closeCategorySitemap($handler)
    {
        $this->close($handler);
    }

    /**
     * @param int $counter
     * @return array
     */
    public function getGeneratedUrlsArray($counter = 20)
    {
        $urls = [];
        for ($i = 0; $i < $counter; $i++) {
            $urls[$i] = [
                'loc' => 'url_' . $i,
                'lastmod' => date('Y-m-d', 1538041952)
            ];
        }
        return $urls;
    }

}
