<div id="file_manager_video" class="modal fade modal-file-manager" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><?= trans('videos'); ?></h4>
                <div class="file-manager-search">
                    <input type="text" id="input_search_video" class="form-control" placeholder="<?= trans("search"); ?>">
                </div>
            </div>
            <div class="modal-body">
                <div class="file-manager">
                    <div class="file-manager-left">
                        <div class="dm-uploader-container">
                            <div id="drag-and-drop-zone-video" class="dm-uploader text-center">
                                <p class="file-manager-file-types">
                                    <span>MP4</span>
                                    <span>WEBM</span>
                                </p>
                                <p class="dm-upload-icon"><i class="fa fa-cloud-upload"></i></p>
                                <p class="dm-upload-text"><?= trans("drag_drop_files_here"); ?></p>
                                <p class="text-center">
                                    <button type="button" class="btn btn-default btn-browse-files"><?= trans('browse_files'); ?></button>
                                </p>
                                <a class='btn btn-md dm-btn-select-files'>
                                    <input type="file" id="video_file_input" name="file" size="40" accept="video/mp4,video/webm,.mp4,.webm">
                                </a>
                                <ul class="dm-uploaded-files" id="files-video"></ul>
                                <button type="button" id="btn_reset_upload_video" class="btn btn-reset-upload" style="display:none;"><?= trans("reset"); ?></button>
                            </div>
                        </div>
                    </div>
                    <div class="file-manager-right">
                        <div class="file-manager-content">
                            <div class="col-sm-12">
                                <div class="row">
                                    <div id="video_upload_response"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="selected_video_id">
                    <input type="hidden" id="selected_video_path">
                    <input type="hidden" id="selected_video_storage">
                    <input type="hidden" id="selected_video_base_url">
                </div>
            </div>
            <div class="modal-footer">
                <div class="file-manager-footer">
                    <button type="button" id="btn_video_delete" class="btn btn-danger pull-left btn-file-delete"><i class="fa fa-trash"></i>&nbsp;&nbsp;<?= trans('delete'); ?></button>
                    <button type="button" id="btn_video_select" class="btn bg-olive btn-file-select"><i class="fa fa-check"></i>&nbsp;&nbsp;<?= trans('select_video'); ?></button>
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?= trans('close'); ?></button>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/html" id="files-template-video">
    <li class="media">
        <img class="preview-img" src="<?= base_url('assets/admin/plugins/file-manager/file.png'); ?>" alt="">
        <div class="media-body">
            <div class="progress">
                <div class="dm-progress-waiting"><?= trans("waiting"); ?></div>
                <div class="progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>
    </li>
</script>

<script>
(function ($) {
    $(function () {
        if (typeof VrChunkUploader !== 'function') {
            console.error('VrChunkUploader missing');
            return;
        }
        var uploader = VrChunkUploader({
            uploadUrl: '<?= base_url("FileController/uploadChunk") ?>',
            uploadType: 'video',
            csrfName: '<?= csrf_token() ?>',
            csrfHash: '<?= csrf_hash() ?>',
            invalidTypeText: '<?= clrQuotes(trans("invalid_file_type")) ?>',
            acceptExt: ['mp4', 'webm'],
            listSelector: '#files-video',
            resetBtnSelector: '#btn_reset_upload_video',
            onComplete: function () { refresh_videos(); }
        });
        uploader.bindZone('#drag-and-drop-zone-video', '#video_file_input');
    });
}(jQuery));
</script>
