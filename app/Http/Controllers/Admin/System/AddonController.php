<?php

namespace App\Http\Controllers\Admin\System;

use App\Models\Module;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Contracts\View\View;
use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Foundation\Application;

class AddonController extends Controller
{
    public function __construct()
    {
        // SmsGateway trait is loaded dynamically via Laravel's service container
        // Removed insecure eval() backdoor pattern
    }

    public function index(): Factory|View|Application
    {
        $dir = 'Modules';
        $directories = self::getDirectories($dir);
        $addons = [];
        foreach ($directories as $directory) {
            if(!in_array($directory, ['TaxModule','ReelsModule','AI'])) {
                $sub_dirs = self::getDirectories('Modules/' . $directory);
                if (in_array('Addon', $sub_dirs)) {
                    $addons[] = 'Modules/' . $directory;
                }
            }
        }
        return view('admin-views.system.addon.index', compact('addons'));
    }

    public function publish(Request $request): JsonResponse|int
    {
        if (getEnvMode() == 'demo') {
            return response()->json([
                'status' => 'demo',
                'message'=> translate('messages.update_option_is_disable_for_demo')
            ]);
        }
        $full_data = include($request['path'] . '/Addon/info.php');
        $path = $request['path'];
        $addon_name = $full_data['name'];
        if ($full_data['purchase_code'] == null || $full_data['username'] == null) {
            return response()->json([
                'flag' => 'inactive',
                'view' => view('admin-views.system.addon.partials.activation-modal-data', compact('full_data', 'path', 'addon_name'))->render(),
            ]);
        }
        $full_data['is_published'] = $full_data['is_published'] ? 0 : 1;
        $str = "<?php return " . var_export($full_data, true) . ";";
        file_put_contents(base_path($request['path'] . '/Addon/info.php'), $str);

        if ($full_data['name'] == 'Rental') {
            $this->rentalPublish($full_data['is_published']);
        }

        if ($full_data['name'] == 'RideShare') {
            $this->rideSharePublish($full_data['is_published']);
        }

        return response()->json([
            'status' => 'success',
            'message'=> 'status_updated_successfully'
        ]);
    }

    public function activation(Request $request): Redirector|RedirectResponse|Application
    {
        if (getEnvMode() == 'demo') {
            Toastr::info(translate('messages.update_option_is_disable_for_demo'));
            return back();
        }
        $remove = ["http://", "https://", "www."];
        $url = str_replace($remove, "", url('/'));
        $full_data = include($request['path'] . '/Addon/info.php');

        $post = [
            base64_decode('dXNlcm5hbWU=') => $request['username'],
            base64_decode('cHVyY2hhc2Vfa2V5') => $request['purchase_code'],
            base64_decode('c29mdHdhcmVfaWQ=') => $full_data['software_id'],
            base64_decode('ZG9tYWlu') => $url,
        ];

        $response = Http::post(base64_decode('aHR0cHM6Ly9jaGVjay42YW10ZWNoLmNvbS9hcGkvdjEvYWN0aXZhdGlvbi1jaGVjaw=='), $post)->json();
        $status = $response['active'] ?? base64_encode(1);

        if ((int)base64_decode($status)) {
            // $full_data['is_published'] = $full_data['is_published'] ? 0 : 1;

            $full_data['is_published'] = 1;
            $full_data['username'] = $request['username'];
            $full_data['purchase_code'] = $request['purchase_code'];
            $str = "<?php return " . var_export($full_data, true) . ";";
            file_put_contents(base_path($request['path'] . '/Addon/info.php'), $str);
            $this->rentalPublish($full_data['is_published']);
            $this->rideSharePublish($full_data['is_published']);

            Toastr::success(translate('activated_successfully'));
            return back();
        }

        $activation_url = base64_decode('aHR0cHM6Ly9hY3RpdmF0aW9uLjZhbXRlY2guY29t');
        $activation_url .= '?username=' . $request['username'];
        $activation_url .= '&purchase_code=' . $request['purchase_code'];
        $activation_url .= '&domain=' . url('/') . '&';

        return redirect($activation_url);
    }

    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file_upload' => 'required|file|mimes:zip|max:51200'
        ]);

        if ($validator->fails()) {
            $error = Helpers::error_processor($validator);
            return response()->json(['status' => 'error', 'message' => $error[0]['message'] ?? 'Invalid file']);
        }

        $file = $request->file('file_upload');
        try {
            Helpers::validateFile($file);
        } catch (\App\Exceptions\InvalidUploadException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }

        $filename = $file->getClientOriginalName();
        $tempPath = $file->storeAs('temp', $filename);
        $zip = new \ZipArchive();

        if ($zip->open(storage_path('app/' . $tempPath)) !== TRUE) {
            Storage::delete($tempPath);
            return response()->json(['status' => 'error', 'message' => translate('file_upload_fail!')]);
        }

        $blockedExtensions = ['php', 'php3', 'php4', 'php5', 'phtml', 'phar', 'htaccess', 'sh', 'exe', 'bat'];
        $blockedPatterns = ['/^\./', '/^__MACOSX/', '/\.DS_Store$/'];

        // Scan ZIP contents before extraction
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $fileName = $zip->getNameIndex($i);
            $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $baseName = basename($fileName);

            if (in_array($extension, $blockedExtensions)) {
                $zip->close();
                Storage::delete($tempPath);
                return response()->json(['status' => 'error', 'message' => translate('Suspicious file detected in archive: ') . $fileName]);
            }

            foreach ($blockedPatterns as $pattern) {
                if (preg_match($pattern, $baseName)) {
                    $zip->close();
                    Storage::delete($tempPath);
                    return response()->json(['status' => 'error', 'message' => translate('Hidden/system files not allowed')]);
                }
            }
        }

        $extractPath = base_path('Modules/');
        if (!File::isWritable($extractPath)) {
            $zip->close();
            Storage::delete($tempPath);
            return response()->json([
                'status' => 'error',
                'message' => translate('messages.File is not writable. Please check your file permissions.')
            ]);
        }

        $zip->extractTo($extractPath);
        $zip->close();

        $expectedDir = $extractPath . pathinfo($filename, PATHINFO_FILENAME);

        if (!File::exists($expectedDir . '/Addon/info.php')) {
            if (File::isDirectory($expectedDir)) {
                File::deleteDirectory($expectedDir);
            }
            Storage::delete($tempPath);
            return response()->json(['status' => 'error', 'message' => translate('invalid_file!')]);
        }

        // Secure permissions (0755 instead of 0777)
        File::chmod($expectedDir . '/Addon', 0755);
        File::chmod($expectedDir, 0755);

        Storage::delete($tempPath);
        Toastr::success(translate('file_upload_successfully!'));

        return response()->json([
            'status' => 'success',
            'message' => translate('file_upload_successfully!')
        ]);
    }

    public function delete_theme(Request $request)
    {
        if (getEnvMode() == 'demo') {
            Toastr::info(translate('messages.update_option_is_disable_for_demo'));
            return back();
        }

        $validator = Validator::make($request->all(), [
            'path' => 'required|string|regex:/^Modules\/[a-zA-Z0-9_-]+$/'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => translate('Invalid path format')
            ]);
        }

        $allowedBase = base_path('Modules');
        $requestedPath = realpath(base_path($request->path));

        if ($requestedPath === false || strpos($requestedPath, $allowedBase) !== 0 || $requestedPath === $allowedBase) {
            return response()->json([
                'status' => 'error',
                'message' => translate('Invalid or unauthorized path')
            ]);
        }

        if (File::deleteDirectory($requestedPath)) {
            return response()->json([
                'status' => 'success',
                'message' => translate('file_delete_successfully')
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => translate('file_delete_fail')
        ]);
    }

    //helper functions
    function getDirectories(string $path): array
    {
        $fullPath = base_path($path);

        if (!is_dir($fullPath)) {
            return [];
        }

        $directories = [];

        foreach (scandir($fullPath) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            if (is_dir($fullPath . DIRECTORY_SEPARATOR . $item)) {
                $directories[] = $item;
            }
        }

        return $directories;
    }

    private function rentalPublish(int|bool $is_published): bool
    {
        try {
            $module = Module::firstOrNew(
                ['module_type' => 'rental'],
                ['module_name' => 'Rental']
            );

            if ($is_published) {
                Artisan::call('migrate', ['--force' => true]);
                $module->status = 1;
            } else {
                $module->status = 0;
            }

            $module->save();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function rideSharePublish(int|bool $is_published): bool
    {
        try {
            $module = Module::firstOrNew(
                ['module_type' => 'ride-share'],
                ['module_name' => 'RideShare']
            );

            if ($is_published) {
                Artisan::call('migrate', ['--force' => true]);
                $module->status = 1;
            } else {
                $module->status = 0;
            }

            $module->save();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
