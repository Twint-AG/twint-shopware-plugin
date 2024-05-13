<?php declare(strict_types=1);

namespace Twint\Tests\Core\Setting\Validator;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Twint\Core\Setting\Validator\CertificateFileValidator;

class CertificateFileValidatorTest extends TestCase
{
    use IntegrationTestBehaviour;

    private CertificateFileValidator $certificateFileValidator;

    /**
     * @return string
     */
    static function getName()
    {
        return "CertificateFileValidatorTest";
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->certificateFileValidator = new CertificateFileValidator();
    }
    public function testValidate(): void
    {
        $file = new UploadedFile(dirname(__DIR__, 3).'/_fixture/test.p12', 'test.p12', null, null, true);
        static::assertTrue($this->certificateFileValidator->validate($file));
    }
    public function testFailValidate(): void
    {
        $file = new UploadedFile(dirname(__DIR__, 3).'/_fixture/test.p13', 'test.p13', null, null, true);
        static::assertFalse($this->certificateFileValidator->validate($file));
    }
}