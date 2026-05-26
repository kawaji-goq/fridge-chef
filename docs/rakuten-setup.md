# 楽天レシピ API セットアップ

> 楽天ウェブサービスに無料登録し、ApplicationId を取得して `.env` に設定する手順。

## 1. 楽天 ID を持っていない場合

[楽天会員登録](https://member.id.rakuten.co.jp/rms/nid/registp) で無料アカウントを作成。

## 2. 楽天ウェブサービスに開発者登録

1. <https://webservice.rakuten.co.jp/> にアクセス
2. 楽天 ID でログイン
3. 右上「アプリ ID 発行」または「マイページ」→「アプリ ID 発行」
4. アプリ情報を入力:

| 項目 | 入力例 |
|---|---|
| アプリ名 | fridge-chef |
| アプリ URL | `http://localhost`（個人利用なので localhost で OK） |
| 利用目的 | 自宅の冷蔵庫管理アプリで楽天レシピを表示するため |

5. 「規約に同意してアプリ ID 発行」
6. 発行された **applicationId**（数字 19 桁）をコピー

## 3. `.env` に追記

`/Users/yusaku/Documents/GitHub/fridge-chef/.env` の末尾に追加:

```env
RAKUTEN_APP_ID=ここに19桁の数字
```

`.env` は AI から読めないので、エディタで直接編集してください。

## 4. Sail 再起動

```bash
./vendor/bin/sail restart
```

## 5. 動作確認（手動クロール、少量で試す）

```bash
./vendor/bin/sail artisan recipes:crawl-rakuten --limit=3
```

成功すると:
```
カテゴリ一覧を取得中…
カテゴリ 3 件を巡回します…
完了: 12 件 upsert / 0 カテゴリで取得失敗
```

確認:
```bash
docker exec fridge-chef-mysql-1 mysql -uroot -ppassword laravel \
  -e "SELECT title, attribution_url FROM recipes WHERE source_type='rakuten' LIMIT 5;"
```

楽天レシピが DB に入っていれば成功。

## 6. 全カテゴリでクロール（約 40 件 × 4 = 160 件）

```bash
./vendor/bin/sail artisan recipes:crawl-rakuten
```

カテゴリ約 40 件 × 1 秒間隔なので **約 45 秒** かかります。

## 7. 日次自動実行

`routes/console.php` に既に登録済み:

```php
Schedule::command('recipes:crawl-rakuten')->dailyAt('03:00');
```

これを実際に毎日走らせるには、Laravel Schedule を動かす必要があります。

### 開発（Sail）の場合
`compose.yaml` の `scheduler` サービスを使うか、cron で `php artisan schedule:run` を毎分実行。

最も簡単なのは Sail の Schedule コンテナを追加:

```yaml
scheduler:
    image: 'sail-8.5/app'
    command: 'php artisan schedule:work'
    volumes:
        - '.:/var/www/html'
    networks:
        - sail
    depends_on:
        - mysql
```

これを `docker-compose.yaml` に追加すれば、`./vendor/bin/sail up -d` で自動起動。

`schedule:work` は `schedule:run` を毎分実行し続けます。

## 8. 楽天 API 利用上の注意

- **無料**で商用利用も可能
- **1 秒以上の間隔** をあけてリクエスト（既に実装済み）
- **出典表示** が必須（`attribution_label = '楽天レシピ'` で UI 表示済み）
- **取得できる項目**: title, recipeUrl, image, materials（文字列リスト）, indication（調理時間）等
- **取得できない**: 構造化された材料（だから「在庫照合」はできず、楽天レシピは検索 + 出典リンクのみ）

## 9. トラブルシューティング

| 症状 | 原因 | 対処 |
|---|---|---|
| `RAKUTEN_APP_ID が .env に設定されていません` | 環境変数未設定 | `.env` に追記して `sail restart` |
| `カテゴリ一覧の取得に失敗しました` | 無効な ID or ネットワーク | applicationId を再確認 |
| カテゴリ別ランキングが 0 件 | カテゴリ ID 形式の不一致 | API は時々仕様変更があるためログ確認 |
