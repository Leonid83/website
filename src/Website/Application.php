<?php
namespace Freefeed\Website;


use Freefeed\Website\Controllers\Dummy;
use Freefeed\Website\Controllers\User;
use Monolog\Logger;
use Predis\Silex\ClientsServiceProvider as PredisProvider;
use Silex\Application\MonologTrait;
use Silex\Application\SwiftmailerTrait;
use Silex\Application\TwigTrait;
use Silex\Application\UrlGeneratorTrait;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\SwiftmailerServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Snc\RedisBundle\Session\Storage\Handler\RedisSessionHandler;
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
    use SwiftmailerTrait;

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

        $this->register(new SessionServiceProvider(), array(
            'session.storage.options' => array(
                'name' => $this->settings['session']['cookie_name'],
                'cookie_lifetime' => $this->settings['session']['cookie_lifetime'],
                'cookie_httponly' => true,
                'cookie_domain' => $this->settings['session']['cookie_domain'],
                'cookie_secure' => $this->settings['session']['cookie_secure']
            ),
            'session.storage.handler' => $this->share(function(){
                return new RedisSessionHandler($this['predis']['sessions']);
            }),
        ));

        $this->register(new ServiceControllerServiceProvider());

        $this->register(new SwiftmailerServiceProvider(), array(
            'swiftmailer.options' => $this->settings['smtp'],
        ));
    }

    public function setupRouting()
    {
        $this['controllers.dummy'] = $this->share(function(){
            return new Dummy();
        });

        $this['controllers.user'] = $this->share(function(){
            return new User();
        });

        $this->get('/', 'controllers.dummy:landingAction')->bind('index');
        $this->get('/refuse', 'controllers.dummy:refuseAction');

        $this->get('/login', 'controllers.user:loginAction')->bind('login');
        $this->post('/login', 'controllers.user:loginPostAction')->bind('login_submit');
    }


    /**
     * @return \Pimple
     */
    public function getSettings()
    {
        return $this->settings;
    }
}
