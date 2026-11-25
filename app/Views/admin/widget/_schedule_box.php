<?php
/**
 * Scheduled content versions of a custom widget.
 * Variables: $widget, $schedules, $activeScheduleId, $scheduleForm, $scheduleNowText, $scheduleModel
 */
$f = $scheduleForm;
$isEditing = !empty($f['id']);
?>
<div class="row" id="widget_schedule_box">
    <div class="col-lg-8 col-md-12">
        <div class="box box-info">
            <div class="box-header with-border">
                <div class="left">
                    <h3 class="box-title">زمان‌بندی محتوا (تقویم شمسی)</h3>
                </div>
            </div>
            <div class="box-body">
                <div class="alert alert-info" style="margin-bottom: 15px;">
                    <p style="margin-bottom: 6px;">
                        برای هر زمان انتشار یک «نسخه» از محتوای این ابزارک (عکس، لینک، متن) تعریف کنید. در همان تاریخ و ساعت، نسخهٔ جدید به‌صورت خودکار جایگزین نسخهٔ قبلی می‌شود و تا زمان انتشار نسخهٔ بعدی نمایش داده می‌شود.
                    </p>
                    <p style="margin-bottom: 6px;">تا وقتی زمان هیچ نسخه‌ای نرسیده باشد، «محتوای اصلی ابزارک» (ویرایشگر بالای همین صفحه) نمایش داده می‌شود.</p>
                    <p style="margin-bottom: 0;"><strong>ساعت فعلی سایت:</strong> <span dir="rtl"><?= esc($scheduleNowText); ?></span></p>
                </div>

                <h4 style="margin-top: 0;">نسخه‌ها</h4>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th width="40">#</th>
                            <th>زمان انتشار (شمسی)</th>
                            <th>میلادی</th>
                            <th>وضعیت</th>
                            <th>پیش‌نمایش</th>
                            <th style="width: 150px;"><?= trans('options'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($schedules)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">هنوز نسخه‌ای تعریف نشده است. محتوای اصلی ابزارک نمایش داده می‌شود.</td>
                            </tr>
                        <?php else:
                            $nowText = date('Y-m-d H:i:s');
                            foreach ($schedules as $item):
                                $excerpt = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $item->content)));
                                $excerpt = mb_strlen($excerpt, 'UTF-8') > 70 ? mb_substr($excerpt, 0, 70, 'UTF-8') . '...' : $excerpt;
                                $thumb = '';
                                if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', (string) $item->content, $m)) {
                                    $thumb = $m[1];
                                } ?>
                                <tr<?= $isEditing && $f['id'] == $item->id ? ' class="info"' : ''; ?>>
                                    <td><?= (int) $item->id; ?></td>
                                    <td dir="rtl"><?= esc($scheduleModel->formatJalali($item->publish_at)); ?></td>
                                    <td><small><?= esc(substr($item->publish_at, 0, 16)); ?></small></td>
                                    <td>
                                        <?php if ($activeScheduleId !== null && (int) $item->id === (int) $activeScheduleId): ?>
                                            <span class="label label-success">در حال نمایش</span>
                                        <?php elseif ($item->publish_at > $nowText): ?>
                                            <span class="label label-warning">در انتظار انتشار</span>
                                        <?php else: ?>
                                            <span class="label label-default">قدیمی</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($thumb !== ''): ?>
                                            <img src="<?= esc($thumb); ?>" alt="" style="max-width: 60px; max-height: 60px; margin-bottom: 4px; display: block;">
                                        <?php endif; ?>
                                        <small class="text-muted"><?= esc($excerpt); ?></small>
                                    </td>
                                    <td>
                                        <a href="<?= adminUrl('edit-widget/' . $widget->id . '?schedule=' . $item->id); ?>#widget_schedule_box" class="btn btn-sm btn-primary"><i class="fa fa-edit"></i> <?= trans('edit'); ?></a>
                                        <form action="<?= base_url('AdminController/deleteWidgetSchedulePost'); ?>" method="post" style="display: inline-block;" onsubmit="return confirm('این نسخه حذف شود؟');">
                                            <?= csrf_field(); ?>
                                            <input type="hidden" name="widget_id" value="<?= (int) $widget->id; ?>">
                                            <input type="hidden" name="schedule_id" value="<?= (int) $item->id; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger"><i class="fa fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach;
                        endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (!empty($schedules) && $activeScheduleId === null): ?>
                    <p class="text-muted"><small>در حال حاضر زمان هیچ نسخه‌ای نرسیده است؛ محتوای اصلی ابزارک نمایش داده می‌شود.</small></p>
                <?php endif; ?>

                <hr>
                <h4><?= $isEditing ? 'ویرایش نسخهٔ شمارهٔ ' . (int) $f['id'] : 'افزودن نسخهٔ جدید'; ?></h4>
                <form action="<?= base_url('AdminController/saveWidgetSchedulePost'); ?>" method="post" id="widget_schedule_form">
                    <?= csrf_field(); ?>
                    <input type="hidden" name="widget_id" value="<?= (int) $widget->id; ?>">
                    <input type="hidden" name="schedule_id" value="<?= (int) $f['id']; ?>">

                    <div class="form-group">
                        <label class="control-label">تاریخ و ساعت انتشار (شمسی)</label>
                        <div class="row" dir="rtl">
                            <div class="col-xs-6 col-sm-2" style="margin-bottom: 8px;">
                                <select name="jy" id="ws_jy" class="form-control">
                                    <?php foreach ($f['years'] as $y): ?>
                                        <option value="<?= $y; ?>" <?= $y == $f['jy'] ? 'selected' : ''; ?>><?= $y; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-xs-6 col-sm-3" style="margin-bottom: 8px;">
                                <select name="jm" id="ws_jm" class="form-control">
                                    <?php foreach ($f['months'] as $i => $monthName): ?>
                                        <option value="<?= $i + 1; ?>" <?= ($i + 1) == $f['jm'] ? 'selected' : ''; ?>><?= $monthName; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-xs-4 col-sm-2" style="margin-bottom: 8px;">
                                <select name="jd" id="ws_jd" class="form-control">
                                    <?php for ($d = 1; $d <= 31; $d++): ?>
                                        <option value="<?= $d; ?>" <?= $d == $f['jd'] ? 'selected' : ''; ?>><?= $d; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-xs-4 col-sm-2" style="margin-bottom: 8px;">
                                <select name="jh" id="ws_jh" class="form-control">
                                    <?php for ($h = 0; $h < 24; $h++): ?>
                                        <option value="<?= $h; ?>" <?= $h == $f['hour'] ? 'selected' : ''; ?>><?= sprintf('%02d', $h); ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-xs-4 col-sm-2" style="margin-bottom: 8px;">
                                <select name="ji" id="ws_ji" class="form-control">
                                    <?php for ($i = 0; $i < 60; $i++): ?>
                                        <option value="<?= $i; ?>" <?= $i == $f['minute'] ? 'selected' : ''; ?>><?= sprintf('%02d', $i); ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        <small class="text-muted">به ترتیب: سال، ماه، روز، ساعت، دقیقه (به وقت سایت). اگر زمان گذشته انتخاب شود، نسخه بلافاصله نمایش داده می‌شود.</small>
                    </div>

                    <div class="form-group">
                        <label>محتوای این نسخه</label>
                        <div class="row">
                            <div class="col-sm-12 editor-buttons">
                                <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#file_manager_image" data-image-type="list_item_editor" data-editor-id="sch"><i class="fa fa-image"></i>&nbsp;&nbsp;&nbsp;<?= trans("add_image"); ?></button>
                            </div>
                        </div>
                        <textarea id="editor_sch" class="tinyMCEsmall form-control" name="content"><?= esc($f['content']); ?></textarea>
                    </div>

                    <div>
                        <button type="submit" class="btn btn-primary pull-right"><?= $isEditing ? 'ذخیرهٔ تغییرات نسخه' : 'افزودن نسخه'; ?></button>
                        <?php if ($isEditing): ?>
                            <a href="<?= adminUrl('edit-widget/' . $widget->id); ?>#widget_schedule_box" class="btn btn-default pull-right" style="margin-right: 8px;">انصراف</a>
                        <?php endif; ?>
                        <div class="clearfix"></div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    //limit the day list to the number of days of the selected jalali month
    function wsUpdateDays() {
        var month = parseInt($('#ws_jm').val(), 10);
        var max = month <= 6 ? 31 : 30;
        var $day = $('#ws_jd');
        $day.find('option').each(function () {
            var disabled = parseInt(this.value, 10) > max;
            $(this).prop('disabled', disabled).toggle(!disabled);
        });
        if (parseInt($day.val(), 10) > max) {
            $day.val(max);
        }
    }

    $(function () {
        $('#ws_jm').on('change', wsUpdateDays);
        wsUpdateDays();
    });
</script>
