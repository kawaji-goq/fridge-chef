# 技術スタック選定（MVP）

> 2026-05 時点。**個人利用前提**（1〜5 ユーザー）でコスト最小化。
> ユーザーは PHP エンジニア（フロントは推奨優先）。DB は Docker の MySQL を採用。

## 1. 推奨スタック（一覧）

| レイヤ | 採用 | 理由 |
|---|---|---|
| バックエンド | **Laravel 11**（PHP） | 本職言語、Bedrock SDK for PHP あり、バッチ/CLI/Web 全部いける |
| フロント | **Laravel Livewire 3 + Alpine.js + Tailwind CSS** | **ほぼ全部 PHP で書ける**、JS フレームワーク学習不要 |
| DB | **MySQL 8（Docker）** | ユーザー指定。Laravel との相性◎、JSON 型でメタ保持可 |
| LLM | **AWS Bedrock / Claude Haiku 4.5** | 個人利用ではコスト最優先。品質不足を感じたら Sonnet 4.6 へ昇格 |
| PWA 化 | **`laravel-pwa` パッケージ**（or 自前マニフェスト） | スマホでホーム追加可能、軽量 |
| バッチ | **Laravel Schedule + cron** | 楽天 API 日次クロール用、追加サービス不要 |
| ホスティング | **AWS Lightsail（または EC2 t4g.micro）** | $3.5〜$7/月。Laravel ＋ MySQL ＋ cron が 1 台で完結 |
| ファイル | **S3** | レシピ画像・将来の OCR 用 |
| IaC | **後回し（必要になったら Terraform）** | 個人利用では手動構築でも十分 |
| CI/CD | **GitHub Actions（簡易デプロイ）** | テスト＋ Lightsail への自動 SSH デプロイ |
| 認証 | **MVP は自前匿名 ID**（Cookie / localStorage UUID） | ログインなし方針 |
| 言語 | **PHP 8.3 + 最低限の JS** | フロントの JS は Alpine.js の宣言的記述のみ |
| リージョン | **ap-northeast-1 (Tokyo)** | Bedrock Cross-Region Inference for Japan |

---

## 2. なぜ Laravel + Livewire か（PHP エンジニア視点）

**Livewire は「PHP で動的 UI を書く」フレームワーク**。

書き味のイメージ：
```php
// app/Livewire/InventoryList.php
class InventoryList extends Component {
    public $items = [];

    public function mount() {
        $this->items = InventoryItem::where('user_id', auth()->id())->get();
    }

    public function delete($id) {
        InventoryItem::find($id)->delete();
        $this->items = InventoryItem::where('user_id', auth()->id())->get();
    }

    public function render() {
        return view('livewire.inventory-list');
    }
}
```

```blade
{{-- resources/views/livewire/inventory-list.blade.php --}}
<div>
  @foreach($items as $item)
    <div>{{ $item->name }} <button wire:click="delete({{ $item->id }})">削除</button></div>
  @endforeach
</div>
```

- **React も Vue も書かなくていい**
- ボタンクリック → サーバ往復で UI 更新（裏で AJAX、開発者は意識しない）
- スマホで重要な「リアクティブ感」も `wire:loading` 等で表現可能

**他案との比較**:
| 案 | 採用判定 | コメント |
|---|---|---|
| **Laravel + Livewire** | ◎ 採用 | PHP オンリー、学習コスト最小 |
| Laravel + Inertia + Vue 3 | △ | Vue を新規に学ぶ。React より優しいが学習コストあり |
| Laravel + Inertia + React | △ | フロント完全フレームワーク学習が必要 |
| Next.js（フルスタック JS） | × | PHP 経験を捨てる、AI 支援は強いが学習コスト大 |
| Laravel + Filament | △ | 管理画面はラクだが、エンドユーザー向け UI には不向き |

**結論**: Livewire 一択。AI（Claude/Copilot）も Livewire コードを問題なく書ける。

---

## 3. なぜ DB が MySQL でいいか（データモデルの調整点）

ユーザー指定の MySQL でも `data-model.md` の設計はほぼそのまま使える。差分のみ：

| PostgreSQL 想定 | MySQL での代替 |
|---|---|
| `JSONB` カラム | `JSON` 型（MySQL 8 はインデックスも可能） |
| `pg_trgm` であいまい検索 | MySQL 全文検索（`FULLTEXT INDEX` + `MATCH AGAINST`）or 単純 LIKE |
| `UUID` 型 | `CHAR(36)` or `BINARY(16)`（Laravel は `Str::uuid()` で対応） |
| `TIMESTAMPTZ` | `TIMESTAMP`（タイムゾーンはアプリ側で UTC 統一） |
| `NUMERIC` | `DECIMAL` |

> MVP の食材検索は 2,538 件しかないので、全文検索すら不要で LIKE で十分。

---

## 4. なぜ Bedrock Claude Haiku 4.5 か（コスト試算）

**個人利用スケール再試算**（5 ユーザー × 月 30 提案 = 150 req/月）

| モデル | 入力料金 | 出力料金 | 月額試算 |
|---|---|---|---|
| **Haiku 4.5** | $0.80 / M | $4 / M | **約 $2/月** |
| Sonnet 4.6 | $3 / M | $15 / M | 約 $7/月 |
| Opus 4.6 | $5 / M | $25 / M | 約 $12/月 |

> 1 リクエスト = 5K input + 2K output トークン想定。Cross-Region Inference for Japan の +10% 込み。

**最初は Haiku で出して、提案品質に不満があれば Sonnet に切り替え**でいい。
モデル切替は Laravel の Bedrock クライアントを 1 行変えるだけ。

---

## 5. 月額コスト試算（個人利用）

| 項目 | 月額 | 備考 |
|---|---|---|
| Lightsail（2GB / 60GB SSD）| **$10** | Laravel + MySQL + cron 同居の標準プラン |
| or Lightsail（512MB / 20GB） | $3.5 | 最小プラン。MySQL は別途調整が必要 |
| Bedrock (Haiku 4.5) | **$2** | 5 ユーザー × 30 提案/月 |
| S3 | $0.5 | レシピ画像少量 |
| ドメイン（独自運用なら） | $1 | お名前.com / Route53 等 |
| 楽天 API | $0 | 無料 |
| Vercel / Amplify | $0 | 使わない（Lightsail で完結） |
| **合計** | **約 $14/月**（最小構成 $7） | スケールしても線形 |

> 商用サービスでの $70〜100/月 見積もりは過剰でした。個人利用なら 1 桁ドル〜2 桁ドル前半で収まります。

---

## 6. アーキテクチャ概観

```
[ユーザー / スマホブラウザ]
        │ HTTPS
        ▼
┌──────────────────────────────────────┐
│  AWS Lightsail（または EC2 t4g.micro）│
│                                       │
│  ┌──────────────────────────────┐   │
│  │ Nginx + PHP-FPM              │   │
│  │   Laravel 11                 │   │
│  │   + Livewire (UI)            │   │
│  │   + Bedrock SDK for PHP      │   │
│  │   + 楽天 API クライアント   │   │
│  └──────────────────────────────┘   │
│                                       │
│  ┌──────────────────────────────┐   │
│  │ MySQL 8（Docker または直接） │   │
│  └──────────────────────────────┘   │
│                                       │
│  cron → php artisan schedule:run     │
└──────────────────────────────────────┘
            │
            ├─→ AWS Bedrock (Claude Haiku 4.5)
            ├─→ 楽天レシピ API
            └─→ S3 (画像)
```

シンプル。**Lambda も API Gateway もマネージド DB も使わない**。

---

## 7. 開発・デプロイの流れ

### 開発
- ローカル：`docker compose up` で MySQL + Mailpit など起動、PHP/Laravel は Valet/Herd or `php artisan serve`
- AI 支援：Claude Code が Livewire/Laravel コードを書ける（実績あり）

### デプロイ
- 案 A（推奨）：**Lightsail に Laravel Forge** を繋いで自動デプロイ
- 案 B：GitHub Actions + SSH で `rsync` + `php artisan migrate`
- 案 C：手動 SSH（一番安いが原始的）

### 監視
- Laravel Pulse（公式の軽量モニタリング）
- CloudWatch は Bedrock 料金監視用に最低限設定

---

## 8. 提案 API の同期/非同期（決定保留）

ユーザー回答で「非同期（ジョブ ID + SSE/ポーリング）」が選ばれていますが、Laravel + Livewire の場合は実装が少し違います：

**Livewire の場合のおすすめ**:
- **同期＋ローディング UI** で十分（30 秒以内に終わるならこれが最も簡単）
- 30 秒を超えそうなら：**Laravel Queue + Livewire ポーリング**（`wire:poll`）
  - 提案リクエスト → ジョブを Queue に放り込む → Livewire が 2 秒ごとにポーリングして完了を検出
  - 非同期感は出るが、実装は SSE より楽

> 個人利用なら、Haiku で 5 秒前後で返るはず。MVP は同期で開始し、遅延が問題になったらキューに逃がす方針を推奨。

---

## 9. 決定事項（2026-05-25）

| 論点 | 決定 | 含意 |
|---|---|---|
| 提案 API の作り | **Queue + Livewire ポーリング**（最初から） | `wire:poll` でジョブ完了を検知。30 秒制限を気にしないで済む、提案中の進捗 UI も出しやすい |
| Bedrock モデル | **Claude Haiku 4.5 を起点** | 月 $2 規模。品質に不満が出たら Sonnet 4.6 へ。切替はクライアントコード 1 行 |
| ホスティング | **既存 EC2 に Docker Compose で同居** | Docker 既導入、t3.small 以上で余力あり、既存サービスは止まっても OK の条件が揃っている。追加コストは Bedrock と S3 のみで月 $3 程度 |

### 9.1 既存 EC2 同居の構成

確認結果：
- Docker は導入済
- t3.small 以上で余力あり
- 既存サービスは個人実験／趣味、停止してもOK

→ **fridge-chef を既存 EC2 上の Docker Compose スタックとして同居** で決定。

#### コンテナ構成案

```yaml
# docker-compose.yml （イメージ）
services:
  app:        # PHP 8.3 + Laravel + Nginx
    image: fridge-chef-app:latest
    ports: ["8080:80"]    # 既存サービスとポート競合しない値を選ぶ
    environment:
      DB_HOST: db
      AWS_REGION: ap-northeast-1
      BEDROCK_MODEL: anthropic.claude-haiku-4-5
    depends_on: [db, queue]

  db:         # MySQL 8
    image: mysql:8
    volumes: ["./mysql-data:/var/lib/mysql"]
    environment:
      MYSQL_DATABASE: fridge_chef

  queue:      # Laravel Queue worker
    image: fridge-chef-app:latest
    command: php artisan queue:work
    depends_on: [db]

  scheduler:  # Laravel cron (楽天 API 日次クロール等)
    image: fridge-chef-app:latest
    command: php artisan schedule:work
    depends_on: [db]
```

#### 既存サービスとの分離ポイント

- **ポート**: リバプロ（Nginx/Caddy/Traefik）で `fridge-chef.example.com` を 8080 にルーティング、既存サービスは別ポートのまま
- **ボリューム**: `./mysql-data` を fridge-chef 専用ディレクトリに分離
- **IAM 権限**: 既存サービスのロールに Bedrock 権限を足さず、**専用 IAM ユーザー**を作って AWS アクセスキーを `.env` 経由で渡す（最小権限：`bedrock:InvokeModel` のみ）
- **AWS リージョン**: ap-northeast-1（東京）に固定、Bedrock Cross-Region Inference を利用

#### 追加コスト（既存 EC2 のインスタンス代は除く）

| 項目 | 月額 |
|---|---|
| Bedrock (Haiku 4.5, 5 ユーザー × 30 提案/月) | $2 |
| S3（レシピ画像等少量） | $0.5 |
| ドメイン（既存ドメインのサブドメイン使えば $0） | $0〜1 |
| **追加合計** | **約 $3/月** |

EC2 のインスタンス代は既に発生中のため**実質追加コストはほぼ Bedrock のみ**。
