<?php
declare(strict_types=1);

namespace CardPay\Controllers;

use CardPay\Core\Csrf;
use CardPay\Core\Database;
use CardPay\Core\DomainException;
use CardPay\Core\Request;
use CardPay\Core\Response;
use CardPay\Core\Session;
use CardPay\Core\Url;
use CardPay\Core\View;
use CardPay\Services\AuditService;
use CardPay\Services\RateLimiter;

final class AuthController
{
    public function __construct(private readonly Database$db,private readonly View$view,private readonly Session$session,private readonly Csrf$csrf,private readonly RateLimiter$limiter,private readonly AuditService$audit){}
    public function show(Request$request):Response
    {
        if($this->session->get('user_id'))return Response::redirect(Url::to('/admin'));
        return new Response($this->view->render('auth/login',['title'=>'ورود مدیر','csrf'=>$this->csrf->token(),'flash'=>$this->session->pullFlash()],'layouts/guest'),200,['Content-Type'=>'text/html; charset=utf-8']);
    }
    public function login(Request$request):Response
    {
        if(!$this->csrf->valid((string)$request->input('_csrf')))throw new DomainException('csrf_mismatch','نشست منقضی شده است؛ دوباره تلاش کنید.',419);
        $username=mb_strtolower(trim((string)$request->input('username')));$this->limiter->hit('login',$request->ip.'|'.$username,5,300);
        $user=$this->db->fetch('SELECT * FROM '.$this->db->table('users').' WHERE username=? AND is_active=1',[$username]);
        if(!$user||!password_verify((string)$request->input('password'),$user['password_hash'])){$this->session->flash('danger','نام کاربری یا رمز عبور درست نیست.');return Response::redirect(Url::to('/login'));}
        if(password_needs_rehash($user['password_hash'],PASSWORD_DEFAULT))$this->db->execute('UPDATE '.$this->db->table('users').' SET password_hash=? WHERE id=?',[password_hash((string)$request->input('password'),PASSWORD_DEFAULT),$user['id']]);
        $this->session->regenerate();$this->session->put('user_id',(int)$user['id']);$this->session->put('user_name',$user['name']);$this->session->put('user_role',$user['role']);$this->db->execute('UPDATE '.$this->db->table('users').' SET last_login_at=?,last_login_ip=? WHERE id=?',[gmdate('Y-m-d H:i:s'),$request->ip,$user['id']]);$this->audit->record('admin.login','user',$user['id'],[],[],(int)$user['id']);return Response::redirect(Url::to('/admin'));
    }
    public function logout(Request$request):Response
    {
        if(!$this->csrf->valid((string)$request->input('_csrf')))throw new DomainException('csrf_mismatch','Invalid CSRF token.',419);$id=(int)$this->session->get('user_id',0);if($id)$this->audit->record('admin.logout','user',$id,[],[],$id);$this->session->destroy();return Response::redirect(Url::to('/login'));
    }
}
