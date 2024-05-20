<?php

declare(strict_types=1);

namespace Twint\Administration\Controller;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twint\Core\Util\CertificateHandler;
use Twint\Core\Util\CredentialValidatorInterface;
use Twint\Core\Util\CryptoHandler;
use Twint\Sdk\Certificate\Pkcs12Certificate;

#[Package('checkout')]
#[Route(defaults: [
    '_routeScope' => ['api'],
])]
class TwintController extends AbstractController
{
    private CryptoHandler $encryptor;

    private CredentialValidatorInterface $validator;

    public function setEncryptor(CryptoHandler $encryptor): void
    {
        $this->encryptor = $encryptor;
    }

    public function setValidator(CredentialValidatorInterface $validator): void
    {
        $this->validator = $validator;
    }

    #[Route(path: '/api/_actions/twint/extract-pem', name: 'api.action.twint.extract_pem', methods: ['POST'])]
    public function extractPem(Request $request, Context $context): Response
    {
        $file = $request->files->get('file');
        $password = $request->get('password') ?? '';

        if ($file instanceof UploadedFile) {
            $content = file_get_contents($file->getPathname());

            $extractor = new CertificateHandler();
            $certificate = $extractor->read((string) $content, $password);

            if ($certificate instanceof Pkcs12Certificate) {
                return $this->json([
                    'success' => true,
                    'message' => 'Certificate validation successful ',
                    'data' => [
                        'certificate' => $this->encryptor->encrypt($certificate->content()),
                        'passphrase' => $this->encryptor->encrypt($certificate->passphrase()),
                    ],
                ]);
            }

            return $this->json([
                'success' => false,
                'message' => 'Invalid certificate file ',
                'errorCode' => $certificate,
            ], 400);
        }

        return $this->json([
            'success' => false,
            'message' => 'Please upload a valid certificate file ',
        ], 400);
    }

    #[Route(
        path: '/api/_actions/twint/validate-api-credential',
        name: 'api.action.twint.validate_credential',
        methods: ['POST']
    )]
    public function validate(Request $request, Context $context): Response
    {
        $certificate = $request->get('cert') ?? [];
        $merchantId = $request->get('merchantId') ?? '';
        $testMode = $request->get('testMode') ?? false;

        $valid = $this->validator->validate($certificate, $merchantId, $testMode);

        return $this->json([
            'success' => $valid,
        ]);
    }
}
