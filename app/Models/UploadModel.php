<?php namespace App\Models;

require_once APPPATH . "ThirdParty/intervention-image/vendor/autoload.php";

use CodeIgniter\Model;
use Intervention\Image\ImageManager;
use Intervention\Image\ImageManagerStatic as Image;

class UploadModel extends BaseModel
{
    public function __construct()
    {
        parent::__construct();
        $this->imgQuality = 85;
    }

    //upload file
    private function upload($inputName, $directory, $namePrefix, $allowedExtensions = null, $keepOrjName = false)
    {
        if ($allowedExtensions != null && is_array($allowedExtensions) && !empty($allowedExtensions[0])) {
            if (!$this->checkAllowedFileTypes($inputName, $allowedExtensions)) {
                return null;
            }
        }
        $file = $this->request->getFile($inputName);
        if (!empty($file) && !empty($file->getName())) {
            $orjName = $file->getName();
            $name = pathinfo($orjName, PATHINFO_FILENAME);
            $ext = pathinfo($orjName, PATHINFO_EXTENSION);
            $name = strSlug($name);
            if (empty($name)) {
                $name = generateToken();
            }
            $uniqiName = $namePrefix . generateToken() . '.' . $ext;
            if ($keepOrjName == true) {
                $fullName = $name . '.' . $ext;
                if (file_exists(FCPATH . $directory . '/' . $fullName)) {
                    $fullName = $name . '-' . uniqid() . '.' . $ext;
                }
                $uniqiName = $fullName;
            }
            $path = $directory . $uniqiName;
            if (!$file->hasMoved()) {
                if ($file->move(FCPATH . $directory, $uniqiName)) {
                    return ['name' => $uniqiName, 'orjName' => $orjName, 'path' => $path, 'ext' => $ext];
                }
            }
        }
        return null;
    }

    //upload temp file
    public function uploadTempFile($inputName, $isImage = false)
    {
        $allowedExtensions = array();
        if ($isImage) {
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        }
        return $this->upload($inputName, 'uploads/tmp/', 'temp_', $allowedExtensions);
    }

    //upload file
    public function uploadFile($inputName)
    {
        $extensions = $this->generalSettings->allowed_file_extensions;
        $extArray = array();
        if (!empty($extensions)) {
            $extArray = @explode(',', $extensions);
        }
        return $this->upload($inputName, "uploads/files/", "file_", $extArray, true);
    }

    //upload GIF image
    public function uploadGIF($fileName, $targetDirectory)
    {
        $newName = 'img_' . generateToken() . '.gif';
        $directory = $this->createUploadDirectory($targetDirectory);
        rename(FCPATH . 'uploads/tmp/' . $fileName, FCPATH . 'uploads/' . $targetDirectory . '/' . $directory . $newName);
        return 'uploads/' . $targetDirectory . '/' . $directory . $newName;
    }

    //upload CSV file
    public function uploadCSVFile($inputName)
    {
        return $this->upload($inputName, 'uploads/tmp/', 'temp_', ['csv']);
    }

    //upload post image
    public function uploadPostImage($tempPath, $type)
    {
        $img = Image::make($tempPath)->orientate();
        $name = '';
        if ($type == 'big') {
            $name = 'image_870x580_';
            $img->fit(870, 580);
        } elseif ($type == 'default') {
            $name = 'image_870x_';
            $img->resize(870, null, function ($constraint) {
                $constraint->aspectRatio();
            });
        } elseif ($type == 'slider') {
            $name = 'image_694x532_';
            $img->fit(694, 532);
        } elseif ($type == 'mid') {
            $name = 'image_430x256_';
            $img->fit(450, 280);
        } elseif ($type == 'small') {
            $name = 'image_140x98_';
            $img->fit(140, 98);
        }
        $newPath = 'uploads/images/' . $this->createUploadDirectory('images') . $name . uniqid() . '.jpg';
        $img->save(FCPATH . $newPath, $this->imgQuality);
        return $newPath;
    }

    //upload quiz image
    public function uploadQuizImage($tempPath, $type)
    {
        $img = Image::make($tempPath)->orientate();
        $name = '';
        if ($type == 'default') {
            $name = 'image_870x580_';
            $img->fit(870, 580);
        } elseif ($type == 'small') {
            $name = 'image_420x420_';
            $img->fit(420, 420);
        }
        $newPath = 'uploads/quiz/' . $this->createUploadDirectory('quiz') . $name . uniqid() . '.jpg';
        $img->save(FCPATH . $newPath, $this->imgQuality);
        return $newPath;
    }

    //gallery image upload
    public function uploadGalleryImage($tempPath, $width)
    {
        $newPath = 'uploads/gallery/' . $this->createUploadDirectory('gallery') . 'image_' . $width . 'x_' . uniqid() . '.jpg';
        $img = Image::make($tempPath)->orientate();
        $img->resize($width, null, function ($constraint) {
            $constraint->aspectRatio();
        });
        $img->save(FCPATH . $newPath, $this->imgQuality);
        return $newPath;
    }

    //avatar image upload
    public function uploadAvatar($userId, $path)
    {
        $directory = $this->createUploadDirectory('profile');
        $newPath = 'uploads/profile/' . $directory . 'avatar_' . $userId . '_' . uniqid() . '.jpg';
        $img = Image::make($path)->orientate();
        $img->fit(240, 240);
        $img->save(FCPATH . $newPath, 100);
        return $newPath;
    }

    //cover image upload
    public function uploadCoverImage($userId, $path)
    {
        $directory = $this->createUploadDirectory('profile');
        $newPath = 'uploads/profile/' . $directory . 'cover_' . $userId . '_' . uniqid() . '.jpg';
        $img = Image::make($path)->orientate();
        $img->fit(1920, 600);
        $img->save(FCPATH . $newPath, $this->imgQuality);
        return $newPath;
    }

    //upload video
    public function uploadVideo($inputName)
    {
        $directory = $this->createUploadDirectory('videos');
        return $this->upload($inputName, 'uploads/videos/' . $directory, 'video_');
    }

    //upload audio
    public function uploadAudio($inputName)
    {
        $directory = $this->createUploadDirectory('audios');
        return $this->upload($inputName, 'uploads/audios/' . $directory, 'audio_', null, true);
    }

    //logo upload
    public function uploadLogo($inputName)
    {
        return $this->upload($inputName, "uploads/logo/", "logo_", ['jpg', 'jpeg', 'png', 'gif', 'svg']);
    }

    //favicon image upload
    //logo upload
    public function uploadFavicon($inputName)
    {
        return $this->upload($inputName, "uploads/logo/", "favicon_", ['jpg', 'jpeg', 'png', 'gif']);
    }

    //ad upload
    public function uploadAd($inputName)
    {
        return $this->upload($inputName, "uploads/blocks/", "block_", ['jpg', 'jpeg', 'png', 'gif']);
    }

    //download temp image
    function downloadTempImage($url, $ext, $fileName = 'temp')
    {
        $pathJPG = FCPATH . 'uploads/tmp/' . $fileName . '.jpg';
        $pathGIF = FCPATH . 'uploads/tmp/' . $fileName . '.gif';
        if (file_exists($pathJPG)) {
            @unlink($pathJPG);
        }
        if (file_exists($pathGIF)) {
            @unlink($pathGIF);
        }
        $path = $pathJPG;
        if ($ext == 'gif') {
            $path = $pathGIF;
        }
        $context = stream_context_create(array(
            'http' => array(
                'header' => array('User-Agent: Mozilla/5.0 (Windows; U; Windows NT 6.1; rv:2.2) Gecko/20110201')
            )
        ));
        if (copy($url, $path, $context)) {
            return $path;
        }
        return false;
    }

    //create upload directory
    public function createUploadDirectory($folder)
    {
        $directory = date("Ym");
        $directoryPath = FCPATH . 'uploads/' . $folder . '/' . $directory . '/';
        if (!is_dir($directoryPath)) {
            @mkdir($directoryPath, 0755, true);
        }
        if (!file_exists($directoryPath . "index.html")) {
            @copy(FCPATH . "uploads/index.html", $directoryPath . "index.html");
        }
        return $directory . '/';
    }

    private function chunkUploadDir($uploadId): string
    {
        return FCPATH . 'uploads/tmp/chunks/' . $uploadId . '/';
    }

    private function readChunkMeta(string $dir): array
    {
        $metaPath = $dir . 'meta.json';
        $meta = ['next' => 0];
        if (is_file($metaPath)) {
            $decoded = json_decode((string) @file_get_contents($metaPath), true);
            if (is_array($decoded)) {
                $meta = array_merge($meta, $decoded);
            }
        }
        $meta['next'] = (int) ($meta['next'] ?? 0);
        return $meta;
    }

    private function writeChunkMeta(string $dir, array $meta): bool
    {
        return @file_put_contents($dir . 'meta.json', json_encode($meta), LOCK_EX) !== false;
    }

    public $lastChunkError = '';

    private function chunkFailure(string $message): bool
    {
        $this->lastChunkError = $message;
        log_message('error', 'Chunk upload: ' . $message);
        return false;
    }

    private function phpUploadErrorText(int $error): string
    {
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            return 'حجم قطعه از محدودیت آپلود سرور بیشتر است';
        }
        if ($error === UPLOAD_ERR_PARTIAL) {
            return 'قطعه ویدیو ناقص به سرور رسید';
        }
        if ($error === UPLOAD_ERR_NO_FILE) {
            return 'فایل قطعه به سرور نرسید';
        }
        if ($error === UPLOAD_ERR_NO_TMP_DIR || $error === UPLOAD_ERR_CANT_WRITE) {
            return 'پوشه موقت PHP روی سرور قابل نوشتن نیست';
        }
        return 'ذخیره قطعه ناموفق بود';
    }

    //append each chunk into one data.bin so finalize is a fast rename/copy
    public function saveUploadChunk($inputName, $uploadId, $chunkIndex)
    {
        $this->lastChunkError = '';
        $uploadId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $uploadId);
        if ($uploadId === '' || strlen($uploadId) > 80) {
            return $this->chunkFailure('شناسه آپلود نامعتبر است');
        }
        $chunkIndex = (int) $chunkIndex;
        if ($chunkIndex < 0) {
            return $this->chunkFailure('شماره قطعه نامعتبر است');
        }

        $upload = $_FILES[$inputName] ?? null;
        if (!is_array($upload)) {
            return $this->chunkFailure('فایل قطعه به سرور نرسید');
        }
        $error = (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            return $this->chunkFailure($this->phpUploadErrorText($error));
        }
        $tmpName = (string) ($upload['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            return $this->chunkFailure('فایل موقت آپلود معتبر نیست');
        }

        $dir = $this->chunkUploadDir($uploadId);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return $this->chunkFailure('پوشه موقت آپلود ساخته نشد');
        }
        if (!is_writable($dir)) {
            return $this->chunkFailure('پوشه موقت آپلود قابل نوشتن نیست');
        }

        $lock = @fopen($dir . 'write.lock', 'c');
        if ($lock === false || !@flock($lock, LOCK_EX)) {
            if ($lock !== false) {
                fclose($lock);
            }
            return $this->chunkFailure('قفل نوشتن قطعه برقرار نشد');
        }

        try {
            $meta = $this->readChunkMeta($dir);
            $next = (int) ($meta['next'] ?? 0);
            if ($chunkIndex < $next) {
                return true;
            }
            if ($chunkIndex !== $next) {
                return $this->chunkFailure('ترتیب قطعه‌ها نادرست است');
            }

            $partPath = $dir . $chunkIndex . '.part';
            if (is_file($partPath)) {
                @unlink($partPath);
            }
            if (!@move_uploaded_file($tmpName, $partPath) && !@copy($tmpName, $partPath)) {
                return $this->chunkFailure('ذخیره قطعه روی دیسک انجام نشد');
            }
            if (!is_file($partPath)) {
                return $this->chunkFailure('قطعه بعد از ذخیره پیدا نشد');
            }

            $dataPath = $dir . 'data.bin';
            $beforeSize = is_file($dataPath) ? (int) filesize($dataPath) : 0;
            $in = @fopen($partPath, 'rb');
            $out = @fopen($dataPath, 'ab');
            if ($in === false || $out === false) {
                if ($in !== false) {
                    fclose($in);
                }
                if ($out !== false) {
                    fclose($out);
                }
                return $this->chunkFailure('باز کردن فایل ویدیو برای نوشتن ممکن نشد');
            }

            $copied = @stream_copy_to_stream($in, $out);
            fclose($in);
            fclose($out);
            if ($copied === false) {
                $fh = @fopen($dataPath, 'c+b');
                if ($fh) {
                    @ftruncate($fh, $beforeSize);
                    fclose($fh);
                }
                return $this->chunkFailure('چسباندن قطعه به ویدیو ناموفق بود');
            }

            $meta['next'] = $chunkIndex + 1;
            if (!$this->writeChunkMeta($dir, $meta)) {
                $fh = @fopen($dataPath, 'c+b');
                if ($fh) {
                    @ftruncate($fh, $beforeSize);
                    fclose($fh);
                }
                return $this->chunkFailure('ثبت وضعیت قطعه ناموفق بود');
            }

            @unlink($partPath);
            return true;
        } finally {
            @flock($lock, LOCK_UN);
            @fclose($lock);
        }
    }

    //move assembled data.bin (or legacy .part files) into the final uploads folder
    public function finalizeChunkedUpload($uploadId, $originalName, $targetFolder, $namePrefix, $keepOrjName = false, $totalChunks = 0)
    {
        $uploadId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $uploadId);
        $dir = $this->chunkUploadDir($uploadId);
        if ($uploadId === '' || !is_dir($dir)) {
            return null;
        }

        $dataPath = $dir . 'data.bin';
        $meta = $this->readChunkMeta($dir);
        $assembled = is_file($dataPath);
        $parts = [];

        if ($assembled) {
            if ($totalChunks > 0 && (int) ($meta['next'] ?? 0) < $totalChunks) {
                log_message('error', 'Chunk finalize incomplete for ' . $uploadId);
                return null;
            }
        } else {
            // Legacy fallback: many .part files from older uploader
            $parts = glob($dir . '*.part') ?: [];
            if (empty($parts)) {
                return null;
            }
            natsort($parts);
            $parts = array_values($parts);
            if ($totalChunks > 0 && count($parts) < $totalChunks) {
                log_message('error', 'Chunk finalize incomplete for ' . $uploadId);
                return null;
            }
        }

        $orjName = !empty($originalName) ? $originalName : ('file_' . $uploadId);
        $name = pathinfo($orjName, PATHINFO_FILENAME);
        $ext = pathinfo($orjName, PATHINFO_EXTENSION);
        $name = strSlug($name);
        if (empty($name)) {
            $name = generateToken();
        }
        if (empty($ext)) {
            $ext = 'bin';
        }

        $directory = $this->createUploadDirectory($targetFolder);
        $baseDir = 'uploads/' . $targetFolder . '/' . $directory;
        $targetDir = FCPATH . $baseDir;
        if (!is_dir($targetDir) && !@mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
            return null;
        }

        $uniqiName = $namePrefix . generateToken() . '.' . $ext;
        if ($keepOrjName === true) {
            $fullName = $name . '.' . $ext;
            if (file_exists($targetDir . $fullName)) {
                $fullName = $name . '-' . uniqid() . '.' . $ext;
            }
            $uniqiName = $fullName;
        }

        $finalPath = $targetDir . $uniqiName;
        if ($assembled) {
            if (!@rename($dataPath, $finalPath)) {
                if (!@copy($dataPath, $finalPath)) {
                    return null;
                }
                @unlink($dataPath);
            }
        } else {
            $out = @fopen($finalPath, 'wb');
            if ($out === false) {
                return null;
            }
            foreach ($parts as $part) {
                $in = @fopen($part, 'rb');
                if ($in === false) {
                    fclose($out);
                    @unlink($finalPath);
                    return null;
                }
                stream_copy_to_stream($in, $out);
                fclose($in);
            }
            fclose($out);
        }

        $this->deleteChunkedUpload($uploadId);

        return [
            'name' => $uniqiName,
            'orjName' => $orjName,
            'path' => $baseDir . $uniqiName,
            'ext' => $ext
        ];
    }

    //delete incomplete chunked upload
    public function deleteChunkedUpload($uploadId)
    {
        $uploadId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $uploadId);
        if ($uploadId === '') {
            return;
        }
        $dir = $this->chunkUploadDir($uploadId);
        if (!is_dir($dir)) {
            $legacy = FCPATH . 'uploads/tmp/chunks/' . $uploadId . '.part';
            if (is_file($legacy)) {
                @unlink($legacy);
            }
            return;
        }
        foreach (glob($dir . '*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($dir);
    }

    //check allowed file types
    public function checkAllowedFileTypes($fileName, $allowedTypes)
    {
        if (!isset($_FILES[$fileName])) {
            return false;
        }
        if (empty($_FILES[$fileName]['name'])) {
            return false;
        }

        $ext = pathinfo($_FILES[$fileName]['name'], PATHINFO_EXTENSION);
        if(!empty($ext)){
            $ext = strtolower($ext);
        }
        $extArray = array();
        if (!empty($allowedTypes) && is_array($allowedTypes)) {
            foreach ($allowedTypes as $item) {
                if (!empty($item)) {
                    $item = trim($item, '"');
                }
                if (!empty($item)) {
                    $item = trim($item, "'");
                }
                array_push($extArray, $item);
            }
        }
        if (!empty($extArray) && in_array($ext, $extArray)) {
            return true;
        }
        return false;
    }

    //get file extension
    public function getFileExtension($name)
    {
        $ext = '';
        if (!empty($name)) {
            $ext = pathinfo($name, PATHINFO_EXTENSION);
        }
        if (!empty($ext)) {
            $ext = strtolower($ext);
        }
        return $ext;
    }

    //delete temp file
    public function deleteTempFile($path)
    {
        if (file_exists($path)) {
            @unlink($path);
        }
    }
}