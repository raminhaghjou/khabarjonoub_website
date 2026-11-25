<?php namespace App\Models;

/**
 * Scheduled content versions for custom widgets.
 *
 * Every row is one "version" of a widget content with a publish date/time.
 * On the front-end the newest version whose publish_at <= now is displayed;
 * when no version is due yet, the widget's own content (widgets.content) is displayed.
 *
 * Dates are stored in the site timezone (Settings > General > Timezone) as DATETIME.
 */
class WidgetScheduleModel extends BaseModel
{
    public $lastError = '';

    public static $jalaliMonths = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Create the table when it does not exist yet (safe to call many times).
     */
    public function ensureTable()
    {
        $table = $this->db->DBPrefix . 'widget_schedules';
        $this->db->query('CREATE TABLE IF NOT EXISTS `' . $table . '` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `widget_id` INT NOT NULL,
            `content` MEDIUMTEXT NULL,
            `publish_at` DATETIME NOT NULL,
            `created_at` DATETIME NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_widget_publish` (`widget_id`, `publish_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3');
    }

    //get all versions of a widget (newest publish time first)
    public function getSchedules($widgetId)
    {
        return $this->db->table('widget_schedules')
            ->where('widget_id', (int) $widgetId)
            ->orderBy('publish_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->get()->getResult();
    }

    //get a version
    public function getSchedule($id)
    {
        return $this->db->table('widget_schedules')->where('id', (int) $id)->get()->getRow();
    }

    //id of the version that is displayed right now (null = the widget's own content)
    public function getActiveScheduleId($schedules, $now = null)
    {
        $now = $now ?: date('Y-m-d H:i:s');
        $active = null;
        if (!empty($schedules)) {
            foreach ($schedules as $item) {
                if ($item->publish_at <= $now) {
                    if ($active === null || $item->publish_at > $active->publish_at || ($item->publish_at == $active->publish_at && $item->id > $active->id)) {
                        $active = $item;
                    }
                }
            }
        }
        return $active ? (int) $active->id : null;
    }

    //does the html contain something to display
    public function hasContent($content)
    {
        $content = (string) $content;
        if (trim(strip_tags($content, '<img><iframe><video><audio><picture><embed><object>')) === '') {
            return false;
        }
        return true;
    }

    //utf8 (3-byte) database: strip 4-byte characters (emoji)
    public function sanitizeContent($content)
    {
        $content = (string) $content;
        $clean = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $content);
        return $clean === null ? $content : $clean;
    }

    /**
     * Insert or update a version. Returns the id or false (see $this->lastError).
     */
    public function saveSchedule($widgetId, $scheduleId, $content, $publishAt)
    {
        $this->lastError = '';
        $widgetId = (int) $widgetId;
        $scheduleId = (int) $scheduleId;
        $data = [
            'widget_id' => $widgetId,
            'content' => $this->sanitizeContent($content),
            'publish_at' => $publishAt,
        ];
        try {
            if ($scheduleId > 0) {
                $existing = $this->getSchedule($scheduleId);
                if (empty($existing) || (int) $existing->widget_id !== $widgetId) {
                    $this->lastError = 'نسخهٔ مورد نظر پیدا نشد.';
                    return false;
                }
                $this->db->table('widget_schedules')->where('id', $scheduleId)->update($data);
                return $scheduleId;
            }
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->db->table('widget_schedules')->insert($data);
            return (int) $this->db->insertID();
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            log_message('error', 'WidgetScheduleModel::saveSchedule: ' . $e->getMessage());
            return false;
        }
    }

    //delete a version
    public function deleteSchedule($id, $widgetId)
    {
        return $this->db->table('widget_schedules')->where('id', (int) $id)->where('widget_id', (int) $widgetId)->delete();
    }

    //delete all versions of a widget
    public function deleteByWidget($widgetId)
    {
        return $this->db->table('widget_schedules')->where('widget_id', (int) $widgetId)->delete();
    }

    /**
     * Rows needed by the front-end: for every widget the newest version that is already due
     * plus all versions that are still in the future. This keeps the cached data small while
     * the correct version can still be picked at any later moment.
     *
     * @return array<int, array<int, array{id:int, publish_at:string, content:string}>>
     */
    public function getFrontRows($now)
    {
        $table = $this->db->prefixTable('widget_schedules');
        $future = $this->db->query('SELECT id, widget_id, publish_at, content FROM `' . $table . '` WHERE publish_at > ?', [$now])->getResultArray();
        $current = $this->db->query('SELECT s.id, s.widget_id, s.publish_at, s.content FROM `' . $table . '` s
            INNER JOIN (SELECT widget_id, MAX(publish_at) AS mp FROM `' . $table . '` WHERE publish_at <= ? GROUP BY widget_id) m
            ON m.widget_id = s.widget_id AND m.mp = s.publish_at', [$now])->getResultArray();
        $rows = [];
        foreach (array_merge($current, $future) as $r) {
            $rows[(int) $r['widget_id']][] = [
                'id' => (int) $r['id'],
                'publish_at' => (string) $r['publish_at'],
                'content' => (string) $r['content'],
            ];
        }
        return $rows;
    }

    /*
    *------------------------------------------------------------------------------------------
    * JALALI (SHAMSI) HELPERS
    *------------------------------------------------------------------------------------------
    */

    //jalali date/time input -> "Y-m-d H:i:00" (site timezone) or null when invalid
    public function jalaliToDateTime($jy, $jm, $jd, $hour, $minute)
    {
        $this->lastError = '';
        $jdf = new \App\Libraries\jdf();
        $jy = (int) $jdf->tr_num((string) $jy);
        $jm = (int) $jdf->tr_num((string) $jm);
        $jd = (int) $jdf->tr_num((string) $jd);
        $hour = (int) $jdf->tr_num((string) $hour);
        $minute = (int) $jdf->tr_num((string) $minute);
        if ($jy < 1300 || $jy > 1700 || $jm < 1 || $jm > 12 || $jd < 1 || $jd > 31 || $hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
            $this->lastError = 'تاریخ یا ساعت انتخاب‌شده معتبر نیست.';
            return null;
        }
        if (!$jdf->jcheckdate($jm, $jd, $jy)) {
            $this->lastError = 'این روز در ماه انتخاب‌شده وجود ندارد.';
            return null;
        }
        $g = $jdf->jalali_to_gregorian($jy, $jm, $jd);
        if (!is_array($g) || count($g) != 3 || !checkdate((int) $g[1], (int) $g[2], (int) $g[0])) {
            $this->lastError = 'تبدیل تاریخ شمسی به میلادی انجام نشد.';
            return null;
        }
        //round trip check: protects against any conversion edge case
        $back = $jdf->gregorian_to_jalali($g[0], $g[1], $g[2]);
        if (!is_array($back) || (int) $back[0] !== $jy || (int) $back[1] !== $jm || (int) $back[2] !== $jd) {
            $this->lastError = 'تاریخ شمسی انتخاب‌شده معتبر نیست.';
            return null;
        }
        return sprintf('%04d-%02d-%02d %02d:%02d:00', $g[0], $g[1], $g[2], $hour, $minute);
    }

    //"Y-m-d H:i:s" -> [jy, jm, jd, hour, minute]
    public function dateTimeToJalali($dateTime)
    {
        $ts = strtotime((string) $dateTime);
        if ($ts === false) {
            $ts = time();
        }
        $jdf = new \App\Libraries\jdf();
        $j = $jdf->gregorian_to_jalali((int) date('Y', $ts), (int) date('n', $ts), (int) date('j', $ts));
        return [(int) $j[0], (int) $j[1], (int) $j[2], (int) date('G', $ts), (int) date('i', $ts)];
    }

    //human readable jalali date, e.g. "۴ آذر ۱۴۰۵ - ساعت ۰۲:۰۰"
    public function formatJalali($dateTime)
    {
        $j = $this->dateTimeToJalali($dateTime);
        $jdf = new \App\Libraries\jdf();
        $text = $j[2] . ' ' . self::$jalaliMonths[$j[1] - 1] . ' ' . $j[0] . ' - ساعت ' . sprintf('%02d:%02d', $j[3], $j[4]);
        return $jdf->tr_num($text, 'fa');
    }

    /**
     * Values for the admin form. $schedule = version being edited (or null for a new one).
     * New versions default to tomorrow 02:00.
     */
    public function getFormValues($schedule = null)
    {
        if (!empty($schedule)) {
            $j = $this->dateTimeToJalali($schedule->publish_at);
            $content = $schedule->content;
            $id = (int) $schedule->id;
        } else {
            $j = $this->dateTimeToJalali(date('Y-m-d 02:00:00', strtotime('+1 day')));
            $content = '';
            $id = 0;
        }
        $currentYear = $this->dateTimeToJalali(date('Y-m-d H:i:s'))[0];
        $years = [];
        for ($y = min($currentYear - 1, $j[0]); $y <= max($currentYear + 3, $j[0]); $y++) {
            $years[] = $y;
        }
        return [
            'id' => $id, 'jy' => $j[0], 'jm' => $j[1], 'jd' => $j[2], 'hour' => $j[3], 'minute' => $j[4],
            'content' => $content, 'years' => $years, 'months' => self::$jalaliMonths,
        ];
    }
}
