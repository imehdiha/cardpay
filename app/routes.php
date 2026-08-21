<?php
declare(strict_types=1);

use CardPay\Controllers\AdminController;
use CardPay\Controllers\Api\DeviceApiController;
use CardPay\Controllers\Api\PaymentApiController;
use CardPay\Controllers\AuthController;
use CardPay\Controllers\InstallerController;
use CardPay\Controllers\PaymentPageController;
use CardPay\Core\Config;
use CardPay\Core\Crypto;
use CardPay\Core\Csrf;
use CardPay\Core\Database;
use CardPay\Core\Request;
use CardPay\Core\Response;
use CardPay\Core\Session;
use CardPay\Core\Url;
use CardPay\Core\View;
use CardPay\Repositories\PaymentRepository;
use CardPay\Repositories\SettingsRepository;
use CardPay\Services\AdminService;
use CardPay\Services\ApiAuthService;
use CardPay\Services\AuditService;
use CardPay\Services\DeviceAuthService;
use CardPay\Services\MaintenanceService;
use CardPay\Services\ManualReviewService;
use CardPay\Services\PaymentService;
use CardPay\Services\RateLimiter;
use CardPay\Services\SmsService;
use CardPay\Services\WebhookService;
use CardPay\Services\UploadService;

$session=$container->get(Session::class);$view=$container->get(View::class);$config=$container->get(Config::class);
$authController=new AuthController($container->get(Database::class),$view,$session,$container->get(Csrf::class),$container->get(RateLimiter::class),$container->get(AuditService::class));
$paymentApi=new PaymentApiController($container->get(ApiAuthService::class),$container->get(PaymentService::class),$container->get(MaintenanceService::class),$container->get(RateLimiter::class),$config);
$deviceApi=new DeviceApiController($container->get(DeviceAuthService::class),$container->get(SmsService::class),$container->get(MaintenanceService::class),$container->get(RateLimiter::class),$config);
$paymentPage=new PaymentPageController($container->get(PaymentRepository::class),$container->get(SettingsRepository::class),$container->get(Crypto::class),$view,$container->get(Csrf::class),$container->get(MaintenanceService::class),$container->get(ManualReviewService::class),$container->get(RateLimiter::class),$config,$root);
$adminController=new AdminController($container->get(AdminService::class),$container->get(SettingsRepository::class),$view,$session,$container->get(Csrf::class),$container->get(MaintenanceService::class),$container->get(ManualReviewService::class),$container->get(WebhookService::class),$container->get(AuditService::class),$container->get(UploadService::class),$container->get(PaymentService::class),$config,$root);
$requireAdmin=static function(Request$req,callable$next)use($session):Response{if(!$session->get('user_id'))return Response::redirect(Url::to('/login'));if($session->get('user_role')!=='admin')return new Response('Forbidden',403);return$next($req);};

$router->get('/',fn()=>Response::redirect(Url::to('/admin')));
$router->get('/install',fn(Request$r)=>(new InstallerController($root,$view,$session,$container->get(Csrf::class)))->show($r));
$router->get('/login',[$authController,'show']);$router->post('/login',[$authController,'login']);$router->post('/logout',[$authController,'logout'],[$requireAdmin]);

$router->post('/api/v1/payments',[$paymentApi,'create']);$router->get('/api/v1/payments/{payment_id}',[$paymentApi,'show']);$router->post('/api/v1/payments/{payment_id}/verify',[$paymentApi,'verify']);$router->post('/api/v1/payments/{payment_id}/cancel',[$paymentApi,'cancel']);
$router->post('/api/v1/devices/incoming-sms',[$deviceApi,'incoming']);$router->post('/api/v1/devices/shortcut-sms',[$deviceApi,'shortcutIncoming']);$router->get('/api/v1/public/payments/{public_id}/status',[$paymentPage,'status']);
$router->get('/p/{public_id}',[$paymentPage,'show']);$router->post('/p/{public_id}/manual-review',[$paymentPage,'report']);

$router->get('/admin',[$adminController,'dashboard'],[$requireAdmin]);
$router->get('/admin/setup',[$adminController,'setup'],[$requireAdmin]);
$router->post('/admin/test-payment',[$adminController,'createTestPayment'],[$requireAdmin]);
$router->get('/admin/connection',[$adminController,'connection'],[$requireAdmin]);
$router->post('/admin/connection/create',[$adminController,'createConnection'],[$requireAdmin]);
$router->post('/admin/connection/save',[$adminController,'saveConnection'],[$requireAdmin]);
$router->post('/admin/connection/rotate',[$adminController,'rotateConnection'],[$requireAdmin]);
$router->get('/admin/applications',[$adminController,'applications'],[$requireAdmin]);$router->get('/admin/applications/create',[$adminController,'applicationForm'],[$requireAdmin]);$router->post('/admin/applications/create',[$adminController,'saveApplication'],[$requireAdmin]);$router->get('/admin/applications/{id}/edit',[$adminController,'applicationForm'],[$requireAdmin]);$router->post('/admin/applications/{id}/edit',[$adminController,'saveApplication'],[$requireAdmin]);$router->get('/admin/applications/{id}/credentials',[$adminController,'applicationCredentials'],[$requireAdmin]);$router->post('/admin/applications/{id}/rotate',[$adminController,'rotateApplication'],[$requireAdmin]);
$router->get('/admin/cards',[$adminController,'cards'],[$requireAdmin]);$router->get('/admin/cards/create',[$adminController,'cardForm'],[$requireAdmin]);$router->post('/admin/cards/create',[$adminController,'saveCard'],[$requireAdmin]);$router->get('/admin/cards/{id}/edit',[$adminController,'cardForm'],[$requireAdmin]);$router->post('/admin/cards/{id}/edit',[$adminController,'saveCard'],[$requireAdmin]);
$router->get('/admin/devices',[$adminController,'devices'],[$requireAdmin]);$router->get('/admin/devices/create',[$adminController,'deviceForm'],[$requireAdmin]);$router->post('/admin/devices/create',[$adminController,'saveDevice'],[$requireAdmin]);$router->get('/admin/devices/{id}/setup',[$adminController,'deviceSetup'],[$requireAdmin]);$router->post('/admin/devices/{id}/rotate',[$adminController,'rotateDevice'],[$requireAdmin]);$router->post('/admin/devices/{id}/revoke',[$adminController,'revokeDevice'],[$requireAdmin]);
$router->get('/admin/parsers',[$adminController,'parsers'],[$requireAdmin]);$router->get('/admin/parsers/create',[$adminController,'parserForm'],[$requireAdmin]);$router->post('/admin/parsers/create',[$adminController,'saveParser'],[$requireAdmin]);$router->get('/admin/parsers/{id}/edit',[$adminController,'parserForm'],[$requireAdmin]);$router->post('/admin/parsers/{id}/edit',[$adminController,'saveParser'],[$requireAdmin]);$router->post('/admin/parsers/test',[$adminController,'testParser'],[$requireAdmin]);
$router->get('/admin/payments',[$adminController,'payments'],[$requireAdmin]);$router->get('/admin/payments/{id}',[$adminController,'payment'],[$requireAdmin]);
$router->get('/admin/sms',[$adminController,'sms'],[$requireAdmin]);$router->get('/admin/sms/{id}',[$adminController,'smsDetail'],[$requireAdmin]);$router->get('/admin/unmatched',[$adminController,'unmatched'],[$requireAdmin]);
$router->get('/admin/reviews',[$adminController,'reviewList'],[$requireAdmin]);$router->get('/admin/reviews/{id}',[$adminController,'review'],[$requireAdmin]);$router->post('/admin/reviews/{id}/approve',[$adminController,'approveReview'],[$requireAdmin]);$router->post('/admin/reviews/{id}/reject',[$adminController,'rejectReview'],[$requireAdmin]);
$router->get('/admin/webhooks',[$adminController,'webhookList'],[$requireAdmin]);$router->get('/admin/webhooks/{id}',[$adminController,'webhook'],[$requireAdmin]);$router->post('/admin/webhooks/{id}/retry',[$adminController,'retryWebhook'],[$requireAdmin]);
$router->get('/admin/settings',[$adminController,'settings'],[$requireAdmin]);$router->get('/admin/settings/{section}',[$adminController,'settings'],[$requireAdmin]);$router->post('/admin/settings/{section}',[$adminController,'saveSettings'],[$requireAdmin]);
$router->get('/admin/reports',[$adminController,'reports'],[$requireAdmin]);$router->get('/admin/reports.csv',[$adminController,'reportCsv'],[$requireAdmin]);$router->get('/admin/logs',[$adminController,'logs'],[$requireAdmin]);$router->get('/admin/system',[$adminController,'system'],[$requireAdmin]);$router->get('/admin/profile',[$adminController,'profile'],[$requireAdmin]);$router->post('/admin/profile/password',[$adminController,'changePassword'],[$requireAdmin]);
