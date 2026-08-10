<?php

declare(strict_types=1);

namespace App\Rate\Controller;

use App\Http\Request;
use App\Http\Response;
use App\Rate\Domain\Entity\CurrencyCollection;
use App\Rate\Domain\Interface\CurrencyRepositoryInterface;

final readonly class CurrencyController
{
    public function __construct(private CurrencyRepositoryInterface $repository)
    {
    }

    public function index(Request $request): Response
    {
        $currencies = $this->repository->getAll();

        return Response::json($this->mapCurrenciesToArray($currencies));
    }

    private function mapCurrenciesToArray(CurrencyCollection $currencies): array
    {
        $result = [];

        foreach ($currencies as $currency) {
            $result[] = [
                'code' => $currency->code,
                'name' => $currency->name,
            ];
        }

        return $result;
    }
}
