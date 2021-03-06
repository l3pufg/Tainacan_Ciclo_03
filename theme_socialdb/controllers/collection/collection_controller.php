<?php

/**
 * The main template file
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * e.g., it puts together the home page when no home.php file exists.
 *
 * Learn more: {@link https://codex.wordpress.org/Template_Hierarchy}
 *
 * @package WordPress
 * @subpackage Twenty_Fifteen
 * @since Twenty Fifteen 1.0
 */
require_once(dirname(__FILE__) . '../../../models/collection/collection_model.php');
require_once(dirname(__FILE__) . '../../../models/collection/collection_parent_model.php');
require_once(dirname(__FILE__) . '../../../models/collection/collection_import_model.php');
require_once(dirname(__FILE__) . '../../../models/collection/visualization_model.php');
require_once(dirname(__FILE__) . '../../../models/property/property_model.php');
require_once(dirname(__FILE__) . '../../general/general_controller.php');
include_once (dirname(__FILE__) . '../../../models/event/event_collection/event_collection_create_model.php');

class CollectionController extends Controller {

    public function operation($operation, $data) {
        $collection_model = new CollectionModel();
        $collection_parent_model = new CollectionParentModel();
        $visualization_model = new VisualizationModel();
        switch ($operation) {
            case "initDynatree":
                return $visualization_model->initDynatree($data);
                break;
            case "initDynatreeSingleEdit":
                return $visualization_model->initDynatreeSingleEdit($data);
                break;
            case "expand_dynatree":
                return json_encode($visualization_model->expandDynatree($data));
                break;
            case "create":
                return $collection_model->create();
                break;
            case 'simple_add':
                $data['collection_name'] = trim($data['collection_name']);
                $data['collection_object'] = trim($data['collection_object']);
                if(empty($data['collection_name'])||empty($data['collection_object'])):
                    header("location:" . get_permalink(get_option('collection_root_id')) . '?info_messages=' . __('Invalid collection name or object name!','tainacan') . '&info_title=' . __('Attention','tainacan'));
                elseif (is_user_logged_in()):
                    $new_collection_id = $collection_model->simple_add($data);
                    if ($new_collection_id) {
                        $result = json_decode($this->insert_collection_event($new_collection_id, $data));
                        if ($result->type == 'success') {
                            header("location:" . get_permalink($new_collection_id) . '?open_wizard=true');
                        } else {
                            header("location:" . get_permalink(get_option('collection_root_id')) . '?info_messages=' . __('Collection sent for approval','tainacan') . '&info_title=' . __('Attention','tainacan'));
                        }
                    } else {
                        header("location:" . get_permalink(get_option('collection_root_id')) . '?info_messages=' . __('Collection already exists','tainacan') . '&info_title=' . __('Attention','tainacan'));
                    }
                else:
                    header("location:" . get_permalink(get_option('collection_root_id')) . '?info_messages=' . __('You must be logged in to create collecions','tainacan') . '&info_title=' . __('Attention','tainacan'));
                endif;
                break;
            case "add":
                return $collection_model->add($data);
                break;
            case "edit":
                return $collection_model->edit($data);
                break;
            case "update":
                if (isset($data['save_and_next']) && $data['save_and_next'] == 'true') {
                    $data['next_step'] = true;
                } else {
                    $data['next_step'] = false;
                }
                $data['update'] = $collection_model->update($data);
                return json_encode($data);
                break;
            case "delete":
                return $collection_model->delete($data);
                break;
            case "list":
                return $collection_model->list_collection();
                break;
            case "show_header":
                $mycollections = $data['mycollections'];
                $data = $collection_model->get_collection_data($data['collection_id']);
                $data['mycollections'] = $mycollections;
                $data['json_autocomplete'] = $collection_model->create_main_json_autocomplete($data['collection_post']->ID);
                return $this->render(dirname(__FILE__) . '../../../views/collection/header_collection.php', $data);
                break;
            case "edit_configuration":
                if (is_user_logged_in()) {
                    $data = $collection_model->get_collection_data($data['collection_id']);
                    return $this->render(dirname(__FILE__) . '../../../views/collection/edit.php', $data);
                } else {
                    wp_redirect(get_the_permalink(get_option('collection_root_id')));
                }
                break;
            case "list_ordenation":
                $data = $collection_model->list_ordenation($data);
                $data['names']['general_ordenation'] = __('General Ordenation','tainacan');
                $data['names']['data_property'] = __('Property Data','tainacan');
                $data['names']['ranking'] = __('Rankings','tainacan');
                return json_encode($data);
                break;
            case "show_form_data_property":
                return $collection_model->list_ordenation($data);
                break;
            case 'list_autocomplete' :
                return json_encode($collection_model->create_main_json_autocomplete($data['collection_id'], $data['term']));
            case "initGeneralJit":
                return $visualization_model->initJit($data);
                break;
            case "initTreemapJit":
                return $visualization_model->initTreemapJit($data);
                break;
            case "get_collections_json":// pega todos as colecoes e coloca em um array json
                return $collection_model->get_collections_json($data);
                break;
            case 'get_most_participatory_authors':
                $collection_id = $data['collection_id'];
                $data = $collection_model->get_collection_data($collection_id);
                if ($data['collection_metas']['socialdb_collection_most_participatory'] == 'yes') {
                    $data['authors'] = $collection_model->get_most_participatory_authors($collection_id);
                    return $this->render(dirname(__FILE__) . '../../../views/collection/most_participatory_authors.php', $data);
                }
                break;
            case 'get_category_property':
                return $collection_model->get_order_category_properties($data);
                break;
            case 'check_privacity':
                return $collection_model->check_privacity($data);
                break;
            case 'verify_name_collection':
                return json_encode($collection_model->verify_name_collection($data));
            case 'delete_collection':
                return $collection_model->delete($data);
            case 'list_collections_parent':
                return json_encode($collection_parent_model->list_collection_parent($data['collection_id']));
            case "show_filters":
                $data = $collection_model->get_filters($data);
                return $this->render(dirname(__FILE__) . '../../../views/collection/filters.php', $data);
                break;
            //index search visualizations
            case "set_container_classes":
                return json_encode($visualization_model->set_container_classes($data));
                break;
             case 'load_menu_left':
                $data['facets'] = $visualization_model->get_facets_visualization($data['collection_id'],'left-column');
                $data['has_tree'] = $visualization_model->has_tree($data['collection_id'],'left-column');
                if($data['has_tree']){
                    $data['tree'] = $visualization_model->get_data_tree($data['collection_id']);
                }
                return $this->render(dirname(__FILE__) . '../../../views/search/menu_left.php', $data);
                break;
            case 'load_menu_right':
                $data['facets'] = $visualization_model->get_facets_visualization($data['collection_id'],'right-column');
                $data['has_tree'] = $visualization_model->has_tree($data['collection_id'],'right-column');
                if($data['has_tree']){
                    $data['tree'] = $visualization_model->get_data_tree($data['collection_id']);
                }
                return $this->render(dirname(__FILE__) . '../../../views/search/menu_right.php', $data);
                break;
             case 'load_menu_top':
                $data['facets'] = $visualization_model->get_facets_visualization($data['collection_id'],'horizontal');
                 return $this->render(dirname(__FILE__) . '../../../views/search/menu_top.php', $data);
                break;
            
            case 'list_items_search_autocomplete':
                $property_model = new PropertyModel;
                $property = get_term_by('id', $data['property_id'], 'socialdb_property_type');
                if ($property) {
                    if($property_model->get_property_type($property->term_id)=='socialdb_property_object'){
                        return  $visualization_model->get_objects_by_property_json($data);
                    }else{
                        return  $visualization_model->get_data_by_property_json($data);
                    }
                }else{
                    return  $visualization_model->get_terms_by_property_json($data);
                }
            case 'list_items_search_autocomplete_advanced_search':
                return $visualization_model->get_objects_by_property_json_advanced_search($data);
            // IMPORTACAO DE COLECAO
            case 'importCollection':
                $collectionImportation = new CollectionImportModel;
                return json_encode($collectionImportation->import($data));
                
        }
    }

    /**
     * @signature - function insert_event($object_id, $data )
     * @param int $object_id O id do Objeto
     * @param array $data Os dados vindos do formulario
     * @return array os dados para o evento
     * @description - 
     * @author: Eduardo 
     */
    public function insert_collection_event($collection_id, $data) {
        $eventAddCollection = new EventCollectionCreateModel();
        $data['socialdb_event_create_collection_id'] = $collection_id;
        $data['socialdb_event_collection_id'] = get_option('collection_root_id');
        $data['socialdb_event_user_id'] = get_current_user_id();
        $data['socialdb_event_create_date'] = mktime();
        return $eventAddCollection->create_event($data);
    }

}

/*
 * Controller execution
 */

if ($_POST['operation']) {
    $operation = $_POST['operation'];
    $data = $_POST;
} else {
    $operation = $_GET['operation'];
    $data = $_GET;
}

$collection_controller = new CollectionController();
echo $collection_controller->operation($operation, $data);
?>