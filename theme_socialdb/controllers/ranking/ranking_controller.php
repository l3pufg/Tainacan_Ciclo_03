<?php

/**
 * Author: Eduardo Humberto
 */
require_once(dirname(__FILE__) . '../../../models/ranking/ranking_model.php');
require_once(dirname(__FILE__) . '../../general/general_controller.php');

class RankingController extends Controller {

    public function operation($operation, $data) {
        $ranking_model = new RankingModel();
        switch ($operation) {
            case "add":
                echo $ranking_model->add($data);
                break;
            case "edit":
                echo $ranking_model->edit($data);
                break;
            case "remove":
                $ranking_model->remove();
                break;
            case "list_data":
                return $this->render(dirname(__FILE__) . '../../../views/ranking/list.php');
                break;

            case "list_ranking":
                return $ranking_model->list_ranking($data);
                break;
            case "list_value_ordenation":
                $data = $ranking_model->list_value_ordenation($data);
                return $this->render(dirname(__FILE__) . '../../../views/object/ranking/list_value_ordenation.php', $data);
                break;
            case "list_ranking_object":
                $data = $ranking_model->list_ranking_object($data);
                return $this->render(dirname(__FILE__) . '../../../views/object/ranking/list_ranking.php', $data);
                break;
            case "single_list_ranking_object":
                $data = $ranking_model->list_ranking_object($data);
                return $this->render(dirname(__FILE__) . '../../../views/object/single_object/ranking/list_ranking.php', $data);
                break;

            case "edit_ranking":
                $ranking = $ranking_model->edit_ranking($data);

                return $this->render(dirname(__FILE__) . '../../../views/ranking/edit.php', $ranking);
                break;
            case "delete_ranking":
                return $ranking_model->delete($data);
                break;

            case "add_new":
                return $this->render(dirname(__FILE__) . '../../../views/ranking/add.php');
                break;

            case "save_vote_stars":
                if (is_user_logged_in()) {
                    $data['is_user_logged_in'] = true;
                    $data['is_new'] = $ranking_model->save_vote($data);
                    $data['results'] = $ranking_model->calculate_vote_stars($data['property_id'], $data['object_id']);
                    $score = ceil(($data['score']))/2;
                    $final_score =  ceil(($data['results']['final_score'])*2)/2;
                    $data['msg'] = __('Your vote was ','tainacan').$score.__(', the average is ','tainacan').$final_score;
                } else {
                    $data['results'] = $ranking_model->calculate_vote_stars($data['property_id'], $data['object_id']);
                    $data['is_user_logged_in'] = false;
                }
                return json_encode($data);
                break;
            case "save_vote_like":
                if (is_user_logged_in()) {
                    $data['is_user_logged_in'] = true;
                    $data['is_new'] = $ranking_model->save_vote($data, false);
                    $data['results'] = $ranking_model->calculate_vote_like($data['property_id'], $data['object_id']);
                } else {
                    $data['results'] = $ranking_model->calculate_vote_like($data['property_id'], $data['object_id']);
                    $data['is_user_logged_in'] = false;
                }
                return json_encode($data);
                break;
            case "save_vote_binary":
                if (is_user_logged_in()) {
                    $data['is_user_logged_in'] = true;
                    $data['is_new'] = $ranking_model->save_vote($data);
                    $data['results'] = $ranking_model->calculate_vote_binary($data['property_id'], $data['object_id']);
                } else {
                    $data['results'] = $ranking_model->calculate_vote_binary($data['property_id'], $data['object_id']);
                    $data['is_user_logged_in'] = false;
                }
                return json_encode($data);
                break;

            case 'redirect_facebook':
                return json_encode($ranking_model->redirect_facebook($data));
            case 'next_step':
                if ($data['property_data_required'] == 'true') {
                    return $ranking_model->create_santard_vote($data);
                } else {
                    return true;
                }
                break;
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

$ranking_controller = new RankingController();
echo $ranking_controller->operation($operation, $data);
?>