<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Expediente;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class ExpedienteTest extends TestCase
{
    private Expediente $expediente;

    protected function setUp(): void
    {
        $this->expediente = new Expediente('09:00', '19:00', '12:00', '13:00');
    }

    public function test_servico_comum_que_encosta_no_intervalo_e_permitido(): void
    {
        $this->assertFalse($this->expediente->bloqueadoPeloIntervalo($this->as('11:00'), $this->as('12:00'), false));
        $this->assertFalse($this->expediente->bloqueadoPeloIntervalo($this->as('13:00'), $this->as('14:00'), false));
    }

    public function test_servico_comum_que_cruza_o_intervalo_e_bloqueado(): void
    {
        $this->assertTrue($this->expediente->bloqueadoPeloIntervalo($this->as('11:00'), $this->as('15:00'), false));
    }

    public function test_servico_que_pode_atravessar_passa_por_cima_do_intervalo(): void
    {
        $this->assertFalse($this->expediente->bloqueadoPeloIntervalo($this->as('10:00'), $this->as('16:00'), true));
    }

    public function test_servico_que_pode_atravessar_nao_comeca_dentro_do_intervalo(): void
    {
        $this->assertTrue($this->expediente->bloqueadoPeloIntervalo($this->as('12:00'), $this->as('16:00'), true));
        $this->assertTrue($this->expediente->bloqueadoPeloIntervalo($this->as('12:59'), $this->as('16:00'), true));
        $this->assertFalse($this->expediente->bloqueadoPeloIntervalo($this->as('13:00'), $this->as('17:00'), true));
    }

    public function test_sem_intervalo_nada_e_bloqueado(): void
    {
        $semIntervalo = new Expediente('09:00', '19:00');

        $this->assertFalse($semIntervalo->bloqueadoPeloIntervalo($this->as('12:00'), $this->as('18:00'), false));
    }

    private function as(string $hora): DateTimeImmutable
    {
        return new DateTimeImmutable("2030-01-10 {$hora}:00");
    }
}
