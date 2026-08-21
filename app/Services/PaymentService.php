<?php
declare(strict_types=1);

namespace CardPay\Services;

use CardPay\Core\Config;
use CardPay\Core\Database;
use CardPay\Core\DomainException;
use CardPay\Core\Id;
use CardPay\Repositories\CardRepository;
use CardPay\Repositories\PaymentRepository;
use PDOException;

final class PaymentService
{
    public function __construct(
        private readonly Database $db,
        private readonly CardRepository $cards,
        private readonly PaymentRepository $payments,
        private readonly TokenAllocator $tokens,
        private readonly PaymentStateMachine $states,
        private readonly WebhookService $webhooks,
        private readonly Config $config,
    ) {}

    public function create(array $application, array $input, ?string $idempotencyKey): array
    {
        $amount = filter_var($input['amount'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        if ($amount === false) throw new DomainException('invalid_amount', 'Amount must be a positive integer.', 422);
        if (!$idempotencyKey || strlen($idempotencyKey) < 8 || strlen($idempotencyKey) > 190) throw new DomainException('validation_failed', 'A valid Idempotency-Key header is required.', 422, ['idempotency_key'=>['required']]);
        $cardId = (int) ($input['bank_card_id'] ?? $application['default_bank_card_id'] ?? 0);
        $card = $this->cards->find($cardId, true);
        if (!$card) throw new DomainException('invalid_bank_card', 'The selected bank card is inactive or unavailable.', 422);
        $returnUrl = $input['return_url'] ?? $application['callback_url'] ?? null;
        if ($returnUrl !== null && !$this->validUrl((string) $returnUrl)) throw new DomainException('validation_failed', 'Return URL is invalid.', 422);
        if ($returnUrl !== null && !$this->allowedDomain((string)$returnUrl,(string)($application['allowed_domains']??''))) throw new DomainException('validation_failed','Return URL domain is not allowed for this application.',422);
        $callbackUrl = $input['callback_url'] ?? null;
        if ($callbackUrl !== null && !$this->validUrl((string) $callbackUrl)) throw new DomainException('validation_failed', 'Callback URL is invalid.', 422);
        if ($callbackUrl !== null && !$this->allowedDomain((string)$callbackUrl,(string)($application['allowed_domains']??''))) throw new DomainException('validation_failed','Callback URL domain is not allowed for this application.',422);
        $hash = hash('sha256', json_encode($input, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION));
        $result = $this->db->transaction(function () use ($application,$input,$idempotencyKey,$hash,$amount,$cardId,$returnUrl,$callbackUrl): array {
            $idem = $this->db->table('idempotency_keys');
            $existing = $this->db->fetch("SELECT * FROM {$idem} WHERE application_id=? AND idempotency_key=?", [$application['id'],$idempotencyKey]);
            if ($existing) {
                if (!hash_equals((string)$existing['request_hash'],$hash)) throw new DomainException('idempotency_conflict','Idempotency key was already used with a different request.',409);
                if ($existing['payment_id']) return ['payment'=>$this->db->fetch('SELECT * FROM '.$this->db->table('payments').' WHERE id=?',[$existing['payment_id']]),'replayed'=>true];
            } else {
                try { $this->db->insert("INSERT INTO {$idem} (application_id,idempotency_key,request_hash,expires_at,created_at) VALUES (?,?,?,?,?)",[$application['id'],$idempotencyKey,$hash,gmdate('Y-m-d H:i:s',time()+86400),gmdate('Y-m-d H:i:s')]); }
                catch(PDOException){ $row=$this->db->fetch("SELECT * FROM {$idem} WHERE application_id=? AND idempotency_key=?",[$application['id'],$idempotencyKey]); if($row&&$row['payment_id'])return ['payment'=>$this->db->fetch('SELECT * FROM '.$this->db->table('payments').' WHERE id=?',[$row['payment_id']]),'replayed'=>true]; throw new DomainException('idempotency_in_progress','An identical request is being processed.',409); }
            }
            $reservation=$this->tokens->allocate($cardId,(int)$amount,(int)$application['token_digits']);
            $now=gmdate('Y-m-d H:i:s');$expires=gmdate('Y-m-d H:i:s',time()+((int)$application['payment_expiration_minutes']*60));$customer=is_array($input['customer']??null)?$input['customer']:[];
            $paymentId=$this->db->insert('INSERT INTO '.$this->db->table('payments').' (public_id,application_id,bank_card_id,driver,external_order_id,original_amount,token,payable_amount,currency,description,customer_name,customer_mobile,customer_reference,status,expires_at,return_url,callback_url,metadata_json,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',[
                Id::prefixed('PAY'),$application['id'],$cardId,'card_transfer',$input['external_order_id']??null,$amount,$reservation['token'],$reservation['payable_amount'],$input['currency']??$this->config->get('app.currency','IRR'),$input['description']??null,$customer['name']??null,$customer['mobile']??null,$customer['reference']??null,'pending',$expires,$returnUrl,$callbackUrl,json_encode($input['metadata']??[],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),$now,$now,
            ]);
            $this->tokens->bindPayment($reservation['id'],$paymentId);
            $this->db->execute("UPDATE {$idem} SET payment_id=? WHERE application_id=? AND idempotency_key=?",[$paymentId,$application['id'],$idempotencyKey]);
            return ['payment'=>$this->db->fetch('SELECT * FROM '.$this->db->table('payments').' WHERE id=?',[$paymentId]),'replayed'=>false];
        });
        $full=$this->payments->findPublic((string)$result['payment']['public_id'])??$result['payment'];
        if(!$result['replayed']){$this->webhooks->emit($full,'payment.created');$this->webhooks->processDue(1);}
        return $this->present($full,(bool)$result['replayed']);
    }

    public function status(array $application,string $publicId): array
    {
        $payment=$this->payments->findForApplication($publicId,(int)$application['id']);
        if(!$payment)throw new DomainException('payment_not_found','Payment was not found.',404);
        return $this->present($payment,false);
    }
    public function verify(array $application,string $publicId): array { return $this->status($application,$publicId); }
    public function cancel(array $application,string $publicId): array
    {
        $payment=$this->payments->findForApplication($publicId,(int)$application['id']);
        if(!$payment)throw new DomainException('payment_not_found','Payment was not found.',404);
        $this->states->assert((string)$payment['status'],'canceled');
        if(!$this->payments->transition((int)$payment['id'],(string)$payment['status'],'canceled',['canceled_at'=>gmdate('Y-m-d H:i:s')]))throw new DomainException('payment_cannot_be_canceled','Payment changed concurrently.',409);
        $this->tokens->cooldown((int)$payment['id'],(int)$this->config->get('payment.token_cooldown_minutes',10));
        $full=$this->payments->findPublic($publicId);$this->webhooks->emit($full,'payment.canceled');$this->webhooks->processDue(1);
        return $this->present($full,false);
    }
    public function present(array $payment,bool $replayed=false): array
    {
        $url=rtrim((string)$this->config->get('app.url',''),'/').'/p/'.$payment['public_id'];
        return ['payment_id'=>$payment['public_id'],'status'=>$payment['status'],'original_amount'=>(int)$payment['original_amount'],'token'=>(int)$payment['token'],'payable_amount'=>(int)$payment['payable_amount'],'currency'=>$payment['currency'],'payment_url'=>$url,'expires_at'=>gmdate('c',strtotime((string)$payment['expires_at'].' UTC')),'paid_at'=>$payment['paid_at']?gmdate('c',strtotime((string)$payment['paid_at'].' UTC')):null,'idempotent_replay'=>$replayed];
    }
    private function validUrl(string $url): bool { return filter_var($url,FILTER_VALIDATE_URL)!==false&&in_array(parse_url($url,PHP_URL_SCHEME),['http','https'],true); }
    private function allowedDomain(string$url,string$allowed):bool{$domains=array_values(array_filter(array_map(fn($v)=>mb_strtolower(trim($v)),preg_split('/[,\n]+/u',$allowed)?:[])));if($domains===[])return true;$host=mb_strtolower((string)parse_url($url,PHP_URL_HOST));foreach($domains as$domain){$domain=preg_replace('#^https?://#','',$domain);$domain=trim((string)$domain,'/');if($host===$domain||str_ends_with($host,'.'.$domain))return true;}return false;}
}
