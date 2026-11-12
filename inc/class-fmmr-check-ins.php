<?php
//Class to get attendee data from MR Events

class MRCheckIns{
    private $check_ins = [];
    private $date_from;
    private $date_to;
    public function __construct(?string $date_from,?string $date_to){
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->attendee_table=$this->wpdb->prefix . 'hdk_events_attendees';
        $this->post_meta_table=$this->wpdb->prefix . 'postmeta';
        $this->date_from = $date_from;
        $this->date_to = $date_to;
        $this->get_check_ins();
    }

    public function get_check_ins(){
        if($this->date_from && $this->date_to){
            $this->get_check_ins_by_date();
        }
        elseif($this->date_from){
            $this->get_check_ins_by_date_from();
        }
        else{
            $this->get_all_check_ins();
        }
        
        
    }

    public function get_check_ins_by_date(){
        $query = "SELECT attendee.*, pm1.meta_value as OrderId, pm2.meta_value as FmId FROM $this->attendee_table as attendee LEFT JOIN $this->post_meta_table as pm1 ON attendee.ticketNumber = pm1.post_id LEFT JOIN $this->post_meta_table as pm2 ON attendee.EventId = pm2.post_id WHERE attendee.updated BETWEEN '$this->date_from' AND '$this->date_to' AND pm1.meta_key = 'hdk_event_order_id' AND pm2.meta_key = 'filemaker_id' ORDER BY attendee.updated DESC";
        $this->check_ins = $this->wpdb->get_results($query,ARRAY_A)?:[];
       ;    }
    public function get_check_ins_by_date_from(){
        $query = "SELECT attendee.*, pm1.meta_value as OrderId, pm2.meta_value as FmId FROM $this->attendee_table as attendee LEFT JOIN $this->post_meta_table as pm1 ON attendee.ticketNumber = pm1.post_id LEFT JOIN $this->post_meta_table as pm2 ON attendee.EventId = pm2.post_id WHERE attendee.updated >= '$this->date_from' AND pm1.meta_key = 'hdk_event_order_id' AND pm2.meta_key = 'filemaker_id' ORDER BY attendee.updated DESC";
        $this->check_ins = $this->wpdb->get_results($query,ARRAY_A)?:[];
    }

    public function get_all_check_ins(){
        /*Three days of data if no date is passed*/
        $date = date('Y-m-d H:i:s',strtotime('-3 days'));
        $query = "SELECT attendee.*, pm1.meta_value as OrderId, pm2.meta_value as FmId FROM $this->attendee_table as attendee LEFT JOIN $this->post_meta_table as pm1 ON attendee.ticketNumber = pm1.post_id LEFT JOIN $this->post_meta_table as pm2 ON attendee.EventId = pm2.post_id WHERE attendee.updated >= '$date' AND pm1.meta_key = 'hdk_event_order_id' AND pm2.meta_key = 'filemaker_id' ORDER BY attendee.updated DESC";
        $this->check_ins = $this->wpdb->get_results($query,ARRAY_A)?:[];
    }
    
    public function map_checkins(){
        if($this->check_ins){
            $orders = [];
            $this->check_ins=array_map(function($n)use(&$orders){
                $order_id = $n['OrderId'];
                if(!array_key_exists($order_id,$orders)){
                    $orders[$order_id] = $order_id?wc_get_order($order_id):false;
                }
                $order = $orders[$order_id];
                return [
                    'name'=>$n['AttendeeName'],
                    'ticket'=>$n['TicketId'],
                    'status'=>$n['TicketStatus'],
                    'event_id'=>$n['EventId'],
                    'date'=>$n['EventDate'],
                    'filemaker_id'=>$n['FmId'],
                    'order_id'=>$n['OrderId'],
                    'ticket_type'=>$order?MROrders::get_ticket_type($order):null,
                    'payment_method'=>$order?MROrders::get_payment_method($order):null,
                    'id'=>$n['AttendeeId'],
                    'updated'=>$n['updated']
                ];
            },$this->check_ins);
        }
    }

    public function response(){
        $this->map_checkins();
        return $this->check_ins;
    }
}