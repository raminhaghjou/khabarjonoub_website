<div id="file_manager" class="modal fade modal-file-manager" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><?= trans('files'); ?></h4>
                <div class="file-manager-search">
                    <input type="text" id="input_search_file" class="form-control" placeholder="<?= trans("search"); ?>">
                </div>
            </div>
            <div class="modal-body">
                <div class="file-manager">
                    <div class="file-manager-left">
                        <div class="dm-uploader-container">
                            <div id="drag-and-drop-zone" class="dm-uploader text-center">
                                <?php if (!empty($generalSettings->allowed_file_extensions)):
                                    $exts = null;
                                    if (!empty($generalSettings->allowed_file_extensions)) {
                                        $exts = explode(',', $generalSettings->allowed_file_extensions);
                                    }
                                    if (!empty($exts) && countItems($exts) > 0): ?>
                                        <p class="file-manager-file-types">
                                            <?php foreach ($exts as $ext):
                                                $ext = strReplace('"', '', $ext);
                                                if (!empty($ext)) {
                                                    $ext = strtoupper($ext);
                                                } ?>
                                                <span><?= esc($ext); ?></span>
                                            <?php endforeach; ?>
                                        </p>
                                    <?php endif;
                                endif; ?>
                                <p class="dm-upload-icon">
                                    <i class="fa fa-cloud-upload"></i>
                                </p>
                                <p class="dm-upload-text"><?= trans("drag_drop_files_here"); ?></p>
                                <p class="text-center">
                                    <button class="btn btn-default btn-browse-files"><?= trans('browse_files'); ?></button>
                                </p>
                                <a class='btn btn-md dm-btn-select-files'>
                                    <input type="file" name="file" size="40" multiple="multiple">
                                </a>
                                <ul class="dm-uploaded-files" id="files-file"></ul>
                                <button type="button" id="btn_reset_upload" class="btn btn-reset-upload"><?= trans("reset"); ?></button>
                            </div>
                        </div>
                    </div>
                    <div class="file-manager-right">
                        <div class="file-manager-content">
                            <div class="col-sm-12">
                                <div class="row">
                                    <div id="file_upload_response">
                                        <?php foreach ($files as $file):
                                            if (!empty($file)): ?>
                                                <div class="col-file-manager" id="file_col_id_<?= $file->id; ?>">
                                                    <div class="file-box" data-file-id="<?= $file->id; ?>" data-file-name="<?= esc($file->file_name); ?>">
                                                        <div class="image-container icon-container">
                                                            <div class="file-icon file-icon-lg" data-type="<?= @pathinfo($file->file_path, PATHINFO_EXTENSION); ?>"></div>
                                                        </div>
                                                        <span class="file-name"><?= esc($file->file_name); ?></span>
                                                    </div>
                                                </div>
                                            <?php endif;
                                        endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="selected_file_id">
                    <input type="hidden" id="selected_file_name">
                    <input type="hidden" id="selected_file_path">
                </div>
            </div>
            <div class="modal-footer">
                <div class="file-manager-footer">
                    <button type="button" id="btn_file_delete" class="btn btn-danger pull-left btn-file-delete"><i class="fa fa-trash"></i>&nbsp;&nbsp;<?= trans('delete'); ?></button>
                    <button type="button" id="btn_file_select" class="btn bg-olive btn-file-select"><i class="fa fa-check"></i>&nbsp;&nbsp;<?= trans('select_file'); ?></button>
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?= trans('close'); ?></button>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/html" id="files-template-file">
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

<?php
$fileExtensions = [];
$extArray = [];
if (!empty($generalSettings->allowed_file_extensions)) {
    $extArray = explode(',', $generalSettings->allowed_file_extensions);
}
foreach ($extArray as $item) {
    $item = trim(str_replace(['"', "'"], '', $item));
    if ($item !== '') {
        $fileExtensions[] = strtolower($item);
    }
}
$fileExtensionsJson = json_encode($fileExtensions);
?>

<script>
(function ($) {
    $(function () {
        if (typeof VrChunkUploader !== 'function') {
            console.error('VrChunkUploader missing');
            return;
        }
        var uploader = VrChunkUploader({
            uploadUrl: '<?= base_url("FileController/uploadChunk") ?>',
            uploadType: 'file',
            csrfName: '<?= csrf_token() ?>',
            csrfHash: '<?= csrf_hash() ?>',
            invalidTypeText: '<?= clrQuotes(trans("invalid_file_type")) ?>',
            acceptExt: <?= $fileExtensionsJson ?>,
            listSelector: '#files-file',
            resetBtnSelector: '#btn_reset_upload',
            onComplete: function () { refresh_files(); }
        });
        uploader.bindZone('#drag-and-drop-zone', '#drag-and-drop-zone input[type=file]');
    });
}(jQuery));
</script>
