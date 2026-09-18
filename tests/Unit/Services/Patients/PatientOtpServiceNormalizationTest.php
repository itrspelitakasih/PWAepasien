<?php

namespace Tests\Unit\Services\Patients;

use App\Services\Patients\PatientOtpService;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class PatientOtpServiceNormalizationTest extends TestCase
{
    #[TestWith(['08123456789', '628123456789'])]
    #[TestWith(['+628123456789', '628123456789'])]
    #[TestWith(['628123456789', '628123456789'])]
    #[TestWith(['0812-3456-789', '628123456789'])]
    #[TestWith(['8123456789', '628123456789'])]
    public function test_it_normalizes_indonesian_numbers_to_whatsapp_msisdn(string $raw, string $expected): void
    {
        $this->assertSame($expected, PatientOtpService::toWhatsAppId($raw));
    }

    #[TestWith([''])]
    #[TestWith(['abc'])]
    #[TestWith(['12'])]
    public function test_it_rejects_numbers_that_cannot_be_a_valid_msisdn(string $raw): void
    {
        $this->assertNull(PatientOtpService::toWhatsAppId($raw));
    }
}
