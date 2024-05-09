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
use Twint\Core\Util\CryptoHandler;
use Twint\Core\Util\PemExtractor;

#[Package('checkout')]
#[Route(defaults: [
    '_routeScope' => ['api'],
])]
class TwintController extends AbstractController
{
    private CryptoHandler $encryptor;

    public function setEncryptor(CryptoHandler $encryptor): void
    {
        $this->encryptor = $encryptor;
    }

    #[Route(path: '/api/_actions/twint/extract-pem', name: 'api.action.twint.extract_pem', methods: ['POST'])]
    public function extractPem(Request $request, Context $context): Response
    {
        $file = $request->files->get('file');
        $password = $request->get('password') ?? '';

        if ($file instanceof UploadedFile) {
            $content = file_get_contents($file->getPathname());

            $extractor = new PemExtractor();
            $certificate = $extractor->extractFromPKCS12((string) $content, $password);
            if (is_array($certificate)) {
                return $this->json([
                    'success' => true,
                    'message' => 'Extract certificate successfully',
                    'data' => [
                        'cert' => $this->encryptor->setPassphraseForPemCertificate($certificate['cert']),
                        'pkey' => $this->encryptor->encrypt($certificate['pkey']),
                    ],
                ]);
            }

            return $this->json([
                'success' => false,
                'message' => 'Invalid certificate file',
                'errorCode' => $certificate,
            ], 400);
        }

        return $this->json([
            'success' => false,
            'message' => 'Please upload an valid file',
        ], 400);
    }
}
