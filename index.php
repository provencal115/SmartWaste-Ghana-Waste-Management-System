<?php
/**
 * Smart Garbage Collection & Inventory Management System
 * Front Controller
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
date_default_timezone_set('Africa/Accra');

// Autoload core
require_once __DIR__ . '/includes/AppConfig.php';
require_once __DIR__ . '/includes/Model.php';
require_once __DIR__ . '/includes/Controller.php';
require_once __DIR__ . '/includes/Router.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Csrf.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/stats.php';
require_once __DIR__ . '/includes/images.php';
require_once __DIR__ . '/includes/ui.php';
require_once __DIR__ . '/includes/pages.php';

ensureBrandImageAssets();

if (is_file(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}
require_once __DIR__ . '/includes/Mailer.php';
require_once __DIR__ . '/includes/SmsService.php';
require_once __DIR__ . '/includes/NotificationDispatcher.php';

// Models
foreach (glob(__DIR__ . '/models/*.php') as $model) {
    require_once $model;
}

Auth::start();

$router = new Router();

// Public routes
$router->get('home', 'HomeController@index');
$router->get('about', 'PageController@about');
$router->get('faq', 'PageController@faq');
$router->get('contact', 'PageController@contact');
$router->post('contact', 'PageController@contactPost');
$router->get('privacy', 'PageController@privacy');
$router->get('terms', 'PageController@terms');
$router->get('auth/login', 'AuthController@login');
$router->post('auth/login', 'AuthController@loginPost');
$router->get('auth/register', 'AuthController@register');
$router->post('auth/register', 'AuthController@registerPost');
$router->get('auth/confirm', 'AuthController@confirm');
$router->post('auth/confirm', 'AuthController@confirmPost');
$router->get('auth/logout', 'AuthController@logout');
$router->get('auth/forgot', 'AuthController@forgot');
$router->post('auth/forgot', 'AuthController@forgotPost');
$router->get('auth/reset', 'AuthController@reset');
$router->post('auth/reset', 'AuthController@resetPost');

// Resident
$router->get('resident/dashboard', 'ResidentController@dashboard');
$router->get('resident/schedule', 'ResidentController@schedule');
$router->post('resident/schedule', 'ResidentController@schedulePost');
$router->get('resident/payments', 'ResidentController@payments');
$router->post('resident/payments', 'ResidentController@paymentsPost');
$router->get('resident/feedback', 'ResidentController@feedback');
$router->post('resident/feedback', 'ResidentController@feedbackPost');
$router->get('resident/notifications', 'ResidentController@notifications');
$router->post('resident/notifications/read', 'ResidentController@markNotificationsRead');

// Collector
$router->get('collector/dashboard', 'CollectorController@dashboard');
$router->get('collector/routes', 'CollectorController@routes');
$router->get('collector/scan', 'CollectorController@scan');
$router->post('collector/scan', 'CollectorController@scanPost');
$router->post('collector/pickup', 'CollectorController@updatePickup');
$router->get('collector/reports', 'CollectorController@reports');
$router->post('collector/reports', 'CollectorController@reportsPost');

// Inventory
$router->get('inventory/dashboard', 'InventoryController@dashboard');
$router->get('inventory/bins', 'InventoryController@bins');
$router->post('inventory/bins', 'InventoryController@binsPost');
$router->get('inventory/reports', 'InventoryController@reports');

// Admin
$router->get('admin/dashboard', 'AdminController@dashboard');
$router->get('admin/users', 'AdminController@users');
$router->post('admin/users', 'AdminController@usersPost');
$router->get('admin/routes', 'AdminController@routes');
$router->get('admin/trucks', 'AdminController@trucks');
$router->get('admin/complaints', 'AdminController@complaints');
$router->post('admin/complaints', 'AdminController@complaintsPost');
$router->get('admin/reports', 'AdminController@reports');
$router->get('admin/logs', 'AdminController@logs');
$router->get('admin/messages', 'AdminController@messages');
$router->get('admin/messages/view', 'AdminController@messageView');
$router->post('admin/messages', 'AdminController@messagesPost');
$router->get('admin/sms', 'AdminController@sms');
$router->post('admin/sms', 'AdminController@smsPost');
$router->get('admin/chatbot', 'AdminController@chatbot');
$router->post('admin/chatbot', 'AdminController@chatbotPost');
$router->get('admin/settings', 'AdminController@settings');
$router->post('admin/settings', 'AdminController@settingsPost');
$router->post('admin/routes', 'AdminController@routesPost');

// Finance
$router->get('finance/dashboard', 'FinanceController@dashboard');
$router->get('finance/payments', 'FinanceController@payments');
$router->post('finance/verify', 'FinanceController@verifyCash');
$router->get('finance/pricing', 'FinanceController@pricing');
$router->post('finance/pricing', 'FinanceController@pricingPost');
$router->get('finance/reports', 'FinanceController@reports');

// AJAX / API
$router->get('api/pricing', 'ApiController@pricing');
$router->get('api/export', 'ApiController@export');
$router->get('api/receipt', 'ApiController@receipt');
$router->get('api/chatbot/init', 'ChatbotController@init');
$router->post('api/chatbot/send', 'ChatbotController@send');

$router->dispatch();
