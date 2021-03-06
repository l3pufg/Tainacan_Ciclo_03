<?php
/*
 * 
 * View responsavel em mostrar um objeto especifico
 * 
 * 
 */

include_once ('../../../../../wp-config.php');
include_once ('../../../../../wp-load.php');
include_once ('../../../../../wp-includes/wp-db.php');
include_once ('js/list_single_js.php');
?>  
<input type="hidden" name="single_object_id" id="single_object_id" value="<?php echo $object->ID; ?>" >
<div class="container-fluid">

    <ol class="breadcrumb">
        <button class="btn bt-defaul content-back" onclick="backToMainPageSingleItem()"><span class="glyphicon glyphicon-arrow-left"></span></button>
        <li><a href="<?php echo get_permalink(get_option('collection_root_id')); ?>">Repositorio</a></li>
        <li><a href="#" onclick="backToMainPageSingleItem()"><?php echo get_post($collection_id)->post_title; ?></a></li>
        <li class="active"><?php echo $object->post_title; ?></li>
        <input type="hidden" id="socialdb_permalink_object" name="socialdb_permalink_object" value="<?php echo get_the_permalink($collection_id) . '?item=' . $object->post_name; ?>" />
        <button class="btn bt-defaul content-back pull-right" id="iframebuttonObject" data-container="body" data-toggle="popoverObject" data-placement="left" data-title="Item URL" data-content="">
            <span class="glyphicon glyphicon-link"></span>
        </button>
    </ol>
    <hr class="no-margin">
    <div class="col-md-12 content-title">
        <h2><?php echo $object->post_title; ?> <small><?php echo $username; ?></small></h2>
    </div>
</div>
<div id="container_three_columns" class="container-fluid">
    <div class="row">
        <div class="col-md-2">
            <div class="row">
                <div class="col-md-9 content-thumb">
                    <?php
                    if (get_the_post_thumbnail($object->ID, 'thumbnail')) {
                        $url_image = wp_get_attachment_url(get_post_thumbnail_id($object->ID));
                        ?>
                        <a href="#" onclick="$.prettyPhoto.open(['<?php echo $url_image; ?>'], [''], ['']);
                                return false">
                            <img src="<?php echo $url_image; ?>" class="img-responsive" />
                            <!--?php
                            echo get_the_post_thumbnail($object->ID, 'thumbnail');
                            ?-->
                        </a>
                        <?php
                    } else {
                        ?>
                        <img class="img-responsive" src="<?php echo get_item_thumbnail_default($object->ID); ?>">
                    <?php } ?>
      <!--a href=""><img src="images/imagem.png" alt="" class="img-responsive"></a-->
                </div>
                <div class="col-md-3 item-redesocial content-redesocial">
                    <a target="_blank" href="http://www.facebook.com/sharer/sharer.php?s=100&amp;p[url]=<?php echo get_the_permalink($collection_id) . '?item=' . $object->post_name; ?>&amp;p[images][0]=<?php echo wp_get_attachment_url(get_post_thumbnail_id($object->ID)); ?>&amp;p[title]=<?php echo htmlentities($object->post_title); ?>&amp;p[summary]=<?php echo strip_tags($object->post_content); ?>">
                        <span data-icon="&#xe021;"></span>
                    </a>
                    <a target="_blank" href="https://twitter.com/intent/tweet?url=<?php echo get_the_permalink($collection_id) . '?item=' . $object->post_name; ?>&amp;text=<?php echo htmlentities($object->post_title); ?>&amp;via=socialdb">
                        <span data-icon="&#xe005;"></span>
                    </a>
                    <a target="_blank" href="https://plus.google.com/share?url=<?php echo get_the_permalink($collection_id) . '?item=' . $object->post_name; ?>">
                        <span data-icon="&#xe01b;"></span>
                    </a>
                </div>
                <ul class="col-md-3 item-funcs">
                    <?php if ($is_moderator || $object->post_author == get_current_user_id()): ?>
                              <!--li><a href=""><span class="glyphicon glyphicon-trash"></span></a></li>
                              <li class="hide"><a href=""><span class="glyphicon glyphicon-warning-sign"></span></a></li>
                              <li><a href=""><span class="glyphicon glyphicon-pencil"></span></a></li>
                              <li class="hide"><a href=""><span class="glyphicon glyphicon-comment"></span></a></li-->
                        <li>
                            <a onclick="single_delete_object('<?= __('Delete Object', 'tainacan') ?>', '<?= __('Are you sure to remove the object: ', 'tainacan') . $object->post_title ?>', '<?php echo $object->ID ?>', '<?= mktime() ?>')" href="#" class="remove"> 
                                <span class="glyphicon glyphicon-trash"></span>
                            </a>
                        </li>
                        <li>
                            <a href="#"  onclick="show_edit_object('<?php echo $object->ID ?>')" class="edit">
                                <span class="glyphicon glyphicon-pencil"></span>
                            </a>
                        </li>
                    <?php else: ?>
                        <li>
                            <a onclick="single_show_report_abuse('<?php echo $object->ID ?>')" href="#" class="report_abuse">
                                <span class="glyphicon glyphicon-warning-sign"></span>
                            </a>
                        </li>
                        <!-- modal exluir -->
                        <div class="modal fade" id="single_modal_delete_object<?php echo $object->ID ?>" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">  
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                        <h4 class="modal-title" id="myModalLabel"><span class="glyphicon glyphicon-trash"></span>&nbsp;<?php _e('Report Abuse', 'tainacan'); ?></h4>
                                    </div>
                                    <div class="modal-body">
                                        <?php echo __('Describe why the object: ', 'tainacan') . get_the_title() . __(' is abusive: ', 'tainacan'); ?>
                                        <textarea id="observation_delete_object<?php echo $object->ID ?>" class="form-control"></textarea>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close', 'tainacan'); ?></button>
                                        <button onclick="single_report_abuse_object('<?= __('Delete Object') ?>', '<?= __('Are you sure to remove the object: ', 'tainacan') . get_the_title() ?>', '<?php echo $object->ID ?>', '<?= mktime() ?>')" type="button" class="btn btn-primary"><?php echo __('Delete', 'tainacan'); ?></button>
                                    </div>
                                    </form>  
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="row same-height">
                <div class="col-md-9">
                    <span><small>Data: <?php echo get_the_date('d/m/y', $object->ID); ?></small></span><br>
                 <!--a href=""><span><small>Ver edições anteriores</small></span></a-->
                </div>
                <input type="hidden" class="post_id" name="post_id" value="<?= $object->ID ?>">
            </div>
            <div class="row">
                <div class="col-md-12">
                    <h4 class="title-pipe"><?php _e('Ranking', 'tainacan'); ?></h4>
                    <div id="single_list_ranking_<?php echo $object->ID; ?>"></div>
                </div>
            </div>

        </div>
        <!-- TAINACAN: esta div agrupa a listagem de itens ,submissao de novos itens e ordencao -->
        <div id="div_central" class="col-md-8">
            <div class="row content-wrapper">
                <div>
                    <?php
//                  echo '<pre>';
//                  var_dump($metas);
//                  echo '</pre>';
//                  exit();

                    if ($metas['socialdb_object_dc_type'][0] == 'text') {
                        echo $metas['socialdb_object_content'][0];
                    } else {
                        if ($metas['socialdb_object_from'][0] == 'internal') {
                            $url = wp_get_attachment_url($metas['socialdb_object_content'][0]);
                            switch ($metas['socialdb_object_dc_type'][0]) {
                                case 'audio':
                                    $content = '<audio controls><source src="' . $url . '">' . __('Your browser does not support the audio element.', 'tainacan') . '</audio>';
                                    break;
                                case 'image':
                                    if (get_the_post_thumbnail($object->ID, 'thumbnail')) {
                                        $url_image = wp_get_attachment_url(get_post_thumbnail_id($object->ID, 'large'));
                                        $content = '<center><a href="#" onclick="$.prettyPhoto.open([\''.$url_image.'\'], [\'\'], [\'\']);return false">
                                                        <img src="'.$url_image.'" class="img-responsive" />
                                                    </a></center>';
                                    } 
                                    //$content = "<img src='" . $url . "' class='img-responsive' />";
                                    break;
                                case 'video':
                                    $content = '<video width="400" controls><source src="' . $url . '">' . __('Your browser does not support HTML5 video.', 'tainacan') . '</video>';
                                    break;
                                case 'pdf':
                                    $content = '<embed src="' . $url . '" width="600" height="500" alt="pdf" pluginspage="http://www.adobe.com/products/acrobat/readstep2.html">';
                                    break;
                                default:
                                    $content = '<p style="text-align:center;">'.__('File link:') . ' <a target="_blank" href="' . $url . '">' . __('Click here!', 'tainacan') . '</a></p>';
                                    break;
                            }
                        } else {
                            switch ($metas['socialdb_object_dc_type'][0]) {
                                case 'audio':
                                    $content = '<audio controls><source src="' . $metas['socialdb_object_content'][0] . '">' . __('Your browser does not support the audio element.', 'tainacan') . '</audio>';
                                    break;
                                case 'image':
                                    if (get_the_post_thumbnail($object->ID, 'thumbnail')) {
                                        $url_image = wp_get_attachment_url(get_post_thumbnail_id($object->ID, 'large'));
                                        $content = '<center><a href="#" onclick="$.prettyPhoto.open([\''.$url_image.'\'], [\'\'], [\'\']);return false">
                                                        <img src="'.$url_image.'" class="img-responsive" />
                                                    </a></center>';
                                    }else{ 
                                        $content = "<img src='" . $metas['socialdb_object_content'][0] . "' class='img-responsive' />";
                                    }
                                    break;
                                case 'video':
                                    if (strpos($metas['socialdb_object_content'][0], 'youtube') !== false) {
                                        $step1 = explode('v=', $metas['socialdb_object_content'][0]);
                                        $step2 = explode('&', $step1[1]);
                                        $video_id = $step2[0];
                                        $content = "<div style='height:600px;'  ><iframe  class='embed-responsive-item' src='http://www.youtube.com/embed/" . $video_id . "?html5=1' allowfullscreen frameborder='0'></iframe></div>";
                                    } elseif (strpos($metas['socialdb_object_content'][0], 'vimeo') !== false) {
                                        $step1 = explode('/', rtrim($metas['socialdb_object_content'][0],'/'));
                                        $video_id = end($step1);
                                        //"https://player.vimeo.com/video/132886713"
                                        $content = "<div class=\"embed-responsive embed-responsive-16by9\"><iframe class='embed-responsive-item' src='https://player.vimeo.com/video/" . $video_id . "' frameborder='0'></iframe></div>";
                                    } else {
                                        $content = "<div class=\"embed-responsive embed-responsive-16by9\"><iframe class='embed-responsive-item' src='" . $metas['socialdb_object_content'][0] . "' frameborder='0'></iframe></div>";
                                    }
                                    break;
                                case 'pdf':
                                    $content = '<embed src="' . $metas['socialdb_object_content'][0] . '" width="600" height="500" alt="pdf" pluginspage="http://www.adobe.com/products/acrobat/readstep2.html">';
                                    break;
                                default:
                                    $content = '<p style="text-align:center;">'.__('File link:', 'tainacan') . ' <a target="_blank" href="' . $metas['socialdb_object_content'][0] . '">' . __('Click here!', 'tainacan') . '</a></p>';
                                    break;
                            }
                        }

                        echo $content;
                    }
                    ?>

                    <!--iframe class="embed-responsive-item" src="https://www.youtube.com/embed/_oACsOlz_PQ" frameborder="0" allowfullscreen></iframe-->
                </div>
            </div>
            <div class="row">
                <h4 class="title-pipe">Descrição<!--small><a href=""><span class="glyphicon glyphicon-pencil"></span></a></small--></h4>
                <div class="col-md-12">
                    <p><?php echo $object->post_content; ?></p>
                </div>
            </div>
            <hr>
            <div class="row">
                <h4 class="title-pipe">Metadado
                  <!--a href=""><small><span class="glyphicon glyphicon-pencil"></span></small></a-->
                    <div class="btn-group">
                        <button data-toggle="dropdown" class="btn btn-default dropdown-toggle" type="button" id="btnGroupVerticalDrop1" style="font-size:11px;">
                            <span class="glyphicon glyphicon-plus grayleft" ></span>
                            <span class="caret"></span>
                        </button>
                        <ul  aria-labelledby="btnGroupVerticalDrop1" role="menu" class="dropdown-menu" style="width: 200px;">
                            <li>&nbsp;<span class="glyphicon glyphicon-th-list graydrop"></span>&nbsp;<span><a class="add_property_data" onclick="show_form_data_property_single('<?php echo $object->ID ?>')" href="#property_form_<?php echo $object->ID ?>"><?php _e('Add new data property', 'tainacan'); ?></a></span></li>
                            <li>&nbsp;<span class="glyphicon glyphicon-th-list graydrop"></span>&nbsp;<span><a class="add_property_object" onclick="show_form_object_property_single('<?php echo $object->ID ?>')" href="#property_form_<?php echo $object->ID ?>"><?php _e('Add new object property', 'tainacan'); ?></a></span></li>
                        </ul>   
                    </div>
                    <div class="btn-group">
                        <button  data-toggle="dropdown" class="btn btn-default dropdown-toggle" type="button" id="btnGroupVerticalDrop2" style="font-size:11px;">
                            <span class="glyphicon glyphicon-pencil grayleft"></span>
                            <span class="caret"></span>
                        </button>
                        <ul id="single_list_properties_edit_remove" style="width:225px;" aria-labelledby="btnGroupVerticalDrop1" role="menu" class="dropdown-menu">
                        </ul>   
                    </div>
                    ´</h4>
                <div class="col-md-12">
                    <div id="single_list_all_properties_<?php echo $object->ID ?>">
                    </div> 
                    <div id="single_data_property_form_<?php echo $object->ID ?>">
                    </div>
                    <div id="single_object_property_form_<?php echo $object->ID ?>">
                    </div> 
                    <div id="single_edit_data_property_form_<?php echo $object->ID ?>">
                    </div>
                    <div id="single_edit_object_property_form_<?php echo $object->ID ?>">
                    </div> 
                </div>
            </div>
            <div class="row">
                <h4 class="title-pipe"><?php _e('Categories and Tags', 'tainacan'); ?><a href=""><small><span class="glyphicon glyphicon-pencil"></span></small></a></h4>
                <div class="col-md-12">
                    <input type="hidden" value="<?php echo $object->ID ?>" class="object_id">
                    <center><button id="single_show_classificiations_<?php echo $object->ID; ?>" onclick="show_classifications_single('<?php echo $object->ID; ?>')" class="btn btn-default btn-lg"><?php _e('Show classifications', 'tainacan'); ?></button></center>
                    <div id="single_classifications_<?php echo $object->ID ?>">
                    </div>
                    <script>
                        $('#single_show_classificiations_<?php echo $object->ID ?>').hide();
                        $('#single_show_classificiations_<?php echo $object->ID ?>').trigger('click');
                    </script>
                </div>
            </div>
            <hr>
            <div class="row">
                <div id="comments_object"></div>
                <!--h4 class="title-pipe">Comentários (10)</h4>
                <br>
                <div class="row">
                  <div class="col-md-2">
                    <div class="col-md-10 pull-right content-thumb">
                      <a href=""><img src="images/imagem.png" alt="" class="img-responsive"></a>
                    </div>
                  </div>
                  <div class="col-md-10">
                    <p><b class="azul">Autor: </b>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum</p>
                  </div>
                </div>
                <hr>
                <div class="row">
                  <div class="col-md-2 col-md-offset-1">
                    <div class="col-md-10 pull-right content-thumb">
                      <a href=""><img src="images/imagem.png" alt="" class="img-responsive"></a>
                    </div>
                  </div>
                  <div class="col-md-9">
                    <p><b class="azul">Autor: </b>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum</p>
                  </div>
                </div-->
            </div>
            <br>
        </div>
        <div class="col-md-2">
            <h4 class="title-pipe" style="margin-bottom: 20px;"><?php _e('Attachments', 'tainacan'); ?></h4>
            <div id="single_list_files_<?php echo $object->ID ?>"></div>
            <!--div class="row">
              <div class="col-md-8 col-md-offset-2 content-thumb">
                <a href=""><img src="images/imagem.png" alt="" class="img-responsive"></a>
              </div>
              <h4 class="text-center"><small>Imagem titulo</small></h4>
            </div>
            <div class="row">
              <div class="col-md-8 col-md-offset-2 content-thumb">
                <a href=""><img src="images/imagem.png" alt="" class="img-responsive"></a>
              </div>
              <h4 class="text-center"><small>Imagem titulo</small></h4>
            </div-->
        </div>
    </div>
</div>