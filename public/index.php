<?php

declare(strict_types=1);

// Suppress E_DEPRECATED from vendor libs (hassankhan/config v2.1 on PHP 8.4)
// and never show errors in a browser (log them instead).
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

$autoload = file_exists('/var/www/libs/vendor/autoload.php')
    ? '/var/www/libs/vendor/autoload.php'
    : dirname(__DIR__) . '/vendor/autoload.php';

$loader = require_once $autoload;

// When using the shared vendor mount (/var/www/libs/vendor), the generated
// autoloader does not know where our src/ lives. Register it explicitly.
if ($autoload === '/var/www/libs/vendor/autoload.php') {
    $loader->addPsr4('FilebrowserNg\\', dirname(__DIR__) . '/src/');
}

use FilebrowserNg\Controller\AuthController;
use FilebrowserNg\Controller\BrowserController;
use FilebrowserNg\Controller\FileActionController;
use FilebrowserNg\Controller\UploadController;
use FilebrowserNg\Http\Request;
use FilebrowserNg\Http\Response;
use FilebrowserNg\Http\Router;
use FilebrowserNg\I18n\Translator;
use FilebrowserNg\Repository\ServerRepository;
use FilebrowserNg\Session\SessionManager;
use FilebrowserNg\Storage\Smb\SmbProvider;
use FilebrowserNg\View\TemplateEngine;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;
use Noodlehaus\Config;

// ── Config ─────────────────────────────────────────────────────────────────
$configFile = '/var/www/conf/config.json';
if (!file_exists($configFile)) {
    // dev fallback
    $configFile = dirname(__DIR__) . '/conf/config.json';
}
if (!file_exists($configFile)) {
    http_response_code(503);
    echo 'Configuration missing. Check container environment variables.';
    exit;
}
$config = new Config($configFile);

// ── Logger ─────────────────────────────────────────────────────────────────
$levelMap = [
    'debug'   => Level::Debug,
    'info'    => Level::Info,
    'warning' => Level::Warning,
    'error'   => Level::Error,
];
$logger = new Logger('filebrowser-ng');
$logger->pushHandler(new RotatingFileHandler(
    (string) $config->get('logging.path', '/var/www/log/filebrowser-ng.log'),
    (int)    $config->get('logging.max_days', 30),
    $levelMap[strtolower((string) $config->get('logging.level', 'info'))] ?? Level::Info,
));

// ── Session ────────────────────────────────────────────────────────────────
$session = new SessionManager($config);
$session->start();

// ── I18n + View ────────────────────────────────────────────────────────────
$translator = new Translator(
    dirname(__DIR__) . '/lang',
    (string) $config->get('i18n.default_language', 'en')
);
$view = new TemplateEngine(dirname(__DIR__) . '/templates', $translator);
$view->setGlobals([
    'appVersion' => (string) (getenv('APP_VERSION') ?: 'dev'),
]);

// ── Storage ────────────────────────────────────────────────────────────────
$provider = new SmbProvider($logger, (string) $config->get('smb.domain', ''));

// ── Repository ─────────────────────────────────────────────────────────────
$serverRepo = new ServerRepository(
    (string) $config->get('database.path', '/var/www/data/filebrowser.sqlite')
);

// ── Controllers ────────────────────────────────────────────────────────────
$auth    = new AuthController($session, $provider, $view, $config, $logger);
$browser = new BrowserController($session, $provider, $serverRepo, $view, $config, $logger);
$upload  = new UploadController($session, $provider, $config, $logger);
$actions = new FileActionController($session, $provider, $config, $logger);

// ── Routes ─────────────────────────────────────────────────────────────────
$router = new Router();

$router->get('/',                 fn($req) => $auth->showLogin($req));
$router->post('/auth/login',      fn($req) => $auth->login($req));
$router->get('/auth/logout',      fn($req) => $auth->logout($req));
$router->get('/browser',          fn($req) => $browser->index($req));
$router->get('/api/shares',       fn($req) => $browser->shares($req));
$router->get('/api/files',        fn($req) => $browser->listFiles($req));
$router->get('/browser/download', fn($req) => $browser->download($req));
$router->post('/upload',          fn($req) => $upload->upload($req));
$router->post('/file/rename',     fn($req) => $actions->rename($req));
$router->post('/file/delete',     fn($req) => $actions->delete($req));
$router->post('/file/mkdir',      fn($req) => $actions->mkdir($req));

$router->dispatch(new Request());
