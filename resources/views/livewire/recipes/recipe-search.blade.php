<div class="mx-auto max-w-md p-4 space-y-6">
    <x-app-header title="料理から探す" />

    @if(! $this->selectedRecipe)
        <section class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-4 space-y-3">
            <p class="text-sm text-gray-700">作りたい料理を入力すると、冷蔵庫の中身と照らし合わせて足りない材料を表示します。</p>

            <div class="relative">
                <input type="text"
                    wire:model.live.debounce.300ms="query"
                    placeholder="例：肉じゃが、カレー、パスタ"
                    autocomplete="off"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-rose-400 focus:ring-rose-300 text-base">

                @if($this->searchResults)
                    <ul class="absolute z-10 mt-1 w-full rounded-lg bg-white shadow-lg ring-1 ring-gray-200 overflow-hidden max-h-96 overflow-y-auto">
                        @foreach($this->searchResults as $r)
                            <li>
                                <button type="button"
                                    wire:click="selectRecipe('{{ $r['id'] }}')"
                                    class="w-full text-left px-3 py-2 hover:bg-rose-50 text-sm flex items-center justify-between gap-2">
                                    <span class="flex-1 truncate">{{ $r['title'] }}</span>
                                    @if(! empty($r['attribution_label']))
                                        <span class="text-[10px] bg-rose-100 text-rose-800 rounded-full px-1.5 py-0.5">{{ $r['attribution_label'] }}</span>
                                    @endif
                                    <span class="text-xs text-gray-500">{{ $r['minutes'] ? $r['minutes'].'分・' : '' }}{{ $r['servings'] }}人前</span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @elseif(trim($query) !== '')
                    <p class="mt-2 text-xs text-gray-500">該当する料理が見つかりません。下の外部サイトもお試しください。</p>
                @endif
            </div>

            {{-- 外部レシピサイトでの検索リンク --}}
            @if(trim($query) !== '')
                <div class="pt-2 border-t border-gray-100">
                    <p class="text-xs font-semibold text-gray-700 mb-2">外部サイトで「{{ $query }}」を検索</p>
                    <div class="grid grid-cols-2 gap-2">
                        <a href="https://cookpad.com/search/{{ urlencode($query) }}" target="_blank" rel="noopener noreferrer"
                            class="block rounded-lg bg-orange-100 text-orange-900 text-center font-semibold py-2 text-sm hover:bg-orange-200">
                            クックパッドで探す →
                        </a>
                        <a href="https://recipe.rakuten.co.jp/search/{{ urlencode($query) }}" target="_blank" rel="noopener noreferrer"
                            class="block rounded-lg bg-rose-100 text-rose-900 text-center font-semibold py-2 text-sm hover:bg-rose-200">
                            楽天レシピで探す →
                        </a>
                    </div>
                </div>
            @endif
        </section>

        {{-- マイレシピへの導線 --}}
        <section class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-4">
            <a href="/my-recipes" class="flex items-center justify-between text-sm font-semibold text-gray-700 hover:text-rose-700">
                <span>📒 マイレシピを管理する</span>
                <span>→</span>
            </a>
            <p class="text-xs text-gray-500 mt-1">自分だけのオリジナルレシピを登録できます。</p>
        </section>
    @endif

    @if($this->selectedRecipe)
        @php $recipe = $this->selectedRecipe; @endphp
        @php $breakdown = $this->ingredientBreakdown; @endphp

        @php $isRakuten = $recipe->source_type === 'rakuten'; @endphp

        <article class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 overflow-hidden">
            @if($recipe->image_url)
                <img src="{{ $recipe->image_url }}" alt="{{ $recipe->title }}" class="w-full h-48 object-cover">
            @endif

            <div class="p-4 space-y-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="text-lg font-bold text-gray-900">{{ $recipe->title }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">
                            @if($recipe->total_cook_minutes){{ $recipe->total_cook_minutes }}分・@endif{{ $recipe->servings_default }}人前
                            @if($recipe->attribution_label)
                                <span class="ml-1 inline-block bg-rose-100 text-rose-800 rounded-full px-2 py-0.5">{{ $recipe->attribution_label }}</span>
                            @endif
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

                @if($isRakuten)
                    {{-- 楽天レシピ: 材料は文字列のままなので照合せず一覧表示 --}}
                    <div class="rounded-lg bg-rose-50 ring-1 ring-rose-200 p-3 text-xs text-rose-900">
                        楽天レシピは在庫照合できません。材料リストと出典をご覧ください。
                    </div>

                    @if(! empty($recipe->materials_text))
                        <div>
                            <h3 class="text-xs font-semibold text-gray-700 mb-2">材料</h3>
                            <ul class="text-sm space-y-1">
                                @foreach($recipe->materials_text as $m)
                                    <li class="bg-gray-50 rounded px-3 py-1.5 text-gray-800">{{ $m }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @else
                    {{-- 標準レシピ: 在庫照合あり --}}
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
                @endif

                @php $instructions = $recipe->instructions_beginner ?: $recipe->instructions; @endphp
                @if($instructions)
                    <details class="text-xs text-gray-600">
                        <summary class="cursor-pointer font-medium text-gray-700">作り方を見る</summary>
                        <div class="mt-2 whitespace-pre-line leading-relaxed text-gray-700">{{ $instructions }}</div>
                    </details>
                @endif

                @if($recipe->attribution_url)
                    <a href="{{ $recipe->attribution_url }}" target="_blank" rel="noopener noreferrer"
                        class="block w-full text-center rounded-lg bg-rose-200 text-rose-900 font-semibold py-2.5 hover:bg-rose-300">
                        {{ $recipe->attribution_label ?: '出典' }}で詳細を見る →
                    </a>
                @endif

                <button type="button"
                    wire:click="clearSelection"
                    class="block w-full rounded-lg bg-white text-gray-700 ring-1 ring-gray-300 font-semibold py-2 hover:bg-gray-50">
                    別の料理を探す
                </button>
            </div>
        </article>
    @endif
</div>
