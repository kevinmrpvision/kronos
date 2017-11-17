<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mrpvision\Kronos\Resourses\Company\Configuration;
use Mrpvision\Kronos\KronosClient;
/**
 * Description of CostCenters
 *
 * @author kevpat
 */
class CostCenters {
    //put your code here
    protected $client;
    public function __construct(KronosClient $client) {
        $this->client = $client;
    }
    
    public function getAll($query= []) {
        if(!isset($query['tree_index'])){
            $query = ['tree_index'=>'1'];
        }
        if (false === $response = $this->client->get('/ta/rest/v2/companies/{cid}/config/cost-centers?'.http_build_query($query))) {
            throw new \Exception('Error in getting all cost center data.');
        }
        return $response->json();
    }

    public function getById($cost_center_id){
        if (false === $response = $this->client->get('/ta/rest/v2/companies/{cid}/config/cost-centers/'.$cost_center_id)) {
            throw new \Exception('Error in getting cost center data.');
        }
        return $response->json();
    }
    
    public function update($cost_center_id, $data = []){
        if (false === $response = $this->client->put('/ta/rest/v2/companies/{cid}/config/cost-centers/'.$cost_center_id)) {
            throw new \Exception('Error in updating cost center data.');
        }
        return $response->json();
    }
    
    public function upload($data = []){
        if (false === $response = $this->client->post('/ta/rest/v2/companies/{cid}/config/cost-centers/collection')) {
            throw new \Exception('Error in bulk upload cost center data.');
        }
        return $response->json();
    }
    
}
