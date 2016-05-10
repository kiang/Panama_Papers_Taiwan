<?php
$ref = array();
$fh = fopen(__DIR__ . '/offshore_leaks_csvs/all_edges.csv', 'r');
/*
rel_type

Array
(
    [intermediary_of] => 1
    [officer_of] => 1
    [registered_address] => 1
    [similar] => 1
    [underlying] => 1
)
*/
while($line = fgetcsv($fh, 2048)) {
  if($line[1] === 'officer_of') {
    if(!isset($ref[$line[2]])) {
      $ref[$line[2]] = array();
    }
    $ref[$line[2]][] = $line[0];
  }
}

$fh = fopen(__DIR__ . '/offshore_leaks_csvs/Officers.csv', 'r');
/*
Array
(
    [0] => name
    [1] => icij_id
    [2] => valid_until
    [3] => country_codes
    [4] => countries
    [5] => node_id
    [6] => sourceID
)
*/
$officers = array();
$header = fgetcsv($fh, 4096);
while($line = fgetcsv($fh, 4096)) {
  if(isset($line[6]) && $line[6] === 'Panama Papers' && false !== strpos($line[3], 'TWN')) {
    $officers[$line[5]] = array_combine($header, $line);
  }
}

$fh = fopen(__DIR__ . '/offshore_leaks_csvs/Entities.csv', 'r');
/*
    [0] => name
    [1] => original_name
    [2] => former_name
    [3] => jurisdiction
    [4] => jurisdiction_description
    [5] => company_type
    [6] => address
    [7] => internal_id
    [8] => incorporation_date
    [9] => inactivation_date
    [10] => struck_off_date
    [11] => dorm_date
    [12] => status
    [13] => service_provider
    [14] => ibcRUC
    [15] => country_codes
    [16] => countries
    [17] => note
    [18] => valid_until
    [19] => node_id
    [20] => sourceID
*/
$result = array();
$header = fgetcsv($fh, 4096);
while($line = fgetcsv($fh, 4096)) {
  if(isset($line[20]) && $line[20] === 'Panama Papers' && false !== strpos($line[15], 'TWN')) {
    $line = array_combine($header, $line);
    $line['officers'] = array();
    if(isset($ref[$line['node_id']])) {
      foreach($ref[$line['node_id']] AS $nodeId) {
        if(isset($officers[$nodeId])) {
          $line['officers'][] = $officers[$nodeId];
        }
      }
    }
    $result[] = $line;
  }
}
file_put_contents(__DIR__ . '/taiwan.json', json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
