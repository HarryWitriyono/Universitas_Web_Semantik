<?php
require_once __DIR__.'/role_helper.php';
require_once __DIR__.'/csrf.php';
if($_SERVER['REQUEST_METHOD']!=='POST'){header('Location: role.php');exit;}
try{
    csrf_verify();
    $id=(int)($_POST['id_role']??0);
    if($id<=0)throw new RuntimeException('ID role tidak valid.');
    $response=role_soa_call('delete',['target_id_role'=>$id]);
    if(!empty($response['error'])||($response['data']['success']??false)!==true)
        throw new RuntimeException(role_soa_message($response,'Gagal menghapus role.'));
    $_SESSION['success']=role_soa_message($response,'Role berhasil dihapus.');
}catch(Throwable $e){$_SESSION['error']=$e->getMessage();}
header('Location: role.php');exit;
