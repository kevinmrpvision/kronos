<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mrpvision\Kronos\Resourses\Company\Employees;

use Mrpvision\Kronos\KronosClient;
use Carbon\Carbon;

/**
 * Description of Employees
 *
 * @author kevpat
 */
class Employees {

    protected $client;

    public function __construct(KronosClient $client) {
        $this->client = $client;
    }
/*
 * Rest API V2
 */
    public function getAll($query = []) {
        if (false === $response = $this->client->get('/ta/rest/v2/companies/{cid}/employees')) {
            throw new \Exception('Error in getting employees data.');
        }
        return $response->json();
    }

    public function getById($employee_id) {
        if (!$employee_id)
            throw new Exception('Account should be referenced via account id or account external id using |external_id format.');
        if (false === $response = $this->client->get('/ta/rest/v2/companies/{cid}/employees/' . $employee_id)) {
            throw new \Exception('Error in getting employees data.');
        }
        return $response->json();
    }

    public function create($data) {
        if(!$data or !  is_array($data))throw new Exception('Employee data is required');
        if (false === $response = $this->client->rawpost('/ta/rest/v2/companies/{cid}/employees', $data)) {
            throw new \Exception('Error in getting employees data.');
        }
        return $response->json();
    }

    public function me() {
        if (false === $response = $this->client->get('/ta/rest/v2/companies/{cid}/employees/me')) {
            throw new \Exception('Error in getting employees data.');
        }
        return $response->json();
    }

    public function changed(Carbon $since) {
        $now = Carbon::now();
        if ($since->diffInDays($now) > 31)
            throw new \Exception('Date cannot be more than 31 days in the past.');
        if (false === $response = $this->client->get('/ta/rest/v2/companies/{cid}/employees/changed?since=' . $since->format('Y-m-d'))) {
            throw new \Exception('Error in getting changed employees data.');
        }
        return $response->json();
    }
    /*
     * REST API V1
     */
    
    public function todo($query=[],$all=false) {
        if(!isset($query['company:id'])){
            $query['company:id']=$this->client->getProvider()->getCompanyID();
        }
        if( !$all and !isset($query['employee:username'])){
            $query['employee:username']=$this->client->getProvider()->getUserName();
        }
        
        
        if (false === $response = $this->client->get('/ta/rest/v1/employee/todo?'.  http_build_query($query))) {
            throw new \Exception('Error in getting employees todo data.');
        }
        return $response->json();
    }
}
