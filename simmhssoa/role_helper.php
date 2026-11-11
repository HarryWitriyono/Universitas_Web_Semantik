<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/api_client.php';
wajib_role(['ADMIN']);

function role_soa_call(string $action,array $payload=[]):array{
    $payload['action']=$action;
    $payload['id_pengguna']=(int)($_SESSION['id_pengguna']??0);
    return soa_request('apirole.php',$payload);
}
function role_soa_message(array $r,string $fallback):string{
    if(!empty($r['error']))return (string)$r['error'];
    return (string)($r['data']['message']??$fallback);
}
function role_soa_rows(array $r,string $key):array{
    if(!empty($r['error'])||($r['data']['success']??false)!==true)return [];
    $rows=$r['data']['data'][$key]??[];
    return is_array($rows)?$rows:[];
}
function role_h($v):string{return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
