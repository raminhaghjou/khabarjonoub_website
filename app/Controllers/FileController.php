<?php

namespace App\Controllers;

use App\Models\FileModel;

class FileController extends BaseAdminController
{
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        if (!checkUserPermission('admin_panel')) {
            exit();
        }
        $this->fileModel = new FileModel();
    }

    /*
    * --------------------------------------------------------------------
    * IMAGES
    * --------------------------------------------------------------------
    */

    /**
     * Upload Image
     */
    public function uploadImage()
    {
        $this->fileModel->uploadImage();
    }

    /**
     * Get Images
     */
    public function getImages()
    {
        $this->printImages($this->fileModel->getImages());
    }

    /**
     * Select Image File
     */
    public function selectImage()
    {
        $fileId = inputPost('file_id');
        $file = $this->fileModel->getImage($fileId);
        if (!empty($file)) {
            echo base_url($file->image_mid);
        }
    }

    /**
     * Laod More Images
     */
    public function loadMoreImages()
    {
        $lastId = inputPost('min');
        $this->printImages($this->fileModel->getMoreImages($lastId));
    }

    /**
     * Search Images
     */
    public function searchImage()
    {
        $search = inputPost('search');
        $this->printImages($this->fileModel->searchImages($search));
    }

    /**
     * Print Images
     */
    public function printImages($images)
    {
        $data = [
            'result' => 0,
            'content' => ''
        ];
        if (!empty($images)) {
            foreach ($images as $image) {
                $imgBaseURL = getBaseURLByStorage($image->storage);
                $data['content'] .= '<div class="col-file-manager" id="img_col_id_' . $image->id . '">';
                $data['content'] .= '<div class="file-box" data-file-id="' . $image->id . '" data-mid-file-path="' . $image->image_mid . '" data-default-file-path="' . $image->image_default . '" data-slider-file-path="' . $image->image_slider . '" data-big-file-path="' . $image->image_big . '" data-file-storage="' . $image->storage . '" data-file-base-url="' . $imgBaseURL . '">';
                $data['content'] .= '<div class="image-container">';
                $data['content'] .= '<img src="' . $imgBaseURL . $image->image_slider . '" alt="" class="img-responsive">';
                $data['content'] .= '</div>';
                if (!empty($image->file_name)):
                    $data['content'] .= '<span class="file-name">' . esc($image->file_name) . '</span>';
                endif;
                $data['content'] .= '</div> </div>';
            }
        }
        $data['result'] = 1;
        echo json_encode($data);
    }

    /**
     * Delete Image
     */
    public function deleteImage()
    {
        $fileId = inputPost('file_id');
        $this->fileModel->deleteImage($fileId);
    }


    /*
    *------------------------------------------------------------------------------------------
    * QUIZ IMAGES
    *------------------------------------------------------------------------------------------
    */

    /**
     * Upload Quiz Image File
     */
    public function uploadQuizImageFile()
    {
        $this->fileModel->uploadQuizImage();
    }

    /**
     * Get Quiz Images
     */
    public function getQuizImages()
    {
        $quizImages = $this->fileModel->getQuizImages();
        $this->printQuizImages($quizImages);
    }

    /**
     * Laod More Quiz Images
     */
    public function loadMoreQuizImages()
    {
        $min = inputPost('min');
        $quizImages = $this->fileModel->getMoreQuizImages($min);
        $this->printQuizImages($quizImages);
    }

    /**
     * Search Quiz Images
     */
    public function searchQuizImage()
    {
        $search = inputPost('search');
        $quizImages = $this->fileModel->searchQuizImages($search);
        $this->printQuizImages($quizImages);
    }

    /**
     * Print Quiz Images
     */
    public function printQuizImages($quizImages)
    {
        $data = [
            'result' => 0,
            'content' => ''
        ];
        if (!empty($quizImages)) {
            foreach ($quizImages as $image) {
                $imgBaseURL = getBaseURLByStorage($image->storage);
                $data['content'] .= '<div class="col-file-manager" id="img_col_id_' . $image->id . '">';
                $data['content'] .= '<div class="file-box" data-file-id="' . $image->id . '" data-default-file-path="' . $image->image_default . '" data-small-file-path="' . $image->image_small . '" data-file-storage="' . $image->storage . '" data-file-base-url="' . $imgBaseURL . '">';
                $data['content'] .= '<div class="image-container">';
                $data['content'] .= '<img src="' . $imgBaseURL . $image->image_small . '" alt="" class="img-responsive">';
                $data['content'] .= '</div>';
                if (!empty($image->file_name)) {
                    $data['content'] .= '<span class="file-name">' . esc($image->file_name) . '</span>';
                }
                $data['content'] .= '</div> </div>';
            }
        }
        $data['result'] = 1;
        echo json_encode($data);
    }

    /**
     * Delete Quiz Image File
     */
    public function deleteQuizImage()
    {
        $fileId = inputPost('file_id');
        $this->fileModel->deleteQuizImage($fileId);
    }


    /*
    *------------------------------------------------------------------------------------------
    * FILES
    *------------------------------------------------------------------------------------------
    */

    private function prepareUploadRequest()
    {
        // Prevent PHP 8.x deprecations/notices/warnings from being converted into fatal ErrorExceptions in CI4 development mode
        @error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR);
        @ini_set('display_errors', '0');
        if (\function_exists('ignore_user_abort')) {
            @ignore_user_abort(true);
        }
        if (\function_exists('set_time_limit')) {
            @set_time_limit(600);
        }
        @ini_set('max_execution_time', '600');
        @ini_set('max_input_time', '600');
        @ini_set('memory_limit', '512M');
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }
    }

    /**
     * Upload File
     */
    public function uploadFile()
    {
        $this->prepareUploadRequest();

        try {
            $ok = $this->fileModel->uploadFile();
            if (!$ok) {
                return $this->response->setStatusCode(400)->setJSON([
                    'status' => 0,
                    'message' => 'File upload failed'
                ]);
            }
            return $this->response->setJSON(['status' => 1]);
        } catch (\Throwable $e) {
            log_message('error', 'FileController::uploadFile: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 0,
                'message' => 'File upload error'
            ]);
        }
    }

    /**
     * Upload file/video in small chunks (avoids mid-transfer stalls on LiteSpeed/VPN)
     */
    public function uploadChunk()
    {
        $this->prepareUploadRequest();

        $uploadType = inputPost('upload_type');
        $uploadId = inputPost('upload_id');
        $chunkIndex = (int) inputPost('chunk_index');
        $totalChunks = (int) inputPost('total_chunks');
        $fileName = inputPost('file_name');

        if (!in_array($uploadType, ['file', 'video'], true) || empty($uploadId) || $totalChunks < 1 || $chunkIndex < 0 || $chunkIndex >= $totalChunks) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 0, 'message' => 'Invalid chunk request']);
        }

        if ($uploadType === 'video') {
            $ext = strtolower(pathinfo((string) $fileName, PATHINFO_EXTENSION));
            if (!in_array($ext, ['mp4', 'webm'], true)) {
                return $this->response->setStatusCode(400)->setJSON(['status' => 0, 'message' => 'Invalid video type']);
            }
        }

        try {
            $saved = $this->fileModel->receiveUploadChunk('file', $uploadId, $chunkIndex);
            if (!$saved) {
                $message = trim((string) ($this->fileModel->uploadModel->lastChunkError ?? ''));
                if ($message === '') {
                    $fileError = $_FILES['file']['error'] ?? 'none';
                    $message = 'Chunk save failed [v4 file=' . $fileError . ']';
                }
                return $this->response->setStatusCode(400)->setJSON([
                    'status' => 0,
                    'retryable' => 0,
                    'message' => $message,
                    'handler' => 'v4'
                ]);
            }

            if ($chunkIndex < $totalChunks - 1) {
                return $this->response->setJSON([
                    'status' => 1,
                    'done' => 0,
                    'chunk_index' => $chunkIndex,
                    'handler' => 'v4'
                ]);
            }

            $ok = $this->fileModel->saveChunkedUpload($uploadType, $uploadId, $fileName, $totalChunks);
            if (!$ok) {
                $message = trim((string) ($this->fileModel->uploadModel->lastChunkError ?? ''));
                return $this->response->setStatusCode(400)->setJSON([
                    'status' => 0,
                    'retryable' => 0,
                    'message' => $message !== '' ? $message : 'Finalize failed',
                    'handler' => 'v4'
                ]);
            }

            return $this->response->setJSON(['status' => 1, 'done' => 1, 'handler' => 'v4']);
        } catch (\Throwable $e) {
            $detail = $e->getMessage() . ' [' . basename($e->getFile()) . ':' . $e->getLine() . ']';
            log_message('error', 'FileController::uploadChunk: ' . $detail);
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 0,
                'retryable' => 0,
                'message' => 'خطای سرور هنگام ذخیره ویدیو: ' . $detail,
                'handler' => 'v4'
            ]);
        }
    }

    /**
     * Upload diagnostics (admin only): open /FileController/uploadDiag in the browser while logged in.
     */
    public function uploadDiag()
    {
        @error_reporting(E_ERROR | E_PARSE);
        @ini_set('display_errors', '0');
        $out = [];
        $out['handler_version'] = 'v4';
        $out['php_version'] = PHP_VERSION;
        $out['sapi'] = PHP_SAPI;
        $out['user'] = function_exists('posix_getpwuid') ? (@posix_getpwuid(@posix_geteuid())['name'] ?? '?') : get_current_user();
        $out['environment'] = ENVIRONMENT;
        $out['file_controller_mtime'] = date('Y-m-d H:i:s', @filemtime(__FILE__));
        $out['file_controller_path'] = __FILE__;
        $out['opcache_enabled'] = function_exists('opcache_get_status') ? (bool) (@opcache_get_status(false)['opcache_enabled'] ?? false) : false;
        $out['opcache_validate_timestamps'] = ini_get('opcache.validate_timestamps');
        $out['opcache_revalidate_freq'] = ini_get('opcache.revalidate_freq');
        $out['upload_max_filesize'] = ini_get('upload_max_filesize');
        $out['post_max_size'] = ini_get('post_max_size');
        $out['memory_limit'] = ini_get('memory_limit');
        $out['max_execution_time'] = ini_get('max_execution_time');
        $out['sys_temp_dir'] = sys_get_temp_dir();
        $out['sys_temp_writable'] = is_writable(sys_get_temp_dir());
        $out['disk_free_mb'] = round((float) @disk_free_space(FCPATH) / 1048576);

        $dirs = [
            'WRITEPATH' => WRITEPATH,
            'WRITEPATH/chunk-uploads' => WRITEPATH . 'chunk-uploads',
            'uploads' => FCPATH . 'uploads',
            'uploads/videos' => FCPATH . 'uploads/videos',
            'uploads/tmp' => FCPATH . 'uploads/tmp',
        ];
        foreach ($dirs as $label => $dir) {
            $exists = is_dir($dir);
            $canWrite = false;
            if ($exists) {
                $probe = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . '.diag_' . uniqid();
                $canWrite = @file_put_contents($probe, 'x') !== false;
                @unlink($probe);
            }
            $out['dirs'][$label] = ['exists' => $exists, 'writable_real_test' => $canWrite];
        }

        try {
            $db = \Config\Database::connect(null, false);
            $out['db_charset'] = $db->getPlatform() . ' / ' . ($db->charset ?? '');
            foreach (['videos', 'files'] as $table) {
                $cols = [];
                foreach ($db->query('SHOW COLUMNS FROM `' . $db->DBPrefix . $table . '`')->getResultArray() as $c) {
                    $cols[] = $c['Field'] . ' ' . $c['Type'] . ($c['Null'] === 'NO' ? ' NOT NULL' : '') . ($c['Extra'] ? ' ' . $c['Extra'] : '') . ($c['Default'] !== null ? ' default=' . $c['Default'] : '');
                }
                $out['table_' . $table] = $cols;
            }
        } catch (\Throwable $e) {
            $out['db_error'] = $e->getMessage() . ' [' . basename($e->getFile()) . ':' . $e->getLine() . ']';
        }

        return $this->response->setHeader('Content-Type', 'application/json; charset=utf-8')
            ->setBody(json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Get Files
     */
    public function getFiles()
    {
        $files = $this->fileModel->getFiles();
        $this->printFiles($files);
    }

    /**
     * Laod More Files
     */
    public function loadMoreFiles()
    {
        $min = inputPost('min');
        $files = $this->fileModel->getMoreFiles($min);
        $this->printFiles($files);
    }

    /**
     * Search Files
     */
    public function searchFiles()
    {
        $search = inputPost('search');
        $files = $this->fileModel->searchFiles($search);
        $this->printFiles($files);
    }

    /**
     * Print Files
     */
    public function printFiles($files)
    {
        $data = [
            'result' => 0,
            'content' => ''
        ];
        if (!empty($files)) {
            foreach ($files as $file) {
                $data['content'] .= '<div class="col-file-manager" id="file_col_id_' . $file->id . '">';
                $data['content'] .= '<div class="file-box" data-file-id="' . $file->id . '" data-file-name="' . $file->file_name . '">';
                $data['content'] .= '<div class="image-container icon-container">';
                $data['content'] .= '<div class="file-icon file-icon-lg" data-type="' . @pathinfo($file->file_name, PATHINFO_EXTENSION) . '"></div>';
                $data['content'] .= '</div>';
                $data['content'] .= '<span class="file-name">' . esc($file->file_name) . '</span>';
                $data['content'] .= '</div> </div>';
            }
        }
        $data['result'] = 1;
        echo json_encode($data);
    }

    /**
     * Delete File
     */
    public function deleteFile()
    {
        $fileId = inputPost('file_id');
        $this->fileModel->deleteFile($fileId);
    }


    /*
    *------------------------------------------------------------------------------------------
    * VIDEOS
    *------------------------------------------------------------------------------------------
    */

    /**
     * Upload Video
     */
    public function uploadVideo()
    {
        $this->prepareUploadRequest();

        try {
            $ok = $this->fileModel->uploadVideo();
            if (!$ok) {
                return $this->response->setStatusCode(400)->setJSON([
                    'status' => 0,
                    'message' => 'Video upload failed'
                ]);
            }
            return $this->response->setJSON(['status' => 1]);
        } catch (\Throwable $e) {
            log_message('error', 'FileController::uploadVideo: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 0,
                'message' => 'Video upload error'
            ]);
        }
    }

    /**
     * Get Videos
     */
    public function getVideos()
    {
        $videos = $this->fileModel->getVideos();
        $this->printVideos($videos);
    }

    /**
     * Laod More Videos
     */
    public function loadMoreVideos()
    {
        $min = inputPost('min');
        $videos = $this->fileModel->getMoreVideos($min);
        $this->printVideos($videos);
    }

    /**
     * Search Videos
     */
    public function searchVideos()
    {
        $search = inputPost('search');
        $videos = $this->fileModel->searchVideos($search);
        $this->printVideos($videos);
    }

    /**
     * Print Videos
     */
    public function printVideos($videos)
    {
        $data = [
            'result' => 0,
            'content' => ''
        ];
        if (!empty($videos)) {
            foreach ($videos as $video) {
                $videoBaseURL = getBaseURLByStorage($video->storage);
                $data['content'] .= '<div class="col-file-manager" id="video_col_id_' . $video->id . '">';
                $data['content'] .= '<div class="file-box" data-video-id="' . $video->id . '" data-video-path="' . $video->video_path . '" data-video-storage="' . $video->storage . '" data-video-base-url="' . $videoBaseURL . '">';
                $data['content'] .= '<div class="image-container icon-container">';
                $data['content'] .= '<div class="file-icon file-icon-lg" data-type="' . @pathinfo($video->video_name, PATHINFO_EXTENSION) . '"></div>';
                $data['content'] .= '</div>';
                $data['content'] .= '<span class="file-name">' . esc($video->video_name) . '</span>';
                $data['content'] .= '</div> </div>';
            }
        }
        $data['result'] = 1;
        echo json_encode($data);
    }

    /**
     * Delete Video
     */
    public function deleteVideo()
    {
        $videoId = inputPost('video_id');
        $this->fileModel->deleteVideo($videoId);
    }


    /*
    *------------------------------------------------------------------------------------------
    * AUDIOS
    *------------------------------------------------------------------------------------------
    */

    /**
     * Upload Audio
     */
    public function uploadAudio()
    {
        $this->fileModel->uploadAudio();
    }

    /**
     * Get Audios
     */
    public function getAudios()
    {
        $audios = $this->fileModel->getAudios();
        $this->printAudios($audios);
    }

    /**
     * Laod More Audios
     */
    public function loadMoreAudios()
    {
        $min = inputPost('min');
        $audios = $this->fileModel->getMoreAudios($min);
        $this->printAudios($audios);
    }

    /**
     * Search Audios
     */
    public function searchAudios()
    {
        $search = inputPost('search');
        $audios = $this->fileModel->searchAudios($search);
        $this->printAudios($audios);
    }

    /**
     * Print Audios
     */
    public function printAudios($audios)
    {
        $data = [
            'result' => 0,
            'content' => ''
        ];
        if (!empty($audios)) {
            foreach ($audios as $audio) {
                $data['content'] .= '<div class="col-file-manager" id="audio_col_id_' . $audio->id . '">';
                $data['content'] .= '<div class="file-box" data-audio-id="' . $audio->id . '" data-audio-name="' . $audio->audio_name . '">';
                $data['content'] .= '<div class="image-container icon-container">';
                $data['content'] .= '<div class="file-icon file-icon-lg" data-type="' . @pathinfo($audio->audio_path, PATHINFO_EXTENSION) . '"></div>';
                $data['content'] .= '</div>';
                $data['content'] .= '<span class="file-name">' . esc($audio->audio_name) . '</span>';
                $data['content'] .= '</div> </div>';
            }
        }
        $data['result'] = 1;
        echo json_encode($data);
    }

    /**
     * Delete Audio
     */
    public function deleteAudio()
    {
        $audioId = inputPost('audio_id');
        $this->fileModel->deleteAudio($audioId);
    }
}
