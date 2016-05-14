<?php

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
    $name = strtolower(preg_replace('/[^a-z]/i', '', $line[0]));
    if (!isset($officers[$name])) {
        $officers[$name] = array();
    }
    $officers[$name][$line[5]] = $line[6];
}

$jsons = array('2012.json', '2014.json', '2016.json');
$fh = fopen(__DIR__ . '/match.csv', 'w');
fputcsv($fh, array('公職', '中文姓名', '英文姓名', 'node_id', '選舉黃頁網址'));
foreach ($jsons AS $jsonFile) {
    $json = json_decode(file_get_contents(__DIR__ . '/' . $jsonFile), true);
    foreach ($json AS $item) {
        $nameFound = false;
        if (!empty($item['names'])) {
            foreach ($item['names'] AS $name) {
                if (!in_array(count($name), array(2, 3))) {
                    continue;
                }
                if (false === $nameFound) {
                    $nameKey = strtolower($name[0] . $name[1] . (isset($name[2]) ? $name[2] : ''));
                    if (isset($officers[$nameKey])) {
                        $nameFound = implode(',', $name);
                    }
                }
                if (false === $nameFound) {
                    $nameKey = strtolower($name[1] . (isset($name[2]) ? $name[2] : '') . $name[0]);
                    if (isset($officers[$nameKey])) {
                        $nameFound = implode(',', $name);
                    }
                }
            }
        }
        if (false !== $nameFound) {
            foreach ($item['candidates'] AS $candidate) {
                $icij = array();
                foreach ($officers[$nameKey] AS $nodeId => $source) {
                    $icij[] = "[{$source}]{$nodeId}";
                }
                fputcsv($fh, array("[{$candidate[0]}]{$candidate[1]}", $candidate[2], $nameFound, implode('|', $icij), $candidate[3]));
            }
        }
    }
}