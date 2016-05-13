<?php

$nameKeys = array();
$cachePath = __DIR__ . '/cache';
if (!file_exists($cachePath)) {
    mkdir($cachePath, 0777, true);
}

$candidates = array();
$fh = fopen('/home/kiang/public_html/elections/Console/Command/data/2016.csv', 'r');
while ($line = fgetcsv($fh, 2048)) {
    if (!isset($candidates[$line[2]])) {
        $candidates[$line[2]] = array(
            'candidates' => array(),
            'names' => array(),
        );
    }
    $candidates[$line[2]]['candidates'][] = $line;
}

$names = array_keys($candidates);

$nameParts = array();
$nameCount = 0;
foreach ($names AS $name) {
    if (preg_match('/[a-z]/i', $name)) {
        continue;
    }
    ++$nameCount;
    switch (mb_strlen($name, 'utf-8')) {
        case 3:
            $nameParts[] = implode(',', preg_split('/(?<!^)(?!$)/u', $name));
            break;
        case 2:
            $nameParts[] = implode(',', preg_split('/(?<!^)(?!$)/u', $name));
            break;
        case 4:
            $parts = preg_split('/(?<!^)(?!$)/u', $name);
            $nameParts[] = implode(',', array(
                $parts[0] . $parts[1],
                $parts[2],
                $parts[3],
            ));
            break;
    }
    if ($nameCount == 100) {
        $q = urlencode(implode(';', $nameParts));
        $cachedFile = $cachePath . '/' . md5($q);
        $content = '';
        if (file_exists($cachedFile)) {
            $content = file_get_contents($cachedFile);
            if (false !== strpos($content, '網頁不存在!請重新確認網址')) {
                unlink($cachedFile);
                $content = '';
            }
        }
        if (empty($content)) {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, 'https://www.boca.gov.tw/sp.asp?xdURL=E2C/c2102-5.asp&CtNode=677&mp=1&type=B');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, 'namelist=' . urlencode(implode(';', $nameParts)));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

            $content = curl_exec($ch);
            file_put_contents($cachedFile, $content);

            curl_close($ch);
        }
        $nameParts = array();
        $nameCount = 0;
        if (!empty($content)) {
            extractNames($content);
        }
    }
}
$q = urlencode(implode(';', $nameParts));
$cachedFile = $cachePath . '/' . md5($q);
$content = '';
if (file_exists($cachedFile)) {
    $content = file_get_contents($cachedFile);
    if (false !== strpos($content, '網頁不存在!請重新確認網址')) {
        unlink($cachedFile);
        $content = '';
    }
}
if (empty($content)) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, 'https://www.boca.gov.tw/sp.asp?xdURL=E2C/c2102-5.asp&CtNode=677&mp=1&type=B');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'namelist=' . urlencode(implode(';', $nameParts)));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

    $content = curl_exec($ch);
    file_put_contents($cachedFile, $content);

    curl_close($ch);
}
extractNames($content);
$nameParts = array();
$nameCount = 0;

function extractNames($c) {
    global $nameKeys;
    $blocks = explode('</table>', $c);
    foreach ($blocks AS $block) {
        if (false === strpos($block, '查詢姓名：')) {
            continue;
        }
        $block = str_replace('查詢姓名：', '', $block);
        $blockData = array();
        $lines = explode('</tr>', $block);
        foreach ($lines AS $line) {
            $cols = explode('</span>', $line);
            foreach ($cols AS $k => $v) {
                $cols[$k] = trim(strip_tags($v));
            }
            if (count($cols) > 1) {
                $blockData[] = $cols;
            }
        }
        $pos = strpos($blockData[0][0], '(');
        if (false !== $pos) {
            $name = substr($blockData[0][0], 0, $pos);
            if (!isset($nameKeys[$name])) {
                $nameKeys[$name] = array();
            }
            foreach ($blockData AS $k => $item) {
                if ($k > 0) {
                    $parts = preg_split('/[,\\-]/', $item[1]);
                    foreach ($parts AS $b => $part) {
                        $parts[$b] = trim($part);
                    }
                    $nameKeys[$name][implode(',', $parts)] = $parts;
                }
            }
        }
    }
}

foreach ($candidates AS $name => $data) {
    if (isset($nameKeys[$name])) {
        $candidates[$name]['names'] = $nameKeys[$name];
    }
}

file_put_contents(__DIR__ . '/2016.json', json_encode($candidates, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
