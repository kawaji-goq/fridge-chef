<?php

namespace App\Services\Recipes;

/**
 * テキスト形式の材料を構造化データに変換する（ルールベース）。
 * 典型的なクックパッド/楽天形式に対応：
 *   "豚バラ肉 300g" → name=豚バラ肉, qty=300, unit=g
 *   "醤油 大さじ3"  → name=醤油, qty=3, unit=大さじ
 *   "☆みりん 大さじ2"→ name=みりん, qty=2, unit=大さじ
 *   "じゃがいも (中) 3個" → name=じゃがいも, qty=3, unit=個
 *   "卵 1/2個" → name=卵, qty=0.5, unit=個
 */
class MaterialParser
{
    /** 認識する単位ラベル（label_ja）。長いものを先に並べる（部分一致の衝突を防ぐ） */
    private const UNIT_LABELS = ['大さじ', '小さじ', 'カップ', 'パック', 'kg', 'ml', '袋', '合', 'g', 'l', '個'];

    /**
     * 複数行をパース。
     *
     * @return array<int, array{name: string, quantity: ?float, unit_label: ?string, raw: string}>
     */
    public function parseMultiline(string $text): array
    {
        $lines = preg_split('/\r?\n/', $text);
        $result = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $result[] = $this->parseLine($line);
        }

        return $result;
    }

    /**
     * 1 行をパース。失敗時も raw を含む形で返す（パース成否は quantity/unit_label の存在で判定）
     *
     * @return array{name: string, quantity: ?float, unit_label: ?string, raw: string}
     */
    public function parseLine(string $raw): array
    {
        // 先頭の装飾記号を除去
        $line = preg_replace('/^[★●☆◯◎▲▼※・■□◆◇▪▫\s]+/u', '', $raw) ?? $raw;
        // 括弧内（中・小・正味 等）を除去
        $line = preg_replace('/[（(].*?[)）]/u', ' ', $line) ?? $line;
        $line = trim($line);

        foreach (self::UNIT_LABELS as $unit) {
            // パターン 1: 名前 + 単位 + 数量（"醤油 大さじ3"）
            if (preg_match('/^(.+?)\s*'.preg_quote($unit, '/').'\s*(\d+(?:[\/\.]\d+)?)\s*$/u', $line, $m)) {
                return [
                    'name' => trim($m[1]),
                    'quantity' => $this->parseNumber($m[2]),
                    'unit_label' => $unit,
                    'raw' => $raw,
                ];
            }
            // パターン 2: 名前 + 数量 + 単位（"じゃがいも 3個"、"豚バラ肉 300g"）
            if (preg_match('/^(.+?)\s*(\d+(?:[\/\.]\d+)?)\s*'.preg_quote($unit, '/').'(?:\s|$)/u', $line, $m)) {
                return [
                    'name' => trim($m[1]),
                    'quantity' => $this->parseNumber($m[2]),
                    'unit_label' => $unit,
                    'raw' => $raw,
                ];
            }
        }

        // 単位が判定できない場合：数量だけ取り出して unit_label = null で返す
        if (preg_match('/^(.+?)\s*(\d+(?:[\/\.]\d+)?)\s*$/u', $line, $m)) {
            return [
                'name' => trim($m[1]),
                'quantity' => $this->parseNumber($m[2]),
                'unit_label' => null,
                'raw' => $raw,
            ];
        }

        // 完全に解析できない: 名前だけ返す（"塩 少々" など）
        return [
            'name' => $line,
            'quantity' => null,
            'unit_label' => null,
            'raw' => $raw,
        ];
    }

    private function parseNumber(string $s): ?float
    {
        if (str_contains($s, '/')) {
            [$num, $den] = explode('/', $s, 2);
            $den = (float) $den;
            if ($den == 0.0) {
                return null;
            }

            return (float) $num / $den;
        }

        return (float) $s;
    }
}
