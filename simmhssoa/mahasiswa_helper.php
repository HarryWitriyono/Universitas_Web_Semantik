<?php
require_once __DIR__.'/auth_check.php';require_once __DIR__.'/api_client.php';
function mahasiswa_soa(string $action,array $payload=[]):array{$payload['action']=$action;$payload['id_pengguna']=(int)($_SESSION['id_pengguna']??0);return soa_request('apimahasiswa.php',$payload);}
function mahasiswa_data(array $r,string $key):array{return (($r['data']['success']??false)===true && is_array($r['data']['data'][$key]??null))?$r['data']['data'][$key]:[];}
function mahasiswa_h($v):string{return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
