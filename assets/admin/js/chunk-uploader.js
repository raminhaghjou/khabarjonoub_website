/**
 * Robust sequential chunked uploader for admin file/video manager.
 * - Larger chunks = fewer round-trips (helps LiteSpeed / VPN stalls)
 * - Safe JSON parsing so HTML/empty responses never freeze the queue
 * - Shorter per-request timeout + visible retries
 */
(function (window, $) {
    'use strict';

    if (!$ || window.VrChunkUploader) {
        return;
    }

    var DEFAULTS = {
        chunkSize: 512 * 1024, // stays under common 1MB/2MB PHP upload limits
        retries: 5,
        timeout: 90000,             // 90s per chunk
        uploadUrl: null,
        uploadType: 'file',
        csrfName: null,
        invalidTypeText: 'Invalid file type',
        acceptExt: null,            // null = any, or ['mp4','webm']
        listSelector: null,
        resetBtnSelector: null,
        onComplete: null,           // function(elId)
        onError: null               // function(elId)
    };

    function csrfHash(fallback) {
        if (typeof window.getCsrfHash === 'function') {
            var fromHelper = window.getCsrfHash();
            if (fromHelper) {
                return fromHelper;
            }
        }
        var names = [];
        if (window.VrConfig && VrConfig.csrfCookieName) {
            names.push(VrConfig.csrfCookieName, 'vr' + VrConfig.csrfCookieName);
        }
        names.push('app_csrf_cookie', 'vrapp_csrf_cookie');
        var raw = document.cookie || '';
        var parts = raw.split(';');
        for (var n = 0; n < names.length; n++) {
            var prefix = names[n] + '=';
            for (var i = 0; i < parts.length; i++) {
                var c = parts[i].replace(/^\s+/, '');
                if (c.indexOf(prefix) === 0 && c.length > prefix.length) {
                    try {
                        return decodeURIComponent(c.slice(prefix.length));
                    } catch (e) {
                        return c.slice(prefix.length);
                    }
                }
            }
        }
        return fallback || '';
    }

    function parseResponse(res) {
        if (res == null || res === '') {
            return null;
        }
        if (typeof res === 'object') {
            return res;
        }
        try {
            return JSON.parse(res);
        } catch (e) {
            return null;
        }
    }

    function makeId(prefix) {
        return (prefix || 'u') + Date.now().toString(36) + Math.random().toString(36).slice(2, 8);
    }

    function createUploader(userOpts) {
        var opts = $.extend({}, DEFAULTS, userOpts || {});
        var queue = [];
        var uploading = false;
        var seq = 0;
        var activeXhr = null;

        function showReset(show) {
            if (!opts.resetBtnSelector) {
                return;
            }
            if (show) {
                $(opts.resetBtnSelector).show();
            } else if (!queue.length) {
                $(opts.resetBtnSelector).hide();
            }
        }

        function sendChunk(item, idx, attempt) {
            var start = idx * opts.chunkSize;
            var end = Math.min(start + opts.chunkSize, item.file.size);
            var fd = new FormData();
            fd.append('file', item.file.slice(start, end), item.file.name);
            fd.append('upload_type', opts.uploadType);
            fd.append('upload_id', item.uploadId);
            fd.append('chunk_index', idx);
            fd.append('total_chunks', item.total);
            fd.append('file_name', item.file.name);
            if (opts.csrfName) {
                fd.append(opts.csrfName, csrfHash(opts.csrfHash));
            }

            activeXhr = $.ajax({
                url: opts.uploadUrl,
                type: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                timeout: opts.timeout,
                dataType: 'text',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            activeXhr.done(function (res) {
                var d = parseResponse(res);
                if (!d || Number(d.status) !== 1) {
                    onChunkFail(item, idx, attempt, (d && d.message) ? d.message : 'bad response');
                    return;
                }
                var pct = Math.round(((idx + 1) / item.total) * 100);
                ui_multi_update_file_progress(item.elId, Math.min(99, pct));
                if (attempt > 0) {
                    ui_multi_update_file_status(item.elId, 'uploading', 'Uploading...');
                }
                if (idx + 1 < item.total) {
                    sendChunk(item, idx + 1, 0);
                } else {
                    onDone(item.elId);
                }
            }).fail(function (xhr, status) {
                var body = parseResponse(xhr && xhr.responseText);
                var reason = (body && body.message) ? body.message : (status || 'error');
                if ((!body || !body.message) && xhr && xhr.status) {
                    reason += ' (' + xhr.status + ')';
                }
                var retryable = !xhr || !xhr.status || xhr.status >= 500 || status === 'timeout';
                if (!retryable) {
                    console.error('chunk upload failed', reason);
                    onError(item.elId, reason);
                    return;
                }
                onChunkFail(item, idx, attempt, reason);
            }).always(function () {
                activeXhr = null;
            });
        }

        function onChunkFail(item, idx, attempt, reason) {
            if (attempt + 1 < opts.retries) {
                var next = attempt + 1;
                ui_multi_update_file_status(
                    item.elId,
                    'uploading',
                    'Retry ' + next + '/' + (opts.retries - 1) + '...'
                );
                setTimeout(function () {
                    sendChunk(item, idx, next);
                }, Math.min(8000, 1000 * next));
                return;
            }
            console.error('chunk upload failed', reason);
            onError(item.elId, reason);
        }

        function onDone(elId) {
            if (typeof opts.onComplete === 'function') {
                opts.onComplete(elId);
            }
            var el = document.getElementById('uploaderFile' + elId);
            if (el) {
                el.remove();
            }
            uploading = false;
            showReset(false);
            processQueue();
        }

        function onError(elId, reason) {
            ui_multi_update_file_progress(elId, 100, 'danger', false);
            ui_multi_update_file_status(elId, 'danger', 'Error');
            if (typeof opts.onError === 'function') {
                opts.onError(elId);
            }
            showReset(true);
            uploading = false;
            processQueue();
            if (window.swal) {
                swal({
                    text: reason || 'آپلود ناموفق بود. اتصال را بررسی کنید و دوباره امتحان کنید.',
                    icon: 'error',
                    button: (window.VrConfig && VrConfig.textOk) ? VrConfig.textOk : 'OK'
                });
            }
        }

        function processQueue() {
            if (uploading || !queue.length) {
                return;
            }
            uploading = true;
            var fileItem = queue.shift();
            var total = Math.max(1, Math.ceil(fileItem.file.size / opts.chunkSize));
            var item = {
                file: fileItem.file,
                elId: fileItem.id,
                uploadId: makeId(opts.uploadType === 'video' ? 'v' : 'f'),
                total: total
            };

            $('#uploaderFile' + fileItem.id + ' .dm-progress-waiting').hide();
            ui_multi_update_file_progress(fileItem.id, 0, '', true);
            ui_multi_update_file_status(fileItem.id, 'uploading', 'Uploading...');
            showReset(true);
            sendChunk(item, 0, 0);
        }

        function enqueue(fileList) {
            $.each(fileList, function (_, file) {
                if (opts.acceptExt && opts.acceptExt.length) {
                    var ext = (file.name || '').split('.').pop().toLowerCase();
                    if (opts.acceptExt.indexOf(ext) === -1) {
                        if (window.swal) {
                            swal({
                                text: opts.invalidTypeText,
                                icon: 'warning',
                                button: (window.VrConfig && VrConfig.textOk) ? VrConfig.textOk : 'OK'
                            });
                        }
                        return;
                    }
                }
                seq++;
                ui_multi_add_file(seq, file, opts.uploadType === 'video' ? 'video' : 'file');
                queue.push({ id: seq, file: file });
            });
            processQueue();
        }

        function reset() {
            if (activeXhr && typeof activeXhr.abort === 'function') {
                try { activeXhr.abort(); } catch (e) {}
            }
            queue = [];
            uploading = false;
            activeXhr = null;
            if (opts.listSelector) {
                $(opts.listSelector).empty();
            }
            showReset(false);
        }

        return {
            enqueue: enqueue,
            reset: reset,
            bindZone: function (zoneSelector, inputSelector) {
                var $zone = $(zoneSelector);
                $zone.on('dragenter dragover', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $zone.addClass('active');
                }).on('dragleave drop', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $zone.removeClass('active');
                }).on('drop', function (e) {
                    var dt = e.originalEvent && e.originalEvent.dataTransfer;
                    if (dt && dt.files && dt.files.length) {
                        enqueue(dt.files);
                    }
                });
                $(inputSelector).on('change', function () {
                    if (this.files && this.files.length) {
                        enqueue(this.files);
                        this.value = '';
                    }
                });
                if (opts.resetBtnSelector) {
                    $(document).on('click', opts.resetBtnSelector, function () {
                        reset();
                    });
                }
            }
        };
    }

    window.VrChunkUploader = createUploader;
}(window, window.jQuery));
