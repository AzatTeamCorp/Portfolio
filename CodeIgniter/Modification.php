<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Notifications extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('projects_model');
    }

    public function notify_sud()
    {
        // 24
        $notifications = $this->tasks_model->get_judge_events(25);
        $this->send_notif($notifications);

        echo json_encode(['msg' => 'success', 'status' => 'ok', 'code' => '200']);
    }

    private function send_notif($notifications)
    {
        foreach ($notifications as $key => $value):
            // get assigned
            
            $assigned = $this->tasks_model->get_all_assigned($value['id']);

            foreach($assigned as $key1 => $value1){
                $value['email'] = $value1['email'];
                $value['telegram_id'] = $value1['telegram_id'];

                // send telegram
                $this->to_telegram($value);
            }
            
        endforeach;
    }
        
    private function to_telegram($entity)
    {
        $apiToken = "5952428584:AAHTwnXdCJpqle8iT9FMQXyw2Oix8_Ve8gs";
        $data = [
            'chat_id' => $entity['telegram_id'],
            'text' => $entity['name'] . "\n" .
                    'Судья: ' .  $entity['judger'] . "\n" . 
                    'Дата Время: ' .  $entity['startdate'] . ' ' . $entity['starttime']
        ];
        $response = file_get_contents("https://api.telegram.org/bot$apiToken/sendMessage?" .
                                        http_build_query($data) );
    }

}
