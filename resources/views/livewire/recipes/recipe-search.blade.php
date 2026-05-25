<div class="mx-auto max-w-md p-4 space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-gray-900">料理から探す</h1>
        <a href="/inventory" class="text-sm text-emerald-700 hover:text-emerald-900 underline">← 冷蔵庫へ</a>
    </div>

    @if(! $this->selectedRecipe)
        <section class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-4 space-y-3">
            <p class="text-sm text-gray-700">作りたい料理を入力すると、冷蔵庫の中身と照らし合わせて足りない材料を表示します。</p>

            <div class="relative">
                <input type="text"
                    wire:model.live.debounce.300ms="query"
                    placeholder="例：肉じゃが、カレー、パスタ"
                    autocomplete="off"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-base">

                @if($this->searchResults)
                    <ul class="absolute z-10 mt-1 w-full rounded-lg bg-white shadow-lg ring-1 ring-gray-200 overflow-hidden max-h-96 overflow-y-auto">
                        @foreach($this->searchResults as $r)
                            <li>
                                <button type="button"
                                    wire:click="selectRecipe('{{ $r['id'] }}')"
                                    class="w-full text-left px-3 py-2 hover:bg-emerald-50 text-sm flex items-center justify-between">
                                    <span>{{ $r['title'] }}</span>
                                    <span class="text-xs text-gray-500">{{ $r['minutes'] }}分・{{ $r['servings'] }}人前</span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @elseif(trim($query) !== '')
                    <p class="mt-2 text-xs text-gray-500">該当する料理が見つかりません。別のキーワードでお試しください。</p>
                @endif
            </div>
        </section>
    @endif

    @if($this->selectedRecipe)
        @php $recipe = $this->selectedRecipe; @endphp
        @php $breakdown = $this->ingredientBreakdown; @endphp

        <article class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-4 space-y-4">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="text-lg font-bold text-gray-900">{{ $recipe->title }}</h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ $recipe->total_cook_minutes }}分・{{ $recipe->servings_default }}人前
                    </p>
                </div>
                <div class="shrink-0 text-right">
                    @php
                        $kcal = $recipe->nutrientValues?->firstWhere('nutrient.code', 'energy_kcal')?->value_per_serving;
                    @endphp
                    @if($kcal !== null)
                        <div class="text-sm font-semibold text-emerald-700">{{ round((float) $kcal) }} kcal</div>
                        <div class="text-[10px] text-gray-400">/ 人前</div>
                    @endif
                </div>
            </div>

            @if($this->canMake)
                <div class="rounded-lg bg-emerald-50 ring-1 ring-emerald-200 p-3 text-sm text-emerald-900 font-medium">
                    🎉 冷蔵庫にある材料だけで作れます！
                </div>
            @elseif(empty($breakdown['have']))
                <div class="rounded-lg bg-amber-50 ring-1 ring-amber-200 p-3 text-sm text-amber-800">
                    冷蔵庫に該当する材料がありません。買い物が必要です。
                </div>
            @else
                <div class="rounded-lg bg-amber-50 ring-1 ring-amber-200 p-3 text-sm text-amber-800">
                    あと <strong>{{ count(array_filter($breakdown['missing'], fn($m) => ! $m['optional'])) }}</strong> 品買えば作れます。
                </div>
            @endif

            @if(! empty($breakdown['have']))
                <div>
                    <h3 class="text-xs font-semibold text-emerald-700 mb-2">✓ 冷蔵庫にあるもの（{{ count($breakdown['have']) }}品）</h3>
                    <ul class="text-sm space-y-1">
                        @foreach($breakdown['have'] as $h)
                            <li class="flex justify-between bg-emerald-50 rounded px-3 py-1.5">
                                <span class="text-emerald-900">{{ $h['name'] }}</span>
                                <span class="text-emerald-700 text-xs">{{ $h['quantity'] }} {{ $h['unit'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(! empty($breakdown['missing']))
                <div>
                    <h3 class="text-xs font-semibold text-amber-700 mb-2">🛒 足りないもの（{{ count($breakdown['missing']) }}品）</h3>
                    <ul class="text-sm space-y-1">
                        @foreach($breakdown['missing'] as $m)
                            <li class="flex justify-between bg-amber-50 rounded px-3 py-1.5">
                                <span class="text-amber-900">
                                    {{ $m['name'] }}
                                    @if($m['optional'])<span class="text-xs text-gray-500">（任意）</span>@endif
                                </span>
                                <span class="text-amber-700 text-xs">{{ $m['quantity'] }} {{ $m['unit'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @php $instructions = $recipe->instructions_beginner ?: $recipe->instructions; @endphp
            @if($instructions)
                <details class="text-xs text-gray-600">
                    <summary class="cursor-pointer font-medium text-gray-700">作り方を見る</summary>
                    <div class="mt-2 whitespace-pre-line leading-relaxed text-gray-700">{{ $instructions }}</div>
                </details>
            @endif

            <button type="button"
                wire:click="clearSelection"
                class="block w-full rounded-lg bg-white text-emerald-700 ring-1 ring-emerald-300 font-semibold py-2 hover:bg-emerald-50">
                別の料理を探す
            </button>
        </article>
    @endif
</div>
