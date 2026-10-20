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
        @ignore_user_abort(true);
        @set_time_limit(600);
        @ini_set('max_execution_time', '600');
        @ini_set('max_input_time', '600');
        @ini_set('memory_limit', '512M');
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
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
                return $this->response->setStatusCode(400)->setJSON(['status' => 0, 'message' => 'Chunk save failed']);
            }

            if ($chunkIndex < $totalChunks - 1) {
                return $this->response->setJSON([
                    'status' => 1,
                    'done' => 0,
                    'chunk_index' => $chunkIndex
                ]);
            }

            $ok = $this->fileModel->saveChunkedUpload($uploadType, $uploadId, $fileName, $totalChunks);
            if (!$ok) {
                $this->fileModel->clearChunkedUpload($uploadId);
                return $this->response->setStatusCode(400)->setJSON(['status' => 0, 'message' => 'Finalize failed']);
            }

            return $this->response->setJSON(['status' => 1, 'done' => 1]);
        } catch (\Throwable $e) {
            log_message('error', 'FileController::uploadChunk: ' . $e->getMessage());
            $this->fileModel->clearChunkedUpload($uploadId);
            return $this->response->setStatusCode(500)->setJSON(['status' => 0, 'message' => 'Chunk upload error']);
        }
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
