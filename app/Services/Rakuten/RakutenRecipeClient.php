<?php

namespace App\Services\Rakuten;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 楽天レシピ API クライアント (新 OpenAPI 仕様)。
 * - エンドポイント: https://openapi.rakuten.co.jp/recipems/api/Recipe
 * - 認証: applicationId (UUID) + accessKey
 * - カテゴリ一覧: CategoryList
 * - カテゴリ別ランキング: CategoryRanking（4 件/カテゴリ）
 * - レート制限: 1 秒以上の間隔（規約）
 */
class RakutenRecipeClient
{
    private const BASE = 'https://openapi.rakuten.co.jp/recipems/api/Recipe';

    private const CATEGORY_LIST_VERSION = '20170426';

    private const CATEGORY_RANKING_VERSION = '20170426';

    public function __construct(
        private readonly string $applicationId,
        private readonly string $accessKey,
    ) {}

    /**
     * カテゴリ一覧を取得。
     * 戻り値の各カテゴリは ['categoryId' => '10|123', 'categoryName' => '...', 'categoryUrl' => '...']
     */
    public function categoryList(string $categoryType = 'large'): array
    {
        $resp = Http::get(self::BASE.'/CategoryList/'.self::CATEGORY_LIST_VERSION, [
            'applicationId' => $this->applicationId,
            'accessKey' => $this->accessKey,
            'categoryType' => $categoryType,
            'format' => 'json',
        ]);
        if (! $resp->successful()) {
            Log::warning('Rakuten CategoryList failed', ['status' => $resp->status(), 'body' => $resp->body()]);

            return [];
        }

        return $resp->json('result.'.$categoryType, []);
    }

    /**
     * カテゴリ別ランキング（4 件）を取得。
     * categoryId 形式: large.medium または large.medium.small（例: "10-100"）
     */
    public function categoryRanking(string $categoryId): array
    {
        $resp = Http::get(self::BASE.'/CategoryRanking/'.self::CATEGORY_RANKING_VERSION, [
            'applicationId' => $this->applicationId,
            'accessKey' => $this->accessKey,
            'categoryId' => $categoryId,
            'format' => 'json',
        ]);
        if (! $resp->successful()) {
            Log::warning('Rakuten CategoryRanking failed', [
                'categoryId' => $categoryId,
                'status' => $resp->status(),
                'body' => $resp->body(),
            ]);

            return [];
        }

        return $resp->json('result', []);
    }
}
