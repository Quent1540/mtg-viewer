<?php

namespace App\Controller;

use App\Entity\Card;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use OpenApi\Attributes as OA;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/card', name: 'api_card_')]
#[OA\Tag(name: 'Card', description: 'Routes for cards')]
class ApiCardController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {
    }

    #[Route('/all', name: 'list_all', methods: ['GET'])]
    #[OA\Get(description: 'Retourne une liste de cartes paginée (100 par page par defaut)')]
    #[OA\Parameter(name: 'page', in: 'query', required: false, description: 'Page number', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'limit', in: 'query', required: false, description: 'Results per page', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'setCode', in: 'query', required: false, description: 'Filter by set code', schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Paginated list of cards')]
    public function cardAll(Request $request): Response
    {
        $this->logger->info('API: cardAll');

        $page = max(1, (int)$request->query->get('page', 1));
        $limit = max(1, min(500, (int)$request->query->get('limit', 100))); // default 100
        $setCode = $request->query->get('setCode');

        $result = $this->entityManager->getRepository(Card::class)->searchWithFilters(null, $setCode, $page, $limit);
        $data = array_map(fn(Card $c) => $c->jsonSerialize(), $result['results']);

        return $this->json([
            'data' => $data,
            'total' => $result['total'],
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    #[Route('/search', name: 'search', methods: ['GET'])]
    #[OA\Get(description: 'Search cards by name (min 3 chars) + optional setCode; quick search returns up to 20 results')]
    #[OA\Parameter(name: 'name', in: 'query', required: false, description: 'Partial name to search', schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'setCode', in: 'query', required: false, description: 'Filter by set code', schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'page', in: 'query', required: false, description: 'Page number', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'limit', in: 'query', required: false, description: 'Results per page', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Search result (data, total, page, limit)')]
    public function search(Request $request): Response
    {
        $name = $request->query->get('name');
        $setCode = $request->query->get('setCode');

        $this->logger->info('API: search', ['name' => $name, 'setCode' => $setCode]);

        if ($name !== null && mb_strlen((string)$name) < 3) {
            return $this->json(['data' => [], 'total' => 0, 'page' => 1, 'limit' => 20]);
        }

        //recherche rapide si seulement le nom est fourni, avec une limite de 20 resultats
        if ($name !== null && ($setCode === null || $setCode === '')) {
            $cards = $this->entityManager->getRepository(Card::class)->searchByName((string)$name, 20);
            $data = array_map(fn(Card $c) => $c->jsonSerialize(), $cards);
            return $this->json(['data' => $data, 'total' => count($data), 'page' => 1, 'limit' => 20]);
        }

        $page = max(1, (int)$request->query->get('page', 1));
        $limit = max(1, min(500, (int)$request->query->get('limit', 20)));

        $result = $this->entityManager->getRepository(Card::class)->searchWithFilters($name, $setCode, $page, $limit);
        $data = array_map(fn(Card $c) => $c->jsonSerialize(), $result['results']);

        return $this->json([
            'data' => $data,
            'total' => $result['total'],
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    #[Route('/sets', name: 'sets', methods: ['GET'])]
    #[OA\Get(description: 'Return distinct set codes')]
    #[OA\Response(response: 200, description: 'List of set codes')]
    public function sets(): Response
    {
        $this->logger->info('API: sets');
        $sets = $this->entityManager->getRepository(Card::class)->getDistinctSetCodes();
        return $this->json($sets);
    }

    #[Route('/{uuid}', name: 'show_card', methods: ['GET'])]
    #[OA\Parameter(name: 'uuid', in: 'path', required: true, description: 'UUID of the card', schema: new OA\Schema(type: 'string'))]
    #[OA\Get(description: 'Get a card by UUID')]
    #[OA\Response(response: 200, description: 'Show card')]
    #[OA\Response(response: 404, description: 'Card not found')]
    public function cardShow(string $uuid): Response
    {
        $this->logger->info('API: cardShow', ['uuid' => $uuid]);
        $card = $this->entityManager->getRepository(Card::class)->findOneBy(['uuid' => $uuid]);
        if (!$card) {
            $this->logger->warning('Card not found', ['uuid' => $uuid]);
            return $this->json(['error' => 'Card not found'], 404);
        }
        return $this->json($card);
    }
}
