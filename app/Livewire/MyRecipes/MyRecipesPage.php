<?php

namespace App\Livewire\MyRecipes;

use App\Models\Recipe;
use App\Services\Bedrock\Contracts\BedrockClient;
use App\Services\Recipes\MaterialParser;
use App\Services\Recipes\RecipeIngredientLinker;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
class MyRecipesPage extends Component
{
    public ?string $editingRecipeId = null;

    #[Validate('required|string|max:255')]
    public string $title = '';

    public string $materialsRaw = '';

    public string $instructions = '';

    #[Validate('required|integer|min:1|max:20')]
    public int $servings = 2;

    public ?int $cookMinutes = null;

    public bool $savedFlash = false;

    public bool $showForm = false;

    /** @var array{linked:int,skipped:int,unknown_units:int}|null */
    public ?array $lastParseResult = null;

    public bool $aiParseFlash = false;

    public function parseWithAi(BedrockClient $bedrock): void
    {
        if (trim($this->materialsRaw) === '') {
            return;
        }
        $result = $bedrock->parseIngredients($this->materialsRaw);
        if ($result !== '') {
            $this->materialsRaw = $result;
            $this->aiParseFlash = true;
        }
    }

    #[Computed]
    public function myRecipes(): Collection
    {
        return Recipe::where('source_type', 'user_created')
            ->where('created_by_user_id', Auth::id())
            ->orderByDesc('updated_at')
            ->get();
    }

    public function newRecipe(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(string $recipeId): void
    {
        $recipe = Recipe::where('source_type', 'user_created')
            ->where('created_by_user_id', Auth::id())
            ->where('id', $recipeId)
            ->firstOrFail();

        $this->editingRecipeId = $recipe->id;
        $this->title = $recipe->title;
        $this->materialsRaw = collect($recipe->materials_text ?? [])->implode("\n");
        $this->instructions = $recipe->instructions ?? '';
        $this->servings = (int) $recipe->servings_default;
        $this->cookMinutes = $recipe->total_cook_minutes;
        $this->showForm = true;
        $this->resetErrorBag();
    }

    public function save(MaterialParser $parser, RecipeIngredientLinker $linker): void
    {
        $this->validate();

        $materials = collect(explode("\n", $this->materialsRaw))
            ->map(fn ($l) => trim($l))
            ->filter(fn ($l) => $l !== '')
            ->values()
            ->all();

        $data = [
            'title' => $this->title,
            'locale' => 'ja-JP',
            'servings_default' => $this->servings,
            'total_cook_minutes' => $this->cookMinutes,
            'instructions' => $this->instructions,
            'materials_text' => $materials,
            'attribution_label' => 'マイレシピ',
            'created_by_user_id' => Auth::id(),
        ];

        if ($this->editingRecipeId) {
            Recipe::where('source_type', 'user_created')
                ->where('created_by_user_id', Auth::id())
                ->where('id', $this->editingRecipeId)
                ->update($data);
            $recipe = Recipe::findOrFail($this->editingRecipeId);
        } else {
            $recipe = Recipe::create($data + ['source_type' => 'user_created']);
        }

        // 材料テキストをパース → 食材マスタにリンク → recipe_ingredients を作成
        $parsed = $parser->parseMultiline($this->materialsRaw);
        $this->lastParseResult = $linker->syncToRecipe($recipe, $parsed);

        $this->resetForm();
        $this->showForm = false;
        $this->savedFlash = true;
        unset($this->myRecipes);
    }

    public function delete(string $recipeId): void
    {
        Recipe::where('source_type', 'user_created')
            ->where('created_by_user_id', Auth::id())
            ->where('id', $recipeId)
            ->delete();
        unset($this->myRecipes);
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    private function resetForm(): void
    {
        $this->editingRecipeId = null;
        $this->title = '';
        $this->materialsRaw = '';
        $this->instructions = '';
        $this->servings = 2;
        $this->cookMinutes = null;
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.my-recipes.my-recipes-page');
    }
}
