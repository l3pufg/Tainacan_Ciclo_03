<?php
include_once ('../../../../../wp-config.php');
include_once ('../../../../../wp-load.php');
include_once ('../../../../../wp-includes/wp-db.php');
include_once ('js/recents_js.php');
?>


<?php if (isset($recents) && !empty($recents)): ?>
    <?php foreach ($recents as $key => $recent): ?> 
        <div class="col-md-4">
            <div class="panel panel-default">
                <div class="panel-body" style="padding:3px; width: 150px; height: 150px;">
                    <a href="<?php echo get_the_permalink($recent['collection']->ID); ?>">
                        <?php
                        $url_image = wp_get_attachment_url(get_post_thumbnail_id($recent['collection']->ID));
                        if (get_the_post_thumbnail($recent['collection']->ID, 'thumbnail') && $url_image) {
                            //echo get_the_post_thumbnail($collection_post->ID, $thumbSize);
                            ?><img class="img-responsive" src="<?php echo $url_image; ?>" style="max-height: 150px; max-width: 150px;" /><?php
                        } else {
                            $rand = rand(1,20);
                            ?>
                            <img src="<?php echo get_template_directory_uri() ?>/libraries/images/collection_thumbs/colecao_thumb<?php echo $rand; ?>.jpg" class="img-responsive">
                        <?php } ?>
                    </a>
                </div>
                <div class="panel-footer" style="padding:3px;">
                    <a href="<?php echo get_the_permalink($recent['collection']->ID); ?>"><span><small><?php echo Words($recent['collection']->post_title, 20); ?></small></span></a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
