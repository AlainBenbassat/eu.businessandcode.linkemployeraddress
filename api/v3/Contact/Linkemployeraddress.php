<?php
use CRM_Linkemployeraddress_ExtensionUtil as E;

function _civicrm_api3_contact_Linkemployeraddress_spec(&$spec) {
}

function civicrm_api3_contact_Linkemployeraddress($params) {
    // search for individuals
    // without an address
    // but with an employer who has an address
    $sql = "
      select
        c.id person_id, 
        a.* 
      from
        civicrm_contact c
      inner join
        civicrm_address a on a.contact_id = c.employer_id and a.is_primary = 1
      where 
        c.contact_type = 'Individual'
      and 
        c.employer_id > 0
      and 
        not exists (
          select * from civicrm_address ca where ca.contact_id = c.id 
        )
      and 
        c.is_deleted = 0
      limit
        0,1000
    ";
    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      // link the employer address to this contact
      $params = [
        'is_primary' => 1,
        'contact_id' => $dao->person_id,
        'location_type_id' => 2, // work
        'street_address' => $dao->street_address,
        'supplemental_address_1' => $dao->supplemental_address_1,
        'supplemental_address_2' => $dao->supplemental_address_2,
        'supplemental_address_3' => $dao->supplemental_address_3,
        'city' => $dao->city,
        'county_id' => $dao->county_id,
        'state_province_id' => $dao->state_province_id,
        'postal_code_suffix' => $dao->postal_code_suffix,
        'postal_code' => $dao->postal_code,
        'country_id' => $dao->country_id,
      ];

      // if the employer address is already a link to another address take that,
      // otherwise point the address of the employee to the employer's address
      if ($dao->master_id) {
        $params['master_id'] = $dao->master_id;
      }
      else {
        $params['master_id'] = $dao->id;
      }

      civicrm_api3('Address', 'create', $params);
    }


    return civicrm_api3_create_success('OK', $params, 'Contact', 'Linkemployeraddress');
}
