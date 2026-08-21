<?php
declare(strict_types=1);
namespace CardPay\Tests\Unit;
use CardPay\Core\DomainException;use CardPay\Services\PaymentStateMachine;use PHPUnit\Framework\TestCase;
final class PaymentStateMachineTest extends TestCase
{
    public function test_allows_controlled_transitions():void{$s=new PaymentStateMachine();self::assertTrue($s->can('pending','paid'));self::assertTrue($s->can('expired','paid'));self::assertFalse($s->can('paid','expired'));}
    public function test_paid_is_terminal():void{$this->expectException(DomainException::class);(new PaymentStateMachine())->assert('paid','expired');}
}
