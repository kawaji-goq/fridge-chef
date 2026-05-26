# 本番デプロイ手順

対象環境：
- 既存 EC2（Ubuntu、Docker / Docker Compose 導入済）
- ホスト側 Nginx で別ドメインを既に運用中
- ドメイン: `fridge-chef.bug-sandbox.com`
- 親ドメイン `bug-sandbox.com` の DNS は本人が管理

## 1. DNS

`bug-sandbox.com` の DNS 管理画面で A レコード追加:

| 種別 | ホスト | 値 | TTL |
|---|---|---|---|
| A | `fridge-chef` | EC2 の Elastic IP | 300 |

確認:

```bash
dig +short fridge-chef.bug-sandbox.com
# → EC2 IP が返ればOK
```

伝播待ち（数分〜数十分）。

---

## 2. ソース配置

EC2 にログインして:

```bash
sudo mkdir -p /opt/fridge-chef
sudo chown ubuntu:ubuntu /opt/fridge-chef
cd /opt/fridge-chef

# リポジトリ clone（feature/mvp ブランチをそのまま使う or main にマージ後）
git clone <REPO_URL> .
# git checkout main
```

> もし GitHub プライベート repo なら、デプロイ用の SSH キーを EC2 に置いて `git@github.com:...` で clone。

---

## 3. `.env` 作成

`docs/env.production.example` をコピーして編集:

```bash
cp docs/env.production.example .env
chmod 600 .env
nano .env
```

最低限変更が必要な値:

| 項目 | 値 |
|---|---|
| `APP_KEY` | 後で `key:generate` で生成 |
| `APP_URL` | `https://fridge-chef.bug-sandbox.com` |
| `DB_PASSWORD` | 強めのランダム文字列 |
| `AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY` | IAM ユーザー `fridge-chef-app` の値 |
| `RAKUTEN_APP_ID` | 楽天 19 桁 |
| `APP_PORT` | **既存サイトと被らない** ホストポート（例: 8090） |

`APP_PORT` 競合確認:

```bash
sudo lsof -iTCP -sTCP:LISTEN | grep -E ':(80|443|8080|8090)'
```

---

## 4. ビルド・初期化（コンテナ起動前）

Composer / npm はホストに入れなくても、Sail のイメージを使って実行できる:

```bash
# 一度だけビルド用に sail コンテナを上げる
docker compose -f compose.yaml -f compose.prod.yaml up -d laravel.test mysql redis

# composer 依存（dev は除外）
docker compose exec laravel.test composer install --no-dev --optimize-autoloader

# フロントアセット
docker compose exec laravel.test npm install
docker compose exec laravel.test npm run build

# APP_KEY 生成（.env に書き込まれる）
docker compose exec laravel.test php artisan key:generate

# キャッシュ
docker compose exec laravel.test php artisan config:cache
docker compose exec laravel.test php artisan route:cache
docker compose exec laravel.test php artisan view:cache
```

---

## 5. データベース初期化

```bash
# マイグレーション + マスタ Seeder（食材・栄養・標準レシピ 38 件）
docker compose exec laravel.test php artisan migrate --seed --force
```

---

## 6. AI 初心者向け作り方を一括生成（任意）

`BEDROCK_DRIVER=real` なので、本番では実際に Bedrock を叩く。
1 回だけ実行すれば 38 レシピ分の詳細手順が DB に保存される（約 13 円）:

```bash
docker compose exec laravel.test php artisan recipes:enhance-instructions
```

---

## 7. 全コンテナ起動

```bash
docker compose -f compose.yaml -f compose.prod.yaml up -d
docker compose ps
```

| サービス | 役割 |
|---|---|
| `laravel.test` | アプリ本体（127.0.0.1:8090 でホストに開く） |
| `mysql` | DB |
| `redis` | Cache / Session |
| `queue` | Queue Worker (`queue:work`) |
| `scheduler` | `schedule:work`（楽天 API 日次クロール等） |

`mailpit` は `dev-only` プロファイル付きなので本番では起動しない。

ローカル確認:

```bash
curl -I http://127.0.0.1:8090
# HTTP/1.0 200 OK が返ればコンテナは動いている
```

---

## 8. Nginx 設定

設定ファイルをコピー:

```bash
sudo cp /opt/fridge-chef/docs/nginx/fridge-chef.bug-sandbox.com.conf \
        /etc/nginx/sites-available/fridge-chef.bug-sandbox.com
sudo ln -s /etc/nginx/sites-available/fridge-chef.bug-sandbox.com \
           /etc/nginx/sites-enabled/
```

最初は HTTPS ブロックがコメントアウト or 失敗する状態（証明書未取得）。
**HTTP のみで先に試す**ためにファイル末尾の `server { listen 80; ... return 301 ... }` の中身を一時的に直接プロキシに変えるか、HTTP-only セクションを追加する:

```nginx
server {
    listen 80;
    server_name fridge-chef.bug-sandbox.com;

    location /.well-known/acme-challenge/ {
        root /var/www/certbot;
    }
    location / {
        proxy_pass http://127.0.0.1:8090;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

```bash
sudo nginx -t   # 構文チェック
sudo systemctl reload nginx
```

ブラウザで `http://fridge-chef.bug-sandbox.com` にアクセス。

---

## 9. SSL（Let's Encrypt）

certbot が入っていれば:

```bash
sudo certbot --nginx -d fridge-chef.bug-sandbox.com
```

→ 対話に従う。HTTPS ブロックは certbot が自動追記してくれる。

完了したら provided sample（`docs/nginx/...conf`）を改めてコピーして整える:

```bash
sudo cp /opt/fridge-chef/docs/nginx/fridge-chef.bug-sandbox.com.conf \
        /etc/nginx/sites-available/fridge-chef.bug-sandbox.com
sudo nginx -t
sudo systemctl reload nginx
```

---

## 10. 動作確認

ブラウザで:

- `https://fridge-chef.bug-sandbox.com/` → `/propose` にリダイレクト
- 「材料から」「料理から」「冷蔵庫」「マイレシピ」「履歴」「設定」のナビが表示
- 食材を 5〜10 個 追加 → 「献立を提案する」で 5 件出てくる

---

## 11. 再デプロイ（コード更新時）

```bash
cd /opt/fridge-chef
git pull origin main

# 依存とアセット
docker compose exec laravel.test composer install --no-dev --optimize-autoloader
docker compose exec laravel.test npm install
docker compose exec laravel.test npm run build

# DB マイグレーション
docker compose exec laravel.test php artisan migrate --force

# キャッシュ更新
docker compose exec laravel.test php artisan config:cache
docker compose exec laravel.test php artisan route:cache
docker compose exec laravel.test php artisan view:cache

# Queue Worker を再起動（コード変更を反映）
docker compose restart queue scheduler
```

スクリプト化（`scripts/deploy.sh`）するのは Phase2 候補。

---

## 12. 楽天レシピのクロール開始

`scheduler` コンテナが動いていれば、毎日午前 3 時に自動で走る。
最初に手動で 1 回流して感触を確認:

```bash
docker compose exec laravel.test php artisan recipes:crawl-rakuten --limit=5
# → 楽天レシピ約 20 件が DB に入り、/search で出てくる
```

OK なら全件:

```bash
docker compose exec laravel.test php artisan recipes:crawl-rakuten
# 約 45 秒（カテゴリ約 40 × 4 件）
```

---

## 13. 既存サイトとの分離ポイント

- **ポート**: 本サイトは `127.0.0.1:8090` のみ。他サイトと衝突しない値に
- **MySQL データ**: Docker named volume（`fridge-chef_sail-mysql`）で他サイトと完全分離
- **IAM 権限**: 既存ロールに足さず、専用 IAM ユーザー `fridge-chef-app` 経由
- **ログ**: `/opt/fridge-chef/storage/logs/laravel.log` ＋ Docker logs
- **バックアップ**: `docker compose exec mysql mysqldump ...` を別途 cron 化推奨

---

## 14. トラブルシューティング

| 症状 | 原因 | 対処 |
|---|---|---|
| HTTP 500 | APP_KEY 未生成 | `php artisan key:generate` |
| Session が維持されない | SESSION_DOMAIN / SECURE_COOKIE 不一致 | `.env` の `SESSION_DOMAIN`, `SESSION_SECURE_COOKIE` を確認 |
| 画像/CSS が読めない | `npm run build` 未実行 / `APP_URL` mismatch | ビルド & APP_URL を https フル URL に |
| Bedrock AccessDenied | IAM 反映待ち or use case form 未提出 | 2 分待って再試行、または `docs/aws-setup.md` 確認 |
| Cookie が `Secure` で送信されない | アプリが http と認識 | `trustProxies` 設定済みか、Nginx の X-Forwarded-Proto が来てるか確認 |
| 楽天 API 失敗 | RAKUTEN_APP_ID 未設定 | `.env` 確認、`sail restart` |
