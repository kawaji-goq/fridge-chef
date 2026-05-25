# AWS / Bedrock セットアップガイド

> fridge-chef 専用の IAM ユーザーを新規作成し、Claude Haiku 4.5 を Tokyo（ap-northeast-1）から呼び出せるようにする手順。

## 1. IAM ユーザー作成

### 1.1 マネジメントコンソール

1. [AWS Console / IAM](https://console.aws.amazon.com/iam/) を開く
2. 左メニュー「ユーザー」→「ユーザーを作成」
3. ユーザー名: `fridge-chef-app`
4. 「Provide access to the AWS Management Console」は **チェックしない**（プログラム用なので Console アクセス不要）
5. 「次へ」

### 1.2 アクセス権限の付与

1. 「ポリシーを直接アタッチする」を選択
2. 「ポリシーの作成」ボタン（別タブが開く）
3. JSON タブで以下を貼り付け：

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Sid": "InvokeClaudeOnBedrock",
      "Effect": "Allow",
      "Action": [
        "bedrock:InvokeModel",
        "bedrock:InvokeModelWithResponseStream"
      ],
      "Resource": [
        "arn:aws:bedrock:*::foundation-model/anthropic.claude-*",
        "arn:aws:bedrock:*:*:inference-profile/*anthropic.claude-*"
      ]
    },
    {
      "Sid": "ListBedrockResources",
      "Effect": "Allow",
      "Action": [
        "bedrock:ListFoundationModels",
        "bedrock:ListInferenceProfiles",
        "bedrock:GetInferenceProfile"
      ],
      "Resource": "*"
    },
    {
      "Sid": "AwsMarketplaceForBedrock",
      "Effect": "Allow",
      "Action": [
        "aws-marketplace:ViewSubscriptions",
        "aws-marketplace:Subscribe"
      ],
      "Resource": "*"
    }
  ]
}
```

> Anthropic モデルは AWS Marketplace 経由で配信されるため、Marketplace 権限も必要です。

1. ポリシー名: `FridgeChefBedrockInvoke`
2. 作成

戻って、`fridge-chef-app` ユーザーに `FridgeChefBedrockInvoke` をアタッチ → 「次へ」→「ユーザーの作成」

### 1.3 アクセスキー発行

1. 作成した `fridge-chef-app` を開く
2. 「セキュリティ認証情報」タブ → 「アクセスキーを作成」
3. ユースケース: 「コマンドラインインターフェイス (CLI)」または「ローカルコード」
4. 「Access key ID」と「Secret access key」をメモ（**Secret はこの画面でしか見られない**）

---

## 2. Bedrock モデルアクセスを有効化

Bedrock のモデルは、リージョンごとに「アクセスリクエスト → 承認」が必要。

1. [Bedrock コンソール](https://console.aws.amazon.com/bedrock/) を開く
2. リージョンを「**アジアパシフィック (東京) ap-northeast-1**」に切り替え
3. 左メニュー「Model access」（モデルアクセス）
4. 「Modify model access」→ 以下にチェック：
    - `Anthropic / Claude Haiku 4.5`
    - `Anthropic / Claude Sonnet 4.6`（将来用、有効化しておくと楽）
5. 「Submit」または「Save changes」
6. 通常は数分以内に「Access granted」になる

### 2.1 Cross-Region Inference プロファイルの確認

東京リージョンから Claude Haiku 4.5 を呼ぶには、**Cross-Region Inference Profile** を使う必要がある。
モデル ID は次のような形式：

```text
apac.anthropic.claude-haiku-4-5-20251001-v1:0
```

正確な ID は Bedrock コンソールの「Inference profiles」タブで確認できる。

---

## 3. `.env` への記入

ローカルの `.env` ファイルに以下を追記：

```env
AWS_ACCESS_KEY_ID=AKIA****************
AWS_SECRET_ACCESS_KEY=****************************************
AWS_DEFAULT_REGION=ap-northeast-1

BEDROCK_REGION=ap-northeast-1
BEDROCK_MODEL_ID=jp.anthropic.claude-haiku-4-5-20251001-v1:0
```

> `.env` は `.gitignore` 済みなので、コミットされません。
> 注：日本リージョン向け Inference Profile は `jp.` プレフィックス。`apac.` ではないので注意。

### 3.1 追記コマンド（このマシンでの追記方法）

このマシンの権限制限で `.env` の中身は AI からは読めません。以下を `!` プレフィックスで実行してください：

```bash
cat >> /Users/yusaku/Documents/GitHub/fridge-chef/.env <<'EOF'

AWS_ACCESS_KEY_ID=ここにアクセスキー
AWS_SECRET_ACCESS_KEY=ここにシークレット
AWS_DEFAULT_REGION=ap-northeast-1

BEDROCK_REGION=ap-northeast-1
BEDROCK_MODEL_ID=jp.anthropic.claude-haiku-4-5-20251001-v1:0
EOF
```

---

## 4. SDK インストール

AWS SDK for PHP を入れる（バックグラウンドで実行中の場合あり）。

```bash
./vendor/bin/sail composer require aws/aws-sdk-php
```

---

## 5. 動作確認（次のステップで実装予定）

簡単な `artisan tinker` テスト：

```php
$client = new \Aws\BedrockRuntime\BedrockRuntimeClient([
    'region' => env('BEDROCK_REGION'),
    'version' => 'latest',
]);

$response = $client->invokeModel([
    'modelId' => env('BEDROCK_MODEL_ID'),
    'contentType' => 'application/json',
    'accept' => 'application/json',
    'body' => json_encode([
        'anthropic_version' => 'bedrock-2023-05-31',
        'max_tokens' => 100,
        'messages' => [
            ['role' => 'user', 'content' => 'こんにちは、簡単な献立を1つ提案してください。']
        ],
    ]),
]);

echo $response['body']->getContents();
```

「こんにちは」に対する応答が返ってくれば認証 OK。

---

## 6. コスト管理（重要）

### 6.1 月額アラートの設定

1. [Billing コンソール](https://console.aws.amazon.com/billing/)
2. 「Budgets」→「予算を作成」
3. 月額予算: **$10〜20**（個人利用なら十分）
4. アラート閾値: 80%, 100% で通知メール

### 6.2 想定料金（再掲）

- Claude Haiku 4.5 + Cross-Region: 約 $2/月（5 ユーザー × 30 提案/月）
- Sonnet 4.6 に切替えても約 $7/月

### 6.3 暴走防止

- アプリ側でも `proposals` テーブルにレート制限（同一ユーザーは 1 時間に N 回まで等）を Phase2 で入れる予定
- MVP では呼び出し回数のログを CloudWatch メトリクスで監視

---

## 7. トラブルシューティング

| 症状 | 原因 | 対処 |
| --- | --- | --- |
| `AccessDeniedException` | モデルアクセス未承認 | Bedrock コンソールで Model access を再確認 |
| `ValidationException: ... model not found` | Cross-Region Inference Profile を使っていない | `apac.` プレフィックス付きの inference profile ID を使う |
| `InvalidSignatureException` | アクセスキー/シークレットの誤り | `.env` を確認、コピペミスがないか |
| 料金が想定より多い | Sonnet/Opus を誤って指定 | `BEDROCK_MODEL_ID` を Haiku に戻す |
