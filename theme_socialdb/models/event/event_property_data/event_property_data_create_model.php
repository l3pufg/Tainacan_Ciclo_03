<?php

include_once ('../../../../../wp-config.php');
include_once ('../../../../../wp-load.php');
include_once ('../../../../../wp-includes/wp-db.php');
require_once(dirname(__FILE__) . '../../../event/event_model.php');
require_once(dirname(__FILE__) . '../../../property/property_model.php');

class EventPropertyDataCreate extends EventModel {

    public function EventPropertyDataCreate() {
        $this->parent = get_term_by('name', 'socialdb_event_property_data_create', 'socialdb_event_type');
        $this->permission_name = 'socialdb_collection_permission_create_property_data';
    }

    /**
     * function generate_title($data)
     * @param string $data  Os dados vindo do formulario
     * @return ara  
     * 
     * Autor: Eduardo Humberto 
     */
    public function generate_title($data) {
        $collection = get_post($data['socialdb_event_collection_id']);
        $property_name = $data['socialdb_event_property_data_create_name'];
        $title = __('Create the data property ','tainacan').'('.$property_name.')'.__(' in the collection ','tainacan').'<b>'.$collection->post_title.'</b>';
        return $title;
    }

    /**
     * function verify_event($data)
     * @param string $data  Os dados do evento a ser verificado
     * @param string $automatically_verified  Se o evento foi automaticamente verificado
     * @return array  
     * 
     * Autor: Eduardo Humberto 
     */
    public function verify_event($data,$automatically_verified = false) {
       $actual_state = get_post_meta($data['event_id'], 'socialdb_event_confirmed',true);
       if($actual_state!='confirmed'&&$automatically_verified||(isset($data['socialdb_event_confirmed'])&&$data['socialdb_event_confirmed']=='true')){// se o evento foi confirmado automaticamente ou pelos moderadores
           $data = $this->add_property($data['event_id'],$data,$automatically_verified);    
       }elseif($actual_state!='confirmed'){
           $this->set_approval_metas($data['event_id'], $data['socialdb_event_observation'], $automatically_verified);
           $this->update_event_state('not_confirmed', $data['event_id']);
           $data['msg'] = __('The event was successful NOT confirmed','tainacan');
           $data['type'] = 'success';
            $data['title'] = __('Success','tainacan');
       }else{
           $data['msg'] = __('This event is already confirmed','tainacan');
           $data['type'] = 'info';
            $data['title'] = __('Atention','tainacan');
       }
        $this->notificate_user_email(get_post_meta($data['event_id'], 'socialdb_event_collection_id',true),  get_post_meta($data['event_id'], 'socialdb_event_user_id',true), $data['event_id']);
       return json_encode($data);
    }
      /**
     * function update_post_status($data)
     * @param string $event_id  O id do evento que vai pegar os metas
     * @param string $data  Os dados do evento a ser verificado
     * @param string $automatically_verified  Se o evento foi automaticamente verificado
     * @return array    
     * 
     * Autor: Eduardo Humberto 
     */
    public function add_property($event_id,$data,$automatically_verified) {
        $propertyModel = new PropertyModel();
        // coloco os dados necessarios para criacao da propriedade
        $data['property_data_name'] = get_post_meta($event_id, 'socialdb_event_property_data_create_name',true) ;
        $data['collection_id'] = get_post_meta($event_id, 'socialdb_event_collection_id',true) ;
        $data['property_data_widget'] = get_post_meta($event_id, 'socialdb_event_property_data_create_widget',true) ;
        $data['property_data_required'] = get_post_meta($event_id, 'socialdb_event_property_data_create_required',true) ;
        $data['property_data_column_ordenation'] = get_post_meta($event_id, 'socialdb_event_property_data_create_ordenation_column',true) ;
        // chamo a funcao do model de propriedade para fazer a insercao
        $result = json_decode($propertyModel->add_property_data($data));
        // verifying if is everything all right
        if ($result->success=='true') {
            $this->set_approval_metas($data['event_id'], $data['socialdb_event_observation'], $automatically_verified);
            $this->update_event_state('confirmed', $data['event_id']);
            $data['msg'] = __('The event was successful','tainacan');
            $data['type'] = 'success';
             $data['title'] = __('Success','tainacan');
        } else {
            $this->update_event_state('invalid', $data['event_id']); // seto a o evento como invalido
            $data['msg'] = $result->msg;
            $data['type'] ='error';
            $data['title'] = 'Erro';
        }
        return $data;
    }

}
