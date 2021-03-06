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
require_once(dirname(__FILE__) . '../../../models/search/search_model.php');
require_once(dirname(__FILE__) . '../../../models/collection/collection_model.php');
require_once(dirname(__FILE__) . '../../general/general_controller.php');
require_once(dirname(__FILE__) . '../../../models/object/object_model.php');

class SearchController extends Controller {

    public function operation($operation, $data) {
        $search_model = new SearchModel();
        switch ($operation) {

            case "list_facets":
                $arrFacets = $search_model->get_saved_facets($data['collection_id']);
                return json_encode($arrFacets);
                break;
            case "edit":
                $collection_id = $data['collection_id'];
                $object_model = new ObjectModel();
                $data = $object_model->show_object_properties($data);
                $data['default_widget_tree'] = get_post_meta($collection_id, 'socialdb_collection_facet_widget_tree', true);
                $data['default_widget_tree_orientation'] = get_post_meta($collection_id, 'socialdb_collection_facet_widget_tree_orientation', true);
                $data['collection_id'] = $collection_id;
                $data['category_root_id'] = $object_model->get_category_root($collection_id);
                $data['category_root_name'] = get_term_by('id', $data['category_root_id'], 'socialdb_category_type')->name;
                $data['ordenation'] = $object_model->get_collection_data($collection_id);
                //$data = $design_model->get_collection_data($collection_id);

                return $this->render(dirname(__FILE__) . '../../../views/search/edit.php', $data);
                break;
            case 'get_widgets':
                return json_encode($search_model->get_widgets($data));
            case 'append_range':
                $data['type'] = $search_model->get_widget($data['facet_id']);
                return $this->render(dirname(__FILE__) . '../../../views/search/container_range.php', $data);
            case 'add':
                return json_encode($search_model->add($data));
            case 'update':
                return json_encode($search_model->update($data));
            case 'save_default_widget_tree':
                return json_encode($search_model->save_default_widget_tree($data));
                break;
            case 'save_default_widget_tree_orientation':
                return json_encode($search_model->save_default_widget_tree_orientation($data));
                break;
            case 'fill_edit_form':
                $data = $search_model->get_widget_edit($data);
                return json_encode($data);
                break;
            case 'delete_facet':
                return json_encode($search_model->delete($data));
                break;
            case 'update_ordenation':
                return json_encode($search_model->update_ordenation($data));
                break;
            case 'save_new_priority':
                return $search_model->save_new_priority($data);
                break;
            case 'remove_property_ordenation':
                return $search_model->remove_property_ordenation($data);
             case 'add_property_ordenation':
                return $search_model->add_property_ordenation($data);
            case 'get_widget_tree_type':
                return $search_model->get_widget_tree_type($data['property_id']);
        }
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

$search_controller = new SearchController();
echo $search_controller->operation($operation, $data);
?>