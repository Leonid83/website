<?php
namespace Freefeed\Website;


use Monolog\Logger;
use Predis\Silex\ClientsServiceProvider as PredisProvider;
use Silex\Application\MonologTrait;
use Silex\Application\TwigTrait;
use Silex\Application\UrlGeneratorTrait;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Debug\ErrorHandler;
use Symfony\Component\Debug\ExceptionHandler;
use Tacker\Configurator;
use Tacker\Loader\CacheLoader;
use Tacker\Loader\NormalizerLoader;
use Tacker\Loader\YamlFileLoader;
use Tacker\Normalizer\ChainNormalizer;
use Tacker\ResourceCollection;

class Application extends \Silex\Application
{
    use UrlGeneratorTrait;
    use MonologTrait;
    use TwigTrait;

    /** @var string  */
    protected $root;

    /** @var \Pimple */
    protected $settings;

    public function __construct(array $values = [])
    {
        parent::__construct($values);

        $this->root = realpath(__DIR__.'/../..');

        ini_set('upload_tmp_dir', $this->root.'/cache/tmp');
        ini_set('error_log', $this->root.'/logs/php.log');

        $this->initSettings();

        ErrorHandler::register(null, $this['debug']);
        ExceptionHandler::register($this['debug']);

        $this->setupServices();
    }

    protected function initSettings()
    {
        $resources = new ResourceCollection();
        $loader = new YamlFileLoader(new FileLocator(), $resources);

        $loader = new CacheLoader(new NormalizerLoader($loader, new ChainNormalizer()), $resources);
        $loader->setCacheDir($this->root.'/cache/config');

        $this->settings = new \Pimple();

        $configurator = new Configurator($loader);
        $configurator->configure($this->settings, $this->root.'/config/default.yaml');

        if (is_readable($this->root.'/config/local.yaml')) {
            $configurator->configure($this->settings, $this->root.'/config/local.yaml');
        }

        $this['debug'] = $this->settings['debug'];  // used by various services
    }

    protected function setupServices()
    {
        $this->register(new MonologServiceProvider(), array(
            'monolog.logfile'    => $this->root.'/logs/monolog.log',
            'monolog.name'       => 'freefeed.net',
            'monolog.level'      => $this->settings['debug'] ? Logger::DEBUG : Logger::NOTICE,
        ));

        $this->register(new TwigServiceProvider(), array(
            'twig.path'       => $this->root.'/templates',
            'twig.options'    => ['cache' => $this->root.'/cache/twig']
        ));

        $this->register(new UrlGeneratorServiceProvider());
        $this->register(new TranslationServiceProvider());

        $this->register(new DoctrineServiceProvider(), array(
            'dbs.options' => array (
                'master'  => ['driver' => 'pdo_mysql', 'charset' => 'utf8'] + $this->settings['db']['master'],
            ),
        ));

        $this->register(new PredisProvider(), [
            'predis.clients' => $this->settings['redis'],
        ]);
    }

    public function setupRouting()
    {
        $this->get('/', function() {
            return $this->render('refuse.twig');
        });
    }
}
