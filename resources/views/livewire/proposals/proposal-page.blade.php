<div class="mx-auto max-w-md p-4 space-y-6">
    @if($proposalState === 'queued')
        <div wire:poll.1500ms="poll" class="hidden"></div>
    @endif
    <h1 class="text-2xl font-semibold text-gray-900">今日の献立</h1>

    @if($proposalState === 'idle')
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-5 space-y-4">
            <p class="text-gray-700">冷蔵庫の中身から、今夜の献立を 3 つ提案します。</p>

            @if($this->inventoryItems->isNotEmpty())
                <div class="space-y-2">
                    <p class="text-xs font-medium text-gray-700">
                        絶対使いたい食材を選ぶ <span class="text-gray-500 font-normal">（任意・複数可）</span>
                    </p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($this->inventoryItems as $item)
                            @php
                                $isSelected = in_array($item->ingredient_id, $mustUseIngredientIds, true);
                                $isExpiringSoon = $item->expires_at && $item->expires_at->diffInDays(now(), false) >= -3;
                            @endphp
                            <button
                                type="button"
                                wire:click="toggleMustUse('{{ $item->ingredient_id }}')"
                                class="rounded-full px-3 py-1.5 text-xs font-medium border transition
                                    {{ $isSelected
                                        ? 'bg-emerald-600 text-white border-emerald-600'
                                        : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}"
                            >
                                {{ $isSelected ? '✓ ' : '' }}{{ $item->ingredient->displayName() }}@if($isExpiringSoon && ! $isSelected)<span class="text-amber-600 ml-1">!</span>@endif
                            </button>
                        @endforeach
                    </div>
                    @if(! empty($mustUseIngredientIds))
                        <p class="text-xs text-emerald-700">
                            {{ count($mustUseIngredientIds) }} 件を含むレシピを優先します。
                        </p>
                    @endif
                </div>
            @else
                <p class="text-xs text-gray-500">在庫が空です。先に <a href="/inventory" class="underline">冷蔵庫</a> に食材を追加してください。</p>
            @endif

            <button
                type="button"
                wire:click="request"
                @disabled($this->inventoryItems->isEmpty())
                class="w-full rounded-lg bg-emerald-600 text-white font-semibold py-3 hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >今日の献立を提案する</button>
        </div>
    @endif

    @if($proposalState === 'queued')
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-6 text-center space-y-3">
            <div class="inline-block animate-spin rounded-full size-8 border-4 border-gray-200 border-t-emerald-600"></div>
            <p class="text-gray-700">提案を生成中…</p>
            <p class="text-xs text-gray-500">数秒で結果が出ます</p>
        </div>
    @endif

    @if($proposalState === 'completed' && $this->proposal)
        <div class="space-y-4">
            @if(! empty($this->proposal->context_snapshot['must_use_fell_back']))
                <div class="rounded-lg bg-amber-50 ring-1 ring-amber-200 p-3 text-sm text-amber-800">
                    指定した食材を使うレシピが見つからなかったので、在庫マッチの良いものを表示しています。
                </div>
            @endif

            @foreach($this->proposal->candidates as $i => $cand)
                <article class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-4 space-y-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="inline-flex items-center justify-center rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold size-6">{{ $cand->rank }}</span>
                                @php
                                    $sourceLabel = $cand->recipe?->source_type === 'ai_generated' ? 'AI 生成' : '標準レシピ';
                                    $sourceClass = $cand->recipe?->source_type === 'ai_generated' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800';
                                @endphp
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-medium {{ $sourceClass }}">{{ $sourceLabel }}</span>
                            </div>
                            <h2 class="text-lg font-bold text-gray-900">{{ $cand->recipe?->title ?? ($cand->recipe_snapshot['title'] ?? '?') }}</h2>
                        </div>
                        <div class="shrink-0 text-right text-xs text-gray-500 space-y-0.5">
                            @if($cand->recipe?->total_cook_minutes)
                                <div>{{ $cand->recipe->total_cook_minutes }}分</div>
                            @endif
                            @php
                                $kcalValue = $cand->recipe?->nutrientValues
                                    ?->firstWhere('nutrient.code', 'energy_kcal')
                                    ?->value_per_serving;
                            @endphp
                            @if($kcalValue !== null)
                                <div class="font-semibold text-emerald-700">{{ round((float) $kcalValue) }} kcal</div>
                                <div class="text-[10px] text-gray-400">/ 人前</div>
                            @endif
                        </div>
                    </div>

                    @if($cand->reason_text)
                        <p class="text-sm text-gray-700">{{ $cand->reason_text }}</p>
                    @endif

                    @php $usedFromInventory = $cand->recipe_snapshot['used_from_inventory'] ?? []; @endphp

                    @if(! empty($usedFromInventory))
                        <div class="text-xs">
                            <p class="font-medium text-emerald-700 mb-1">🥗 冷蔵庫から使うもの（{{ count($usedFromInventory) }}品）</p>
                            <p class="text-emerald-700 leading-relaxed">
                                @foreach($usedFromInventory as $u)
                                    <span class="inline-block bg-emerald-50 px-2 py-0.5 rounded mr-1 mb-1">{{ $u['name'] ?? '?' }}</span>
                                @endforeach
                            </p>
                        </div>
                    @endif

                    @if(! empty($cand->missing_ingredients))
                        <div class="text-xs">
                            <p class="font-medium text-amber-700 mb-1">不足しているもの</p>
                            <p class="text-amber-700">
                                @foreach($cand->missing_ingredients as $m)
                                    <span class="inline-block bg-amber-50 px-2 py-0.5 rounded mr-1 mb-1">{{ $m['name'] ?? '?' }}</span>
                                @endforeach
                            </p>
                        </div>
                    @endif

                    @if($cand->recipe)
                        <details class="text-xs text-gray-600">
                            <summary class="cursor-pointer font-medium text-gray-700">材料・栄養を見る（{{ $cand->recipe->servings_default }}人前）</summary>
                            <div class="mt-2 space-y-2">
                                @if($cand->recipe->nutrientValues->isNotEmpty())
                                    <div class="grid grid-cols-4 gap-1 text-center text-[11px] bg-gray-50 rounded-lg p-2">
                                        @foreach(['protein_g'=>'P','fat_g'=>'F','carb_g'=>'C','sodium_mg'=>'Na'] as $code => $abbr)
                                            @php $v = $cand->recipe->nutrientValues->firstWhere('nutrient.code', $code); @endphp
                                            <div>
                                                <div class="text-gray-400">{{ $abbr }}</div>
                                                <div class="font-semibold">{{ $v ? round((float) $v->value_per_serving, 1) : '-' }}{{ $code === 'sodium_mg' ? 'mg' : 'g' }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <ul class="space-y-0.5">
                                    @foreach($cand->recipe->ingredients as $ri)
                                        <li class="flex justify-between">
                                            <span>{{ $ri->ingredient->displayName() }}</span>
                                            <span class="text-gray-500">{{ rtrim(rtrim((string) $ri->quantity, '0'), '.') }} {{ $ri->unit->label_ja }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </details>

                        @php $instructions = $cand->recipe->instructions_beginner ?: $cand->recipe->instructions; @endphp
                        @if($instructions)
                            <details class="text-xs text-gray-600">
                                <summary class="cursor-pointer font-medium text-gray-700">作り方を見る</summary>
                                <div class="mt-2 whitespace-pre-line leading-relaxed text-gray-700">{{ $instructions }}</div>
                            </details>
                        @endif
                    @endif

                    <div class="flex items-center justify-between pt-1">
                        <div class="text-[10px] text-gray-400">在庫マッチ度: {{ rtrim(rtrim((string) $cand->score, '0'), '.') }}</div>
                        @if($cand->recipe)
                            <button
                                type="button"
                                wire:click="adopt('{{ $cand->id }}')"
                                wire:confirm="この献立を採用しますか？在庫から必要分を減らします。"
                                class="rounded-lg bg-emerald-600 text-white text-sm font-semibold px-4 py-2 hover:bg-emerald-700"
                            >これにする</button>
                        @endif
                    </div>
                </article>
            @endforeach

            <div class="text-center pt-2">
                <button
                    type="button"
                    wire:click="resetProposal"
                    class="text-sm text-emerald-700 hover:text-emerald-900 underline"
                >もう一度提案する</button>
            </div>

            @if($this->proposal->candidates->isEmpty())
                <p class="text-center text-gray-500 py-8">候補が見つかりませんでした。在庫を増やしてから再試行してください。</p>
            @endif
        </div>
    @endif

    @if($proposalState === 'adopted')
        <div class="rounded-xl bg-emerald-50 ring-1 ring-emerald-200 p-5 space-y-3">
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6 text-emerald-600"><path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" /></svg>
                <h2 class="text-lg font-bold text-emerald-900">「{{ $adoptedRecipeTitle }}」採用しました</h2>
            </div>

            @if(! empty($adoptedConsumed))
                <div class="text-sm">
                    <p class="font-medium text-emerald-800 mb-1">冷蔵庫から消費したもの</p>
                    <ul class="text-emerald-700 space-y-0.5">
                        @foreach($adoptedConsumed as $c)
                            <li>・{{ $c['ingredient_name'] }} {{ rtrim(rtrim(number_format($c['used_base_quantity'], 2), '0'), '.') }} {{ $c['base_unit_label'] ?? '' }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(! empty($adoptedShortages))
                <div class="text-sm">
                    <p class="font-medium text-amber-800 mb-1">不足のため減らせなかった分</p>
                    <ul class="text-amber-700 space-y-0.5">
                        @foreach($adoptedShortages as $s)
                            <li>・{{ $s['ingredient_name'] }} {{ rtrim(rtrim(number_format($s['shortage_base'], 2), '0'), '.') }} {{ $s['base_unit_label'] ?? '' }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex gap-2 pt-2">
                <a href="/inventory" class="flex-1 text-center rounded-lg bg-white text-emerald-700 ring-1 ring-emerald-300 font-semibold py-2 hover:bg-emerald-100">冷蔵庫を見る</a>
                <button
                    type="button"
                    wire:click="resetProposal"
                    class="flex-1 rounded-lg bg-emerald-600 text-white font-semibold py-2 hover:bg-emerald-700"
                >別の献立を提案</button>
            </div>
        </div>
    @endif

    <nav class="text-center text-sm pt-4">
        <a href="/inventory" class="text-emerald-700 hover:text-emerald-900 underline">← 冷蔵庫に戻る</a>
    </nav>
</div>
