<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\GameScore;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Ulid;

final class ShareScoreController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UrlGeneratorInterface $urls,
    ) {
    }

    public function __invoke(GameScore $data): JsonResponse
    {
        if (null === $data->getShareToken()) {
            $data->setShareToken(new Ulid()->toBase32());
            $this->em->flush();
        }

        $url = $this->urls->generate(
            'shared_score',
            ['shareToken' => $data->getShareToken()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        return new JsonResponse([
            'shareToken' => $data->getShareToken(),
            'shareUrl' => $url,
        ]);
    }
}
