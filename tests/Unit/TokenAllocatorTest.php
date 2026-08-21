<?php
declare(strict_types=1);
namespace CardPay\Tests\Unit;
use CardPay\Core\DomainException;use CardPay\Tests\TestCase;
final class TokenAllocatorTest extends TestCase
{
    public function test_tokens_and_payable_amounts_are_unique():void{$seen=[];for($i=0;$i<25;$i++){$r=$this->tokens->allocate($this->cardId,80000000,3);self::assertSame(80000000+$r['token'],$r['payable_amount']);self::assertArrayNotHasKey($r['payable_amount'],$seen);$seen[$r['payable_amount']]=true;}}
    public function test_two_digit_pool_exhaustion_is_explicit():void{for($i=0;$i<99;$i++)$this->tokens->allocate($this->cardId,90000000,2);$this->expectException(DomainException::class);$this->expectExceptionMessage('No payment token');$this->tokens->allocate($this->cardId,90000000,2);}
}
