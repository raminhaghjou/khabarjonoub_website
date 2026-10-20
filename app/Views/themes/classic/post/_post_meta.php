<?php if ($generalSettings->show_post_author == 1): ?>
    <a href="<?= generateProfileURL($post->author_slug); ?>"><?= esc($post->author_username); ?></a>
<?php endif; 
use App\Libraries\jdf;
if ($generalSettings->show_post_date == 1): ?>
    <span><?php $jdf = new \App\Libraries\jdf(); echo $jdf->jdate("تاریخ: Y/m/d - H:i", strtotime($post->created_at));  ?></span>

<?php endif;
if ($generalSettings->show_hits): ?>
    <span class="m-r-0"><i class="icon-eye"></i><?= isset($post->pageviews_count) ? $post->pageviews_count : $post->pageviews; ?></span>
<?php endif; ?>