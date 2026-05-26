<div class="mx-auto max-w-md p-4 space-y-6">
    <x-app-header title="マイレシピ" />

    @if($this->savedFlash)
        <div class="rounded-lg bg-rose-100 text-rose-900 px-4 py-3 text-sm ring-1 ring-rose-200 space-y-1">
            <p class="font-semibold">✓ 保存しました</p>
            @if($this->lastParseResult)
                @php $p = $this->lastParseResult; @endphp
                <p class="text-xs">
                    材料の自動リンク: 在庫照合できる <strong>{{ $p['linked'] }}</strong> 件
                    @if($p['skipped'] > 0) ／ 数量不明 {{ $p['skipped'] }} 件 @endif
                    @if($p['unknown_units'] > 0) ／ 単位不明 {{ $p['unknown_units'] }} 件 @endif
                </p>
            @endif
        </div>
    @endif

    {{-- 追加・編集フォーム --}}
    @if($showForm)
        <section class="rounded-xl bg-white shadow-sm ring-1 ring-rose-200 p-4 space-y-3">
            <h2 class="text-sm font-semibold text-gray-700">
                {{ $editingRecipeId ? 'レシピを編集' : '新しいレシピを登録' }}
            </h2>

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">料理名 <span class="text-rose-500">*</span></label>
                <input type="text" wire:model="title"
                    placeholder="例：おばあちゃんの肉じゃが"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-rose-400 focus:ring-rose-300 text-base">
                @error('title') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">材料（1 行ずつ／クックパッドからのペースト可）</label>
                <textarea wire:model="materialsRaw" rows="6"
                    placeholder="豚バラ肉 200g&#10;じゃがいも 3個&#10;玉ねぎ 1個&#10;醤油 大さじ3"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-rose-400 focus:ring-rose-300 text-base"></textarea>

                <button type="button"
                    wire:click="parseWithAi"
                    wire:loading.attr="disabled"
                    wire:target="parseWithAi"
                    class="mt-2 inline-flex items-center gap-1 rounded-lg bg-rose-100 text-rose-900 text-xs font-semibold px-3 py-1.5 hover:bg-rose-200 disabled:opacity-50">
                    <span wire:loading.remove wire:target="parseWithAi">🤖 AI で材料を整形</span>
                    <span wire:loading wire:target="parseWithAi">解析中…</span>
                </button>
                @if($aiParseFlash)
                    <span class="ml-2 text-xs text-emerald-700">✓ 整形しました（保存前に内容を確認してください）</span>
                @endif
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">作り方</label>
                <textarea wire:model="instructions" rows="5"
                    placeholder="1. じゃがいもは一口大に切る。&#10;2. 鍋に油を熱し、豚肉を炒める。"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-rose-400 focus:ring-rose-300 text-base"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">人数 <span class="text-rose-500">*</span></label>
                    <input type="number" min="1" max="20" wire:model="servings"
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-rose-400 focus:ring-rose-300 text-base">
                    @error('servings') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">調理時間（分）</label>
                    <input type="number" min="1" max="600" wire:model="cookMinutes"
                        placeholder="30"
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-rose-400 focus:ring-rose-300 text-base">
                </div>
            </div>

            <div class="flex gap-2 pt-1">
                <button type="button" wire:click="cancel"
                    class="flex-1 rounded-lg bg-white text-gray-700 ring-1 ring-gray-300 font-semibold py-2 hover:bg-gray-50">
                    キャンセル
                </button>
                <button type="button" wire:click="save"
                    class="flex-1 rounded-lg bg-rose-200 text-rose-900 font-semibold py-2 hover:bg-rose-300">
                    {{ $editingRecipeId ? '更新する' : '登録する' }}
                </button>
            </div>
        </section>
    @else
        <button type="button" wire:click="newRecipe"
            class="block w-full rounded-lg bg-rose-200 text-rose-900 font-semibold py-3 hover:bg-rose-300">
            ＋ 新しいレシピを登録
        </button>
    @endif

    {{-- レシピ一覧 --}}
    <section class="space-y-3">
        <h2 class="text-sm font-semibold text-gray-600">登録済みレシピ（{{ $this->myRecipes->count() }}件）</h2>

        @forelse($this->myRecipes as $recipe)
            <article class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-4 space-y-2">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="font-bold text-gray-900">{{ $recipe->title }}</h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            @if($recipe->total_cook_minutes){{ $recipe->total_cook_minutes }}分・@endif{{ $recipe->servings_default }}人前
                        </p>
                    </div>
                </div>

                @if(! empty($recipe->materials_text))
                    <details class="text-xs text-gray-600">
                        <summary class="cursor-pointer text-gray-700">材料（{{ count($recipe->materials_text) }}品）</summary>
                        <ul class="mt-2 space-y-0.5">
                            @foreach($recipe->materials_text as $m)
                                <li>・{{ $m }}</li>
                            @endforeach
                        </ul>
                    </details>
                @endif

                @if($recipe->instructions)
                    <details class="text-xs text-gray-600">
                        <summary class="cursor-pointer text-gray-700">作り方</summary>
                        <div class="mt-2 whitespace-pre-line leading-relaxed">{{ $recipe->instructions }}</div>
                    </details>
                @endif

                <div class="flex justify-end gap-2 pt-1">
                    <button type="button" wire:click="edit('{{ $recipe->id }}')"
                        class="text-xs text-rose-700 hover:text-rose-900 px-2 py-1">編集</button>
                    <button type="button" wire:click="delete('{{ $recipe->id }}')"
                        wire:confirm="このレシピを削除しますか？"
                        class="text-xs text-red-600 hover:text-red-800 px-2 py-1">削除</button>
                </div>
            </article>
        @empty
            <p class="text-center text-gray-500 py-8">まだレシピが登録されていません。</p>
        @endforelse
    </section>
</div>
