<?php

$ref = $addressLinks = $address = $caddress = array();

$json = json_decode(file_get_contents(__DIR__ . '/caddress.json'), true);
foreach ($json['address'] AS $line) {
    $caddress[$line['node_id']] = $line['caddress'];
}

/*
  Array
  (
  [0] => address
  [1] => icij_id
  [2] => valid_until
  [3] => country_codes
  [4] => countries
  [5] => node_id
  [6] => sourceID
  )
 */
$fh = fopen(__DIR__ . '/offshore_leaks_csvs/Addresses.csv', 'r');
$header = fgetcsv($fh, 2048);
while ($line = fgetcsv($fh, 2048)) {
    if (!isset($line[5])) {
        continue;
    }
    $address[$line[5]] = array_combine($header, $line);
    $address[$line[5]]['caddress'] = '';
    if (isset($caddress[$line[5]])) {
        $address[$line[5]]['caddress'] = $caddress[$line[5]];
    }
}

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
$hi = array();
while ($line = fgetcsv($fh, 2048)) {
    switch ($line[1]) {
        case 'registered address':
            if (isset($address[$line[2]])) {
                if (!isset($addressLinks[$line[0]])) {
                    $addressLinks[$line[0]] = array();
                }
                $addressLinks[$line[0]][] = $address[$line[2]];
            }
            break;
        default:
            if (!isset($ref[$line[2]])) {
                $ref[$line[2]] = array();
            }
            $ref[$line[2]][] = array($line[0], $line[1]);
            break;
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
while ($line = fgetcsv($fh, 4096)) {
    if (isset($line[6]) && $line[6] === 'Panama Papers') {
        $officers[$line[5]] = array_combine($header, $line);
        $officers[$line[5]]['registered_address'] = array();
        if (isset($addressLinks[$line[5]])) {
            $officers[$line[5]]['registered_address'] = $addressLinks[$line[5]];
        }
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
while ($line = fgetcsv($fh, 4096)) {
    if (isset($line[20]) && $line[20] === 'Panama Papers' && preg_match('/(TWN|HKG|CHN)/i', $line[15])) {
        $line = array_combine($header, $line);
        $line['officers'] = array();
        if (isset($ref[$line['node_id']])) {
            foreach ($ref[$line['node_id']] AS $node) {
                if (isset($officers[$node[0]])) {
                    if (!isset($line['officers'][$node[1]])) {
                        $line['officers'][$node[1]] = array();
                    }
                    $line['officers'][$node[1]][] = $officers[$node[0]];
                }
            }
        }
        $result[] = $line;
    }
}
file_put_contents(__DIR__ . '/twhkcn.json', json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
