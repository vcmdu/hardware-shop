<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Setting;
use App\Helpers\AuditLogger;
use App\Helpers\FileUpload;

class SettingController extends Controller {
    public function index(Request $request, Response $response) {
        $this->requireRoles(['super_admin', 'admin']);
        $model = new Setting();
        $settings = $model->getAll();
        $this->render('settings/index', ['title' => 'System Settings', 'settings' => $settings]);
    }

    public function update(Request $request, Response $response) {
        $this->requireRoles(['super_admin', 'admin']);
        $this->validateCsrf($request);
        $body = $request->getBody();
        $model = new Setting();

        // Handle logo upload
        $files = $request->getFiles();
        if (!empty($files['shop_logo']['tmp_name'])) {
            $path = FileUpload::uploadProductImage($files['shop_logo']);
            if ($path) $body['shop_logo'] = $path;
        }

        $allowed = ['shop_name','shop_address','shop_gst','shop_phone','shop_email',
                    'invoice_prefix','currency','currency_symbol','tax_settings','shop_logo'];
        $toSave = array_intersect_key($body, array_flip($allowed));
        $model->updateSettings($toSave);

        AuditLogger::log('System settings updated', array_keys($toSave));
        \App\Core\Session::setFlash('success', 'Settings saved successfully.');
        $response->redirect('/settings');
    }
}
