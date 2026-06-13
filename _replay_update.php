<?php
// Self-contained: logs in as admin, parses the real edit form, replays it with an image upload.
$cookie = sys_get_temp_dir().'/gryt_replay_cookies.txt';
@unlink($cookie);
$png = sys_get_temp_dir().'/gryt_replay.png';
$im=imagecreatetruecolor(64,64); imagefill($im,0,0,imagecolorallocate($im,200,40,40)); imagepng($im,$png);
$base = 'http://localhost:8000';
$slug = 'whey-protein-concentrate';

function req($url, $cookie, $post=null, $follow=false) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER=>true, CURLOPT_COOKIEFILE=>$cookie, CURLOPT_COOKIEJAR=>$cookie,
        CURLOPT_FOLLOWLOCATION=>$follow, CURLOPT_IPRESOLVE=>CURL_IPRESOLVE_V4,
    ]);
    if ($post !== null) { curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, $post); }
    $body = curl_exec($ch);
    $err = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $loc = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    curl_close($ch);
    if ($err) echo "curl error: $err\n";
    return [$body, $code, $loc];
}
function token($html, $name='_token') {
    if (preg_match('/name="'.$name.'"\s+value="([^"]+)"/', $html, $m)) return $m[1];
    if (preg_match('/name="csrf-token"\s+content="([^"]+)"/', $html, $m)) return $m[1];
    return '';
}

// 1) login
[$login,$c1] = req("$base/admin/login", $cookie);
$tok = token($login);
[$b,$code,$loc] = req("$base/admin/login", $cookie, ['_token'=>$tok,'email'=>'admin@example.com','password'=>'password']);
echo "login: $code -> $loc\n";

// 2) get edit page
[$html,$ec] = req("$base/admin/products/$slug/edit", $cookie);
echo "edit page: $ec, bytes ".strlen($html)."\n";

$doc = new DOMDocument(); @$doc->loadHTML($html); $xp = new DOMXPath($doc);
$fields = [];
foreach ($xp->query('//form') as $form) {
    if (strpos($form->getAttribute('action'), "/products/$slug") === false) continue;
    foreach ($xp->query('.//input', $form) as $in) {
        $n=$in->getAttribute('name'); if($n==='')continue;
        $t=strtolower($in->getAttribute('type'));
        if(in_array($t,['file','submit','button']))continue;
        if(in_array($t,['checkbox','radio'])&&!$in->hasAttribute('checked'))continue;
        $fields[$n]=$in->getAttribute('value');
    }
    foreach ($xp->query('.//textarea', $form) as $ta){ $n=$ta->getAttribute('name'); if($n)$fields[$n]=$ta->textContent; }
    foreach ($xp->query('.//select', $form) as $sel){ $n=$sel->getAttribute('name'); if(!$n)continue; $v=''; foreach($xp->query('.//option',$sel) as $o){ if($o->hasAttribute('selected')){$v=$o->getAttribute('value');break;} } $fields[$n]=$v; }
    break;
}
echo "parsed ".count($fields)." fields\n";

$post = $fields;
$post['_method']='PUT';
$post['main_image'] = new CURLFile($png,'image/png','replay.png');
[$resp,$uc,$ul] = req("$base/admin/products/$slug", $cookie, $post);
echo "UPDATE: $uc -> $ul\n";
if ($uc != 302) {
    if (preg_match('/<title>([^<]*)<\/title>/',$resp,$m)) echo "title: ".trim($m[1])."\n";
    echo "text: ".substr(preg_replace('/\s+/',' ',strip_tags($resp)),0,500)."\n";
}
