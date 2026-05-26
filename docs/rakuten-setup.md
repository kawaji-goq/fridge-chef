# 楽天レシピ API セットアップ

> Rakuten Developers に無料登録し、ApplicationId と AccessKey を取得して `.env` に設定する手順。
>
> 注: 楽天の認証モデルは 2026 年時点で OpenAPI 方式（applicationId + accessKey）に刷新済み。旧 19 桁 applicationId のみの公開系 API は非推奨。

## 1. 楽天 ID を持っていない場合

[楽天会員登録](https://member.id.rakuten.co.jp/rms/nid/registp) で無料アカウントを作成。

## 2. Rakuten Developers にアプリ登録

1. <https://webservice.rakuten.co.jp/> にアクセス
2. 楽天 ID でログイン
3. 右上「アプリ ID 発行」
4. アプリ情報を入力:

| 項目 | 入力例 |
|---|---|
| アプリ名 | fridge-chef（任意） |
| アプリケーションタイプ | Web アプリケーション |
| アプリ URL | 本番ドメイン（例: `http://fridge-chef.bug-sandbox.com`） |
| 許可された Web サイト | `*.bug-sandbox.com` 等のワイルドカード |
| API アクセススコープ | **楽天レシピ API** を選択（必須）。楽天市場 API 等も併用可 |
| 利用目的 | 自宅の冷蔵庫管理アプリで楽天レシピを表示するため |

5. 「規約に同意してアプリ ID 発行」
6. 発行されたアプリの「詳細を見る」/「編集」から **applicationId (UUID)** と **アクセスキー (`pk_...` で始まる文字列)** をコピー

## 3. `.env` に追記

`/opt/fridge-chef/.env`（本番）または `/Users/yusaku/Documents/GitHub/fridge-chef/.env`（dev）の末尾に追加:

```env
RAKUTEN_APP_ID=ここにUUID
RAKUTEN_ACCESS_KEY=ここに pk_ で始まるアクセスキー
```

`.env` は AI から読めないので、エディタで直接編集してください。

## 4. コンテナ再起動

dev:
```bash
./vendor/bin/sail restart
```

本番:
```bash
docker compose -f compose.yaml -f compose.prod.yaml restart laravel.test queue scheduler
```

## 5. 動作確認

直接 curl でテスト（applicationId と accessKey を置換）:

```
curl -s "https://openapi.rakuten.co.jp/recipems/api/Recipe/CategoryList/20170426?applicationId=YOUR_APP_ID&accessKey=YOUR_ACCESS_KEY&categoryType=large&format=json" | head -c 400
```

`{"result":{"large":[{"categoryId":"10","categoryName":"人気メニュー",...` のような JSON が返れば OK。

`{"error_description":"specify valid applicationId","error":"wrong_parameter"}` が返る場合は applicationId/accessKey の誤りか、Rakuten 側のアプリスコープに「楽天レシピ API」が含まれていない。

## 6. 少量クロールで試す

```
docker compose -f compose.yaml -f compose.prod.yaml exec laravel.test php artisan recipes:crawl-rakuten --limit=3
```

成功すると:
```
カテゴリ一覧を取得中…
カテゴリ 3 件を巡回します…
完了: 12 件 upsert / 0 カテゴリで取得失敗
```

確認:
```
docker compose -f compose.yaml -f compose.prod.yaml exec laravel.test php artisan tinker --execute='dump(App\Models\Recipe::where("source_type","rakuten")->count());'
```

## 7. 全カテゴリでクロール（約 50 件 × 4 = 200 件）

```
docker compose -f compose.yaml -f compose.prod.yaml exec laravel.test php artisan recipes:crawl-rakuten
```

楽天規約により 1 秒間隔なので **約 1 分** かかります。

## 8. 日次自動実行

`routes/console.php` で日次 3:00 JST に登録済み:

```php
Schedule::command('recipes:crawl-rakuten')->dailyAt('03:00');
```

本番では `compose.prod.yaml` の `scheduler` サービスで `php artisan schedule:work` が常駐し、これを毎分チェックして時刻が来たら実行します。

## 9. 楽天 API 利用上の注意

- **無料**で商用利用も可能（要規約遵守）
- **1 秒以上の間隔** をあけてリクエスト（既に実装済み）
- **出典表示** が必須（`attribution_label = '楽天レシピ'` で UI 表示済み）
- **取得できる項目**: title, recipeUrl, image, materials（文字列リスト）, indication（調理時間）等
- **取得できない**: 構造化された材料（だから「在庫照合」はできず、楽天レシピは検索 + 出典リンクのみ）

## 10. トラブルシューティング

| 症状 | 原因 | 対処 |
|---|---|---|
| `RAKUTEN_APP_ID と RAKUTEN_ACCESS_KEY が .env に設定されていません` | 環境変数未設定 | `.env` に両方追記して再起動 |
| `wrong_parameter` / `specify valid applicationId` | applicationId or accessKey 不正、もしくはアプリのスコープに楽天レシピ API が含まれていない | Rakuten Developers でアプリ設定を確認 |
| `REQUEST_CONTEXT_BODY_HTTP_REFERRER_MISSING` (403) | Referer ヘッダが Rakuten 登録の「許可された Web サイト」と一致していない | `.env` の `APP_URL` を本番ドメインに設定（クライアントは `APP_URL` を Referer に送る） |
| カテゴリ別ランキングが 0 件 | カテゴリ ID 形式の不一致 | API は時々仕様変更があるためログ確認 |
